<!-- <?= $_message = sprintf('%s (%d %s)', $exceptionMessage, $statusCode, $statusText); ?> -->
<!DOCTYPE html>
<html lang="en" class="px-network-preview">
    <head>
        <meta charset="<?= $this->charset; ?>" />
        <meta name="robots" content="noindex,nofollow" />
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
        <title><?= $_message; ?> · Pinoox</title>
        <?php if (!empty($pinoox['logo_data_uri'])) { ?>
        <link rel="icon" type="image/png" href="<?= htmlspecialchars((string) $pinoox['logo_data_uri'], ENT_QUOTES); ?>" />
        <?php } ?>
        <style><?= $this->include('assets/css/exception.css'); ?></style>
        <style><?= $this->include('assets/css/pinoox-exception.css'); ?></style>
        <style><?= $this->include('assets/css/exception_controls.css'); ?></style>
        <style><?= $this->include('assets/css/exception_embed.css'); ?></style>
    </head>
    <body class="pinoox-exception-page sf-reset theme-dark px-network-preview">
        <header class="px-topbar px-preview-topbar">
            <div class="px-container px-topbar-inner">
                <div class="px-brand">
                    <?php if (!empty($pinoox['logo_data_uri'])) { ?>
                        <img class="px-logo" src="<?= htmlspecialchars((string) $pinoox['logo_data_uri'], ENT_QUOTES); ?>" alt="Pinoox">
                    <?php } else { ?>
                        <span class="px-logo-fallback">P</span>
                    <?php } ?>
                    <span class="px-brand-text">
                        <strong>Pinoox Exception</strong>
                        <small>Network preview · read-only · no JavaScript required</small>
                    </span>
                </div>
            </div>
        </header>

        <?= $this->include('views/exception_preview.html.php', [
            'exception' => $exception,
            'exceptionMessage' => $exceptionMessage,
            'statusText' => $statusText,
            'statusCode' => $statusCode,
            'logger' => $logger,
            'currentContent' => $currentContent,
            'pinoox' => $pinoox,
            'hints' => $hints,
        ]); ?>
    </body>
</html>
<!-- <?= $_message; ?> -->
