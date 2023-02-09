<?php
/**
 * ADMIN FORM PRO
 * 
 * @package          Admin_form_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       Admin form PRO
 * Description:       Admin form PRO is a tool designed to manage administration form.
 * Version:           1.6.1
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

define('ADFO_PRO_VERSION', '1.6.1');
//define('ADFO_PRO_VERSION', rand());

$idrs = dirname(plugin_dir_path(__FILE__));

require_once(__DIR__ . "/includes/dbp-loader-list-form.php");
require_once(__DIR__ . "/includes/dbp-loader-list-sql-edit.php");
require_once(__DIR__ . "/includes/dbp-list-loader.php");
require_once(__DIR__ . "/includes/adfo-loader-import-export.php");

if (is_admin()) {
    require_once( __DIR__ . "/includes/adfo-plugin-updater.php" );
    require_once(__DIR__ . "/database_press/database_press.php");
    new ADFO_gitHub_plugin_updater(__FILE__);
}
