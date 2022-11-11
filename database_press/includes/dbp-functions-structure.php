<?php 

/**
 * Le funzioni per il tab structure

 *
 * @package   db-press
 */

namespace DbPress;


class  Dbp_fn_structure {
    /**
     * Dalla query che estrae le informazioni delle colonne sul mysql alle informazioni mostrate sulla form
     */
    static function convert_show_column_mysql_row_to_form_data($column) {
        $new_column = (object)[];
        $new_column->field_name = $column->Field;
        list($new_column->field_type, $new_column->field_length, $new_column->attributes) = dbp_model_structure::reverse_type($column->Type);
        if ($column->Null == 'NO') {
            $new_column->null = "f";
        } else {
            $new_column->null = "t";
        }
        if ($column->Default != "NULL") {
            $new_column->default  = $column->Default;
        } else {
            $new_column->default = $column->Default;
        }
        if ($column->Key == "PRI" ) {
            $new_column->primary = "t";
        }  else {
            $new_column->primary = "f";
        }
        if ($column->Extra == "auto_increment") {
            $new_column->auto_increment = "t";
        } else {
            $new_column->auto_increment = "f";
        }
        if ($column->Key == "PRI" && $column->Extra == "auto_increment") {
            $new_column->dbp_primary = "t";
        } else {
            $new_column->dbp_primary = "f";
        }
        $new_column->preset= self::get_preset($new_column);
        return $new_column;
    }

    /**
     * Crea una tabella temporanea e la popola
     * @return String|Boolean
     */
    static function create_temporaly_table_from($table) {
        global $wpdb;
        if (!current_user_can('administrator')) return false;
        $table_temp = substr(dbp_fn::clean_string($table),0,56)."__ctemp";
        $r = $wpdb->query('CREATE TEMPORARY TABLE IF NOT EXISTS `'.esc_sql($table_temp).'` LIKE `'.esc_sql($table).'`;');       
        if ($r) {
            $wpdb->query('INSERT INTO `'.esc_sql($table_temp).'` SELECT * FROM `'.esc_sql($table).'` ORDER BY RAND() LIMIT 1000;');
            return $table_temp ;
        } else {
            return false;
        }
    }

    /**
     * Cancella una tabella temporanea
     */
    static function drop_temporaly_table($table) {
        global $wpdb;   
        $wpdb->query('DROP TABLE IF EXISTS `'.esc_sql($table).'`;');
    }

    /**
     * Carico tutti i dati della tabella temporanea
     */
    static function load_rows($table, $primary) {
        global $wpdb;   
        $values = $wpdb->get_results('SELECT * FROM `'.$table.'` ORDER BY `'.$primary.'` ASC LIMIT 1000');
        $result = [];
        foreach ($values as $v) {
            if (isset($v->$primary)) {
                $result[$v->$primary] = $v;
            }
        }
        return $result;
    }

    /**
     * Ripulisce una stringa dai caratteri speciali
     * @param String $str
     * @return String
     */
    static function clean_column_name($str) {
        $str = str_replace(' ', '_', $str); 
        $str = preg_replace('/[^A-Za-z0-9\-_]/', '_', $str);
        $str = str_replace(["____","___","__"], "_", $str);
        if (strlen($str) > 65) {
            $str = substr($str,0,64);
        }
        return $str;
    }

    /**
     * Trova il preset a partire da una configurazione
     * @return String
     */
    public static function get_preset($new_column) {
        if (!isset($new_column->auto_increment)) {
            $new_column->auto_increment = "f";
        }
        if (!isset($new_column->default)) {
            $new_column->default = "";
        }
        if ($new_column->field_type == "INT" && $new_column->auto_increment == "t" && $new_column->primary = "t") {
            return "pri";
        }
        if ($new_column->field_type == "VARCHAR" && $new_column->field_length == "255" && $new_column->attributes == "" && $new_column->null == "f" && $new_column->default == "") {
            return "varchar";
        }
        if ($new_column->field_type == "TEXT" && $new_column->field_length == "" && $new_column->attributes == "" && $new_column->null == "f"  && $new_column->default == "") {
            return "text";
        }
        if ($new_column->field_type == "INT" && $new_column->attributes == "" && $new_column->null == "f") {
            return "int_signed";
        }
        if ($new_column->field_type == "DECIMAL" && $new_column->field_length == "9,2" && $new_column->attributes == "" && $new_column->null == "f" && $new_column->default == "") {
            return "decimal";
        }
        if ($new_column->field_type == "DATE" && $new_column->field_length == "" && $new_column->attributes == "" && $new_column->null == "f"  && $new_column->default == "") {
            return "date";
        }
        if ($new_column->field_type == "DATETIME" && $new_column->field_length == "" && $new_column->attributes == "" && $new_column->null == "f") {
            return "datetime";
        }
      
        return 'advanced';
    }

}