<div class="trace trace-as-text px-text-trace-block">
    <div class="trace-head px-text-trace-head">
        <?php if ($numExceptions > 1) { ?>
            <span class="text-muted">[<?= $numExceptions - $index + 1; ?>/<?= $numExceptions; ?>]</span>
        <?php } ?>
        <strong><?= ($parts = explode('\\', $exception['class'])) ? end($parts) : ''; ?></strong>
    </div>
    <?php if ($exception['trace']) { ?>
    <pre class="stacktrace"><?php
        echo $this->escape($exception['class']).":\n";
        if ($exception['message']) {
            echo $this->escape($exception['message'])."\n";
        }

        foreach ($exception['trace'] as $trace) {
            echo "\n  ";
            if ($trace['function']) {
                echo $this->escape('at '.$trace['class'].$trace['type'].$trace['function']).'('.(isset($trace['args']) ? $this->formatArgsAsText($trace['args']) : '').')';
            }
            if ($trace['file'] && $trace['line']) {
                echo($trace['function'] ? "\n     (" : 'at ').strtr(strip_tags($this->formatFile($trace['file'], $trace['line'])), [' at line '.$trace['line'] => '']).':'.$trace['line'].($trace['function'] ? ')' : '');
            }
        }
    ?></pre>
    <?php } ?>
</div>
