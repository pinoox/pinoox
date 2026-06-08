<?php

namespace Feature;

use PHPUnit\Framework\TestCase;
use Pinoox\Component\Kernel\Debug\Support\PortalContextResolver;
use Pinoox\Component\Source\PortalCallSite;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class PortalContextResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        PortalCallSite::reset();
    }

    public function test_remembers_portal_call_site_from_user_file(): void
    {
        $userFile = sys_get_temp_dir() . '/pinoox_portal_call_' . uniqid('', true) . '.php';
        file_put_contents($userFile, "<?php\naction('home', fn() => \\Pinoox\\Portal\\View::render3('index'));\n");

        PortalCallSite::capture(
            \Pinoox\Portal\View::class,
            'render3',
            ['index'],
            [
                ['file' => testProjectRoot() . '/pincore/Component/Source/Portal.php', 'line' => 304, 'function' => '__callStatic'],
                ['file' => $userFile, 'line' => 2, 'function' => 'action'],
            ],
        );

        $context = PortalContextResolver::resolve(
            FlattenException::createFromThrowable(new \Error('Attempted to call an undefined method named "render3" of class "Pinoox\\Component\\Template\\View". Did you mean to call "render"?'))
        );

        $this->assertSame('View::render3(\'index\')', $context['call']);
        $this->assertStringContainsString('render3', implode("\n", array_column($context['source']['snippet'], 'content')));
        $this->assertSame('render', $context['suggestion']);
        $this->assertTrue($context['via_portal']);

        @unlink($userFile);
    }

    public function test_infers_origin_from_trace_when_call_site_missing(): void
    {
        $userFile = sys_get_temp_dir() . '/pinoox_portal_trace_' . uniqid('', true) . '.php';
        file_put_contents($userFile, "<?php\n\\Pinoox\\Portal\\View::render3('page');\n");

        $error = new \Error('Attempted to call an undefined method named "render3" of class "Pinoox\\Component\\Template\\View".');
        $trace = [
            ['file' => testProjectRoot() . '/pincore/Component/Source/Portal.php', 'line' => 225, 'function' => 'callMethod', 'class' => \Pinoox\Component\Source\Portal::class, 'type' => '::', 'args' => ['render3', ['page']]],
            ['file' => testProjectRoot() . '/pincore/Component/Source/Portal.php', 'line' => 304, 'function' => '__callStatic', 'class' => \Pinoox\Portal\View::class, 'type' => '::', 'args' => ['render3', ['page']]],
            ['file' => $userFile, 'line' => 2, 'function' => 'test', 'class' => self::class, 'type' => '->'],
        ];

        $exception = FlattenException::createFromThrowable($error);
        $exception->setTrace($trace, $error->getFile(), $error->getLine());

        $context = PortalContextResolver::resolve($exception);

        $this->assertSame('View::render3(…)', $context['call']);
        $this->assertStringContainsString("render3('page')", implode("\n", array_column($context['source']['snippet'], 'content')));

        @unlink($userFile);
    }
}

