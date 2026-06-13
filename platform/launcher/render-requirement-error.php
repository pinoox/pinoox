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
        'expected_file_label' => (string) ($labels['expected_file'] ?? ''),
        'current_label' => (string) ($labels['current'] ?? ''),
        'hint_title' => (string) ($labels['hint_title'] ?? ''),
        'hints' => $translateHints($php['hints'] ?? []),
        'vendor_title' => (string) ($vendor['title'] ?? ''),
        'vendor_heading' => (string) ($vendor['heading'] ?? ''),
        'vendor_message' => (string) ($vendor['message'] ?? ''),
        'vendor_hints' => $translateHints($vendor['hints'] ?? []),
        'project_path_label' => (string) ($labels['project_path'] ?? ''),
        'language_label' => (string) ($labels['language'] ?? 'Language'),
        'vendor_ui' => $vendor,
    ];
}

function pinoox_requirement_lang_switcher_styles(): string
{
    return '.page-wrap{display:flex;flex-direction:column;align-items:center;gap:.65rem;width:min(100%,58rem)}'
        . '.lang-bar{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.45rem .65rem;border-radius:.75rem;background:rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.06)}'
        . '.lang-bar label{font-size:.74rem;color:rgba(255,255,255,.68);white-space:nowrap}'
        . '.lang-select{min-width:8.5rem;padding:.45rem .65rem;border-radius:.55rem;border:1px solid rgba(255,255,255,.1);background:rgba(0,0,0,.22);color:#fff;font:inherit;font-size:.78rem;cursor:pointer}'
        . '.lang-select option{color:#111;background:#fff}';
}

function pinoox_render_requirement_lang_switcher(string $locale, string $label): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $locale = pinoox_requirement_normalize_locale($locale);
    $locales = pinoox_requirement_locales();

    echo '<div class="lang-bar">';
    echo '<label for="requirement-lang">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>';
    echo '<select id="requirement-lang" class="lang-select" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';

    foreach ($locales as $code => $name) {
        $selected = $code === $locale ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>';
        echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    echo '</select></div>';
    echo '<script>(function(){var select=document.getElementById("requirement-lang");if(!select)return;select.addEventListener("change",function(){var url=new URL(window.location.href);url.searchParams.set("lang",select.value);window.location.href=url.toString();});})();</script>';
}

function pinoox_requirement_logo_url(): string
{
    return pinoox_requirement_asset_url('images/logo.png');
}

function pinoox_requirement_kalameh_css_url(): string
{
    return pinoox_requirement_asset_url('css/fonts-kalameh.css');
}

function pinoox_requirement_font_link(string $locale): void
{
    if ($locale !== 'fa' || PHP_SAPI === 'cli') {
        return;
    }

    $href = htmlspecialchars(pinoox_requirement_kalameh_css_url(), ENT_QUOTES, 'UTF-8');
    echo '<link rel="stylesheet" href="' . $href . '">';
}

function pinoox_requirement_font_styles(string $locale): string
{
    if ($locale !== 'fa') {
        return '';
    }

    return 'html[lang="fa"] body,html[lang="fa"] .lang-bar,html[lang="fa"] .card{font-family:Kalameh,Tahoma,Segoe UI,sans-serif}'
        . 'html[lang="fa"] code,html[lang="fa"] .command code,html[lang="fa"] .output,html[lang="fa"] .console-body{font-family:Consolas,Monaco,monospace}';
}

function pinoox_requirement_cli_enable_vt(): void
{
    if (PHP_SAPI !== 'cli' || DIRECTORY_SEPARATOR !== '\\' || !function_exists('sapi_windows_vt100_support')) {
        return;
    }

    @sapi_windows_vt100_support(STDERR, true);
}

function pinoox_requirement_cli_use_color(): bool
{
    if (PHP_SAPI !== 'cli') {
        return false;
    }

    if (getenv('NO_COLOR') !== false && getenv('NO_COLOR') !== '') {
        return false;
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        return getenv('ANSICON') !== false
            || getenv('ConEmuANSI') === 'ON'
            || (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDERR));
    }

    return function_exists('posix_isatty') ? @posix_isatty(STDERR) : true;
}

function pinoox_requirement_cli_c(string $text, ?string $code = null): string
{
    if ($code === null || !pinoox_requirement_cli_use_color()) {
        return $text;
    }

    return "\033[" . $code . 'm' . $text . "\033[0m";
}

function pinoox_requirement_cli_write(string $line = ''): void
{
    fwrite(STDERR, $line . PHP_EOL);
}

function pinoox_requirement_cli_pad_line(string $line, int $inner): string
{
    $line = trim($line);
    $length = mb_strlen($line, 'UTF-8');

    if ($length > $inner) {
        $line = mb_substr($line, 0, max(1, $inner - 1), 'UTF-8') . '…';
        $length = mb_strlen($line, 'UTF-8');
    }

    return $line . str_repeat(' ', max(0, $inner - $length));
}

function pinoox_requirement_cli_block(string $title, string $body, string $accent = '36'): void
{
    $blank = '  ';
    $inner = 50;
    $bar = str_repeat('─', $inner);

    pinoox_requirement_cli_write('');
    pinoox_requirement_cli_write($blank . pinoox_requirement_cli_c('╭' . $bar . '╮', $accent));
    pinoox_requirement_cli_write($blank . pinoox_requirement_cli_c('│', $accent) . pinoox_requirement_cli_c(pinoox_requirement_cli_pad_line($title, $inner), '1;97') . pinoox_requirement_cli_c('│', $accent));
    pinoox_requirement_cli_write($blank . pinoox_requirement_cli_c('├' . $bar . '┤', $accent));

    foreach (preg_split('/\R/u', trim($body)) ?: [] as $line) {
        pinoox_requirement_cli_write($blank . pinoox_requirement_cli_c('│', $accent) . pinoox_requirement_cli_pad_line($line, $inner) . pinoox_requirement_cli_c('│', $accent));
    }

    pinoox_requirement_cli_write($blank . pinoox_requirement_cli_c('╰' . $bar . '╯', $accent));
}

function pinoox_requirement_cli_fact(string $label, string $value, string $valueColor = '97'): void
{
    pinoox_requirement_cli_write('');
    pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c($label, '2;36'));
    pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c($value, $valueColor));
}

function pinoox_requirement_cli_command(string $label, string $command): void
{
    pinoox_requirement_cli_write('');
    pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c($label, '1;33'));
    pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('▸ ', '32') . pinoox_requirement_cli_c($command, '1;92'));
}

function pinoox_requirement_cli_hints(string $title, array $hints): void
{
    if ($hints === []) {
        return;
    }

    pinoox_requirement_cli_write('');
    pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c($title, '1;33'));

    foreach ($hints as $hint) {
        pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('•', '33') . ' ' . $hint);
    }
}

function pinoox_render_vendor_missing_cli(array $copy, array $ui, string $projectPath, string $terminalCommand): void
{
    pinoox_requirement_cli_enable_vt();

    $badge = (string) ($ui['badge'] ?? $copy['badge'] ?? 'Setup');
    $heading = $copy['vendor_heading'] ?? '';
    $message = $copy['vendor_message'] ?? '';

    pinoox_requirement_cli_write('');
    pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('◆ Pinoox', '1;96') . ' ' . pinoox_requirement_cli_c('·', '2') . ' ' . pinoox_requirement_cli_c($badge, '33'));
    pinoox_requirement_cli_block($heading, $message);

    pinoox_requirement_cli_fact(
        $copy['project_path_label'] ?: 'Project directory',
        $projectPath
    );
    pinoox_requirement_cli_fact(
        $copy['expected_file_label'] ?: 'Expected file',
        'vendor/autoload.php',
        '93'
    );
    pinoox_requirement_cli_fact(
        $copy['current_label'] ?: 'PHP',
        PHP_VERSION,
        '93'
    );

    pinoox_requirement_cli_command(
        (string) ($ui['terminal_title'] ?? 'Terminal command'),
        $terminalCommand
    );

    $composer = pinoox_detect_composer();
    pinoox_requirement_cli_write('');

    if (!$composer['shell_available']) {
        pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('⚠ ', '33') . ($ui['shell_disabled'] ?? 'Shell is disabled.'));
    } elseif ($composer['installed']) {
        $found = str_replace('{source}', (string) $composer['source'], (string) ($ui['composer_found'] ?? 'Composer found'));
        pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('✓ ', '32') . pinoox_requirement_cli_c($found, '92'));
        if (!empty($composer['version'])) {
            pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c((string) $composer['version'], '2'));
        }
    } else {
        pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('✗ ', '31') . ($ui['composer_missing'] ?? 'Composer is not installed.'));
        pinoox_requirement_cli_write('  ' . pinoox_requirement_cli_c('https://getcomposer.org/download/', '94'));
    }

    pinoox_requirement_cli_hints(
        $copy['hint_title'] ?: 'How to fix',
        $copy['vendor_hints'] ?? []
    );
    pinoox_requirement_cli_write('');
}

function pinoox_requirement_vendor_page_styles(string $dir, string $textAlign): string
{
    $hintAlign = $dir === 'rtl' ? 'right' : 'left';

    return '*,*::before,*::after{box-sizing:border-box}'
        . 'body{margin:0;min-height:100vh;height:100vh;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:1.25rem;font-family:Tahoma,Segoe UI,sans-serif;background:#02090c;color:#fff;position:relative}'
        . 'body::before,body::after{content:"";position:fixed;border-radius:50%;filter:blur(72px);opacity:.42;pointer-events:none;z-index:0}'
        . 'body::before{width:28rem;height:28rem;top:-8rem;left:-6rem;background:radial-gradient(circle,#1f8a7a 0,transparent 70%)}'
        . 'body::after{width:24rem;height:24rem;right:-5rem;bottom:-7rem;background:radial-gradient(circle,#0d4d6f 0,transparent 72%)}'
        . '.page-wrap{position:relative;z-index:1;display:flex;flex-direction:column;align-items:stretch;gap:.65rem;width:min(100%,56rem);max-height:calc(100vh - 2.5rem)}'
        . '.card{width:100%;max-height:100%;display:flex;flex-direction:column;gap:1rem;padding:1.35rem 1.4rem;border-radius:1.25rem;background:linear-gradient(165deg,rgba(8,18,22,.94),rgba(4,10,14,.88));border:1px solid rgba(255,255,255,.08);box-shadow:0 28px 60px rgba(0,0,0,.45),inset 0 1px 0 rgba(255,255,255,.05);backdrop-filter:blur(18px);overflow:hidden;animation:card-in .45s ease}'
        . '@keyframes card-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}'
        . '.hero{display:flex;flex-direction:column;align-items:center;text-align:center;gap:.55rem;padding-bottom:.15rem;border-bottom:1px solid rgba(255,255,255,.06)}'
        . '.hero-logo{width:4rem;height:4rem;border-radius:1.1rem;box-shadow:0 10px 28px rgba(0,0,0,.35),0 0 0 1px rgba(255,255,255,.08)}'
        . '.badge{display:inline-block;padding:.18rem .7rem;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.02em;background:rgba(255,180,120,.12);border:1px solid rgba(255,180,120,.24);color:#ffd8b0}'
        . 'h1{margin:0;font-size:1.22rem;line-height:1.4}.lead{margin:0;max-width:36rem;font-size:.84rem;line-height:1.65;color:rgba(255,255,255,.76)}'
        . '.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.6rem}'
        . '.stat{padding:.65rem .75rem;border-radius:.8rem;background:rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.05);min-width:0;transition:border-color .2s ease,transform .2s ease}'
        . '.stat:hover{border-color:rgba(255,210,122,.18);transform:translateY(-1px)}'
        . '.stat strong{display:block;margin-bottom:.2rem;font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;color:rgba(255,255,255,.52)}'
        . '.stat span{display:block;font-size:.78rem;line-height:1.45;word-break:break-all;color:rgba(255,255,255,.92)}'
        . '.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;min-height:0}'
        . '.panel{display:flex;flex-direction:column;gap:.5rem;padding:.85rem;border-radius:.95rem;background:rgba(0,0,0,.24);border:1px solid rgba(255,255,255,.06);text-align:' . $textAlign . ';min-height:0}'
        . '.panel.panel-highlight{border-color:rgba(255,210,122,.32);box-shadow:0 0 0 1px rgba(255,210,122,.12),0 12px 28px rgba(0,0,0,.18);background:linear-gradient(160deg,rgba(255,210,122,.07),rgba(0,0,0,.22))}'
        . '.panel-head{display:flex;align-items:center;gap:.45rem}'
        . '.panel-icon{flex:0 0 auto;width:1.65rem;height:1.65rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.55rem;font-size:.82rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)}'
        . '.section-title{margin:0;font-size:.84rem;font-weight:700;color:#ffe9a8}'
        . '.panel p{margin:0;font-size:.76rem;line-height:1.55;color:rgba(255,255,255,.72)}'
        . '.command{display:flex;flex-direction:column;gap:.5rem;margin-top:auto}'
        . '.command code{display:block;width:100%;padding:.7rem .8rem;border-radius:.65rem;background:rgba(0,0,0,.46);border:1px solid rgba(120,220,160,.12);font:500 .75rem/1.6 Consolas,Monaco,monospace;color:#d8ffe6;white-space:pre-wrap;word-break:break-all;overflow:visible}'
        . '.btn{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;padding:.62rem .9rem;border:none;border-radius:.6rem;font:inherit;font-size:.78rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:transform .15s ease,box-shadow .15s ease,opacity .15s ease}'
        . '.btn:disabled{opacity:.5;cursor:not-allowed}.btn:not(:disabled):hover{transform:translateY(-1px)}'
        . '.btn-copy{align-self:flex-start;background:rgba(255,255,255,.06);color:#fff;border:1px solid rgba(255,255,255,.1)}'
        . '.btn-copy:not(:disabled):hover{box-shadow:0 8px 18px rgba(0,0,0,.22)}'
        . '.btn-install{width:100%;background:linear-gradient(135deg,#ffd27a 0,#ffb347 55%,#ff9f43 100%);color:#142026;box-shadow:0 10px 24px rgba(255,179,71,.22)}'
        . '.btn-install:not(:disabled):hover{box-shadow:0 14px 28px rgba(255,179,71,.32)}'
        . '.btn-install:not(:disabled){animation:install-pulse 2.4s ease-in-out infinite}'
        . '@keyframes install-pulse{0%,100%{box-shadow:0 10px 24px rgba(255,179,71,.22)}50%{box-shadow:0 12px 30px rgba(255,179,71,.36)}}'
        . '.btn-link{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:.55rem .75rem;border-radius:.6rem;background:rgba(0,0,0,.24);color:#fff;text-decoration:none;font-size:.76rem;border:1px solid rgba(255,255,255,.08)}'
        . '.status{padding:.6rem .7rem;border-radius:.6rem;font-size:.74rem;line-height:1.45}'
        . '.status.info{background:rgba(0,0,0,.24);color:rgba(255,255,255,.78);border:1px solid rgba(255,255,255,.05)}'
        . '.status.success{background:rgba(120,220,160,.12);color:#d7ffe7;border:1px solid rgba(120,220,160,.22)}'
        . '.status.warn{background:rgba(255,210,122,.1);color:#ffe9a8;border:1px solid rgba(255,210,122,.2)}'
        . '.status.error{background:rgba(255,120,120,.1);color:#ffd0d0;border:1px solid rgba(255,120,120,.2)}'
        . '.auto-actions{display:flex;flex-direction:column;gap:.5rem;margin-top:auto}'
        . '.steps{margin:0;padding:0;list-style:none;display:grid;gap:.45rem;text-align:' . $hintAlign . '}'
        . '.steps li{display:flex;align-items:flex-start;gap:.55rem;padding:.55rem .65rem;border-radius:.7rem;background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.04);font-size:.76rem;line-height:1.55;color:rgba(255,255,255,.78)}'
        . '.step-num{flex:0 0 auto;width:1.35rem;height:1.35rem;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;font-size:.68rem;font-weight:700;color:#142026;background:linear-gradient(135deg,#ffd27a,#ffb347)}'
        . '.steps-title{margin:0 0 .35rem;font-size:.82rem;font-weight:700;color:#ffe9a8}'
        . '.console{display:none;flex-direction:column;border-radius:.85rem;overflow:hidden;border:1px solid rgba(255,255,255,.08);background:#050809;min-height:0;flex-shrink:0}'
        . '.console.is-open{display:flex;min-height:12rem}'
        . '.console-top{display:flex;align-items:center;gap:.55rem;padding:.5rem .7rem;background:rgba(0,0,0,.32);border-bottom:1px solid rgba(255,255,255,.06)}'
        . '.console-dots{display:flex;gap:.28rem}.console-dots span{width:.55rem;height:.55rem;border-radius:50%;background:rgba(255,120,120,.75)}.console-dots span:nth-child(2){background:rgba(255,210,122,.75)}.console-dots span:nth-child(3){background:rgba(120,220,160,.75)}'
        . '.console-title{flex:1;font-size:.74rem;font-weight:700;color:rgba(255,255,255,.88)}'
        . '.console-badge{font-size:.68rem;padding:.12rem .45rem;border-radius:999px;background:rgba(255,210,122,.16);color:#ffe9a8;border:1px solid rgba(255,210,122,.22)}'
        . '.console-badge.success{background:rgba(120,220,160,.16);color:#d7ffe7;border-color:rgba(120,220,160,.22)}'
        . '.console-badge.error{background:rgba(255,120,120,.16);color:#ffd0d0;border-color:rgba(255,120,120,.22)}'
        . '.console-body{flex:1;min-height:12rem;max-height:18rem;overflow-y:auto;overflow-x:hidden;padding:.75rem .8rem;font:.72rem/1.55 Consolas,Monaco,monospace;color:#d9ffe0;white-space:pre-wrap;word-break:break-word;text-align:left;direction:ltr;scroll-behavior:smooth}'
        . '.console-line{display:block}.console-line.prompt{color:#8dffb0}.console-line.info{color:#ffe9a8}.console-line.error{color:#ffb4b4}.console-line.output{color:#d9ffe0}'
        . 'body.console-open{height:auto;overflow:auto}body.console-open .page-wrap,.page-wrap.console-open{max-height:none}'
        . '@media (max-width:760px){body{height:auto;overflow:auto;padding:.85rem}.page-wrap{max-height:none}.stats,.grid{grid-template-columns:1fr}.console.is-open{min-height:10rem}.console-body{min-height:10rem;max-height:14rem}}';
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
    pinoox_requirement_font_link($copy['lang']);
    echo '<style>';
    echo 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem;font-family:Tahoma,Segoe UI,sans-serif;background:linear-gradient(135deg,#124550,#011018);color:#fff;}';
    echo pinoox_requirement_font_styles($copy['lang']);
    echo pinoox_requirement_lang_switcher_styles();
    echo '.card{width:100%;max-width:34rem;padding:1.75rem 1.65rem;border-radius:1.15rem;background:rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.08);box-shadow:0 22px 48px rgba(0,0,0,.38);backdrop-filter:blur(14px);}';
    echo '.brand{text-align:center;margin-bottom:.85rem;} .brand img{width:3.5rem;height:3.5rem;border-radius:1rem;}';
    echo '.badge{display:inline-block;margin-bottom:.65rem;padding:.2rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700;background:rgba(255,120,120,.16);border:1px solid rgba(255,120,120,.24);color:#ffc4c4;}';
    echo 'h1{margin:0 0 .65rem;font-size:1.28rem;} p{margin:0 0 1rem;line-height:1.65;color:rgba(255,255,255,.82);}';
    echo '.facts{margin:0 0 1rem;padding:0;list-style:none;} .facts li{padding:.75rem .85rem;border-radius:.75rem;background:rgba(0,0,0,.32);border:1px solid rgba(255,255,255,.06);} .facts li+li{margin-top:.55rem;}';
    echo '.facts strong{display:block;font-size:.76rem;color:rgba(255,255,255,.68);margin-bottom:.2rem;} .facts span{font-size:.92rem;}';
    echo '.hints{margin:0;padding:0;list-style:none;text-align:' . ($copy['dir'] === 'rtl' ? 'right' : 'left') . ';} .hints li{position:relative;padding-' . ($copy['dir'] === 'rtl' ? 'right' : 'left') . ':1rem;margin-bottom:.55rem;line-height:1.6;color:rgba(255,255,255,.82);font-size:.86rem;}';
    echo '.hints li::before{content:"•";position:absolute;' . ($copy['dir'] === 'rtl' ? 'right' : 'left') . ':0;color:#ffd27a;}';
    echo '.hint-title{margin:0 0 .65rem;font-size:.92rem;color:#ffe9a8;}';
    echo '</style></head><body><div class="page-wrap"><div class="card" role="alert">';
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

    echo '</ul></div>';
    pinoox_render_requirement_lang_switcher($copy['lang'], $copy['language_label'] ?? 'Language');
    echo '</div></body></html>';
}

function pinoox_render_php_requirement_error(): void
{
    $locale = pinoox_requirement_locale();
    $copy = pinoox_requirement_messages($locale);
    $copy['language_label'] = $copy['language_label'] ?? 'Language';
    $facts = [
        $copy['required_label'] => pinoox_composer_php_constraint() . ' (>= ' . pinoox_min_php_version() . ')',
        $copy['current_label'] => PHP_VERSION,
    ];

    pinoox_render_requirement_page($copy, $facts, $copy['hints']);
}

function pinoox_render_vendor_missing_error(): void
{
    pinoox_load_composer_helper();

    $locale = pinoox_requirement_locale();
    $copy = pinoox_requirement_messages($locale);
    $ui = is_array($copy['vendor_ui'] ?? null) ? $copy['vendor_ui'] : [];
    $projectPath = pinoox_project_path();
    $terminalCommand = pinoox_composer_terminal_command();
    $actionUrl = pinoox_composer_action_url();

    if (PHP_SAPI === 'cli') {
        pinoox_render_vendor_missing_cli($copy, $ui, $projectPath, $terminalCommand);

        return;
    }

    $copy['title'] = $copy['vendor_title'];
    $copy['badge'] = (string) ($ui['badge'] ?? $copy['badge']);
    $copy['heading'] = $copy['vendor_heading'];
    $copy['message'] = $copy['vendor_message'];

    $facts = [
        $copy['project_path_label'] => $projectPath,
        $copy['expected_file_label'] => 'vendor/autoload.php',
        $copy['current_label'] => PHP_VERSION,
    ];

    $uiJson = json_encode([
        'actionUrl' => $actionUrl,
        'terminalCommand' => $terminalCommand,
        'copyCommand' => (string) ($ui['copy_command'] ?? 'Copy command'),
        'copied' => (string) ($ui['copied'] ?? 'Copied'),
        'terminalTitle' => (string) ($ui['terminal_title'] ?? ''),
        'terminalHint' => (string) ($ui['terminal_hint'] ?? ''),
        'autoTitle' => (string) ($ui['auto_title'] ?? ''),
        'autoHint' => (string) ($ui['auto_hint'] ?? ''),
        'installButton' => (string) ($ui['install_button'] ?? 'Install'),
        'installRunning' => (string) ($ui['install_running'] ?? 'Installing...'),
        'installSuccess' => (string) ($ui['install_success'] ?? 'Success'),
        'composerChecking' => (string) ($ui['composer_checking'] ?? 'Checking...'),
        'composerFound' => (string) ($ui['composer_found'] ?? 'Composer found'),
        'composerMissing' => (string) ($ui['composer_missing'] ?? 'Composer missing'),
        'composerDownload' => (string) ($ui['composer_download'] ?? 'Download Composer'),
        'shellDisabled' => (string) ($ui['shell_disabled'] ?? 'Shell disabled'),
        'autoUnavailable' => (string) ($ui['auto_unavailable'] ?? 'Automatic install unavailable'),
        'manualFallbackTitle' => (string) ($ui['manual_fallback_title'] ?? 'Suggested: manual install'),
        'manualFallbackMessage' => (string) ($ui['manual_fallback_message'] ?? ''),
        'manualFallbackHint' => (string) ($ui['manual_fallback_hint'] ?? ''),
        'installFailed' => (string) ($ui['install_failed'] ?? 'Install failed'),
        'consoleTitle' => (string) ($ui['console_title'] ?? 'Terminal'),
        'consoleWait' => (string) ($ui['console_wait'] ?? 'Please wait...'),
        'consoleRunning' => (string) ($ui['console_running'] ?? 'Running'),
        'consoleSuccess' => (string) ($ui['console_success'] ?? 'Success'),
        'consoleFailed' => (string) ($ui['console_failed'] ?? 'Failed'),
        'consoleExecuting' => (string) ($ui['console_executing'] ?? 'Executing command:'),
        'consoleWorking' => (string) ($ui['console_working'] ?? 'Still working...'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    $logo = htmlspecialchars(pinoox_requirement_logo_url(), ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($copy['title'], ENT_QUOTES, 'UTF-8');
    $badge = htmlspecialchars($copy['badge'], ENT_QUOTES, 'UTF-8');
    $heading = htmlspecialchars($copy['heading'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($copy['message'], ENT_QUOTES, 'UTF-8');
    $dir = $copy['dir'];
    $textAlign = $dir === 'rtl' ? 'right' : 'left';

    echo '<!doctype html><html lang="' . $copy['lang'] . '" dir="' . $dir . '"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . $title . '</title>';
    pinoox_requirement_font_link($copy['lang']);
    echo '<style>';
    echo pinoox_requirement_vendor_page_styles($dir, $textAlign);
    echo pinoox_requirement_font_styles($copy['lang']);
    echo pinoox_requirement_lang_switcher_styles();
    echo '</style></head><body><div class="page-wrap"><div class="card" role="alert">';
    echo '<header class="hero">';
    echo '<img class="hero-logo" src="' . $logo . '" alt="Pinoox">';
    echo '<span class="badge">' . $badge . '</span>';
    echo '<h1>' . $heading . '</h1><p class="lead">' . $message . '</p></header>';

    echo '<div class="stats">';

    foreach ($facts as $label => $value) {
        echo '<div class="stat"><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>';
        echo '<span>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</span></div>';
    }

    echo '</div><div class="grid">';

    echo '<section class="panel" id="manual-panel">';
    echo '<div class="panel-head"><span class="panel-icon" aria-hidden="true">⌘</span>';
    echo '<h2 class="section-title">' . htmlspecialchars((string) ($ui['terminal_title'] ?? ''), ENT_QUOTES, 'UTF-8') . '</h2></div>';
    echo '<p>' . htmlspecialchars((string) ($ui['terminal_hint'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<div class="command"><code id="terminal-command">' . htmlspecialchars($terminalCommand, ENT_QUOTES, 'UTF-8') . '</code>';
    echo '<button type="button" class="btn btn-copy" id="copy-command">' . htmlspecialchars((string) ($ui['copy_command'] ?? 'Copy'), ENT_QUOTES, 'UTF-8') . '</button></div></section>';

    echo '<section class="panel">';
    echo '<div class="panel-head"><span class="panel-icon" aria-hidden="true">⚡</span>';
    echo '<h2 class="section-title">' . htmlspecialchars((string) ($ui['auto_title'] ?? ''), ENT_QUOTES, 'UTF-8') . '</h2></div>';
    echo '<p>' . htmlspecialchars((string) ($ui['auto_hint'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<div class="auto-actions">';
    echo '<div id="composer-status" class="status info">' . htmlspecialchars((string) ($ui['composer_checking'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div id="auto-fallback" class="status warn" style="display:none"></div>';
    echo '<button type="button" class="btn btn-install" id="install-button" disabled>' . htmlspecialchars((string) ($ui['install_button'] ?? 'Install'), ENT_QUOTES, 'UTF-8') . '</button>';
    echo '</div></section></div>';

    if (!empty($copy['vendor_hints'])) {
        echo '<section><h2 class="steps-title">' . htmlspecialchars($copy['hint_title'] ?? '', ENT_QUOTES, 'UTF-8') . '</h2><ol class="steps">';

        foreach ($copy['vendor_hints'] as $index => $hint) {
            echo '<li><span class="step-num">' . ($index + 1) . '</span><span>' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</span></li>';
        }

        echo '</ol></section>';
    }

    echo '<section id="install-console" class="console" aria-live="polite">';
    echo '<div class="console-top"><div class="console-dots"><span></span><span></span><span></span></div>';
    echo '<span class="console-title">' . htmlspecialchars((string) ($ui['console_title'] ?? 'Terminal'), ENT_QUOTES, 'UTF-8') . '</span>';
    echo '<span id="console-badge" class="console-badge">' . htmlspecialchars((string) ($ui['console_running'] ?? 'Running'), ENT_QUOTES, 'UTF-8') . '</span></div>';
    echo '<div id="console-body" class="console-body"></div></section>';

    echo '</div>';
    pinoox_render_requirement_lang_switcher($locale, $copy['language_label'] ?? 'Language');
    echo '<script>window.PINOoxVendorSetup=' . ($uiJson ?: '{}') . ';</script>';
    echo <<<'JS'
<script>(function(){
var cfg=window.PINOoxVendorSetup||{};
var statusEl=document.getElementById("composer-status");
var installBtn=document.getElementById("install-button");
var copyBtn=document.getElementById("copy-command");
var commandEl=document.getElementById("terminal-command");
var autoActions=installBtn.parentElement;
var consoleEl=document.getElementById("install-console");
var consoleBody=document.getElementById("console-body");
var consoleBadge=document.getElementById("console-badge");
var manualPanel=document.getElementById("manual-panel");
var autoFallback=document.getElementById("auto-fallback");
var installing=false;
var outputNode=null;
var heartbeatNode=null;

function setStatus(text,kind){statusEl.className="status "+(kind||"info");statusEl.textContent=text;}
function shortVersion(v){if(!v)return"";var m=String(v).match(/(\d+\.\d+\.\d+)/);return m?m[1]:String(v).slice(0,24);}
function scrollConsole(){if(!consoleBody){return;}requestAnimationFrame(function(){requestAnimationFrame(function(){consoleBody.scrollTop=consoleBody.scrollHeight;});});}
function clearHeartbeat(){if(heartbeatNode&&heartbeatNode.parentNode){heartbeatNode.parentNode.removeChild(heartbeatNode);}heartbeatNode=null;}
function showHeartbeat(text){if(!heartbeatNode){heartbeatNode=document.createElement("div");heartbeatNode.className="console-line info";consoleBody.appendChild(heartbeatNode);}heartbeatNode.textContent=text;scrollConsole();}
function appendConsole(text,kind){if(kind){outputNode=null;clearHeartbeat();var line=document.createElement("div");line.className="console-line "+kind;line.textContent=text;consoleBody.appendChild(line);scrollConsole();return;}clearHeartbeat();if(!outputNode){outputNode=document.createElement("div");outputNode.className="console-line output";consoleBody.appendChild(outputNode);}outputNode.textContent+=String(text||"").replace(/\r/g,"\n");scrollConsole();}
function setConsoleBadge(text,kind){consoleBadge.textContent=text;consoleBadge.className="console-badge"+(kind?" "+kind:"");}
function openConsole(){consoleEl.classList.add("is-open");document.body.classList.add("console-open");if(consoleEl.parentElement){consoleEl.parentElement.classList.add("console-open");}scrollConsole();}
function showManualFallback(opts){opts=opts||{};if(manualPanel){manualPanel.classList.add("panel-highlight");if(!opts.silent){manualPanel.scrollIntoView({behavior:"smooth",block:"nearest"});}}if(autoFallback){autoFallback.style.display="block";autoFallback.textContent=cfg.manualFallbackMessage||cfg.manualFallbackHint||"";}if(opts.status!==false){setStatus((cfg.autoUnavailable||cfg.installFailed||"")+" "+(cfg.manualFallbackHint||""),"warn");}if(opts.console){openConsole();appendConsole(cfg.manualFallbackTitle||"Manual install","info");appendConsole(cfg.manualFallbackMessage||"","info");}}
function messageForError(code){if(code==="composer_missing")return cfg.composerMissing||"Composer missing";if(code==="shell_disabled")return cfg.shellDisabled||"Shell disabled";if(code==="php_version")return cfg.installFailed||"Install failed";return cfg.consoleFailed||"Installation failed";}
function applyStatus(data){var composer=(data&&data.composer)||{};autoActions.querySelectorAll(".btn-link").forEach(function(node){node.remove();});if(data&&data.vendor_installed){setStatus(cfg.installSuccess||"Installed","success");setTimeout(function(){location.reload();},900);return;}if(!composer.shell_available){showManualFallback({status:false});setStatus((cfg.autoUnavailable||cfg.shellDisabled||"")+" "+(cfg.manualFallbackHint||""),"warn");installBtn.disabled=true;return;}if(composer.installed){setStatus((cfg.composerFound||"Composer found").replace("{source}",composer.source||"")+(composer.version?" · v"+shortVersion(composer.version):""),"success");installBtn.disabled=installing;return;}setStatus(cfg.composerMissing||"Composer missing","warn");showManualFallback({status:false,console:false,silent:true});installBtn.disabled=true;var link=document.createElement("a");link.className="btn-link";link.href=(data&&data.composer_download_url)||"https://getcomposer.org/download/";link.target="_blank";link.rel="noopener";link.textContent=cfg.composerDownload||"Download Composer";autoActions.appendChild(link);}
copyBtn.addEventListener("click",function(){var text=commandEl.textContent||"";if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(text).then(function(){copyBtn.textContent=cfg.copied||"Copied";setTimeout(function(){copyBtn.textContent=cfg.copyCommand||"Copy";},1800);});return;}var area=document.createElement("textarea");area.value=text;document.body.appendChild(area);area.select();try{document.execCommand("copy");copyBtn.textContent=cfg.copied||"Copied";setTimeout(function(){copyBtn.textContent=cfg.copyCommand||"Copy";},1800);}catch(e){}document.body.removeChild(area);});
fetch(cfg.actionUrl+"?action=status",{credentials:"same-origin"}).then(function(r){return r.json();}).then(applyStatus).catch(function(){showManualFallback({status:false});setStatus((cfg.autoUnavailable||"Unable to check Composer")+" "+(cfg.manualFallbackHint||""),"warn");});
function failInstall(message,output){installing=false;setConsoleBadge(cfg.consoleFailed||"Failed","error");setStatus((message||cfg.installFailed||"Install failed")+" "+(cfg.manualFallbackHint||""),"error");if(output){appendConsole("\n"+output,"error");}showManualFallback({console:true,status:false});installBtn.disabled=false;}
async function handleStreamEvent(event){if(!event||!event.type)return;if(event.type==="heartbeat"){showHeartbeat(cfg.consoleWorking||"Still working...");return;}if(event.type==="start"){consoleBody.textContent="";heartbeatNode=null;outputNode=null;appendConsole("> cd \""+(event.cwd||"")+"\"","prompt");appendConsole("> "+(event.display_command||event.command||""),"prompt");appendConsole("","prompt");appendConsole(cfg.consoleExecuting||"Executing command:","info");appendConsole(cfg.consoleWait||"Please wait...","info");appendConsole("","info");return;}if(event.type==="error"){appendConsole(messageForError(event.message),"error");return;}if(event.type==="output"&&event.text){appendConsole(event.text);return;}if(event.type==="done"){clearHeartbeat();if(event.success){installing=false;setConsoleBadge(cfg.consoleSuccess||"Success","success");setStatus(cfg.installSuccess||"Success","success");setTimeout(function(){location.reload();},1200);return;}failInstall(cfg.installFailed,event.output||messageForError(event.message||""));}}
installBtn.addEventListener("click",async function(){if(installing)return;installing=true;installBtn.disabled=true;openConsole();consoleBody.textContent="";setConsoleBadge(cfg.consoleRunning||"Running");setStatus(cfg.installRunning||"Installing...","info");try{var response=await fetch(cfg.actionUrl+"?action=install-stream",{method:"POST",credentials:"same-origin"});if(!response.ok||!response.body){throw new Error("stream_failed");}var reader=response.body.getReader();var decoder=new TextDecoder();var buffer="";while(true){var chunk=await reader.read();if(chunk.done)break;buffer+=decoder.decode(chunk.value,{stream:true});var lines=buffer.split("\n");buffer=lines.pop()||"";for(var i=0;i<lines.length;i++){var line=lines[i].trim();if(!line)continue;try{await handleStreamEvent(JSON.parse(line));}catch(e){}}}if(buffer.trim()){try{await handleStreamEvent(JSON.parse(buffer.trim()));}catch(e){}}}catch(e){failInstall(cfg.installFailed,String(e&&e.message?e.message:e));}});
})();</script>
JS;
    echo '</div></body></html>';
}
