<?php 

/**
 * Tutte le funzioni che servono per gestire le importazioni
 *
 * @package  DbPress
 */

namespace DbPress;

class  Dbp_fn_import {
    
    /**
     * @var string $import_sql_file_msg eventuali messaggi d'errore dell'importazione sql
     */
    static $import_sql_file_msg = '';
   
    /**
     * Converte i dati della form dell'import csv in un array usabile
     * @param  Array $import_tables [uniqid:table_name, ...]
     * @param Array $import_field [uniqid:[field,field, ...], ...]
     * @param Array $import_csv_column [uniqid:[field,field, ...], ...]
     * @return Array [uniqid:{table:String, fields:[]}, ...]
     */
    static function convert_csv_data_request_to_vars($import_tables, $import_field, $import_csv_column) {
        $table_insert = [];
        foreach ($import_tables as $uniqid_key=>$table) {
			$temp_fields = [];
			foreach ($import_field[$uniqid_key] as $if_key => $ifd) {
				$temp_fields[$ifd] = $import_csv_column[$uniqid_key][$if_key];
			}
			$table_insert[$uniqid_key] = (object)['table'=>$table,'fields'=>$temp_fields];
		}
        return $table_insert;
    }

    /**
     * Estrae gli id da un csv a partire dalla primary id della tabella su cui importare i dati
     * @param  Array  $csv_items 
     * @param  String $primary_key
     * @param  Array  $fields è i fields risultanti da convert_csv_data_request_to_vars
     * @return Array
     */
    static function get_ids($csv_items, $primary_key, $fields) {
        $ids = [];
        if (array_key_exists($primary_key,$fields )) {
            $csv_pri = $fields[$primary_key];
            foreach ($csv_items as $item) {
                PinaCode::set_var('item', $item) ;
                $val = PinaCode::execute_shortcode(wp_unslash($csv_pri));
                if ($val > 0) {
                    $ids[] = "'".esc_sql(absint($val))."'";
                }
                
            }
        }
        $ids = array_unique($ids);
        return $ids;
    }

    /**
     * Crea una tabella temporanea e la popola
     * @return String|Boolean
     */
    static function create_temporaly_table_from($ti, $csv_items, $primary_key) {
        global $wpdb;
        if (!current_user_can('administrator')) return false;
        $table_temp = substr(dbp_fn::clean_string($ti->table),0,57)."__temp";
        $r = $wpdb->query('CREATE TEMPORARY TABLE IF NOT EXISTS `'.Dbp_fn::sanitize_key($table_temp).'` LIKE `'.Dbp_fn::sanitize_key($ti->table).'`;');       
        $ids = dbp_fn_import::get_ids($csv_items, $primary_key, $ti->fields);
        $ids[] = "'".absint($wpdb->get_var('SELECT `'.Dbp_fn::sanitize_key($primary_key).'` FROM `'.Dbp_fn::sanitize_key($ti->table).'` ORDER BY `'.esc_sql($primary_key).'` DESC LIMIT 1'))."'";

        if ($r) {
            if (count ($ids) > 0) {     
                $wpdb->query('INSERT INTO `'.Dbp_fn::sanitize_key($table_temp).'` SELECT * FROM `'.esc_sql($ti->table).'` WHERE `'.Dbp_fn::sanitize_key($primary_key).'` IN ('.implode(",", $ids).');');
            }
            return $table_temp ;
        } else {
            return false;
        }
    }

    /**
     * Verifica se un record è da inserire oppure da aggiornare e lo inserisce o aggiorna se e solo se deve essere aggiornato/inserito un solo record
     * In caso che il where ritorni più di un record allora la funzione non esegue nulla!
     * @param String $table
     * @param Array $data
     * @param String $primary  (e autoincrement)
     * @return Array ['row':Object,'result':Boolean,'error':String]
     */
    static function wpdb_replace($table, $data, $primary_key) {
        global $wpdb;
        if (!current_user_can('administrator')) return ['result'=>false,'error'=>'Permission'];
        $result = false;
        $sql_where = '';
        $exist_record = [];
        $old_row = [];
        $query = "";
        $error = "";
        $new_row = [];
        $where = [];
        $action = "";
        $load_id = 0;
        $update = [];
        foreach ($data as $field=>$value) {
            if ($field == $primary_key) {
                $sql_where = "`".Dbp_fn::sanitize_key($primary_key)."` = '".esc_sql($value)."'";
                $where[Dbp_fn::sanitize_key($primary_key)] = $value;
            } else {
                $update[Dbp_fn::sanitize_key($field)] = $value;
            }
        }       
        $ris = $wpdb->get_results('SELECT * FROM `'.Dbp_fn::sanitize_key($table).'`');
        if ($sql_where != "") {
            $exist_record = $wpdb->get_results('SELECT * FROM `'.Dbp_fn::sanitize_key($table).'` WHERE '.$sql_where." LIMIT 10");
        }
        if (count($exist_record) == 1) {
            // https://core.trac.wordpress.org/ticket/32315 se i valori sono più lunghi della query ritorna sbaglia gli errori!
            $result = $wpdb->update($table, $update, $where);
            $query = $wpdb->last_query;
            $error = $wpdb->last_error ;
            $load_id = reset($where);
            $old_row = reset($exist_record);
            $action = "update";
        } else if (count($exist_record) == 0) {
            $old_row = [];
            // https://core.trac.wordpress.org/ticket/32315 
            $result = $wpdb->insert($table, $data);
            $query = $wpdb->last_query;
            $error = $wpdb->last_error ;
            $load_id = $wpdb->insert_id;
            $action = "insert";

        } else {
           return ['old_row'=>'', 'row'=>'','result'=>false, 'error'=> __('The query does not return a unique value', 'db_press'), 'action'=>'' ] ;
        }
        if ( $load_id > 0) {
            $new_row = $wpdb->get_row('SELECT * FROM `'.Dbp_fn::sanitize_key($table).'` WHERE `'.Dbp_fn::sanitize_key($primary_key).'` = \''.esc_sql( $load_id )."'");
        }
        return ['old_row'=>$old_row,  'row'=>$new_row, 'data'=>$data, 'result'=>$result, 'sql'=> $query,  'error'=>   $error, 'id'=>$load_id, 'action'=> $action ] ;
       
    }

    /**
     * Importa un file SQL
     * @return boolean
     */
    static function import_sql_file($file_path) {
        self::$import_sql_file_msg = "";
		if (!current_user_can('administrator')) return false;
        $file_path = str_replace(["..","\n","'",'"',")"], "", $file_path);
        $errors = [];
        $msg = "";
		if (is_file($file_path)) {
           
            $sql = file_get_contents($file_path);
            $mysqli = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die ('DB CONNECTION ERROR?!');
            $mysqli->multi_query($sql);
            //Make sure this keeps php waiting for queries to be done
            $count_query = 0;
            do {
                /* store the result set in PHP */
                if ($result = $mysqli->store_result()) {
                    while ($row = $result->fetch_row()) {
                        $errors[] = $row[0] ;
                    }
                }
                $count_query++;
            } while ($mysqli->next_result());

            if (is_countable($mysqli->error_list) && count($mysqli->error_list) > 0) {
                foreach ($mysqli->error_list as $el) {
                    $errors[] = $el['error'];
                }
            } else {
                $msg = sprintf(__('%s queries executed successfully.', 'db_press'), $count_query );
            }
            $mysqli->close();	
            
			dbp_fn::get_table_list(false);
		} else {
            self::$import_sql_file_msg = __("file doesn't exists", 'db_press');
            return false;
        }
        if (count($errors) == 0) {
            self::$import_sql_file_msg = $msg;
            return true;
        } else {
            self::$import_sql_file_msg = implode("\n", $errors);
		    return false;
        }
	}
}