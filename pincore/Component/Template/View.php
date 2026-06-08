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

namespace Pinoox\Component\Template;

use Pinoox\Component\Store\Config\Data\DataManager;
use Pinoox\Component\Template\Engine\PhpEngine;
use Pinoox\Component\Template\Engine\PhpTwigEngine;
use Pinoox\Component\Template\Engine\TwigEngine;
use Pinoox\Component\Template\Parser\TemplateNameParser;
use Pinoox\Component\Template\Engine\DelegatingEngine;
use Pinoox\Component\Template\Reference\TemplatePathReference;
use Pinoox\Component\Template\Theme\ThemeAssets;
use Twig\Extension\DebugExtension;
use Twig\Extension\StringLoaderExtension;

class View implements ViewInterface
{
    private DelegatingEngine $template;
    protected array $globals = [];
    protected TemplateNameParser $parser;
    protected PhpEngine $phpEngine;
    protected TwigEngine $twigEngine;
    protected PhpTwigEngine $phpTwigEngine;
    private array $readyRenders = [];
    private array $twigOptions = [];
    private string|array $folders = [];
    private string $pathTheme = '';
    /** @var list<string> */
    private array $themePaths = [];

    /**
     * View constructor.
     *
     * @param string|array $folders Theme folder name(s) or absolute theme paths
     * @param string $pathTheme Base theme directory when $folders are relative names
     */
    public function __construct(string|array $folders, string $pathTheme = '', array $twigOptions = [])
    {
        $this->twigOptions = $twigOptions;
        $this->setView($folders, $pathTheme);
    }

    /**
     * Set View
     * @param string|array $folders
     * @param string $pathTheme
     * @return View
     */
    public function setView(string|array $folders, string $pathTheme = ''): static
    {
        $this->folders = $folders;
        $this->pathTheme = $pathTheme;
        $this->themePaths = $this->resolveThemePaths($folders, $pathTheme);

        // template name parser
        $this->parser = new TemplateNameParser();

        // instance engines
        $this->phpEngine = new PhpEngine($this->parser, $this->themePaths); // .php engine
        $this->twigEngine = new TwigEngine($this->parser, $this->themePaths, $this->twigEnvironmentOptions()); // .twig engine
        $this->phpTwigEngine = new PhpTwigEngine($this->parser, $this->phpEngine, $this->twigEngine); // .twig.php engine

        // set main template engine
        $this->template = new DelegatingEngine([
            $this->phpEngine,
            $this->twigEngine,
            $this->phpTwigEngine
        ]);

        // add twig extensions
        $this->twigEngine->template->enableDebug();
        $this->twigEngine->addExtension(new DebugExtension());
        $this->twigEngine->addExtension(new StringLoaderExtension());

        // add twig functions
        $internalFunctions = $this->twigOption('functions', []);
        $this->twigEngine->addInternalFunction(array_merge($internalFunctions, [
            'url',
            'package',
            'theme',
            'asset',
            'lang' => 't',
            't',
            'config',
            'app',
            'dd',
            'dump',
            'assets',
            'pinoox_bootstrap',
            'pinoox_script',
            'vite',
            'vite_tags',
            'vite_css_tags',
            'seo_tags',
            'share_seo',
            'jalali',
            'jformat',
            'format_jalali',
            'gdate',
            'date_format_smart',
            'date_ago',
            'user',
            'isLogin',
            'app_urls',
            'app_url',
            'rewrite_active',
        ]));

        // add custom functions from inherited themes (parents first, child last)
        foreach ($this->functionFiles() as $functions) {
            $this->twigEngine->addFunctionsFile($functions);
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    private function functionFiles(): array
    {
        $files = [];

        foreach (array_reverse($this->themePaths) as $themePath) {
            $file = rtrim(str_replace('\\', '/', $themePath), '/') . '/functions.php';
            if (is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function resolveThemePaths(string|array $folders, string $pathTheme): array
    {
        if ($pathTheme === '' && $this->looksLikeAbsolutePaths($folders)) {
            return is_array($folders) ? array_values($folders) : [$folders];
        }

        if ($pathTheme !== '' && is_array($folders)) {
            $paths = [];
            foreach ($folders as $folder) {
                $paths[] = rtrim(str_replace('\\', '/', $pathTheme . '/' . $folder), '/');
            }

            return $paths;
        }

        if ($pathTheme !== '' && is_string($folders)) {
            return [rtrim(str_replace('\\', '/', $pathTheme . '/' . $folders), '/')];
        }

        return is_array($folders) ? array_values($folders) : [(string) $folders];
    }

    private function looksLikeAbsolutePaths(string|array $folders): bool
    {
        $items = is_array($folders) ? $folders : [$folders];

        foreach ($items as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }

            if (self::isFilesystemPath($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function twigEnvironmentOptions(): array
    {
        $options = [];

        if (isset($this->twigOptions['cache']) && is_string($this->twigOptions['cache'])) {
            if (!is_dir($this->twigOptions['cache'])) {
                mkdir($this->twigOptions['cache'], 0777, true);
            }
            $options['cache'] = $this->twigOptions['cache'];
        }

        if (array_key_exists('auto_reload', $this->twigOptions)) {
            $options['auto_reload'] = (bool) $this->twigOptions['auto_reload'];
        }

        if (!empty($this->twigOptions['debug'])) {
            $options['debug'] = true;
        }

        return $options;
    }

    public function twigOption(?string $key = null, mixed $default = null): mixed
    {
        $data = new DataManager($this->twigOptions);
        return $data->get($key, $default);
    }

    public function changeTheme(string|array $folders, string $pathTheme = ''): static
    {
        $this->setView($folders, $pathTheme);

        return $this;
    }

    private function addCustomFunctions(string|array $folders, string $pathTheme): static
    {
        if (is_array($folders)) {
            foreach ($folders as $folder) {
                $this->addCustomFunctions($folder, $pathTheme);
            }
        }

        return $this;
    }

    /**
     * render view file
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function renderFile(string $name, array $parameters = []): string
    {
        $parameters = array_replace($this->getAll(), $parameters);
        return $this->template->render($name, $parameters);
    }

    /**
     * exists view file
     *
     * @param string $name
     * @return bool
     */
    public function existsFile(string $name): bool
    {
        return $this->template->exists($name);
    }

    /**
     * exists view
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        if ($this->existsFile($name))
            return true;

        $engines = $this->engines();
        foreach ($engines as $engine) {
            $filename = $name . '.' . $engine;
            if ($this->existsFile($filename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the assigned globals data.
     */
    public function getAll(): array
    {
        return $this->globals;
    }

    /**
     * Returns the assigned one global data.
     *
     * @param string|int $index
     * @return mixed
     */
    public function get(string|int $index): mixed
    {
        return isset($this->globals[$index]) ? $this->globals[$index] : null;
    }

    /**
     * Set global data
     *
     * @param string $name
     * @param mixed $value
     * @return View
     */
    public function set(string $name, mixed $value): static
    {
        $this->globals[$name] = $value;

        return $this;
    }

    public function add(array $data): static
    {
        $this->globals = array_merge($this->globals, $data);
        return $this;
    }

    public function setData(array $data): static
    {
        $this->globals = $data;
        return $this;
    }

    /**
     * get all engines
     *
     * @return array
     */
    public function engines(): array
    {
        return $this->parser::ENGINES;
    }

    public static function isFilesystemPath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    /**
     * Resolve a theme asset to a public URL, or return the filesystem path.
     *
     * @param string $link Relative file, or @theme/file for another theme in the same app.
     */
    public function assets(string $link = '', bool $asPath = false, ?string $theme = null): string
    {
        return ThemeAssets::resolve($link, $theme, null, $asPath, $this->themePaths);
    }

    public function asstes(string $file = ''): string
    {
        return $this->path()->assets($file);
    }

    /**
     * render view
     *
     * @param string|array|null $name
     * @param array $parameters
     * @param bool $exist
     * @return string
     */
    public function render(string|array|null $name = null, array $parameters = [], bool $exist = true): string
    {
        $result = $this->getContentReady();
        return $result . $this->renderByEngine($name, $parameters, $exist);
    }

    public function renderByEngine(string|array|null $name, array $parameters, bool $exist = true): string
    {
        $result = '';

        if (empty($name))
            return $result;

        if (is_array($name)) {
            foreach ($name as $n) {
                $result .= $this->renderByEngine($n, $parameters, $exist);
            }
            return $result;
        }

        if ($this->existsFile($name))
            return $this->renderFile($name, $parameters);

        $engines = $this->engines();
        foreach ($engines as $engine) {
            $filename = $name . '.' . $engine;
            if ($this->existsFile($filename)) {
                return $this->renderFile($filename, $parameters);
            }
        }

        if ($exist) {
            $folders = !is_array($this->folders) ? [$this->folders] : $this->folders;
            $folders = implode(', ',$folders);
            $template = $this->parser->parse($name);
            throw new \InvalidArgumentException(sprintf('The template "%s" in "%s" theme does not exist.', $template,$folders));
        }

        return $result;
    }

    /**
     * add ready render
     *
     * @param string|array $name
     * @param array $parameters
     * @param bool $exist
     * @return View
     */
    public function ready(string|array $name = '', array $parameters = [], bool $exist = true): static
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                $this->ready($n, $parameters);
            }
            return $this;
        }

        if (!empty($name))
            $this->readyRenders[] = ['name' => $name, 'parameters' => $parameters, 'exist' => $exist];

        return $this;
    }

    /**
     * get content ready
     *
     * @return string
     */
    public function getContentReady(): string
    {
        $content = '';
        foreach ($this->readyRenders as ['name' => $name, 'parameters' => $parameters, 'exist' => $exist]) {
            $content .= $this->renderByEngine($name, $parameters, $exist);
        }

        return $content;
    }

    public function path(): TemplatePathReference
    {
        if ($this->themePaths !== []) {
            return new TemplatePathReference($this->themePaths);
        }

        return new TemplatePathReference($this->folders, $this->pathTheme);
    }

    /**
     * @return list<string>
     */
    public function themePaths(): array
    {
        return $this->themePaths;
    }

    /**
     * @return array{name: string, stack: list<string>, paths: list<string>}
     */
    public function themeStack(): array
    {
        return [
            'name' => is_array($this->folders) ? (string) ($this->folders[0] ?? '') : (string) $this->folders,
            'stack' => is_array($this->folders) ? $this->folders : [(string) $this->folders],
            'paths' => $this->themePaths,
        ];
    }
}