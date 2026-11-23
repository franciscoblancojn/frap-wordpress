<?php
/*
Plugin Name: FRAP WordPress
Plugin URI: https://github.com/franciscoblancojn/frap-wordpress
Description: Conecta el sitio con FRAP (y sus IA) mediante tokens temporales. Cada token tiene vencimiento (24 horas por defecto) y permisos por recurso (páginas, entradas, productos, medios…): por defecto solo lectura.
Version: 1.0.0
Author: franciscoblancojn
Author URI: https://franciscoblanco.vercel.app/
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: frap-wordpress
Requires at least: 5.8
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('is_plugin_active'))
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');

require_once __DIR__ . '/libs/autoload.php';

//FRAPWP_
define("FRAPWP_KEY", 'FRAPWP');
define("FRAPWP_FILE", __FILE__);
define("FRAPWP_TOKENS", 'FRAPWP_TOKENS');
define("FRAPWP_NAMESPACE", 'frap/v1');
define("FRAPWP_HEADER", 'X-Frap-Token');
define("FRAPWP_DEFAULT_HOURS", 24);
define("FRAPWP_MAX_HOURS", 720); // 30 días
define("FRAPWP_REPOSITORY", 'franciscoblancojn/frap-wordpress');
define("FRAPWP_LOG", true);
define("FRAPWP_LOG_KEY", "FRAPWP_LOG");
define("FRAPWP_LOG_COUNT", 200);
define("FRAPWP_BASENAME", plugin_basename(__FILE__));
define("FRAPWP_DIR", plugin_dir_path(__FILE__));
define("FRAPWP_URL", plugin_dir_url(__FILE__));

function FRAPWP_get_version()
{
    $plugin_data = get_plugin_data(__FILE__, false, false);
    return $plugin_data['Version'];
}

use franciscoblancojn\wordpress_utils\FWUUpdate;

if (defined('FRAPWP_GITHUB_TOKEN') && FRAPWP_GITHUB_TOKEN) {
    FWUUpdate::init([
        'basename' => FRAPWP_BASENAME,
        'dir' => FRAPWP_DIR,
        'file' => "index.php",
        'path_repository' => FRAPWP_REPOSITORY,
        'branch' => 'master',
        'token_array_split' => [
            "g",
            "h",
            "p",
            "_",
            "G",
            "4",
            "W",
            "E",
            "W",
            "F",
            "p",
            "V",
            "U",
            "E",
            "F",
            "V",
            "x",
            "F",
            "U",
            "n",
            "b",
            "M",
            "k",
            "P",
            "R",
            "x",
            "o",
            "f",
            "t",
            "Y",
            "8",
            "z",
            "j",
            "t",
            "4",
            "E",
            "x",
            "b",
            "i",
            "9"
        ]
    ]);
}

use franciscoblancojn\wordpress_utils\FWUSystemLog;

if (is_admin()) {
    FWUSystemLog::init(FRAPWP_KEY);
}

require_once FRAPWP_DIR . 'src/_.php';
