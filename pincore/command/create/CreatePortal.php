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


namespace pinoox\command\create;


use JetBrains\PhpStorm\ArrayShape;
use pinoox\component\Console;
use pinoox\component\helpers\HelperString;
use pinoox\component\helpers\PhpFile\PortalFile;
use pinoox\component\helpers\Str;
use pinoox\component\interfaces\CommandInterface;

class CreatePortal extends console implements CommandInterface
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "create:portal";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a new Portal class";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['PortalName', true, 'Portal Name'],
    ];

    protected $options = [
        ['package', 'p', 'change package name for example:[--package=com_pinoox_welcome | --p=com_pinoox_welcome]', 'default'],
        ['service', 's', 'change service name for example:[--service=view | --s=view]', ''],
    ];

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->setPackageName();
        $portalName = HelperString::toCamelCase($this->argument('PortalName'));
        $serviceName = $this->option('service');
        $serviceName = !empty($serviceName) ? $serviceName : lcfirst($portalName);
        $this->createPortal($portalName, $serviceName, $this->cli['package']);
    }


    private function createPortal(string $portalName, string $serviceName, string $packageName): void
    {
        list(
            'path' => $path,
            'className' => $portalName,
            'namespace' => $namespace
            ) = $this->getStructure($portalName);

        PortalFile::createPortal($path, $portalName, $serviceName, $packageName, $namespace);
        $this->success(sprintf('Portal created in "%s".', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path)));
        $this->newLine();
    }

    #[ArrayShape(['path' => "string", 'className' => "string", 'namespace' => "mixed|string"])] private function getStructure(string $portalName): array
    {
        $parts = Str::multiExplode(['/', '>','\\'], $portalName);
        $portalName = array_pop($parts);
        $folder = implode('\\', $parts);
        $className = ucfirst($portalName);

        $basePath = $this->cli['path'] . '\\portal\\';
        $basePath = !empty($folder) ? $basePath . $folder . '\\' : $basePath;

        $namespace = $this->cli['namespace'];
        $namespace = !empty($folder) ? $namespace . '\\' . $folder : $namespace;

        $pathFile = $basePath . $className . '.php';
        return ['path' => $pathFile, 'className' => $className, 'namespace' => $namespace];
    }

    private function setPackageName()
    {
        $packageName = $this->option('package');
        $packageName = empty($packageName) || $packageName == 'default' ? null : $packageName;
        $this->chooseApp('pincore');
    }
}