<?php
/**
 * La pagina caricata da section table-browse e che carica query e tabella di visualizzazione standard.
 *
 * @var Class $dbp function
 * @var Array $table_items
 * @var Array $list_of_tables
 * @var database_press_model_base $table_model  
 */
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
$my_custom_link = admin_url("admin.php?page=database_press&amp;section=table-browse");
if (array_key_exists('table', $_REQUEST)) {
    $my_custom_link = add_query_arg(['table'=>sanitize_text_field($_REQUEST['table'])], $my_custom_link);
}
$some_error = $table_model->last_error || @$msg_error != "";
?>

<div class="dbp-content-header">
    <?php require dirname(__FILE__).'/dbp-partial-tabs.php'; ?>
</div>

 <div class="dbp-content-table js-id-dbp-content" >
    <form id="table_filter" method="post" action="<?php echo $my_custom_link; ?>">
        <input type="hidden" id="dbp_table_sort_field" name="filter[sort][field]" value="<?php echo esc_attr(dbp_fn::esc_request('filter.sort.field')); ?>">
        <input type="hidden" id="dbp_table_sort_order"  name="filter[sort][order]" value="<?php echo esc_attr(dbp_fn::esc_request('filter.sort.order')); ?>">
        <input type="hidden" id="dbp_table_filter_limit_start" name="filter[limit_start]" value="<?php echo esc_attr($table_model->limit_start); ?>">
        <input type="hidden" name="action_query" id="dbp_action_query"  value="">
        <input type="hidden" name="search" id="dbp_original_search"  value="<?php echo esc_attr(wp_unslash(dbp_fn::req('search', ''))); ?>">
        
        <?php dbp_html_sql::render_sql_from($table_model, $show_query); ?>
        
        <?php if (@$msg != "") : ?>
            <div class="dbp-alert-info"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if (@$msg_error != ""): ?>
            <div class="dbp-alert-sql-error"><?php echo $msg_error; ?></div>
        <?php endif ; ?>
        <div class="dbp-alert-info" id="dbp_cookie_msg" style="display:none"></div>
        <div class="dbp-alert-sql-error"  id="dbp_cookie_error" style="display:none"></div>
     
            <!--
            <ul class="subsubsub">
                <li class="all"><a href="#" class="current" aria-current="page">view1 <span class="count">(2)</span></a> |</li>
                <li class="publish"><a href="#">view2 <span class="count">(1)</span></a> |</li>
                <li class="draft"><a href="#">edit views <span class="count">(1)</span></a></li>
            </ul>
            -->
        
            <div class="tablenav top dbp-tablenav-top">
                <?php if ($table_model->last_error == false && $table_model->total_items > 0) : ?>
                    <div class="alignleft">
                        <span class="displaying-num">Show <?php echo count($table_items) -1; ?> of <?php echo $table_model->total_items; ?> items</span>
                        <span class="" >Element per page: </span>
                        <input type="hidden" name="cache_count" id="cache_count"  value="<?php echo absint($table_model->total_items); ?>">
                        <input type="number" name="filter[limit]" id="Element_per_page" class="dbp-pagination-input" value="<?php echo absint($table_model->limit); ?>" style="width:3.4rem; padding-right:0;" min="1" max="500">
                        <div name="change_limit_start" class="button action dbp-pagination-input"  onclick="dbp_submit_table_filter('change_limit')" >Apply</div>
                        <?php dbp_fn::get_pagination($table_model->total_items, $table_model->limit_start, $table_model->limit); ?>
                        <?php if (dbp_fn::is_query_filtered())  : ?>
                            <div id="dbp-bnt-clear-filter-query" class="button"  onclick="dbp_clear_filter()"><?php _e('Clear Filter','db_press'); ?></div>
                        <?php endif; ?>
                    </div>
               
                    <div style="float: left; margin-left: 1rem; line-height: 1.6rem;">
                        <label>
                            <input type="checkbox" name="dbp-show-all-text" value="1" onchange="dbp_submit_table_filter()"<?php echo(isset($_REQUEST['dbp-show-all-text']) && $_REQUEST['dbp-show-all-text'] == 1) ? 'checked="checked"' : ''; ?>><?php _e('Show all text', 'admin_form'); ?></label>
                    </div>
                <?php endif; ?>
                <?php if (count($table_model->tables_primaries) > 0) : ?>
                <div class="alignright">
                    <div class="dbp-submit" onclick="dbp_edit_details_v2()"><?php _e('Add New record','db_press'); ?></div>
                </div>
                <?php endif; ?>
                <br class="clear">
            </div>
      
        <?php echo $html_content; ?>
        <?php if ($table_model->last_error === false && is_countable($table_model->items) && $table_model->items > 1 && $table_model->sql_type() == "select") : ?>
            <?php 
           
            $max_input_vars = (int)dbp_fn::get_max_input_vars();
            $table_bulk_ok = ($table_model->table_status() != 'CLOSE' && count($table_model->get_pirmaries()) > 0);
            ?>
            <div class="dbp-table-footer">
                <div class="tablenav-pages dbp-table-footer-left">
                    <div class="alignleft actions bulkactions">
                        <select id="dbp_bulk_action_selector_bottom">
                        <option value="-1"><?php _e('Bulk actions', 'db_press'); ?></option>
                            <option value="download" class="hide-if-no-js"><?php _e('Download'); ?></option>
                            <?php if  ($table_bulk_ok) : ?>
                            <option value="delete"><?php _e('Delete'); ?></option>
                            <?php endif; ?>
                        </select>
                        <select id="dbp_bulk_on_selector_bottom">
                            <?php if ($max_input_vars-50 > count($table_model->items) && $table_bulk_ok) : ?>
                            <option value="checkboxes" class="hide-if-no-js"><?php _e('On selected records','db_press'); ?></option>
                            <?php endif;?>
                            <option value="sql"><?php _e('Query results operations','db_press'); ?></option>
                        </select>
                       
                        <div class="button" onclick="dbp_bulk_actions()"><?php _e('Apply'); ?></div>
                    </div>
                </div>
                <div class="tablenav-pages dbp-table-footer-right">
                    <?php dbp_fn::get_pagination($table_model->total_items, $table_model->limit_start, $table_model->limit); ?>
                </div>
                <br class="clear">
            </div>
        <?php endif; ?>

    </form>
    <?php 
    $list_of_tables_js = [];
    $list_of_tables = dbp_fn::get_table_list();
    foreach ($list_of_tables['tables'] as $lot) {
        $list_of_tables_js[] = $lot;
    }
    $list_of_columns = dbp_fn::get_all_columns();
    ?>
    <script>
    var dbp_tables = <?php echo json_encode($list_of_tables_js); ?>;
    var dbp_columns = <?php echo json_encode($list_of_columns); ?>;
    </script>
</div>
