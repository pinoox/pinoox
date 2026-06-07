<?php if (!empty($source['snippet'])) { ?>
<pre class="px-source-snippet"><?php foreach ($source['snippet'] as $line) { ?><span class="px-source-line<?= !empty($line['highlight']) ? ' is-highlight' : ''; ?>"><span class="px-source-gutter"><?= (int) $line['number']; ?></span><span class="px-source-code"><?= htmlspecialchars((string) ($line['content'] ?? ''), ENT_QUOTES); ?></span></span>
<?php } ?></pre>
<?php } ?>

