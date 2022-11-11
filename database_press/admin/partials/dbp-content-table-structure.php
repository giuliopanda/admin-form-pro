<?php 
/**
* La struttura di una tabella. 
* @var String $table
*/
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
?>
<div class="dbp-content-header">
    <?php require dirname(__FILE__).'/dbp-partial-tabs.php'; ?>
</div>
<div class="dbp-content-table js-id-dbp-content" >
    <div class="dbp-content-margin">
        <h2 class="dbp-h2-inline dbp-content-margin"><?php printf(__('Table %s','db_press'), $table); ?></h2>
        <?php if ($table != "") : ?>
            <ul class="dbp-submenu" style="display: inline-block;">
                <?php if (isset($_REQUEST['action']) && $_REQUEST['action'] == "show_create_structure" || $table == "") : ?>
                    <li><a  href="<?php echo esc_attr(\admin_url("admin.php?page=database_press&section=table-structure&table=".$table)); ?>"><?php _e('Go Back','db_press'); ?></a></li>
                <?php elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "structure-edit" ) : ?>
                    <li><a  href="<?php echo esc_attr(\admin_url("admin.php?page=database_press&section=table-structure&table=".$table)); ?>"><?php _e('Go Back','db_press'); ?></a></li>
                    <li><a href="<?php echo esc_attr(\admin_url("admin.php?page=database_press&section=table-structure&action=structure-edit&table=".$table)); ?>"><?php _e('Reload','db_press'); ?></a></li>
                <?php else: ?>
                    <?php if (count($table_model->items) < $max_row_allowed && !$table_model->error_primary &&  $table_options['status'] == 'DRAFT' ) : ?>
                        <li><a class="dbp-submit" href="<?php echo esc_attr(admin_url("admin.php?page=database_press&section=table-structure&action=structure-edit&table=".$table)); ?>"><?php _e('Edit table structure','db_press'); ?></a></li>
                    <?php elseif ( $table_options['status'] != 'DRAFT' ) : ?>
                        <li><span class="dbp-submit dbp-btn-disabled" onclick="alert('<?php echo esc_attr(__('You must first set the table in draft mode', 'db_press')); ?>');" ><?php _e('Edit table structure','db_press'); ?></span></li>
                    <?php elseif ( count($table_model->items) >= $max_row_allowed ) : ?>
                        <li><span class="dbp-submit dbp-btn-disabled" onclick="alert('<?php echo esc_attr(__('The max_input_vars value is sufficient to edit this form', 'db_press')); ?>');" ><?php _e('Edit table structure','db_press'); ?></span></li>
                    <?php elseif($table_model->error_primary ) : ?>
                        <li><span class="dbp-submit dbp-btn-disabled" onclick="alert('<?php echo esc_attr(__('Set the primary key through queries before changing the structure', 'db_press')); ?>');" ><?php _e('Edit table structure','db_press'); ?></span></li>
                    <?php endif; ?>
                    <li><a href="<?php echo esc_attr(admin_url("admin.php?page=database_press&section=table-structure&action=show_create_structure&table=".$table)); ?>"><?php _e('Show sql','db_press'); ?></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
        
       

        <?php if ($msg != "") : ?>
            <div class="dbp-alert-info"><?php echo ($msg); ?></div>
        <?php endif; ?>
        <?php if (@$msg_error != ""): ?>
            <div class="dbp-alert-sql-error"><?php echo ($msg_error); ?></div>
        <?php endif ; ?>

        <?php if ($this->last_error != "" && dbp_fn::req('action', '', 'string') != 'create-table-csv-data') : ?>
            <div class="dbp-alert-sql-error"><?php echo ($this->last_error); ?></div>
        <?php endif; ?>
        <?php if ($action == 'show_create_structure') : ?>
            <div class="dbp-result-query js-dbp-mysql-query-text">
                <?php 
                $temp_sql =str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;", nl2br(htmlentities($sql_sctructure)));
                echo $temp_sql;
                ?>
            </div>
        <?php elseif ($action == 'structure-edit') : ?>
            <h3 id="dbp_result_alert_table_title" style="display:none">
                <?php _e('Executing...', 'db_press'); ?>
            </h3>
            <div id="dbp_result_alert_table"></div>
            <div id="dbp_link_return"></div>
            <div  class="js-hide-after-save" id="dbp_content_structure_table">
                <form method="POST" id="dbp_create_table">
                    <input type="hidden" name="page" value="database_press" />
                    <?php // La sezione deve sempre esistere perché serve a caricare il loader giusto ?>
                    <input type="hidden" name="section" value="table-structure" />
                    <input type="hidden" name="table" value="<?php echo esc_attr($table); ?>" />
                    <div id="edit_options">
                        <hr>
                        <?php if ($table != "") : ?>
                        <div class="dbp-form-row">
                            <label><span class="dbp-form-label"><?php  _e('Status', 'db_press'); ?></span><?php dbp_fn::html_select(['DRAFT'=>'Draft','PUBLISH'=>'Publish','CLOSE'=>'Close'], true, 'name="options[status]"', $table_options['status']); ?></label>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="options[status]" value="DRAFT">
                        <?php endif; ?>
                        <?php if ($table_options['status'] == "DRAFT") : ?>
                            <div class="dbp-form-row">
                                <label class="dbp-form-label-top"><?php _e('Description', 'db_press'); ?></label><textarea class="dbp-form-textarea" name="options[description]"><?php echo (esc_textarea(wp_unslash(@$table_options['description']))); ?></textarea>
                            </div>
                        <?php else: ?>
                            <?php echo _e('Description', 'db_press'); ?>: <?php echo esc_attr(@$table_options['description']); ?>
                            <textarea style="display:none" name="options[description]"><?php echo esc_textarea(wp_unslash(@$table_options['description'])); ?></textarea>
                            </div>
                        <?php endif; ?>
                        <hr>
                    </div>
                    <?php if ($table_options['status'] == "DRAFT") : ?>
                        <?php require dirname(__FILE__).'/dbp-partial-alter-table.php'; ?>
                        <div class="dbp-box-create-table-box">
                            <?php if ($table != "") : ?>
                            <div id="dbp_content_button_create_form" style="display:none">
                                <div onclick="dbp_submit_test_edit_structure()" class="dbp-submit"><?php _e('Analysis of changes', 'db_press'); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else : // table_options[satus] == DRAFT ?>
                        <div class="dbp-alert-sql-error js-hide-after-save">
                            <?php if ($table_options['status'] == 'CLOSE') : ?>
                                <?php _e('A Close table cannot be modified! You must first change the status to DRAFT.', 'db_press'); ?>
                                <?php else : ?>
                                    <?php _e('A table in production cannot be modified! You must first change the status to DRAFT.', 'db_press'); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div onclick="dbp_submit_edit_structure('<?php echo ($table != '') ? 'dbp_update_table_structure' : 'dbp_create_table_structure'; ?>')" class="dbp-submit js-hide-after-save"><?php _e('Save', 'db_press'); ?></div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <h3 id="dbp_result_alert_table_title_test" class="dbp-structure-result-alert-table-title-test js-hide-after-save">
                <?php _e('Analysis in progress', 'db_press'); ?>
            </h3>
            <div id="dbp_result_alert_table_test"></div>
            <hr>
            
            <div id="dbp_execute_query_command" style="display:none" >
                <?php if ($table_options['status'] == "DRAFT") : ?>
                    <div onclick="dbp_submit_edit_structure('<?php echo ($table != '') ? 'dbp_update_table_structure' : 'dbp_create_table_structure'; ?>')" class="dbp-submit js-hide-after-save"><?php _e(( ($table != '') ?'Alter table' : 'Create table'), 'db_press'); ?></div>
                <?php endif; ?>    
            </div>
            <div id="dbp_msg_fix_error_before" style="display:none"class="dbp-alert-sql-error" ><?php _e('One or more queries have failed. Correct them before editing the table.', 'db_press'); ?></div>
        <?php elseif (!isset($table)) : ?>
            ?><div class="dbp-alert-sql-error"><?php _e('No table was selected','database_press'); ?></div>
        <?php else : ?>
            <hr>
            <div id="dbp_show_metadata" class="dbp-css-mb-1">
                <?php if (isset($table_options['description']) && $table_options['description'] != "") : ?>
                    <div><?php  echo esc_textarea(wp_unslash($table_options['description'])); ?> </div>
                <?php endif; ?>
                <?php if (isset($table_options['status']) && $table_options['status'] != "") : ?>
                    <div><?php _e('Status', 'db_press'); ?>: <?php  echo esc_attr($table_options['status']); 
                    dbp_fn::echo_html_icon_help('database_press-table-structure','status'); ?> </div>
                <?php endif; ?>
             
            </div>
            <div id="dbp_edit_metadata" style="display:none">
                <form method="POST" id="dbp_edit_metadata_form"  action="<?php echo admin_url("admin.php?page=database_press&section=table-structure"); ?>">
                    <input type="hidden" name="table" value="<?php echo esc_attr($table); ?>" />
                    <input type="hidden" name="action" value="save_metadata" />
                    <div class="dbp-form-row">
                        <label><span class="dbp-form-label"><?php  _e('Status', 'db_press'); ?></span><?php dbp_fn::html_select(['DRAFT'=>'Draft','PUBLISH'=>'Publish','CLOSE'=>'Close'], true, 'name="options[status]"', $table_options['status']); ?></label>
                    </div>
                    <div class="dbp-form-row">
                        <label class="dbp-form-label-top"><?php _e('Description', 'db_press'); ?></label><textarea class="dbp-form-textarea" name="options[description]"><?php echo (esc_textarea(wp_unslash(@$table_options['description']))); ?></textarea>
                    </div>
                    <div class="dbp-form-row">
                        <div onclick="dbp_submit_edit_metadata()" class="dbp-submit "><?php _e('Save', 'db_press'); ?></div>
                        <div onclick="dbp_cancel_edit_metadata()" class="button"><?php _e('Cancel', 'db_press'); ?></div>
                    </div>
                </form>
            </div>
            <div id="dbp_edit_metadata_btn">
                <?php if ($table_options['external_filter']) : ?>
        
                <?php else : ?>
                <div onclick="dbp_show_edit_metadata()" class="dbp-submit-style-link"><?php _e('Edit Status & Description', 'db_press'); ?></div>
                <?php endif ; ?>

            </div>
            <hr>
            <?php
            $html_table   = new Dbp_html_simple_table();
            $html_table->add_table_class('wp-list-table widefat striped dbp-table-view-list');
           // echo $html_table->template_render($table_model);
            require dirname(__FILE__).'/dbp-partial-structure-show-table.php';
            echo '<h3>'.__('Indexes','db_press').'</h3>';
            echo '<p>'.__('Indexes can be used to improve query performance. Instead, you can use unique keys to avoid duplicate fields within one or more columns.','db_press');
            dbp_fn::echo_html_icon_help('database_press-table-structure','indexes');
            echo '</p>';
            if (!$table_model->error_primary && $table_options['status'] == "DRAFT") {
                ?><a class="dbp-submit" href="<?php echo esc_attr(add_query_arg(['section'=>'table-structure','table'=>$table,'action'=>'edit-index','dbp_id'=>''], admin_url("admin.php?page=database_press"))); ?>"><?php _e('Add index','db_press'); ?></a><br ><br ><?php
            }
            if (is_countable($indexes) && count($indexes) > 0) {
                echo $html_table->render($indexes);
            }
        endif; ?>
    </div>
</div>