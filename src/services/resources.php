<?php

/**
 * Recursos que una integración puede usar y cómo se traducen a la API REST de WordPress / WooCommerce.
 *
 * actions: acciones posibles del recurso (read, create, update, delete).
 * read:    si la lectura viene activada en un token nuevo. Por defecto todo es solo lectura, salvo lo
 *          que tiene datos personales (usuarios, clientes, pedidos), que empieza apagado.
 * woo:     solo existe con WooCommerce activo.
 */
class FRAPWP_RESOURCES
{
    const ACTIONS = ['read', 'create', 'update', 'delete'];

    const ACTION_LABELS = [
        'read' => 'Ver',
        'create' => 'Crear',
        'update' => 'Editar',
        'delete' => 'Eliminar',
    ];

    public static function all()
    {
        $list = [
            'posts' => ['label' => 'Entradas', 'route' => '/wp/v2/posts', 'actions' => self::ACTIONS, 'read' => true],
            'pages' => ['label' => 'Páginas', 'route' => '/wp/v2/pages', 'actions' => self::ACTIONS, 'read' => true],
            'media' => ['label' => 'Medios', 'route' => '/wp/v2/media', 'actions' => ['read', 'update', 'delete'], 'read' => true],
            'categories' => ['label' => 'Categorías', 'route' => '/wp/v2/categories', 'actions' => self::ACTIONS, 'read' => true],
            'tags' => ['label' => 'Etiquetas', 'route' => '/wp/v2/tags', 'actions' => self::ACTIONS, 'read' => true],
            'comments' => ['label' => 'Comentarios', 'route' => '/wp/v2/comments', 'actions' => self::ACTIONS, 'read' => true],
            'users' => ['label' => 'Usuarios', 'route' => '/wp/v2/users', 'actions' => ['read'], 'read' => false, 'personal' => true],
            'plugins' => ['label' => 'Plugins (lista)', 'route' => '/wp/v2/plugins', 'actions' => ['read'], 'read' => true, 'list_only' => true],
            'products' => ['label' => 'Productos', 'route' => '/wc/v3/products', 'actions' => self::ACTIONS, 'read' => true, 'woo' => true, 'sub' => ['variations']],
            'product_categories' => ['label' => 'Categorías de productos', 'route' => '/wc/v3/products/categories', 'actions' => self::ACTIONS, 'read' => true, 'woo' => true],
            'coupons' => ['label' => 'Cupones', 'route' => '/wc/v3/coupons', 'actions' => self::ACTIONS, 'read' => true, 'woo' => true],
            'orders' => ['label' => 'Pedidos', 'route' => '/wc/v3/orders', 'actions' => ['read', 'update'], 'read' => false, 'woo' => true, 'personal' => true],
            'customers' => ['label' => 'Clientes', 'route' => '/wc/v3/customers', 'actions' => ['read'], 'read' => false, 'woo' => true, 'personal' => true],
        ];
        $woo = class_exists('WooCommerce');
        foreach ($list as $key => $r) {
            if (!empty($r['woo']) && !$woo) {
                unset($list[$key]);
            }
        }
        return apply_filters('frapwp_resources', $list);
    }

    public static function get($key)
    {
        $all = self::all();
        return $all[$key] ?? null;
    }

    /** Permisos de un token nuevo: solo lectura (sin datos personales). */
    public static function default_permissions()
    {
        $out = [];
        foreach (self::all() as $key => $r) {
            $out[$key] = ['read' => !empty($r['read']), 'create' => false, 'update' => false, 'delete' => false];
        }
        return $out;
    }

    /**
     * Normaliza lo que llega del formulario: [recurso => [acción => '1']]. Lo que no llega queda apagado.
     * Ver es requisito de las demás: no se puede editar lo que no se puede ver.
     */
    public static function sanitize_permissions($input)
    {
        $input = is_array($input) ? $input : [];
        $out = [];
        foreach (self::all() as $key => $r) {
            $row = isset($input[$key]) && is_array($input[$key]) ? $input[$key] : [];
            $perm = [];
            foreach (self::ACTIONS as $a) {
                $perm[$a] = in_array($a, $r['actions'], true) && !empty($row[$a]);
            }
            if (!$perm['read']) {
                $perm = ['read' => false, 'create' => false, 'update' => false, 'delete' => false];
            }
            $out[$key] = $perm;
        }
        return $out;
    }

    public static function allowed($token, $resource, $action)
    {
        return !empty($token['permissions'][$resource][$action]) && null !== self::get($resource);
    }

    /** Lista para FRAP: recurso → acciones permitidas a este token. */
    public static function summary($token)
    {
        $out = [];
        foreach (self::all() as $key => $r) {
            $acts = [];
            foreach (self::ACTIONS as $a) {
                if (self::allowed($token, $key, $a)) {
                    $acts[] = $a;
                }
            }
            $out[$key] = ['label' => $r['label'], 'allowed' => $acts, 'available' => $r['actions']];
        }
        return $out;
    }
}
