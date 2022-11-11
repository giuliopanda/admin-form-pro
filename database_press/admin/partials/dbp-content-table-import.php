<?php
/**
* La grafica del tab import
 * https://deliciousbrains.com/using-javascript-file-api-to-avoid-file-upload-limits/
 * https://stackoverflow.com/questions/17666249/how-do-i-import-an-sql-file-using-the-command-line-in-mysql
 * https://stackoverflow.com/questions/11679275/mysqldump-via-php
 */
namespace DbPress;

if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
 ?>
<div class="dbp-content-header">
    <?php require dirname(__FILE__).'/dbp-partial-tabs.php'; ?>
</div>
<div class="dbp-content-table js-id-dbp-content" id="dbp_content_table" >
    <?php if (@$this->last_error != "" && dbp_fn::req('action', '', 'string') != 'create-table-csv-data') : ?>
        <div class="dbp-alert-sql-error"><?php echo $this->last_error; ?></div>
    <?php endif; ?>

    <?php if (@$this->msg != "" && dbp_fn::req('action', '', 'string') != 'create-table-csv-data') : ?>
        <div class="dbp-alert-info"><?php echo $this->msg; ?></div>
    <?php endif; ?>

    <?php if (in_array($action, ['import-csv-file', 'execute-csv-data', 'create-table-csv-data', 'insert-csv-data']) ) : 
        /**
         * @var $csv_filename;
		 * @var $csv_delimiter;
		 * @var $csv_items;
         */
       
        if (is_countable($csv_items)) {
            $fields_name = array_values(reset($csv_items));
            $select_fields_name = array_merge([""], $fields_name);
            dbp_fn::echo_pinacode_variables_script(['item'=>$fields_name]);
        }
        ?>
        <div id="first_block">
            <div class="dbp-alert-info">
                <?php _e('The csv file has been loaded, check if the data is correct.','db_press'); ?>
                <a href="<?php echo add_query_arg(['section'=>'table-import'],  admin_url("admin.php?page=database_press")); ?>"><?php _e('Upload a new file','db_press'); ?></a>
            </div>
            <div class="dbp-content-margin">
                <div class="dbp-import-params-csv">
                    <form method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-import"); ?>" enctype="multipart/form-data" >
                        <input type="hidden" name="page" value="database_press" />
                        <input type="hidden" name="section" value="table-import" />
                        <input type="hidden" name="table" value="<?php echo @$import_table; ?>" />
                        <input type="hidden" name="csv_name_of_file" value="<?php echo esc_attr($name_of_file); ?>" />
                        <input type="hidden" name="csv_temporaly_filename" value="<?php echo esc_attr($csv_filename); ?>" />
                        <input type="hidden" name="action" value="execute-csv-data" />
                        Delimiter <input type="text" name="csv_delimiter" value="<?php echo dbp_fn::convert_char_to_special($csv_delimiter); ?>" />
                        <?php if ((isset($allow_use_first_row) && $allow_use_first_row == true) || !isset($allow_use_first_row)) : ?>
                            <label><input type="checkbox" name="csv_first_row_as_headers" value="1" <?php echo ($csv_first_row_as_headers) ? ' checked="checked"' : '';?>> <?php _e('Use first row as Headers', 'db_press'); ?></label>
                        <?php else : ?>
                            <label><input type="checkbox"  disabled> <?php _e('Use first row as Headers', 'db_press'); ?></label>
                        <?php endif; ?>   
                        <input type="hidden" name="allow_use_first_row" value="<?php echo (@$allow_use_first_row) ? 1 : 0; ?>">
                        <input type="submit" value="<?php _e('Update Preview', 'db_press'); ?>" />
                        
                        <?php dbp_fn::echo_html_icon_help('database_press-table-import','delimiter'); ?>
                    </form>
                </div>
                <h4 class="dbp-subtitle"><?php _e('Preview', 'database-press'); ?></h4>
                <?php 
                $html_table   = new Dbp_html_simple_table();
                $html_table->add_table_class('dbp-table-preview-csv');
                ?>
            
                <div class="dbp-import-table-csv-preview">
                    <?php echo $html_table->render($csv_items, false, false); ?>
                </div>
            </div>

            <div class="dbp-choose-import-csv-action">
                <select id="dbp_import_select_action" onchange="dbp_toggle_action_import(this)">
                    <option value=""><?php _e('Choose action', 'db_press'); ?></option>
                    <option value="create_table" <?php echo (@$select_action == 'create_database') ? 'selected="selected"' : ''; ?>><?php _e('Create Table', 'db_press'); ?></option>
                    <option value="insert_records" <?php echo ($action == 'insert-csv-data' || @$select_action == 'insert_records') ? 'selected="selected"' : ''; ?>><?php _e('Insert/Update Records', 'db_press'); ?></option>
                </select>
                <?php dbp_fn::echo_html_icon_help('database_press-table-import','choose_action'); ?>
            </div>
            <?php if (dbp_fn::req('action', '', 'string') == 'create-table-csv-data') : ?>
                <?php if ($this->last_error != "" ) : ?>
                    <div class="dbp-alert-sql-error"><?php echo $this->last_error; ?></div>
                <?php endif; ?>
                <?php if (isset( $this->msg) &&  $this->msg != "") : ?>
                    <div class="dbp-alert-info">
                        <?php echo $this->msg; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($sql) && $sql != "") : ?>
                    <div class="dbp-result-query js-dbp-mysql-query-text">
                        <?php echo $sql; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="js-dbp-import-content-toggle" id="dbp_content_create_table" style="display:none">
                <form method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-import"); ?>" id="dbp_create_table">
                    <input type="hidden" name="page" value="database_press" />
                    <input type="hidden" name="section" value="table-import" />
                    <input type="hidden" name="table" value="<?php echo @$import_table; ?>" />
                    <input type="hidden" name="csv_temporaly_filename" value="<?php echo esc_attr($csv_filename); ?>" />
                    <input type="hidden" name="action" value="create-table-csv-data" />
                    <input type="hidden" name="csv_delimiter" value="<?php echo dbp_fn::convert_char_to_special($csv_delimiter); ?>" />
                    <input type="hidden" name="csv_first_row_as_headers" value="<?php echo ($csv_first_row_as_headers) ? '1' : '0';?>">

                    <div id="dbp_create_table" class="dbp-import-content-create-table" style="<?php echo (dbp_fn::req('action', '', 'string') == 'create-table-csv-data') ? '' : '' ; ?>">
                        <div class="dbp-import-table-name">
                            <label><?php _e('Table name', 'database-press'); ?></label>
                            <label id="dbp_wp_prefix" class="dbp-wp-prefix"><?php echo dbp_fn::get_prefix(); ?></label><input type="text" name="csv_name_of_file" value="<?php echo esc_attr($name_of_file); ?>">
                            <label><input type="checkbox" name="use_prefix" value="1" checked="checked" onchange="dbp_use_prefix(this, 'dbp_wp_prefix')"><?php _e('Use wp prefix', 'database-press'); ?> </label>
                        </div>
                        <table class="wp-list-table widefat striped dbp-table-view-list js-dragable-table">
                            <thead>
                                <th><?php _e('Order','db_press'); ?></th>
                                <th><?php _e('Table name','db_press'); ?></th>
                                <th><?php _e('Preset','db_press'); ?></th>
                                <th><?php _e('Import from CSV column','db_press'); ?></th>
                                <th><?php _e('Action','db_press'); ?></th>
                            
                            </thead>
                            <?php  $row = 1; ?>
                            <tr class="js-clore-master">
                                <td class="js-dragable-handle"><span class="dashicons dashicons-sort"></span></td>
                                <td><input type="text" name="form_create[field_name][]" value=""></td>
                                <td>
                                    <?php echo dbp_fn::html_select(['varchar'=>'String (1 line)', 'text'=>'Text (Multiline)','int_signed'=>'Number', 'decimal'=>'Decimal (123.12)', 'date'=>'Date', 'datetime'=>'Date Time'], true, 'class="js-field-preselect" name="form_create[field_type][]"', 'varchar'); ?>  
                                </td>
                                <td>
                                    <?php echo dbp_fn::html_select($select_fields_name, false, 'name="form_create[csv_name][]" class="js-create-table-type"', 'VARCHAR'); ?>
                                </td>

                                <td>
                                    <div class="button" onClick="dbp_import_csv_create_table_delete_row(this);"><?php _e('Delete Row' , 'db_press'); ?></div>
                                </td>
                            </tr>
                            <?php
                            if (isset($csv_structure) && is_array($csv_structure)) {
                                foreach ($csv_structure as $cs) {
                                    ?>
                                    <tr class="js-dragable-tr">
                                        <td class="js-dragable-handle"><span class="dashicons dashicons-sort"></span></td>
                                        <td><input type="text" name="form_create[field_name][]" value="<?php echo dbp_fn::convert_to_mysql_column_name($cs->field_name); ?>"></td>
                                        <td>
                                            <?php 
                                            if ($cs->preset == "pri") {
                                                ?>
                                                <input type="hidden" name="form_create[field_type][]" value="pri">
                                                PRIMARY KEY
                                                <?php
                                            } else {
                                                echo dbp_fn::html_select(['varchar'=>'String (1 line)', 'text'=>'Text (Multiline)','int'=>'Number', 'decimal'=>'decimal (123.12)', 'date'=>'Date', 'datetime'=>'Date Time'], true, 'class="js-field-preselect" name="form_create[field_type][]"',  @$cs->preset);
                                                }
                                            ?>  
                                        </td>
                                        <td>
                                            <?php echo dbp_fn::html_select($select_fields_name, false, 'name="form_create[csv_name][]" class="js-create-table-type"', @$cs->name); ?>
                                        </td>
                                        <td>
                                            <?php if ($cs->preset != "pri") : ?>
                                            <div class="button" onClick="dbp_import_csv_create_table_delete_row(this);"><?php _e('Delete Row' , 'db_press'); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                    $row++;
                                    if ($row > $max_row_allowed && $max_row_allowed > 0)   break;
                                }
                            }
                            ?>
                            <tr>
                                <td colspan="5">
                                    <div onclick="dbp_create_table_add_row(this, '<?php echo @$max_row_allowed; ?>')" class="button"><?php _e('Add row', 'db_press'); ?></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="dbp-box-create-table-box">
                        <div id="dbp_content_button_create_form" >
                            <input type="submit" class="dbp-submit" value="<?php _e('Create Table', 'db_press'); ?>" /> 
                            <?php dbp_fn::echo_html_icon_help('database_press-table-import','create_table'); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php /** IMPORTO I DATI */ ?>
        <div class="js-dbp-import-content-toggle" id="dbp_content_insert_records" style="display:none">
            <form id="dbp_import_csv_data_config" method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-import"); ?>" >
                <input type="hidden" name="page" value="database_press" />
                <input type="hidden" name="section" value="table-import" />
                <input type="hidden" id="csv_import_original_table" name="table" value="<?php echo @$import_table; ?>" />
                <input type="hidden" id="csv_temporaly_filename" name="csv_temporaly_filename" value="<?php echo esc_attr($csv_filename); ?>" />
                <input type="hidden" id="csv_delimiter" name="csv_delimiter" value="<?php echo dbp_fn::convert_char_to_special($csv_delimiter); ?>" />
                <input type="hidden" id="csv_first_row_as_headers" name="csv_first_row_as_headers" value="<?php echo ($csv_first_row_as_headers) ? '1' : '0';?>">
                <?php /** Qui vengono disegnate le tabelle in js con la configurazione per importare i dati di un csv */ ?>
                <div class="dbp-msg-unique-table-id-explane"><?php _e('Select one or more tables in which to insert data.<br>On each field choose which column of the csv to insert.<br>If you want to insert data on multiple tables, for example post and postmeta: First select the post table and associate the fields of your csv. At the bottom of the table you will find a string. This refers to the id of the records that will be created or updated.<br>Add the postmeta table and on the post_id field select the code in square brackets.<br>','db_press'); ?></div>

                <div id="content_all_insert_fields_block" class="dbp-import-content-all-block">
                   
                </div>
                <div class="dbp-import-content-clone-block">
                    <div class="js-insert-fields-content-clone dbp-insert-fields-content">
                        <div class="dbp-import-params-csv">
                            <?php dbp_fn::html_select(array_merge([''=>__('Select table', 'db_press')],  $this->table_list['tables']), true, 'class="js-select-tables-import jsonchange-select-tables-import-clone"', @$current_table); ?>
                            <?php if (@$select_action == "insert_records" && isset($csv_structure_table_created) && is_countable($csv_structure_table_created) ) : ?>
                                <script>
                                    var csv_structure_table_created = <?php echo json_encode($csv_structure_table_created); ?>;
                                    jQuery(document).ready(function () {
                                        jQuery('.js-select-tables-import').first().change();
                                    });
                                </script>
                            <?php endif ; ?> 
                        <!--div class="js-immport-choose-table-remove-btn button" style="display:none"><?php _e('Delete','db_press'); ?></div-->
                        </div>
                        <div class="js-content-table-fields"></div>
                        <div class="js-msg-yes-pri-key dbp-msg-yes-no-pri-key" style="display:none">* <?php _e('Records with the same primary key will be updated.','db_press'); ?></div>
                        <div class="js-msg-no-pri-key dbp-msg-yes-no-pri-key"  style="display:none">* <?php _e('All records will be inserted as new.','db_press'); ?></div>
                        <div class="dbp-msg-unique-table-id" style="display:none" >* <?php _e('If you want to insert data into multiple tables, you can reference the newly created or updated record ID using the following code:','db_press'); ?> <span class="js-unique-code"></span></div>
                    </div>
                </div>
                
            </form>
            <div class="dbp-import-content-all-block">
                <div id="dbp_import_csv_btns">
                    <div class="button" onclick="dbp_csv_test_import()"><?php _e('Test the Import', 'db_press'); ?></div>
                    &nbsp; <div class="dbp-submit" onclick="dbp_csv_exec_import(0,0,0,0)"><?php _e("I'm feeling lucky! Import the data", 'db_press'); ?></div>
                    <?php dbp_fn::echo_html_icon_help('database_press-table-import','insert_record'); ?>
                </div>
                <div id="dbp_result_import_box" class="dbp-insert-fields-content" style="display:none">
                    <div id="dbp_import_csv_alert" ></div>
                    <div id="dbp_result_test_import_csv"></div>

                    <table id="dbp_import_csv_exec_import" class="wp-list-table widefat striped dbp-table-view-list " style="display:none">
                        <tbody>
                            <tr>
                                <td><?php _e('Total row','database_press'); ?></td>
                                <td id="dbp_result_import_csv_total_row"></td>
                            </tr> 
                            <tr>
                                <td><?php _e('Erorrs','database_press'); ?></td>
                                <td id="dbp_result_import_csv_errors"></td>
                            </tr>   
                            <tr>
                                <td><?php _e('Insert','database_press'); ?></td>
                                <td id="dbp_result_import_csv_insert"></td>
                            </tr>   
                            <tr>
                                <td><?php _e('Update','database_press'); ?></td>
                                <td id="dbp_result_import_csv_update"></td>
                            </tr>   
                        </tbody>
                    </table>
                    <a  class="btn-csv-download" id="btn_csv_download" href="<?php echo add_query_arg(['section'=>'table-import', 'action'=>'dbp_download_csv_report','filename'=>$csv_filename],  admin_url("admin-post.php")); ?>" style="display:none;">Download csv with report</a>
                </div>
            </div>
        </div>

    <?php else: // in_array($action, ['import-csv-file', 'execute-csv-data', 'create-table-csv-data', 'insert-csv-data']

        /*
        * DEFAULT form di caricamento file
        * TODO upload di file di grandi dimensioni https://deliciousbrains.com/using-javascript-file-api-to-avoid-file-upload-limits/
        * Forse è meglio poter caricare zip e basta!
         */
        if (ini_get('upload_max_filesize') > 0 && ini_get('post_max_size') > 0) {
            $memory_limit = min(ini_get('upload_max_filesize'), ini_get('post_max_size'));
        } else if (ini_get('upload_max_filesize') > 0) {
            $memory_limit = ini_get('upload_max_filesize');
        } else {
            $memory_limit = "2Mb";
        }
        if (intval($memory_limit) != "") {
            $memory_limit = intval($memory_limit)*1024*1024;
        } else {
            $memory_limit = 2*1024*1024;
        }
        ?>
        <div class="dbp-content-margin">
            <h2 class="dbp-h2"><?php _e('File to import', 'db_press'); ?></h2>
            <p class="dbp-p"><?php 
            _e('Upload <b>.SQL</b> or <b>.CSV</b> file', 'db_press'); 
            dbp_fn::echo_html_icon_help('database_press-table-import','sql'); 
            ?></p>
            <form method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-import"); ?>" enctype="multipart/form-data" >
                <input type="hidden" name="page" value="database_press" />
                <input type="hidden" name="section" value="table-import" />
                <input type="hidden" name="action" value="import-sql-big-file" />
                <input type="hidden" name="table" value="<?php echo @$import_table; ?>" />
                <div id="dbpUploadProgress"></div>
                <input id="dbi_import_file" name="sql_file" type="file" accept=".csv,.sql" />
                <input id="dbi-file-upload-submit" type="button" class="dbp-submit" value="<?php _e('Go ahead', 'db_press'); ?>" />
            </form>
            <hr>
            <div id="dbpNextStep"></div> 
            <?php 
            /* 
            // VECCHIO METODO
            <h2 class="dbp-h2"><?php _e('File to import', 'db_press'); ?></h2>
            <p class="dbp-p"><?php 
            _e('Upload an <b>sql</b> file with the queries to run', 'db_press'); 
            dbp_fn::echo_html_icon_help('database_press-table-import','sql'); 
            ?></p>
            <form method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-import"); ?>" enctype="multipart/form-data" >
                <input type="hidden" name="page" value="database_press" />
                <input type="hidden" name="section" value="table-import" />
                <input type="hidden" name="action" value="import-sql-file" />
                <input type="hidden" name="table" value="<?php echo @$import_table; ?>" />
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo  esc_attr($memory_limit); ?>" /> 
                <input id="fileupload" name="sql_file" type="file"  />
                <input type="submit" class="dbp-submit" value="<?php _e('Execute import', 'db_press'); ?>" />
            </form>
            <hr>
            <p class="dbp-p"><?php 
            _e('Upload a <b>csv</b> file and go ahead to configure the import', 'db_press'); 
            dbp_fn::echo_html_icon_help('database_press-table-import','csv');
            ?></p>
            <form method="POST" action="<?php echo admin_url("admin.php?page=database_press&section=table-import"); ?>" enctype="multipart/form-data" >
                <input type="hidden" name="action" value="import-csv-file">
                <input type="hidden" name="page" value="database_press" />
                <input type="hidden" name="section" value="table-import" />
                <input type="hidden" name="table" value="<?php echo @$import_table; ?>" />
                <input id="fileupload" name="sql_file" type="file" />
                <input type="submit" class="dbp-submit" value="<?php _e('Go ahead', 'db_press'); ?>" />
            </form>
            <?php 
            $max = dbp_fn::get_max_upload_file();
            if ($max > 0) {
                ?>  <hr> <br><?php 
                printf(__("max upload files <b>%s</b>", 'db_press'), $max); 
             
            }
            */
             ?>
        </div>
    <?php endif; ?>

</div>