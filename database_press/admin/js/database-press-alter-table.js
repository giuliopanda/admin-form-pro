/**
 * Il js dell'alter table NO SUBMIT
 */
 jQuery(document).ready(function () {
    jQuery('.js-create-table-type').change(function() {
        dbp_check_type(this);
    });
    jQuery('.js-create-table-type').each(function() {
        dbp_check_type(this);
    })
    
     //aggiungo la possibilità di fare il sort sulla creazione del nuovo db
     jQuery('.js-dragable-table > tbody').sortable({
        items: '.js-dragable-tr',
        opacity: 0.5,
        cursor: 'move',
        axis: 'y',
        handle: ".js-dragable-handle"
    });
    /**
     * Il nome della tabella
     */
    jQuery('#dbp_partial_name_table').on('keyup', function(e) {
        table_real_name();
    })

    jQuery('#dbp_table_use_prefix').change(function(e) {
        table_real_name();
    })

    /**
     * Se è primary disabilito il null
     */
     jQuery('.js-unique-primary').change(function(e) {
         check_null_primary();
     })
     check_null_primary();

    jQuery('.js-field-preselect').each(function() {
        dbp_preselect(this);
    })

    jQuery('.js-unique-primary').change(function() {
        dbp_unique_primary(this);
    });
    dbp_check_primary();

});

/**
 * Rimuove una riga dalla tabella di creazione di una tabella mysql
 */
 function dbp_alter_table_delete_row(el) {
    let $tr = jQuery(el).parents('tr');
    $tr.css('display','none');
    $tr.find('.js-field-action').val('delete');
    $tr.find('.js-unique-primary').val('f');
    dbp_check_primary();
}

/**
 * Il bottone per creare nuove righe della tabelle per la creazione delle tabelle mysql
 */
 function dbp_alter_add_row(el, max_allow) {
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
    dbp_preselect($tr2.find('.js-field-preselect').get(0));
 }


 /**
 * Inserisce il nome della tabella
 * @param {*} el 
 */
function table_real_name() {
    let new_name = "";
    if (jQuery('#dbp_table_use_prefix').is(':checked')) {
        new_name = jQuery('#dbp_table_use_prefix').val();
        jQuery('#dbp_wp_prefix').css('visibility','');
    } else {
        jQuery('#dbp_wp_prefix').css('visibility','hidden');
    }
    new_name += jQuery('#dbp_partial_name_table').val();
    jQuery('#dbp_structure_table_name').val(new_name);
}


/**
 * Gestisco il checkbox che definisce se è nullo o no
 */
function checkbox_null(el_checkcbox) {
    $td = jQuery(el_checkcbox).parent();
    val =  (jQuery(el_checkcbox).is(':checked')) ? 't' : 'f';
    $tr = jQuery($td).parents('tr');
    $pri_val = $tr.find('.js-unique-primary').val();
    if ($pri_val == "t") {
        jQuery(el_checkcbox).prop('checked',false);
        jQuery(el_checkcbox).prop('disabled', true);
        jQuery(el_checkcbox).attr('disabled', true);
        val = "f";
    }
    $td.find('.js-check-null-value').val(val);
}

function  check_null_primary() {
    jQuery('.js-unique-primary').each(function() {
        $tr = jQuery(this).parents('tr');
        $checkbox =  $tr.find('.js-check-null-checkbox');
        if ( jQuery(this).val() == "t") {
            $checkbox.prop('checked',false);
            $checkbox.prop('disabled', true);
            $checkbox.attr('disabled', true);
            $tr.find('.js-check-null-value').val('f');
        } else {
            $checkbox.prop('disabled', false);
            $checkbox.attr('disabled', false);
        }

    })
}

/**
 * Verifica che i select dei primary key sia univoco 
 */
 function dbp_unique_primary(el) {
    let value = jQuery(el).val();
    jQuery('.js-unique-primary').val('f');
    
    jQuery(el).val(value);
    let $tr =jQuery(el).parents('tr');
    $tr.find('.js-create-table-type').val('INT');
    $tr.find('.js-create-table-length').val('10');
    $tr.find('.js-create-table-attributes').val('UNSIGNED');
    $tr.find('.js-create-table-comment').val('');
    $tr.find('.js-create-table-default').val('');
    dbp_check_primary();
}


function dbp_check_primary() {
    var count_primary = 0;
    jQuery('.js-unique-primary').each(function() {
        jQuery(this).parent().parent().find('.js-dashicons').remove();
        if (jQuery(this).val() == "t") {
            count_primary ++;
            jQuery(this).parent().css('display','none');
            jQuery(this).parent().parent().append('<span class="js-dashicons dashicons dashicons-admin-network" style="color:#e2c447;display: block; margin: 0 auto;" title="Primary"></span>');
            $tr = jQuery(this).parents('tr');
            jQuery('.js-field-preselect').each(function() {
                if (jQuery(this).val() == "pri") {
                    jQuery(this).val('int_signed');
                }
            })
            $tr.find('.js-field-preselect').val('pri');


        } else {
            //console.log (jQuery(this).parent());
            jQuery(this).parent().css('display','block');
            jQuery(this).parent().css('visibility','initial');
        }
    });
    
    if (count_primary == 1) {
        jQuery('#dbp_content_button_create_form_msg_no_primary').css('display','none');
        jQuery('#dbp_content_button_create_form').css('display','block');
        jQuery('#dbp_execute_query_command').css('display','block');
    } else {
        jQuery('#dbp_content_button_create_form_msg_no_primary').css('display','block');
        jQuery('#dbp_content_button_create_form').css('display','none');
        jQuery('#dbp_execute_query_command').css('display','none');
    }
}

function dbp_preselect(el) {
    $tr = jQuery(el).parents('tr');
    if (jQuery(el).val() == "advanced") {
        $tr.find('.js-td-advanced').css('visibility','visible');
    } else {
      //  $tr.find('.js-create-table-default').val('');
        $tr.find('.js-check-null-value').val('f');
        $tr.find('.js-check-null-checkbox').prop('checked', false);
        $tr.find('.js-unique-primary').val('f');
        if (jQuery(el).val() == "int_signed") {
            $tr.find('.js-create-table-type').val('INT');
            $tr.find('.js-create-table-length').val('');
            $tr.find('.js-create-table-attributes').val('');
        }else if (jQuery(el).val() == "decimal"|| jQuery(el).val() == 'double') {
            $tr.find('.js-create-table-type').val('DECIMAL');
            $tr.find('.js-create-table-length').val('9,2');
            $tr.find('.js-create-table-attributes').val('');
        } else if (jQuery(el).val() == "varchar") {
            $tr.find('.js-create-table-type').val('VARCHAR');
            $tr.find('.js-create-table-length').val('255');
            $tr.find('.js-create-table-attributes').val('');
        } else if (jQuery(el).val() == "text") {
            $tr.find('.js-create-table-type').val('TEXT');
            $tr.find('.js-create-table-length').val('');
            $tr.find('.js-create-table-attributes').val('');
        } else if (jQuery(el).val() == "date") {
            $tr.find('.js-create-table-type').val('DATE');
            $tr.find('.js-create-table-length').val('');
            $tr.find('.js-create-table-attributes').val('');
        } else if (jQuery(el).val() == "datetime") {
            $tr.find('.js-create-table-type').val('DATETIME');
            $tr.find('.js-create-table-length').val('');
            $tr.find('.js-create-table-attributes').val('');
        } else if (jQuery(el).val() == "pri") {
            jQuery('.js-unique-primary').each(function() {
                jQuery(this).val('f');
                jQuery(this).parent().parent().find('.js-dashicons').remove();
            });
            $tr.find('.js-create-table-type').val('INT');
            $tr.find('.js-create-table-length').val('');
            $tr.find('.js-create-table-attributes').val('UNSIGNED');
            $tr.find('.js-unique-primary').val('t');
        }
        $tr.find('.js-td-advanced').css('visibility','hidden');
        dbp_check_primary();
        check_null_primary();
    }
}



/**
 * A seconda del tipo di dato che viene selezionato popolo le impostazioni e cosa si può fare e cosa no
 * @param DOM el 
 */
 function dbp_check_type(el) {
    $el = jQuery(el);
    $tr = $el.parents('tr');
    numeric = ['INT','TINYINT', 'SMALLINT','MEDIUMINT','BIGINT','DECIMAL','FLOAT','DOUBLE'];
    timestamp = ['TIMESTAMP','DATETIME'];
    nolength = ['TIMESTAMP','DATETIME', 'TINYTEXT','TEXT','MEDIUMTEXT','LONGTEXT','DATE','TIME', 'YEAR','BOOLEAN','JSON'];
    console.log ("dbp_check_type "+$el.val());
    if(nolength.indexOf($el.val()) != -1) {
        $tr.find(".js-create-table-length").css('visibility','hidden');
        $tr.find(".js-create-table-length").val('');
    } else {
        $tr.find(".js-create-table-length").css('visibility','');
        if ($tr.find(".js-create-table-length").val() == "") {
            if ($el.val() =="VARCHAR") {
                $tr.find(".js-create-table-length").val('255');
            }
            if ($el.val() =="INT") {
                $tr.find(".js-create-table-length").val('11');
            }
            if ($el.val() =="BIGINT") {
                $tr.find(".js-create-table-length").val('20');
            }
            if ($el.val() =="CHAR") {
                $tr.find(".js-create-table-length").val('100');
            }
            if ($el.val() =="DECIMAL" || $el.val() =="DOUBLE") {
                $tr.find(".js-create-table-length").val('9,2');
            }
        }
    }
    if(timestamp.indexOf($el.val()) != -1) {
        if ($tr.find(".js-create-table-attributes option[value='on update CURRENT_TIMESTAMP']").length == 0) {
            $tr.find(".js-create-table-attributes").append('<option value="on update CURRENT_TIMESTAMP">on update CURRENT_TIMESTAMP</option>');
        }
        $tr.find(".js-create-table-attributes option[value='UNSIGNED']").remove();
        $tr.find(".js-create-table-attributes option[value='UNSIGNED ZEROFILL']").remove();
        $tr.find(".js-create-table-attributes").css('visibility','');
        $tr.find(".js-unique-primary").css('visibility','hidden');
        $tr.find(".js-unique-primary").val('f');
    } else {
        $tr.find(".js-create-table-attributes option[value='on update CURRENT_TIMESTAMP']").remove();
        if(numeric.indexOf($el.val()) != -1) {
            if ($tr.find(".js-create-table-attributes option[value='UNSIGNED']").length == 0) {
                $tr.find(".js-create-table-attributes option[value='']").after('<option value="UNSIGNED ZEROFILL">UNSIGNED ZEROFILL</option>');
                $tr.find(".js-create-table-attributes option[value='']").after('<option value="UNSIGNED">UNSIGNED</option>');
            }
            $tr.find(".js-create-table-attributes").css('visibility','');
            $tr.find(".js-unique-primary").css('visibility','');
        } else {
            $tr.find(".js-create-table-attributes").css('visibility','hidden');
            $tr.find(".js-create-table-attributes option[value='UNSIGNED']").remove();
            $tr.find(".js-create-table-attributes option[value='UNSIGNED ZEROFILL']").remove();
            $tr.find(".js-unique-primary").css('visibility','hidden');
            $tr.find(".js-unique-primary").val('f');
            if ($tr.find(".js-create-table-default").val() == "0") {
                $tr.find(".js-create-table-default").val('');
            }
            dbp_check_primary();
        }
    }
    check_null_primary();
}
