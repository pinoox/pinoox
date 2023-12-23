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


namespace pinoox\component\package\engine;


use pinoox\component\package\AppManager;
use pinoox\component\package\loader\ArrayLoader;
use pinoox\component\package\loader\ChainLoader;
use pinoox\component\package\loader\LoaderInterface;
use pinoox\component\package\loader\PackageLoader;
use pinoox\component\Path\reference\ReferenceInterface;
use pinoox\component\Path\Manager\PathManager;
use pinoox\component\router\Router;
use pinoox\component\store\config\Config;
use pinoox\component\store\config\strategy\FileConfigStrategy;
use pinoox\component\store\baker\Pinker;
use Exception;
use pinoox\portal\app\App;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use pinoox\component\store\config\ConfigInterface;

class AppEngine implements EngineInterface
{
    private LoaderInterface $loader;
    private PackageLoader $packageLoader;
    private ArrayLoader $arrayLoader;
    /**
     * @var PathManager[]
     */
    private array $pathManager;

    /**
     * @var AppManager[]
     */
    private array $appManager;
    /**
     * @var ConfigInterface[]
     */
    private array $appConfig;
    /**
     * @var Router[]
     */
    private array $router;

    /**
     * AppEngine constructor.
     *
     * @param string $pathApp
     * @param array $defaultData
     * @param string $appFile
     * @param string $folderPinker
     */
    public function __construct(private string $pathApp, private string $appFile, private string $folderPinker, private array $defaultData = [])
    {
        $this->arrayLoader = new ArrayLoader($appFile);
        $this->packageLoader = new PackageLoader($appFile, $pathApp);

        $this->loader = new ChainLoader([
            $this->arrayLoader,
            $this->packageLoader,
        ]);
    }

    public function stable(string|ReferenceInterface $packageName): bool
    {
        $enable = false;

        if ($this->exists($packageName)) {
            try {
                $enable = (bool)$this->config($packageName)->get('enable');
            } catch (Exception $e) {
            }
        }

        return $enable === true;
    }

    public function routes(ReferenceInterface|string $packageName, string $path = ''): Router
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();
        $key = $packageName . ':' . $path;
        if (empty($this->router[$key])) {
            $this->router[$key] = App::meeting($packageName, function () {
                return \pinoox\portal\Router::___();
            }, $path);
        }
        return $this->router[$key];
    }

    public function manager(ReferenceInterface|string $packageName): AppManager
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();

        if (empty($this->appManager[$packageName]))
            $this->appManager[$packageName] = new AppManager($this, $packageName);

        return $this->appManager[$packageName];
    }

    /**
     * Get config app.
     *
     * @param ReferenceInterface|string $packageName
     * @return ConfigInterface
     * @throws \Exception
     */
    public function config(ReferenceInterface|string $packageName): ConfigInterface
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();

        if (empty($this->appConfig[$packageName])) {
            $mainFile = $this->path($packageName, $this->appFile);
            $bakedFile = $this->path($packageName, $this->folderPinker . '/' . $this->appFile);
            $pinker = new Pinker($mainFile, $bakedFile);
            $pinker
                ->dumping(true);
            $fileStrategy = new FileConfigStrategy($pinker);
            $config = new Config($fileStrategy);
            $config->merge($this->defaultData);
            $config->set('package', $packageName);
            $this->appConfig[$packageName] = $config;
        }
        return $this->appConfig[$packageName];
    }


    /**
     * Exists app.
     *
     * @param ReferenceInterface|string $packageName
     * @return bool
     */
    public function exists(ReferenceInterface|string $packageName): bool
    {
        return $this->loader->exists($packageName);
    }

    /**
     * add app
     *
     * @param string $packageName
     * @param string $path
     */
    public function add(string $packageName, string $path): void
    {
        $this->arrayLoader->add($packageName, $path);
    }

    /**
     * Get path app.
     *
     * @param ReferenceInterface|string $packageName
     * @return string
     * @throws \Exception
     */
    private function pathPackage(ReferenceInterface|string $packageName): string
    {
        return $this->loader->path($packageName);
    }

    /**
     * Get path app.
     *
     * @param ReferenceInterface|string $packageName
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function path(ReferenceInterface|string $packageName, string $path = ''): string
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();

        if (empty($this->pathManager[$packageName])) {
            $basePath = $this->pathPackage($packageName);
            $this->pathManager[$packageName] = new PathManager($basePath);
        }
        return ($this->pathManager[$packageName])->get($path);
    }

    /**
     * Supports app.
     *
     * @param ReferenceInterface|string $packageName
     * @return bool
     */
    public function supports(ReferenceInterface|string $packageName): bool
    {
        return $this->checkName($packageName) && $this->exists($packageName);
    }

    /**
     * Check valid package name
     *
     * @param string $packageName
     * @return bool
     */
    private function checkName(string $packageName): bool
    {
        return !!preg_match('/^[a-zA-Z]+[a-zA-Z0-9]*+[_]\s{0,1}[a-zA-Z0-9]+[_]\s{0,1}[a-zA-Z0-9]+[_]{0,1}[a-zA-Z0-9]+$/m', $packageName);
    }


    public function all(): array
    {
        $files = [];
        try {
            $files = (new Finder())->in($this->pathApp)->depth(1)->files();

        } catch (DirectoryNotFoundException $e) {
        }

        $result = [];
        $arrayPackages = $this->arrayLoader->getPackages();
        foreach ($arrayPackages as $package => $path) {
            if ($this->exists($package)) {
                $result[$package] = $this->manager($package);
            }
        }
        /**
         * @var SplFileInfo $file
         */
        foreach ($files as $file) {
            $package = $file->getRelativePath();
            if ($this->supports($package)) {
                $result[$package] = $this->manager($package);
            }
        }
        return $result;
    }
}