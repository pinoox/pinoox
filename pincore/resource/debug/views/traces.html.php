<?php

use Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier;

$portalError = $portalError ?? false;
$projectRoot = $projectRoot ?? null;
$originIndex = TraceFrameClassifier::findOriginIndex($exception['trace'], $projectRoot);

$separator = strrpos($exception['class'], '\\');
$separator = false === $separator ? 0 : $separator + 1;
$namespace = substr($exception['class'], 0, $separator);
$class = substr($exception['class'], $separator);
?>
<details class="trace trace-as-html px-trace-box" id="trace-box-<?= $index; ?>"<?= !empty($expand) ? ' open' : ''; ?>>
    <summary class="trace-head">
        <?php if ('' === $class) { ?>
            <span class="trace-class">Exception</span>
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
    </summary>

    <?php if (\count($exception['data'] ?? [])) { ?>
        <details class="exception-properties-wrapper">
            <summary>Show exception properties</summary>
            <div class="exception-properties">
                <?= $this->dumpValue($exception['data']) ?>
            </div>
        </details>
    <?php } ?>

    <div class="px-trace-frames" id="trace-html-<?= $index; ?>">
        <?php
        $isFirstUserCode = true;
        foreach ($exception['trace'] as $i => $trace) {
            $lineClass = TraceFrameClassifier::lineClasses($trace, $originIndex, $i, $projectRoot);
            $style = TraceFrameClassifier::displayStyle($trace, $originIndex, $i, $isFirstUserCode, $projectRoot);
            $isOrigin = $originIndex === $i;

            echo $this->include('views/trace.html.php', [
                'prefix' => $index,
                'i' => $i,
                'trace' => $trace,
                'style' => $style,
                'isOrigin' => $isOrigin,
                'lineClass' => $lineClass,
            ]);
        }
        ?>
    </div>
</details>
