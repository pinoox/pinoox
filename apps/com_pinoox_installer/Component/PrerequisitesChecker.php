<?php

/**

 *      ****  *  *     *  ****  ****  *    *

 *      *  *  *  * *   *  *  *  *  *   *  *

 *      ****  *  *  *  *  *  *  *  *    *

 *      *     *  *   * *  *  *  *  *  *   *  *

 *      *     *  *    **  ****  ****  *    *

 * @author   Pinoox

 * @link https://www.pinoox.com/

 * @license  https://opensource.org/licenses/MIT MIT License

 */



namespace App\com_pinoox_installer\Component;



use Pinoox\Component\Router\QueryRouteResolver;



class PrerequisitesChecker

{

    private const MIN_FREE_SPACE_MB = 50;



    private function loadRequirements(): void

    {

        if (function_exists('pinoox_min_php_version')) {

            return;

        }



        require_once dirname(__DIR__, 3) . '/pincore/bootstrap/requirements.php';

    }



    private function minPhpVersion(): string

    {

        $this->loadRequirements();



        return pinoox_min_php_version();

    }



    private function composerPhpConstraint(): string

    {

        $this->loadRequirements();



        return pinoox_composer_php_constraint();

    }



    public function checkAll(): array

    {

        $items = [

            'free_space' => $this->checkFreeSpace(),

            'php' => $this->checkPhp(),

            'mod_rewrite' => $this->checkUrlRewrite(),

            'mysql' => $this->checkMysql(),

        ];



        return [

            'items' => $items,

            'canContinue' => $this->canContinue($items),

        ];

    }



    public function check(string $type): array

    {

        return match ($type) {

            'free_space' => $this->checkFreeSpace(),

            'php' => $this->checkPhp(),

            'mod_rewrite' => $this->checkUrlRewrite(),

            'mysql' => $this->checkMysql(),

            default => $this->unknown(),

        };

    }



    public function canContinue(array $items): bool

    {

        foreach ($items as $item) {

            if (($item['state'] ?? '') === 'fail') {

                return false;

            }

        }



        return true;

    }



    private function checkPhp(): array

    {

        $version = PHP_VERSION;

        $minimum = $this->minPhpVersion();

        $pass = version_compare($version, $minimum, '>=');



        return array_merge(

            $this->result($pass ? 'pass' : 'fail', $version, $version),

            [

                'required' => $minimum,

                'composer_constraint' => $this->composerPhpConstraint(),

            ]

        );

    }



    private function checkFreeSpace(): array

    {

        $path = $this->writablePath();

        $free = @disk_free_space($path);



        if ($free === false) {

            return $this->unknown('shared_hosting', null);

        }



        $mb = $free / 1024 / 1024;

        $current = round($mb, 1) . ' MB';



        return $this->result(

            $mb >= self::MIN_FREE_SPACE_MB ? 'pass' : 'fail',

            $current,

            $current

        );

    }



    private function checkUrlRewrite(): array

    {

        $routingActive = $this->isRoutingActive();

        $server = $this->detectWebServer();



        if ($server['type'] === 'apache') {

            $apacheRewrite = $this->apacheRewriteStatus();



            if ($apacheRewrite === true) {

                return $this->rewriteResult(

                    'pass',

                    'Apache mod_rewrite',

                    'Apache mod_rewrite',

                    $server,

                    $routingActive

                );

            }



            if ($apacheRewrite === false) {

                return $this->rewriteUnknown(

                    $server,

                    $routingActive,

                    $routingActive ? 'Apache' : 'manual_verify'

                );

            }

        }



        if ($routingActive) {

            return $this->rewriteUnknown(

                $server,

                true,

                $server['label'] ?? null

            );

        }



        if ($server['detected']) {

            return $this->rewriteUnknown($server, false, $server['label']);

        }



        return $this->rewriteUnknown($server, false, null);

    }



    /**

     * @return bool|null true = enabled, false = disabled, null = cannot detect

     */

    private function apacheRewriteStatus(): ?bool

    {

        if (!function_exists('apache_get_modules')) {

            return null;

        }



        $modules = @apache_get_modules();



        if (!is_array($modules)) {

            return null;

        }



        return in_array('mod_rewrite', $modules, true);

    }



    private function detectWebServer(): array

    {

        $software = strtolower(trim((string) ($_SERVER['SERVER_SOFTWARE'] ?? '')));



        $servers = [

            ['needles' => ['apache', 'httpd'], 'type' => 'apache', 'label' => 'Apache'],

            ['needles' => ['nginx'], 'type' => 'nginx', 'label' => 'nginx'],

            ['needles' => ['microsoft-iis', 'iis/'], 'type' => 'iis', 'label' => 'IIS'],

            ['needles' => ['openlitespeed'], 'type' => 'litespeed', 'label' => 'OpenLiteSpeed'],

            ['needles' => ['litespeed'], 'type' => 'litespeed', 'label' => 'LiteSpeed'],

            ['needles' => ['caddy'], 'type' => 'caddy', 'label' => 'Caddy'],

            ['needles' => ['lighttpd'], 'type' => 'lighttpd', 'label' => 'lighttpd'],

            ['needles' => ['cherokee'], 'type' => 'cherokee', 'label' => 'Cherokee'],

            ['needles' => ['tomcat'], 'type' => 'tomcat', 'label' => 'Apache Tomcat'],

            ['needles' => ['cloudflare'], 'type' => 'cloudflare', 'label' => 'Cloudflare'],

            ['needles' => ['microsoft'], 'type' => 'iis', 'label' => 'IIS'],

        ];



        foreach ($servers as $entry) {

            foreach ($entry['needles'] as $needle) {

                if ($software !== '' && str_contains($software, $needle)) {

                    return $this->serverInfo($entry['type'], $entry['label'], $software);

                }

            }

        }



        if (PHP_SAPI === 'cli-server') {

            return $this->serverInfo('php-builtin', 'PHP built-in server', $software);

        }



        if (function_exists('apache_get_modules')) {

            return $this->serverInfo('apache', 'Apache', $software ?: 'Apache');

        }



        if ($software !== '') {

            return $this->serverInfo('other', $software, $software);

        }



        return $this->serverInfo('unknown', null, null);

    }



    private function serverInfo(string $type, ?string $label, ?string $software): array

    {

        return [

            'type' => $type,

            'label' => $label,

            'detected' => $type !== 'unknown',

            'software' => $software,

        ];

    }



    private function rewriteUnknown(array $server, bool $routingActive, ?string $current): array

    {

        $detail = $server['label'] ?? $server['software'] ?? null;



        return $this->rewriteResult(

            'unknown',

            $detail,

            $current ?? $detail,

            $server,

            $routingActive

        );

    }



    private function rewriteResult(

        string $state,

        ?string $detail,

        ?string $current,

        array $server,

        bool $routingActive

    ): array {

        return [

            'state' => $state,

            'detail' => $detail,

            'current' => $current ?? $detail,

            'status' => $state === 'pass',

            'routing_active' => $routingActive,

            'server' => $server['label'] ?? $server['software'] ?? null,

            'server_type' => $server['type'],

            'server_detected' => $server['detected'],

        ];

    }



    private function isRoutingActive(): bool

    {

        if (QueryRouteResolver::wasApplied()) {

            return false;

        }



        if (isset($_SERVER['REDIRECT_STATUS']) && (int) $_SERVER['REDIRECT_STATUS'] === 200) {

            return true;

        }



        if (!empty($_SERVER['REDIRECT_URL'])) {

            return true;

        }



        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));



        if (str_contains($uri, '/api/') && $script === 'index.php') {

            return true;

        }



        if (!empty($_SERVER['PATH_INFO'])) {

            return true;

        }



        return false;

    }



    private function checkMysql(): array

    {

        if (extension_loaded('pdo_mysql')) {

            return $this->result('pass', 'PDO MySQL', 'PDO MySQL');

        }



        if (extension_loaded('mysqli')) {

            return $this->result('pass', 'MySQLi', 'MySQLi');

        }



        return $this->result('fail', null, 'none');

    }



    private function writablePath(): string

    {

        if (function_exists('path')) {

            $root = @path('~');



            if (is_string($root) && $root !== '' && is_dir($root)) {

                return $root;

            }

        }



        $candidates = [

            dirname(__DIR__, 3),

            sys_get_temp_dir(),

        ];



        foreach ($candidates as $candidate) {

            if (is_string($candidate) && $candidate !== '' && is_dir($candidate)) {

                return $candidate;

            }

        }



        return '.';

    }



    private function result(string $state, ?string $detail = null, ?string $current = null): array

    {

        return [

            'state' => $state,

            'detail' => $detail,

            'current' => $current ?? $detail,

            'status' => $state === 'pass',

        ];

    }



    private function unknown(?string $detail = null, ?string $current = null): array

    {

        return $this->result('unknown', $detail, $current ?? $detail);

    }

}


