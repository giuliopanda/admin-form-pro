/**
 * Qui tutte le funzioni per gestire le query multiple (o più avanti gli import) 
 */

/**
 * La funzione che carica le multiquery
 * @param String filename 
 */
function dbp_multiqueries_ajax(filename) {
    jQuery('#multiqueries_end_ok').css('display','none');
    jQuery('#multiqueries_end_no_ok').css('display','none');
    jQuery('#multiqueries_continue').css('display','none');
    jQuery('#dbp_count_queries_executed').css('display','inline-block');

    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        data: { action: "dbp_multiqueries_ajax", filename: filename, ignore_errors:document.getElementById('dbp_ignore_errors').checked }
    }).done(function (ris) {
        jQuery('#dbp_count_queries_executed').text(ris.executed_queries);
        if (ris.executed_queries < ris.total_queries) {
            if (ris.last_error != '') {
                jQuery('#multiqueries_continue').css('display','block');
                jQuery('#multiqueries_last_error_msg').html(ris.last_error);
            } else {
                dbp_multiqueries_ajax(ris.filename);
            }
        } else {
            // FINE!
            if (ris.error_count == 0) {
                jQuery('#multiqueries_end_ok').css('display','block');
            } else {
                jQuery('#multiqueries_end_no_ok').css('display','block');
                
            }
        }
    }).error(function() {
        console.warn ("ERROR");
    });
}


function dbp_multiqueries_cancel() {
    // quando premi cancel deve interrompere tutto.
    jQuery('#multiqueries_end_ok').css('display','none');
    jQuery('#multiqueries_end_no_ok').css('display','none');
    jQuery('#multiqueries_continue').css('display','none');
    jQuery('#multiqueries_cancel').css('display','block');
}