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

function pinoox_requirement_messages(string $locale): array
{
    $lang = pinoox_load_requirement_lang($locale);
    $fallback = $locale !== 'en' ? pinoox_load_requirement_lang('en') : [];
    $meta = is_array($lang['meta'] ?? null) ? $lang['meta'] : ($fallback['meta'] ?? ['dir' => 'ltr', 'lang' => 'en']);
    $labels = is_array($lang['labels'] ?? null) ? $lang['labels'] : ($fallback['labels'] ?? []);
    $php = pinoox_requirement_lang($locale, 'php');
    $vendor = pinoox_requirement_lang($locale, 'vendor');
    $replace = ['required' => pinoox_min_php_version()];

    $translateHints = static function (array $hints) use ($replace): array {
        return array_map(
            static fn (string $hint): string => pinoox_requirement_replace($hint, $replace),
            $hints
        );
    };

    return [
        'dir' => (string) ($meta['dir'] ?? 'ltr'),
        'lang' => (string) ($meta['lang'] ?? 'en'),
        'title' => (string) ($php['title'] ?? ''),
        'badge' => (string) ($php['badge'] ?? ''),
        'heading' => (string) ($php['heading'] ?? ''),
        'message' => (string) ($php['message'] ?? ''),
        'required_label' => (string) ($labels['required'] ?? ''),
        'current_label' => (string) ($labels['current'] ?? ''),
        'hint_title' => (string) ($labels['hint_title'] ?? ''),
        'hints' => $translateHints($php['hints'] ?? []),
        'vendor_title' => (string) ($vendor['title'] ?? ''),
        'vendor_heading' => (string) ($vendor['heading'] ?? ''),
        'vendor_message' => (string) ($vendor['message'] ?? ''),
        'vendor_hints' => $translateHints($vendor['hints'] ?? []),
    ];
}

function pinoox_requirement_logo_url(): string
{
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $base = rtrim($scriptDir, '/');

    return $base . '/pincore/resource/images/logo.png';
}

function pinoox_render_requirement_page(array $copy, array $facts, array $hints, int $status = 503): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $copy['heading'] . PHP_EOL);
        fwrite(STDERR, $copy['message'] . PHP_EOL);

        foreach ($facts as $label => $value) {
            fwrite(STDERR, $label . ': ' . $value . PHP_EOL);
        }

        foreach ($hints as $hint) {
            fwrite(STDERR, '- ' . $hint . PHP_EOL);
        }

        return;
    }

    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    $logo = htmlspecialchars(pinoox_requirement_logo_url(), ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($copy['title'], ENT_QUOTES, 'UTF-8');
    $badge = htmlspecialchars($copy['badge'], ENT_QUOTES, 'UTF-8');
    $heading = htmlspecialchars($copy['heading'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($copy['message'], ENT_QUOTES, 'UTF-8');
    $hintTitle = htmlspecialchars($copy['hint_title'], ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="' . $copy['lang'] . '" dir="' . $copy['dir'] . '"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . $title . '</title>';
    echo '<style>';
    echo 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem;font-family:Tahoma,Segoe UI,sans-serif;background:linear-gradient(135deg,#1f7a8c,#022b3a);color:#fff;}';
    echo '.card{width:100%;max-width:34rem;padding:1.75rem 1.65rem;border-radius:1.15rem;background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.16);box-shadow:0 22px 48px rgba(0,0,0,.22);backdrop-filter:blur(14px);}';
    echo '.brand{text-align:center;margin-bottom:.85rem;} .brand img{width:3.5rem;height:3.5rem;border-radius:1rem;}';
    echo '.badge{display:inline-block;margin-bottom:.65rem;padding:.2rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700;background:rgba(255,120,120,.2);border:1px solid rgba(255,120,120,.32);color:#ffd0d0;}';
    echo 'h1{margin:0 0 .65rem;font-size:1.28rem;} p{margin:0 0 1rem;line-height:1.65;color:rgba(255,255,255,.88);}';
    echo '.facts{margin:0 0 1rem;padding:0;list-style:none;} .facts li{padding:.75rem .85rem;border-radius:.75rem;background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.08);} .facts li+li{margin-top:.55rem;}';
    echo '.facts strong{display:block;font-size:.76rem;color:rgba(255,255,255,.68);margin-bottom:.2rem;} .facts span{font-size:.92rem;}';
    echo '.hints{margin:0;padding:0;list-style:none;text-align:' . ($copy['dir'] === 'rtl' ? 'right' : 'left') . ';} .hints li{position:relative;padding-' . ($copy['dir'] === 'rtl' ? 'right' : 'left') . ':1rem;margin-bottom:.55rem;line-height:1.6;color:rgba(255,255,255,.82);font-size:.86rem;}';
    echo '.hints li::before{content:"•";position:absolute;' . ($copy['dir'] === 'rtl' ? 'right' : 'left') . ':0;color:#ffd27a;}';
    echo '.hint-title{margin:0 0 .65rem;font-size:.92rem;color:#ffe9a8;}';
    echo '</style></head><body><div class="card" role="alert">';
    echo '<div class="brand"><img src="' . $logo . '" alt="Pinoox"></div>';
    echo '<span class="badge">' . $badge . '</span>';
    echo '<h1>' . $heading . '</h1><p>' . $message . '</p><ul class="facts">';

    foreach ($facts as $label => $value) {
        echo '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>';
        echo '<span>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</span></li>';
    }

    echo '</ul><h2 class="hint-title">' . $hintTitle . '</h2><ul class="hints">';

    foreach ($hints as $hint) {
        echo '<li>' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</li>';
    }

    echo '</ul></div></body></html>';
}

function pinoox_render_php_requirement_error(): void
{
    $copy = pinoox_requirement_messages(pinoox_preferred_locale());
    $facts = [
        $copy['required_label'] => pinoox_composer_php_constraint() . ' (>= ' . pinoox_min_php_version() . ')',
        $copy['current_label'] => PHP_VERSION,
    ];

    pinoox_render_requirement_page($copy, $facts, $copy['hints']);
}

function pinoox_render_vendor_missing_error(): void
{
    $copy = pinoox_requirement_messages(pinoox_preferred_locale());
    $copy['title'] = $copy['vendor_title'];
    $copy['heading'] = $copy['vendor_heading'];
    $copy['message'] = $copy['vendor_message'];

    $facts = [
        $copy['required_label'] => pinoox_composer_php_constraint() . ' (>= ' . pinoox_min_php_version() . ')',
        $copy['current_label'] => PHP_VERSION,
    ];

    pinoox_render_requirement_page($copy, $facts, $copy['vendor_hints'], 500);
}
