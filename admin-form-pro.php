<?php
/**
 * ADMIN FORM PRO
 * 
 * @wordpress-plugin
 * Plugin Name:       Admin form PRO
 * Description:       Admin form PRO is a tool designed to manage administration form.
 * Version:           1.7.0
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 	  database_press
 * Domain Path: 	  /languages
 */
namespace admin_form;

if (!defined('WPINC')) die;

define('ADFO_PRO_VERSION', '1.7.0');
//define('ADFO_PRO_VERSION', rand());

if(is_file(__DIR__ . "/../admin-form/admin-form.php")) {
    define('ADFO_EXIST', 'OK');
}
$idrs = dirname(plugin_dir_path(__FILE__));
if (defined('ADFO_EXIST')) {
    require_once(__DIR__ . "/includes/dbp-loader-list-form.php");
    require_once(__DIR__ . "/includes/dbp-loader-list-sql-edit.php");
    require_once(__DIR__ . "/includes/dbp-list-loader.php");
    require_once(__DIR__ . "/includes/adfo-loader-import-export.php");
}
if (is_admin()) {
    require_once( __DIR__ . "/includes/adfo-plugin-updater.php" );
    require_once(__DIR__ . "/database_press/database_press.php");
    new ADFO_gitHub_plugin_updater(__FILE__);
}