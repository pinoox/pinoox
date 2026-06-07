<?php

use Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier;

$portalError = $portalError ?? false;
$projectRoot = $projectRoot ?? null;
$originIndex = TraceFrameClassifier::findOriginIndex($exception['trace'], $projectRoot);
?>
<div class="trace trace-as-html" id="trace-box-<?= $index; ?>">
    <div class="trace-details">
        <div class="trace-head">
            <div class="sf-toggle" data-toggle-selector="#trace-html-<?= $index; ?>" data-toggle-initial="<?= $expand ? 'display' : ''; ?>">
                <span class="icon icon-close"><?= $this->include('assets/images/icon-minus-square-o.svg'); ?></span>
                <span class="icon icon-open"><?= $this->include('assets/images/icon-plus-square-o.svg'); ?></span>
                <?php
                $separator = strrpos($exception['class'], '\\');
                $separator = false === $separator ? 0 : $separator + 1;

                $namespace = substr($exception['class'], 0, $separator);
                $class = substr($exception['class'], $separator);
                ?>
                <?php if ('' === $class) { ?>
                    <br>
                <?php } else { ?>
                    <h3 class="trace-class">
                        <?php if ('' !== $namespace) { ?>
                            <span class="trace-namespace"><?= $namespace; ?></span>
                        <?php } ?>
                        <?= $class; ?>
                    </h3>
                <?php } ?>
                <?php if ($exception['message'] && $index > 1) { ?>
                    <p class="break-long-words trace-message"><?= $this->escape($exception['message']); ?></p>
                <?php } ?>
            </div>
            <?php if (\count($exception['data'] ?? [])) { ?>
                <details class="exception-properties-wrapper">
                    <summary>Show exception properties</summary>
                    <div class="exception-properties">
                        <?= $this->dumpValue($exception['data']) ?>
                    </div>
                </details>
            <?php } ?>
        </div>

        <div id="trace-html-<?= $index; ?>" class="sf-toggle-content">
        <?php
        $isFirstUserCode = true;
        foreach ($exception['trace'] as $i => $trace) {
            $lineClass = TraceFrameClassifier::lineClasses($trace, $originIndex, $i, $projectRoot);
            $style = TraceFrameClassifier::displayStyle($trace, $originIndex, $i, $isFirstUserCode, $projectRoot);
            $isOrigin = $originIndex === $i;
            ?>
            <div class="trace-line <?= $lineClass; ?>"<?= $isOrigin ? ' id="px-trace-origin"' : ''; ?>>
                <?= $this->include('views/trace.html.php', [
                    'prefix' => $index,
                    'i' => $i,
                    'trace' => $trace,
                    'style' => $style,
                    'isOrigin' => $isOrigin,
                ]); ?>
            </div>
            <?php
        } ?>
        </div>
    </div>
</div>
