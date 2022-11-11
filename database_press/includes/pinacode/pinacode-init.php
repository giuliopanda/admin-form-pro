<?php
/**
 * PINACODE
 * php version 7.2
 * 
 * @category template_engine
 * @package  DbPress
 * @subpackage Pinacode
 * @author   Giulio Pandolfelli <giuliopanda@gmail.com>
 * @license  GPL v2 or later
 * @link     https://github.com/giuliopanda/database_press
 * @example ```php
 * PinaCode::set_var('item', 'FOO') ;
 * echo PinaCode::get_registry()->short_code('io [%item]');
 * ```php
 */

namespace DbPress;

require_once dirname(__FILE__) . "/pina-class.php";
require_once dirname(__FILE__) . "/pina-functions.php";
require_once dirname(__FILE__) . "/pina-functions-parse-query.php";
require_once dirname(__FILE__) . "/pina-logical-math.php";
require_once dirname(__FILE__) . "/pina-registry.php";
require_once dirname(__FILE__) . "/pina-actions.php";
require_once dirname(__FILE__) . "/pina-attributes.php";
require_once dirname(__FILE__) . "/pina-attributes-wrap.php";
require_once dirname(__FILE__) . "/pina-filter.php";
require_once dirname(__FILE__) . "/pina-errors.php";