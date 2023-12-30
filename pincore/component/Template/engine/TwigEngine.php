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


namespace Pinoox\Component\Template\Engine;

use Exception;
use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFunction;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigEngine implements EngineInterface
{
    private LoaderInterface $loader;
    private LoaderInterface $fileLoader;
    private ArrayLoader $arrayLoader;
    private TemplateNameParserInterface $parser;
    public Environment $template;

    /**
     * TwigEngine constructor.
     * @param TemplateNameParserInterface $parser
     * @param LoaderInterface|string|array $folder
     * @param string|null $rootPath
     */
    public function __construct(TemplateNameParserInterface $parser, LoaderInterface|string|array $folder, ?string $rootPath = null)
    {
        if ($folder instanceof LoaderInterface) {
            $this->fileLoader = $folder;
        } else {
            $this->fileLoader = new FilesystemLoader($folder, $rootPath);
        }
        $this->arrayLoader = new ArrayLoader();
        $this->loader = new ChainLoader([
            $this->arrayLoader,
            $this->fileLoader
        ]);
        $this->parser = $parser;
        $this->template = new Environment($this->loader);
    }

    /**
     * Set loader
     * @param LoaderInterface $loader
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->template->setLoader($loader);
    }

    /**
     * Get Loader
     * @return LoaderInterface
     */
    public function getLoader(): LoaderInterface
    {
        return $this->template->getLoader();
    }

    /**
     * Get Loader
     * @param string $name
     * @param string $template
     * @return void
     */
    public function setTemplate(string $name, string $template): void
    {
        $this->arrayLoader->setTemplate($name, $template);
    }

    /**
     * Add Function
     *
     * @param string $name
     * @param callable $callback
     */
    public function addFunction(string $name, callable $callback): void
    {
        $this->template->addFunction(new TwigFunction($name, $callback));
    }

    /**
     * Add Extension
     *
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension): void
    {
        $this->template->addExtension($extension);
    }

    /**
     * Get all functions
     */
    public function getFunctions(): array
    {
        return $this->template->getFunctions();
    }

    /**
     * Get function
     * @param string $name
     * @return TwigFunction
     */
    public function getFunction(string $name): TwigFunction
    {
        return $this->template->getFunction($name);
    }

    /**
     * Add internal function
     *
     * @param string|array $names
     * @param string|null $namespace
     * @param string|null $replace
     */
    public function addInternalFunction(string|array $names,?string $namespace = null, ?string $replace = null): void
    {
        if (empty($names))
            return;

        if (is_array($names)) {
            foreach ($names as $key => $name) {
                $replace = !is_numeric($key) ? $name : null;
                $name = !is_numeric($key) ? $key : $name;
                $this->addInternalFunction($name, $namespace, $replace);
            }
        } else {
            try {
                $names = !empty($namespace) ? $namespace . '\\' . $names : $names;
                $funcName = empty($replace) ? $names : $replace;
                $this->addFunction($names, function () use ($funcName) {
                    $result = call_user_func_array($funcName, func_get_args());
                    return is_array($result)? json_encode($result) : $result;
                });
            } catch (\Exception) {
            }
        }

    }

    /**
     * Add functions on PHP file
     *
     * @param string|array $files
     * @param bool $isNamespace
     */
    public function addFunctionsFile(string|array $files, bool $isNamespace = false): void
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                $this->addFunctionsFile($file);
            }
        } else {
            if (is_file($files)) {
                $regex = '/function[\s\n]+(\S+)[\s\n]*\(/';
                $content = file_get_contents($files);
                $functions = [];
                preg_match_all($regex, $content, $functions);
                if (count($functions) > 1) {
                    $functions = $functions[1];
                }
                $namespace = $isNamespace ? File::extract_namespace(Dir::path('~pincore/boot/routes.php')) : null;
                $this->addInternalFunction($functions, $namespace);
            }
        }
    }


    /**
     * render view
     *
     * @param TemplateReferenceInterface|string $name
     * @param array $parameters
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(TemplateReferenceInterface|string $name, array $parameters = []): string
    {
        return $this->template->render($name, $parameters);
    }

    /**
     * exists view
     *
     * @param TemplateReferenceInterface|string $name
     * @return bool
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function exists(TemplateReferenceInterface|string $name): bool
    {
        try {
            $this->template->load($name);
        } catch (LoaderError $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TemplateReferenceInterface|string $name): bool
    {
        $reference = $this->parser->parse($name);

        return 'twig' === $reference->get('engine');
    }
}