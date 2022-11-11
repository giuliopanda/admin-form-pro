<?php 
/**
* La modifica/creazione degli indici nella struttura della tabella
*/
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
?>
<div class="dbp-content-header">
    <?php require dirname(__FILE__).'/dbp-partial-tabs.php'; ?>
</div>
<div class="dbp-content-table js-id-dbp-content">
    <div class="dbp-content-margin">
        <h2><?php printf(__('Table %s INDEX','database_press'), $table); ?></h2>
        <?php if ($msg != "") : ?>
            <div class="dbp-alert-info"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if (@$msg_error != ""): ?>
            <div class="dbp-alert-sql-error"><?php echo $msg_error; ?></div>
        <?php endif ; ?>
        <div class="dbp-content-margin">
            <form id="table_structure_index" method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-structure&table=".$table); ?>" id="dbp_indexes">
                <input type="hidden" name="action" value="save_index" />
                <input type="hidden" name="dbp_id" value="<?php echo esc_attr($id); ?>" />
                <input type="hidden" name="original_index" value="<?php echo esc_attr($indexes->choice); ?>" />
                <input type="hidden" name="original_name" value="<?php echo esc_attr($indexes->name); ?>" />
                <?php if ($indexes->name != "") : ?>
                <div class="dbp-form-row">
                    <label><span class="dbp-form-label "><?php _e('Index name', 'db_press'); ?></span>
                        <input name="index[name]" value="<?php echo esc_attr($indexes->name); ?>" class="dbp-input" required>
                    </label>
                </div>
                <?php endif; ?>
                <div class="dbp-form-row">
                    <label><span class="dbp-form-label "><?php _e('Index choice', 'db_press'); ?></span>
                    <?php echo dbp_fn::html_select(['INDEX' => 'Optimize MySQL Search (Index)', 'UNIQUE' => 'Unique values'], true, 'name="index[type]"', $indexes->choice); ?>
                    </label>
                </div>
                <div class="dbp-form-row">
                    <label><span class="dbp-form-label "><?php _e('Columns', 'db_press'); ?></span>
                    <div class="button" onclick="clone_li_master()"><?php _e('Add new', 'db_press'); ?></div>
                    </label>
                    <ul style="margin-left: 8rem;" class="js-drag-index-column">
                        <li class="js-clore-master">
                        <span class="js-dragable-handle"><span class="dashicons dashicons-sort"></span></span>
                            <?php echo dbp_fn::html_select($table_fields,false, 'name="index[columns][]"') ; ?> <span class="dbp-warning-link" onclick="dbp_index_remove_cols(this)">DELETE</span>
                        </li>  
                        <?php foreach ( $indexes->columns as $column) : ?>
                        <li class="js-dragable-li">
                        <span class="js-dragable-handle"><span class="dashicons dashicons-sort"></span></span>
                            <?php echo dbp_fn::html_select($table_fields,false, 'name="index[columns][]"', $column) ; ?> <span class="dbp-warning-link" onclick="dbp_index_remove_cols(this)">DELETE</span></li>  
                        <?php endforeach ;?>
                    </ul>
                    <input type="submit" class="dbp-submit" value="<?php _e('Save','db_press'); ?>" >
                </div>
            </form>
        </div>
    </div>
</div>
<?php if (count($indexes->columns) == 0) : ?>
    <script>
        jQuery(document).ready(function ($) {
            setTimeout(() =>{clone_li_master()},888);
        });
    </script>
<?php endif; ?>