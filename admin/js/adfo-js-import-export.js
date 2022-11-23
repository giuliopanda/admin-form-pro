function adfo_download_csv() {
    // console.log ('dbp_bulk_actions');
     let action = jQuery('#dbp_bulk_action_selector_bottom').val();
     let mwith = jQuery('#dbp_bulk_on_selector_bottom').val();
     if (action == "delete" && mwith == 'sql') {
         let sql = jQuery('#sql_query_executed').val();
         dbp_open_sidebar_popup('bulk_delete');
         jQuery.ajax({
             type : "post",
             dataType : "json",
             url : ajaxurl,
             data : {'sql':sql,  'action': 'af_check_delete_from_sql' },
             success: function(response) { 
                 dbp_close_sidebar_loading();
                 if (response.error != "") {
                     jQuery('#dbp_dbp_content').append('<div class="dbp-alert-sql-error" style="margin-top:0; margin-right:.4rem">'+response.error+'</div>');
                 } else {
                     jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
                     jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><div id="dbp-bnt-edit-query" class="dbp-submit-warning" onclick="dbp_ajax_detete_from_query(0,0,\'\')">DELETE</div>  <div class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
                     let $form = jQuery('<div class="dbp-form-deletes-rows" id="dbp_form_deletes_rows"></div>');
                     let count_items = 0;
                     for (x in response.items) count_items++;
                     if (count_items > 1) {
                         $form.append('<div  class="dbp-alert-info">The query result records will be removed. Choose the tables from which to clear the data.</div>');
                         for (x in response.items) {
                             $form.append('<div class="dbp-dropdown-line-flex"><span style="margin-right:.5rem"><input name="remove_tables_query" type="checkbox" checked="checked" value="'+x+'"></span><div class="dbp-xmp">'+response.items[x]+'</div></div>');
                         }
                       
                     } else if (count_items == 1) {
                         $form.append('<div class="dbp-alert-info">Are you sure you want to delete all data extracted from the query?</div>');
                         for (x in response.items) {
                             $form.append('<div style="display:none"><input name="remove_tables_query" type="checkbox" checked="checked" value="'+x+'"></div>');
                         }
                     } else {
                         $form.append('<div class="dbp-alert-info">I can\'t find any records that can be removed</div>');
                     }
 
                     $form.append('<div id="box_result_delete_query" style="margin-top:1rem; padding-top:1rem; border-top:1px solid #CCC"></div>');
                     jQuery('#dbp_dbp_content').append($form);
                 }
                
                 
             }
         });
 
     }
     if (action == "delete" && mwith == 'checkboxes') {
         var table_ids = [];
         jQuery('#table_filter .js-dbp-table-checkbox:checked').each(function() {
             table_ids.push(dbp_tb_id[jQuery(this).val()]);
         });
         //console.log (" !!DELETE CHECKBOXES: " + table_ids);
         af_delete_confirm(table_ids);
     }
     if (action == "download" && mwith == 'checkboxes') {
         var table_ids = [];
         jQuery('#table_filter .js-dbp-table-checkbox:checked').each(function() {
             table_ids.push(dbp_tb_id[jQuery(this).val()]);
         });
         let sql = jQuery('#sql_query_executed').val();
         dbp_open_sidebar_popup('download');
         dbp_close_sidebar_loading();
         jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
         jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>Export Data</h3></div>');
         jQuery('#dbp_dbp_content').append('<div class="dbp-download-data"></div>');
         af_download_csv(sql, table_ids, 0 ,'');
     }
     if (action == "download" && mwith == 'sql') {
         let sql = jQuery('#sql_query_executed').val();
         dbp_open_sidebar_popup('download');
         dbp_close_sidebar_loading();
         jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
         jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>Export Data</h3></div>');
         jQuery('#dbp_dbp_content').append('<div class="dbp-download-data"></div>');
         af_download_csv(sql, false, 0 ,'');
     }
 }