<?php

namespace Pinoox\Component\Package;

/**
 * Cross-app resource reference parser.
 *
 * Syntax (aligned with ThemeReference):
 *   @com_shop:config.database.host
 *   @com_shop:lang.welcome.title
 *   @com_shop:action.home
 *   @com_shop:path.theme/default/main.twig
 *   @com_shop:class.Model.OrderModel
 *   @com_shop/home                 (action shorthand)
 *   com_shop:config.foo            (without @)
 */
final class AppResourceReference
{

    public const TYPE_CONFIG = 'config';

    public const TYPE_LANG = 'lang';

    public const TYPE_ACTION = 'action';

    public const TYPE_PATH = 'path';

    public const TYPE_CLASS = 'class';

    public function __construct(
        public readonly string $package,
        public readonly string $type,
        public readonly string $value,
    ) {
    }

    public static function parse(string $reference, ?string $defaultPackage = null): self
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new \InvalidArgumentException('App resource reference cannot be empty.');
        }

        if (str_starts_with($reference, '@')) {
            $reference = substr($reference, 1);
        }

        if (str_contains($reference, ':')) {
            [$package, $rest] = explode(':', $reference, 2);
            $package = trim($package);
            $rest = trim($rest);

            if ($package === '' || $rest === '') {
                throw new \InvalidArgumentException('Invalid app resource reference: ' . $reference);
            }

            foreach ([
                self::TYPE_CONFIG => 'config.',
                self::TYPE_LANG => 'lang.',
                self::TYPE_ACTION => 'action.',
                self::TYPE_PATH => 'path.',
                self::TYPE_CLASS => 'class.',
            ] as $type => $prefix) {
                if (str_starts_with($rest, $prefix)) {
                    return new self($package, $type, substr($rest, strlen($prefix)));
                }
            }

            return new self($package, self::TYPE_ACTION, $rest);
        }

        if (str_contains($reference, '/')) {
            [$package, $rest] = explode('/', $reference, 2);
            $package = trim($package);
            $rest = trim($rest);

            if ($package !== '' && self::looksLikePackage($package) && $rest !== '') {
                return new self($package, self::TYPE_ACTION, $rest);
            }
        }

        if ($defaultPackage === null || trim($defaultPackage) === '') {
            throw new \InvalidArgumentException('Package name is required for reference: ' . $reference);
        }

        return new self(trim($defaultPackage), self::TYPE_ACTION, $reference);
    }

    /**
     * Parse cross-app action references used in routes and url()->action().
     *
     * @return array{package: string, action: string}|null
     */
    public static function parseActionReference(string $reference): ?array
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        try {
            $parsed = self::parse($reference);
        } catch (\Throwable) {
            return null;
        }

        if ($parsed->type !== self::TYPE_ACTION || !self::looksLikePackage($parsed->package)) {
            return null;
        }

        return [
            'package' => $parsed->package,
            'action' => $parsed->value,
        ];
    }

    public function key(): string
    {
        return $this->package . ':' . $this->type . '.' . $this->value;
    }

    private static function looksLikePackage(string $value): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*$/', $value) === 1 && str_contains($value, '_');
    }
}

