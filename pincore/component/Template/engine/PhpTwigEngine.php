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


namespace pinoox\component\template\engine;

use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

class PhpTwigEngine implements EngineInterface
{
    /**
     * PhpTwigEngine constructor.
     *
     * @param TemplateNameParserInterface $parser
     * @param PhpEngine|null $php
     * @param TwigEngine|null $twig
     */
    public function __construct(
        public TemplateNameParserInterface $parser,
        public ?PhpEngine $php = null,
        public ?TwigEngine $twig = null)
    {
    }

    /**
     * Set php engine
     *
     * @param PhpEngine $php
     */
    public function setPhpEngine(PhpEngine $php)
    {
        $this->php = $php;
    }

    /**
     * Set twig engine
     *
     * @param TwigEngine $twig
     */
    public function setTwigEngine(TwigEngine $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritDoc}
     */
    public function render(TemplateReferenceInterface|string $name, array $parameters = []): string
    {
        $this->twig->setTemplate($name, $this->php->render($name, $parameters));
        return $this->twig->render($name, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(TemplateReferenceInterface|string $name): bool
    {
        return $this->twig->exists($name);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TemplateReferenceInterface|string $name): bool
    {
        $template = $this->parser->parse($name);

        return 'twig.php' === $template->get('engine');
    }
}