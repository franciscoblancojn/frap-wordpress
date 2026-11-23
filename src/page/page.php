<?php

use franciscoblancojn\wordpress_utils\FWUPage;

echo FWUPage::css();

$TAGS = [
    [
        'key' => 'tokens',
        'title' => 'Tokens',
    ],
    [
        'key' => 'actividad',
        'title' => 'Actividad',
    ],
    [
        'key' => 'ayuda',
        'title' => 'Cómo conectar',
    ],
];
$defaultTag = $TAGS[0]['key'];

$respond = get_transient('frapwp_respond_' . get_current_user_id());
if ($respond) {
    delete_transient('frapwp_respond_' . get_current_user_id());
}

?>
<div id="page-<?= esc_attr(FRAPWP_KEY) ?>" class="wrap frapwp-page">
    <h1>FRAP <span class="frapwp-version">v<?= esc_html(FRAPWP_get_version()) ?></span></h1>
    <p class="frapwp-lead">Tokens temporales para que FRAP (y sus IA) trabajen con este sitio. Cada token vence solo y tiene sus propios permisos: por defecto, solo lectura.</p>
    <?php if ($respond) : ?>
        <div class="notice notice-<?= 'error' === $respond['status'] ? 'error' : 'success' ?> is-dismissible">
            <p><?= esc_html($respond['message']) ?></p>
        </div>
    <?php endif; ?>
    <?php FWUPage::tabs($TAGS, $defaultTag); ?>
    <?php
    foreach ($TAGS as $value) {
    ?>
        <div class="tab-content <?= $value['key'] == $defaultTag ? "nav-tab-active" : "" ?>" id="<?= esc_attr($value['key']) ?>">
            <?php
            require_once FRAPWP_DIR . 'src/page/sections/' . $value['key'] . ".php";
            ?>
        </div>
    <?php
    }
    ?>
</div>
<?php

echo FWUPage::js();
