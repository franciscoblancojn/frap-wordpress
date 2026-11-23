<?php

use franciscoblancojn\wordpress_utils\FWUSystemLog;

/**
 * Formularios de la pestaña Tokens (admin-post.php): crear, guardar permisos, renovar, revocar y eliminar.
 */
class FRAPWP_API_TOKENS
{
    const NONCE = 'frapwp_tokens';

    public static function init()
    {
        foreach (['create', 'save', 'renew', 'revoke', 'delete'] as $a) {
            add_action('admin_post_frapwp_' . $a, [self::class, $a]);
        }
    }

    private static function guard()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para gestionar los tokens de FRAP.', 403);
        }
        check_admin_referer(self::NONCE);
    }

    private static function back($status, $message)
    {
        set_transient('frapwp_respond_' . get_current_user_id(), ['status' => $status, 'message' => $message], 60);
        wp_safe_redirect(admin_url('admin.php?page=' . FRAPWP_KEY));
        exit;
    }

    private static function id()
    {
        return sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
    }

    private static function audit($type, $row)
    {
        FWUSystemLog::add(FRAPWP_KEY, [
            'type' => 'token_' . $type,
            'date' => wp_date('Y-m-d H:i:s'),
            'token' => $row['name'] ?? '?',
            'user' => get_current_user_id(),
            'expires' => isset($row['expires']) ? wp_date('Y-m-d H:i:s', (int) $row['expires']) : null,
            'permissions' => isset($row['permissions']) ? FRAPWP_RESOURCES::summary($row) : null,
        ]);
    }

    public static function create()
    {
        self::guard();
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        if ('' === $name) {
            self::back('error', 'Ponle un nombre al token (p. ej. "FRAP de Francisco").');
        }
        $perms = wp_unslash($_POST['perm'] ?? []);
        list($row, $token) = (new FRAPWP_USE_DATA_TOKENS())->create($name, absint($_POST['hours'] ?? FRAPWP_DEFAULT_HOURS), $perms);
        self::audit('create', $row);
        // El valor se muestra una sola vez, a quien lo creó.
        set_transient('frapwp_new_token_' . get_current_user_id(), ['id' => $row['id'], 'token' => $token], 10 * MINUTE_IN_SECONDS);
        self::back('success', 'Token creado. Cópialo ahora: no se vuelve a mostrar.');
    }

    public static function save()
    {
        self::guard();
        $data = new FRAPWP_USE_DATA_TOKENS();
        $id = self::id();
        if (!$data->find($id)) {
            self::back('error', 'El token no existe.');
        }
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $changes = ['permissions' => FRAPWP_RESOURCES::sanitize_permissions(wp_unslash($_POST['perm'] ?? []))];
        if ('' !== $name) {
            $changes['name'] = $name;
        }
        $row = $data->update($id, $changes);
        self::audit('save', $row);
        self::back('success', 'Permisos guardados.');
    }

    public static function renew()
    {
        self::guard();
        $row = (new FRAPWP_USE_DATA_TOKENS())->renew(self::id(), absint($_POST['hours'] ?? FRAPWP_DEFAULT_HOURS));
        if (!$row) {
            self::back('error', 'El token no existe.');
        }
        self::audit('renew', $row);
        self::back('success', 'Token renovado hasta ' . wp_date('Y-m-d H:i', (int) $row['expires']) . '. El valor no cambia: FRAP sigue funcionando sin tocar nada.');
    }

    public static function revoke()
    {
        self::guard();
        $row = (new FRAPWP_USE_DATA_TOKENS())->revoke(self::id());
        if (!$row) {
            self::back('error', 'El token no existe.');
        }
        self::audit('revoke', $row);
        self::back('success', 'Token revocado: deja de funcionar de inmediato.');
    }

    public static function delete()
    {
        self::guard();
        $data = new FRAPWP_USE_DATA_TOKENS();
        $row = $data->find(self::id());
        if (!$row || !$data->delete($row['id'])) {
            self::back('error', 'El token no existe.');
        }
        self::audit('delete', $row);
        self::back('success', 'Token eliminado.');
    }
}

FRAPWP_API_TOKENS::init();
