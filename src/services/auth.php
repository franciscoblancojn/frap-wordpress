<?php

/**
 * Validación del token de una petición (cabecera X-Frap-Token).
 */
class FRAPWP_AUTH
{
    /** Token de la petición en curso (lo deja `check()` para el callback). */
    public static $token = null;

    /**
     * @return true|WP_Error
     */
    public static function check(WP_REST_Request $request)
    {
        self::$token = null;
        $value = (string) $request->get_header('x_frap_token');
        if ('' === $value) {
            return new WP_Error('frapwp_no_token', 'Falta la cabecera ' . FRAPWP_HEADER . '.', ['status' => 401]);
        }
        $row = (new FRAPWP_USE_DATA_TOKENS())->find_by_token($value);
        if (!$row) {
            return new WP_Error('frapwp_bad_token', 'Token inválido.', ['status' => 401]);
        }
        if (!empty($row['revoked'])) {
            return new WP_Error('frapwp_revoked', 'Token revocado.', ['status' => 401]);
        }
        if ((int) $row['expires'] <= time()) {
            return new WP_Error('frapwp_expired', 'Token vencido: renuévalo en WordPress (FRAP → Tokens).', ['status' => 401]);
        }
        // Las peticiones se hacen como el usuario que creó el token: si ya no existe o dejó de ser
        // administrador, el token deja de servir.
        $user = get_user_by('id', (int) $row['user']);
        if (!$user || !user_can($user, 'manage_options')) {
            return new WP_Error('frapwp_user', 'El usuario que creó el token ya no existe o no es administrador.', ['status' => 403]);
        }
        self::$token = $row;
        return true;
    }
}
