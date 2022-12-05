<?php
/**
 * Database Press
 * php version 7.2
 * 
 * @category plugins
 * @package  DbPress
 * @author   Giulio Pandolfelli <giuliopanda@gmail.com>
 * @license  GPL v2 or later
 * @link     https://github.com/giuliopanda/database_press
 * 
 * @wordpress-plugin     
 * Plugin Name:       Database Press
 * Plugin URI:        https://github.com/giuliopanda/database_press
 * Description:       Db Press is a tool designed to show MySQL tables.
 * Version:           2.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       db_press
 * Domain Path:       /languages
 */

namespace DbPress;

if (!defined('WPINC')) {
    die;
}
define('DB_PRESS_VERSION', rand());
define('DBP_DIR',   __DIR__ . "/");


require_once DBP_DIR . "includes/dbp-functions.php" ;
require_once DBP_DIR . "includes/dbp-loader.php" ;
require_once DBP_DIR . "includes/dbp-functions-import.php" ;
require_once DBP_DIR . "includes/dbp-functions-structure.php";
require_once DBP_DIR . "includes/dbp-functions-items-setting.php";
require_once DBP_DIR . "includes/pinacode/pinacode-init.php";

/**
 * Activate the plugin.
 * 
 * @param $h 
 * 
 * @return void
 */
function dbpress_activate($h)
{ 
    //echo "Grazie per aver installato questo plugin!";
    $opt = ['date'=>date('Y-m-d'), 'voted'=>'no'];
    update_option('_dbp_activete_info', $opt, false);
}
\register_activation_hook(__FILE__, '\DbPress\dbpress_activate');