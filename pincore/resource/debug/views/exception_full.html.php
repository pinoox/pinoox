<!-- <?= $_message = sprintf('%s (%d %s)', $exceptionMessage, $statusCode, $statusText); ?> -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="<?= $this->charset; ?>" />
        <meta name="robots" content="noindex,nofollow" />
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
        <title><?= $_message; ?> · Pinoox</title>
        <?php if (!empty($pinoox['logo_data_uri'])) { ?>
        <link rel="icon" type="image/png" href="<?= htmlspecialchars((string) $pinoox['logo_data_uri'], ENT_QUOTES); ?>" />
        <?php } ?>
        <style><?= $this->include('assets/css/exception.css'); ?></style>
        <style><?= $this->include('assets/css/exception_full.css'); ?></style>
        <style><?= $this->include('assets/css/pinoox-exception.css'); ?></style>
        <style><?= $this->include('assets/css/exception_controls.css'); ?></style>
        <style><?= $this->include('assets/css/exception_embed.css'); ?></style>
    </head>
    <body class="pinoox-exception-page sf-reset<?= \Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier::isFrameworkSurfacePath(str_replace('\\', '/', (string) $exception->getFile()), (string) ($pinoox['project_root'] ?? '') ?: null) ? ' px-thrown-in-pincore' : ''; ?><?= !empty($pinoox['portal']['via_portal']) ? ' px-portal-error' : ''; ?><?= !empty($pinoox['meeting']['active']) ? ' px-meeting-error' : ''; ?><?= !empty($networkPreview) ? ' px-network-preview' : ''; ?>">
        <div class="px-focus-guard" aria-hidden="true">
            <input type="checkbox" id="px-theme-light" class="px-sr-input">
            <input type="checkbox" id="px-filter-pincore" class="px-sr-input">
            <input type="checkbox" id="px-filter-vendor" class="px-sr-input">
            <input type="checkbox" id="px-expand-all" class="px-sr-input">
            <input type="radio" name="px-tab" id="px-tab-exception" class="px-tab-input" checked>
            <?php if (!empty($networkPreview)) { ?>
            <input type="radio" name="px-tab" id="px-tab-pincore" class="px-tab-input">
            <input type="radio" name="px-tab" id="px-tab-vendor" class="px-tab-input">
            <?php } ?>
            <input type="radio" name="px-tab" id="px-tab-stack" class="px-tab-input">
            <input type="radio" name="px-tab" id="px-tab-portal" class="px-tab-input">
            <?php if ($logger) { ?>
            <input type="radio" name="px-tab" id="px-tab-logs" class="px-tab-input">
            <?php } ?>
            <input type="radio" name="px-tab" id="px-tab-context" class="px-tab-input">
            <input type="radio" name="px-tab" id="px-tab-tools" class="px-tab-input">
        </div>

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
                    <label for="px-tab-tools" class="px-btn px-control-label px-full-only">Copy fields</label>
                    <label for="px-theme-light" class="px-btn px-btn-ghost px-control-label"><span class="px-theme-label-text" aria-hidden="true"></span><span class="px-sr-only">Toggle light/dark theme</span></label>
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
            'networkPreview' => $networkPreview ?? false,
        ]); ?>
    </body>
</html>
<!-- <?= $_message; ?> -->
