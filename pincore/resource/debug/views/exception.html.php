<?php
use Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier;

$exceptionAsArray = $exception->toArray();
$exceptionWithUserCode = [];
$exceptionAsArrayCount = count($exceptionAsArray);
$last = $exceptionAsArrayCount - 1;

$projectRoot = str_replace('\\', '/', (string) ($pinoox['project_root'] ?? ''));
$projectRootArg = $projectRoot !== '' ? $projectRoot : null;

foreach ($exceptionAsArray as $i => $e) {
    foreach ($e['trace'] as $trace) {
        if (TraceFrameClassifier::isUserCodeFrame($trace, $projectRootArg) && $i < $last) {
            $exceptionWithUserCode[] = $i;
        }
    }
}

$primaryFile = str_replace('\\', '/', (string) $exception->getFile());
$primaryLine = (int) $exception->getLine();
$primaryRelative = $primaryFile;
if ($projectRoot !== '' && str_starts_with($primaryFile, $projectRoot)) {
    $primaryRelative = ltrim(substr($primaryFile, strlen($projectRoot)), '/');
}

$portalError = !empty($pinoox['portal']['via_portal']);
$hasPortalContext = !empty($pinoox['portal']['source']['snippet']) || !empty($pinoox['portal']['call']);
$thrownInFramework = TraceFrameClassifier::isFrameworkSurfacePath($primaryFile, $projectRootArg);
$thrownInPortal = $portalError && str_contains($primaryFile, '/Component/Source/Portal.php');
$thrownInProjectEntry = TraceFrameClassifier::isProjectEntryPath($primaryFile, $projectRootArg);
$thrownInSystem = TraceFrameClassifier::isSystemPath($primaryFile, $projectRootArg);

$plainMessage = html_entity_decode(strip_tags((string) $exceptionMessage), ENT_QUOTES, 'UTF-8');
$plainMessageLine = $exception->getClass() . ': ' . $plainMessage;
if ($exception->getFile()) {
    $plainMessageLine .= "\n at " . $exception->getFile() . ':' . $exception->getLine();
}

$stackTraceParts = [];
foreach ($exceptionAsArray as $i => $e) {
    $chunk = $e['class'] . ":\n";
    if (!empty($e['message'])) {
        $chunk .= $e['message'] . "\n";
    }
    foreach ($e['trace'] as $trace) {
        $chunk .= "\n  ";
        if (!empty($trace['function'])) {
            $chunk .= 'at ' . $trace['class'] . $trace['type'] . $trace['function'] . '(' . (isset($trace['args']) ? $this->formatArgsAsText($trace['args']) : '') . ')';
        }
        if (!empty($trace['file']) && !empty($trace['line'])) {
            $chunk .= (!empty($trace['function']) ? "\n     (" : 'at ') . $trace['file'] . ':' . $trace['line'] . (!empty($trace['function']) ? ')' : '');
        }
    }
    $stackTraceParts[] = trim($chunk);
}
$plainStackTrace = implode("\n\n", $stackTraceParts);

$traceWrapHtml = '';
foreach ($exceptionAsArray as $i => $e) {
    $traceWrapHtml .= $this->include('views/traces.html.php', [
        'exception' => $e,
        'index' => $i + 1,
        'expand' => in_array($i, $exceptionWithUserCode, true) || ([] === $exceptionWithUserCode && 0 === $i),
        'portalError' => $portalError,
        'projectRoot' => $projectRootArg,
    ]);
}
?>

<main class="px-main">
    <div class="px-container">
        <?php if (!empty($networkPreview)) { ?>
        <section class="px-preview-banner" role="note">
            <p>
                <strong>Network preview</strong> — tabs work without JavaScript. Use the cURL block in Tools to replay this request.
            </p>
        </section>
        <?php } ?>

        <?php if (!empty($pinoox['meeting']['active'])) {
            $meetingHost = trim((string) ($pinoox['meeting']['host'] ?? ''));
            $meetingGuest = trim((string) ($pinoox['meeting']['guest'] ?? ''));
        ?>
        <section class="px-preview-banner px-meeting-banner" role="note">
            <p>
                <strong>Meeting mode</strong> —
                <?php if ($meetingHost !== '' && $meetingGuest !== '') { ?>
                guest app <code><?= htmlspecialchars($meetingGuest, ENT_QUOTES); ?></code> running inside host <code><?= htmlspecialchars($meetingHost, ENT_QUOTES); ?></code>
                <?php } elseif ($meetingGuest !== '') { ?>
                guest app <code><?= htmlspecialchars($meetingGuest, ENT_QUOTES); ?></code>
                <?php } else { ?>
                a guest app invoked via <code>App::meeting()</code>
                <?php } ?>
            </p>
        </section>
        <?php } ?>

        <section class="px-hero">
            <div class="px-hero-top">
                <div class="px-badges">
                    <span class="px-badge px-badge-error">HTTP <?= (int) $statusCode; ?></span>
                    <span class="px-badge"><?= htmlspecialchars((string) $statusText, ENT_QUOTES); ?></span>
                    <span class="px-badge px-badge-muted"><?= $this->abbrClass($exception->getClass()); ?></span>
                </div>
                <?php if ($exceptionAsArrayCount > 1) { ?>
                <div class="px-chain">
                    <?php foreach (array_reverse($exception->getAllPrevious(), true) as $index => $previousException) { ?>
                        <a href="#trace-box-<?= $index + 2; ?>" class="px-chain-link"><?= $this->abbrClass($previousException->getClass()); ?></a>
                        <span class="px-chain-sep">›</span>
                    <?php } ?>
                </div>
                <?php } ?>
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

        <?php if (empty($networkPreview)) { ?>
        <?php if (!empty($pinoox['route'])) {
            $routeCtx = $pinoox['route'];
            $routeMethods = implode('|', (array) ($routeCtx['methods'] ?? ['GET']));
            $primarySource = $routeCtx['action_source'] ?? $routeCtx['route_source'] ?? null;
        ?>
        <section class="px-route-context">
            <div class="px-route-context-head">
                <h2>Route context</h2>
                <p>Where this request was handled in your route files</p>
            </div>

            <dl class="px-route-grid">
                <?php if (!empty($routeCtx['methods'])) { ?>
                <dt>HTTP</dt>
                <dd><code><?= htmlspecialchars($routeMethods, ENT_QUOTES); ?></code> <span class="px-route-path"><?= htmlspecialchars((string) ($routeCtx['path'] ?? ''), ENT_QUOTES); ?></span></dd>
                <?php } ?>
                <?php if (!empty($routeCtx['name'])) { ?>
                <dt>Route name</dt>
                <dd><code><?= htmlspecialchars((string) $routeCtx['name'], ENT_QUOTES); ?></code></dd>
                <?php } ?>
                <?php if (!empty($routeCtx['action_ref'])) { ?>
                <dt>Action</dt>
                <dd><code><?= htmlspecialchars((string) $routeCtx['action_ref'], ENT_QUOTES); ?></code></dd>
                <?php } ?>
                <?php if (!empty($routeCtx['handler']) && ($routeCtx['handler'] ?? '') !== ($routeCtx['action_ref'] ?? '')) { ?>
                <dt>Handler</dt>
                <dd><code><?= htmlspecialchars((string) $routeCtx['handler'], ENT_QUOTES); ?></code></dd>
                <?php } ?>
            </dl>

            <?php if (!empty($routeCtx['action_source']) && !empty($routeCtx['route_source'])
                && ($routeCtx['action_source']['relative_file'] ?? '') !== ($routeCtx['route_source']['relative_file'] ?? '')
            ) { ?>
            <details class="px-disclosure px-disclosure-route" open>
                <summary>
                    <span>Route registration</span>
                    <code class="px-disclosure-file"><?= htmlspecialchars((string) ($routeCtx['route_source']['relative_file'] ?? ''), ENT_QUOTES); ?>:<?= (int) ($routeCtx['route_source']['line'] ?? 0); ?></code>
                </summary>
                <div class="px-disclosure-body">
                    <?= $this->include('views/partials/source_snippet.html.php', ['source' => $routeCtx['route_source']]); ?>
                </div>
            </details>
            <?php } ?>

            <?php if (!empty($primarySource['snippet'])) { ?>
            <details class="px-disclosure px-disclosure-route px-disclosure-route-primary" open>
                <summary>
                    <span><?= !empty($routeCtx['action_source']) ? 'Action definition' : 'Route definition'; ?></span>
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
        <?php } ?>

        <section class="px-workspace">
            <nav class="px-tabs" role="tablist">
                <?php if (!empty($networkPreview)) { ?>
                <label for="px-tab-exception" class="px-tab px-control-label" role="tab">Your code</label>
                <label for="px-tab-pincore" class="px-tab px-control-label" role="tab">Pincore</label>
                <label for="px-tab-vendor" class="px-tab px-control-label" role="tab">Vendor</label>
                <?php } else { ?>
                <label for="px-tab-exception" class="px-tab px-control-label" role="tab">Exception</label>
                <?php } ?>
                <label for="px-tab-stack" class="px-tab px-control-label" role="tab">Stack trace</label>
                <?php if ($hasPortalContext) { ?>
                <label for="px-tab-portal" class="px-tab px-control-label" role="tab">Portal</label>
                <?php } ?>
                <?php if ($logger) { ?>
                <label for="px-tab-logs" class="px-tab px-control-label" role="tab">
                    Logs<?php if ($logger->countErrors()) { ?><span class="px-tab-badge"><?= $logger->countErrors(); ?></span><?php } ?>
                </label>
                <?php } ?>
                <label for="px-tab-context" class="px-tab px-control-label" role="tab">Context</label>
                <label for="px-tab-tools" class="px-tab px-control-label" role="tab">Tools</label>
            </nav>

            <div class="px-tab-panels">
                <?php if (!empty($networkPreview)) { ?>
                <div class="px-trace-stage" data-px-trace-stage>
                    <div class="px-trace-wrap">
                        <?= $traceWrapHtml; ?>
                    </div>
                </div>
                <?php } else { ?>
                <div class="px-tab-panel" data-px-panel="exception" role="tabpanel">
                    <div class="px-panel-toolbar px-panel-toolbar--scroll px-full-only">
                        <a class="px-btn px-btn-small px-btn-primary" href="#px-trace-origin">Jump to your code</a>
                        <label for="px-filter-pincore" class="px-btn px-btn-small px-btn-ghost px-control-label">Show pincore frames</label>
                        <label for="px-filter-vendor" class="px-btn px-btn-small px-btn-ghost px-control-label">Show vendor frames</label>
                        <label for="px-expand-all" class="px-btn px-btn-small px-control-label">Expand all</label>
                    </div>
                    <div class="px-trace-wrap">
                        <?= $traceWrapHtml; ?>
                    </div>
                </div>
                <?php } ?>

                <div class="px-tab-panel" data-px-panel="stack" role="tabpanel">
                    <p class="px-panel-hint">Select the text below to copy the stack trace.</p>
                    <div class="px-stack-wrap" id="px-copy-trace">
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
                </div>

                <?php if ($hasPortalContext) { ?>
                <div class="px-tab-panel" data-px-panel="portal" role="tabpanel">
                    <?= $this->include('views/partials/portal_context_panel.html.php', ['pinoox' => $pinoox]); ?>
                </div>
                <?php } ?>

                <?php if ($logger) { ?>
                <div class="px-tab-panel" data-px-panel="logs" role="tabpanel">
                    <?php if ($logger->getLogs()) { ?>
                        <?= $this->include('views/logs.html.php', ['logs' => $logger->getLogs()]); ?>
                    <?php } else { ?>
                        <div class="px-empty">No log messages recorded for this request.</div>
                    <?php } ?>
                </div>
                <?php } ?>

                <div class="px-tab-panel" data-px-panel="context" role="tabpanel">
                    <?php if (!empty($networkPreview)) { ?>
                    <?= $this->include('views/partials/exception_extra_context.html.php', [
                        'pinoox' => $pinoox,
                        'hints' => $hints,
                    ]); ?>
                    <?php if ($hasPortalContext) { ?>
                    <details class="px-disclosure">
                        <summary><span>Portal call</span></summary>
                        <div class="px-disclosure-body">
                            <?= $this->include('views/partials/portal_context_panel.html.php', ['pinoox' => $pinoox]); ?>
                        </div>
                    </details>
                    <?php } ?>
                    <?php } ?>

                    <?php if ($currentContent) { ?>
                    <details class="px-disclosure px-disclosure-response" open>
                        <summary><span>Response body</span><span class="px-disclosure-meta">output</span></summary>
                        <div class="px-disclosure-body">
                            <pre class="px-code-block px-output-buffer"><?= htmlspecialchars(strip_tags((string) $currentContent), ENT_QUOTES); ?></pre>
                        </div>
                    </details>
                    <?php } ?>

                    <?php if (!empty($pinoox['route']) && empty($networkPreview)) { ?>
                    <details class="px-disclosure" open>
                        <summary><span>Matched route</span></summary>
                        <div class="px-disclosure-body">
                            <dl class="px-kv-grid">
                                <dt>Route name</dt><dd><code><?= htmlspecialchars((string) ($pinoox['route']['name'] ?? '—'), ENT_QUOTES); ?></code></dd>
                                <dt>Path</dt><dd><code><?= htmlspecialchars((string) ($pinoox['route']['path'] ?? '—'), ENT_QUOTES); ?></code></dd>
                                <dt>Methods</dt><dd><?= htmlspecialchars(implode(', ', (array) ($pinoox['route']['methods'] ?? [])), ENT_QUOTES); ?></dd>
                                <dt>Action</dt><dd><code><?= htmlspecialchars((string) ($pinoox['route']['action_ref'] ?? '—'), ENT_QUOTES); ?></code></dd>
                                <?php if (!empty($pinoox['route']['action_source']['relative_file'])) { ?>
                                <dt>Defined in</dt><dd><code><?= htmlspecialchars((string) $pinoox['route']['action_source']['relative_file'], ENT_QUOTES); ?>:<?= (int) ($pinoox['route']['action_source']['line'] ?? 0); ?></code></dd>
                                <?php } elseif (!empty($pinoox['route']['route_source']['relative_file'])) { ?>
                                <dt>Defined in</dt><dd><code><?= htmlspecialchars((string) $pinoox['route']['route_source']['relative_file'], ENT_QUOTES); ?>:<?= (int) ($pinoox['route']['route_source']['line'] ?? 0); ?></code></dd>
                                <?php } ?>
                            </dl>
                        </div>
                    </details>
                    <?php } ?>

                    <details class="px-disclosure" open>
                        <summary><span>Request</span></summary>
                        <div class="px-disclosure-body">
                            <dl class="px-kv-grid">
                                <dt>URL</dt><dd class="break-long-words"><?= htmlspecialchars((string) ($pinoox['request']['url'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Path</dt><dd><?= htmlspecialchars((string) ($pinoox['request']['path'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Query</dt><dd><?= htmlspecialchars((string) (($pinoox['request']['query'] ?? '') !== '' ? $pinoox['request']['query'] : '—'), ENT_QUOTES); ?></dd>
                                <dt>IP</dt><dd><?= htmlspecialchars((string) ($pinoox['request']['ip'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>User agent</dt><dd class="break-long-words"><?= htmlspecialchars((string) ($pinoox['request']['user_agent'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Referer</dt><dd class="break-long-words"><?= htmlspecialchars((string) (($pinoox['request']['referer'] ?? '') !== '' ? $pinoox['request']['referer'] : '—'), ENT_QUOTES); ?></dd>
                            </dl>
                        </div>
                    </details>

                    <details class="px-disclosure">
                        <summary><span>Application</span></summary>
                        <div class="px-disclosure-body">
                            <dl class="px-kv-grid">
                                <dt>Package</dt><dd><?= htmlspecialchars((string) (($pinoox['package'] ?? '') !== '' ? $pinoox['package'] : '—'), ENT_QUOTES); ?></dd>
                                <?php if (!empty($pinoox['meeting']['active'])) {
                                    $meetingHost = trim((string) ($pinoox['meeting']['host'] ?? ''));
                                    if ($meetingHost !== '') { ?>
                                <dt>Meeting host</dt><dd><?= htmlspecialchars($meetingHost, ENT_QUOTES); ?></dd>
                                <?php } } ?>
                                <dt>Pinoox version</dt><dd><?= htmlspecialchars((string) ($pinoox['pinoox_version']['label'] ?? '—'), ENT_QUOTES); ?></dd>
                                <dt>App version</dt><dd><?= htmlspecialchars((string) ($pinoox['app_version']['label'] ?? '—'), ENT_QUOTES); ?></dd>
                                <dt>Environment</dt><dd><?= htmlspecialchars((string) ($pinoox['env']['app_env'] ?? 'local'), ENT_QUOTES); ?></dd>
                                <dt>APP debug</dt><dd><?= htmlspecialchars((string) ($pinoox['env']['app_debug'] ?? 'false'), ENT_QUOTES); ?></dd>
                                <dt>Pinoox Exception</dt><dd><?= htmlspecialchars((string) ($pinoox['env']['pinoox_exception'] ?? 'true'), ENT_QUOTES); ?></dd>
                                <dt>Project root</dt><dd class="break-long-words"><?= htmlspecialchars((string) ($pinoox['project_root'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Core path</dt><dd class="break-long-words"><?= htmlspecialchars((string) ($pinoox['core_path'] ?? ''), ENT_QUOTES); ?></dd>
                            </dl>
                        </div>
                    </details>

                    <?php if (!empty($pinoox['request']['query_params'])) { ?>
                    <details class="px-disclosure">
                        <summary><span>Query string</span><span class="px-disclosure-meta"><?= count($pinoox['request']['query_params']); ?></span></summary>
                        <div class="px-disclosure-body">
                            <pre class="px-code-block"><?= htmlspecialchars(json_encode($pinoox['request']['query_params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?></pre>
                        </div>
                    </details>
                    <?php } ?>

                    <?php if (!empty($pinoox['request']['post_params'])) { ?>
                    <details class="px-disclosure">
                        <summary><span>POST data</span><span class="px-disclosure-meta"><?= count($pinoox['request']['post_params']); ?></span></summary>
                        <div class="px-disclosure-body">
                            <pre class="px-code-block"><?= htmlspecialchars(json_encode($pinoox['request']['post_params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?></pre>
                        </div>
                    </details>
                    <?php } ?>

                    <?php if (($pinoox['request']['body'] ?? '') !== '') { ?>
                    <details class="px-disclosure">
                        <summary><span>Request body</span></summary>
                        <div class="px-disclosure-body">
                            <pre class="px-code-block"><?= htmlspecialchars((string) $pinoox['request']['body'], ENT_QUOTES); ?></pre>
                        </div>
                    </details>
                    <?php } ?>

                    <details class="px-disclosure">
                        <summary><span>Headers</span><span class="px-disclosure-meta"><?= count($pinoox['request']['headers'] ?? []); ?></span></summary>
                        <div class="px-disclosure-body">
                            <pre class="px-code-block"><?= htmlspecialchars(json_encode($pinoox['request']['headers'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?></pre>
                        </div>
                    </details>
                </div>

                <div class="px-tab-panel" data-px-panel="tools" role="tabpanel">
                    <p class="px-panel-hint px-preview-only">Select text in each box, then Ctrl+C to copy.</p>
                    <p class="px-panel-hint px-full-only">All fields are selectable — use Ctrl+A inside each box to copy.</p>

                    <details class="px-disclosure" open>
                        <summary><span>Error message</span></summary>
                        <div class="px-disclosure-body">
                            <textarea readonly class="px-copy-field" id="px-copy-message" rows="4"><?= htmlspecialchars($plainMessageLine, ENT_QUOTES); ?></textarea>
                        </div>
                    </details>

                    <details class="px-disclosure" open>
                        <summary><span>Stack trace</span></summary>
                        <div class="px-disclosure-body">
                            <textarea readonly class="px-copy-field" rows="12"><?= htmlspecialchars($plainStackTrace, ENT_QUOTES); ?></textarea>
                        </div>
                    </details>

                    <details class="px-disclosure" open>
                        <summary><span>Request URL</span></summary>
                        <div class="px-disclosure-body">
                            <textarea readonly class="px-copy-field" id="px-copy-url" rows="2"><?= htmlspecialchars((string) ($pinoox['request']['url'] ?? ''), ENT_QUOTES); ?></textarea>
                        </div>
                    </details>

                    <?php if (!empty($pinoox['request']['curl'])) { ?>
                    <details class="px-disclosure" open>
                        <summary><span>Replay with cURL</span></summary>
                        <div class="px-disclosure-body">
                            <textarea readonly class="px-copy-field" id="px-copy-curl" rows="6"><?= htmlspecialchars((string) $pinoox['request']['curl'], ENT_QUOTES); ?></textarea>
                        </div>
                    </details>
                    <?php } ?>

                    <div class="px-tools-grid px-full-only">
                        <label for="px-filter-pincore" class="px-tool-card px-control-label">
                            <strong>Show pincore frames</strong>
                            <span>Reveal framework / system stack frames</span>
                        </label>
                        <label for="px-filter-vendor" class="px-tool-card px-control-label">
                            <strong>Show vendor frames</strong>
                            <span>Reveal Composer / vendor stack frames</span>
                        </label>
                        <label for="px-expand-all" class="px-tool-card px-control-label">
                            <strong>Expand all frames</strong>
                            <span>Show every source snippet at once</span>
                        </label>
                        <a class="px-tool-card" href="<?= htmlspecialchars((string) ($pinoox['docs_url'] ?? 'https://www.pinoox.com/docs'), ENT_QUOTES); ?>" target="_blank" rel="noopener">
                            <strong>Open Pinoox Docs</strong>
                            <span>Troubleshooting guides</span>
                        </a>
                    </div>

                    <details class="px-disclosure">
                        <summary><span>Runtime & server</span></summary>
                        <div class="px-disclosure-body">
                            <dl class="px-kv-grid">
                                <dt>PHP SAPI</dt><dd><?= htmlspecialchars((string) ($pinoox['php_sapi'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Memory</dt><dd><?= htmlspecialchars((string) ($pinoox['memory'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Peak memory</dt><dd><?= htmlspecialchars((string) ($pinoox['memory_peak'] ?? ''), ENT_QUOTES); ?></dd>
                                <dt>Server</dt><dd><?= htmlspecialchars((string) ($pinoox['server']['software'] ?? '—'), ENT_QUOTES); ?></dd>
                                <dt>Protocol</dt><dd><?= htmlspecialchars((string) ($pinoox['server']['protocol'] ?? '—'), ENT_QUOTES); ?></dd>
                                <dt>Document root</dt><dd class="break-long-words"><?= htmlspecialchars((string) ($pinoox['server']['document_root'] ?? '—'), ENT_QUOTES); ?></dd>
                            </dl>
                        </div>
                    </details>

                    <?php if ($currentContent) { ?>
                    <details class="px-disclosure">
                        <summary><span>Output buffer</span></summary>
                        <div class="px-disclosure-body px-output-buffer"><?= $currentContent; ?></div>
                    </details>
                    <?php } ?>
                </div>
            </div>
        </section>
    </div>
</main>

