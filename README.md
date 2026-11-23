# FRAP WordPress

**Versión:** 1.0.1

Plugin de WordPress que conecta el sitio con [FRAP](https://github.com/franciscoblancojn/frap) y sus IA (claude, codex, opencode) mediante **tokens temporales con permisos por recurso**.

- Cada token vence solo: **24 horas por defecto** (de 1 hora a 30 días). Se renueva sin cambiar su valor, así FRAP sigue funcionando.
- **Permisos por recurso** (entradas, páginas, medios, categorías, etiquetas, comentarios, usuarios, plugins y, con WooCommerce, productos, categorías de productos, cupones, pedidos y clientes) y por acción (ver, crear, editar, eliminar).
- **Por defecto, solo lectura.** Lo que tiene datos personales (usuarios, pedidos, clientes) empieza apagado.
- Del token solo se guarda el hash: el valor se muestra una vez, al crearlo. Se puede revocar al instante.
- Toda petición queda en la pestaña **Actividad**.

## Uso

1. WordPress → **FRAP** → Tokens → «＋ Nuevo token»: nombre, duración y permisos. Copia el token.
2. FRAP → panel izquierdo → **Integraciones → WordPress → ＋**: URL del sitio y token → «Probar».

## API

Base `/wp-json/frap/v1` (o `?rest_route=/frap/v1/…`), cabecera `X-Frap-Token`.

| Petición | Qué hace |
|---|---|
| `GET /info` | Sitio, versiones, vencimiento y permisos del token |
| `GET /<recurso>[/<id>]` | Ver o listar (parámetros de la API de WordPress/WooCommerce) |
| `POST /<recurso>` | Crear |
| `PUT /<recurso>/<id>` | Editar |
| `DELETE /<recurso>/<id>` | Papelera (`force=true` borra del todo) |
| `/products/<id>/variations[/<id>]` | Variaciones (permiso de productos) |

Las peticiones se traducen a la API REST de WordPress (`/wp/v2/…`) o WooCommerce (`/wc/v3/…`) y se ejecutan como el administrador que creó el token, recortadas por los permisos del token.

## Desarrollo

El código vive dentro del repo de FRAP (`integrations/wordpress/frap-wordpress`) y se publica en su propio repositorio con `git subtree split`.

- `npm run check`: validaciones (php -l, JS ES5, require_once, secretos, rutas REST con permiso).
- `npm run push-v -- patch|minor|major`: sube la versión, hace commit en FRAP y publica en el repo del plugin con el tag (el workflow crea la release).
- `npm run publish`: solo empuja la rama al repo del plugin (`FRAPWP_REMOTE` cambia el destino).
- Actualizaciones automáticas (`FWUUpdate`): el token de GitHub va en `index.php` (`token_array_split`), de solo lectura a repos públicos, como en los demás plugins del autor.
