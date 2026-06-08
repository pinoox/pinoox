<?php
$meetingHost = trim((string) ($pinoox['meeting']['host'] ?? ''));
$metaItems = [
    ['label' => 'Pinoox', 'value' => (string) ($pinoox['pinoox_version']['label'] ?? '—')],
    ['label' => 'App', 'value' => (string) ($pinoox['app_version']['label'] ?? '—')],
    ['label' => 'Method', 'value' => (string) ($pinoox['request']['method'] ?? '—')],
    ['label' => 'Package', 'value' => (string) (($pinoox['package'] ?? '') !== '' ? $pinoox['package'] : '—'), 'mono' => true],
];
if (!empty($pinoox['meeting']['active']) && $meetingHost !== '') {
    $metaItems[] = ['label' => 'Host', 'value' => $meetingHost, 'mono' => true];
}
$metaItems = array_merge($metaItems, [
    ['label' => 'PHP', 'value' => (string) ($pinoox['php_version'] ?? '—')],
    ['label' => 'Memory', 'value' => (string) ($pinoox['memory'] ?? '—')],
]);
?>
<dl class="px-meta-bar">
    <?php foreach ($metaItems as $item) { ?>
        <div class="px-meta-item">
            <dt><?= htmlspecialchars($item['label'], ENT_QUOTES); ?></dt>
            <dd<?= !empty($item['mono']) ? ' class="px-meta-mono"' : ''; ?>><?= htmlspecialchars($item['value'], ENT_QUOTES); ?></dd>
        </div>
    <?php } ?>
</dl>
