<?php

add_action('admin_menu', function () {
    add_menu_page(
        'FRAP',                 // Título página
        'FRAP',                 // Nombre en menú
        'manage_options',       // Permisos
        FRAPWP_KEY,             // Slug
        'FRAPWP_PAGE_VIEW',
        'dashicons-rest-api',
        81                      // Debajo de Ajustes
    );
});

function FRAPWP_PAGE_VIEW()
{
    require_once FRAPWP_DIR . 'src/page/page.php';
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ('toplevel_page_' . FRAPWP_KEY !== $hook) {
        return;
    }
    $css = FRAPWP_DIR . 'src/css/admin.css';
    $js = FRAPWP_DIR . 'src/js/admin.js';
    wp_enqueue_style('frapwp-admin', FRAPWP_URL . 'src/css/admin.css', [], filemtime($css));
    wp_enqueue_script('frapwp-admin', FRAPWP_URL . 'src/js/admin.js', ['jquery'], filemtime($js), true);
});

/** Duraciones que se ofrecen al crear o renovar un token (horas => texto). */
function FRAPWP_durations()
{
    return [
        1 => '1 hora',
        6 => '6 horas',
        24 => '24 horas',
        72 => '3 días',
        168 => '7 días',
        720 => '30 días',
    ];
}

/** "en 23 h 10 min" / "vencido". */
function FRAPWP_remaining($ts)
{
    $s = (int) $ts - time();
    if ($s <= 0) {
        return 'vencido';
    }
    $d = floor($s / DAY_IN_SECONDS);
    $h = floor(($s % DAY_IN_SECONDS) / HOUR_IN_SECONDS);
    $m = floor(($s % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);
    return 'en ' . ($d ? $d . ' d ' : '') . ($d || $h ? $h . ' h ' : '') . $m . ' min';
}

/** Tabla de permisos (recurso × acción) para los formularios. Lo que el recurso no admite sale deshabilitado. */
function FRAPWP_permissions_table($perm)
{
    $resources = FRAPWP_RESOURCES::all();
?>
    <table class="widefat striped frapwp-perms">
        <thead>
            <tr>
                <th>Recurso</th>
                <?php foreach (FRAPWP_RESOURCES::ACTION_LABELS as $a => $label) : ?>
                    <th class="frapwp-c"><?= esc_html($label) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resources as $key => $r) : ?>
                <tr>
                    <td>
                        <?= esc_html($r['label']) ?> <code><?= esc_html($key) ?></code>
                        <?php if (!empty($r['personal'])) : ?><span class="frapwp-tag" title="Tiene datos personales: empieza apagado">datos personales</span><?php endif; ?>
                    </td>
                    <?php foreach (FRAPWP_RESOURCES::ACTIONS as $a) : ?>
                        <td class="frapwp-c">
                            <?php if (in_array($a, $r['actions'], true)) : ?>
                                <input type="checkbox" class="frapwp-perm" data-action="<?= esc_attr($a) ?>" name="perm[<?= esc_attr($key) ?>][<?= esc_attr($a) ?>]" value="1" <?php checked(!empty($perm[$key][$a])); ?>>
                            <?php else : ?>
                                <span class="frapwp-na" title="No disponible para este recurso">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="description">Para crear, editar o eliminar hace falta poder ver. Los cambios se hacen con el usuario que creó el token y quedan en «Actividad».</p>
<?php
}

/** Select de duración. */
function FRAPWP_hours_select($name, $selected = FRAPWP_DEFAULT_HOURS)
{
?>
    <select name="<?= esc_attr($name) ?>">
        <?php foreach (FRAPWP_durations() as $h => $label) : ?>
            <option value="<?= (int) $h ?>" <?php selected((int) $selected, $h); ?>><?= esc_html($label) ?></option>
        <?php endforeach; ?>
    </select>
<?php
}
