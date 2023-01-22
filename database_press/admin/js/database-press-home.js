jQuery(document).ready(function () {
    setInterval(refreshTable, 5000);
    refreshTable();
});

function refreshTable() {
    jQuery.ajax({
        type: 'POST',
        url: 'admin-ajax.php',
        dataType: 'json',
        data: {
            action: 'get_processlist',
        },
        success: function(response) {
            $table = adfo_create_processlist_table(response);
            jQuery('#adfo_processlist').empty().append($table);
        },
        error: function(error) {
            console.log(error);
        }
    });
}



function adfo_create_processlist_table(data) {
    var $table = jQuery('<table>').addClass('processlist-table wp-list-table widefat striped dbp-table-view-list ');
    var $thead = jQuery('<thead>').appendTo($table);
    var $tbody = jQuery('<tbody>').appendTo($table);
    if (data.length == 0) {
        var msgDiv = jQuery('<div class="dbp-alert-info">There are no slow queries running</div>');
        return msgDiv;
    }
    // Creazione dell'intestazione della tabella
    var $tr = jQuery('<tr>').appendTo($thead);
    for (var key in data[0]) {
        jQuery('<th>').text(key).appendTo($tr);
    }
    //aggiungiamo la colonna del bottone
    jQuery('<th>').text("kill").appendTo($tr);

    // Iterazione attraverso i risultati del JSON e creazione delle righe della tabella
    for (var i = 0; i < data.length; i++) {
        var $tr = jQuery('<tr>').appendTo($tbody);
        for (var key in data[i]) {
            jQuery('<td>').text(data[i][key]).appendTo($tr);
        }
        var $killBtn = jQuery('<button>').text('kill').addClass('kill-btn btn btn-danger').attr('data-id', data[i]['Id']).appendTo(jQuery('<td>').appendTo($tr));
    }

    // Aggiunta della tabella al documento
    $table.appendTo(document.body);
    //aggiungiamo l'evento click al bottone
    jQuery('.kill-btn').on('click', function() {
       
        var id = jQuery(this).attr('data-id');
        killProcess(id);
        jQuery(this).remove();
    });
    return $table;
}





function killProcess(processId) {
    jQuery.ajax({
        type: 'POST',
        url: 'admin-ajax.php',
        data: {
            action: 'kill_process',
            process_id: processId
        },
        success: function(response) {
            //console.log(response);
            kill_print_msg(response.msg, response.status);
        },
        error: function(error) {
            console.log(error);
            
        }
    });
}

function kill_print_msg(status, message) {
    // svuotare il div con id adfo_msg_kill
    jQuery("#adfo_msg_kill").empty();
    // Creare un div con un contenuto di testo
    var msgDiv = jQuery("<div>" + message + "</div>");
    // Aggiungere la classe corretta in base allo stato passato come parametro
    if (status === "success") {
        msgDiv.addClass("dbp-alert-info");
    } else {
        msgDiv.addClass("dbp-alert-danger");
    }
    // Appendere il div al div con id adfo_msg_kill
    jQuery("#adfo_msg_kill").append(msgDiv);
    adfo_remove_msg(msgDiv);
}

function adfo_remove_msg(selector) {
    setTimeout(function() {
        jQuery(selector).fadeOut(1000, function() {
            jQuery(this).remove();
        });
    }, 5000);
}
