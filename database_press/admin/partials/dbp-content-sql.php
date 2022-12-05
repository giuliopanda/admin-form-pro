<?php
/**
 * Scrive una query
 * 
 * Per il rendering delle tabelle chiama: dirname(__FILE__)."/ddbp-content-table-without-filter.php" 
 * 
 * @var Boolean $ajax_continue 
 * @var Array $info
 * @var $queries

 * @package     db-press
 */

namespace DbPress;

if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
?>
<div class="dbp-content-header">
    <?php require dirname(__FILE__) . '/dbp-partial-tabs.php'; ?>
</div>
<div class="dbp-content-table js-id-dbp-content" >
    <form id="table_filter" method="post" action="<?php echo admin_url("admin.php?page=database_press"); ?>">
        <input type="hidden" name="section" value="table-browse">
        <input type="hidden" name="action_query" value="custom_query">
        <?php dbp_html_sql::render_sql_from($table_model, true); ?>
    </form>
</div>