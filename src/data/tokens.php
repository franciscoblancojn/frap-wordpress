<?php

/**
 * Tokens de integración (opción FRAPWP_TOKENS).
 *
 * Del token solo se guarda el hash (sha256): el valor se muestra una sola vez, al crearlo.
 * Cada token: id, name, hash, hint (últimos 4 caracteres), user (quien lo creó: las peticiones
 * se ejecutan con sus permisos de WordPress, recortados por los del token), created, expires,
 * revoked, last_used y permissions [recurso => [read, create, update, delete]].
 */
class FRAPWP_USE_DATA_TOKENS extends FRAPWP_USE_DATA_BASE
{
    protected $KEY = FRAPWP_TOKENS;

    public static function hours($hours)
    {
        $hours = (int) $hours;
        if ($hours < 1) {
            $hours = FRAPWP_DEFAULT_HOURS;
        }
        return min(FRAPWP_MAX_HOURS, $hours);
    }

    public static function generate()
    {
        return 'frapwp_' . bin2hex(random_bytes(24));
    }

    public static function hash($token)
    {
        return hash('sha256', (string) $token);
    }

    public static function is_active($t)
    {
        return empty($t['revoked']) && (int) ($t['expires'] ?? 0) > time();
    }

    /**
     * Crea un token. Devuelve [registro, valor en claro] (el valor no se vuelve a poder ver).
     */
    public function create($name, $hours, $permissions)
    {
        $token = self::generate();
        $id = wp_generate_uuid4();
        $row = [
            'id' => $id,
            'name' => $name,
            'hash' => self::hash($token),
            'hint' => substr($token, -4),
            'user' => get_current_user_id(),
            'created' => time(),
            'hours' => self::hours($hours),
            'expires' => time() + self::hours($hours) * HOUR_IN_SECONDS,
            'revoked' => 0,
            'last_used' => 0,
            'permissions' => FRAPWP_RESOURCES::sanitize_permissions($permissions),
        ];
        $this->DATA[$id] = $row;
        $this->save();
        return [$row, $token];
    }

    public function find($id)
    {
        return $this->DATA[$id] ?? null;
    }

    /** Busca el token por su valor (comparación en tiempo constante del hash). */
    public function find_by_token($token)
    {
        if (!is_string($token) || strlen($token) < 20) {
            return null;
        }
        $hash = self::hash($token);
        foreach ($this->DATA as $row) {
            if (hash_equals((string) ($row['hash'] ?? ''), $hash)) {
                return $row;
            }
        }
        return null;
    }

    public function update($id, $changes)
    {
        if (!isset($this->DATA[$id])) {
            return false;
        }
        $this->DATA[$id] = array_merge($this->DATA[$id], $changes);
        $this->save();
        return $this->DATA[$id];
    }

    /** Vuelve a dar `hours` horas desde ahora (el valor del token no cambia: FRAP no hay que tocarlo). */
    public function renew($id, $hours)
    {
        return $this->update($id, [
            'hours' => self::hours($hours),
            'expires' => time() + self::hours($hours) * HOUR_IN_SECONDS,
            'revoked' => 0,
        ]);
    }

    public function revoke($id)
    {
        return $this->update($id, ['revoked' => time()]);
    }

    public function delete($id)
    {
        if (!isset($this->DATA[$id])) {
            return false;
        }
        unset($this->DATA[$id]);
        $this->save();
        return true;
    }

    /** Último uso del token: se guarda como mucho una vez por minuto para no escribir la opción en cada petición. */
    public function touch($id)
    {
        if (!isset($this->DATA[$id])) {
            return;
        }
        if (time() - (int) ($this->DATA[$id]['last_used'] ?? 0) >= MINUTE_IN_SECONDS) {
            $this->DATA[$id]['last_used'] = time();
            $this->save();
        }
    }
}
