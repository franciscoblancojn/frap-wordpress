<?php
$base = rest_url(FRAPWP_NAMESPACE);
$fallback = add_query_arg('rest_route', '/' . FRAPWP_NAMESPACE . '/info', home_url('/'));
?>
<ol class="frapwp-steps">
    <li>Crea un token en la pestaña <strong>Tokens</strong> con los permisos que necesites (por defecto solo lectura) y cópialo.</li>
    <li>En FRAP: panel izquierdo → <strong>Integraciones → WordPress → ＋</strong>. Pega la URL del sitio (<code><?= esc_html(home_url()) ?></code>) y el token, y pulsa «Probar».</li>
    <li>En FRAP, cada conexión tiene dos interruptores: <strong>Activa</strong> y <strong>IA</strong> (si las IA la pueden usar). Las IA trabajan con la skill <code>frap-wordpress</code>.</li>
    <li>Cuando el token vence, se renueva aquí sin cambiarlo: FRAP sigue funcionando.</li>
</ol>

<h3>API</h3>
<p>Base: <code><?= esc_html($base) ?></code> (sin enlaces permanentes: <code><?= esc_html($fallback) ?></code>). Cabecera: <code><?= esc_html(FRAPWP_HEADER) ?>: &lt;token&gt;</code>.</p>
<table class="widefat striped">
    <tbody>
        <tr><td><code>GET /info</code></td><td>Sitio, versiones, vencimiento y permisos del token.</td></tr>
        <tr><td><code>GET /&lt;recurso&gt;</code></td><td>Listar (parámetros de la API de WordPress/WooCommerce: <code>search</code>, <code>per_page</code>, <code>page</code>, <code>status</code>, <code>_fields</code>…).</td></tr>
        <tr><td><code>GET /&lt;recurso&gt;/&lt;id&gt;</code></td><td>Ver uno.</td></tr>
        <tr><td><code>POST /&lt;recurso&gt;</code></td><td>Crear (JSON con los campos de la API).</td></tr>
        <tr><td><code>PUT /&lt;recurso&gt;/&lt;id&gt;</code></td><td>Editar.</td></tr>
        <tr><td><code>DELETE /&lt;recurso&gt;/&lt;id&gt;</code></td><td>A la papelera (<code>force=true</code> la borra del todo).</td></tr>
        <tr><td><code>/products/&lt;id&gt;/variations</code></td><td>Variaciones de un producto (con el permiso de productos).</td></tr>
    </tbody>
</table>
<p class="description">Recursos: <?= esc_html(implode(', ', array_keys(FRAPWP_RESOURCES::all()))) ?>.</p>
