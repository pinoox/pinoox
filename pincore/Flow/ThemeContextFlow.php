<?php

namespace Pinoox\Flow;

use Pinoox\Component\Flow\Flow;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Template\Theme\ThemeContext as ThemeContextManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Switches the active theme context for the current request (site, panel, kids, ...).
 *
 * Register via theme_flow_aliases() in app.php and attach to routes:
 *   flows: ['theme.panel']
 */
class ThemeContextFlow extends Flow
{
    /** @var array<string, self> */

    private static array $instances = [];

    public function __construct(
        ?RequestEvent $requestEvent = null,
        private readonly string $context = 'default',
    ) {
        parent::__construct($requestEvent);
    }

    public static function for(string $context): self
    {
        return self::$instances[$context] ??= new self(null, $context);
    }

    protected function before(Request $request): void
    {
        ThemeContextManager::activate($this->context);
    }
}

