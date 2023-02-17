<?php 
/**
 * Tutte le funzioni che servono per gestire il plugin
 * 
 * @package  DbPress
 */
namespace DbPress;

class  Dbp_fn {
    /**
     * @static execute_time_start
     */
    static $execute_time_start = 0;
    /**
     * @static Array table_list
     */
    static $table_list = [];
    /**
     * @static Array primary_keys
     */
    static $primary_keys = [];
    /**
     * @static Array cookie_msgs
     */
    static $cookie_msgs = [];
    /**
     * @static Array cookie_msgs
     */
    static $is_form_open = [];
    /**
     * Carica le classi per le query e i risultati delle query
     */
    static function require_init() {
        require_once DBP_DIR . "data-structures/dbp-structures-base.php";
        require_once DBP_DIR . "data-structures/dbp-list-setting.php";
        require_once DBP_DIR . "data-structures/dbp-form-setting.php";
        require_once DBP_DIR . "includes/dbp-model.php";
		require_once DBP_DIR . "includes/dbp-html-table.php";
        require_once DBP_DIR . "includes/dbp-html-sql.php";
        require_once DBP_DIR . "includes/dbp-utilities-marks-par.php";
        require_once DBP_DIR . "includes/dbp-temporaly-files.php";
        require_once DBP_DIR . "includes/dbp-model-structure.php";
        require_once DBP_DIR . "includes/dbp-html-simple-table.php";
        require_once DBP_DIR . "includes/dbp-form.php";

       
        if (is_admin()) {
            // non funziona!?
            ini_set( 'max_input_vars' , '3000' );
        }
    }
  
    /**
     * Ritorna un array con dentro gli elenchi delle tabelle, viste ecc...
     * Una volta caricati li mette in cache e li puoi riusare tutte le volte che vuoi.
     * @param bool $cache 
     * @return Array ['tables':[],'views':[]]
     */
    static function get_table_list($cache = true) {
        global $wpdb;
        if (count(dbp_fn::$table_list) > 0 && $cache) {
            return  dbp_fn::$table_list;
        }
        //$tables = $wpdb->tables();
        $tables = $wpdb->get_results('SHOW FULL TABLES FROM `'.$wpdb->dbname.'`;');
        $return_tables = $return_views = [];
        foreach ($tables as $t) {
            $tt_name = 'Tables_in_'.$wpdb->dbname;
            if ($t->Table_type == "BASE TABLE") {
                $return_tables[$t->$tt_name] = $t->$tt_name;
            } else if ($t->Table_type == "VIEW") {
                $return_views[$t->$tt_name] = $t->$tt_name;
            }
        }
        asort($return_tables);
        $ris = ['tables'=>$return_tables, 'views' => $return_views];
        dbp_fn::$table_list = $ris;
        return $ris;
    }

    /**
     * Ritorna la struttura di una tabella
     * @param Boolean $only_fields ritorna solo i nomi delle colonne
     * @return Array vuoto se la tabella non esiste
     * ```json
     * [{"Field":"dbp_id","Type":"int(10) unsigned","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},
     * {}]
     * ```
     */
    static function get_table_structure($table, $only_fields = false) {
        global $wpdb;
        $table = Dbp_fn::sanitize_key($table);
        $tables_list = self::get_table_list();
        if (!in_array($table, $tables_list['tables']) && in_array($table, $tables_list['views'])) {
            return  [];
        }
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `' . $table . '`');
        if ($only_fields) {
            $cols_name = [];
            foreach ($columns as $column) {
                $cols_name[] = $column->Field;
            }
            return $cols_name;
        }
        return $columns;
    }

    /**
     * Ritorna la chiave primaria di una tabella solo se è singola e autoincrement 
     * @return String ''=> se non trova la chiave
     */
    static function get_primary_key($table) {
        global $wpdb;
        $table = Dbp_fn::sanitize_key($table);
        if (isset(self::$primary_keys[$table])) {
            return self::$primary_keys[$table];
        }
        $tables_list = self::get_table_list();
        if (!in_array($table, $tables_list['tables'])) {
            return '';
        }
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `'.esc_sql($table).'`');
        $primary = '';
        $autoincrement = false;
        foreach ($columns as $col) {
            if ($col->Key == "PRI") {
                if ($primary == "") {
                    $primary = $col->Field;
                    if ($col->Extra == "auto_increment") {
                        $autoincrement = true;
                    }
                } else {
                    return '';
                }
               
            }
           
        }
        if ($autoincrement) {
            self::$primary_keys[$table] = $primary;
            return $primary;
        } else {
            return '';
        }
    }

     /**
     * Ritorna lo schema della chiave primaria estratta da una query
     * @return Object|false
     */
    static function find_primary_key_from_header($header, $orgtable, $primary_id) {
        foreach ($header as $ht) {
            if (isset($ht['schema']) && isset($ht['schema']->orgtable) && isset($ht['schema']->table)  && $ht['schema']->orgtable == $orgtable && $ht['schema']->orgname == $primary_id) {
                return $ht['schema'];
            }
        }
        return false;
    }

    /**
     * Estrae tutte le colonne del database
     * @return Array [table:[column, column], ...] Ritorna tutte le colonne di un database
     */
    static function get_all_columns() {
        global $wpdb;
        $sql = 'SELECT TABLE_NAME as `table`, COLUMN_NAME as `column`, DATA_TYPE as `type`, CHARACTER_MAXIMUM_LENGTH as `length`, ORDINAL_POSITION FROM information_schema.columns where table_schema = "' . esc_sql($wpdb->dbname) . '" order by `table` ASC, `ORDINAL_POSITION` ASC';
        $columns = $wpdb->get_results($sql);
        $result = [];
        if (count($columns) > 0) {
            foreach ($columns as $c) {
                if (!isset($result[$c->table])) {
                    $result[$c->table] = [];
                }
                $result[$c->table][] = $c->column;
            }
        }
        ksort  ($result);
        return $result;
    }

    /**
     * Ritorna la classe per le query che si possono fare da quella tabella
     * @deprecated
     * @todo Da rimuovere Non viene più usata
     */
    
    static function get_model($table_name) {
        $class_name = "database_press_model_".$table_name;
        if (class_exists($class_name)) {
            return new $class_name($table_name);
        } else {
            return new Dbp_model($table_name);
        }
    }

    /**
     * Verifico se il nome della tabella esiste già nel db oppure no
     * @return String Il nome della tabella senza il prefisso
     */
    static function exists_table($table_name) {
        $tables = dbp_fn::get_table_list();
        if (in_array($table_name, $tables['tables']))  {
            return true;
        }
        return false;
    }

    /**
     * Ritorna la classe personalizzata oppure quella di default per disegnare la tabella del db 
     */
    static function get_database_html_table($table_name) {
        $class_name = "Database_html_table_".$table_name;
        if (class_exists($class_name)) {
            return new $class_name();
        } else {
            return new Dbp_html_table();
        }
        
    }

    /**
     * Torna l'array con tutte le tabelle di sistema di wordpress
     * ANCORA NON LA USO
     */
    static function wordpress_table_list() {
        global $wpdb;
        $list = ['commentmeta','comments','links','options','postmeta','posts','term_relationships','term_taxonomy','termmeta','terms','usermeta','users'];
        foreach ($list as &$item) {
            $item = $wpdb->prefix.$item;
        }
        return $list;
    }

    /**
     * Ritorna il limit della query 
     */
    static function get_request_limit($path, $default, $max = 1000) {
        $limit = dbp_fn::req($path, $default, 'absint');
        if ($limit < 1) {
            return $max;
        } else if ($limit > $max * 1.1) {
            $limit = $max;
        }
        return $limit;
    } 
    /**
     * Ritorna il limit_start della query 
     * @param String $path il percorso del request
     */
    static function get_request_limit_start($path, $default, $max) {
        $limit = dbp_fn::req($path, $default, 'absint');
        if ($limit < 0) {
            return $limit = 0;
        } else if ($limit > $max) {
            $limit = $max;
        }
        return $limit;
    }

    /**
     * Ritorna i valori dell'order
     * @param String $path il percorso del request
     */
    static function get_request_sort($path, $default_field = "", $default_order = "") {
        $sort_array = dbp_fn::req($path, true); 
        return $sort_array;
    }

    /**
     * Ritorna i valori dell'order
     * @param String $path il percorso del request
     */
    static function get_request_filter($path) {
        $filter_array = dbp_fn::req($path); 
        $result = [];
        if (is_array($filter_array)) {
            foreach ($filter_array as $fa) {
                if ( isset($fa['op']) && isset($fa['column']) && isset($fa['table']) && isset($fa['value']) && $fa['value'] != "") {
                    $result[] = $fa;
                }
            }
        }
        return $result;
    }

    /**
     * Prende un request e ne fa il sanitaze serve per sanitizzare la richiesta
     * https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/#example-simple-input-field
     */
    static function sanitaze_request($path, $default = "") {
        $value = dbp_fn::req($path, $default);
        return str_replace('"','&quot;', (sanitize_text_field(wp_unslash($value))));
    }

    /**
     * Protegge tabelle e campi quando fai una query select
     */
    static function sanitize_key($table) {
        return preg_replace( '/[^A-Za-z0-9\-_`\.]/', '', $table );
    }

    /**
     * fa il sinitize recursivo dei request
     */
    static function sanitize_text_recursive($values) {
        $my_values = [];
        if (is_array($values)) {
            foreach ($values as $key => $val) {
                if (is_array($val)) {
                    $my_values[sanitize_text_field($key)] = self::sanitize_text_recursive($val);
                } else {
                    $my_values[sanitize_text_field($key)] = sanitize_text_field($val);
                }
            }
            return $my_values;
        } else {
            return sanitize_text_field( $values );
        }
    }

    /**
     * Stampa un request direttamente in un attributo html (value)
     * @param String $path il percorso del request
     */
    static function esc_request($path, $default = "") {
        $value = dbp_fn::req($path, $default);
        return dbp_fn::esc_value($value);
    }

    /**
     * Il valore ricevuto e stampato dentro un input
     */
    static function esc_value($str) {
        return htmlentities(wp_unslash(html_entity_decode($str)));
    }

    /**
     * Ritorna il risultato di un request. Type fa il parsing della richiesta. 
     * In particolare boolean ritorna false se la variabile è 0 o "" o f o false (anche stringa).
     * @param String $path il percorso del request se ad esempio voglio un array annidato scrivo filter.sort. eccc
     * @param String $default il valore di default se non trovo un valore
     * @param String $type boolean|string|int|float|absint o text (non fa parsing)
     * @return Mixed
     */
    static function req($path, $default="", $type = "text") {
        $var = null;
        if ($path != "") {
			$path = explode(".", $path);
			$pointer = $_REQUEST;
			foreach ($path as $p) {
				$p = trim($p);
				if (!is_array($pointer) || !array_key_exists($p, $pointer) || @$pointer[$p] === '') {
					$var = $default;
				}
				$pointer = &$pointer[$p];
			}
			$var = $pointer;
		}  	
        if (!is_null($var)) {
            switch ($type) {
                case "boolean":
                    $var = (String)$var;
                    if (strtolower($var) == "false" || $var == "0" || $var == "" || strtolower($var) == "f" ) {
                        return false;
                    } else {
                        return true;
                    }
                    break;
                case "string":
                    return (string)$var;
                    break;
                case "int":
                    return intval($var);
                    break;
                case "array":
                    if (is_object($var)) {
                        return (array)$var;
                    } else if (is_array($var)) {
                        return $var;
                    } else {
                        return [];
                    }
                    break;
                case "float":
                    return floatval($var);
                    break;
                case "absint":
                    return absint($var);
                    break;
                case "remove_slashes":
                    return wp_unslash( (String)$var );
                    break;
                default;
                    return $var;
                    break;
            }
        } else {
            return $default;
        }
    }

    /**
     * Mostra la paginazione
     * @param Number $total
     * @param Number $limit_start limit_start è il numero dei record da cui si inizia la visualizzazione 
     * @param Number $limit Il numero di record che si sta visualizzando
     */
    static function get_pagination($table_total_items, $table_limit_start,  $table_limit ) {
        if ($table_total_items == 0 || $table_limit == 0) return false;
        $pages = ceil($table_total_items / $table_limit);
        if ($table_limit_start > 0) {
            $curr_page = ceil($table_limit_start / $table_limit);
        } else {
            $curr_page = 0;
        }
        if ($table_total_items > 0 && ceil($table_total_items / $table_limit) > 1) : ?>
            <span class="pagination-links">
                <span class="js-dbp-pagination-first-page tablenav-pages-navspan button <?php echo  ($table_limit_start == 0 || ceil($table_limit_start / $table_limit) < 1) ? 'disabled js-dbp-pag-disabled' : ''; ?>" aria-hidden="true">«</span>
                <span class="js-dbp-pagination-prev-page tablenav-pages-navspan button <?php echo  ($table_limit_start == 0 || ceil($table_limit_start / $table_limit) < 1) ? 'disabled js-dbp-pag-disabled' : ''; ?>" aria-hidden="true" data-currentpage="<?php echo  $table_limit_start - $table_limit ; ?>">‹</span>
                <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label>
                
                <select  onChange="jQuery('#dbp_table_filter_limit_start').val(jQuery(this).val()); dbp_submit_table_filter('limit_start');"  class="current-page dbp-pagination-input">
                    <?php for ($x = 0; $x < $pages; $x++) : ?>
                        <?php $selected = ($x == $curr_page) ? ' selected="selected" ' : '' ; ?>
                        <option value="<?php echo $x*$table_limit; ?>" <?php echo $selected; ?>><?php echo  $x+1;?></option>
                    <?php endfor; ?>
                </select>


                <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo ceil($table_total_items / $table_limit); ?></span></span></span>
                <span class="js-dbp-pagination-next-page tablenav-pages-navspan button <?php echo  ($table_limit_start + $table_limit >= $table_total_items) ? 'disabled js-dbp-pag-disabled' : ''; ?>" aria-hidden="true"  data-currentpage="<?php echo  $table_limit_start + $table_limit ; ?>">›</span>
                <span  class="js-dbp-pagination-last-page tablenav-pages-navspan button <?php echo  ($table_limit_start+ $table_limit >= $table_total_items) ? 'disabled js-dbp-pag-disabled' : ''; ?>" aria-hidden="true"  data-currentpage="<?php echo  ceil(($table_total_items / $table_limit)-1)*$table_limit ; ?>">»</span>
            </span>
        <?php 
        endif;
    } 

    /**
     * Toglie tutti gli spazi prima e dopo di una stringa;
     */
    static function all_trim($string) {
        while (trim($string) != $string) {
            $string = trim($string);
        }
        return $string;
    }

    /**
     * Ripulisce una stringa dai caratteri speciali.
     * La devo usare solo quando creo una stringa o salvo i dati, mai quando riprendo una stringa
     * @param String $str
     * @return String
     */
    static function clean_string($str) {
        $str = str_replace(' ', '_', $str); 
        $str = preg_replace('/[^A-Za-z0-9\-_]/', '_', $str);
        $str = str_replace(["____","___","__"], "_", $str);
        if (substr($str,-1) == "_") {
            $str = substr($str,0,-1);
        }
        if (substr($str,0,1) == "_") {
            $str = substr($str,1);
        }
        return strtolower($str);
    }

    /**
     * Trasforma il type delle informazioni delle colonne in testo
     * 'MYSQLI_TYPE_DECIMAL' => int 0
     * @param Boolean $simple Se true converte se riesce la risposta in NUMERIC/DATE/VARCHAR/TEXT 
     */
    public static function h_type2txt($type_id, $simple=true)
    {
        static $h_type2txt_types;
        if ($type_id == "WP_HTML" || $type_id == "CHECKBOX") return $type_id; // speciale
        $array_convert = [ 'DECIMAL' => 'NUMBER' , 'TINY' => 'NUMBER',  'SHORT' => 'NUMBER', 'LONG' => 'NUMBER', 'FLOAT' => 'NUMBER',
  'DOUBLE' => 'NUMBER', 'LONGLONG' => 'NUMBER', 'INT24' => 'NUMBER', 'NEWDECIMAL' => 'NUMBER', 'TIMESTAMP'=>'DATE', 'DATE'=>'DATE', 'TIME'=>'DATE', 'YEAR'=>'DATE', 'DATETIME'=>'DATE','NEWDATE'=>'DATE', 'ENUM'=>'VARCHAR', 'SET'=>'VARCHAR', 'BLOB' => 'TEXT', 'TINY_BLOB'=>'TEXT', 'MEDIUM_BLOB'=>'TEXT', 'LONG_BLOB'=>'TEXT', 'VAR_STRING'=>'VARCHAR', 'STRING' => 'VARCHAR', 'INTERVAL' => 'DATE', 'BIT'=>'NUMBER' ];

        $array_convert_2 = [ 'DECIMAL' => 'DECIMAL' , 'TINY' => 'TINY',  'SHORT' => 'NUMBER', 'LONG' => 'NUMBER', 'FLOAT' => 'FLOAT',
        'DOUBLE' => 'DECIMAL', 'LONGLONG' => 'NUMBER', 'INT24' => 'NUMBER', 'NEWDECIMAL' => 'DECIMAL', 'TIMESTAMP'=>'TIMESTAMP', 'DATE'=>'DATE', 'TIME'=>'TIME', 'YEAR'=>'YEAR', 'DATETIME'=>'DATETIME','NEWDATE'=>'NEW DATE', 'ENUM'=>'ENUM', 'SET'=>'SET', 'BLOB' => 'TEXT', 'TINY_BLOB'=>'TEXT', 'MEDIUM_BLOB'=>'TEXT', 'LONG_BLOB'=>'TEXT', 'VAR_STRING'=>'STRING', 'STRING' => 'STRING', 'INTERVAL' => 'INTERVAL DATE', 'BIT'=>'TINY '];
        if (!isset($h_type2txt_types))
        {
            $h_type2txt_types = array();
            $constants = get_defined_constants(true);
            foreach ($constants['mysqli'] as $c => $n) if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) $h_type2txt_types[$n] = $m[1];
        }
        $result = array_key_exists($type_id, $h_type2txt_types) ? $h_type2txt_types[$type_id] : NULL;
        if ($simple == true) {
            if (array_key_exists($result, $array_convert)) {
                $result = $array_convert[$result];
            }
        } else {
            if (array_key_exists($result, $array_convert_2)) {
                $result = $array_convert_2[$result];
            }
        }
        return $result;
    }

    public static function fieldtype2txt($field_type) 
    {
        $field_type = strtolower($field_type);
        if (substr($field_type,0,6) == "bigint" || substr($field_type,0,7) == "tinyint" || substr($field_type,0,5) == "float" || substr($field_type,0,8) == "smallint" || substr($field_type,0,9) == "mediumint" || substr($field_type,0,7) == "decimal" || substr($field_type,0,6) == "double") {
            return 'NUMERIC';
        }
        if (substr($field_type,0,4) == "text" || substr($field_type,0,8) == "tinytext" || substr($field_type,0,9) == "mediumtext"  || substr($field_type,0,8) == "longtext"  || substr($field_type,0,4) == "blob") {
            return 'TEXT';
        }
        if (substr($field_type,0,4) == "date" || substr($field_type,0,8) == "datetime" || substr($field_type,0, 9) == "timestamp") {
            return 'DATE';
        }
        return 'VARCHAR';
    }

    /**
     * Stampa una select
     * @param Array  $options [String:String, ...] la chiave è il value dell'option mentre il valore è il label (fa la traduzione qui!)
     * @param Boolean $use_option_key Dice se delle options deve usare key=>value (true) oppure solo value sia nel label che nel valore dell'option 
     * @param String $attributes Gli attributi del select
     * @param String $value il valore da selezionare
     * @param String $default Il valore di default da selezionare se value = false | null
     */
    public static function html_select($options, $use_option_key, $attributes = "", $value = false,  $default = false) {
        
        if (($value === false || is_null($value))  && $default !== false) {
            $value = $default;
        }
        // Check if option_key exists
        ?><select <?php echo $attributes; ?>><?php
      
        foreach ($options as $option_key => $option_value) {
            if (is_array($option_value)) {
                ?> <optgroup label="<?php echo esc_attr($option_key); ?>"><?php
                    foreach ($option_value as $option2_key => $option2_value) {
                        $selected  = "";
                        if (!$use_option_key) {
                            $option2_key = $option2_value;
                        }
                        if ($value !== false) {
                            if (is_array($value)) {
                                foreach ($value as $k=>$v) {
                                    if ($v == $option_key) {
                                        $selected = 'selected="selected"';
                                        break;
                                    }
                                }
                            } else {
                                $selected = ($value == $option2_key) ? 'selected="selected"' : '';
                            }
                        }
                        $option2_value = __($option2_value, 'db_press');
                        ?><option value="<?php echo str_replace('"','\"', $option2_key); ?>"<?php echo  $selected ; ?>><?php echo htmlentities($option2_value); ?></option><?php
                    }
                ?> </optgroup>
                <?php
            } else {
                $selected  = "";
                if (!$use_option_key) {
                    $option_key = $option_value;
                }
                if ($value !== false) {
                    if (is_array($value)) {
                        foreach ($value as $k=>$v) {
                            if ($v == $option_key) {
                                $selected = 'selected="selected"';
                                break;
                            }
                        }
                    }  else {
                        $selected = ($value == $option_key) ? 'selected="selected"' : '';
                    }
                }
                $option_value = __($option_value, 'db_press');
                ?><option value="<?php echo str_replace('"','\"', $option_key); ?>"<?php echo  $selected ; ?>><?php echo htmlentities($option_value); ?></option><?php
            }
        }
        ?></select><?php
    }

    /**
     * Verifica se un valore passato alla query di tipo between è corretto oppure no
     */
    static function is_correct_between_value($value, $op="BETWEEN") {
        if ($op == "BETWEEN" || $op  =="NOT BETWEEN") {
            $val = explode("#AND#", $value);
            if (count($val) == 2 ) {
                if(trim($val[0]) == "" && trim($val[1]) == "") {
                    return false;
                }
                return [$val[0], $val[1]];
            }
        }
        return false;
    }

    /**
     * Aggiunge la parte di query relegata ad un between o not between
     */
    static function set_sql_where_between_value($column, $value, $op="BETWEEN") {

        if ($op == "BETWEEN" || $op  =="NOT BETWEEN") {
            $val = explode("#AND#", $value);
            if (count($val) == 2 && trim($val[0]) != "" && trim($val[1]) != "") {
                return $column." BETWEEN '".esc_sql($val[0])."' AND '".esc_sql($val[1])."'";
            } else if (count($val) == 2 && trim($val[0]) != "" && trim($val[1]) == "") {
                if ($op == "BETWEEN") {
                    return $column." >= '".esc_sql($val[0])."'";
                } else {
                    return $column." < '".esc_sql($val[0])."'"; 
                }
            } else if (count($val) == 2 && trim($val[0]) == "" && trim($val[1]) != "") {
                if ($op == "BETWEEN") {
                    return $column." <= '".esc_sql($val[1])."'";
                } else {
                    return $column." > '".esc_sql($val[1])."'"; 
                }
            }
        }
        return "";
    }

    /**
     * Dentro class-database-html-table viene disegnato il popup per i filtri di ricerca. Qui vengono estratte le opzioni e l'operazione di default a partire dal tipo di campo
     * @param String $symple_type
     * @return Array [[],String]
     */
    static function get_array_for_select_in_drowdown_filter($symple_type) {
        $html_select_array = [
            '=' => __('Equals (=)',  'db_press'),
            '!=' => __('Does Not Equal (!=)',  'db_press'),
            '>'  => __('Greater Than (>)',  'db_press'),
            '>='  => __('Greater or Equal To (>=)',  'db_press'),
            '<'  => __('Less Than (<)',  'db_press'),
            '<='  => __('Less or Equal To (<=)',  'db_press'),
            'LIKE'  => __('Search Text (%LIKE%)',  'db_press'),
            'NOT LIKE'  => __('Exclude Text (NOT LIKE)',  'db_press'),
            'BETWEEN'  => __('Between Values (BETWEEN)',  'db_press'),
            'NOT BETWEEN'  => __('Values outside the range (NOT BETWEEN)', 'db_press'),
            'NULL' => __('Empties',  'db_press'),
            'NOT NULL'  => __('Not Empties',  'db_press')
        ];
        $def_op = "=";
        switch ($symple_type) {
            case "DATE":
                $def_op = "BETWEEN";
                break;
            case "NUMERIC":
                $def_op = "IN";
                break;
            case "VARCHAR":
                $html_select_array = [
                    '=' => __('Equals (=)',  'db_press'),
                    '!=' => __('Does Not Equal (!=)',  'db_press'),
                    '>cast'  => __('Greater Than (>)',  'db_press'),
                    '>=cast'  => __('Greater or Equal To (>=)',  'db_press'),
                    '<cast'  => __('Less Than (<)',  'db_press'),
                    '<=cast'  => __('Less or Equal To (<=)',  'db_press'),
                    'LIKE'  => __('Search Text (%LIKE%)',  'db_press'),
                    'NOT LIKE'  => __('Exclude Text  (NOT LIKE)',  'db_press'),
                    'NULL' => __('Empties',  'db_press'),
                    'NOT NULL'  => __('Not Empties',  'db_press')
                ];
                $def_op = "IN";
                break;
            case "TEXT":
                $def_op = "LIKE";
                $html_select_array = [
                    '=' => __('Equals (=)',  'db_press'),
                    '!=' => __('Does Not Equal (!=)',  'db_press'),
                    '>cast'  => __('Greater Than  (>)',  'db_press'),
                    '>=cast'  => __('Greater or Equal To (>=)',  'db_press'),
                    '<cast'  => __('Less Than  (<)',  'db_press'),
                    '<=cast'  => __('Less or Equal To (<=)',  'db_press'),
                    'LIKE'  => __('Search Text (%LIKE%)',  'db_press'),
                    'NOT LIKE'  => __('Exclude Text (NOT LIKE)',  'db_press'),
                    'NULL' => __('Empties',  'db_press'),
                    'NOT NULL'  => __('Not Empties',  'db_press')
                ];
                break;
            
        } 
        return [$html_select_array ,$def_op];
    }

    /**
     * Funzione per aggiungere limit e order in una query passati da Request Il risultato lo mette dentro il model
     * @param Dbp_model $table_model
     * @param Integer $max_show_items
     * @return string La query sql per fare il count del numero di record se serve, altrimenti torna vuota.
     */

    static function add_request_filter_to_model(&$table_model,  $max_show_items = 100) {
        $action_query = dbp_fn::req('action_query','');
        $table_filter = true;
        if ($action_query != "custom_query") {
            $table_filter	= dbp_fn::get_request_filter('filter.search') ; // [[op:'',column:'',value:'',table:''],...]
            foreach ($table_filter as &$filter) {
                $filter['value'] = wp_unslash($filter['value']);
                $filter['column'] = wp_unslash($filter['column']);
            }
        } 
       
        $table_model->list_add_where($table_filter);

        $count_sql = "";
        if (isset($_REQUEST['cache_count'])) {
            $table_model->total_items = intval($_REQUEST['cache_count']);
        } else {
            $count_sql = $table_model->get_count(true, true);
        }
        
        if ($action_query != "custom_query") {
            $table_limit_start 			= dbp_fn::get_request_limit_start('filter.limit_start', 0, $table_model->total_items) ;
            $table_limit 				= dbp_fn::get_request_limit('filter.limit', 100,  $max_show_items) ;
            $table_model->list_add_limit($table_limit_start, $table_limit);
            $table_sort					= dbp_fn::get_request_sort('filter.sort', true) ; // [field:'','order':'']
            if (is_array($table_sort)) {
                $table_model->list_add_order($table_sort['field'], $table_sort['order']);
            }
        
        } else {
            // trovo il limit
            list($ls, $le) = $table_model->get_partial_query_limit();
            if ( $le == 0 ) {
                $table_model->list_add_limit(0, 100);
            } else if ($le > $max_show_items * 2) {
                $table_model->list_add_limit(0, $max_show_items);
            } else {
                $_REQUEST['filter']['limit'] = $le;
                $_REQUEST['filter']['limit_start'] = $ls;
                $table_model->limit = $le;
                $table_model->limit_start = $ls;
            }
        }
        return  $count_sql;
    }

    /**
     * Sempre per il frontend cerca il nome del column_name e ne ritorna uno dei parametri
     * @param dbpDs_list_setting[] $list_setting
     * @param string $column_name
     * @return string|false
     */
    static function get_val_from_head_column_name($list_settings, $column_name, $return = 'table') {
        foreach ($list_settings as $list_setting) {
            if (strtolower($list_setting->name_request) == strtolower($column_name) && $list_setting->$return != "") {
                return $list_setting->$return;
            }
        }
        return false;
    }

    /**
     * Sempre per il frontend cerca il nome del column_name e ne ritorna la colonna per la query sql
     * @param dbpDs_list_setting[] $list_settings
     * @param string $column_name
     * @return string|false
     */
    static function convert_head_column_in_filter_array($list_settings, $column_name, $value) {
        foreach ($list_settings as $row) {
            if (strtolower($row->name_request) == strtolower($column_name) && $row->mysql_name != "") {
                return  ['op'=>$row->searchable, 'column'=> $row->mysql_name, 'value' => $value, 'table' => $row->mysql_table]; 
            }
        }
        return false;
    }

    /**
     * Funzione per aggiungere limit e order in una query passati da Request Il risultato lo mette dentro il model
     * @param string $query
     * @param Integer $max_show_items
     * @param boolean $multiquery
     * @return database_press_model_base
     */
    static function model_single_query($query, $max_show_items = 1000, $multiquery = false) {
        $table_model = new Dbp_model();
        $table_model->prepare($query, false);
        $effected_row = $table_model->get_count(false);
        if ($max_show_items > 0) {
            if ($table_model->total_items > $max_show_items * 1.1) {
                $table_limit =  $max_show_items;
                $table_model->list_add_limit(0, $table_limit);
            } else {
                $table_limit = $table_model->total_items;
            }
            if ($multiquery) {
                $table_model->get_list(false, false);
            } else {
                $table_model->get_list();
            }
            $table_model->update_items_with_setting();
        } 
        if ($table_model->sql_type() == "select") {
            $table_model->effected_row = $effected_row;
        }
        return $table_model;
    }

   

   /**
     * Crea un csv da scaricare direttamente compatibile con EXCEL.
     * gp_export_data_to_csv(json_encode("{'id':1,'name':'Pippo', 'id':2,'name':'Pluto'}"));
     * @param Array $data i dati del csv.
     * @param String $filename
     * @param String $delimiter
     * @param String $enclosure
     * @param Boolean $csv_headers
     *  
     */
    static function export_data_to_csv($data,  $filename='export', $delimiter = ';', $enclosure = '"',  $csv_headers=true)
    {
        // Tells to the browser that a file is returned, with its name : $filename.csv
        header("Content-disposition: attachment; filename=$filename.csv");
        // Tells to the browser that the content is a csv file
        //header("Content-Type: text/csv");
        // I open PHP memory as a file
        $fp = fopen("php://output", 'w');
        // Insert the UTF-8 BOM in the file
        fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
        // I add the array keys as CSV headers
        if ($csv_headers) {
            $first = reset($data);
            if (is_object($first)) {
                $first = (array)$first;
            }
            fputcsv($fp,array_keys($first),$delimiter,$enclosure);
        }
        // Add all the data in the file
        foreach ($data as $fields) {
            if (is_object($fields)) {
                $fields = (array)$fields;
            }
            fputcsv($fp, $fields, $delimiter, $enclosure);
        }
        // Close the file
        fclose($fp);
        // Stop the script
        die();
    }

     /**
	 * Esegue un gruppo di multiquery a partire da un file
     * Viene chiamata da class-database-press-admin e da class-database-press-loader
	 * @param String $filename il file temporaneo da caricare.
     * @return Array ['last_error', 'total_queries', 'executed_queries' , 'filename' , 'error_count', 'model' , 'items']
	 */
	static function execute_multiqueries($filename, $ignore_error = false) {
		/**
		 * @var dbp_temporaly_files $temporaly La classe per gestire i file temporanei
		 */
		$temporaly = new Dbp_temporaly_files();
		/**
		 * @var String $last_error
		 */
		$last_error = '';
		/**
		 * @var Array $array_temp_file ['queries_filename', count_queries', ...]
		 */
		$array_temp_file = $temporaly->read($filename);
		/**
		 * @var Integer $total_queries Le query eseguite
		 */
		$total_queries = 0;
		/**
		 * @var Integer $executed_queries Le query eseguite
		 */
		$executed_queries = 0;
		/**
		 * @var Array $queries L'array delle queri da eseguire estratto dal file temporaneo
		 */
		$queries = [];
		/**
		 * @var Array $error_count L'array delle query da eseguire estratto dal file temporaneo
		 */
		$error_count = 0;
		/**
		 * @var Array $report_queries  [['query','effected_row','time_executed','error']];
		 */
        $report_queries = [];
         /**
         * @var Array $items [{'model','content'}, ...]
         */
        $items = [];
		if ($temporaly->last_error == "" && isset($array_temp_file['queries_filename']) && isset($array_temp_file['total_queries'])) {
			$queries = $temporaly->read($array_temp_file['queries_filename']);
			$total_queries = $array_temp_file['total_queries'];
			$error_count =  $array_temp_file['error_count'];
			$report_queries = $array_temp_file['report_queries'];
		} 
		if ($temporaly->last_error) {
			$last_error = $temporaly->last_error;
			$error_count++;
		} else if ($queries == false) {
			$last_error = __('No temp data found','db_press');
		} else {
			$count_queries = 0;
			while ($queries) {
				$query = array_shift($queries);
				$single_table_model = dbp_fn::model_single_query($query, 100, true);
				$report_queries[] = [$query, $single_table_model->effected_row, $single_table_model->time_of_query, $single_table_model->last_error];
				$count_queries++;
				if ($single_table_model->last_error != "") {
					$last_error = '<p>'.$query.'</p>'.$single_table_model->last_error;
					$error_count++;
                    if (!$ignore_error) {
					    break;
                    }
				}  else {
                    $html_table   = new Dbp_html_table();
                    $single_table_model->sort = false;
                    $single_table_model->filter = false;
                    $html_content = $html_table->template_render($single_table_model);
                    $items[] = (object)['model'=>$single_table_model, 'content'=>$html_content];
                }
                if (!dbp_fn::get_max_execution_time()) {
					break;
				}
			}
            $temporaly = new Dbp_temporaly_files();
            $temporaly->store(['total_queries' => $total_queries, 'queries_filename' =>  $array_temp_file['queries_filename'], 'error_count' => $error_count, 'report_queries' => $report_queries], $filename);
			if (count($queries) > 0) {
				$temporaly->store($queries, $array_temp_file['queries_filename']);
			}  else {
				$temporaly->delete($array_temp_file['queries_filename']);
			    // $temporaly->delete($filename);
			}
			$executed_queries = $total_queries - count($queries);
		}
		return ['last_error' => $last_error, 'total_queries' => $total_queries, 'executed_queries' => $executed_queries, 'filename' => $filename, 'error_count' => $error_count, 'items'=>$items ];
	}

    /**
     * Quando lo carichi la prima volta setta il tempo iniziale e torna true. Poi ogni volta che lo carichi verifica quanto tempo è passato e se è passato più di un tot di tempo ritorna false;
     * @return Boolean Se hai ancora tempo per l'esecuzione dello script oppure no.
     */
    static function get_max_execution_time() {
        /**
         * @var Integer $max_time Il tempo massimo di esecuzione del php
         */
        $max_time = @ini_get("max_execution_time");
        if ($max_time < 30) {
            $max_time = 30;
        }
        //TODO metterlo a /2
        $max_time = $max_time / 2;
        if ( ( microtime(true) - dbp_fn::$execute_time_start ) < $max_time ) {
            return true;
     
        } else {
            return false;
        }
    }

    /**
     * Converte un carattere speciale nella sua forma visuale
     */
    static function convert_char_to_special($char) {
        $a = array("\\\\t"=>"\\t","\\\\n"=>"\\n","\\\\r"=>"\\r", "\\t"=>"\\t","\\n"=>"\\n","\\r"=>"\\r","\t"=>"\\t","\n"=>"\\n","\r"=>"\\r");
        if(isset($a[$char])) {
            return $a[$char];
        } else {
            return substr(str_replace("\\", "", $char),0,1);
        }
    }
     /**
     * Converte un carattere speciale dalla sua forma visuale alla forma reale
     */
    static function convert_special_to_char($char) {
        $a = array("\t"=>"\t","\n"=>"\n","\r"=>"\r","\t"=>"\t","\\n"=>"\n","\\r"=>"\r","\\\\t"=>"\t","\\\\n"=>"\n","\\\\r"=>"\r");
        if(isset($a[$char])) {
            return $a[$char];
        } else {
            return substr(str_replace("\\", "", $char),0,1);
        }
    }

    /**
     * Ritorna il prefisso delle tabelle di wordpress
     */
    static function get_prefix() {
        global $wpdb;
        return $wpdb->prefix;
    }

    /**
     * Trova l'elenco dei posttype
     * @return array
     */
    static function get_post_types() {
        global $wpdb;
        $p = $wpdb->get_results('SELECT DISTINCT(`post_type`) pt FROM '.$wpdb->prefix.'posts ORDER BY `post_type` DESC LIMIT 1000');
        foreach ($p as &$value) {
            $value = $value->pt;
        }
        return $p;
    }


    /**
     * Calcola il massimo upload file
     */
    static function get_max_upload_file() {
        $max_upload = (ini_get('upload_max_filesize'));
        $max_post =(ini_get('post_max_size'));
        $memory_limit = (ini_get('memory_limit'));
        
        if (stripos($max_upload,'m') > 0 && stripos($max_post, 'm' ) > 0  && stripos($memory_limit, 'm' ) > 0) {
         
                $max_upload = (int)  $max_upload;
                $max_post = (int)  $max_post;
                $memory_limit  = (int)  $memory_limit;
            if ($max_upload > 0 && $max_post > 0 && $memory_limit > 0) {
                return  min($max_upload, $max_post, $memory_limit). " Mb";
            } else if ($max_upload > 0) {
                return $max_upload. " Mb";
            }
        }
        return false;
    }

    /**
     * Calcola il massimo numero di upload_vars impostato
     * Se si passa il numero di input che si intende inviare ritorna il testo dell'errore
     * @return Mixed o il valore max_input_vars oppure 
     * se input_used è impostato allora un messaggio se input used è maggiore di max_input_vars o ""
     */
    static function get_max_input_vars($input_used = 0) {
        $max_input = (int)ini_get('max_input_vars') ;
        if ($max_input == 0 ) {
            $max_input = 2000;
        }
        if ($input_used > 0) {
            if ($input_used > $max_input) {
                return sprintf(__('The maximum number of input_var (max_input_vars) allowed by php.ini is %s But to manage this table I need at least %s', 'db_press'),$max_input, $input_used);
            } else {
                return "";
            }
        }
        return $max_input;
    }

    
    /**
     * Converte una stringa per essere accettata come nome di una colonna mysql
     */

    static function convert_to_mysql_column_name($temp_name) {
        $temp_name = dbp_fn::clean_string($temp_name);
        if (!ctype_alpha(substr($temp_name,0,1))) {
            $temp_name = "col_".$temp_name;
        }
        return substr($temp_name,0, 64);
    }

    /**
     * Ritorna tutte le opzioni delle tabelle
     */
    static function get_all_dbp_options() {
        $table_options = get_option('dbp_table_info');
        return $table_options;
    } 

    /**
     * Trova le opzioni di una tabella specifica
     * @return array [status:'DRAFT|PUBLISH|CLOSE', description, $external_filter:boolean]
     */
    static function get_dbp_option_table($table_name) {
        if ($table_name == "") {
            $return =  ['status'=>'DRAFT', 'description'=>'']; 
        } else {
            $table_name = trim(str_replace('`','',$table_name));
            $table_options = get_option('dbp_table_info');
            if ($table_options === false || !array_key_exists($table_name, $table_options)) {
                $return =  ['status'=>'PUBLISH', 'description'=>''];
            }   else {
                $return = $table_options[$table_name];
            }
        }
        $current_status = $return['status'];
        $return['status'] = apply_filters( 'dbp_table_status', $return['status'], $table_name);
        if ($return['status'] != $current_status) {
            $return['external_filter'] = true;
        }   else {
            $return['external_filter'] = false;
        }
        return $return; 
    }

    /**
     * Aggiorna lo stato di una tabella
     * @param String $table
     * @param String|Boolean $status DRAFT|PUBLISH|CLOSE SE falso non lo aggiorna
     * @param String|Boolean $description Se false non lo aggiorna
     */
    static function update_dbp_option_table_status($table, $status = false, $description = false) {
        $dbp_table_info =  get_option('dbp_table_info');
        if ($dbp_table_info === false ) {
            $dbp_table_info = [];
        }
       
        if ($status == false && $description == false) {
            return false;
        }
        if ($status != false) {
            $dbp_table_info[$table]['status'] = $status;
        }
        if ($description != false) {
            $dbp_table_info[$table]['description'] = $description;
        }
        update_option('dbp_table_info', $dbp_table_info, false);
    }

    /**
     * Rimuove le impostazioni di una tabella dalle option
     * @param String $table
     */
    static function delete_dbp_option_table_status($table) {
        $dbp_table_info =  get_option('dbp_table_info');
        if ($dbp_table_info === false || !array_key_exists($table,$dbp_table_info)) {
            return true;
        }
        unset($dbp_table_info[$table]);
        update_option('dbp_table_info', $dbp_table_info, false);
    }

    /**
     * Ritorna una stringa univoca. (uniqid non è sicuro!)
     */
    static function get_uniqid() {
        $uniq = explode(".", uniqid('', true));
        return (base_convert($uniq[0], 16, 36).base_convert($uniq[1], 10, 36) );
    }

     /**
     * Disegna il titolo
     * @param String $section list|table
     * @param String $title
     * @param String $description
     * @param String $msg
     * @param String $msg_error
     * @return Boolean se $msg_error != ""
     */
    static function echo_html_title_box($section, $title, $description = "", $msg = "", $msg_error = "", $append = "") {
        $array_link_section = ['list'=>'list-all', 'table'=>'information-schema'];
        $array_link_page = ['list'=>'dbp_list', 'table'=>'information-schema'];
        ?>
        <h2 class="dbp-h2-inline dbp-content-margin">
            <a href="<?php echo admin_url("admin.php?page=".$array_link_page[$section]."&section=".$array_link_section[$section]); ?>"><?php echo $section; ?></a>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
            <?php echo $title; ?>
        </h2>
        <?php 
        if ( $msg_error == "") {
            echo $append; 
        }
        ?>
        <hr class="dbp-header-end">
        <?php if ($description != "") : ?>
            <div class="dbp-description"><?php echo $description; ?></div>
        <?php endif; ?>
        <?php if ($msg != "") : ?>
            <div class="dbp-alert-info"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if (@$msg_error != ""): ?>
            <div class="dbp-alert-sql-error"><?php echo $msg_error; ?></div>
        <?php endif ;
        return  (@$msg_error === "");
    }

    /**
     * Sul primo elemento visibile dei risultati di una query (dentro $table_model) 
     * aggiungo i link per le azioni tipo edit|view|delete.
     * I risultati li ripassa dentro table_model->items
     */
    static function items_add_action(&$table_model) {
      
       // var_dump ($table_model->items);
        if (!is_countable($table_model->items) || count($table_model->items) == 0 ){
           return false;
        }
        $table_header = reset($table_model->items);
        $table_model->add_primary_ids();
      
        $primaries = $table_model->get_pirmaries();
        //var_dump ($primaries);
        $first_table_header = 0;
        // trovo la colonna a cui aggiungere le azioni
        foreach ($table_header as $key=>$v) {
            if ($v->toggle != "HIDE") {
                $first_table_header = $key;
                break;
            }
        }

       // $primaries = array_filter($primaries);
        // non trovo chiavi primarie!
        if (count($primaries) == 0) {
            return false;
        }
        reset($table_model->items);
        $keytmi = key($table_model->items);
        //var_dump ($primaries);
       //var_dump ($table_header);
        // Setto nello schema le colonne che sono primary ID
        $found_at_least_one_pri = false;
        foreach ($table_header as $k=>$th) {
            if (isset($th->table) && isset($primaries[$th->original_table]) && strtolower($primaries[$th->original_table]) == strtolower($th->original_name)) {
                $table_model->items[$keytmi][$k]->pri = true;
                $found_at_least_one_pri = true;
                $table_model->items[$keytmi][$first_table_header]->type = "WP_HTML";
               
                $table_model->items[$keytmi][$k]->name = $table_model->items[$keytmi][$k]->name . ' <span class="dashicons dashicons-admin-network" style="color:#e2c447" title="Primary"></span>';
                
            } 
        } 

        if (!$found_at_least_one_pri) { 
            return false;
        }
        
        $count_unique_id = 0;
        $primary_values_ori_table = [];
        //var_dump ($table_header);
        $table_status = $table_model->table_status();
        $max_input_vars = dbp_fn::get_max_input_vars();
        $tables = $table_model->get_partial_query_from(true);
        $max_checkboxes_view = floor((($max_input_vars / count($tables)) - 20 )/100)*100;
        //print $max_input_vars." / ".count($tables)." = ". $max_checkboxes_view ;
        $show_chekboxes = (count($table_model->items)-1 <= $max_checkboxes_view);
        // Importo dentro le singole righe i bottoni delle azioni
      
        if ($table_status != 'CLOSE') {
            foreach ($table_model->items as $key => $_) {
                if ($key == 0 || $key == "" ) {
                    if ($show_chekboxes) {
                        $table_model->items[$key] = array_merge(['__checkbox_' =>  (object)['name'=>'ck', 'original_table' => '',  'table' => '', 'name_column'=>'', 'original_name' => '','field_key'=>'', 'original_field_name'=>'', 'type'=> "CHECKBOX", 'sorting'=>'', 'dropdown' => false, 'width'=>'', 'align'=>'', 'mysql_name' =>'', 'name_request' =>'', 'searchable' => false, 'custom_param' => '', 'format_values' => '', 'format_styles' => '']], $table_model->items[$key]);
                    }
                    continue;
                }
                $primary_values = [];
                // Trovo I valori delle chiavi primarie e le tabelle che le gestiscono e le salvo come variabili js
                reset($table_model->items);
              
                foreach ($table_header as $k=>$th) {
                    if (isset($th->table) && $th->original_table != "" && isset($primaries[$th->original_table]) && $primaries[$th->original_table] == $th->original_name && $table_model->items[$key]->$k > 0) {
                        // la differenza tra primary_values e [..]_ori_table è nel riferimento della tabella: nel primo caso è l'alias usato nella query, nel secondo caso è il nome della tabella stessa
                    
                        $primary_values[$th->table.".".$primaries[$th->original_table]] =  $table_model->items[$key]->$k;
                        $primary_values_ori_table[$th->original_table.".".$primaries[$th->original_table]] =  $table_model->items[$key]->$k;
                    } 
                } 
                $count_unique_id++;
                $table_model->items[$key]->$first_table_header = '<span class="js-text-content">'
                .$table_model->items[$key]->$first_table_header .'</span>';
                $table_model->items[$key]->$first_table_header .= '<script>  dbp_tb_id['.$count_unique_id.'] = '.json_encode($primary_values).'; </script>';
                
                $btns = [];

                $edit = true;
                $other_edit = false;
                $btns0 = [];
                if ($edit) {
                    $btns0['edit'] = '<span class="dbp-submit-style-link" onclick="dbp_edit_details_v2(dbp_tb_id['.$count_unique_id.'], this);">Edit</span>';
                    $btns0['clone'] = '<span class="dbp-submit-style-link" onclick="af_clone_details(dbp_tb_id['.$count_unique_id.'], this);">Clone</span>';
                } else if ($other_edit) {
                    $btns['view'] = '<span class="dbp-submit-style-link" onclick="dbp_edit_details_v2(dbp_tb_id['.$count_unique_id.'], this);">View</span>';
                }
             
                $btns['view'] = '<span class="dbp-submit-style-link" onclick="dbp_view_details(dbp_tb_id['.$count_unique_id.']);">View</span>';
                
                $btns['delete'] = '<span class="dbp-submit-style-link-delete" onclick="dbp_delete_confirm([dbp_tb_id['.$count_unique_id.']], this);">Delete</span>';
                    
                if (count($btns0))  {
                    $table_model->items[$key]->$first_table_header .= '<div class="row-actions">'.implode(" | ", $btns0).'</div><div class="row-actions">'.implode(" | ", $btns).'</div>';
                } else {

                    $table_model->items[$key]->$first_table_header .= '<div class="row-actions">'.implode(" | ", $btns).'</div>';
                }

                if ($show_chekboxes ) {
                    $table_model->items[$key]->__checkbox_ = '<input class="js-dbp-table-checkbox" type="checkbox" value="'.$count_unique_id.'">';
                }
            }

            // Preparo la variabile da controllar per il bottone new. L'unica cosa che mi interessa è che ci siano chiavi primarie visualizzate nella query
            foreach ($table_header as $k=>$th) {
                if (isset($th->table) && isset($th->original_table) && isset($primaries[$th->table]) && $primaries[$th->table] == $th->original_name) {
                    $table_model->add_table_for_btn_new_record($th->original_table.".".$primaries[$th->table]);
                } 
            } 
        }
    }

    /**
     * Trova i valori delle colonne primarie di un record
     * $row = $table_model->items[$key]
     * @param [type] $table_model
     * @param string $key_name il nome che si vuole estrarre. Se si vuole estrarre il nome del campo allora scrivo "name.
     * @return void
     */
    static function data_primaries_values($primaries, $table_header, $row, $key_name ='name_request') {
        $primary_values = [];
        //var_dump ($row);
        //var_dump ($table_header);
        //die();
        foreach ($table_header as $k=>$th) {
            //print ("<p>".$th->original_table." == ". $primaries[$th->original_table]." > ".$row->$k."</p>");
            if (isset($th->table) && $th->original_table != "" && isset($primaries[$th->original_table]) && $primaries[$th->original_table] == $th->original_name && $row->$k > 0) {
                $primary_values[$th->$key_name] =  $row->$k;
            } 
        } 
        return $primary_values;
    }


    /**
     * Trova i valori delle colonne primarie di un record
     * $row = $table_model->items[$key]
     * @param [type] $table_model
     * @param string $key_name il nome che si vuole estrarre. Se si vuole estrarre il nome del campo allora scrivo "name".
     * @return void
     */
    static function data_primaries_values_from_schema($primaries, $table_header_schema, $row, $key_name ='name_request') {
        $primary_values = [];
        //var_dump ($row);
        //var_dump ($table_header);
        //die();
        if (is_array($table_header_schema) || is_object($table_header_schema)) {
            foreach ($table_header_schema as $k=>$th) {
         //    print ("<p>".$th['schema']->orgname." == ". $primaries[$th['schema']->orgtable]." > ".$row->$k."</p>");
                if (isset($th['schema']->table) && $th['schema']->orgtable != "" && isset($primaries[$th['schema']->orgtable]) && $primaries[$th['schema']->orgtable] == $th['schema']->orgname && $row->$k > 0) {
                
                    $primary_values[$th['schema']->$key_name] =  $row->$k;
                } 
            } 
        }
        return $primary_values;
    }

    /**
     * Rimuove tutte le colonne che devono essere nascoste in un'estrazione di un model
     *
     * @param [type] $table_model
     * @return void
     */
    static function remove_hide_columns(&$table_model) {
        if (!is_countable($table_model->items) || count($table_model->items) == 0 ){
            return false;
        }
        $table_header = reset($table_model->items);
        foreach ($table_model->items as $key => $_) {
            foreach ($table_header as $k=>$th) {
                if (isset($th->toggle) && $th->toggle == "HIDE") {
                    if (is_array($table_model->items[$key])) {
                        unset($table_model->items[$key][$k]);
                    } else if (is_object($table_model->items[$key])) {
                        unset($table_model->items[$key]->$k);
                    }
                }
            }
        }
        //echo (htmlentities(json_encode($table_model->items)));
    }

    /**
     * Rimuove tutte le colonne che devono essere nascoste in una riga di un'estrazione
     *
     * @param [type] $table_model
     * @return array
     */
    static function remove_hide_columns_in_row($table_header, $row) {
        foreach ($table_header as $k=>$th) {
            if (isset($th->toggle) && $th->toggle == "HIDE") {
                if (is_array($row) && array_key_exists($k, $row)) {
                    unset($row[$k]);
                } else if (is_object($row) && property_exists($row, $k)) {
                    unset($row->$k);
                }
            }
        }
        return $row;
        //echo (htmlentities(json_encode($table_model->items)));
    }

    /**
     * Cancella una serie di record specifici
     * 
     * @param array $rids [["table_alias.column_pri"=>value, ]] || [' {"table_alias.id":"val"}','{"table_alias.id":"val"}']
     * @param string $sql

     * @return array [error,sql] executed
     */
    static function delete_rows($rids, $request_sql = "") {
        global $wpdb;
      
		if ($request_sql != "") {
			$queries = self::prepare_delete_rows($rids, $request_sql);
        } 
        $errors = [$queries['error']];
        $return_sql = [];
        foreach ($queries['sql'] as $sql) {
            if (!$wpdb->query($sql)) {
                $errors[] = $wpdb->last_error;
            } else {
                $return_sql[] = $sql;
            }
        }
        return ['error'=> implode("\n", $errors), 'sql'=> implode("\n", $return_sql)];
    }

    /**
     * Prepara le query per rimuove le righe selezionate. Scegli se lavorare tramite query o dbp_id
     * @param array $rids [["table_alias.column_pri"=>value, ]] || [' {"table_alias.id":"val"}','{"table_alias.id":"val"}']
     * @param string $sql
     * @return array the errors or "" if ok.
     */
    static function prepare_delete_rows($rids, $request_sql = "") {
        $error = $sql = $items = $checkboxes = [];
        $show_msg = '';
        if (is_countable($rids)) {
            if ($request_sql != "") {
                $form = new Dbp_class_form($request_sql);
            }  else {
                return ['error' => 'Important data is missing.', 'sql' => [], 'items' => [], 'checkboxes' => []];
            }
            list($settings, $table_options) = $form->get_form();
            $table_options = array_shift($table_options);
            foreach ($rids as $ids) {
                if (is_string($ids)) {
                    $ids = json_decode(wp_unslash($ids));
                }
                foreach ($ids as $column => $id) {
                    foreach ($table_options as $table_option) {
                        if ($column == $table_option->table . "." . $table_option->pri_orgname) {
                            $option = dbp_fn::get_dbp_option_table($table_option->orgtable);
                            if ($option['status'] != "CLOSE") {
                                $sql[] = 'DELETE FROM `' . Dbp_fn::sanitize_key($table_option->orgtable) . '` WHERE `' . Dbp_fn::sanitize_key($table_option->orgtable) . '`.`' . Dbp_fn::sanitize_key($table_option->pri_orgname) . '` = \''.absint($id).'\'';
                                
                                $add_item = true;
                               
                                if ($add_item) {
                                    $items[] = '<span class="dbp-cm-keyword">DELETE FROM</span> `' . $table_option->orgtable . '` <span class="dbp-cm-keyword">WHERE</span> ' . $table_option->orgtable . '.' . $table_option->pri_orgname . '= \''.esc_sql($id).'\'';
                                    
                                    $checkboxes[] = esc_attr(json_encode([$column => $id]));
                                }
                            } else {
                                $errors[] = sprintf(__('Records in the "%s" table cannot be removed because they are in a closed state. If you want to be able to remove the data, change the status from the table structure to "published"', 'db_press'), $table_option->orgtable);
                            }
                        }
                    }
                 
                }
            }
        }
        return ['error' => implode("\n",$error), 'sql' => $sql, 'items' => $items, 'checkboxes' => $checkboxes, 'show_msg' => $show_msg];

   
    }

    /**
	 * bulk delete on sql: 
	 * Elimino i dati rispetto ad una tabella scelta dalla query 
	 * (solo una perché per più potrebbe non funzionare)
     * @param String $sql La query da cui partire per eliminare i dati
     * @param String $table_choose la tabella su cui eliminare i dati 
     * (potrebbe esserci più di una tabella interessata nella query, si eliminano i dati da una sola tabella)
     * @return String error 
	 */
	static function dbp_delete_from_sql($sql, $table_choose) {
		global $wpdb;
		dbp_fn::require_init();
		$table_model = new Dbp_model();
        $error = __("There was an unexpected problem", 'db_press'); 
		$table_model->prepare($sql);
		$table_items = $table_model->get_list();
        if ($table_model->last_error ) {
            return $table_model->last_error."<br >".$table_model->get_current_query();
        }
        if (count($table_items) < 2) {
            return $error;
        }
		$header = array_shift($table_items);
		// trovo le tabelle interessate
		$temp_groups = [];
		foreach ($header as $key=>$th) {
			if (isset($th['schema']->table) && isset($th['schema']->orgtable) && $th['schema']->table != "" && $th['schema']->orgtable != "") {
				if (!isset($temp_groups[$th['schema']->table])) {
					//$temp_groups[$th['schema']->table] =['table'=>$th['schema']->orgtable, 'pri' => dbp_fn::get_primary_key($th['schema']->orgtable)];
					$id = dbp_fn::get_primary_key($th['schema']->orgtable);
					$new_id_schema = dbp_fn::find_primary_key_from_header($header, $th['schema']->table, $id);
					if ($id != "" && $new_id_schema != false) {
						$table_model->list_change_select('`'.$new_id_schema->table.'`.`'.$new_id_schema->name.'`');
                        $table_model->remove_limit();
                        $new_query = 	$table_model->get_current_query();
						if ($new_query != "" && $new_id_schema->table == $table_choose) {
                            $error = '';
                            $option = self::get_dbp_option_table($th['schema']->orgtable);
                            if ($option['status'] != "CLOSE") {
                                $sql_to_del = 'DELETE FROM `'.$th['schema']->orgtable.'` WHERE `'.esc_sql($id).'` IN ('.$new_query.')';
                                if (!$wpdb->query($sql_to_del)) {
                                    $error = __(sprintf('Query "%s" returned an error', $sql_to_del), 'db_press');
                                } 
                            } else {
                                $error = __(sprintf('Records in the "%s" table cannot be removed because they are in a closed state. If you want to be able to remove the data, change the status from the table structure to "published"', $th['schema']->orgtable), 'db_press');
                            }
							break;
						}
					}
				}
			}
		}
        return $error;
	}


    /**
     * Setto i cookie è una funzione generica.
     */
    static function set_cookie($variable, $value) {
        setcookie('dbp_'.$variable, $value, time() + 86400);
        $_COOKIE['dbp_'.$variable] =  $value;
    }

    /**
     *  i messaggi ok dai cookie
     */
    static function get_msg_cookie() {
        return (isset(self::$cookie_msgs['dbp_msg'])) ? self::$cookie_msgs['dbp_msg'] : '';
    }
    /**
     *  i messaggi di errore dai cookie
     */
    static function get_error_cookie() {
        return (isset(self::$cookie_msgs['dbp_error'])) ? self::$cookie_msgs['dbp_error'] : '';
    }

    /**
     *  Trastoprto le informazioni dei messaggi dei cookie dentro la variabile statica cookie_msgs
     *  Questa funzione deve essere chiamata dall'init del loader
     */
    static function init_get_msg_cookie() {
        self::$cookie_msgs = [];
        if (isset($_COOKIE['dbp_msg'])) {
            self::$cookie_msgs['msg'] = $_COOKIE['dbp_msg'];
            setcookie("dbp_msg", "", time() - 3600);
        }
        if (isset($_COOKIE['dbp_error'])) {
            self::$cookie_msgs['error'] = $_COOKIE['dbp_error'];
            setcookie("dbp_error", "", time() - 3600);
        }
       
    }

    /**
     * trovo l'alias della tabella di cui si sta facendo il join
     * @param String $other_name Se presente aggiunge altri 3 caratteri al nome
     * esempio wp_post pos
     */
    static function get_table_alias($table, $query, $other_name = "") {
        global $wpdb;
        $table_alias_temp  = substr(dbp_fn::clean_string(str_replace($wpdb->prefix, "", $table)),0 ,3);
        if (strlen($table_alias_temp) < 3 ) {
            $table_alias_temp = $table_alias_temp.substr(md5($table_alias_temp),0 , 2);
        }
       
        if ($other_name != "") {
            $table_alias_temp .= self::get_table_alias($other_name, "");
        }
        $table_alias = $table_alias_temp;
        $count_ta = 1;
        while(stripos($query, $table_alias.'`') !== false || stripos($query, $table_alias.' ') !== false) {
            $table_alias = $table_alias_temp.''.$count_ta;
            $count_ta++;
        }
        return $table_alias;
    }

    /**
     * Creo un alias di una colonna e verifico che non sia già stata usata
     * @param String $string Il nome della colonna
     * @param String Query la query per evitare colonne con lo stesso nome 
     * La query devo prenderla da get_current_query() altrimenti è compressa!!
     */
    static function get_column_alias($string, $query) {
        $alias = $alias_temp =substr(dbp_fn::clean_string($string),0, 60);
        
        $count_alias = 1;
        // verifico che l'alias della colonna non sia stato già usato
        while(stripos($query, $alias.'`') !== false || stripos($query, $alias.' ') !== false) {
            $alias = $alias_temp.''.$count_alias;
            $count_alias++;
        }
        return $alias;
    }

    /**
     * Separa una stringa secondo una serie di occorrenze definite in un array 
     * @param Array $separators L'elenco delle parole per cui dividere la stringa
     * @param String $string la stringa da dividere
     * @param Bool $add_separators Aggiunge nei risultati anche i delimitatori es: [ris1, delimiter, ris2]
     * @return Array ritorna un array di stringe divise dai delimitatori
     */
    static function multi_explode($separators, $string, $add_separators = false) {
        $delimiter = 1;
        $old_delimiter = 0;
        $offset  = 0;
        $explode = [];
        while($delimiter > 0) {
            $current_delimiter = '';
            $delimiter = 0;
            $curr_pos = strlen($string);
            foreach ($separators as $separator) {
                $pos = stripos($string, $separator, $offset);
                if ($pos < $curr_pos && $pos !== false) {
                    $curr_pos = $pos;
                    $delimiter = strlen($separator);
                    $current_delimiter = $separator;
                }
            }
            if ($curr_pos > 0) {
               
                if ($add_separators) {
                    $explode[] =  substr($string, $offset  , $curr_pos -  $offset );
                    $explode[] = $current_delimiter;
                } else {
                    $explode[] =  substr($string, $offset - $old_delimiter , $curr_pos -  $offset + $old_delimiter);
                }
            }
            $offset = $curr_pos + $delimiter;
            $old_delimiter = $delimiter;
        }
        return $explode;
    }
    /**
     * Trova la prima occorrenza tra una serie di delimitatori
     * @param Array $delimiters L'elenco delle parole per cui dividere la stringa
     * @param String $string la stringa da dividere
     * @return Array [position, delimiter]
     */
    static function find_first($needles, $string, $offset = 0) {
        $curr_pos = strlen($string);
        $delimiter = "";
        foreach ($needles as $needle) {
            $pos = stripos($string, $needle, $offset);
            if ($pos < $curr_pos && $pos !== false) {
                $curr_pos = $pos;
                $delimiter = $needle;
            }
        }
        if ($curr_pos == strlen($string)) {
            $curr_pos = 0;
        }
        return [$curr_pos, $delimiter];
    }


    /**
     * Quando genero una form per modificare o inserire un record da una query
     * per ogni tabella collegata che restituisce risultati senza ID mostro tutti i campi della tabella 
     * oltre a quelli selezionati dalla query. Modifica gli $items e i $field_name
     * @param dbpDs_field_param[] $table_param
     */
    /*
    static function adding_external_to_query_fields($info, &$item, &$table_param) {
        $fields =  self::get_table_structure($info['schema']->orgtable);
        foreach ($fields as $field) {
            $new_field_name = 'edit_table['.$info['count_form_block'].']['.$info['schema']->orgtable.']['.$field->Field.'][]';
            $find = false;
            foreach ($table_param as $param) {
                if ($param->name == $field->Field) {
                    $find = true;
                    break;
                }
            }
            if (!$find) {
                $item[$field->Field] = '';
                $field_name[$field->Field] = $new_field_name ;
                if ($field->Type == "DATE") {
                    $type = "DATE";
                } elseif ($field->Type == "DATETIME") {
                    $type = "DATETIME";
                } elseif ($field->Type == "TEXT" || $field->Type == "MEDIUMTEXT" || $field->Type == "LONGTEXT" || $field->Type == "TINYTEXT") {
                    $type = "TEXT";
                } else {
                    $type = "VARCHAR";
                }
              //  print " FIELD FIELD: ". $field->Field." \n\r";
                $table_param[$field->Field] = new DbpDs_field_param(['name'=>$field->Field,'orgtable'=>$info['schema']->orgtable, 'table'=>$info['schema']->table, 'label'=>$field->Field, 'field_name'=> "edit_table[".$info['count_form_block']."][".$info['schema']->orgtable."][".$field->Field."][]", 'form_type'=>$type]);
            }
        }
       // $item['my_custom_field'] = 23;
       // $field_name['my_custom_field'] = 'edit_table['.$info['schema']->orgtable.'][my_custom_column][]';
    }
    */

    /**
	 * Nella visualizzazione dei dati e modifica, se c'è una query che ritorna più righe di risultato 
	 * cerco di raggrupparli per tabelle
     * @param Array $table_items il risultato di model->get_list
     * @param boolean $convert_value per le view
     * @param boolean $add_orphan per le view
	 * @return Array in cui ogni item è il risultato di una tabella
	 */
	static function convert_table_items_to_group($table_items, $convert_value = true, $add_orphan = true) {
		$header = array_shift($table_items);
		// Divido in gruppi a seconda della tabella
		$items = [];
		foreach ($table_items as $item) {
			// preparo il gruppo
			$temp_groups = [];
			foreach ($header as $key=>$th) {
			//	print "\n".$th['schema']->table."\n";
				if ($th['schema']->table != "") {
					if (!isset($temp_groups[$th['schema']->table])) {
						$temp_groups[$th['schema']->table] = [];
					}
					if ($convert_value) {
						$temp_groups[$th['schema']->table][$key] = self::format_single_detail_value($item->$key);
					} else {
						$temp_groups[$th['schema']->table][$key] =$item->$key;
					}
				} else {
					if (!isset($temp_groups['__orphan__'])) {
						$temp_groups['__orphan__'] = [];
					}
					if ($convert_value) {
						$temp_groups['__orphan__'][$key] =  self::format_single_detail_value($item->$key);
					} else {
						$temp_groups['__orphan__'][$key] = $item->$key;
					}
				
				}
			}
			// inserisco il gruppo
            $count_group = 1;
			foreach ($temp_groups as $key=>$group) {
                $count_group++;
                if ($key != '__orphan__') {
				    $items["gr".$count_group] = $group;
                } else if ($add_orphan) {
                    $items[$key] = $group;
                }
			}
		}
		return $items;
	}

    /**
	 * Formatta un valore per la visualizzazione nel datail row
	 */
	static function format_single_detail_value($value) {
		$value = maybe_unserialize($value);
		$htmlentities = true;
		if (is_object($value) || is_array($value)) {
			$htmlentities = false;
			$value = dbp_items_list_setting::show_obj($value, 1,  20000, 100, 1000);
		} else {
			$value2 = json_decode($value, true);
			if (json_last_error() == JSON_ERROR_NONE) {
				$htmlentities = false;
				$value = dbp_items_list_setting::show_obj($value2, 1, 20000, 100, 1000);
			} 
		} 
		if ($htmlentities) {
			$value = htmlentities($value);
		}
		return $value;
	} 

    /**
     * Aggiunge ai select le chiavi primarie per ogni tabella inserita 
     * così da poter gestire form di modifica ed inserimento.
     * @deprecated v0.4 sostituito con $table_model->add_primary_ids
     * @return Void
     */
    static function add_primary_ids_to_sql( &$table_model, &$table_items) {
        $current_query_select = $table_model->get_partial_query_select();
        if (!is_countable($table_items)) return;
        $header = reset($table_items);
        if (!is_countable($header)) return;
        // Preparo i dati:
        // Trovo tutte le chiavi primarie di ogni tabella interessata
        // e raggruppo i campi per tabella
        $all_pri_ids = [];
        $field_group = [];
        foreach ($header as $key=>$th) {
            if ($th['schema']->orgtable != "") {
                // mi segno la chiave primaria della tabella
                if (!array_key_exists($th['schema']->orgtable, $all_pri_ids)) {
                    $all_pri_ids[$th['schema']->orgtable] = self::get_primary_key($th['schema']->orgtable);
                }
                // Raggruppo i campi per tabella (alias)
                if ($th['schema']->table != "") {
                    if (!isset($field_group[$th['schema']->table])) {
                        $field_group[$th['schema']->table] = ['table'=>$th['schema']->orgtable, 'alias_table'=>$th['schema']->table, 'fields'=>[]];
                    }
                    $field_group[$th['schema']->table]['fields'][] = $th['schema'];
                }
            }
        }
        $all_pri_ids = array_filter($all_pri_ids);
  
        // verifico se c'è la chiave primaria, oppure segno che deve essere aggiunta
        $add_select_pri = [];
        foreach ( $field_group as $group) {
          
            // group [table:String, fields:[]]
            $exist_pri = false;
            if (isset($all_pri_ids[$group['table']])) {
                // verifico se è già presente la chiave primaria
                foreach ($group['fields'] as $fields) {
                    if ($fields->orgname == $all_pri_ids[$group['table']]) {
                        $exist_pri = true;
                        break;
                    }
                }
                if (!$exist_pri) {
                    $alias = dbp_fn::get_column_alias($group['alias_table']."_".$all_pri_ids[$group['table']], $current_query_select);
                    $add_select_pri[] =  '`'. $group['alias_table'].'`.`'.$all_pri_ids[$group['table']].'` AS `'.$alias.'`';
                    $current_query_select .= " ".$alias;

                }
            }
        }
        
        // aggiungo i nuovi select, ripeto la query e aggiorno table_items
        if (count($add_select_pri) > 0) {
            $table_model->list_add_select(implode(", ", $add_select_pri));
           
            $table_items = $table_model->get_list();
           
            // se la query non risponde nessun dato (tipo una nuova tabella), allora  ne creo uno dall'header
            if (is_countable($table_items) && count($table_items) == 1) {
                $header = reset($table_items);
                $new_item = (object)[];
                foreach ($header as $kh=>$_) {
                    $new_item->$kh = "";
                }
                $table_items = [$header, $new_item];
            }
        }
    }

    /**
     * inposta una variabile javascript per stampare le variabili pinacode
     */
    static function echo_pinacode_variables_script($array) {
        $array = (array)$array;
       
        $shortcode = [];
        foreach($array as $key=>$value) {
            if (is_array($value)) {
                foreach($value as $kv=>$vv) {
                    if (stripos($vv, " ") === false) {
                        $shortcode[] = "[%".$key.".".$vv."]";
                    } else {
                        $shortcode[] = "[%".$key." get=\"".addslashes($vv)."\"]";
                    }
                }
            } else {
                if (stripos($value, " ") === false) {
                    $shortcode[] = "[%".$value."]";
                } 
            }
        }
        ?><script>
        var dbp_pinacode_vars = <?php echo json_encode($shortcode); ?>;
        </script>
        <?php 
    }

    /**
    * converte le informazioni di formattazione colonna.
    * Sono i csv che stanno nelle informazioni grafiche della lista (style)
    * il primo parametro del csv è il valore  oppure un intervallo =1-23 oppure <99 o >99 )
    */
    static function column_formatting_convert($format_csv_values, $value, $default = "") {
        if ($format_csv_values == NULL) return $default;
        $lines = explode(PHP_EOL, $format_csv_values);
       
        foreach ($lines as $line) {
           if (trim($line) != "") {
               $temp_array = str_getcsv($line);
               if (count($temp_array) > 1) {
                   $if = trim(array_shift($temp_array));
                   if (substr($if,0,1) == "=" && strpos($if,"-") !== false) {
                       $temp_if = explode("-",substr($if,1));
                       if ($value >= $temp_if[0] && $value <= $temp_if[1]) {
                           return implode(", ", $temp_array);
                       }
                   } else if (substr($if,0,1) == '<') {
                       $temp_if = trim(str_replace('<', '', substr($if,1)));
                       if ($value < $temp_if) {
                           return implode(", ", $temp_array);
                       }
                   } else if (substr($if,0,1) == '>') {
                       $temp_if = trim(str_replace('>', '', substr($if,1)));
                       if ($value > $temp_if) {
                           return implode(", ", $temp_array);
                       }
                   } elseif (trim($value) == $if) {
                       return implode(", ", $temp_array);
                   }
               }
           }
       }
       return $default;
       
   }

   /**
    * Fa il parsing delle righe csv di options
    */
    static function parse_csv_options($string) {
        if ($string == "") return [];
        $lines = explode(PHP_EOL, $string);
        $options = [];
        foreach ($lines as $line) {
            if (trim($line) != "") {
                $temp_array = str_getcsv($line);
                if (count($temp_array) == 1) {
                    $options[] = ['label'=>self::all_trim(reset($temp_array))];
                } else if (count($temp_array) >= 2) {
                    $options[] = ['value'=>self::all_trim(array_shift($temp_array)), 'label'=>self::all_trim(implode(",", $temp_array))];
                }
            }
        }
        return $options;
    }
    

    /**
    * Ritrasforma un array in una stringa per la visualizzazione nel textarea dell'options.
    * 
    */
    static function stringify_csv_options($array) {
  
        if (is_countable($array)) {
            $string = [];
            foreach ($array as $key=>$option) {
                if (strpos($option['label'], PHP_EOL)) {
                    $option['label'] = '"'.$option['label'].'"';
                }
                if (array_key_exists('value', $option)) {
                    $string[] = $option['value'].", ". $option['label'];
                } else {
                    $string[] = $option['label'];
                }
            }
            return implode(PHP_EOL, $string);
        }
        if (is_string($array)) {
            return $array;
        } else {
            return '';
        }
    }

    /**
     * Stampa l'icona dell'info
     * @param String $file Il nome del file della documentazione senza .php
     * @param String $anchor L'id del div che si sta ancorando senza dbp_help_
     */
    static function echo_html_icon_help($file, $anchor) {
        ?><span class="dashicons dashicons-info dbp-help-info-icon" onclick="anchor_help('<?php echo esc_attr($file); ?>','<?php echo esc_attr($anchor); ?>')" title="<?php _e('click_me', 'db_press'); ?>"></span><?php
    }


    /**
     * Le tre funzioni is_form_open open_form e close form servono ad identificare se
     * c'è una form aperta dal plugin così da non aprirne altre se ci sono tabelle annidate.
     * Verifica se una form è aperta oppure no
     *
     * @return array
     */
    static function is_form_open() {
        return self::$is_form_open;
    }
    static function set_open_form() {
        self::$is_form_open = true;
    }
    static function set_close_form() {
        self::$is_form_open = false;
    }


    /**
     * Verifica se è stato passato almeno un filtro 
     */
    static function is_query_filtered() {
        if (isset($_REQUEST['filter']['search']) && is_array($_REQUEST['filter']['search'])) {
            foreach ($_REQUEST['filter']['search'] as $key => $value) {
                if (isset($value['value']) && $value['value'] != "") {
                    return true;
                }
            } 
        }
        return false;
    }

    /**
     * Cerca e sostituisce nei dati serializzati
     */
    static function search_and_resplace_in_serialize($obj, $search, $replace) {
        if (is_array($obj)) {
            foreach ($obj as $key=>$o) {
                if (is_array($o) || is_object($o)) {
                    $obj[$key] = self::search_and_resplace_in_serialize($o, $search, $replace);
                } else {
                    $obj[$key] = str_ireplace($search, $replace, $o);
                }
            }
        } else if (is_object($obj)) {
            foreach ($obj as $key=>$o) {
                if (is_array($o) || is_object($o)) {
                    $obj->$key= self::search_and_resplace_in_serialize($o, $search, $replace);
                } else {
                    $obj->$key = str_ireplace($search, $replace, $o);
                }
            }
        } else {
            $obj = str_ireplace($search, $replace, $obj);
        }
        return $obj;
    }

    /**
     * Ritorna una stringa alfanumerica che può essere passata nell'url con gli id del record.
     */
    static function ids_url_encode($table_model, $current_item) {
      
        $primaries = $table_model->get_pirmaries();	
		$ids = dbp_fn::data_primaries_values_from_schema($primaries, reset($table_model->items), $current_item);
    
        return rtrim(strtr(base64_encode(json_encode($ids)), '+/', '-_'), '=');
    }
    /**
     * Ritorna una stringa alfanumerica che può essere passata nell'url con gli id del record.
     */
    static function ids_url_encode2($post_content, $current_item) {
      
        $primaries = $post_content['primaries'];	
		$ids = dbp_fn::data_primaries_values_from_schema($primaries, $post_content['schema'], $current_item);
    
        return rtrim(strtr(base64_encode(json_encode($ids)), '+/', '-_'), '=');
    }
    
     
     /**
     * Ricevuta la stringa alfanumerica ne ritorna l'arrai con gli id
     */
    static function ids_url_decode($data) {
        $data = base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
        $data = (array) json_decode(wp_unslash( $data ));
        return $data;
    }
    

}
// setto 'inizio dell'esecuzione dello script;
dbp_fn::$execute_time_start = microtime(true);

