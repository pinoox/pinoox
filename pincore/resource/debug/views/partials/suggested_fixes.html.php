<?php if (!empty($hints)) { ?>
<details class="px-disclosure px-disclosure-hints">
    <summary>
        <span>Suggested fixes</span>
        <span class="px-disclosure-meta"><?= count($hints); ?> suggestion<?= count($hints) > 1 ? 's' : ''; ?></span>
    </summary>
    <div class="px-disclosure-body">
        <div class="px-hints-list">
            <?php foreach ($hints as $index => $hint) {
                $priority = (string) ($hint['priority'] ?? 'low');
                $isPrimary = $index === 0 && $priority === 'high';
            ?>
            <article class="px-hint-card px-hint-<?= htmlspecialchars($priority, ENT_QUOTES); ?><?= $isPrimary ? ' px-hint-primary' : ''; ?>">
                <div class="px-hint-card-head">
                    <span class="px-hint-priority px-hint-priority-<?= htmlspecialchars($priority, ENT_QUOTES); ?>">
                        <?= $priority === 'high' ? 'Likely fix' : ($priority === 'medium' ? 'Check this' : 'Info'); ?>
                    </span>
                    <?php if (!empty($hint['location'])) { ?>
                    <code class="px-hint-location"><?= htmlspecialchars((string) $hint['location'], ENT_QUOTES); ?></code>
                    <?php } ?>
                </div>

                <h3><?= htmlspecialchars((string) ($hint['title'] ?? 'Hint'), ENT_QUOTES); ?></h3>

                <?php if (!empty($hint['summary'])) { ?>
                <p class="px-hint-summary"><?= htmlspecialchars((string) $hint['summary'], ENT_QUOTES); ?></p>
                <?php } ?>

                <?php if (!empty($hint['steps']) && is_array($hint['steps'])) { ?>
                <ol class="px-hint-steps">
                    <?php foreach ($hint['steps'] as $step) { ?>
                    <li><?= htmlspecialchars((string) $step, ENT_QUOTES); ?></li>
                    <?php } ?>
                </ol>
                <?php } ?>

                <?php if (!empty($hint['fix'])) { ?>
                <div class="px-hint-fix">
                    <span>Suggested change</span>
                    <pre class="px-source-snippet px-hint-fix-code"><code><?= htmlspecialchars((string) $hint['fix'], ENT_QUOTES); ?></code></pre>
                </div>
                <?php } ?>

                <div class="px-hint-actions">
                    <?php if ($isPrimary && !empty($pinoox['portal']['via_portal'])) { ?>
                    <button type="button" class="px-btn px-btn-small" data-action="jump-origin">Jump to your code</button>
                    <?php } ?>
                    <?php if (!empty($hint['docs'])) { ?>
                    <a class="px-btn px-btn-small px-btn-ghost" href="<?= htmlspecialchars((string) $hint['docs'], ENT_QUOTES); ?>" target="_blank" rel="noopener">Pinoox Docs</a>
                    <?php } ?>
                </div>
            </article>
            <?php } ?>
        </div>
    </div>
</details>
<?php } ?>

