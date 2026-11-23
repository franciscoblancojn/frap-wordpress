<?php

/**
 * API para FRAP: /wp-json/frap/v1/… (o ?rest_route=/frap/v1/… sin enlaces permanentes).
 *   GET  /info                                  sitio, versiones, vencimiento y permisos del token
 *   GET  /<recurso>[/<id>]                      ver (listar con los parámetros de la API de WordPress: search, per_page…)
 *   POST /<recurso>                             crear
 *   POST|PUT|PATCH /<recurso>/<id>              editar
 *   DELETE /<recurso>/<id>                      eliminar (papelera; force=true para borrar del todo)
 *   …/products/<id>/variations[/<id>]           variaciones (permiso de productos)
 * Todas piden la cabecera X-Frap-Token.
 */
class FRAPWP_API_REST
{
    public static function init()
    {
        add_action('rest_api_init', [self::class, 'routes']);
        add_filter('rest_post_dispatch', [self::class, 'nocache'], 10, 3);
    }

    public static function routes()
    {
        register_rest_route(FRAPWP_NAMESPACE, '/info', [
            'methods' => 'GET',
            'callback' => [self::class, 'info'],
            'permission_callback' => [FRAPWP_AUTH::class, 'check'],
        ]);
        register_rest_route(FRAPWP_NAMESPACE, '/(?P<resource>[a-z_]+)(?:/(?P<id>\d+))?(?:/(?P<sub>[a-z_]+)(?:/(?P<subid>\d+))?)?', [
            'methods' => 'GET, POST, PUT, PATCH, DELETE',
            'callback' => [FRAPWP_PROXY::class, 'handle'],
            'permission_callback' => [FRAPWP_AUTH::class, 'check'],
        ]);
    }

    public static function info(WP_REST_Request $request)
    {
        $token = FRAPWP_AUTH::$token;
        (new FRAPWP_USE_DATA_TOKENS())->touch($token['id']);
        global $wp_version;
        return FRAPWP_PROXY::nocache(new WP_REST_Response([
            'site' => home_url(),
            'name' => get_bloginfo('name'),
            'plugin' => FRAPWP_get_version(),
            'wordpress' => $wp_version,
            'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null,
            'now' => time(),
            'token' => [
                'name' => $token['name'],
                'expires' => (int) $token['expires'],
                'hours' => (int) ($token['hours'] ?? FRAPWP_DEFAULT_HOURS),
            ],
            'resources' => FRAPWP_RESOURCES::summary($token),
        ]));
    }

    /** Sin caché (LiteSpeed, CDN…) para todo /frap/v1, también en los errores. */
    public static function nocache($result, $server, $request)
    {
        if (0 === strpos($request->get_route(), '/' . FRAPWP_NAMESPACE) && $result instanceof WP_HTTP_Response) {
            FRAPWP_PROXY::nocache($result);
        }
        return $result;
    }
}

FRAPWP_API_REST::init();
