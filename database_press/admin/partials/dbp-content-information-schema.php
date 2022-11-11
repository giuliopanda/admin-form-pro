<?php
/**
 * Il template della pagina amministrativa
 * Lo spazio dei grafici è impostato qui, e poi verrà disegnato in javascript
 * l'html del setup e del resize bulk invece è caricato sui due html a parte
 *
 * @package  DbPress
 */
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator')) return;
?>
<div class="dbp-content-header">
    <?php require dirname(__FILE__) . '/dbp-partial-tabs.php'; ?>
</div>

<div class="dbp-content-table js-id-dbp-content" >
   <div class="dbp-content-margin">

    <h2 class="dbp-h2-inline dbp-content-margin">DATABASE: <?php echo $wpdb->dbname; ?></h2>
    <a class="dbp-submit" href="<?php echo add_query_arg(['section'=>'table-structure','action'=>'structure-edit','dbp_id'=>''], admin_url("admin.php?page=database_press")); ?>"><?php _e('Create new table'); ?></a>
    <hr>

    <form id="table_filter" method="post" action="<?php echo admin_url("admin.php"); ?>">
        <input type="hidden" name="page"  value="database_press">
        <input type="hidden" name="action_query" id="dbp_action_query"  value="">
        <input type="hidden" id="dbp_table_sort_field" name="filter[sort][field]" value="<?php echo dbp_fn::esc_request('filter.sort.field'); ?>">
        <input type="hidden" id="dbp_table_sort_order"  name="filter[sort][order]" value="<?php echo dbp_fn::esc_request('filter.sort.order'); ?>">
        <?php $html_table->render($table_model->items, false); ?>
    </form>
    
    </div>
</div>
<script>
 /**
  * Mostra nasconde il label del prefisso della tabella
  * @param DOM el 
  * @param String id 
  */
function dbp_use_prefix(el, id) {
    if (jQuery(el).is(':checked')) {
        jQuery('#'+id).css('visibility','visible');
    } else {
        jQuery('#'+id).css('visibility','hidden');
    }
}
</script>