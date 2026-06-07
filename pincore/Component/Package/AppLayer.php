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

namespace Pinoox\Component\Package;

class AppLayer
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string  $path,
        private ?string $packageName,
        private array   $context = [],
    )
    {
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setPackageName(string $packageName): void
    {
        $this->packageName = $packageName;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->context;
        }

        return $this->context[$key] ?? $default;
    }

    public function matchedBy(): ?string
    {
        $matchedBy = $this->context('matched_by');

        return is_string($matchedBy) ? $matchedBy : null;
    }

    public function host(): ?string
    {
        $host = $this->context('host');

        return is_string($host) ? $host : null;
    }

    public function subdomain(): ?string
    {
        $subdomain = $this->context('subdomain');

        return is_string($subdomain) && $subdomain !== '' ? $subdomain : null;
    }

    public function isDefaultDomain(): bool
    {
        return (bool) $this->context('is_default_domain', false);
    }

    public function isCanonicalDefault(): bool
    {
        return (bool) $this->context('is_canonical_default', false);
    }
}

