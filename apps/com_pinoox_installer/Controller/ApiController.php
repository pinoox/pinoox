<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_installer\Controller;

use App\com_pinoox_installer\Component\BootstrapDiagnostics;
use App\com_pinoox_installer\Component\HtaccessManager;
use App\com_pinoox_installer\Component\InstallerDatabase;
use App\com_pinoox_installer\Component\LangHelper;
use App\com_pinoox_installer\Component\PrerequisitesChecker;
use App\com_pinoox_installer\Component\SetupException;
use App\com_pinoox_installer\Component\SetupService;
use App\com_pinoox_installer\Resource\AgreementResource;
use App\com_pinoox_installer\Resource\LangResource;
use App\com_pinoox_installer\Resource\PingResource;
use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Http\Api\PayloadResource;
use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController as BaseApiController;
use Pinoox\PinDoc\Api\Attribute\ApiBody;
use Pinoox\PinDoc\Api\Attribute\ApiEndpoint;
use Pinoox\PinDoc\Api\Attribute\ApiParam;
use Pinoox\PinDoc\Api\Attribute\ApiResponse;
use Pinoox\Portal\App\App;

class ApiController extends BaseApiController
{
    #[ApiParam(name: 'lang', in: 'path', type: 'string', required: true, description: 'Language code', example: 'en')]
    #[ApiEndpoint(summary: 'Change installer language', description: 'Updates active installer locale.', tag: 'Localization')]
    #[ApiResponse(status: 200, description: 'Updated language payload')]
    public function changeLang(string $lang): JsonResponse
    {
        $lang = strtolower($lang);
        App::set('lang', $lang)->save();

        return $this->resource(new LangResource($this->langPayload($lang)));
    }

    #[ApiEndpoint(summary: 'Validate database connection', description: 'Tests database credentials before installation.', tag: 'Database')]
    #[ApiBody(
        description: 'Database connection settings',
        properties: [
            'host' => 'string',
            'database' => 'string',
            'username' => 'string',
            'password' => 'string',
            'prefix' => 'string',
        ],
        example: [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
        ],
    )]
    #[ApiResponse(status: 200, description: 'Connection result')]
    public function checkDB(Request $request): JsonResponse
    {
        $input = InstallerDatabase::readFromRequest($request);

        if (InstallerDatabase::testConnection($input)) {
            return $this->ok(['connected' => true], 'connect', translate: true);
        }

        return $this->fail('DB_CONNECTION_FAILED', 'disconnect', status: 422);
    }

    #[ApiEndpoint(summary: 'Installer health check', description: 'Returns API reachability and server timestamp.', tag: 'System')]
    #[ApiResponse(status: 200, description: 'Service is healthy')]
    public function ping(): JsonResponse
    {
        return $this->resource(new PingResource(['timestamp' => time()]));
    }

    #[ApiEndpoint(summary: 'Bootstrap diagnostics', description: 'Returns installer bootstrap checks for rewrite, htaccess, and assets.', tag: 'System')]
    #[ApiResponse(status: 200, description: 'Bootstrap diagnostics payload')]
    public function bootstrapDiagnostics(): JsonResponse
    {
        return $this->resource(new PayloadResource((new BootstrapDiagnostics())->run()));
    }

    #[ApiEndpoint(summary: 'Check all prerequisites', description: 'Runs disk, PHP, rewrite, and database extension checks.', tag: 'System')]
    #[ApiResponse(status: 200, description: 'Prerequisites overview')]
    public function checkAllPrerequisites(): JsonResponse
    {
        return $this->resource(new PayloadResource((new PrerequisitesChecker())->checkAll()));
    }

    #[ApiEndpoint(summary: 'Check one prerequisite', description: 'Runs a single prerequisite check by type.', tag: 'System')]
    #[ApiParam(name: 'type', in: 'path', type: 'string', required: true, example: 'rewrite')]
    #[ApiResponse(status: 200, description: 'Single prerequisite result')]
    public function checkPrerequisites(string $type): JsonResponse
    {
        $result = (new PrerequisitesChecker())->check($type);

        return $this->resource(new PayloadResource(array_merge(['type' => $type], $result)));
    }

    #[ApiEndpoint(summary: 'Htaccess status', description: 'Reports whether project .htaccess is present and valid.', tag: 'System')]
    #[ApiResponse(status: 200, description: 'Htaccess status payload')]
    public function htaccessStatus(): JsonResponse
    {
        return $this->resource(new PayloadResource((new HtaccessManager())->status()));
    }

    #[ApiEndpoint(summary: 'Create htaccess', description: 'Writes the default Pinoox .htaccess file when missing.', tag: 'System')]
    #[ApiResponse(status: 200, description: 'Htaccess creation result')]
    public function htaccessCreate(): JsonResponse
    {
        return $this->resource(new PayloadResource((new HtaccessManager())->create()));
    }

    #[ApiEndpoint(summary: 'Installer agreement', description: 'Returns translated agreement text.', tag: 'Localization')]
    #[ApiResponse(status: 200, description: 'Agreement text')]
    public function agreement(): JsonResponse
    {
        return $this->resource(new AgreementResource(t('agreement')));
    }

    #[ApiEndpoint(summary: 'Complete installation', description: 'Persists database config, migrates core/apps, creates admin user, and disables installer.', tag: 'Setup')]
    #[ApiBody(
        description: 'Database and admin user payload',
        properties: [
            'db' => 'object',
            'user' => 'object',
        ],
    )]
    #[ApiResponse(status: 200, description: 'Installation result')]
    public function setup(Request $request): JsonResponse
    {
        $validation = $request->validation([
            'user.fname' => 'required|min:3',
            'user.lname' => 'required|min:3',
            'user.email' => 'required|email',
            'user.username' => 'required|alpha_dash:ascii|min:3',
            'user.password' => 'required|min:6',
            'db.host' => 'required',
            'db.database' => 'required',
            'db.username' => 'required',
        ]);

        if ($validation->fails()) {
            return $this->fail('VALIDATION_FAILED', $validation->errors()->first(), status: 422, translate: false);
        }

        $data = $validation->validate();

        try {
            SetupService::make()->run(
                $data['db'],
                $data['user'],
                App::get('lang'),
            );
        } catch (SetupException $e) {
            return $this->fail('SETUP_FAILED', $e->messageKey());
        } catch (\Throwable) {
            return $this->fail('SETUP_FAILED', 'install.err_insert_tables');
        }

        return $this->ok(['installed' => true], 'success', translate: true);
    }

    /**
     * @return array{direction: string, lang: array<string, mixed>}
     */
    private function langPayload(?string $lang = null): array
    {
        $lang = empty($lang) ? App::get('lang') : $lang;

        return [
            'direction' => LangHelper::direction($lang),
            'lang' => LangHelper::forFrontend($lang),
        ];
    }
}
