
function adfo_download_raw_csv(dbp_id) {
    // console.log ('dbp_bulk_actions');
    let action = jQuery('#dbp_bulk_action_selector_bottom').val();
    let mwith = jQuery('#dbp_bulk_on_selector_bottom').val();

    let sql = jQuery('#sql_query_executed').val();
    dbp_open_sidebar_popup('download');
    dbp_close_sidebar_loading();
    jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>Export Data</h3></div>');
    jQuery('#dbp_dbp_content').append('<div class="dbp-download-data"></div>');
    adfo_ajax_download_raw_csv(dbp_id, 0 ,'');
 }


 /**
 * prepara il csv da scaricare 
 */
function adfo_ajax_download_raw_csv(dbp_id, limit_start, csv_filename) {
    data = {'sql':'', 'dbp_id': dbp_id, 'ids':false, 'limit_start':limit_start, 'csv_filename':csv_filename, 'action':'af_download_csv', 'section':'table-browse', 'data_type':'raw'};
    console.log (data);
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        cache: false,
        data : data,
        success: function(response) {
            $content =   jQuery('#dbp_dbp_content .dbp-download-data').first();
            if (response.error == "") {
                if (response.next_limit_start < response.count && response.next_limit_start > 0) {
                    let perc = Math.round((response.next_limit_start/response.count) * 100);
                    $content.empty().append("<p>Export: "+perc+"% "+response.msg+"</p>");
                    adfo_ajax_download_raw_csv(dbp_id, response.next_limit_start, response.filename); 
                } else {
                    $content.empty().append("<p>Export: " + response.count + " items</p>");
                    let $a = jQuery('<a href="'+response.link+'" class="dbp-submit" onclick="setTimeout(function(){ dbp_close_sidebar_popup();},200); return true;">Click here to download</a>');
                    $content.append($a);
                }
            } else {
                jQuery('#dbp_dbp_content').append('<div class="dbp-alert-sql-error" style="margin-top:0; margin-right:.4rem">'+response.error+'</div>');
            }
        }
    });
}



function adfo_download_csv(dbp_id) {
    // console.log ('dbp_bulk_actions');
    let action = jQuery('#dbp_bulk_action_selector_bottom').val();
    let mwith = jQuery('#dbp_bulk_on_selector_bottom').val();

    let sql = jQuery('#sql_query_executed').val();
    dbp_open_sidebar_popup('download');
    dbp_close_sidebar_loading();
    jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>Export Data</h3></div>');
    jQuery('#dbp_dbp_content').append('<div class="dbp-download-data"></div>');
    adfo_ajax_download_csv(dbp_id, 0 ,'');
 }


 /**
 * prepara il csv da scaricare 
 */
function adfo_ajax_download_csv(dbp_id, limit_start, csv_filename) {
    data = {'sql':'', 'dbp_id': dbp_id, 'ids':false, 'limit_start':limit_start, 'csv_filename':csv_filename, 'action':'af_download_csv', 'section':'table-browse'};
    console.log (data);
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        cache: false,
        data : data,
        success: function(response) {
            $content =   jQuery('#dbp_dbp_content .dbp-download-data').first();
            if (response.error == "") {
                if (response.next_limit_start < response.count && response.next_limit_start > 0) {
                    let perc = Math.round((response.next_limit_start/response.count) * 100);
                    $content.empty().append("<p>Export: "+perc+"% "+response.msg+"</p>");
                    adfo_ajax_download_csv(dbp_id, response.next_limit_start, response.filename); 
                } else {
                    $content.empty().append("<p>Export: " + response.count + " items</p>");
                    let $a = jQuery('<a href="'+response.link+'" class="dbp-submit" onclick="setTimeout(function(){ dbp_close_sidebar_popup();},200); return true;">Click here to download</a>');
                    $content.append($a);
                }
            } else {
                jQuery('#dbp_dbp_content').append('<div class="dbp-alert-sql-error" style="margin-top:0; margin-right:.4rem">'+response.error+'</div>');
            }
        }
    });
}


/**
 * IMPORT BIG DATA
 */


    var dbp_reader = {};
    var dbp_file_upl = {};
    var slice_size = 1900 * 1024;

    function dbp_start_upload( dbp_id ) {
        //event.preventDefault();
        if ( jQuery('#dbi_import_file').prop('disabled')) return;
        dbp_reader = new FileReader();
        dbp_file_upl = document.getElementById('dbi_import_file').files[0];
        if (dbp_file_upl == undefined) return;
        if (dbp_file_upl.size > 0 ) {
            console.log ("file.size");
            jQuery('#dbi-file-upload-submit').addClass('dbp-btn-disabled');
            jQuery('#dbi_import_file').prop('disabled', true);
            upload_file( 0, '', dbp_id );
        }
        return false;
    }


    
    function upload_file( start, filename, dbp_id ) {
        var next_slice = start + slice_size + 1;
        var blob = dbp_file_upl.slice( start, next_slice );
        
        dbp_reader.onloadend = function( event ) {
            if ( event.target.readyState !== FileReader.DONE ) {
                return;
            }
            jQuery.ajax( {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                cache: false,
                data: {
                    action: 'dbi_upload_file',
                    section: 'table-import',
                    file_data: event.target.result,
                    file: dbp_file_upl.name,
                    file_name: filename,
                    file_type: dbp_file_upl.type,
                    nonce: dbi_vars.upload_file_nonce
                },
                error: function( jqXHR, textStatus, errorThrown ) {
                    console.log( jqXHR, textStatus, errorThrown );
                },
                success: function( data ) {
                    var size_done = start + slice_size;
                    var percent_done = Math.floor( ( size_done / dbp_file_upl.size ) * 100 );

                    if ( next_slice < dbp_file_upl.size ) {
                        // Update upload progress
                        jQuery( '#dbpUploadProgress' ).html( 'Uploading File - '+ percent_done+'%');
                        // More to upload, call function recursively
                        upload_file( next_slice, data.file_name );
                    } else {
                        jQuery('#dbi-file-upload-submit').addClass('dbp-btn-disabled');
                        // Update upload progress
                        jQuery( '#dbpUploadProgress' ).html( 'Upload Complete' );
                        jQuery('#dbi-file-upload-submit').removeClass('dbp-btn-disabled');
                        jQuery('#dbi_import_file').prop('disabled', false);
                        jQuery('#container_step2').empty();
                        adfo_check_data(data.file_name, data.org_name, dbp_id, 0);
                       
                    }
                }
            } );
        };

        dbp_reader.readAsDataURL( blob );
    }




/**
 * Step 2 i dati sono stati caricati ora li provo su una tabella temporanea e mostro il risultato
 */
function adfo_check_data(filename, orgname, dbp_id, limit_start) {
    console.log ("dbp_id: "+dbp_id)
    jQuery.ajax( {
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {
            action: 'adfo_check_import_data',
            section: 'import-export',
            filename: filename,
            orgname: orgname,
            dbp_id: dbp_id,
            limit_start:limit_start
        },
        error: function( jqXHR, textStatus, errorThrown ) {
            jQuery('#container_step2').append('<div class="dbp-alert-warning">ERROR: '+ textStatus+': '+ errorThrown +'</div>');
        },
        success: function( data ) {
            if (data.hasOwnProperty('table_array')) {
                $table =  gp_draw_table(data.table_array, data.table_data);
                jQuery('#container_step2').append($table);
              
                if (data.import_table) {
                    if (data.limit_start+data.limit < data.total_row) {
                        // check in paginazione
                        adfo_check_data(filename, orgname, dbp_id, data.limit_start+data.limit);    
                        return;       
                    }
                    $msg = jQuery('<div class="dbp-alert-info">The file looks correct, click on import to load the data</div>');
                    $btn_step3 = jQuery('<div class="dbp-submit js-btn-exec-import" style="margin-top:1rem">Import</div>');
                    $btn_step3_bis = $btn_step3.clone();
                    jQuery('#container_step2').prepend($msg).prepend($btn_step3_bis).append($msg.clone()).append($btn_step3);
                    jQuery('#container_step2').data('filename', filename);
                    jQuery('#container_step2').data('dbp_id', dbp_id);
                    $btn_step3_bis.click(function() {btn_start_import(this);})
                    $btn_step3.click(function() {btn_start_import(this);});
                } else {
                    $msg = jQuery('<div class="dbp-alert-warning">Correct the reported errors and try again to upload the file.</div>');
                    jQuery('#container_step2').prepend($msg).append($msg.clone());
                }
            }
            console.log(data);
        }
    });
}


function btn_start_import(el) {
    jQuery(el).remove();
    filename = jQuery('#container_step2').data('filename');
    dbp_id =   jQuery('#container_step2').data('dbp_id');
    jQuery('#container_step2').empty().append('<div id="msg_importing" class="dbp-alert-warning" >I\'m importing the file, don\'t close the window.</div><br><div id="container_step3"></div>');
    adfo_import_insert = 0;
    adfo_import_update = 0;
    adfo_import_error = 0;
    adfo_import_csv(filename, dbp_id, 0);
}

var adfo_import_insert = 0;
var adfo_import_update = 0;
var adfo_import_error = 0;

function adfo_import_csv(filename, dbp_id, limit_start) {
    console.log ("dbp_id: "+dbp_id)
    if ( jQuery('.js-btn-exec-import').prop('disabled')) return;
    jQuery('.js-btn-exec-import').prop('disabled', true);
    jQuery.ajax( {
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {
            action: 'adfo_list_import_data',
            section: 'import-export',
            filename: filename,
            limit_start: limit_start,
            dbp_id: dbp_id
        },
        error: function( jqXHR, textStatus, errorThrown ) {
            console.log( jqXHR, textStatus, errorThrown );
        },
        success: function( data ) {
            if (data.error != "") {
                jQuery('#msg_importing').empty().html('Import finished').removeClass('dbp-alert-info').addClass('dbp-alert-warning');
                return;
            }
           
            for (i in data.result)  {
                let row = data.result[i];
                if (row.execute) {
                    console.log ("EXECUTE OK");
                    temp_import = '';
                    for (d in row.details) {
                        let detail = row.details[d];
                        if (detail.result && detail.action == 'update') {
                            temp_import = 'update';
                        } else if (detail.result && detail.action == 'insert') {
                            if (temp_import == '') {
                                temp_import = 'insert';
                            }
                        } else if (detail.error != '') {
                            temp_import = 'error';
                            break;
                        }
                    }
                    if (temp_import == 'insert') {
                        adfo_import_insert++;
                    } else if (temp_import == "update") {
                        adfo_import_update++;
                    } else if (temp_import == "error") {
                        adfo_import_error++;
                    }
                } else {
                    console.log ("EXECUTE ERROR");
                    adfo_import_error++;
                }
                $table =  jQuery('<table class="dbp-table-import-check" id="adfo_import_data_result"></table>');
                $table.append('<tr><td>Insert</td><td>'+adfo_import_insert+'</td></tr>');
                $table.append('<tr><td>Update</td><td>'+adfo_import_update+'</td></tr>');
                $table.append('<tr><td>Errors</td><td>'+adfo_import_error+'</td></tr>');
                jQuery('#container_step3').empty().append($table);
                jQuery('#msg_importing').empty().html('Import finished').removeClass('dbp-alert-warning').addClass('dbp-alert-info');
            }
            if (data.limit_start+data.limit < data.total_row) {
            // check in paginazione
                $table.append('<tr><td>Executed</td><td>'+(data.limit_start+data.limit)+'</td></tr>');
                $table.append('<tr><td>Total Row</td><td>'+(data.total_row)+'</td></tr>');  
                        
                adfo_import_csv(filename, dbp_id,  data.limit_start+data.limit);                           
            } else {
                $table.append('<tr><td>Total Row</td><td>'+(data.total_row)+'</td></tr>');        
            }
        }
    });
}

/**
 * disegna la tabella dei risultati
 * @param {array} data 
 * @returns DOM
 */
function gp_draw_table(data, values) {
    if (jQuery('#table_check_data').length > 0) {
        $table = jQuery('#table_check_data');
    } else {
        $table =  jQuery('<table class="dbp-table-import-check"  id="table_check_data"></table>');
    }
    for (row in data) {
        if (row == 0) {
            custom_class = 'dbp-bg-check-import-row-header';
        } else {
            custom_class = (data[row]['___result___']) ? 'dbp-bg-check-import-row-ok' : 'dbp-bg-check-import-row-gray';
        }
        $tr = jQuery('<tr class="'+custom_class+'"></tr>');
        if (row == 0) {
            $tr.append('<td class="dbp-bg-check-import-row-header"> </td>'); 
        } else {
            oktr = (data[row]['___result___']) ? '<span class="dashicons dashicons-yes"  style="color:#0A0"></span>' : '<span class="dashicons dashicons-no" style="color:#A00"></span>';
            $tr.append('<td>' + oktr + '</td>'); 
        }
        for (cel in data[row]) {
            if (cel != "___result___") {
                if (row == 0) {
                    $tr.append('<td class="dbp-check-import-header">'+data[row][cel]+'</td>');
                } else {
                    custom_class = (data[row][cel] == '') ? 'dbp-bg-check-import-row-ok' : 'dbp-bg-check-import-row-no';
                    $tr.append('<td class="'+custom_class+'">'+values[row][cel]+"<br>"+data[row][cel]+'</td>');
                }
            }
        }
        $table.append($tr);
    }
    return $table;
}