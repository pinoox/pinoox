<?php

use Pinoox\Component\Template\Seo\SeoMeta;
use Pinoox\Portal\View;

if (!function_exists('seo')) {
    /**
     * @param array<string, mixed>|SeoMeta $data
     */
    function seo(array|SeoMeta $data = []): SeoMeta
    {
        if ($data instanceof SeoMeta) {
            return $data;
        }

        return SeoMeta::fromArray($data);
    }
}

if (!function_exists('share_seo')) {
    /**
     * @param array<string, mixed>|SeoMeta $data
     */
    function share_seo(array|SeoMeta $data): void
    {
        View::shareSeo($data);
    }
}

if (!function_exists('seo_tags')) {
    /**
     * @param array<string, mixed>|SeoMeta|null $data
     */
    function seo_tags(array|SeoMeta|null $data = null): string
    {
        if ($data === null) {
            $shared = View::get('_seo');
            if ($shared instanceof SeoMeta) {
                return $shared->renderTags();
            }

            if (is_array($shared)) {
                return SeoMeta::fromArray($shared)->renderTags();
            }

            return '';
        }

        return seo($data)->renderTags();
    }
}

