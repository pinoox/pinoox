<?php

namespace Pinoox\Component\Template\Seo;

class SeoMeta
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $image = null,
        public ?string $type = 'website',
        public ?string $robots = null,
        public ?string $locale = null,
        public array $meta = [],
        public array $jsonLd = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            canonical: isset($data['canonical']) ? (string) $data['canonical'] : null,
            image: isset($data['image']) ? (string) $data['image'] : null,
            type: isset($data['type']) ? (string) $data['type'] : 'website',
            robots: isset($data['robots']) ? (string) $data['robots'] : null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            meta: is_array($data['meta'] ?? null) ? $data['meta'] : [],
            jsonLd: is_array($data['json_ld'] ?? $data['jsonLd'] ?? null) ? ($data['json_ld'] ?? $data['jsonLd']) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'image' => $this->image,
            'type' => $this->type,
            'robots' => $this->robots,
            'locale' => $this->locale,
            'meta' => $this->meta,
            'json_ld' => $this->jsonLd,
        ];
    }

    public function renderTags(): string
    {
        $tags = [];

        if ($this->title !== null && $this->title !== '') {
            $tags[] = $this->tag('title', $this->title, false);
        }

        if ($this->description !== null && $this->description !== '') {
            $tags[] = $this->metaTag('description', $this->description);
            $tags[] = $this->ogTag('description', $this->description);
        }

        if ($this->canonical !== null && $this->canonical !== '') {
            $tags[] = '<link rel="canonical" href="' . $this->escape($this->canonical) . '">';
        }

        if ($this->robots !== null && $this->robots !== '') {
            $tags[] = $this->metaTag('robots', $this->robots);
        }

        if ($this->title !== null && $this->title !== '') {
            $tags[] = $this->ogTag('title', $this->title);
        }

        if ($this->type !== null && $this->type !== '') {
            $tags[] = $this->ogTag('type', $this->type);
        }

        if ($this->locale !== null && $this->locale !== '') {
            $tags[] = '<meta property="og:locale" content="' . $this->escape($this->locale) . '">';
        }

        if ($this->image !== null && $this->image !== '') {
            $tags[] = $this->ogTag('image', $this->image);
            $tags[] = $this->metaTag('twitter:card', 'summary_large_image', true);
            $tags[] = $this->metaTag('twitter:image', $this->image, true);
        }

        foreach ($this->meta as $name => $content) {
            if (is_string($name) && (is_string($content) || is_numeric($content))) {
                $tags[] = $this->metaTag($name, (string) $content, str_contains($name, ':'));
            }
        }

        if ($this->jsonLd !== []) {
            $tags[] = '<script type="application/ld+json">' . json_encode(
                $this->jsonLd,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . '</script>';
        }

        return implode("\n    ", array_filter($tags));
    }

    private function tag(string $name, string $content, bool $isMeta = true): string
    {
        if ($isMeta) {
            return $this->metaTag($name, $content);
        }

        return '<' . $name . '>' . $this->escape($content) . '</' . $name . '>';
    }

    private function metaTag(string $name, string $content, bool $rawName = false): string
    {
        $attr = $rawName ? $name : $name;

        return '<meta name="' . $this->escape($attr) . '" content="' . $this->escape($content) . '">';
    }

    private function ogTag(string $property, string $content): string
    {
        return '<meta property="og:' . $this->escape($property) . '" content="' . $this->escape($content) . '">';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

