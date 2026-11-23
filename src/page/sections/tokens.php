<?php

use franciscoblancojn\wordpress_utils\FWUCollapse;

$tokens = (new FRAPWP_USE_DATA_TOKENS())->get();
uasort($tokens, function ($a, $b) {
    return (int) $b['created'] - (int) $a['created'];
});
$new = get_transient('frapwp_new_token_' . get_current_user_id());
if ($new) {
    delete_transient('frapwp_new_token_' . get_current_user_id());
}
$post_url = admin_url('admin-post.php');
$site = home_url();

?>
<?php if ($new) : ?>
    <div class="frapwp-new">
        <h3>Token nuevo: cópialo ahora</h3>
        <p>No se vuelve a mostrar. En FRAP: panel izquierdo → <strong>Integraciones → WordPress → ＋</strong>, con la URL del sitio y este token.</p>
        <p>
            <input type="text" readonly class="large-text code frapwp-copy-src" value="<?= esc_attr($new['token']) ?>">
        </p>
        <p>
            <button type="button" class="button button-primary frapwp-copy">Copiar token</button>
            <span class="frapwp-muted">URL del sitio: <code><?= esc_html($site) ?></code></span>
        </p>
    </div>
<?php endif; ?>

<?php
ob_start();
?>
<form method="post" action="<?= esc_url($post_url) ?>" class="frapwp-form">
    <input type="hidden" name="action" value="frapwp_create">
    <?php wp_nonce_field(FRAPWP_API_TOKENS::NONCE); ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="frapwp-name">Nombre</label></th>
            <td><input type="text" id="frapwp-name" name="name" class="regular-text" placeholder="FRAP de Francisco" required></td>
        </tr>
        <tr>
            <th scope="row">Duración</th>
            <td>
                <?php FRAPWP_hours_select('hours'); ?>
                <p class="description">Al vencer deja de funcionar. Se puede renovar sin cambiar el token.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Permisos</th>
            <td><?php FRAPWP_permissions_table(FRAPWP_RESOURCES::default_permissions()); ?></td>
        </tr>
    </table>
    <p><button type="submit" class="button button-primary">Crear token</button></p>
</form>
<?php
FWUCollapse::render('＋ Nuevo token', ob_get_clean(), !$tokens);
?>

<?php if (!$tokens) : ?>
    <p><em>Todavía no hay tokens.</em></p>
<?php endif; ?>

<?php
foreach ($tokens as $t) {
    $active = FRAPWP_USE_DATA_TOKENS::is_active($t);
    $state = !empty($t['revoked']) ? 'revocado' : ($active ? 'activo' : 'vencido');
    $user = get_user_by('id', (int) $t['user']);
    $allowed = [];
    foreach (FRAPWP_RESOURCES::summary($t) as $key => $r) {
        if ($r['allowed']) {
            $allowed[] = $r['label'] . ' (' . implode(', ', array_map(function ($a) {
                return FRAPWP_RESOURCES::ACTION_LABELS[$a];
            }, $r['allowed'])) . ')';
        }
    }
    $title = sprintf(
        '<span class="frapwp-badge frapwp-%s">%s</span> %s <code>…%s</code> · %s',
        esc_attr($state),
        esc_html($state),
        esc_html($t['name']),
        esc_html($t['hint']),
        $active ? 'vence ' . esc_html(FRAPWP_remaining($t['expires'])) : 'venció el ' . esc_html(wp_date('Y-m-d H:i', (int) $t['expires']))
    );
    ob_start();
?>
    <p class="frapwp-meta">
        Creado <?= esc_html(wp_date('Y-m-d H:i', (int) $t['created'])) ?> por <?= esc_html($user ? $user->display_name : 'usuario #' . (int) $t['user']) ?>
        · vence <?= esc_html(wp_date('Y-m-d H:i', (int) $t['expires'])) ?>
        · último uso: <?= $t['last_used'] ? esc_html(wp_date('Y-m-d H:i', (int) $t['last_used'])) : 'nunca' ?>
    </p>
    <p class="frapwp-meta">Puede: <?= $allowed ? esc_html(implode(' · ', $allowed)) : '<em>nada</em>' ?></p>
    <div class="frapwp-actions">
        <form method="post" action="<?= esc_url($post_url) ?>">
            <input type="hidden" name="action" value="frapwp_renew">
            <input type="hidden" name="id" value="<?= esc_attr($t['id']) ?>">
            <?php wp_nonce_field(FRAPWP_API_TOKENS::NONCE); ?>
            <?php FRAPWP_hours_select('hours', $t['hours'] ?? FRAPWP_DEFAULT_HOURS); ?>
            <button type="submit" class="button"><?= $active ? 'Renovar' : 'Reactivar' ?></button>
        </form>
        <?php if ($active) : ?>
            <form method="post" action="<?= esc_url($post_url) ?>">
                <input type="hidden" name="action" value="frapwp_revoke">
                <input type="hidden" name="id" value="<?= esc_attr($t['id']) ?>">
                <?php wp_nonce_field(FRAPWP_API_TOKENS::NONCE); ?>
                <button type="submit" class="button">Revocar</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= esc_url($post_url) ?>" class="frapwp-confirm" data-confirm="¿Eliminar el token «<?= esc_attr($t['name']) ?>»? FRAP deja de poder conectarse con él.">
            <input type="hidden" name="action" value="frapwp_delete">
            <input type="hidden" name="id" value="<?= esc_attr($t['id']) ?>">
            <?php wp_nonce_field(FRAPWP_API_TOKENS::NONCE); ?>
            <button type="submit" class="button button-link-delete">Eliminar</button>
        </form>
    </div>
    <form method="post" action="<?= esc_url($post_url) ?>" class="frapwp-form">
        <input type="hidden" name="action" value="frapwp_save">
        <input type="hidden" name="id" value="<?= esc_attr($t['id']) ?>">
        <?php wp_nonce_field(FRAPWP_API_TOKENS::NONCE); ?>
        <p><label>Nombre <input type="text" name="name" class="regular-text" value="<?= esc_attr($t['name']) ?>"></label></p>
        <?php FRAPWP_permissions_table($t['permissions'] ?? []); ?>
        <p><button type="submit" class="button button-primary">Guardar permisos</button></p>
    </form>
<?php
    FWUCollapse::render($title, ob_get_clean(), false);
}
