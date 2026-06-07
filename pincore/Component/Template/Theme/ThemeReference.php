<?php

namespace Pinoox\Component\Template\Theme;

final class ThemeReference
{
    public function __construct(
        public readonly string $package,
        public readonly string $name,
    ) {
    }

    /**
     * Parse theme references:
     * - default
     * - @com_vendor_app/base
     * - com_vendor_app:base
     */
    public static function parse(string $reference, string $defaultPackage): self
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new \InvalidArgumentException('Theme reference cannot be empty.');
        }

        if (str_starts_with($reference, '@')) {
            $reference = substr($reference, 1);
        }

        if (str_contains($reference, ':')) {
            [$package, $name] = explode(':', $reference, 2);

            return new self(trim($package), trim($name));
        }

        if (str_contains($reference, '/')) {
            [$package, $name] = explode('/', $reference, 2);

            return new self(trim($package), trim($name));
        }

        return new self($defaultPackage, $reference);
    }

    public function key(): string
    {
        return $this->package . ':' . $this->name;
    }
}

