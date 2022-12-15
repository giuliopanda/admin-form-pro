jQuery(document).ready(function ($) {
    // serve per mostrare nascondere al caricamento della pagina i box di creazione tabella o inserimento dati
    jQuery('#dbp_import_select_action').change();

    jQuery('.jsonchange-select-tables-import-clone').change(function() {
        dbp_import_add_table_fields_for_insert(this);
    })

    jQuery('.js-unique-primary').change(function() {
        dbp_unique_primary(this);
    });
    // disabilito il tasto invio sulla creazione del nuovo db
    jQuery('#dbp_create_table').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) { 
          e.preventDefault();
          return false;
        }
    });
    //aggiungo la possibilità di fare il sort sulla creazione del nuovo db
    jQuery('.js-dragable-table > tbody').sortable({
        items: '.js-dragable-tr',
        opacity: 0.5,
        cursor: 'move',
        axis: 'y',
        handle: ".js-dragable-handle",
        update: function() {
            /*
            var ordr = jQuery(this).sortable('serialize') + '&action=list_update_order';
            jQuery.post(ajaxurl, ordr, function(response){
                //alert(response);
            });
            */
        }
    });
    // Verifico se c'era una tabella di origine da cui partire per l'import e nel caso la carico come default.
    if (jQuery('#csv_import_original_table').length > 0 && jQuery('#csv_import_original_table').val() != "") {
        jQuery('.jsonchange-select-tables-import-clone').val(jQuery('#csv_import_original_table').val()).change();
    }
});

/**
 * Il bottone per creare nuove righe della tabelle per la creazione delle tabelle mysql
 */
function dbp_create_table_add_row(el, max_allow) {
    $table =  jQuery(el).parents('table');
    $tr = $table.find('tbody > tr.js-clore-master');
    tr_length = $table.find('tbody > tr').length;
    if (max_allow > 0 && tr_length > 0) {
        if (tr_length >= max_allow) {
            alert("New lines cannot be created. max_input_var insufficient");
            return false;
        }
    }
    $tr2 = $tr.clone(true);
    jQuery(el).parents('tr').before($tr2);
    $tr2.removeClass('js-clore-master').addClass('js-dragable-tr');
    jQuery('.js-dragable-table > tbody').sortable('refresh');


}

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

/**
 * Rimuove una riga dalla tabella di creazione di una tabella mysql
 */

function dbp_import_csv_create_table_delete_row(el) {
    jQuery(el).parents('tr').remove();
}

/**
 * Mostra nasconde i blocchi di creazione tabella o inserimento tabella a seconda di come è selezionato il select
 * @param DOM el 
 */
function dbp_toggle_action_import(el) {
    let partial_id = jQuery(el).val();
    jQuery('.js-dbp-import-content-toggle').css('display','none');
    jQuery('#dbp_content_'+partial_id).css('display','block');
   
    jQuery('#dbp_result_import_box').css('display','none');
    jQuery("#dbp_content_table").animate({ scrollTop: jQuery('#dbp_import_select_action').offset().top }, 100);
}


/**
 * Importa csv update/Insert
 *  Clona il div con il select con le tabelle al change del select 
 */
function dbp_import_add_table_fields_for_insert(el) {
    console.log ('dbp_import_add_table_fields_for_insert');
    jQuery('.dbp-import-content-clone-block').css('display','none');
    let $block = jQuery(el).parents('.js-insert-fields-content-clone');
    var select_val = jQuery(el).val();
    $new_block = $block.clone();
    $new_block.removeClass('js-insert-fields-content-clone').addClass('js-insert-fields-content');
    jQuery('#content_all_insert_fields_block').append( $new_block);
    $new_select = $new_block.find('.js-select-tables-import');
    $new_select.val(select_val);
    $new_select.removeClass('jsonchange-select-tables-import-clone');
    let id = dbp_uniqid();
    $new_select.prop('id',id);
    $new_select.change(function() {
        let select_val = jQuery(this).val();
        let content = jQuery(this).parents('.js-insert-fields-content');  
        jQuery('.jsonchange-select-tables-import-clone').val(select_val).change();
        content.find('.js-immport-choose-table-remove-btn').click();

    })
    jQuery(el).val('');
    dbp_load_table_structure(id, select_val);
   // $new_block.find('.js-immport-choose-table-remove-btn').css('display','inline-block');
    $new_block.find('.js-immport-choose-table-remove-btn').click(function() {
        let content = jQuery(this).parents('.js-insert-fields-content');
        let option = content.find('.js-import-unique-input').val();
        remove_select_option(option);
        content.remove();  
    })
}


/**
 * Verifico se una tabella che si sta inserendo ha il primary key inserito oppure no
 */
function check_primary_key_choosen($block) {
    console.log('check_primary_key_choosen');
    console.log ($block);
    $block.find('.js-msg-no-pri-key').css('display', 'block');
    $block.find('.js-msg-yes-pri-key').css('display', 'none');
    $block.find('.js-fields-choosen').each(function() {
        if (jQuery(this).hasClass('js-fields-choosen-key') && jQuery(this).val() != "") {
            $block.find('.js-msg-no-pri-key').css('display', 'none');
            $block.find('.js-msg-yes-pri-key').css('display', 'block');
        }
    });
}

/**
 * 
 * @param String elid 
 * @param String table 
 */  
function dbp_load_table_structure(elid, table) {
    console.log ('dbp_load_table_structure');
   // console.log ("dbp_load_table_structure id: "+elid+" table: "+table );
    csv_temporaly_filename = jQuery('#csv_temporaly_filename').val();
    csv_first_row_as_headers = jQuery('#csv_first_row_as_headers').val();
    csv_delimiter = jQuery('#csv_delimiter').val();
    if (typeof csv_structure_table_created == "undefined") {
        csv_structure_table_created = [];
    }
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        cache: false,
        data : {
            section: 'table-import',
            action:'dbp_import_csv_table_structure', 
            elid:elid, 
            table:table, 
            csv_temporaly_filename:csv_temporaly_filename, 
            csv_first_row_as_headers:csv_first_row_as_headers, 
            csv_delimiter:csv_delimiter,
            csv_structure_table_created : csv_structure_table_created
        },
        success: function(response) {
            let $block = jQuery('#'+response.elid).parents('.js-insert-fields-content');
            if ($block) {
                if (response.result == "ok") {
                    $block.find('.js-content-table-fields').append(response.html);
                   
                    jQuery('.js-insert-fields-content').each(function() {
                        if (jQuery(this).data('dbp_uniqueid')) {
                            add_select_option($block, jQuery(this).data('dbp_uniqueid'));
                        }
                    });                  
                    $block.find('.js-fields-choosen').change(function() {
                        check_primary_key_choosen( jQuery(this).parents('.js-insert-fields-content'));
                    });
                    check_primary_key_choosen($block);
                    $block.data('dbp_uniqueid',response.unique);
                    $block.find('.js-unique-code').text(response.unique);
                    add_custom_select_option(); 
                } else {
                    $block.find('.js-content-table-fields').append('Ops something wrong!');
                }
            }
        }
    });
}

/**
 * Aggiunge ai select dell'associazione campi un js per la gestione del custom
 */

function add_custom_select_option() {
    //console.log ('add_custom_select_option');
    jQuery('.js-fields-choosen').each(function() {
        $e = jQuery(this);
        if ($e.data('jsdbp_custom') !== 1) {
            $e.data('jsdbp_custom', 1);
            $input = jQuery('<textarea rows="1" class="dbp-textarea-pinacode js-show-pinacode-link"></textarea>');
            $input.prop('name', $e.prop('name'));
            $input.css('display', 'none');
            $e.prop('name',"");
            jQuery(this).after($input);
           
            $e.data('jsdbp_input',$input);
            if ($e.val() != "") {
                if ($e.val().indexOf(" ") > -1) {
                    $input.val('[%item get="'+$e.val()+'"]');
                } else {
                    $input.val('[%item.'+$e.val()+']');
                }
            } else {
                $input.val('');
            }
            $e.change(function() {
                $input = jQuery(this).data('jsdbp_input');
                if (jQuery(this).val() == "__[custom]__") {
                    jQuery(this).css('display','none');
                    $input.css('display', 'inline-block');
                    dbp_show_pinacode_link($input);
                    $btn = jQuery('<span class="dashicons dashicons-list-view dbp-input-button-margin"></span>');
                    $btn.data('jsbtn_input', $input);
                    $btn.data('jsbtn_select', jQuery(this));
                    $input.focus();
                    $input.after($btn);
                    $btn.click(function() {
                        $input = jQuery(this).data('jsbtn_input');
                        $select = jQuery(this).data('jsbtn_select');
                        $input.css('display','none');
                        $select.css('display','inline-block');
                        $select.val('');
                        dbp_show_pinacode_link($input);
                        jQuery(this).remove();
                    })
                } else if (jQuery(this).val() != "") {
                    if (jQuery(this).val().indexOf(" ") > -1) {
                        $input.val('[%item get="'+jQuery(this).val()+'"]');
                    } else {
                        $input.val('[%item.'+jQuery(this).val()+']');
                    }
                } else {
                    $input.val('');
                }
            });

        }
       

    });
}

function add_select_option($block, $option) {
    $block.find('.js-fields-choosen').each(function() {
        $select = jQuery(this);
        if ($select.find("option[value='"+$option+"']").length == 0) {
            $select.append(jQuery("<option></option>").val($option).html("["+$option+"]"));
        }
    });
}

function remove_select_option($option) {
    jQuery('.js-fields-choosen').each(function() {
        $select = jQuery(this);
        if ($select.find("option[value='"+$option+"']").length > 0) {
            $select.find("option[value='"+$option+"']").remove();
        }
    });
}

/**
 * Un ajax che prova a creare una tabella temporanea e a fare gli insert richiesti
 */
function dbp_csv_test_import() {

    jQuery('#dbp_result_test_import_csv').empty().css('display','none');
    jQuery('#dbp_import_csv_exec_import').css('display','none');
    jQuery('#dbp_result_import_box').css('display','block');
    jQuery('#dbp_import_csv_alert').removeClass('dbp-alert-sql-error dbp-alert-info').addClass('dbp-alert-gray').css('display','block');
    jQuery('#dbp_import_csv_alert').html('Testing data');
    var data = jQuery('#dbp_import_csv_data_config').serializeArray() ;
    data.push({name: 'action', value:'dbp_test_import_csv_data'});
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        data : data,
        success: function(response) {
            jQuery('#dbp_result_test_import_csv').css('display','block');
            jQuery('#dbp_import_csv_alert').css('display','none');
            if (response.result == 'ok') {
                for (x in response.html) {
                    jQuery('#dbp_result_test_import_csv').append(response.html[x].html);
                }
            } else if (response.msg) {
                jQuery('#dbp_import_csv_alert').removeClass('dbp-alert-info dbp-alert-gray').addClass('dbp-alert-sql-error').css('display','block');
                jQuery('#dbp_import_csv_alert').html(response.msg);
            } else {
                jQuery('#dbp_import_csv_alert').removeClass('dbp-alert-info dbp-alert-gray').addClass('dbp-alert-sql-error').css('display','block');
                jQuery('#dbp_import_csv_alert').html('There was an error testing the data');
            }
        }
    });
}

/**
 * L'ajax per l'importazione finale
 */
 function dbp_csv_exec_import(insert, update, errors, total_row_executed) {
    jQuery('#dbp_result_test_import_csv').empty().css('display','none');
    if (total_row_executed == 0) {
        jQuery('#dbp_import_csv_exec_import').css('display','none');
    } else {
        // non è il primo invio quindi devo mettere csv_first_row_as_headers a true perché dopo la prima esecuzione nel csv risalvato genero le intestazioni
        jQuery('#csv_first_row_as_headers').val(1);

    }
    jQuery ('#dbp_import_csv_data_config').css('display','none');
    jQuery ('#first_block').css('display','none');
    jQuery ('#dbp_import_csv_btns').css('display','none');
    
    jQuery('#dbp_result_import_box').css('display','block');
    jQuery('#dbp_import_csv_alert').removeClass('dbp-alert-sql-error dbp-alert-info').addClass('dbp-alert-gray').css('display','block');
    jQuery('#dbp_import_csv_alert').html('Importing data');
    var data = jQuery('#dbp_import_csv_data_config').serializeArray() ;
    data.push({name: 'action', value:'dbp_import_csv_data'});
    data.push({name: 'total_row_executed', value:total_row_executed});
    data.push({name: 'insert', value:insert});
    data.push({name: 'update', value:update});
    data.push({name: 'errors', value:errors});
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        data : data,
        success: function(response) {
            if (response.result == 'ok') {
                jQuery('#dbp_import_csv_alert').html('I am loading data, please do not close the browser window or change the page');
                jQuery('#dbp_import_csv_exec_import').css('display','table');
                jQuery('#dbp_result_import_csv_total_row').text(response.total_row);
                jQuery('#dbp_result_import_csv_errors').text(response.errors);
                jQuery('#dbp_result_import_csv_insert').text(response.insert);
                jQuery('#dbp_result_import_csv_update').text(response.update); 
                if (response.break == 1) {
                    // devo richiamare nuovamente la query perché stava andando in timeout
                    dbp_csv_exec_import(response.insert, response.update, response.errors, response.total_row_executed);
                } else {
                    jQuery('#dbp_import_csv_alert').removeClass('dbp-alert-sql-error dbp-alert-gray').addClass('dbp-alert-info').css('display','block');
                    jQuery('#dbp_import_csv_alert').html('Import complete');
                    jQuery('#btn_csv_download').css('display','block');
                }
              
            } else {
                jQuery('#dbp_import_csv_alert').removeClass('dbp-alert-info dbp-alert-gray').addClass('dbp-alert-sql-error').css('display','block');
                jQuery('#dbp_import_csv_alert').html('There was an error importing the data');
            }
        }
    });
}



/**
 * IMPORT BIG DATA
 */

 jQuery(document).ready(function ($) {
    var reader = {};
    var file = {};
    var slice_size = 1900 * 1024;

    function start_upload( event ) {
        event.preventDefault();
        if ( jQuery('#dbi_import_file').prop('disabled')) return;
        reader = new FileReader();
        file = document.getElementById('dbi_import_file').files[0];
        if (file == undefined) return;
        if (file.size > 0 ) {
            jQuery('#dbi-file-upload-submit').addClass('dbp-btn-disabled');
            jQuery('#dbi_import_file').prop('disabled', true);
            upload_file( 0, '' );
        }

    }
    
    $( '#dbi-file-upload-submit' ).on( 'click', start_upload );

    
    function upload_file( start, filename ) {
        var next_slice = start + slice_size + 1;
        var blob = file.slice( start, next_slice );
        
        reader.onloadend = function( event ) {
            if ( event.target.readyState !== FileReader.DONE ) {
                return;
            }
            $.ajax( {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                cache: false,
                data: {
                    action: 'dbi_upload_file',
                    section: 'table-import',
                    file_data: event.target.result,
                    file: file.name,
                    file_name: filename,
                    file_type: file.type,
                    nonce: dbi_vars.upload_file_nonce
                },
                error: function( jqXHR, textStatus, errorThrown ) {
                    console.log( jqXHR, textStatus, errorThrown );
                },
                success: function( data ) {
                    var size_done = start + slice_size;
                    var percent_done = Math.floor( ( size_done / file.size ) * 100 );

                    if ( next_slice < file.size ) {
                        // Update upload progress
                        jQuery( '#dbpUploadProgress' ).html( `Uploading File -  ${percent_done}%` );
                        // More to upload, call function recursively
                        upload_file( next_slice, data.file_name );
                    } else {
                        jQuery('#dbi-file-upload-submit').addClass('dbp-btn-disabled');
                        // Update upload progress
                        jQuery( '#dbpUploadProgress' ).html( 'Upload Complete!' );
                        if (file.size > 10000*1024) {
                            jQuery( '#dbpUploadProgress' ).html( 'BIG DATA: Upload Complete!' );
                            if (data.ext == "sql") {
                                jQuery( '#dbpUploadProgress' ).html( 'BIG DATA SQL: Upload Complete!' );
                                create_btn_to_execute_sql(data);
                            } 
                            if (data.ext == "csv") {
                                jQuery( '#dbpUploadProgress' ).html( 'BIG DATA CSV: Upload Complete!' );
                                create_btn_to_execute_csv(data);
                                // Vado avanti con exec import csv (DA SCRIVERE)
                            }
                        } else {
                            jQuery( '#dbpUploadProgress' ).html( 'NORMAL DATA: Upload Complete!' );
                            if (data.ext == "sql") {
                                jQuery( '#dbpUploadProgress' ).html( 'NORMAL DATA SQL: Upload Complete!' );
                                create_btn_to_execute_sql(data);
                            } 
                            if (data.ext == "csv") {
                                jQuery( '#dbpUploadProgress' ).html( 'NORMAL DATA CSV: Upload Complete!' );
                                create_btn_to_execute_csv(data);
                                // Vado avanti con lo standard csv
                            } 
                        }
                    }
                }
            } );
        };

        reader.readAsDataURL( blob );
    }

});


/**
 * Eseguo l'SQL
 * @param {*} data 
 */
function create_btn_to_execute_sql(data) {
    $dbpNextMsg = jQuery('<div class="dbp-alert-info">The sql file is ready to be imported.</div>');
    $dbpBtns = jQuery('<div></div>');
    $dbpNextStep = jQuery('<span class="dbp-submit">Execute import</span>');
    $dbpCancel = jQuery('<span class="button" id="cancel_sql" style="margin-left:1rem">Cancel</span>');
    $dbpBtns.append($dbpNextStep);
    $dbpBtns.append($dbpCancel);
    $dbpNextStep.data('filename', data.file_name);
    jQuery('#dbpNextStep').empty().append($dbpNextMsg).append($dbpBtns);
    // TODO upload sql
    $dbpNextStep.click(function() {
        let filename = jQuery(this).data('filename');
        jQuery(this).remove();
        jQuery('#dbpNextStep').empty().text('loading, be patient...');
        jQuery.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            cache: false,
            data: {
                action: 'exec_big_sql_files',
                section: 'table-import',
                filename: filename
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                console.warn( jqXHR, textStatus, errorThrown );
                jQuery('#dbpNextStep').empty().append('<div class="dbp-alert-danger">NETWORK ERROR</div>');
            },
            success: function( data ) {
                jQuery('#dbpNextStep').empty();
                if (data.return) {
                    jQuery('#dbpNextStep').append('<div class="dbp-alert-info">'+data.msg+'</div>');
                } else {
                    jQuery('#dbpNextStep').append('<div class="dbp-alert-danger">'+data.msg+'</div>');
                }
            }
        });
    });
    $dbpCancel.click(function() {
        jQuery('#dbi_import_file').prop('disabled', false);
        jQuery('#dbpNextStep').empty();
        jQuery('#dbpUploadProgress').empty();
        jQuery('#dbi_import_file').val('');
        jQuery('#dbi-file-upload-submit').removeClass('dbp-btn-disabled');
        jQuery('#dbi_import_file').prop('disabled', false);
        
    });
}
/**
 * Vado avanti con un csv
 * @param {*} data 
 */
 function create_btn_to_execute_csv(data) {
    $dbpNextMsg = jQuery('<div class="dbp-alert-info">The CSV file has been uploaded, go ahead to define how to import it.</div>');
    $dbpBtns = jQuery('<div></div>');
    $dbpNextStep = jQuery('<span class="dbp-submit">Go ahead</span>');
    $dbpCancel = jQuery('<span class="button" style="margin-left:1rem">Cancel</span>');
    $dbpBtns.append($dbpNextStep);
    $dbpBtns.append($dbpCancel);
    $dbpNextStep.data('filename', data.file_name);

    $dbpForm = jQuery('<form method="POST"><input type="hidden" name="page" value="database_press"><input type="hidden" name="section" value="table-import"> <input type="hidden" name="csv_name_of_file" value="'+data.org_name+'"><input type="hidden" name="csv_temporaly_filename" value="'+data.file_name+'"><input type="hidden" name="action" value="execute-csv-data"></form>');
   
    $dbpNextStep.click(function() {
        jQuery('#dbpNextStep form').submit();
    });
    jQuery('#dbpNextStep').empty().append($dbpNextMsg).append($dbpBtns).append($dbpForm);
    $dbpCancel.click(function() {
        jQuery('#dbi_import_file').prop('disabled', false);
        jQuery('#dbpNextStep').empty();
        jQuery('#dbpUploadProgress').empty();
        jQuery('#dbi_import_file').val('');
        jQuery('#dbi-file-upload-submit').removeClass('dbp-btn-disabled');
        jQuery('#dbi_import_file').prop('disabled', false);
        
    });
 }