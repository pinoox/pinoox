<?php

namespace Pinoox\Component\Template\Frontend;

final class ThemeSsrResult
{
    public function __construct(
        public readonly ?string $html,
        public readonly string $strategy,
        public readonly ?string $fallback = null,
    ) {
    }

    public function hasHtml(): bool
    {
        return is_string($this->html) && trim($this->html) !== '';
    }
}
