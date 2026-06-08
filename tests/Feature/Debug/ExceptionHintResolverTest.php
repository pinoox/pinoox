<?php

namespace Feature;

use PHPUnit\Framework\TestCase;
use Pinoox\Component\Kernel\Debug\Support\ExceptionHintResolver;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ExceptionHintResolverTest extends TestCase
{
    public function test_portal_typo_hint_includes_fix_and_location(): void
    {
        $exception = FlattenException::createFromThrowable(new \Error(
            'Attempted to call an undefined method named "render3" of class "Pinoox\\Component\\Template\\View". Did you mean to call "render"?'
        ));

        $hints = ExceptionHintResolver::resolve($exception, [
            'portal' => [
                'via_portal' => true,
                'portal' => 'View',
                'method' => 'render3',
                'suggestion' => 'render',
                'call' => "View::render3('index')",
                'relative_file' => 'apps/com_pinoox_installer/routes/actions.php',
                'line' => 16,
            ],
            'route' => [
                'action_ref' => '@home',
                'action_source' => [
                    'relative_file' => 'apps/com_pinoox_installer/routes/actions.php',
                    'line' => 16,
                ],
            ],
        ]);

        $this->assertNotEmpty($hints);
        $this->assertSame('high', $hints[0]['priority']);
        $this->assertStringContainsString('render3', $hints[0]['summary']);
        $this->assertStringContainsString("View::render('index')", (string) ($hints[0]['fix'] ?? ''));
        $this->assertSame('apps/com_pinoox_installer/routes/actions.php:16', $hints[0]['location']);
    }

    public function test_route_not_found_uses_request_context(): void
    {
        $exception = FlattenException::createFromThrowable(new \Symfony\Component\Routing\Exception\ResourceNotFoundException('No route found'));

        $hints = ExceptionHintResolver::resolve($exception, [
            'package' => 'com_pinoox_installer',
            'request' => [
                'method' => 'POST',
                'path' => '/api/setup',
            ],
        ]);

        $this->assertStringContainsString('POST', $hints[0]['summary']);
        $this->assertStringContainsString('/api/setup', $hints[0]['summary']);
    }
}

