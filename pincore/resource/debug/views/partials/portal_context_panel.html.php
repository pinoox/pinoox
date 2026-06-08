<?php
$hasPortalCall = !empty($pinoox['portal']['call']);
$hasPortalSource = !empty($pinoox['portal']['source']['snippet']);

if (!$hasPortalCall && !$hasPortalSource) {
    return;
}
?>
<section class="px-portal-context">
    <div class="px-route-context-head">
        <h2>Portal call</h2>
        <p>Your code invoked a Portal facade; the error may have surfaced inside <code>Portal.php</code> when delegating to the service.</p>
    </div>

    <?php if ($hasPortalCall) { ?>
    <p class="px-hero-origin px-portal-tab-origin">
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

    <dl class="px-route-grid">
        <?php if ($hasPortalCall) { ?>
        <dt>Call</dt>
        <dd><code><?= htmlspecialchars((string) ($pinoox['portal']['call'] ?? ''), ENT_QUOTES); ?></code></dd>
        <?php } ?>
        <?php if (!empty($pinoox['portal']['target'])) { ?>
        <dt>Service</dt>
        <dd><code><?= htmlspecialchars((string) $pinoox['portal']['target'], ENT_QUOTES); ?></code></dd>
        <?php } ?>
        <?php if (!empty($pinoox['portal']['suggestion'])) { ?>
        <dt>Suggestion</dt>
        <dd><code><?= htmlspecialchars((string) (($pinoox['portal']['portal'] ?? '') . '::' . $pinoox['portal']['suggestion'] . '()'), ENT_QUOTES); ?></code></dd>
        <?php } ?>
    </dl>

    <?php if ($hasPortalSource) { ?>
    <details class="px-disclosure px-disclosure-route px-disclosure-route-primary" open>
        <summary>
            <span>Your call site</span>
            <code class="px-disclosure-file"><?= htmlspecialchars((string) ($pinoox['portal']['source']['relative_file'] ?? ''), ENT_QUOTES); ?>:<?= (int) ($pinoox['portal']['source']['line'] ?? 0); ?></code>
        </summary>
        <div class="px-disclosure-body">
            <?= $this->include('views/partials/source_snippet.html.php', ['source' => $pinoox['portal']['source']]); ?>
        </div>
    </details>
    <?php } ?>
</section>
