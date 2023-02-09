var dbp_edit = null;
var dbp_sql_check = "";
var dbp_alias_table = {};
jQuery(document).ready(function () {
    setInterval(function() {dbp_analyze_query()}, 3000);
   
});
   
/**
 * Verifica se l'editor deve essere aperto o chiuso dalla classe di default
 */
function check_toggle_sql_query_edit()  {
    if (jQuery('#dbp_content_query_edit').hasClass('js-default-show-editor')) {
        show_sql_query_edit(dbp_sql_editor_height);
    } else {
        hide_sql_query_edit();
    }
}


/**
 * Fa apparire l'edit della query
 */
 function show_sql_query_edit(size_height)  {
    if (document.getElementById('sql_query_edit') != null) {
        /*
        jQuery('#dbp-bnt-edit-query').css('display','none');
        jQuery('#dbp-bnt-go-back-filter-query').css('display','none');
        jQuery('#dbp-bnt-go-query').css('display', 'inline-block');
        jQuery('#dbp-bnt-cancel-query').css('display', 'inline-block');
        jQuery('#sql_query_edit').css('display', 'none');
        jQuery('#result_query').css('display', 'none');
        jQuery('#dbp-bnt-reload-query').css('display', 'none');
*/
        jQuery('#sql_query_edit').css('display', 'none');
        jQuery('#result_query').css('display', 'none');
        jQuery('#dbp_content_query_edit').removeClass('js-default-hide-editor').addClass('js-default-show-editor');
        
        dbp_edit = wp.codeEditor.initialize(jQuery('#sql_query_edit'), editorSettings);
        document.getElementById('sql_query_edit').dbp_editor_sql = dbp_edit;
        dbp_edit.codemirror.on('keyup', function (cm, event) {
            // type code and show autocomplete hint in the meanwhile
            setTimeout(function () {
                if (!cm.state.completionActive)
                    cm.showHint({ hint: wp.CodeMirror.hint.database_press, completeSingle: 0 });
            }, 100);
        });
        if (size_height > 0) {
            dbp_edit.codemirror.setSize(null, size_height);
        }
        dbp_edit.codemirror.focus();
    }
}


/**
 * Fa sparire l'edit della query
 */
function hide_sql_query_edit() {
    if (typeof (document.getElementById('sql_query_edit').dbp_editor_sql) != "undefined") {
        jQuery('#result_query').css('display', 'block');
        jQuery('#dbp_content_query_edit').removeClass('js-default-show-editor').addClass('js-default-hide-editor');
        document.getElementById('sql_query_edit').dbp_editor_sql;
        dbp_edit.codemirror.toTextArea();
        document.getElementById('sql_query_edit').dbp_editor_sql = undefined;
        jQuery('#sql_query_edit').val(jQuery('#sql_query_executed').val());
        jQuery('#sql_query_edit').css('display', 'none');
    }
}

/**
 * SUBMIT FORM
 */
 function dbp_submit_custom_query(el) {
     
    if ( document.getElementById('sql_query_edit') != null) {
        code = document.getElementById('sql_query_edit').dbp_editor_sql;
        if (typeof code != 'undefined' && code != null) {
            jQuery('#sql_query_edit').value = code.codemirror.getValue();
        }
        jQuery('#sql_query_edit').parents('form').submit();
    } else {
        jQuery(el).parents('form').submit();
    }
   
}


/**
 * CODE MIRROR CREATE
 */
 var dbp_cm_all = [];

if (typeof dbp_tables != 'undefined' && typeof dbp_cm_variables != 'undefined') {
     dbp_cm_all = dbp_cm_variables.concat(dbp_tables);
    var editorSettings = "";
} else if (typeof dbp_tables != 'undefined') {
    dbp_cm_all = dbp_tables;
}
jQuery(document).ready(function ($) {
    // https://codemirror.net/5/doc/manual.html
    wp.CodeMirror.defineMode("dtsql", function (config, parserConfig) {
         return {
             token: function (stream, state) {
                let dnext = true;
                if (stream.start > 0) {
                    stream.next();
                } else{
                    dnext = false;
                }
                if (stream.string.length > 10000)   {
                    while( stream.next() != null);
                    return null;
                }
                if (stream.string.length < 5000) {
                    let search_table_ch = [' ', '`', '.'];
                    let search_table_ch_2 = [' ', '`', ' '];
                    for (let i = 0; i <dbp_tables.length ; i++) {
                        for (let sh in search_table_ch) {
                            let search = search_table_ch_2[sh] + dbp_tables[i] +  search_table_ch[sh] ;
                            if (stream.match(search, true, true )) {
                                var word = stream.current();
                                if (!dnext ) stream.next();
                          
                            // console.log (" word"+word);
                                return "variable-2";
                            } 
                        }
                    }
                    for (let i in dbp_alias_table) {
                        for (let sh in search_table_ch) {
                            let search = search_table_ch_2[sh] + i +  search_table_ch[sh] ;
                            if (stream.match(search, true, true )) {
                                var word = stream.current();
                                if (!dnext ) stream.next();
                                // console.log (" word"+word);
                                return "variable-2";
                            } 
                        }
                    }
                }

                for (let i = 0; i < dbp_cm_variables.length ; i++) {
                    if (stream.match(dbp_cm_variables[i], true, true )) {
                        chpeek = stream.peek();
                        if (chpeek == " " || chpeek == "`" || chpeek == "." || typeof (chpeek) == 'undefined') {
                            var word = stream.current();
                            if (!dnext ) stream.next();
                            if (word.trim().toLowerCase() == dbp_cm_variables[i].trim().toLowerCase()) {
                                return "keyword bold";
                            } else {
                                return null;
                            }
                        }
                    }                   
                }
                if (!dnext ) stream.next();
                return null;
               
              
            },
        };
    });

    editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
    editorSettings.codemirror = _.extend(
         {},
         editorSettings.codemirror,
         {
             indentUnit: 4,
             tabSize: 4,
             mode: 'dtsql'
         }
    );
      
    wp.CodeMirror.registerHelper("hint", "database_press", function (editor, options) {
    });
         
    wp.CodeMirror.registerHelper("hint", "database_press", function (editor, options) {
        if (editor.getValue().length > 10000) {
            editor.setOption("mode", 'text');  
            return null;
        }
         var cur = editor.getCursor(), curLine = editor.getLine(cur.line);
        
         var start = cur.ch, end = start;
         while (end <= curLine.length && curLine.charAt(end) != " " && curLine.charAt(end) != "[" && curLine.charAt(end) != "]" && curLine.charAt(end) != "=" && curLine.charAt(start - 1) != "'" && curLine.charAt(start - 1) != '"') ++end;
         while (start > 0 && curLine.charAt(start - 1) != " " && curLine.charAt(start - 1) != "'" && curLine.charAt(start - 1) != '"') --start;
         //console.log(cur.ch+ " START "+start+" END "+end);
         var list = [];
         var close = 0, end_close = 0;
         var open_tag = "";
         range_start = start;
 
         if (start != end) {
             tables = get_table_and_alias(editor.getValue());
             dbp_tables_used = tables[0];
             dbp_alias_table = tables[1];
             var curWord = curLine.slice(start, end);
             if (curWord.length >= 1) {
                // le tabelle
                for (let k in dbp_tables) {
                    if (('`' + dbp_tables[k] + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) {
                        list.push('`' + dbp_tables[k] + '`');
                    } else if (dbp_tables[k].substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) {
                        list.push('`' + dbp_tables[k] + '`');
                    }
                }

                 // i comandi sql
                 for (let k in dbp_cm_variables) {
                    if (dbp_cm_variables[k].substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) {
                        list.push(dbp_cm_variables[k]);
                    }
                }
                
                 // le colonne negli alias
                for (let alias in dbp_alias_table) {
                    if (typeof (dbp_columns[dbp_alias_table[alias]]) != 'undefined') {
                        let columns_used = dbp_columns[dbp_alias_table[alias]];
                        for (let k2 in columns_used) {
                            if ((('`' + alias + '`.' + columns_used[k2]).substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1 ) || ((alias + '.' + columns_used[k2]).substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1 ) || ((alias + '.`' + columns_used[k2] + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1 ) || (('`' + alias + '`.`' + columns_used[k2] + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1) || (( alias + '.' + columns_used[k2]).substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1)) {
                                if (list.indexOf('`' + alias + '`.`' + columns_used[k2] + '`') == -1) {
                                    list.push('`' + alias + '`.`' + columns_used[k2] + '`');
                                }
                            } else if (('`' + alias + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) {
                                if (list.indexOf('`' + alias + '`') == -1) {
                                    list.push('`' + alias + '`');
                                }
                            } else if (columns_used[k2].substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) {
                                if (list.indexOf('`' + alias + '`') == -1) {
                                    list.push('`' + alias + '`');
                                }
                            }
                        }

                    }
                }


                 // le colonne
                 for (let k in dbp_tables_used) {
                    if (typeof (dbp_columns[dbp_tables_used[k]]) != 'undefined') {
                        let columns_used = dbp_columns[dbp_tables_used[k]];
                        for (let k2 in columns_used) {
                            if ((('`' + dbp_tables_used[k] + '`.' + columns_used[k2] ).substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1) || (( dbp_tables_used[k] + '.' + columns_used[k2] ).substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1) || (( dbp_tables_used[k] + '.`' + columns_used[k2] + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1) 
                            || (('`' + dbp_tables_used[k] + '`.`' + columns_used[k2] + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1)  || (( dbp_tables_used[k] + '.' + columns_used[k2] + '').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase() && curWord.length > 1)) {
                                list.push('`' + dbp_tables_used[k] + '`.`' + columns_used[k2] + '`');
                            } else if ((('`' + columns_used[k2] + '`').substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) || columns_used[k2].substring(0, curWord.length).toLowerCase() == curWord.toLowerCase()) {
                                list.push('`' + columns_used[k2] + '`');
                            }
                        }

                    }
                }

             }
         }
 
         if (list.length == 1) {
             if (curWord == list[0]) {
                 list = [];
             }
         }
         return { list: list, from: wp.CodeMirror.Pos(cur.line, start), to: wp.CodeMirror.Pos(cur.line, end) };
    });
 
    
 });

 function get_table_and_alias(text) {
    text = text.replaceAll(/[\n\r\t]/gm, ' ');
    var startTime = performance.now();
    dbp_alias_table = {};
    dbp_tables_used = [];
    let words = text.split(" ");
    words.pop();
    for (x in words) {
        words[x] = words[x].replaceAll("`", '');
        if ( words[x].indexOf('.') > -1) continue;
        x = parseInt(x);
        if (dbp_tables.indexOf(words[x]) > -1) {
            if (dbp_tables_used.indexOf(words[x]) == -1) {
                dbp_tables_used.push(words[x]);
            }
            if ((x+1) <= words.length && typeof(words[x + 1]) != 'undefined') {
                words[x + 1] =  words[x + 1].replaceAll("`", '').replaceAll(",", '');
                if (words[x+1].toLowerCase() == "as") {
                    if (x+2 <= words.length && typeof(words[x + 2]) != 'undefined') {
                        if (dbp_cm_variables.indexOf(words[x+2].toUpperCase()) == -1) {
                            words[x + 2] =  words[x + 2].replaceAll("`", '');
                            dbp_alias_table[words[x+2]] = words[x];
                        }
                    }
                } else if (dbp_cm_variables.indexOf(words[x+1].toUpperCase()) == -1) {
                    dbp_alias_table[words[x+1]] = words[x];
                    
                } 
            }
        }
    }
   // let endTime = performance.now();
    return ([dbp_tables_used, dbp_alias_table]);
   
 }

/**
 * I BOTTONI DELLE AZIONI DELLA QUERY TIPO ORGANIZE FIELDS ecc..
 */

 
/**
 * Chiamo un ajax che risponde tutte le possibili colonne della query che si sta visualizzando e quelle visualizzate.
 */
 function dbp_columns_sql_query_edit() {
    if (jQuery('#dbp-bnt-columns-query').hasClass('js-btn-disabled')) return;
    dbp_open_sidebar_popup('columns_sql');
    jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>ORGANIZE COLUMNS</h3>  <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
    if (jQuery('#dbp_cookie_msg').length > 0) {
        jQuery('#dbp_cookie_msg').empty().css('display','none');
    }
    sql = dbp_get_sql_string();
    if ( sql != "") {
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : {action:'dbp_columns_sql_query_edit',sql:sql},
            success: function(response) {
                dbp_close_sidebar_loading();
                if (!response.msg) { 
                    jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
                    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>ORGANIZE COLUMNS</h3><div id="dbp-bnt-edit-query" class="dbp-submit" onclick="columns_sql_query_apply()">Apply</div> <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
                    $form = jQuery('<form class="dbp-form-columns-query" id="dbp_form_columns_new_query"></form>');
                    $custom_query = jQuery('<textarea style="display:none" class="form-input-edit" name="sql" ></textarea>');
                    $custom_query.val(sql);
                    $form.append($custom_query);
                    $form.append('<p class="dbp-alert-info" style="margin-bottom:1rem">Select the columns you want to see and give them a custom name if you want.</p>');
                    let count_xxx = 0;
                    $sortable = jQuery('<div class="js-dragable-table"></div>')
                    for (x in response.all_fields) {
                        let checked = "";
                        let val_label = "";
                        if (x in response.sql_fields) {
                            checked = ' checked="checked"';
                            val_label = response.sql_fields[x];
                        }
                        $checkbox = jQuery('<div class="dbp-dropdown-line-flex js-dragable-item dbp-line-choose-columns"><span class="dbp-sort-choose-columns dashicons dashicons-sort js-dragable-handle"></span><label><span style="vertical-align:middle;margin-left:.5rem;"><input name="choose_columns['+count_xxx+']" type="checkbox" value="" style="vertical-align:middle"'+checked+'></span><div style="width:250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display:inline-block; margin:.2rem .5rem; vertical-align:middle" class="js-column-name-o">'+x+'</div></label> AS&nbsp;&nbsp;&nbsp; <input class="label_as" type="text" name="label['+count_xxx+']" ></div>');
                        $checkbox.find('input:checkbox').val(x);
                        $checkbox.find('.label_as').val(val_label);
                        $checkbox.find('.js-column-name-o').prop('title', x);
                        $sortable.append($checkbox); 
                        count_xxx++;
                    }
                    $form.append($sortable);
                    jQuery('#dbp_dbp_content').append($form);
                
                    jQuery('.js-dragable-table').sortable({
                        items: '.js-dragable-item',
                        opacity: 0.5,
                        cursor: 'move',
                        axis: 'y',
                        handle: ".js-dragable-handle"
                    });
                } else {
                    jQuery('#dbp_dbp_content').append('<p class="dbp-alert-sql-error" style="margin-bottom:1rem">'+response.msg+'</p>');
                }

            }
        });
    } else {
        alert ("sql is empty");
    }
 }


/**
  * Apre la sidebar per creare un merge con un'altra tabella
  */
function dbp_merge_sql_query_edit() {
    if (jQuery('#dbp-bnt-merge-query').hasClass('js-btn-disabled')) return;
    dbp_open_sidebar_popup('merge_sql');
    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>MERGE QUERY</h3> <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
    sql = dbp_get_sql_string();
    if (jQuery('#dbp_cookie_msg').length > 0) {
        jQuery('#dbp_cookie_msg').empty().css('display','none');
    }
    if (sql != "") {
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : {action:'dbp_merge_sql_query_edit',sql:sql},
            success: function(response) {
                dbp_close_sidebar_loading();
                if (!response.msg) {
                    jQuery('#dbp_dbp_title > .dbp-edit-btns').remove();
                    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>MERGE QUERY</h3> <div id="dbp-bnt-edit-query" class="dbp-submit" onclick="merge_sql_query_apply()">Apply</div> <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
                    $form = jQuery('<form class="dbp-form-merge-query" id="dbp_form_merge_new_query"></form>');
                    $custom_query = jQuery('<textarea style="display:none" class="form-input-edit" name="sql" ></textarea>');
                    $custom_query.val(sql);
                    $form.append($custom_query);
                    $form.append('<p class="dbp-alert-gray" style="margin-bottom:1rem">A join is a method of linking data between one ( self-join) or more tables based on values of the common column between the tables.</p>');
                    
                    $field_row = jQuery('<div class="dbp-dropdown-line-flex dbp-form-row"></div>');
                    $field_label = jQuery('<label class="dbp-form-label">Select a join column</label>');
                    $field_select = jQuery('<select name="dbp_ori_field"></select>');
                    $field_row.append($field_label).append($field_select);
                    $form.append($field_row);
                    for (x in response.all_fields) {
                        $option = jQuery('<option></option>');
                        $option.text(response.all_fields[x]);
                        $option.val(x);
                        $field_select.append($option);
                    }

                    $form.append('<div class="dbp-dropdown-line-flex dbp-form-row" style="display:none"><label class="dbp-form-label">Join Method</label><select id="dbp_merge_join" name="dbp_merge_join" onchange="explain_join_in_merge(this)"><option value="LEFT JOIN">left join</option></select></div><div id="dbp_explain_join" class="dbp-alert-gray" style="display:none"></div>');


                    $field2_row = jQuery('<div class="dbp-dropdown-line-flex dbp-form-row"></div>');
                    $field2_label = jQuery('<label class="dbp-form-label">Related table</label>');
                    $field2_select = jQuery('<select name="dbp_merge_table"></select>');

                    $field2_row.append($field2_label).append($field2_select);
                    $form.append($field2_row);
                    //console.log (response.all_tables);
                    for (x in response.all_tables) {
                        $option = jQuery('<option></option>');
                        $option.text(response.all_tables[x]);
                        $option.val(x);
                        $field2_select.append($option);
                    }
                    $field2_select.change(function() {
                        get_fields_in_merge_sidebar(this);
                    })


                    $form.append('<div class="dbp-dropdown-line-flex dbp-form-row"><label class="dbp-form-label">Column to be connected</label><select id="dbp_merge_columns"  name="dbp_merge_column"></select></div>');
                    jQuery('#dbp_dbp_content').append($form);
                    jQuery('#dbp_merge_join').change();
                    $field2_select.change();
                } else {
                    jQuery('#dbp_dbp_content').append('<p class="dbp-alert-sql-error" style="margin-bottom:1rem">'+response.msg+'</p>');
                }

            }
        });
    } else {
        alert("SQL is empty")
    }
}

/**
 * Trova le colonne di una tabella quando stai facendo il merge viene richiamato ogni volta che cambi il select con l'elenco delle tabelle.
 */
function get_fields_in_merge_sidebar(el_select) {
    jQuery('#dbp_merge_columns').empty();
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        cache: false,
        data : {action:'dbp_merge_sql_query_get_fields',table:jQuery(el_select).val()},
        success: function(response) {
            //console.log (response.all_columns);
            for (x in response.all_columns) {
                $option = jQuery('<option></option>');
                $option.text(response.all_columns[x]);
                $option.val(response.all_columns[x]);
                jQuery('#dbp_merge_columns').append($option);
            }
            
        }
    });
}

/**
 * Mostra la spiegazione dei tre join
 * @deprecated V0.9.1 è stato rimosso il tipo di join del merge nella form di scelta del tipo di merge
 */
function explain_join_in_merge(el) {
    let desc = {'LEFT JOIN':'LEFT JOIN: Returns all rows from the left table and matched records from the right table or returns null if it does not find any matching record.','RIGHT JOIN':'RIGHT JOIN: Returns all rows from the right-hand table, and only those results from the other table that fulfilled the join condition','INNER JOIN':'INNER JOIN: Returns only the matching results from table1 and table2:'};
    jQuery('#dbp_explain_join').empty().html(desc[jQuery(el).val()]);
}

/**
 * Invio i dati per il merge delle tabelle e ricevere la nuova query
 */
function merge_sql_query_apply() {
    if (jQuery('#dbp_merge_columns').val() == null) {
        alert ("You have to choose a table and a column to link");
        return;
    }
    dbp_open_sidebar_loading();
    jQuery('#dbp-query-box').empty().append('<div class="dbp-sidebar-loading"><div  class="dbp-spin-loader"></div></div>');
    var data = jQuery('#dbp_form_merge_new_query').serializeArray() ;
    data.push({name: 'action', value: 'dbp_edit_sql_query_merge'});
    data.push({name: 'section', value: 'table-browse'});
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        cache: false,
        data : data,
        success: function(response) {
            if (response.msg) {
                alert(response.msg);
                dbp_close_sidebar_loading();
            } else {
                if (response.html != "") {
                    jQuery('#dbp-query-box').empty().append(response.html);
                    dbp_close_sidebar_popup();
                    if (jQuery('#dbp_cookie_msg').length > 0) {
                        jQuery('#dbp_cookie_msg').empty().append('The query has been updated press the "go" button to execute it.').css('display','block');
                    }
                } else {
                    dbp_close_sidebar_loading();
                }
            } 
            jQuery('#result_query').html(query_color(jQuery('#result_query').text()));
            
            check_toggle_sql_query_edit();
            
        }
    });
}

/**
 * Invia la query con i select cambiati ad una funzione ajax che ricrea la query
 */
function columns_sql_query_apply() {
    var data = jQuery('#dbp_form_columns_new_query').serializeArray() ;
    data.push({name: 'action', value: 'dbp_edit_sql_query_select'});
    data.push({name: 'section', value: 'table-browse'});
    dbp_open_sidebar_loading();
    // TODO: se ritorna un errore ho il box con le textarea e l'editor vuoto!
    jQuery('#dbp-query-box').empty().append('<div class="dbp-sidebar-loading"><div  class="dbp-spin-loader"></div></div>');
    jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxurl,
        cache: false,
        data : data,
        success: function(response) {
            if (response.html != "" && !response.msg) {
                jQuery('#dbp-query-box').empty().append(response.html);
            } else if ( response.msg != ""){
                alert(response.msg);
            }
            dbp_close_sidebar_popup();
            jQuery('#result_query').html(query_color(jQuery('#result_query').text()));
            // i trigger qui non sono riuscito a farli funzionare !?!?!
            if (typeof dbp_list_structure_query_apply === "function") {
                dbp_list_structure_query_apply();
            } 
            if (jQuery('#dbp_cookie_msg').length > 0) {
                jQuery('#dbp_cookie_msg').empty().append('The query has been updated press the "go" button to execute it.').css('display','block');
            }
            check_toggle_sql_query_edit();
        }
    });
}

/**
 * Il bottone per l'aggiunta dei metadata
 * Sistema più automatico possibile!
 * prima seleziono la tabella dei metadata. 
 * Dall'elenco delle tabelle delle query cerco le tabelle dei metadati attraverso il nome
 * nome_tabella_al_singolare+meta
 * La tabella trovata deve avere i 4 campi già defini: chiave primaria meta_id, tabella_al_singolare +_id meta_key, meta_value. 
 * Se non trovo tabelle spiego in dettaglio il motivo 
 * e chiedo se si vuole creare una tabella meta per una delle tabelle selezionate.
 * 
 */
function dbp_metadata_sql_query_edit() {
    if (jQuery('#dbp-bnt-metadata-query').hasClass('js-btn-disabled')) return;
    sql = dbp_get_sql_string();
    if (jQuery('#dbp_cookie_msg').length > 0) {
        jQuery('#dbp_cookie_msg').empty().css('display','none');
    }
    if ( sql != "") {
        dbp_open_sidebar_popup('add_metadata');
        jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>ADD META DATA</h3>  <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : {action:'dbp_metadata_sql_query_edit', sql:sql},
            success: function(response) {
                dbp_close_sidebar_loading();
                $form = jQuery('<form class="dbp-form-addmeta-query" id="dbp_form_addmeta_new_query"></form>');
                let count_obj = 0;
                jQuery('#dbp_dbp_content').append($form);
                if (response.msg) {
                    $form.append('<p class="dbp-alert-sql-error">'+response.msg+'</p>');  
                } else {
                    $field_row = jQuery('<div class="dbp-dropdown-line-flex dbp-form-row"></div>');
                    $field_label = jQuery('<label class="dbp-form-label">Metadata table</label>');
                    $field_select = jQuery('<select name="dbp_meta_table" id="dbp_meta_table" onchange="dbp_metadata_sql_query_edit_step2()"></select>');
                    
                    $field_row.append($field_label).append($field_select);
                    $form.append($field_row);
                    for (x in response.all_tables) {
                        $option = jQuery('<option></option>');
                        $option.text(response.all_tables[x]);
                        $option.val(x);
                        $field_select.append($option);
                        count_obj++;
                    }
                    if (count_obj >= 1) {
                        // step 2 estraggo le colonne
                        dbp_metadata_sql_query_edit_step2();
                    }
                }
            }
        });
    } else {
        alert ("SQL is empty");
    }
}

/**
 * Estraggo i campi del metadata
 */
function dbp_metadata_sql_query_edit_step2() {
    sql = dbp_get_sql_string();
    if ( sql != "") {
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : {action:'dbp_metadata_sql_query_edit_step2', table2:jQuery('#dbp_meta_table').val(), sql:sql},
            success: function(response) {
                let count_xxx = 0;
                if (response.distinct.length > 0) {
                    jQuery('#dbp_dbp_title .dbp-edit-btns').empty();
                    jQuery('#dbp_dbp_title .dbp-edit-btns').append('<h3>ADD META DATA</h3><div id="dbp-bnt-edit-query" class="dbp-submit" onclick="dbp_addmeta_sql_query_apply()">Apply</div> <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div>');
                }
                jQuery('#container_metadata').remove();
                $container = jQuery('<div id="container_metadata"></div>');
                //console.log (response.selected);
                for (x in response.distinct) {
                    if (response.distinct[x] == "") continue;
                    checked = "";
                    if (response.selected.indexOf(response.distinct[x]) > -1) {
                        checked = ' checked="checked"';
                    }
                    $checkbox = jQuery('<div class="dbp-dropdown-line-flex dbp-line-choose-columns"><label><span style="vertical-align:middle;margin-left:.5rem;"><input name="choose_meta['+count_xxx+']" type="checkbox" style="vertical-align:middle"'+checked+'></span><div style="width:250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display:inline-block; margin:.2rem .5rem; vertical-align:middle">'+response.distinct[x]+'</div></label> <input name="altreadychecked_meta['+count_xxx+']" type="hidden" class="already_checked"></div>');
                    $checkbox.find('input:checkbox').val(response.distinct[x]);
                    if (response.selected.indexOf(response.distinct[x]) > -1) {
                        $checkbox.find('input:hidden.already_checked').val(response.distinct[x]);
                    }
                    $container.append($checkbox); 
                    count_xxx++;
                }
                $pri = jQuery('<input name="pri_key" type="hidden">');
                $pri.val(response.pri)
                $parent_id = jQuery('<input name="parent_id"  type="hidden">');
                $parent_id.val(response.parent_id);
                $container.append($pri).append($parent_id);
                jQuery('#dbp_form_addmeta_new_query').append($container);
            }
        });
    } else {
        console.error('dbp_metadata_sql_query_edit_step2: sql is empty!')
    }
}

/**
 * Invia i dati per l'aggiunta nella query dei metadata
 */
function dbp_addmeta_sql_query_apply() {
    sql = dbp_get_sql_string();
    if ( sql != "") {
        var data = jQuery('#dbp_form_addmeta_new_query').serializeArray() ;
        data.push({name: 'action', value: 'dbp_edit_sql_addmeta'});
        data.push({name: 'section', value: 'table-browse'});
        data.push({name: 'sql', value: sql });
        dbp_open_sidebar_loading();
        jQuery('#dbp-query-box').empty().append('<div class="dbp-sidebar-loading"><div  class="dbp-spin-loader"></div></div>');
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : data,
            success: function(response) {
                if (response.sql) {
                    if (response.html != "") {
                        jQuery('#dbp-query-box').empty().append(response.html);
                    }
                    dbp_close_sidebar_popup();
                    jQuery('#result_query').html(query_color(jQuery('#result_query').text()));
                    check_toggle_sql_query_edit();
                    if (jQuery('#dbp_cookie_msg').length > 0) {
                        jQuery('#dbp_cookie_msg').empty().append('The query has been updated press the "go" button to execute it.').css('display','block');
                    }
                }
            }
        });
    } else {
        console.error('dbp_addmeta_sql_query_apply: sql is empty!')
    }
}


/**
 * FINE BOTTONI DELLE AZIONI DELLA QUERY TIPO ORGANIZE FIELDS ecc..
 */

/**
 * Carico una ajax che verifica la query che si sta inserendo
 */
function dbp_analyze_query() {
    sql = dbp_get_sql_string();
    if (sql.length > 7) {
        if (dbp_sql_check != sql) {
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : ajaxurl,
                cache: false,
                data : {action:'dbp_check_query', sql:sql},
                success: function(response) {
                // console.log ('dbp_analyze_query:');
                //  console.log (jQuery('.js-result-query-btns .js-show-only-select-query'));
                jQuery('#dbp_sql_error_show').empty().addClass('dbp-hide');

                    if (response.error != "") {
                        if (response.error.indexOf('You have an error in your SQL syntax') == -1 ) {
                            jQuery('#dbp_sql_error_show').removeClass('dbp-hide').append(response.error);
                        }
                        jQuery('.js-result-query-btns .js-show-only-select-query').addClass('dbp-btn-disabled js-btn-disabled');
                    } else {
                        if (response.is_select == 1) {
                            jQuery('.js-result-query-btns .js-show-only-select-query').removeClass('dbp-btn-disabled js-btn-disabled');
                        }
                        if (response.is_select == 0) {
                            jQuery('.js-result-query-btns .js-show-only-select-query').addClass('dbp-btn-disabled js-btn-disabled');
                        }
                    }
                }
            });
        } 
        dbp_sql_check = sql;
    }
}

/**
 * Ritorna la query che si sta scrivendo
 * @return string 
 */
function dbp_get_sql_string() {
    let sql = "";
    if ( document.getElementById('sql_query_edit') != null) {
        code = document.getElementById('sql_query_edit').dbp_editor_sql;
        if (typeof code != 'undefined' && code != null) {
            sql = code.codemirror.getValue();
        } else {
            sql = jQuery('#sql_query_executed').val();
        }
    } else {
        return '';
    }
    return sql;
}




/**
  * Apre la sidebar per la ricerca
  */
 function dbp_search_sql() {
    if (jQuery('#dbp-bnt-merge-query').hasClass('js-btn-disabled')) return;
    dbp_open_sidebar_popup('search_sql');
    dbp_close_sidebar_loading();
    jQuery('#dbp_dbp_title').append('<div class="dbp-edit-btns"><h3>SEARCH IN QUERY</h3>  <div class="dbp-submit" onclick="dbp_submit_search()" style="margin-left:.2rem;">Search</div> <div id="dbp-bnt-edit-query" class="dbp-btn-cancel" onclick="dbp_close_sidebar_popup()">CANCEL</div></div>');
    
    $search_form = jQuery('<div class="dbp-form-columns-query dbp-dropdown-line-flex" id="dbp_form_search"></div>');
    $search_input = jQuery('<input type="search" name="search" id="dbp_search_input">');
    $search_input.val(jQuery('#dbp_original_search').val());
    $search_submit = jQuery('<div class="button" onclick="dbp_submit_search()" style="margin-left:.2rem;">Search</div>');
    $search_form.append('<label class="dbp-form-label"><span style="width:150px;display:inline-block; margin:.2rem .5rem; vertical-align:middle">Search</span></label>').append($search_input).append($search_submit);
    
    $replace_form =  jQuery('<div class="dbp-form-columns-query dbp-dropdown-line-flex"><label class="dbp-form-label"><span style="width:150px; display:inline-block; margin:.2rem .5rem; vertical-align:middle">Replace</span></label> <textarea name="replace" id="dbp_replace_textarea" style="width:240px;"></textarea></div>');
    $replace_btn = jQuery('<div class="dbp-dropdown-line-flex js-dragable-item dbp-line-choose-columns"><div style="width:168px"></div><div class="button" style="margin-right:.2rem" onclick="dbp_submit_test_replace()">Test Replace</div> <div class="button" onclick="dbp_submit_search_and_replace(0,0,0)">Replace</div></div>');
     

    jQuery('#dbp_dbp_content').append($search_form).append('<hr>').append($replace_form).append($replace_btn);

    jQuery('#dbp_dbp_content').append('<div id="dbp_sql_replace_test_result" style="zoom: .8; margin: 1rem;"></div>');
}

function dbp_submit_search() {
   jQuery('#dbp_original_search').val(jQuery('#dbp_search_input').val());
   dbp_submit_table_filter('search');
}

function dbp_submit_test_replace() {
    sql = dbp_get_sql_string();
    if ( sql != "") {
        //dbp_open_sidebar_loading();
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : {action:'dbp_sql_test_replace',sql:sql,search:jQuery('#dbp_search_input').val(), replace:jQuery('#dbp_replace_textarea').val() },
            success: function(response) {
               // console.log ("RESPONSE: ".response);
                jQuery('#dbp_sql_replace_test_result').empty().append(response.html);
                
            }
        });
    }
}

function dbp_submit_search_and_replace(limit_start, row_replaced, total) {
    sql = dbp_get_sql_string();
    if ( sql != "") {
        //dbp_open_sidebar_loading();
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            cache: false,
            data : {action:'dbp_sql_search_replace',sql:sql,search:jQuery('#dbp_search_input').val(), replace:jQuery('#dbp_replace_textarea').val(),limit_start:limit_start, row_replaced:row_replaced, total:total},
            success: function(response) {
                // console.log ("RESPONSE: ".response);
                if (response.executed < response.total) {
                    jQuery('#dbp_sql_replace_test_result').empty().append("<p>Executed: " + response.executed+" / "+response.total+" Replaced occurrences: "+response.replaced+"</p>");
                    dbp_submit_search_and_replace( response.executed, response.replaced,  response.total);
                } else {
                    jQuery('#dbp_sql_replace_test_result').empty().append("<p>Executed: " + response.total + " / " + response.total + " Replaced occurrences: " + response.replaced + "</p>");
                    jQuery('#dbp_sql_replace_test_result').append('<br><br><div id="dbp-bnt-go-query" class="dbp-submit" onclick="dbp_submit_table_filter(\'custom_query\')">Show updated data</div>');
                }     
            }
        });
    }
}