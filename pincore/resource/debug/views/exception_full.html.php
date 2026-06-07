<!-- <?= $_message = sprintf('%s (%d %s)', $exceptionMessage, $statusCode, $statusText); ?> -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="<?= $this->charset; ?>" />
        <meta name="robots" content="noindex,nofollow" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title><?= $_message; ?> · Pinoox</title>
        <?php if (!empty($pinoox['logo_data_uri'])) { ?>
        <link rel="icon" type="image/png" href="<?= htmlspecialchars((string) $pinoox['logo_data_uri'], ENT_QUOTES); ?>" />
        <?php } ?>
        <style><?= $this->include('assets/css/exception.css'); ?></style>
        <style><?= $this->include('assets/css/exception_full.css'); ?></style>
        <style><?= $this->include('assets/css/pinoox-exception.css'); ?></style>
    </head>
    <body class="pinoox-exception-page sf-reset px-hide-pincore px-hide-vendor<?= \Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier::isFrameworkSurfacePath(str_replace('\\', '/', (string) $exception->getFile()), (string) ($pinoox['project_root'] ?? '') ?: null) ? ' px-thrown-in-pincore' : ''; ?><?= !empty($pinoox['portal']['via_portal']) ? ' px-portal-error' : ''; ?>">
        <script>
            (function () {
                var saved = localStorage.getItem('pinoox/debug-theme');
                var theme = saved === 'light' ? 'theme-light' : 'theme-dark';
                document.body.classList.add(theme);
            })();
        </script>

        <header class="px-topbar">
            <div class="px-container px-topbar-inner">
                <a class="px-brand" href="<?= htmlspecialchars((string) ($pinoox['homepage'] ?? 'https://www.pinoox.com/'), ENT_QUOTES); ?>" target="_blank" rel="noopener">
                    <?php if (!empty($pinoox['logo_data_uri'])) { ?>
                        <img class="px-logo" src="<?= htmlspecialchars((string) $pinoox['logo_data_uri'], ENT_QUOTES); ?>" alt="Pinoox">
                    <?php } else { ?>
                        <span class="px-logo-fallback">P</span>
                    <?php } ?>
                    <span class="px-brand-text">
                        <strong>Pinoox Exception</strong>
                        <small>Developer debug console · Pinoox <?= htmlspecialchars((string) ($pinoox['pinoox_version']['label'] ?? '—'), ENT_QUOTES); ?> · App <?= htmlspecialchars((string) ($pinoox['app_version']['label'] ?? '—'), ENT_QUOTES); ?></small>
                    </span>
                </a>

                <div class="px-topbar-actions">
                    <button type="button" class="px-btn" data-copy="message" title="Copy error message">Copy message</button>
                    <button type="button" class="px-btn" data-copy="trace" title="Copy stack trace">Copy trace</button>
                    <button type="button" class="px-btn px-btn-ghost" id="px-theme-toggle" title="Switch to light mode">Light mode</button>
                    <a class="px-btn px-btn-primary" href="<?= htmlspecialchars((string) ($pinoox['docs_url'] ?? 'https://www.pinoox.com/docs'), ENT_QUOTES); ?>" target="_blank" rel="noopener">
                        <?= htmlspecialchars((string) ($pinoox['docs_label'] ?? 'Pinoox Docs'), ENT_QUOTES); ?>
                    </a>
                </div>
            </div>
        </header>

        <?= $this->include('views/exception.html.php', [
            'exception' => $exception,
            'exceptionMessage' => $exceptionMessage,
            'statusText' => $statusText,
            'statusCode' => $statusCode,
            'logger' => $logger,
            'currentContent' => $currentContent,
            'pinoox' => $pinoox,
            'hints' => $hints,
        ]); ?>

        <script type="application/json" id="px-exception-payload"><?= htmlspecialchars(json_encode([
            'message' => strip_tags((string) $exceptionMessage),
            'status' => (int) $statusCode,
            'class' => $exception->getClass(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => (string) ($pinoox['request']['url'] ?? ''),
            'method' => (string) ($pinoox['request']['method'] ?? 'GET'),
            'headers' => $pinoox['request']['headers'] ?? [],
            'body' => (string) ($pinoox['request']['body'] ?? ''),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_NOQUOTES); ?></script>

        <script><?= $this->include('assets/js/exception.js'); ?></script>
        <script><?= $this->include('assets/js/pinoox-exception.js'); ?></script>
    </body>
</html>
<!-- <?= $_message; ?> -->
