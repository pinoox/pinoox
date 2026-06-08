<?php
use Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier;

$exceptionAsArray = $exception->toArray();
$exceptionAsArrayCount = count($exceptionAsArray);

$projectRoot = str_replace('\\', '/', (string) ($pinoox['project_root'] ?? ''));
$projectRootArg = $projectRoot !== '' ? $projectRoot : null;

$primaryFile = str_replace('\\', '/', (string) $exception->getFile());
$primaryLine = (int) $exception->getLine();
$primaryRelative = $primaryFile;
if ($projectRoot !== '' && str_starts_with($primaryFile, $projectRoot)) {
    $primaryRelative = ltrim(substr($primaryFile, strlen($projectRoot)), '/');
}

$portalError = !empty($pinoox['portal']['via_portal']);
$thrownInFramework = TraceFrameClassifier::isFrameworkSurfacePath($primaryFile, $projectRootArg);
$thrownInPortal = $portalError && str_contains($primaryFile, '/Component/Source/Portal.php');
$thrownInProjectEntry = TraceFrameClassifier::isProjectEntryPath($primaryFile, $projectRootArg);
$thrownInSystem = TraceFrameClassifier::isSystemPath($primaryFile, $projectRootArg);
$request = $pinoox['request'] ?? [];
?>

<main class="px-main px-preview-main">
    <div class="px-container">
        <section class="px-preview-banner" role="note">
            <p>
                <strong>API / Network preview</strong> — this HTML is the exact failed response body.
                Opening the same URL in a new browser tab may return a different result (login redirect, GET instead of POST, missing headers).
                Scroll here to inspect the error, or replay the request with the cURL command below.
            </p>
        </section>

        <section class="px-hero">
            <div class="px-hero-top">
                <div class="px-badges">
                    <span class="px-badge px-badge-error">HTTP <?= (int) $statusCode; ?></span>
                    <span class="px-badge"><?= htmlspecialchars((string) $statusText, ENT_QUOTES); ?></span>
                    <span class="px-badge px-badge-muted"><?= $this->abbrClass($exception->getClass()); ?></span>
                </div>
            </div>

            <h1 class="px-hero-message break-long-words"><?= $this->formatFileFromText(nl2br($exceptionMessage)); ?></h1>

            <?php if ($primaryFile !== '' && !$thrownInFramework) { ?>
            <p class="px-hero-location">
                Thrown in
                <code><?= htmlspecialchars($primaryRelative, ENT_QUOTES); ?></code>
                at line <strong><?= $primaryLine; ?></strong>
            </p>
            <?php } elseif ($thrownInFramework) { ?>
            <p class="px-hero-location px-hero-location-muted">
                <?php if ($thrownInPortal) { ?>
                Error surfaced in Portal delegate
                <?php } elseif ($thrownInProjectEntry) { ?>
                Error surfaced in project entry
                <?php } elseif ($thrownInSystem) { ?>
                Error surfaced in system layer
                <?php } else { ?>
                Error surfaced inside framework
                <?php } ?>
                <code><?= htmlspecialchars($primaryRelative, ENT_QUOTES); ?>:<?= $primaryLine; ?></code>
            </p>
            <?php } ?>

            <?= $this->include('views/partials/exception_meta_bar.html.php', ['pinoox' => $pinoox]); ?>
        </section>

        <?php if (!empty($pinoox['route'])) {
            $routeCtx = $pinoox['route'];
            $primarySource = $routeCtx['action_source'] ?? $routeCtx['route_source'] ?? null;
        ?>
        <section class="px-route-context">
            <div class="px-route-context-head">
                <h2>Route context</h2>
                <p><code><?= htmlspecialchars((string) ($routeCtx['path'] ?? ''), ENT_QUOTES); ?></code><?= !empty($routeCtx['name']) ? ' · ' . htmlspecialchars((string) $routeCtx['name'], ENT_QUOTES) : ''; ?></p>
            </div>
            <?php if (!empty($primarySource['snippet'])) { ?>
            <details class="px-disclosure px-disclosure-route px-disclosure-route-primary" open>
                <summary>
                    <span>Action definition</span>
                    <code class="px-disclosure-file"><?= htmlspecialchars((string) ($primarySource['relative_file'] ?? ''), ENT_QUOTES); ?>:<?= (int) ($primarySource['line'] ?? 0); ?></code>
                </summary>
                <div class="px-disclosure-body">
                    <?= $this->include('views/partials/source_snippet.html.php', ['source' => $primarySource]); ?>
                </div>
            </details>
            <?php } ?>
        </section>
        <?php } ?>

        <?php if (!empty($hints)) { ?>
        <?= $this->include('views/partials/suggested_fixes.html.php', [
            'hints' => $hints,
            'pinoox' => $pinoox,
        ]); ?>
        <?php } ?>

        <section class="px-preview-section">
            <h2 class="px-preview-heading">Stack trace</h2>
            <div class="px-stack-wrap px-preview-stack">
                <?php
                foreach ($exceptionAsArray as $i => $e) {
                    echo $this->include('views/traces_text.html.php', [
                        'exception' => $e,
                        'index' => $i + 1,
                        'numExceptions' => $exceptionAsArrayCount,
                    ]);
                }
                ?>
            </div>
        </section>

        <section class="px-preview-section">
            <h2 class="px-preview-heading">Request</h2>
            <dl class="px-kv-grid">
                <dt>URL</dt><dd class="break-long-words"><?= htmlspecialchars((string) ($request['url'] ?? ''), ENT_QUOTES); ?></dd>
                <dt>Method</dt><dd><?= htmlspecialchars((string) ($request['method'] ?? ''), ENT_QUOTES); ?></dd>
                <dt>Accept</dt><dd class="break-long-words"><?= htmlspecialchars((string) (($request['accept'] ?? '') !== '' ? $request['accept'] : '—'), ENT_QUOTES); ?></dd>
                <dt>Referer</dt><dd class="break-long-words"><?= htmlspecialchars((string) (($request['referer'] ?? '') !== '' ? $request['referer'] : '—'), ENT_QUOTES); ?></dd>
            </dl>
        </section>

        <?php if (!empty($request['curl'])) { ?>
        <section class="px-preview-section">
            <h2 class="px-preview-heading">Replay with cURL</h2>
            <pre class="px-code-block px-preview-curl"><?= htmlspecialchars((string) $request['curl'], ENT_QUOTES); ?></pre>
        </section>
        <?php } ?>

        <?php if (($request['body'] ?? '') !== '') { ?>
        <section class="px-preview-section">
            <details class="px-disclosure" open>
                <summary><span>Request body</span></summary>
                <div class="px-disclosure-body">
                    <pre class="px-code-block"><?= htmlspecialchars((string) $request['body'], ENT_QUOTES); ?></pre>
                </div>
            </details>
        </section>
        <?php } ?>

        <section class="px-preview-section">
            <details class="px-disclosure">
                <summary><span>Headers</span><span class="px-disclosure-meta"><?= count($request['headers'] ?? []); ?></span></summary>
                <div class="px-disclosure-body">
                    <pre class="px-code-block"><?= htmlspecialchars(json_encode($request['headers'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?></pre>
                </div>
            </details>
        </section>
    </div>
</main>
