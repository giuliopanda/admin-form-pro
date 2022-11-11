function dbp_get_backup(table, limit_start, div_id, filename) {
    if (limit_start == 0) {
        jQuery('#'+div_id).empty();
        jQuery('#'+div_id).text('0%');
    }
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        data : {'page':'database_press','section':'information-schema','action':'dbp_dump_table','table':table,'limit_start':limit_start,'div_id':div_id, 'filename':filename},
        success: function(response) {
            console.log(response);
            let tot = parseInt(response.tot);
            let done = parseInt(response.limit_start) + parseInt(response.exec);
            perc = Math.round((done/tot) * 100);
            jQuery('#'+div_id).empty().text(perc+'%');
            if (done < tot && done > 0) {
                dbp_get_backup( response.table, done, response.div_id, response.filename); 
            } else {
                jQuery('#'+div_id).empty().append('<a href="'+response.download+'" class="dbp-submit">Download export</a>');

            }
            //  ['filename' => $filename, 'tot'=>$tot, 'exec'=>count($rows), 'limit_start'=>$limit_start];
        }
    });
}