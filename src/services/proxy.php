<?php

use franciscoblancojn\wordpress_utils\FWUSystemLog;

/**
 * Traduce una petición de FRAP (/frap/v1/<recurso>[/<id>[/<sub>[/<id>]]]) a la API REST de WordPress o
 * WooCommerce, después de comprobar el permiso del token. La petición interna se hace como el usuario
 * que creó el token, así WordPress valida y sanea todo como siempre.
 */
class FRAPWP_PROXY
{
    /** Acción según el método y si la ruta termina en un id. */
    public static function action($method, $has_id)
    {
        $method = strtoupper($method);
        if ('GET' === $method || 'HEAD' === $method) {
            return 'read';
        }
        if ('DELETE' === $method) {
            return 'delete';
        }
        return $has_id ? 'update' : 'create';
    }

    public static function handle(WP_REST_Request $request)
    {
        $token = FRAPWP_AUTH::$token;
        $resource = (string) $request['resource'];
        $id = isset($request['id']) && '' !== (string) $request['id'] ? absint($request['id']) : 0;
        $sub = isset($request['sub']) ? (string) $request['sub'] : '';
        $subid = isset($request['subid']) && '' !== (string) $request['subid'] ? absint($request['subid']) : 0;
        $def = FRAPWP_RESOURCES::get($resource);

        if (!$def) {
            return self::fail($request, $token, 404, 'frapwp_resource', 'Recurso desconocido: ' . $resource . '. Ver GET /' . FRAPWP_NAMESPACE . '/info.');
        }
        if ('' !== $sub && (!$id || empty($def['sub']) || !in_array($sub, $def['sub'], true))) {
            return self::fail($request, $token, 404, 'frapwp_resource', 'Ruta no soportada.');
        }
        if (!empty($def['list_only']) && $id) {
            return self::fail($request, $token, 404, 'frapwp_resource', 'Este recurso solo se puede listar.');
        }
        $has_id = '' !== $sub ? (bool) $subid : (bool) $id;
        $action = self::action($request->get_method(), $has_id);
        if (!in_array($action, $def['actions'], true)) {
            return self::fail($request, $token, 405, 'frapwp_action', 'Acción no disponible para ' . $def['label'] . ': ' . $action . '.');
        }
        if (!FRAPWP_RESOURCES::allowed($token, $resource, $action)) {
            return self::fail($request, $token, 403, 'frapwp_forbidden', 'El token no tiene permiso para ' . FRAPWP_RESOURCES::ACTION_LABELS[$action] . ' en ' . $def['label'] . '. Se cambia en WordPress → FRAP → Tokens.');
        }

        $route = $def['route'] . ($id ? '/' . $id : '') . ('' !== $sub ? '/' . $sub . ($subid ? '/' . $subid : '') : '');
        $inner = new WP_REST_Request($request->get_method(), $route);
        $query = $request->get_query_params();
        unset($query['rest_route'], $query['_method'], $query['_frap_links']);
        $inner->set_query_params($query);
        if ('read' !== $action) {
            $json = $request->get_json_params();
            if (is_array($json)) {
                $inner->set_header('content-type', 'application/json');
                $inner->set_body(wp_json_encode($json));
            } else {
                $inner->set_body_params($request->get_body_params());
            }
        }

        $previous = get_current_user_id();
        wp_set_current_user((int) $token['user']);
        try {
            $response = rest_do_request($inner);
        } finally {
            wp_set_current_user($previous);
        }
        $server = rest_get_server();
        $status = $response->get_status();
        $data = $server->response_to_data($response, false);
        if (empty($request['_frap_links'])) {
            $data = self::strip_links($data);
        }

        (new FRAPWP_USE_DATA_TOKENS())->touch($token['id']);
        self::log($request, $token, $status, $action === 'read' ? null : ($id ?: null));

        $out = new WP_REST_Response($data, $status);
        foreach (['X-WP-Total', 'X-WP-TotalPages'] as $h) {
            $headers = $response->get_headers();
            if (isset($headers[$h])) {
                $out->header($h, $headers[$h]);
            }
        }
        return self::nocache($out);
    }

    /** _links de la API: ocupan mucho y a la IA no le sirven (se piden con _frap_links=1). */
    public static function strip_links($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        unset($data['_links']);
        foreach ($data as $k => $v) {
            if (is_array($v) && isset($v['_links'])) {
                unset($data[$k]['_links']);
            }
        }
        return $data;
    }

    public static function fail(WP_REST_Request $request, $token, $status, $code, $message)
    {
        self::log($request, $token, $status, null, $message);
        return new WP_Error($code, $message, ['status' => $status]);
    }

    public static function log(WP_REST_Request $request, $token, $status, $id = null, $error = '')
    {
        $read = 'GET' === $request->get_method();
        FWUSystemLog::add(FRAPWP_KEY, [
            'type' => $read ? 'read' : 'write',
            'date' => wp_date('Y-m-d H:i:s'),
            'token' => $token['name'] ?? '?',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'query' => $read ? $request->get_query_params() : null,
            'status' => $status,
            'error' => $error,
        ]);
    }

    public static function nocache($response)
    {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        do_action('litespeed_control_set_nocache', 'frap-wordpress api');
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->header('X-LiteSpeed-Cache-Control', 'no-cache');
        $response->header('CDN-Cache-Control', 'no-store');
        $response->header('Pragma', 'no-cache');
        return $response;
    }
}
