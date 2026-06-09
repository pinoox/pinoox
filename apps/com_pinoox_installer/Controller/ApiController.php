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
use App\com_pinoox_installer\Request\SetupRequest;
use Pinoox\Component\Kernel\Controller\ApiController as BaseApiController;
use Pinoox\PinDoc\Api\Attribute\ApiBody;
use Pinoox\PinDoc\Api\Attribute\ApiEndpoint;
use Pinoox\PinDoc\Api\Attribute\ApiParam;
use Pinoox\PinDoc\Api\Attribute\ApiResponse;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Logger;

class ApiController extends BaseApiController
{
    public function changeLang(string $lang): JsonResponse
    {
        $lang = strtolower($lang);
        App::set('lang', $lang)->save();

        return $this->resource(new LangResource($this->langPayload($lang)));
    }

    public function checkDB(Request $request): JsonResponse
    {
        $input = InstallerDatabase::readFromRequest($request);

        if (InstallerDatabase::testConnection($input)) {
            return $this->ok(['connected' => true], 'install.connect_to_database', translate: true);
        }

        return $this->fail(
            'DB_CONNECTION_FAILED',
            'install.err_connect_to_database',
            status: 422,
            translate: true,
        );
    }

    public function ping(): JsonResponse
    {
        return $this->resource(new PingResource(['timestamp' => time()]));
    }

    public function bootstrapDiagnostics(): JsonResponse
    {
        return $this->resource(new PayloadResource((new BootstrapDiagnostics())->run()));
    }

    public function checkAllPrerequisites(): JsonResponse
    {
        return $this->resource(new PayloadResource((new PrerequisitesChecker())->checkAll()));
    }

    public function checkPrerequisites(string $type): JsonResponse
    {
        $result = (new PrerequisitesChecker())->check($type);

        return $this->resource(new PayloadResource(array_merge(['type' => $type], $result)));
    }

    public function htaccessStatus(): JsonResponse
    {
        return $this->resource(new PayloadResource((new HtaccessManager())->status()));
    }

    public function htaccessCreate(): JsonResponse
    {
        return $this->resource(new PayloadResource((new HtaccessManager())->create()));
    }

    public function agreement(): JsonResponse
    {
        return $this->resource(new AgreementResource(t('agreement')));
    }

    public function setup(SetupRequest $request): JsonResponse
    {
        $data = $request->validated();
        $db = InstallerDatabase::readForSetup($request, $data['db'] ?? []);
        $user = $data['user'] ?? [];

        try {
            SetupService::make()->run(
                $db,
                $user,
                App::get('lang'),
            );
        } catch (SetupException $e) {
            return $this->fail('SETUP_FAILED', $e->messageKey(), translate: true);
        } catch (\Throwable $e) {
            Logger::error('Installer setup failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->fail('SETUP_FAILED', 'install.err_insert_tables', translate: true);
        }

        return $this->ok(['installed' => true], 'install.setup_success', translate: true);
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

