<?php

use franciscoblancojn\wordpress_utils\FWUSystemLog;

$log = FWUSystemLog::get(FRAPWP_KEY);
$rows = [];
foreach (is_array($log) ? $log : [] as $type => $items) {
    foreach ((array) $items as $item) {
        if (is_array($item)) {
            $item['type'] = strtolower((string) $type);
            $rows[] = $item;
        }
    }
}
usort($rows, function ($a, $b) {
    return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
});
$rows = array_slice($rows, 0, 300);
$labels = [
    'read' => 'Lectura',
    'write' => 'Cambio',
    'token_create' => 'Token creado',
    'token_save' => 'Permisos guardados',
    'token_renew' => 'Token renovado',
    'token_revoke' => 'Token revocado',
    'token_delete' => 'Token eliminado',
];

?>
<p>Lo que hicieron las integraciones con este sitio y los cambios en los tokens (últimos <?= (int) FRAPWP_LOG_COUNT ?> de cada tipo).</p>
<?php if (!$rows) : ?>
    <p><em>Sin actividad todavía.</em></p>
<?php else : ?>
    <table class="widefat striped frapwp-log">
        <thead>
            <tr><th>Fecha</th><th>Tipo</th><th>Token</th><th>Petición</th><th>Resultado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r) : ?>
                <?php $status = (int) ($r['status'] ?? 0); ?>
                <tr class="<?= $status >= 400 ? 'frapwp-err' : '' ?>">
                    <td><?= esc_html($r['date'] ?? '') ?></td>
                    <td><?= esc_html($labels[$r['type']] ?? $r['type']) ?></td>
                    <td><?= esc_html($r['token'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($r['route'])) : ?>
                            <code><?= esc_html(($r['method'] ?? '') . ' ' . $r['route']) ?></code>
                            <?php if (!empty($r['query'])) : ?><code class="frapwp-muted"><?= esc_html(http_build_query((array) $r['query'])) ?></code><?php endif; ?>
                        <?php elseif (!empty($r['expires'])) : ?>
                            vence <?= esc_html($r['expires']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $status ? esc_html($status) : '' ?> <?= !empty($r['error']) ? esc_html($r['error']) : '' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
