<div class="px-extra-context">
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
</div>
