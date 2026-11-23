# FRAP WordPress — Reglas para IAs

## Código
- **PHP 7.4+**, estándares de WordPress. Clases y funciones con prefijo `FRAPWP_`.
- **JavaScript ES5 + jQuery** (sin arrow functions, `let`/`const` ni template literals). Funciones con prefijo `frapwp_`.
- **CSS** con prefijo `frapwp-` (las clases `fwue-` son de `wordpress_utils`).
- Escapar toda salida (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`) y sanitizar toda entrada.

## Seguridad
- Toda ruta REST usa `'permission_callback' => [FRAPWP_AUTH::class, 'check']` (`npm run check` lo verifica).
- El token solo se guarda como hash (`FRAPWP_USE_DATA_TOKENS::hash`). Nunca se guarda, registra ni devuelve en claro, salvo una vez al crearlo.
- Todo recurso nuevo pasa por `FRAPWP_RESOURCES`: lista blanca de rutas internas y acciones. Por defecto solo lectura; lo que tiene datos personales empieza apagado.
- Las peticiones internas se hacen con `rest_do_request` como el usuario que creó el token (administrador): WordPress valida y sanea como siempre.
- Formularios del admin: `check_admin_referer(FRAPWP_API_TOKENS::NONCE)` + `current_user_can('manage_options')`.
- El token de GitHub de `FWUUpdate` no va en el código: `FRAPWP_GITHUB_TOKEN` en `wp-config.php`.

## Arquitectura
- `index.php`: cabecera y constantes `FRAPWP_*`. Sin lógica.
- `src/_.php`: cargador maestro. Cada carpeta tiene su `_.php`.
- `src/data`: opciones (`FRAPWP_TOKENS`). `src/services`: recursos, autenticación y proxy a la API. `src/api`: rutas REST y formularios. `src/page`: página del admin.
- Plugin genérico: **no** usar nombres ni textos de un sitio concreto.
- El cliente de este plugin es FRAP (`bridge/wordpress.js` y `bridge/frap-wp.js` del repo de FRAP): si cambia la API, se actualizan juntos.

## Publicar
- El código vive en el repo de FRAP (`integrations/wordpress/frap-wordpress`). El repo del plugin recibe esta carpeta con `git subtree split` (`scripts/publish.sh`): no se edita allá.
- Anotar los cambios en `CHANGELOG.md` bajo `## [Sin publicar]`.
- `npm run push-v -- patch|minor|major`.
