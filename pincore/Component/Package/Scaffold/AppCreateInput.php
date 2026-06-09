<?php

namespace Pinoox\Component\Package\Scaffold;

final class AppCreateInput
{
    public const STACK_NONE = 'none';
    public const STACK_TWIG = 'twig';
    public const STACK_VITE = 'vite';
    public const STACK_VUE = 'vue';
    public const STACK_REACT = 'react';

    public const PROFILE_SPA = 'spa';
    public const PROFILE_HYBRID = 'hybrid';

    /** @var list<string> */
    public const STACKS = [
        self::STACK_NONE,
        self::STACK_TWIG,
        self::STACK_VITE,
        self::STACK_VUE,
        self::STACK_REACT,
    ];

    public function __construct(
        public readonly string $package,
        public readonly string $displayName,
        public readonly string $developer,
        public readonly string $description,
        public readonly string $stack = self::STACK_NONE,
        public readonly string $profile = self::PROFILE_HYBRID,
        public readonly bool $registerRoute = false,
        public readonly ?string $routePath = null,
    ) {
    }

    public function hasViteStack(): bool
    {
        return in_array($this->stack, [self::STACK_VITE, self::STACK_VUE, self::STACK_REACT], true);
    }

    public function usesTwigLayouts(): bool
    {
        return $this->stack === self::STACK_TWIG;
    }

    public static function simple(string $package): self
    {
        $displayName = AppCreateScaffolder::displayNameFromPackage($package);

        return new self(
            package: $package,
            displayName: $displayName,
            developer: 'pinoox developer',
            description: $displayName,
            stack: self::STACK_NONE,
            registerRoute: false,
            routePath: null,
        );
    }
}
