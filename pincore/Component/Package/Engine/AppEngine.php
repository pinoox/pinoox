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


namespace Pinoox\Component\Package\Engine;


use Pinoox\Component\Lang\Lang;
use Pinoox\Component\Package\AppManager;
use Pinoox\Component\Package\Loader\ArrayLoader;
use Pinoox\Component\Package\Loader\ChainLoader;
use Pinoox\Component\Package\Loader\LoaderInterface;
use Pinoox\Component\Package\Loader\PackageLoader;
use Pinoox\Component\Path\Reference\ReferenceInterface;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Store\Config\Config;
use Pinoox\Component\Store\Config\Strategy\FileConfigStrategy;
use Pinoox\Component\Store\Baker\Pinker;
use Exception;
use Pinoox\Component\Translator\Loader\FileLoader;
use Pinoox\Component\Translator\Translator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use Pinoox\Component\Store\Config\ConfigInterface;

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
     * @var Lang[]
     */
    private array $appLang;

    /**
     * @var Router[][]
     */
    private array $router;
    private array $defaultData = [];

    /**
     * AppEngine constructor.
     *
     * @param string $pathApp
     * @param array $defaultData
     * @param string $appFile
     * @param string $folderPinker
     */
    public function __construct(private string $pathApp, private string $appFile, private string $folderPinker, ?array $defaultData = null)
    {
        $this->arrayLoader = new ArrayLoader($appFile);
        $this->packageLoader = new PackageLoader($appFile, $pathApp);
        $this->initDefaultData($defaultData);

        $this->loader = new ChainLoader([
            $this->arrayLoader,
            $this->packageLoader,
        ]);
    }

    private function initDefaultData(?array $data): void
    {
        if (is_null($data)) {
            $data = include __DIR__ . '/../data/source.php';
        }

        $this->defaultData = $data;
    }

    public function getDefaultData() : array
    {
        return $this->defaultData;
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

    public function getAllRouters(ReferenceInterface|string $packageName): array
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();
        return !empty($this->router[$packageName]) ? $this->router[$packageName] : [];
    }

    private function buildPath(string $path)
    {
        $path = array_filter(explode('/', $path));
        $path = implode('/', $path);
        return !empty($path) ? '/' . $path : '/';
    }

    public function router(ReferenceInterface|string $packageName, string $path = '/'): Router
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();
        $path = $this->buildPath($path);
        $routes = $this->config($packageName)->get('router.routes');
        if (empty($this->router[$packageName][$path])) {
            $this->router[$packageName][$path] = \Pinoox\Portal\Router::build($path, $routes);
        }
        return $this->router[$packageName][$path];
    }

    public function manager(ReferenceInterface|string $packageName): AppManager
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();

        if (empty($this->appManager[$packageName]))
            $this->appManager[$packageName] = new AppManager($this, $packageName);

        return $this->appManager[$packageName];
    }

    /**
     * @throws Exception
     */
    public function lang(ReferenceInterface|string $packageName): Translator
    {
        $packageName = is_string($packageName) ? $packageName : $packageName->getPackageName();

        if (empty($this->appLang[$packageName])) {

            $path = $this->path($packageName, 'lang');
            $locale = $this->config($packageName)->get('lang');
            $loader = new FileLoader($path, '.lang');
            $this->appLang[$packageName] = new Translator($loader, $locale);
        }

        return $this->appLang[$packageName];
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
    public function checkName(string $packageName): bool
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