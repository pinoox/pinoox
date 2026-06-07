<?php

namespace Pinoox\Component\Kernel\Debug\Support;

/**
 * Chooses a static debug layout for XHR/fetch/API failures (DevTools Network preview).
 * Full-page navigation keeps the interactive exception console.
 */
final class NetworkPreviewDetector
{
    public static function prefersEmbeddedDebugPage(): bool
    {
        $dest = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        if ($dest === 'document' || $dest === 'iframe') {
            return false;
        }

        if ($dest === 'empty') {
            return true;
        }

        if (strcasecmp($xhr, 'XMLHttpRequest') === 0) {
            return true;
        }

        if (str_contains($accept, 'application/json') && !str_contains($accept, 'text/html')) {
            return true;
        }

        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';

        return preg_match('#/api(/|$)#i', $path) === 1 && $dest !== 'document';
    }
}
