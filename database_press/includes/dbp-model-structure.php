<?php
/**
 * Serve per creare/alterare una tabella
 * 
 * @todo esempio su come si usa
 */
namespace DbPress;

class  Dbp_model_structure
{
     /**
     * @var String $table_name La tabella principale della query
     */
    public $table_name = "";
     /**
     * @var Array $columns Le colonne da inserire nella query
     */
    public $columns = [];
    /**
     * @var String $primary_key le colonne che sono primary
     */
    public $primary_key = '';
     /**
     * @var Array $primary_key le colonne che sono primary
     */
    public $key = [];
    /**
     * @var Array $name_list L'elenco dei nomi delle colonne perché è vietato ripetere lo stesso nome 2 volte
     */
    public $name_list = [];
    /**
     * @var String $last_error
     */
    public $last_error = "";
     /**
     * @var Boolean $use_prefix
     */
    public $use_prefix = false;

    /**
     * @var Boolean $sort Non accetta la possibilità di ordinare le colonne
     */
    public $sort = false;
     /**
     * @var Boolean $filter Non permette di filtrare le colonne
     */
    public $filter = false;
    /**
     * @var Boolean $error_primary Verifica se la primary_key è strutturata bene
     */
    public $error_primary = false;
     /**
     * @var Array $columns la struttura della tabella
     */
    public $items = [];

    /**
     * Se table_name è vuoto basta che prepare sia popolato!
     */
    public function __construct($table_name = "") {
        $table_name = str_replace(['  ',' ','-'], "_", $table_name);
		$table_name = Dbp_fn::sanitize_key($table_name);
        $this->table_name = $table_name;
	}

    /**
     * Dal tipo di colonna di mysql show column splitto type e length 
     */
    static function reverse_type($type) {
        $sql_type  = "";
        $length = "";
        $attributes = "";
        if ( strpos($type, " ") != false) {
            $temp = explode(" ", $type);
            $attributes = strtoupper(array_pop($temp));
            $type = implode(" ", $temp);
        }
        if ( strpos($type, "(") != false) {
            $temp = explode("(", $type);
            $sql_type = $temp[0];
            $length = str_replace(")", "", $temp[1]);
        } else {
            $sql_type = $type;
        }
        $sql_type = strtoupper($sql_type);
        return [$sql_type, $length, $attributes];
    }

    /**
     * Torna l'elenco dei possibili campi di mysql
     * https://blog.devart.com/mysql-data-types.html
     */
    static function column_list_type() {
        return ['INT','VARCHAR','TEXT','DATE', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT','DECIMAL', 'FLOAT',  'DOUBLE', 'REAL' ,  'BIT', 'BOOLEAN', 'DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR', 'CHAR', 'VARCHAR','TINYTEXT', 'TEXT',  'MEDIUMTEXT', 'LONGTEXT', '-', 'BINARY', 'VARBINARY', 'TINYBLOB' , 'BLOB', 'MEDIUMBLOB', 'LONGBLOB', 'ENUM', 'SET', 'JSON', 'GEOMETRY', 'POINT', 'LINESTRING', 'POLYGON', 'MULTIPOINT', 'MULTILINESTRING', 'GEOMETRYCOLLECTION' ];
        $select[] = ['INT','VARCHAR','TEXT','DATE'];
        $select['Numeric']  = ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT', '-', 'DECIMAL', 'FLOAT',  'DOUBLE', 'REAL' , '-', 'BIT', 'BOOLEAN', ];
        $select['Date and time']  = ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR' ];
        $select['String'] = ['CHAR', 'VARCHAR', '-', 'TINYTEXT', 'TEXT',  'MEDIUMTEXT', 'LONGTEXT', '-', 'BINARY', 'VARBINARY', '-', 'TINYBLOB' , 'BLOB', 'MEDIUMBLOB', 'LONGBLOB', '-', 'ENUM', 'SET', '-', 'JSON' ];
        $select['Spatial'] = ['GEOMETRY', 'POINT', 'LINESTRING', 'POLYGON', 'MULTIPOINT', 'MULTILINESTRING', 'GEOMETRYCOLLECTION'];
    }

    /**
     * Preparo l'array con le info di una colonna, Lo eseguo con get_create_sql
     * @return boolean
     */
    public function insert_column($name, $type, $length = 0,  $default ="", $primary_key=false, $attributes=false, $auto_increment= false, $null=false,   $comment="") {
        $sql_string = [];
        $name = substr(dbp_fn_structure::clean_column_name($name),0 , 64);
        if ($name == "") {
            return false;
        }
        if (in_array($name, $this->name_list)) {
            $this->last_error = __("Duplicated column name!", 'db_press');
            return false;
        }
        $this->name_list[] = $name;
        $sql_string[] = '`'.esc_sql($name).'`';
        if ($primary_key) {
            if (strtoupper($type) == "INT" || $type == "BIGINT") {
                $sql_string[] = $this->conv_type($type, $length);
            } else {
                $type = "int";
                $length = 10;
                $sql_string[] = 'int(10)';
            }
        } else {
            $sql_string[] = $this->conv_type($type, $length);
        }


        if ($attributes && in_array(strtoupper($attributes), ['BINARY', 'UNSIGNED', 'UNSIGNED ZEROFILL', 'ON UPDATE CURRENT_TIMESTAMP'])) {
            if ($attributes == 'UNSIGNED' || $attributes ==  'UNSIGNED ZEROFILL') {
                if (in_array(strtoupper($type), ['INT','FLOAT','BIGINT','MEDIUMINT','TINYINT','DECIMAL']) ) {
                    $sql_string[] = $attributes;
                } 
            } else if ( strtoupper($type) == 'ON UPDATE CURRENT_TIMESTAMP') {
                if (in_array(strtoupper($type), ['DATE','DATE','TIMESTAMP']) ) {
                    $sql_string[] = $attributes;
                } 
            } else {
                $sql_string[] = $attributes;
            }
        }

        if (!$null) {
            $sql_string[] = 'NOT NULL';
        } else {
            $sql_string[] = 'NULL';
        }
        if ($auto_increment || $primary_key) {
            $auto_increment = true;
            if (!in_array(strtoupper($type), ['TEXT','VARCHAR','CHAR','BIGTEXT','MEDIUMTEXT', 'DECIMAL'])) {
                $sql_string[] = 'AUTO_INCREMENT';
                if (!$primary_key) {
                    $this->key[] = '`'.esc_sql($name).'`';
                }
            } else {
                $auto_increment = false;
            }
        }

        if ($primary_key) {
            if ( $this->primary_key != "") {
                $this->last_error = __('Only one primary key field is allowed', 'db_press');
            } else {
                $this->primary_key = '`'.esc_sql($name).'`';
            }
        }
      
        if ($comment != "") {
            $sql_string[] = "COMMENT '".str_replace(["\r","\n","\t"]," ", esc_sql($comment))."'";
        }

        if (!$auto_increment) {
            if ($default != "") {
                $sql_string[] = "DEFAULT '".esc_sql(wp_unslash($default))."'";
            } else if (!$null) {
                if (in_array(strtoupper($type), ['INT','FLOAT','BIGINT','MEDIUMINT','TINYINT','DECIMAL'])) {
                    $sql_string[] = "DEFAULT 0";
                } else if (in_array(strtoupper($type), ['TEXT','VARCHAR','CHAR','BIGTEXT','MEDIUMTEXT'])) {
                    $sql_string[] = "DEFAULT ''";
                }
            }
        }
        $sql_string = array_unique($sql_string);
        $sql_string = array_filter($sql_string);
        if (count($sql_string) > 0) {
            $this->columns[$name]= implode(" ", $sql_string);
            return true;
        } else {
            return false;
        }
        
    }


    public function get_sql_drop_column($column) {
        $return = ['ALTER TABLE  `'.$this->table_name.'` DROP COLUMN  `'.$column->field_name.'` ;'];
        return $return;
    }
    /**
     * Passa la colonna del db e i dati del form e ne crea la query per modificarla.
     * Prima però verifica se è da modificare oppure no!
    * column: OBJECT:	["field_name"]=> string(2) "ID" ["field_type"]=> string(6) "BIGINT" ["field_length"]=> string(2) "20" ["attributes"]=> string(8) "UNSIGNED" ["null"]=> string(1) "f" ["default"]=> NULL ["primary"]=> string(1) "t" 
     * @param Array $column è l'elenco delle colonne originali
     * @param String $key_column l'id della colonna
     * @param Array $req_update è l'array che arriva direttamente dalla form
     * @param $string $key_req
	 * @return Array un'array di query da eseguire	
     */
    public function get_sql_alter_column($columns, $key_column, $req_update, $key_req, $position) {
        $change = false;
        $result = [];
        $column = $columns[$key_column];
        if ($req_update['field_name'][$key_req] != $column->field_name ) {
            $change = true;
        }
        if (($req_update['field_type'][$key_req] != $column->field_type || ($req_update['field_length'][$key_req] != $column->field_length AND ($req_update['field_length'][$key_req] > 0 && !in_array(strtoupper($column->field_type),['INT','TINYINT','BIGINT','SMALLINT','MEDIUMINT'] ))) || $req_update['attributes'][$key_req] != $column->attributes) || ($req_update['default'][$key_req] != $column->default ) || ($req_update['null'][$key_req] != $column->null ) ) {
           // print (" CHANGE > ".$req_update['field_type'][$key_req]." != ".$column->field_type." || (".$req_update['field_length'][$key_req]." != ".$column->field_length." AND ".$req_update['field_length'][$key_req]." > 0) || ".$req_update['attributes'][$key_req]." != ".$column->attributes.") || ( ".$req_update['default'][$key_req]." != ".$column->default." ) || ( ".$req_update['null'][$key_req]." !=  ".$column->null .") ");
            $change = true;
        }
        if ($req_update['default'][$key_req] != $column->default  || $change) {
            $change = true;
        }

        $new_position = "";
        if ($position != "") {
            if ($position != "FIRST!") {
                if ($req_update['field_original_position'][$key_req] != $position) {
                    $change = true;
                    $new_position = " AFTER `".$position."`"; 
                }
            } else if ($position == "FIRST!") {
                if ($req_update['field_original_position'][$key_req] != $position) {
                    $change = true;
                    $new_position = " FIRST";
                }
            } 
        } 

        $alter = $this-> get_structure_field_to_alter($column->field_name, $req_update['field_name'][$key_req], $req_update['field_type'][$key_req], $req_update['field_length'][$key_req],  $req_update['attributes'][$key_req], $req_update['null'][$key_req], $req_update['default'][$key_req], $req_update['primary'][$key_req], $column->primary ).$new_position;
       
        // Non gestisco l'eliminazione di una primary key, ma solo il cambio, perché tanto una ci deve essere sempre!
        if ($req_update['primary'][$key_req] == "t" && $column->primary == "f") {
            foreach ($columns as $key_col => $col_val) {
                if ($col_val->primary == "t") {
                    // rimuovo l'auto increment
                    $new_cols = $columns;
                    $new_cols[$key_col]->primary = 'f';
                    // rimuovo il vecchio auto_increment
                    $alter2 = $this-> get_structure_field_to_alter($col_val->field_name, $col_val->field_name, $col_val->field_type, $col_val->field_length,  $col_val->attributes, $col_val->null, $col_val->default);
                    $result[] = 'ALTER TABLE `'.$this->table_name.'` CHANGE '.$alter2.";";
                    $result[] = 'ALTER TABLE `'.esc_sql($this->table_name).'` DROP PRIMARY KEY;';
                }
            }
        } 
        
        if ($change) {
            $result[] = 'ALTER TABLE `'.$this->table_name.'` CHANGE '.$alter.";";
        }
        return $result;
    }

    /**
     * Aggiungo una colonna
    * @param Array $column è la colonna originale
    * @param String $key_column 
    * @param Array $req_update è l'array che arriva direttamente dalla form
    * @param $string $key_req
    * @return Array un'array di query da eseguire	
    */
    public function get_sql_add_column($columns, $req_update, $key_req, $position = "") {
        $result = [];
        if (!isset($req_update['field_name'][$key_req])) {
            return [];
        } 
        $req_update['field_name'][$key_req]  = dbp_fn_structure::clean_column_name($req_update['field_name'][$key_req]);
        if ($req_update['field_name'][$key_req] == "") {
            return [];
        }
        if ($req_update['primary'][$key_req] == "t") {
            foreach ($columns as $key_col => $col_val) {
                if ($col_val->primary == "t") {
                    // rimuovo l'auto increment
                    $new_cols = $columns;
                    $new_cols[$key_col]->primary = 'f';
                    // rimuovo il vecchio auto_increment
                    $alter2 = $this->get_structure_field_to_alter($col_val->field_name, $col_val->field_name, $col_val->field_type, $col_val->field_length,  $col_val->attributes, $col_val->null, $col_val->default);
                    $result[] = 'ALTER TABLE `'.$this->table_name.'` CHANGE '.$alter2.";";
                    $result[] = 'ALTER TABLE `'.esc_sql($this->table_name).'` DROP PRIMARY KEY;';
                }
            }
        } 

        if ($position != "") {
            if ($position != "FIRST!") {
                $position = " AFTER `".$position."`"; 
            } else if ($position == "FIRST!") {
                $position = " FIRST";
            } else {
                $position = "";
            }
        } 
        $result[] = 'ALTER TABLE `'.$this->table_name.'` ADD COLUMN '.$this->get_structure_field_to_alter("", $req_update['field_name'][$key_req], $req_update['field_type'][$key_req], $req_update['field_length'][$key_req],  $req_update['attributes'][$key_req], $req_update['null'][$key_req], $req_update['default'][$key_req], $req_update['primary'][$key_req]).$position.";";

        return $result;

    }

    /**
    * Aggiungo una colonna ad una tabella e ne eseguo la query
    * @param Array $column è la colonna originale
    * @param String $key_column 
    * @param Array $req_update è l'array che arriva direttamente dalla form
    * @param $string $key_req
    * @return Array un'array di query da eseguire	
    */
    public function insert_new_column($column_name, $type, $length,  $null = false, $default = false, $attributes = '') {
        $result = [];
        if (!isset($column_name)) {
            $column_name = "fl_".uniqid();
        } 
        $column_name  = dbp_fn_structure::clean_column_name($column_name);
        $this->get_structure();
        $columns = [];
        foreach ($this->items as $cs) {
            if (is_object($cs)) {
                $columns[strtolower($cs->Field)] = '';
            }
        }
        // rinomino la colonna se già esiste
        $new_column_name = $column_name;
        $count_new_name = 1;
        while(array_key_exists(strtolower($new_column_name), $columns)) {
            $new_column_name = $column_name."_".$count_new_name;
            $count_new_name++;
        }


        $sql = 'ALTER TABLE `'.$this->table_name.'` ADD COLUMN '.$this->get_structure_field_to_alter("", $new_column_name, $type, $length,  $attributes, $null, $default).";";
        
        list($result, $this->last_error) = $this->exec_query($sql);
        if ($this->last_error == "") {
            return $new_column_name;
        } else {
            return false;
        }
    }
    /**
    * Rimuovo una colonna di una tabella e ne eseguo la query
    * @param string $column_name 

    * @return boolean
    */
    public function delete_column($column_name) {
        $this->get_structure();
        $columns = [];
        foreach ($this->items as $cs) {
            if (is_object($cs)) {
                $columns[$cs->Field] = '';
            }
        }
        if (isset($columns[$column_name])) {
            $sql = 'ALTER TABLE `'.$this->table_name.'` DROP COLUMN `'.$column_name.'`;';
            list($_, $this->last_error) = $this->exec_query($sql);
            return ($this->last_error == "");
        }
        return false;
    }
    /**
     * Costruisce la parte di stringa dell' ALert table dopo il change 
     */
    private function get_structure_field_to_alter($old_column_name, $new_column_name, $type, $length, $attributes, $null, $default = false, $primary = false, $old_primary = false) {
        $new_column_name = str_replace(['  ',' ','-'], "_", $new_column_name);
        $new_column_name = Dbp_fn::sanitize_key($new_column_name);

        if ($old_column_name != "") {
            $change_sql[] = '`' . esc_sql( $old_column_name ) . '` `' . esc_sql($new_column_name) . '`'; 
        } else {
            $change_sql[] = '`' . esc_sql($new_column_name) . '`';
        }
        $change_sql[] = $this->conv_type($type, $length);
        $change_sql[] = $attributes;
        if ($null == "t") {
            $change_sql[] = 'NULL';
        } else {
            $change_sql[] = 'NOT NULL';
        }
        if ($default !== false) {
            if ($primary != 't') {
                if ($default != "" && $default != "f") {
                    $change_sql[] = "DEFAULT '".esc_sql(wp_unslash($default))."'";
                } else if ($default == "f") {
                    if (in_array(strtoupper($type), ['INT','FLOAT','BIGINT','MEDIUMINT','TINYINT','DECIMAL','DOUBLE'])) {
                        $change_sql[] = "DEFAULT 0";
                    } else if (in_array(strtoupper($type), ['TEXT','VARCHAR','CHAR','BIGTEXT','MEDIUMTEXT'])) {
                        $change_sql[] = "DEFAULT ''";
                    }
                }
            }
        }
        if ($primary == 't') {
            if ($old_primary == "t") {
                $change_sql[] = "AUTO_INCREMENT";
            } else {
                $change_sql[] = "PRIMARY KEY AUTO_INCREMENT";
            }
        }
        return implode(" ", $change_sql);
    }

    /**
     * Esegue una query
     */
    public function exec_query($sql) {
        global $wpdb;
        $result = $wpdb->query($sql);
        if (is_wp_error($result) || !empty($wpdb->last_error)) {
            $result = 0;
        } 
        return [$result, $wpdb->last_error];
    }

    /**
     * Genera la query per la creazione di una tabella
     */
    public function get_create_sql() {
        global $wpdb;
        $this->last_error = "";
        if (count($this->columns) == 0) {
            $this->last_error = __("There are no columns to insert", 'db_press');
            return "";
        } 
        if (count( $this->key) > 0) {
            $this->columns[] = "KEY (".implode(", ", $this->key).")";
        } 
        if ( $this->primary_key != "") {
            $this->columns[] = "PRIMARY KEY ( $this->primary_key )";
        } else {
            $this->last_error = __("Primary key is missing", 'db_press');
        }
        if (!$this->check_table_name()) {
            $this->last_error = sprintf(__("Table %s already exists!", 'db_press'),  $this->get_table_name());
            return false;
        }
        $table_name = $this->get_table_name();
        $sql = 'CREATE TABLE IF NOT EXISTS `'.esc_sql($table_name).'` ('.implode(",\n", $this->columns).");";
        return $sql;
    }


    /**
     * Genera la query per la creazione di una tabella
     * @param Array $req_update l'array del form
     */
    public function sql_create_table_row($req_update) {
        global $wpdb;
        $this->last_error = "";
        $result = [];
        foreach ($req_update['field_name'] as $key_req => $_) {
            if ($req_update['field_name'][$key_req] != "" && $req_update['field_action'][$key_req] != "delete") {
                $result[] = $this->get_structure_field_to_alter("", $req_update['field_name'][$key_req], $req_update['field_type'][$key_req], $req_update['field_length'][$key_req],  $req_update['attributes'][$key_req], $req_update['null'][$key_req], $req_update['default'][$key_req], $req_update['primary'][$key_req]);
            }
        }
        $sql = 'CREATE TABLE `'.$this->table_name.'` ('."\n".implode(",\n", $result)."\n".')  ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET='.$this->get_db_charset();
        $ris = $wpdb->query($sql);
        if (!$ris) {
            $this->last_error = $wpdb->last_error;
        }
        return $ris;
    }

    /**
     * GET DEFAULT DB CHARSET
     */
    private function get_db_charset() {
        global $wpdb;
        $charset =$wpdb->get_var('SELECT @@character_set_database as cs');
        if ($charset == "") {
            $charset = 'utf8mb4';
        }
        return $charset;
    }

    /**
     * Preparo l'array con le info di una colonna
     */
    private function conv_type($type, $length = 0) {
        if ($type == "ENUM") {
            $length = stripcslashes($length);
        }
        if ($length != "" && !in_array($type, ['TIMESTAMP','DATETIME', 'TINYTEXT','TEXT','MEDIUMTEXT','LONGTEXT','DATE','TIME', 'YEAR','BOOLEAN','JSON']) ) {
            return strtoupper($type)."(".$length.")";
        } else {
            return strtoupper($type);
        }
    }

    /**
     * Torna il nome completo di prefisso di una tabella
     */
    public function get_table_name() {
        global $wpdb;
        if ($this->use_prefix == true) {
            $table_name = $wpdb->prefix . $this->table_name;
        } else {
             $table_name =  $this->table_name;
        }
        return $table_name;
    }

     /**
     * Verifico se il nome della tabella esiste già nel db oppure no
     * @return String Il nome della tabella senza il prefisso
     */
    public function check_table_name() {
        $temp_table_name = $this->get_table_name();
        $tables = dbp_fn::get_table_list();
        if (in_array($temp_table_name, $tables['tables']))  {
            return false;
        }
        return true;
    }

    /**
     * Trovo un nome della tabella univoco e lo imposto nella classe come default 
     * @return String Il nome della tabella senza il prefisso
     */
    public function change_unique_table_name($table_name = "", $check_with_prefix = true) {
        global $wpdb;
        if ($table_name == "") {
            $table_name = $this->table_name;
            $check_with_prefix = $this->use_prefix;
        }
        $table_name = explode(".", $table_name);
        if (count($table_name) > 1) {
		    array_pop($table_name);
        }
		$table_name = implode("_", $table_name);
		$table_name = dbp_fn_structure::clean_column_name($table_name);
        
        if ($check_with_prefix) {
            $table_name = $wpdb->prefix.str_replace($wpdb->prefix, "", $table_name);
        }
        $table_name = substr($table_name, 0, 64);

        $tables = dbp_fn::get_table_list();
        $temp_table_name = $table_name;
        $x = 1;
        while (in_array($temp_table_name, $tables['tables']))  {
            $temp_table_name = substr($table_name, 0, 60)."_".$x;
            $x++;
        }
        if ($check_with_prefix) {
            $this->table_name = str_replace($wpdb->prefix, "", $temp_table_name);
            $this->use_prefix = $check_with_prefix;
        } else {
            $this->table_name =  $temp_table_name;
            $this->use_prefix = false;
        }
        return $this->table_name;
    }


    /**
	 * Memorizza e Ritorna la struttura della tabella
     * 
     * @return Array [{schema:{}},{item},{item}] La prima riga da le informazioni sulla colonna!
 	 */
    public function get_structure($cache = true) {
        global $wpdb;
        if (count($this->items) > 0 && $cache) {
            return $this->items ;
        }
        if ($this->table_name == "") {
            $this->total_items = 0;
            $result =  [(object)["Field"=> "Field", "Type"=>"Type", "Null"=> "Null", "Key" => "Key", "Default" => "Default", "Extra" => "Extra"], (object)["Field" => "id", "Type" => "int(11) unsigned",  "Null" =>  "NO", "Key" => "PRI", "Default" => NULL,  "Extra" => "auto_increment"]];
            $this->items =$result;
            return $result;
        }
        $this->last_error = false;
        $this->effected_row = -1;
        $sql = 'SHOW COLUMNS FROM `'. esc_sql($this->table_name).'`';
        $result = $wpdb->get_results($sql);
        $this->error_primary = true;
        foreach ($result as $r) {
            if ($r->Key == "PRI" && $r->Extra == "auto_increment") {
                $this->error_primary = false;
            }
        }
        if (is_wp_error($result) || !empty($wpdb->last_error)) {
            $this->last_error = $wpdb->last_error;
        } else if ($result) {
            $this->effected_row = count($result);
            $first_array = reset($result);
            $new_first_line = [];
            foreach ($first_array as $key => $val ) {
                $new_first_line[$key] =  $key;
            }
            array_unshift($result , $new_first_line);
        }
        $this->total_items = count($result);
        $this->items = $result;
        //var_dump ($result);
        return ($result);
    }

    /**
     * Trova un indice specifico
     */
    public function get_index($id = "") {
        global $wpdb;
        $items = $wpdb->get_results('SHOW INDEXES IN `'. esc_sql($this->table_name).'`');
        $result = [];
        if ($id == '') {
            return (object)['name'=>'','choice'=>'index','columns'=>[]];
        }
        foreach ($items as $item) {
            if (!isset( $result[$item->Key_name])) {
                $choice = ($item->Non_unique) ? 'index' : 'unique';
                $result[$item->Key_name] = (object)['name'=>$item->Key_name,'type'=>$item->Index_type, 'choice'=>$choice,  'columns' => []];
            }
            $result[$item->Key_name]->columns[] = $item->Column_name;
        }
        if (isset($result[$id])) {
            return $result[$id];
        } else {
            return (object)['name'=>'','choice'=>'index','columns'=>[]];
        }
    }

    /**
     * Trova gli indici della tabella
     */
    public function get_indexes() {
        global $wpdb;
        if ($this->table_name == "") {
            return [];
        }
        $items = $wpdb->get_results('SHOW INDEXES IN `'. esc_sql($this->table_name).'`');
        $result = [];
        $table_options = dbp_fn::get_dbp_option_table($this->table_name);
      
        
        foreach ($items as $item) {
            if (!isset( $result[$item->Key_name])) {
               // $non_unique = ($item->Non_unique) ? 'NO' : 'YES';
               // $packed = ($item->Packed) ? 'YES' : 'NO';
                $actions = '<div class="row-actions">';
                if ($table_options['status'] == "DRAFT") {
                    $actions .= '<span class="edit"><a href="'.  admin_url("admin.php?page=database_press&section=table-structure&table=".$this->table_name."&action=edit-index&dbp_id=".$item->Key_name) . '">EDIT</a> | </span>';
                    $actions .= '<span class="edit"><a href="'. admin_url("admin.php?page=database_press&section=table-structure&table=".$this->table_name."&action=delete-index&dbp_id=".$item->Key_name) . '">DROP</a></span>';
                }
                $actions .= '</div>';
                $type = ($item->Non_unique) ? 'Optimize MySQL Search (Index)' : 'Unique values';
                $result[$item->Key_name] = (object)['name'=>"<b>".$item->Key_name."</b>".$actions,'type'=>$type,  'columns' => ''];
            }
           
            $result[$item->Key_name]->columns .= '<p>'.$item->Column_name.'</p>';
        }
        return ($result);
    }

    /**
     * Modifica o crea un indice. 
     * Ritorna Boolean, ma setta anche last_error se la query dà errore
     * @param String $name 
     * @param Array $columns
     * @param String $original_name
     * @param String $original_index INDEX|UNIQUE
     * @param String $type_of_index INDEX|UNIQUE
     * @return Boolean
     */
    public function alter_index( $columns, $original_name, $name = "", $original_index = "INDEX", $type_of_index = "INDEX") {
        global $wpdb;
        $column = [];
        $new_name_array = [];
        if (!is_array($columns) || (is_countable($columns) && count($columns) == 0)) {
            return false;
        }
        $columns = array_unique($columns);
        foreach ($columns as $col) {
            $column[] = '`'.esc_sql($col).'`';
            $new_name_array[] = dbp_fn::clean_string($col);
        }
        if ($name == "") {
            $name = implode("_", $new_name_array);
        }
        $indexes = self::get_indexes();
        $curr_name = $name;
        $count_name = 1;
        while(array_key_exists($curr_name, $indexes) && $original_name != $curr_name && $count_name < 100) {
            $count_name++; 
            $curr_name = $name.str_pad($count_name,2,"0",STR_PAD_LEFT);
        }
        $name = $curr_name;
        $type_of_index = strtoupper($type_of_index);
        $original_index = strtoupper($original_index);
        if ($type_of_index != "INDEX") {
            $type_of_index = 'UNIQUE';
        }
        if ($original_index != "INDEX") {
            $original_index = 'UNIQUE';
        }
        if ($original_name != "") {
           
            if (!$wpdb->query( 'ALTER TABLE `' . esc_sql($this->table_name) . '` DROP '.$original_index.' `'.esc_sql($original_name).'`;')) {
                $this->last_error = $wpdb->last_error;
            }
        }
        if (!$wpdb->query( 'ALTER TABLE `' . esc_sql($this->table_name) . '` ADD '.$type_of_index.' `'.esc_sql($name).'` ('.implode(', ', $column).') USING BTREE;' )) {
            $this->last_error = $wpdb->last_error;
            return false;
        } else {
            return true;
        }
        
    }

    /**
     * Rimuove un indice
     */
    public function delete_index( $name) {
        global $wpdb;
        return $wpdb->query( 'ALTER TABLE `' . esc_sql($this->table_name) . '` DROP INDEX `'.esc_sql($name).'`;');
    }

}