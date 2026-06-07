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
$thrownInFramework = TraceFrameClassifier::isFrameworkSurfacePath($primaryFile, $projectRootArg);
$thrownInPortal = $portalError && str_contains($primaryFile, '/Component/Source/Portal.php');
$thrownInProjectEntry = TraceFrameClassifier::isProjectEntryPath($primaryFile, $projectRootArg);
$thrownInSystem = TraceFrameClassifier::isSystemPath($primaryFile, $projectRootArg);
?>

<main class="px-main">
    <div class="px-container">
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

            <?php if (!empty($pinoox['portal']['call'])) { ?>
            <p class="px-hero-origin">
                Called from
                <?php if (!empty($pinoox['portal']['file'])) { ?>
                <code><?= htmlspecialchars((string) $pinoox['portal']['file'], ENT_QUOTES); ?><?= !empty($pinoox['portal']['line']) ? ':' . (int) $pinoox['portal']['line'] : ''; ?></code>
                <?php } ?>
                as
                <code class="px-hero-origin-call"><?= htmlspecialchars((string) $pinoox['portal']['call'], ENT_QUOTES); ?></code>
                <?php if (!empty($pinoox['portal']['suggestion'])) { ?>
                <span class="px-hero-origin-hint">→ did you mean <code><?= htmlspecialchars((string) $pinoox['portal']['portal'], ENT_QUOTES); ?>::<?= htmlspecialchars((string) $pinoox['portal']['suggestion'], ENT_QUOTES); ?>()</code>?</span>
                <?php } ?>
            </p>
            <?php } ?>

            <div class="px-quick-stats">
                <div class="px-stat"><span>Pinoox</span><strong><?= htmlspecialchars((string) ($pinoox['pinoox_version']['label'] ?? '—'), ENT_QUOTES); ?></strong></div>
                <div class="px-stat"><span>App</span><strong><?= htmlspecialchars((string) ($pinoox['app_version']['label'] ?? '—'), ENT_QUOTES); ?></strong></div>
                <div class="px-stat"><span>Method</span><strong><?= htmlspecialchars((string) ($pinoox['request']['method'] ?? '—'), ENT_QUOTES); ?></strong></div>
                <div class="px-stat"><span>Package</span><strong><?= htmlspecialchars((string) (($pinoox['package'] ?? '') !== '' ? $pinoox['package'] : '—'), ENT_QUOTES); ?></strong></div>
                <div class="px-stat"><span>PHP</span><strong><?= htmlspecialchars((string) ($pinoox['php_version'] ?? ''), ENT_QUOTES); ?></strong></div>
                <div class="px-stat"><span>Memory</span><strong><?= htmlspecialchars((string) ($pinoox['memory'] ?? ''), ENT_QUOTES); ?></strong></div>
            </div>
        </section>

        <?php if (!empty($pinoox['portal']['source']['snippet'])) { ?>
        <section class="px-portal-context">
            <div class="px-route-context-head">
                <h2>Portal call</h2>
                <p>Your code invoked a Portal facade; the error surfaced inside <code>Portal.php</code> when delegating to the service.</p>
            </div>

            <dl class="px-route-grid">
                <dt>Call</dt>
                <dd><code><?= htmlspecialchars((string) ($pinoox['portal']['call'] ?? ''), ENT_QUOTES); ?></code></dd>
                <?php if (!empty($pinoox['portal']['target'])) { ?>
                <dt>Service</dt>
                <dd><code><?= htmlspecialchars((string) $pinoox['portal']['target'], ENT_QUOTES); ?></code></dd>
                <?php } ?>
                <?php if (!empty($pinoox['portal']['suggestion'])) { ?>
                <dt>Suggestion</dt>
                <dd><code><?= htmlspecialchars((string) (($pinoox['portal']['portal'] ?? '') . '::' . $pinoox['portal']['suggestion'] . '()'), ENT_QUOTES); ?></code></dd>
                <?php } ?>
            </dl>

            <details class="px-disclosure px-disclosure-route px-disclosure-route-primary" open>
                <summary>
                    <span>Your call site</span>
                    <code class="px-disclosure-file"><?= htmlspecialchars((string) ($pinoox['portal']['source']['relative_file'] ?? ''), ENT_QUOTES); ?>:<?= (int) ($pinoox['portal']['source']['line'] ?? 0); ?></code>
                </summary>
                <div class="px-disclosure-body">
                    <?= $this->include('views/partials/source_snippet.html.php', ['source' => $pinoox['portal']['source']]); ?>
                </div>
            </details>
        </section>
        <?php } ?>

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

        <section class="px-workspace">
            <nav class="px-tabs" role="tablist">
                <button type="button" class="px-tab is-active" data-px-tab="exception" role="tab" aria-selected="true">Exception</button>
                <button type="button" class="px-tab" data-px-tab="stack" role="tab">Stack trace</button>
                <?php if ($logger) { ?>
                <button type="button" class="px-tab" data-px-tab="logs" role="tab">
                    Logs<?php if ($logger->countErrors()) { ?><span class="px-tab-badge"><?= $logger->countErrors(); ?></span><?php } ?>
                </button>
                <?php } ?>
                <button type="button" class="px-tab" data-px-tab="context" role="tab">Context</button>
                <button type="button" class="px-tab" data-px-tab="tools" role="tab">Tools</button>
            </nav>

            <div class="px-tab-panels">
                <div class="px-tab-panel is-active" data-px-panel="exception" role="tabpanel">
                    <div class="px-panel-toolbar">
                        <button type="button" class="px-btn px-btn-small px-btn-primary" data-action="jump-origin">Jump to your code</button>
                        <button type="button" class="px-btn px-btn-small px-btn-ghost" data-action="filter-pincore">Show pincore frames</button>
                        <button type="button" class="px-btn px-btn-small px-btn-ghost" data-action="filter-vendor">Show vendor frames</button>
                        <button type="button" class="px-btn px-btn-small" data-toggle-traces="expand">Expand all</button>
                        <button type="button" class="px-btn px-btn-small px-btn-ghost" data-toggle-traces="collapse">Collapse all</button>
                    </div>
                    <div class="px-trace-wrap">
                        <?php
                        foreach ($exceptionAsArray as $i => $e) {
                            echo $this->include('views/traces.html.php', [
                                'exception' => $e,
                                'index' => $i + 1,
                                'expand' => in_array($i, $exceptionWithUserCode, true) || ([] === $exceptionWithUserCode && 0 === $i),
                                'portalError' => $portalError,
                                'projectRoot' => $projectRootArg,
                            ]);
                        }
                        ?>
                    </div>
                </div>

                <div class="px-tab-panel" data-px-panel="stack" role="tabpanel" hidden>
                    <div class="px-panel-toolbar">
                        <button type="button" class="px-btn px-btn-small" data-copy="trace">Copy stack trace</button>
                    </div>
                    <div class="px-stack-wrap">
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

                <?php if ($logger) { ?>
                <div class="px-tab-panel" data-px-panel="logs" role="tabpanel" hidden>
                    <?php if ($logger->getLogs()) { ?>
                        <?= $this->include('views/logs.html.php', ['logs' => $logger->getLogs()]); ?>
                    <?php } else { ?>
                        <div class="px-empty">No log messages recorded for this request.</div>
                    <?php } ?>
                </div>
                <?php } ?>

                <div class="px-tab-panel" data-px-panel="context" role="tabpanel" hidden>
                    <?php if (!empty($pinoox['route'])) { ?>
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
                                <dt>Pinoox version</dt><dd><?= htmlspecialchars((string) ($pinoox['pinoox_version']['label'] ?? '—'), ENT_QUOTES); ?></dd>
                                <dt>App version</dt><dd><?= htmlspecialchars((string) ($pinoox['app_version']['label'] ?? '—'), ENT_QUOTES); ?></dd>
                                <dt>Environment</dt><dd><?= htmlspecialchars((string) ($pinoox['env']['app_env'] ?? 'local'), ENT_QUOTES); ?></dd>
                                <dt>Debug</dt><dd><?= htmlspecialchars((string) ($pinoox['env']['app_debug'] ?? 'true'), ENT_QUOTES); ?></dd>
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

                <div class="px-tab-panel" data-px-panel="tools" role="tabpanel" hidden>
                    <div class="px-tools-grid">
                        <button type="button" class="px-tool-card" data-copy="url">
                            <strong>Copy request URL</strong>
                            <span>Paste into browser or API client</span>
                        </button>
                        <button type="button" class="px-tool-card" data-copy="message">
                            <strong>Copy error message</strong>
                            <span>Share in issue tracker or chat</span>
                        </button>
                        <button type="button" class="px-tool-card" data-copy="trace">
                            <strong>Copy full stack trace</strong>
                            <span>Plain text for debugging</span>
                        </button>
                        <button type="button" class="px-tool-card" data-copy="curl">
                            <strong>Copy as cURL</strong>
                            <span>Replay this request in terminal</span>
                        </button>
                        <button type="button" class="px-tool-card" data-action="filter-vendor">
                            <strong>Toggle vendor frames</strong>
                            <span>Hide or show /vendor/ stack frames</span>
                        </button>
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
