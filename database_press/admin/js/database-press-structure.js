var dbp_cm_variables = ['ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC', 'BEGIN', 'BETWEEN', 'BOTH', 'BY', 'CALL', 'CASE', 'COLLATE', 'COMMIT', 'COUNT', 'CREATE', 'CURSOR', 'DATABASE', 'DEFAULT', 'DELETE', 'DESC', 'DISTINCT', 'DROP', 'EACH', 'ELSE', 'ELSEIF', 'END', 'FIELD', 'FOR', 'FROM', 'GLOBAL', 'GROUP BY', 'GROUP', 'HAVING', 'IF', 'IN', 'INDEX', 'INNER', 'INSERT', 'INSERT INTO', 'INTO', 'IS', 'JOIN', 'LIKE', 'NOT', 'ON', 'ORDER', 'OR', 'OUTER', 'RIGHT', 'SELECT', 'SET', 'TABLE', 'UNION', 'UPDATE', 'VALUES', 'WHERE', 'LIMIT', 'LEFT', 'TEMPORARY', 'PRIMARY', 'KEY', 'ENGINE', 'CHARSET','COLLATE', 'NULL', 'FLUSH', 'PRIVILEGES'];
  
jQuery(document).ready(function ($) {


    jQuery('#dbp_create_table').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) { 
          e.preventDefault();
          return false;
        }
    });
   
    /**
     * COLORO LA QUERY ESEGUITA
     */
    $('.js-dbp-mysql-query-text').each(function() {
        $(this).html(query_color2($(this).html()));
    });

    
});



 /**
  * Invio il form per i test
  */
 function dbp_submit_test_edit_structure() {
    //dbp_create_table
    var data = jQuery('#dbp_create_table').serializeArray() ;
    data.push({name: 'action', value:'dbp_update_table_structure_test'});
    jQuery('#dbp_result_alert_table_title_test').css('display','none');
    jQuery('#dbp_result_alert_table_test').empty();
    jQuery('#dbp_execute_query_command').css('display','block');
    jQuery('#dbp_msg_fix_error_before').css('display','none');
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        data : data,
        success: function(response) {
            jQuery('#dbp_result_alert_table_test').empty();
            jQuery('#dbp_result_alert_table_title_test').css('display','block');
           
            let keys = Object.keys(response.row_table);
            if (keys.length > 0) {
                for (x in response.row_table) {
                    let row = response.row_table[x];
                    let $box = jQuery('<div class="structure_box"></div>');
                    
                    $box.append('<h2>' + row.action + '</h2>');
                    $box.append('<p>' + row.sql + '</p>');
                    if (row.query_result == 0 || row.query_error != "") {
                        $box.addClass('dbp-alert-sql-error');
                        $box.append('<p>' + row.query_error + '</p>');
                      //  jQuery('#dbp_execute_query_command').css('display','none');
                        jQuery('#dbp_msg_fix_error_before').css('display','block');
                    } else {
                        $box.addClass('dbp-alert-info');
                        if (row.fields_errors.length == 0) {
                            if (row.hasOwnProperty('query_msg') && row.query_msg != "") {
                                $box.append('<p>'+row.query_msg+'</p>');
                            } else {
                                $box.append('<p>No problems detected</p>');
                            }
                        }
                    }
                    if (row.fields_errors.length > 0) {
                        $table = jQuery('<table class="dbp-table-error-alert"></table>');
                        $table.append('<tr class="dbp-table-error-alert-title"><td>Original Value</td><td>New Value</td></tr>');
                        for (x in row.fields_errors) {
                            $table.append("<tr><td>"+row.fields_errors[x][0]+"</td><td>"+row.fields_errors[x][1]+"</td></tr>");
                        }
                        $box.removeClass('dbp-alert-info').addClass('dbp-alert-sql-error');
                        $box.append('<div class="dbp-table-error-data">There will be data loss if you make this change!</div><p>Here are some examples:</p>');
                        $box.append($table);
                    }
                    jQuery('#dbp_result_alert_table_test').append($box);
                }
            } else {
                jQuery('#dbp_result_alert_table_test').append('<div class="structure_box dbp-alert-info">There is no change to make</div>');
            }
        }
    })
 }

/**
 * Modifica o crea una tabella
 * @param {*} action_value 
 */
 function dbp_submit_edit_structure(action_value) {
    //dbp_create_table
    dbp_validate_form = true;
    jQuery('#dbp_create_table').find('.js-dbp-validity').each(function() {
        if (!this.checkValidity()) {
            $tr = jQuery(this).parents('tr');
            if (!jQuery(this).parents('.js-clore-master').length && $tr.css('display')!= 'none') {
                this.reportValidity();
               
                dbp_validate_form = false;
            }
        }
    });
    if (!dbp_validate_form) {
        return false;
    }
    var data = jQuery('#dbp_create_table').serializeArray() ;
    data.push({name: 'action', value:action_value});
    jQuery('#dbp_result_alert_table_test').empty();
    jQuery('#dbp_result_alert_table').empty();
    jQuery('#dbp_execute_query_command').css('display','block');
    jQuery('#dbp_result_alert_table_title').css('display','block');
    jQuery('.js-hide-after-save').css('display','none');
    jQuery('#dbp_link_return').empty();
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        data : data,
        success: function(response) {
            jQuery('#dbp_result_alert_table').empty();
            jQuery('#dbp_result_alert_table_title').css('display','none');
            let error = false;
            let keys = Object.keys(response.row_table);
            if (keys.length > 0) {
                for (x in response.row_table) {
                    let row = response.row_table[x];
                    let $box = jQuery('<div class="structure_box"></div>');
                    $box.append('<h2>' + row.action + '</h2>');
                    $box.append('<p>' + row.sql + '</p>');
                    if (row.query_result == 0 || row.query_error != "") {
                        $box.addClass('dbp-alert-sql-error');
                        $box.append('<p>' + row.query_error + '</p>');
                        error = true;
                    } else {
                        $box.addClass('dbp-alert-info');
                        if (row.curr_action == "renametable") {
                            jQuery('#wpcontent a').each(function() {
                                let temp_href = this.getAttribute('href');
                                if (temp_href) {
                                    temp_href = temp_href.replace(row.old_table, row.new_table);
                                    jQuery(this).attr('href', temp_href);
                                }
                            });
                            response.table_link = response.table_link.replace(row.old_table, row.new_table);
                            console.log ("response.table_link2 "+response.table_link);
                        }
                    }

                    jQuery('#dbp_result_alert_table').append($box);
                    jQuery('#dbp_result_alert_table_title').html('DONE!');
                   
                }
                if (response.table_link != "" && !error) {
                    jQuery('#dbp_link_return').append('<a class="dbp-submit" href="'+response.table_link+'">Show table</a>');
                } else {
                    jQuery('.js-hide-after-save').css('display', '');
                }
            } else {
                jQuery('#dbp_result_alert_table').append('<div class="structure_box dbp-alert-info">Saved</div>');
            }
        }
    })
 }




 /**
 * Coloro un testo passato con le istruzioni delle query 
 */
function query_color2 (query_text) {
    let new_text = [];
    query_text.split(" ").forEach(function (item) {
       
        if (dbp_cm_variables.indexOf(item.toUpperCase()) != -1) {
            new_text.push('<span class="dbp-cm-keyword">' + item.toUpperCase() + '</span>');
        } else {
            new_text.push(item);
        }
    });
    return new_text.join(" ");
}




/**
 * GESTIONE INDICI
 */
 jQuery(document).ready(function () {
      //aggiungo la possibilità di fare il sort sulla creazione del nuovo db
    jQuery('.js-drag-index-column').sortable({
         items: '.js-dragable-li',
         opacity: 0.5,
         cursor: 'move',
         axis: 'y',
         handle: ".js-dragable-handle"
    });
     // intercetto il submit della form dell'indice
    jQuery('#table_structure_index').submit(   
        function(event) {
            jQuery('.js-clore-master').remove();
            /*var numberTextFieldVal = $('#numberTextField').val();
            if (isNaN(numberTextFieldVal) || numberTextFieldVal == '') {
                writeMessageToWebPage("Data submitted is not a number!");
                event.preventDefault();
            }
            */
        }
    );
});

function clone_li_master() {
    $li_ori = jQuery('.js-clore-master').first();
    $li = $li_ori.clone(true);
    jQuery($li_ori).after($li);
    $li.removeClass('js-clore-master').addClass('js-dragable-li');
    jQuery('.js-drag-index-column').sortable('refresh');
    
}

function dbp_index_remove_cols(el) {
    jQuery(el).parents('li').remove();
}


function dbp_show_edit_metadata() {
    jQuery('#dbp_edit_metadata').css('display','block');
    jQuery('#dbp_show_metadata').css('display','none');
    jQuery('#dbp_edit_metadata_btn').css('display','none');
}
function dbp_cancel_edit_metadata() {
    jQuery('#dbp_edit_metadata').css('display','none');
    jQuery('#dbp_show_metadata').css('display','block');
    jQuery('#dbp_edit_metadata_btn').css('display','block');
}

function dbp_submit_edit_metadata() {
    jQuery('#dbp_edit_metadata').css('display','none');
    jQuery('#dbp_show_metadata').css('display','none');
    jQuery('#dbp_edit_metadata_btn').css('display','none');
    jQuery('#dbp_edit_metadata_form').submit();
}