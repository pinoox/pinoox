<div class="trace-line-header break-long-words <?= $trace['file'] ? 'sf-toggle' : ''; ?><?= !empty($isOrigin) ? ' trace-line-origin-header' : ''; ?>" data-toggle-selector="#trace-html-<?= $prefix; ?>-<?= $i; ?>" data-toggle-initial="<?= 'expanded' === $style ? 'display' : ''; ?>">
    <?php if (!empty($isOrigin)) { ?>
    <div class="px-trace-origin-row">
        <span class="px-trace-origin-badge">Your code</span>
    </div>
    <?php } ?>

    <div class="px-trace-line-body">
        <?php if ($trace['file']) { ?>
        <div class="px-trace-toggle-icons" aria-hidden="true">
            <span class="icon icon-close"><?= $this->include('assets/images/icon-minus-square.svg'); ?></span>
            <span class="icon icon-open"><?= $this->include('assets/images/icon-plus-square.svg'); ?></span>
        </div>
        <?php } ?>

        <div class="px-trace-line-content">
        <?php if ('compact' !== $style && $trace['function']) { ?>
            <span class="trace-class"><?= $this->abbrClass($trace['class']); ?></span><?php if ($trace['type']) { ?><span class="trace-type"><?= $trace['type']; ?></span><?php } ?><span class="trace-method"><?= $trace['function']; ?></span><?php if (isset($trace['args'])) { ?><span class="trace-arguments">(<?= $this->formatArgs($trace['args']); ?>)</span><?php } ?>
        <?php } ?>

        <?php if ($trace['file']) { ?>
            <?php
            $lineNumber = $trace['line'] ?: 1;
            $fileLink = $this->fileLinkFormat->format($trace['file'], $lineNumber);
            $filePath = strtr(strip_tags($this->formatFile($trace['file'], $lineNumber)), [' at line '.$lineNumber => '']);
            $filePathParts = explode(\DIRECTORY_SEPARATOR, $filePath);
            ?>
            <span class="block trace-file-path">
                in
                <a href="<?= $fileLink; ?>">
                    <?= implode(\DIRECTORY_SEPARATOR, array_slice($filePathParts, 0, -1)).\DIRECTORY_SEPARATOR; ?><strong><?= end($filePathParts); ?></strong>
                </a>
                <?php if ('compact' === $style && $trace['function']) { ?>
                    <span class="trace-type"><?= $trace['type']; ?></span>
                    <span class="trace-method"><?= $trace['function']; ?></span>
                <?php } ?>
                (line <?= $lineNumber; ?>)
                <span class="icon icon-copy hidden" data-clipboard-text="<?php echo implode(\DIRECTORY_SEPARATOR, $filePathParts).':'.$lineNumber; ?>">
                    <?php echo $this->include('assets/images/icon-copy.svg'); ?>
                </span>
            </span>
        <?php } ?>
        </div>
    </div>
</div>
<?php if ($trace['file']) { ?>
    <div id="trace-html-<?= $prefix.'-'.$i; ?>" class="trace-code sf-toggle-content">
        <?= strtr($this->fileExcerpt($trace['file'], $trace['line'], 5), [
            '#0000BB' => 'var(--px-code-fn)',
            '#DD0000' => 'var(--highlight-string)',
            '#007700' => 'var(--highlight-keyword)',
            '#FF8000' => 'var(--highlight-comment)',
            '#000000' => 'var(--highlight-default)',
        ]); ?>
    </div>
<?php } ?>
