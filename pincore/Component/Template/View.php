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

    /**
     * View constructor.
     *
     * @param string|array $folders
     * @param string $pathTheme
     */
    public function __construct(string|array $folders, string $pathTheme, array $twigOptions = [])
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
    public function setView(string|array $folders, string $pathTheme): static
    {
        $this->folders = $folders;
        $this->pathTheme = $pathTheme;

        // template name parser
        $this->parser = new TemplateNameParser();

        // instance engines
        $this->phpEngine = new PhpEngine($this->parser, $folders, $pathTheme); // .php engine
        $this->twigEngine = new TwigEngine($this->parser, $folders, $pathTheme); // .twig engine
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
            'furl',
            'lang' => 't',
            't',
            'config',
            'app',
            'dd',
            'dump',
            'assets',
            'vite',
            'user',
            'isLogin',
            'getAppUrls',
            'getAppUrlFirst',
        ]));

        // add custom functions
        @include $functions = $this->path()
            ->assets('functions.php');
        $this->twigEngine
            ->addFunctionsFile($functions);

        return $this;
    }

    public function twigOption(?string $key = null, mixed $default = null): mixed
    {
        $data = new DataManager($this->twigOptions);
        return $data->get($key, $default);
    }

    public function changeTheme(string|array $folders): static
    {
        $this->setView($folders, $this->pathTheme);

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
        return new TemplatePathReference($this->folders, $this->pathTheme);
    }
}