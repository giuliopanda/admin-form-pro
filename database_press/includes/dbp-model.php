<?php
/**
 * Il model per la gestione delle query
 * La logica è che ogni query ha un suo model che tiene tutte le informazioni della query eseguita: 
 * limit, order, il risultato della query eseguita, il numero totale di elementi senza i limit ecc.
 * 
 * @since      0.1.0
 *
 * @package  DbPress
 */
namespace DbPress;

class  Dbp_model {
    /**
     * @var String $table_name La tabella principale della query
     */
    public $table_name = "";
    /**
     * @var String $current_query La query che si stà costruendo
     */
    protected $current_query = "";
    /**
     * @var String $default_query La query passata al model da cui partire prima di essere modificata
     */
    protected $default_query = "";
     /**
     * @var String $last_error L'ultimo errore generato dal model (di solito l'errore mysql)
     */
    public $last_error = false;
    /**
     * @var Int $limit_start 
     */
    public  $limit_start = 0;
    /**
     * @var Int $limit 
     */
    public  $limit = 100;
     /**
     * @var Array|Boolean $sort [field, order] 
     */
    public  $sort = true;
     /**
     * @var Array|Boolean $filter [[column, op, value]] 
     */
    public  $filter = true;
     /**
     * @var Int $total_items Il numero totale di elementi della query
     */
    public  $total_items = 0;
    /**
     * @var Array $items Il risultato della query La prima riga è composta dallo schema del risultato
     */
    public  $items = [];
     /**
     * @var Float $time_of_query Il tempo di esecuzione della query
     */
    public  $time_of_query = 0;
    /**
     * @var dbp_util_marks_parentheses $ulitities_marks La classe che converte " e parentesi della query per poi renderla più semplice da elaborare
     */
    protected  $ulitities_marks = false;
      /**
     * @var Int $effected_row Il numero di righe interessate dalla query
     */
    public $effected_row = -1;
    /**
     * @var Array $tables_primaries L'elenco delle tabelle.primary_key interessate nella query. 
     * Viene compilato solo da dbp_fn::items_add_action
     */
    public $tables_primaries = [];

    /**
     * @var Array $current_schema lo schema della query
     */
    public $current_schema = false;
    
    /**
     * @var Array $primary_added chiavi primarie aggiunte alla tabella [['table_alias','field'], ...];
     */
    public $primary_added = [];

    public $all_primaries = [];
    public $query_before_primary_added = '';

    /**
     * @var Array $get_primaries Cache delle chiavi primarie
     */
    private $get_primaries = [];
    private $get_primaries_alias = [];


    /**
     * Se table_name è vuoto è d'obbligo chiamare prepare()
     * @param String $table_name
     */
    public function __construct($table_name = "") {
		$this->table_name = $table_name;
        $this->ulitities_marks = new Dbp_util_marks_parentheses();
        $this->prepare();
	}

    /**
     * Prepara la query solo per le select
     * @param String $query
     */
    public function prepare($query = "", $strip_slashes = true) {
        if(trim($query) != "") {
            $query = dbp_fn::all_trim($query);
            while  (substr($query,-1, 1) == ";") {
                $query = substr($query, 0, -1);
            }
            if ( $strip_slashes ) {

                $this->current_query = trim(wp_unslash($query));
            } else {
                $this->current_query = trim($query);
            }
        } else if ($this->table_name != "") {
            $this->current_query = 'SELECT * FROM  `'. Dbp_fn::sanitize_key($this->table_name).'`';
        } else {
            return false;
        }
        // default query è la query di partenza non modificata dai filtri
        $this->current_query = $this->ulitities_marks->replace($this->current_query);
        $this->default_query = $this->current_query;
        $this->last_error = false;
    }

    /**
     * Trova di una query i parametri AS all'interno del select e restituisce un array con gli As come indice  
     * e come è stata costruita la colonna come valori. Non esegue Query
     * @param Boolean $only_orgname Se mostrare solo il nome della colonna o anche il nome della tabella se inserito
     * @return Array L'array con le colonne della query che non fanno parte dei campi del db tipo CONCAT()
     */
    public function get_original_column_name($only_orgname = false) {
        $new_fields = [];
        if ($this->sql_type() == "select") {
            $current_query = $this->current_query;
            $from = stripos($current_query, 'from');
            if ($from > 0) {
                $select = substr($current_query, 0, $from);
                $select = substr($select, stripos($select, 'select ')+7);
                $select_tmp = explode(", ", $select);
                foreach ($select_tmp as $field) {
                    $temp_field = explode(" as ", str_replace([" AS "," As "," aS "],' as ', trim($field)));
                    if (count($temp_field) == 1) {
                        $temp_field = explode(" ", str_replace(["   ","  "],' ', trim($field)));
                    }
                    $temp_field = array_filter($temp_field); 
                    if (count($temp_field) == 2) {
                        $field1 = trim(str_replace("`","", $this->ulitities_marks->restore(trim($temp_field[1]))));
                        if ($only_orgname) {
                            $tm1 = explode(".", $temp_field[0]);
                            $new_fields[$field1] = str_replace('`','',$this->ulitities_marks->restore(array_pop($tm1)));
                        } else {
                            $new_fields[$field1] = $this->ulitities_marks->restore($temp_field[0]);
                        }
                    } else {
                        $new_fields[$this->ulitities_marks->restore($field)] = $this->ulitities_marks->restore($field);
                    }
                }
            }
        }
        return $new_fields;
    }

    /**
     * Ritorna la query che si sta per eseguire
     * Se è un multiqueries ritorna l'elenco delle query
     */
    public function get_current_query() {
       // print " this->sql_type(): ".$this->sql_type() ."<br>";
        if($this->sql_type() == "multiqueries") {
           $queries = explode(";",$this->default_query);
           foreach ($queries as &$q) {
                $q = $this->ulitities_marks->restore($q);
                $q = dbp_fn::all_trim($q);
           }
           $queries = array_filter($queries);
           return $queries;
        } else {
            return $this->ulitities_marks->restore($this->current_query);
        }
    }

    /**
     * Ritorna la query impostata in originale
     */
    public function get_default_query() {
        return $this->ulitities_marks->restore($this->default_query);
    }

    /**
     * Aggiunge il parametro limit alla query
     * @param Numeric $limit_start
     * @param Numeric $limit
     */
    public function list_add_limit($limit_start, $limit) {
        $this->limit = absint($limit);
        if($this->sql_type() == "select") {
            $this->current_query = dbp_fn::all_trim($this->current_query);
            $limit_start =  absint($limit_start);
            if (substr($this->current_query, -1) == ";") {
                $this->current_query = substr($this->current_query, 0, -1);
            }
            $limit_position = strripos($this->current_query, 'limit'); // trovo l'ultima occorrenza
            if ($limit > 0) {
                $this->limit_start =  $limit_start;
                if ($limit_position > 0) {
                    $this->current_query = substr($this->current_query, 0, $limit_position)." LIMIT ".$limit_start.", ".$limit;
                } else {
                    $this->current_query = $this->current_query." LIMIT ".$limit_start.", ".$limit;
                }
            }
        }
    }

    /**
     * Estrae la parte di query del where
     * @param Boolean $split se dividerlo in gruppi oppure no. 
     * SE diviso in gruppi ritorna  [[single condition], ...]
     * @return String|Array 
     */
    public function get_partial_query_where($split = false) {
        $first_occurrance = stripos($this->current_query, 'where');
        if ($first_occurrance === false) return "";
        $where_split = [];
        $first_occurrance += 5;
       
        list($found_string, $pos) = $this->strpos_array($this->current_query, ['order ','group ','limit ','having '],  $first_occurrance);
        if ($found_string != "") {
           $from = substr($this->current_query,  $first_occurrance, $pos -  $first_occurrance);
        } else {
            $from = substr($this->current_query,  $first_occurrance);
        }
        if ($split) {
            $neddles = [' AND ',' OR ', 'AND(',' OR(' , ')AND ',')OR '];
            $list_of_parts = dbp_fn::multi_explode($neddles, $from);
            foreach ($list_of_parts as $single_from) {
                list($pos, $join) = dbp_fn::find_first($neddles, $single_from);
                $single = substr($single_from, $pos + strlen($join));
                $where_split[] = $this->ulitities_marks->restore($single);
            }
            return $where_split;
        }

        return $this->ulitities_marks->restore($from);
    }

    /**
     * Rimuove le clausule del where dove compare la colonna indicata
     * @param String $column 
     * @return Void
     */
    public function removes_column_from_where_sql($column) {
        $first_occurrance = stripos($this->current_query, 'where');
        $column = trim(str_replace('`', '', trim($column)));
        if ($first_occurrance === false || $column == "") return "";
        $first_occurrance += 5;
        list($found_string, $pos) = $this->strpos_array($this->current_query, ['order ','group ','limit ','having '],  $first_occurrance);
        $rest = '';
        $where_string = '';
        if ($found_string != "") {
            $where_string = substr($this->current_query,  $first_occurrance, $pos -  $first_occurrance);
            $rest = substr($this->current_query,  $pos );
        } else {
            $where_string = substr($this->current_query,  $first_occurrance);
        }

        if ($where_string  !== '') {
            $new_query = substr( $this->current_query, 0, $first_occurrance - 5);
            $where_array = dbp_fn::multi_explode([' AND ',' OR ', 'AND(',' OR(' , ')AND ',')OR '],  $this->ulitities_marks->restore($where_string), true);
            $new_where = [];
            $skip = false;
            $brackets_open = $brackets_close = 0;
            foreach ($where_array as $w) {
                if (str_ireplace([' ','`'],"", $w) == "") continue;
                if ($skip) {
                    $skip = false;
                    if (trim(str_ireplace(['and','or','`'],"", $w)) == "") continue;
                    $brackets_open = substr_count($w, "(");
                    $brackets_close = substr_count($w, ")");
                    $new_where[] = str_ireplace(['and','or'], "", $w);  
                    continue;
                }
                if (stripos(str_replace('`', '',$w), $column." ") === false && stripos(str_replace('`', '',$w), $column.")") === false) {
                    $brackets_open += substr_count($w, "(");
                    $brackets_close += substr_count($w, ")");
                    $new_where[] = $w;
                } else {
                    $skip = true;
                }
            }
            
            if ($skip && count($new_where)) {
                $other = array_pop($new_where);
                $brackets_open -= substr_count($other, "(");
                $brackets_close -= substr_count($other, ")");
            }
            while  ($brackets_open > $brackets_close) {
                $new_where[] = ")";
                $brackets_close++;
            }
            while  ($brackets_open < $brackets_close) {
                array_unshift($new_where, "(");
                $brackets_open++;
            }
            if (count($new_where) > 0) {
                $new_query .= " WHERE ".trim( implode(" ",$new_where));
            }
            $new_query .= " ".$rest;
            $this->prepare($new_query);
        } 
    }

    /**
     * Estrae la parte di query del from
     * @param Boolean $split (Default:false) se dividerlo in gruppi oppure no. SE diviso in gruppi ritorna
     * ```json
     * [["table", "as", "where", "la parte di stringa elaborata"], ["..."]]
     * ```
     * Altrimenti la porzione di stringa del from
     * @return String|Array 
     */
    public function get_partial_query_from($split = false) {
        $first_occurrance = stripos($this->current_query, 'from') + 4;
        if ($first_occurrance === false) return "";
       
        list($found_string, $pos) = $this->strpos_array($this->current_query, ['where ','order ','group ','limit ','having '],  $first_occurrance);
        if ($found_string != "") {
           $from = substr($this->current_query,  $first_occurrance, $pos -  $first_occurrance);
        } else {
            $from = substr($this->current_query,  $first_occurrance);
        }
        if ($split) {
            $neddles = ['left join','right join','inner join', 'full join', 'outer join','join',','];
            $list_of_parts = dbp_fn::multi_explode($neddles, $from);
            $from_split = [];
            foreach ($list_of_parts as $single_from) {
               // print "<p>".$single_from."</p>";
                list($pos, $join) = dbp_fn::find_first($neddles, $single_from);
                $single = substr($single_from, $pos + strlen($join));
                $single = str_ireplace(' on ',' on ', $single);
                $from_ris_2 = explode(' on ', $single);
                if (count($from_ris_2) == 2) {
                    $left = str_ireplace([' as ', '  '], ' ', array_shift($from_ris_2));
                    $form_2 = dbp_fn::all_trim($this->ulitities_marks->restore(array_shift($from_ris_2)));
                } else {
                    $left = str_ireplace([' as ', '  '], ' ', dbp_fn::all_trim($single));
                    $form_2 = '';
                }
                $left_2 = explode(" ", $left);
                $left_2 = array_filter($left_2);
                
                if (count ($left_2) == 1) {
                    $table = dbp_fn::all_trim(str_replace("`","", $this->ulitities_marks->restore(reset($left_2))));
                    $table_alias = trim(str_replace("`","", $this->ulitities_marks->restore(reset($left_2))));
                    $from_split[] = [$table, $table_alias, '',  dbp_fn::all_trim($this->ulitities_marks->restore($single_from))] ;
                }
                if (count ($left_2) == 2) {
                    $table = dbp_fn::all_trim(str_replace("`","", $this->ulitities_marks->restore(array_shift($left_2))));
                    $table_alias = trim(str_replace("`","", $this->ulitities_marks->restore(array_shift($left_2))));
                    $from_split[] = [$table, $table_alias, $form_2,   dbp_fn::all_trim($this->ulitities_marks->restore($single_from))];
                }
               
            }
            return $from_split;

        }
        return $this->ulitities_marks->restore($from);
    }

    /**
     * Estrae la parte di query del Select
     * @param Boolean $split se dividerlo in gruppi oppure no. SE diviso in gruppi ritorna 
     * [[table, field, as, all_part], ...]
     * @return String|Array 
     */
    public function get_partial_query_select($split = false) {
        $new_fields = [];
        if ($this->sql_type() == "select") {
            $current_query = $this->current_query;
            $from = stripos($current_query, 'from');
            if ($from > 0) {
                $select = substr($current_query, 0, $from);
                $select = substr($select, stripos($select, 'select ') + 7);
                if (!$split) {
                    return $this->ulitities_marks->restore($select);
                }
                $select_tmp = explode(", ", $select);
                foreach ($select_tmp as $field) {
                    $temp_field = explode(" as ", str_replace([" AS "," As "," aS "],' as ', trim($field)));
                    if (count($temp_field) == 1) {
                        $temp_field = explode(" ", str_replace(["   ","  "],' ', trim($field)));
                    }
                    $temp_field = array_filter($temp_field); 
                    if (count($temp_field) == 2) {
                        $field1 = trim(str_replace("`","", $this->ulitities_marks->restore(trim($temp_field[1]))));
                       
                        $tm1 = explode(".", $temp_field[0]);
                        if (count($tm1) == 2) {
                            $new_fields[] = [str_replace('`','',$this->ulitities_marks->restore($tm1[0])), str_replace('`','',$this->ulitities_marks->restore($tm1[1])), $field1, $this->ulitities_marks->restore($field)];
                        } else {
                            $new_fields[] = ['', str_replace('`', '', $this->ulitities_marks->restore($temp_field[0])), $field1, $this->ulitities_marks->restore($field)];
                        }
                    } else {
                        $new_fields[] = ['--',  str_replace('`', '', $this->ulitities_marks->restore($field)), str_replace('`', '', $this->ulitities_marks->restore($field)), $this->ulitities_marks->restore($field)];
                    }
                }
            }
        }
        return $new_fields;
    }

     /**
     * Estrae la parte di query del Select
     * @param Boolean $split se dividerlo in gruppi oppure no. SE diviso in gruppi ritorna 
     * [[table, field, as, all_part], ...]
     * @return Array  [limit_start, limit_end] [0,0] se il limite non è impostato
     */
    public function get_partial_query_limit() {
        $limit_start = $limit_end = 0;
        if ($this->sql_type() == "select") {
            $current_query = $this->current_query;
            $limit = stripos($current_query, 'limit');
            if ($limit > 0) {
                $limit_string = str_replace(";","",substr($current_query, $limit + 5));
                $limit_temp = explode (",", $limit_string);
                if (count($limit_temp) == 1) {
                    $limit_start = 0;
                    $limit_end = intval(dbp_fn::all_trim(array_shift($limit_temp)));
                }
                if (count($limit_temp) == 2) {
                    $limit_start = intval(dbp_fn::all_trim(array_shift($limit_temp)));
                    $limit_end = intval(dbp_fn::all_trim(array_shift($limit_temp)));
                }
            } 
        }
        return [$limit_start, $limit_end];
    }

    /**
     * Aggiunge un nuovo from se c'è da passare la virgola, bisogna metterla a inizio stringa
     * @param String $from
     * @return Void;
     */
    public function list_add_from($from) {
        $first_occurrance = stripos($this->current_query, 'from');
        if ($first_occurrance === false) return "";
        list($found_string, $pos) = $this->strpos_array($this->current_query, ['where ','order ','group ','limit ','having '],  $first_occurrance);
        if ($found_string != "") {
            $this->current_query = substr($this->current_query, 0, $pos) . " " . $from . " " . substr($this->current_query, $pos);
        } else {
            $this->current_query = $this->current_query. " " . $from;
        }
    }

      /**
     * Fa il replace della sezione From
     * @param String $from
     * @return Void;
     */
    public function list_change_from($from) {
        $first_occurrance = stripos($this->current_query, 'from');
        if ($first_occurrance === false) return "";
        list($found_string, $pos) = $this->strpos_array($this->current_query, ['where ','order ','group ','limit ','having '],  $first_occurrance);
        if ($found_string != "") {
            $this->current_query = substr($this->current_query, 0, $first_occurrance) . " FROM " . $from . " " . substr($this->current_query, $pos);
        } else {
            $this->current_query = substr($this->current_query, 0, $first_occurrance) . " FROM " . $from;
        }
    }

    /**
     * Cambia il select della query. Per riavere la nuova query poi devi usare get_current_query
     * @todo bisognerebbe convertire la nuova stringa con ulitities_marks
     * @param String $new_select
     * @return Void 
     */
    public function list_change_select($new_select) {
        if($this->sql_type() == "select") {
            $this->current_schema = false;
            $from = stripos($this->current_query, 'from');
            if ($from > 0) {
                $this->current_query = "SELECT ".$this->ulitities_marks->replace($new_select)." ".substr($this->current_query, $from);
            } 
        }
    }

    /**
     * Aggiunte delle colonne al select
     * @param String $add_select
     * @return Void
     * 
     */
    public function list_add_select($add_select) {
        if($this->sql_type() != "select") {
            return false;
        }
        $current_query = $this->current_query;
        $from = stripos($current_query, 'from');
        $select_string =  stripos($current_query, 'select') + 6;
        $select = dbp_fn::all_trim(substr($current_query, $select_string , $from - $select_string));
        $sql_schema = $this->get_schema();
        $table_name_unique = [];
        // se è select * e basta, elenco le vacchie tabelle e ci aggiungo l'asterisco
        if ($select == "*") {
            // trovo tutte le tabelle e cambio il select
            $tables = [];
            foreach ($sql_schema as $field) {
                if (isset($field->orgtable) && $field->orgtable != "" && isset($field->table) ) {
                    $table_name = ($field->table != "") ? $field->table : $field->orgtable;
                    if (!in_array($table_name, $table_name_unique)) {
                        $table_name_unique[] = $table_name;
                        $table_name_unique[] = '`'.$table_name."`.*";
                        $table_name_unique[] = $table_name.".*";
                        $tables[$field->table] = '`'.$table_name."`.*";
                    }
                }
            }
            $select = implode(", ", array_unique($tables));
        }
        if ($add_select != "") {
            if (!in_array($add_select, $table_name_unique)) {
                $select = $select.", ".$add_select;
            }
            $this->list_change_select($this->ulitities_marks->restore($select));
        }
    }

    /**
     * Aggiunge l'order alla query
     * @param String $field Il campo da ordinare
     * @param String $order ASC|DESC
     */
    public function list_add_order($field, $order) {
        if($this->sql_type() == "select") {
            $this->current_query = dbp_fn::all_trim($this->current_query);
            if (substr($this->current_query, -1) == ";") {
                $this->current_query = substr($this->current_query, 0, -1);
            }
            $limit_position = strripos($this->current_query, ' LIMIT '); // trovo l'ultima occorrenza
            $order_position = strripos($this->current_query, ' ORDER '); // trovo l'ultima occorrenza
            if ($field == "") {
                return false;
            }
            if (strtoupper($order) != "DESC") { 
                $order = "ASC";
            }
            if ($limit_position > 0) {
                $sql = substr($this->current_query, 0, $limit_position);
                $limit_sql = substr($this->current_query, $limit_position);
            } else {
                $sql = $this->current_query;
                $limit_sql = '';
            } 
            if ($order_position > 0) {
               $sql = substr($sql, 0, $order_position);
            } 
            $this->sort = ['field'=>$field, 'order'=>$order];
            $sql = dbp_fn::all_trim($sql);
            $this->current_query = $sql." ORDER BY ".Dbp_fn::sanitize_key($field)." ".$order;
            if ($limit_sql != "") {
               $this->current_query = $this->current_query . " " . $limit_sql;
            }
        }
    }

    /**
     * Aggiunge i where
     * @param Array $filter [[op:'', column:'',value:'' ], ... ]
     */
    public function list_add_where($filter, $conjunction = "AND") {
        if($this->sql_type() == "select" && is_array($filter) && count($filter) > 0) {
            //$string_start = strlen("where");
            // trovo la porzione di query in cui con la clausola where
            $where = $this->substr_with_strings($this->current_query, "where ", ['having ','order ','group ','limit ','window ','for ']);
            // trovo la posizione degli operatori finali della query
            list($last_operator_of_query, $pos) = $this->strpos_array($this->current_query, ['having ','order ','group ','limit ','window ','for ']);  
            $where_filter = $this->convert_filter_to_string($filter, $conjunction);
            if ($where_filter != "") {
                if ($conjunction == "AND") {
                    $this->filter = $filter;
                }
            } else {
                return "";
            }
            $where_filter = $this->ulitities_marks->replace($where_filter);
            if ($where == "") {
                // se nella query non c'era la clausola where
                if ($last_operator_of_query == "") {
                    $this->current_query =  trim($this->current_query)." WHERE ".$where_filter;
                } else {
                    $this->current_query =  trim(substr($this->current_query, 0, $pos))." WHERE ".$where_filter." ".trim(substr($this->current_query, $pos));
                }
            } else {
                // se c'è la clausola where
                $first_occurrance = stripos($this->current_query, "where");
                if ($last_operator_of_query == "") {
                    $this->current_query =  trim(substr($this->current_query, 0, $first_occurrance))." WHERE (".trim($where).") AND ".$where_filter; 
                } else {
                    $this->current_query =  trim(substr($this->current_query, 0, $first_occurrance))." WHERE (".trim($where).") AND ".$where_filter." ".trim(substr($this->current_query, $pos)); 
                }
            }
        }
    }
   
	/**
	 * Memorizza e Ritorna l'elenco dei dati. La prima riga è la struttura della tabella
     * @param Boolean $check_same_column
     * @param Boolean $force_select previene sql injection
     * @return Array|false [{schema:{}},{item},{item}] La prima riga da le informazioni sulla colonna!
 	 */
    public function get_list($check_same_column = true, $force_select = true) {
        global $wpdb;
        $start = microtime(true);
        $this->last_error = false;
        $this->effected_row = 0;
        $sql = $this->ulitities_marks->restore($this->current_query);
        if ($force_select == true && $this->sql_type() != "select" ) {
            $this->last_error = 'No select query';
            $this->items = [];
            return false;
        }
        if ($this->sql_type() == "select" || $this->sql_type() == "show") {
            if ($this->sql_type() == "show") {
                $this->sort = false;
                $this->filter = false;
            }
            //print "<p>SQL ".$sql."</p>";
            $result = $wpdb->get_results($sql);
            if (is_wp_error($result) || !empty($wpdb->last_error)) {
                if (stripos($wpdb->last_error, 'is marked as crashed and should be repaired')) {
                    $this->last_error = $wpdb->last_error." Try running the query 'REPAIR TABLE `<table name>`;' to correct the error'";
                } else {
                    $this->last_error = $wpdb->last_error;
                }
            } else {
                $this->effected_row = count($result);
                // $constants = get_defined_constants(true);
                $this->current_schema = $list_last_get_col_info = $wpdb->__get('col_info'); 
                
                // verifico se ci sono colonne con lo stesso nome Non è permesso, fa casini!
                if ($check_same_column) {
                    if (!$this->get_list_check_same_column($list_last_get_col_info)) {
                        $this->time_of_query = round(microtime(true) - $start, 4);
                        return [];
                    }
                }
                $as_fields = $this->get_original_column_name(true);
                $first_array = reset($result);
                $new_first_line = [];
                if (is_array($first_array)) {
                    foreach ($first_array as $key => $val ) {
                        $schema =  array_shift($list_last_get_col_info);
                        // orgname con l'AS nel select sbaglia!
                        if ($schema->orgname == $schema->name && is_array($as_fields) && array_key_exists($schema->name, $as_fields)) {
                            $schema->orgname = $as_fields[$schema->name];
                        }
                        $new_first_line[$key] = ['schema' => clone $schema];
                    }
                } else {
                    foreach ($list_last_get_col_info as $key => $schema ) {
                       if (isset($schema->name)) {
                            // orgname con l'AS nel select sbaglia!
                            if ($schema->orgname == $schema->name && is_array($as_fields) && array_key_exists($schema->name, $as_fields)) {
                                $schema->orgname = $as_fields[$schema->name];
                            }
                            $new_first_line[$schema->name] = ['schema' => $schema];
                       }
                    }
                }
                array_unshift($result , $new_first_line);
            }

        } else if ($this->sql_type() != "multiqueries") {
            $this->sort = false;
            $this->filter = false;
            // print "<p>SQL ".$sql."</p>";
            // query di modifica!
            $close_is_ok =  $this->check_is_ok_for_close($sql);
            $draft_is_ok = $this->check_is_ok_for_draft($sql) ;
            if ($draft_is_ok && $close_is_ok) {
                $result = $wpdb->query($sql);
                if (is_wp_error($result) || !empty($wpdb->last_error)) {
                    $this->last_error = $wpdb->last_error;
                   // $result =  [['effected_row' =>['schema'=>(object)['name'=>'effected_row','orgname'=>'','orgtable'=>'','table'=>'','type'=>'','db'=>'']]], ['effected_row'=>0]];
                     $this->effected_row = 0;
                } else if ($result) {
                    $this->items =  [['effected_row' =>['schema'=>(object)['name'=>'effected_row','orgname'=>'','orgtable'=>'','table'=>'','type'=>'MYSQLI_TYPE_INT24','db'=>'']]], ['effected_row'=>(int)$result]];
                    $this->effected_row =(int) $result;
                    $result = $this->items;
                }
            } else {
                if (!$draft_is_ok) {
                    $this->last_error = __('This type of query is not allowed because the table is in PUBLISH state', 'db_press');
                } else {
                    $this->last_error = __('This type of query is not allowed because the table is in CLOSE state', 'db_press');
                }
                $this->effected_row = 0;
                $result = [['effected_row' =>['schema'=>(object)['name'=>'effected_row','orgname'=>'','orgtable'=>'','table'=>'','type'=>'','db'=>'']]], ['effected_row'=>0]];
            }
        } else { 
            return false;
        }
       
        $this->items = $result;
        $this->time_of_query = round(microtime(true) - $start, 4);
        return ($result);
    }

    /**
	 * Memorizza e Ritorna la struttura della tabella
     * 
     * @return Array [{schema:{}},{item},{item}] La prima riga da le informazioni sulla colonna!
     * @deprecated Passato a includes\dbp-model-structure.php
 	 */
      public function get_structure() {
        global $wpdb;
        $start = microtime(true);
        $this->last_error = false;
        $this->effected_row = -1;
        $sql = 'SHOW COLUMNS FROM `'. Dbp_fn::sanitize_key($this->table_name).'`';
       
        $result = $wpdb->get_results($sql);
        
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
        $this->total_items = count ($result);
        $this->items = $result;
        $this->time_of_query = round(microtime(true) - $start, 4);
        return ($result);
    }

    /**
     * Conta il numero di risultati della query attiva e lo mette dentro $this->total_items
     * @param Booleam $remove_limit
     * @return Integer -1 se non ha potuto contare il numero di righe
     */
    public function get_count($remove_limit = true) {
        global $wpdb;
        $sql = $this->current_query;
        if($this->sql_type() == "select") {
            $from = stripos($sql, 'from');
            $select = substr($sql, 0, $from);
            $select = substr($select, stripos($select, 'select') + 6);
            if ($from > 0) {
                if (stripos($select,'distinct') !== false) {
                    $new_sql = "SELECT count($select) ".substr($sql, $from);
                } else {
                    $new_sql = "SELECT count(*) ".substr($sql, $from);
                }
                if ($remove_limit) {
                    $limit = strripos($new_sql, ' LIMIT ');
                    if ($limit > 0) {
                        $new_sql = substr($new_sql, 0, $limit);
                    }
                }
                $order = strripos($new_sql, ' ORDER ');
                if ($order > 0) {
                    $new_sql = substr($new_sql, 0, $order);
                }
                $new_sql = $this->ulitities_marks->restore($new_sql);
               // print ("<p>count: ".$new_sql."</p>");
                $result = $wpdb->get_var($new_sql);
                if (is_wp_error($result) || !empty($wpdb->last_error)) {
                    $this->last_error = $wpdb->last_error;
                    $this->total_items = -1;
                    return -1;
                }  
                $this->total_items = $result;
                return $result;
               
            } else {
                $this->last_error = "Query parsing";
                $this->total_items = -1;
                return -1;
            }
        } else {
            $this->total_items = -1;
            return -1;
        }
    }

    /**
     * Rimuove eventuali limit di una query e ritorna il limite
     * @return Number 
     */
    public function remove_limit() {
        $limit = strripos($this->current_query, ' LIMIT ');
        $limit_value = 0;
        if ($limit > 0) {
            $new_limit_temp =  substr($this->current_query, $limit);
            $new_limit_temp = explode(",", $new_limit_temp);
            $limit_value = (int)trim(array_pop($new_limit_temp));
            $this->current_query = substr($this->current_query, 0, $limit);
        }
        if ($limit_value > 0 && $limit_value < 1000) {
            return $limit_value;
        } else {
            return 0;
        }
    }

    /**
     * rimuove l'order alla query
     */
    public function remove_order() {
        if($this->sql_type() == "select") {
            $this->current_query = dbp_fn::all_trim($this->current_query);
            if (substr($this->current_query, -1) == ";") {
                $this->current_query = substr($this->current_query, 0, -1);
            }
            $limit_position = strripos($this->current_query, ' LIMIT '); // trovo l'ultima occorrenza
            $order_position = strripos($this->current_query, ' ORDER '); // trovo l'ultima occorrenza
           
            if ($limit_position > 0) {
                $sql = substr($this->current_query, 0, $limit_position);
                $limit_sql = substr($this->current_query, $limit_position);
            } else {
                $sql = $this->current_query;
                $limit_sql = '';
            } 
            if ($order_position > 0) {
               $sql = substr($sql, 0, $order_position);
            } 
            $this->current_query =  dbp_fn::all_trim($sql);
            if ($limit_sql != "") {
               $this->current_query = $this->current_query . " " . $limit_sql;
            }
        }
    }


    /**
     * Ritorna lo schema di una query 
     * Può essere che una query ad un id sia più veloce che solo con limit 0,1 ...
     * @todo Aggiungere la cache
     */
    public function get_schema() {
        global $wpdb;
        $sql = $this->current_query;
        if ($this->current_schema != false) {
            return $this->current_schema;
        }
        if($this->sql_type() == "select") {
            $from = stripos($sql, 'from');
            if ($from > 0) {
                $new_sql = $sql;
                $new_sql = str_replace(["\t","\r","\n"]," ", $sql);
                $limit = strripos($new_sql, ' LIMIT ');
                if ($limit > 0) {
                    $new_sql = substr($new_sql, 0, $limit);
                }
                $where = strripos($new_sql, ' WHERE ');
                if ($where > 0) {
                    $new_sql = substr($new_sql, 0, $where);
                }
                $order = strripos($new_sql, ' ORDER BY ');
                if ($order > 0) {
                    $new_sql = substr($new_sql, 0, $order);
                }
                $group = strripos($new_sql, ' GROUP BY ');
                if ($group > 0) {
                    $new_sql = substr($new_sql, 0, $group);
                }
                $having = strripos($new_sql, ' HAVING ');
                if ($having > 0) {
                    $new_sql = substr($new_sql, 0, $having);
                }
 
                // è più veloce rispetto ad inserire limit 1 !!!!
                $new_sql = $new_sql." WHERE 1 = 2";
                $new_sql = $this->ulitities_marks->restore($new_sql);
                //print ("<p>new_sql: ".$new_sql."</p>");
                $result = $wpdb->get_var($new_sql);
                if (is_wp_error($result) || !empty($wpdb->last_error)) {
                    $this->last_error = str_replace(" WHERE 1 = 2", "",$wpdb->last_error);
                    return false;
                } else {
                    $this->current_schema = $wpdb->__get('col_info'); 
                    return $this->current_schema;
                }
                
            }
        }
       // $this->last_error = 'The sql is not a select query';
        return false;
    }

    /**
     * dopo che è stata già eseguita la query verifica lo stato delle tabelle interessate 
     * @param boolean $single_return
     * @return string|array 
     * con $single_return true torna un DRAFT|PUBLISH|CLOSE|MIXED Se non tutte hanno lo stesso livello di status 
     */
    public function table_status($single_return = true) {
        $table_status = '';
        $tables = [];
        if (is_array($this->items) && count($this->items) > 0) {
            $table_header = reset($this->items);
            foreach ($table_header as $k=>$th) {
                if (isset($th->table) && $th->original_table != "" && !isset($tables[$th->original_table])) {
                    $tables[$th->original_table] = dbp_fn::get_dbp_option_table($th->original_table);
                    if ($table_status == '') {
                        $table_status = $tables[$th->original_table]['status'];
                    } else if ( $table_status != $tables[$th->original_table]['status']) {
                        $table_status = 'MIXED';
                    }
                } 
            } 
        }
        if ($single_return) {
            return $table_status;
        } else {
            return $tables;
        }
    }

    /**
     * Torna tutte le colonne di tutte le tabelle interessate in una query
     * @return Array Lo Schema della tabella
     */
    public function get_all_fields_from_query() {
        global $wpdb;
        $sql = $this->current_query;
        if($this->sql_type() == "select") {
            $sql = str_replace(["\t","\r","\n"]," ", $sql);
            $from = stripos($sql, 'from');
            if ($from > 0) {
                $new_sql = "SELECT * ".substr($sql, $from);
                
                $limit = strripos($new_sql, ' LIMIT ');
                if ($limit > 0) {
                    $new_sql = substr($new_sql, 0, $limit);
                }
                
                $order = strripos($new_sql, ' ORDER BY ');
                if ($order > 0) {
                    $new_sql = substr($new_sql, 0, $order);
                }
                $new_sql = $new_sql." LIMIT 1";
                $new_sql = $this->ulitities_marks->restore($new_sql);
               // print ("<p>count: ".$new_sql."</p>");
                $result = $wpdb->get_var($new_sql);
                if (is_wp_error($result) || !empty($wpdb->last_error)) {
                    $this->last_error = $wpdb->last_error;
                    return [];
                } else {
                    $list_last_get_col_info = $wpdb->__get('col_info'); 
                    $fields = [];
                    foreach ($list_last_get_col_info as $llgci) {
                        if (isset($llgci->table) && isset($llgci->orgname)) {
                            $fields['`'.$llgci->table.'`.`'.$llgci->orgname.'`'] = $llgci->table.'.'.$llgci->orgname;
                        }
                    }
                    return $fields;
                }
            } else {
                $this->last_error = "Query parsing";
                return [];
            }
        } else {
            $this->last_error = "Not Select";
            return [];
        }
    }

    /**
     * Dopo get_list nasconde le chiavi primarie aggiungendo toggle hide ai campi aggiunti
     */
    public function remove_primary_added() {
        if (!is_array($this->items)) return;
        $table_header = reset($this->items);
        $key0 = key($this->items);
        foreach ($this->primary_added as $pri) {
            foreach ($table_header as $k=>$th) {
                if ($th->table == $pri->table_alias && $th->name_column == $pri->name) {
                    $this->items[$key0][$k]->toggle = "HIDE";
                }
            }
        }
        if ($this->query_before_primary_added != "") {
            $this->current_query = $this->query_before_primary_added;
            
        }
    }

    /**
     * Nelle liste vengono settati dei parametri di visualizzazione.
     * Questi vengono elaborati in questa fase. Vedi la classe dbp_items_list_setting per maggiori dettagli
     * @param Object $post
     * @param Boolean $htmlentities se è nel frontend (o nel menu esterno) non posso mostrare i tag html, ma devo fare lo striptag se taglio il testo, altrimenti li mostro 
     * @param int $text_length quando estraggo i dati di una lista se lascio text_length = 0 allora uso i parametri settati, se metto -1 allora lo mette per intero
     */
    public function update_items_with_setting($post = false, $htmlentities = true, $text_length = 0) {
      
        $list_general_setting = [];
        
        $list_general_setting['htmlentities'] = $htmlentities;
        if ($text_length != 0) {
            $list_general_setting['text_length'] = $text_length;
        } else if (!isset($list_general_setting['text_length'])) {
            $list_general_setting['text_length'] = 80;
        }
        $setting = new Dbp_items_list_setting();
        $this->items = $setting->execute_list_settings($this, false, $list_general_setting, 0);
    }

    /**
     * Ritorna il distinct di una colonna
     * @param String $column  Il nome della colonna che si intende estrarre con gli apici
     * @param String $filter Se impostato ritorna i valori che contengono come sottostringa il $filter
     * @return Array [{c=>il testo del campo distinct, p=>l'id se serve di filtrare per id oppure -1 n il numero di volte che compare},{}] | false if is not a select query 
     * Se p è > -1 allora è un campo speciale ovvero è meglio ricercarlo per primary key che passarlo
     * 
     */
    public function distinct($column, $filter = "") {
        global $wpdb;
        if($this->sql_type() != "select") return false;
        //get_primary_key
        $pri = $this->get_primary_key();
        $substring = $this->substr_with_strings($this->current_query, 'SELECT ', ['from ']);
        if ($pri == "") {
            return false;
        } else {
            if (strpos($column, '`.`') !== false) {
                $table_temp =  explode('`.`', $column);
                $table_alias = str_replace("`",'',$table_temp[0]);
                $pri_alias = '`'.Dbp_fn::sanitize_key($table_alias).'`.`'.Dbp_fn::sanitize_key($pri).'`';
            } else {
                $pri_alias = '`'.Dbp_fn::sanitize_key($this->table_name).'`.`'.Dbp_fn::sanitize_key($pri).'`';
            }
            $new_fragment = ' '.Dbp_fn::sanitize_key($column).' AS c, ' . $pri_alias . ' as p, count('. $pri_alias .') as n ';
        }
        
        $sql = str_replace($substring, $new_fragment, $this->current_query);
        $order_position = strripos($sql, ' ORDER '); // trovo l'ultima occorrenza
        if ($order_position > 0) {
            $sql = substr($sql, 0, $order_position);
        } 
        $limit_position = strripos($sql, ' LIMIT '); // trovo l'ultima occorrenza
        if ($limit_position > 0) {
            $sql = substr($sql, 0, $limit_position);
        } 
        $group_position = strripos($sql, ' GROUP BY '); // trovo l'ultima occorrenza
        if ($group_position > 0) {
            $sql = substr($sql, 0, $group_position);
        } 
        $have_position = strripos($sql, ' HAVING '); // trovo l'ultima occorrenza
        if ($have_position > 0) {
            $sql = substr($sql, 0, $have_position);
        } 
        $sql .=' GROUP BY '.esc_sql($column).' LIMIT 0, 50000';
        $sql = $this->ulitities_marks->restore($sql);
        $result = $wpdb->get_results($sql);
        
        if (is_wp_error($result) || !empty($wpdb->last_error)) {
            $this->last_error = $wpdb->last_error;
        } else if ($result) { 
            foreach ($result as $k=>&$r) {  
                if ($filter != "") {
                    if (stripos((string)$r->c, $filter) === false) {
                        unset($result[$k]);
                        continue;
                    }
                } 
                if (str_replace(" ","", strip_tags($r->c)) == "") {
                    $r->c = htmlentities($r->c);
                }
                // questi  simboli mi servono per fare i filtri speciali
                if (strlen($r->c) > 80 || substr($r->c, 0, 1) == "#" ||  substr($r->c, 0, 1) == "^" || esc_sql($r->c) != $r->c) {
                    if (strlen($r->c) > 80 ) {
                        $r->c = (substr(strip_tags($r->c),0, 70))."...";
                    }
                } else {
                    $r->c = strip_tags($r->c);
                    if (strlen($r->c) == 0) {
                        $r->c = '_##Empty values##_';
                    }
                    $r->p = -1;
                }
                
            }
            sort($result);
            // $response = array_filter($response);
        }
        return $result;
        
    }

    /**
     * Trova il tipo di query
     * @return String select|insert|updae|delete|multiqueries
     */
    public function sql_type($sql = "") {
        if ($sql == "") {
            $sql = $this->current_query;
        }
        //print "<p>CURRENT QUERY: ".$this->current_query."</p>";
        $array_ask = [];
        $sql = dbp_fn::all_trim($sql);
        if (substr_count($sql, ";") > 0) {
            $queries = explode(";", $sql);
            foreach ($queries as &$q) {
                $q = dbp_fn::all_trim($q);
            }
            $queries =  array_filter($queries); 
            if (count($queries) > 1) {
                return 'multiqueries';
            }
        }
        $sql = " ".$sql; // altrimenti array_filter rimuove i valori = 0
        $array_ask['select'] = stripos($sql, 'select');
        $array_ask['show']   = stripos($sql, 'show');
        $array_ask['insert'] = stripos($sql, 'insert');
        $array_ask['update'] = stripos($sql, 'update');
        $array_ask['delete'] = stripos($sql, 'delete');
        $array_ask['alter'] = stripos($sql, 'alter');
        $array_ask['truncate'] = stripos($sql, 'truncate');
        $array_ask['drop'] = stripos($sql, 'drop');
        $array_ask = array_filter($array_ask); 
        asort($array_ask);
        reset($array_ask);
        return key($array_ask);
    }

    /**
     * Ritorna la tabella del primo from
     * @todo v0.4 Sostituito da get_partial_query_from
     * @return sring
     */
    public function get_table() {
       // die ('get_table' .$this->current_query);
        if ($this->table_name != "") {
            return $this->table_name;
        }
        $substr = $this->substr_with_strings($this->current_query, "from ",  ['where ','order ','group ','limit ','join ',',']);
        $substr = $this->ulitities_marks->restore($substr);
        $tables = dbp_fn::get_table_list();
        foreach ($tables['tables'] as $table) {
            if (stripos($substr, $table) !== false)  {
                return $table;
            }
        }
        return "";
    }

    /**
     * Verifica se tutte le tabelle interessate dalla query hanno una chiave primaria autoincrement.
     * Se trova anche una sola tabella che non ce l'ha non mostra i filtri di ricerca.
     */
    public function check_for_filter() {
        if (is_countable($this->items)) {
            $header = reset($this->items);
            $tables = [];
            if (is_countable($header)) {
                foreach ($header as $item) {
                    $tables[] =  $item->original_table;
                }
            }
            $tables = array_filter(array_unique($tables));
            foreach ($tables as $table) {
                if ($this->get_primary_key($table) == "") {
                    $this->filter = false;
                }
            }
        }
    }

    /**
     * Memorizza le tabelle da passare se si deve creare un nuovo record
     * @param String $table_field table.column  column è la primary key
     */
    public function add_table_for_btn_new_record($table_field) {
        $this->tables_primaries[] = $table_field;
    }


    /**
     * ritorna l'array delle clausole where inserite nelle query
     * @return array [[table,field,value], ...]
     */
    public function get_default_values_from_query( ) {
        $from = $this->get_partial_query_from(true);
        $result = [];
        if (is_array($from)) {
            foreach ($from as $f) {
                if (isset($f[2])) {
                    // TODO se è OR non lo gestisco!
                    $temp_exp = explode(" @SPLIT@ ", str_ireplace([" and "," or "], " @SPLIT@ ", $f[2]));
                    foreach ($temp_exp as $exp) {
                       
                        $ris = $this->where_to_values($exp,  str_replace(["`",' '], '', $f[1]));
                        if (is_array($ris)) {
                            $result[] = $ris;
                        }
                    }
                    
                }
            }
        }
       
        $where = $this->get_partial_query_where(true);
        $table = $this->get_table();
        if (is_array($where)) {
            foreach ($where as $f) {
                $ris = $this->where_to_values($f, $table);
                if (is_array($ris)) {
                    $result[] = $ris;
                }
            }
        }
        return $result;
    }

    /**
     * Ritorna un array con tutte le tabelle e le rispettive chiavi primarie se accettabili dal sistema
     * Deve essere stata eseguita una query!!!!
     * @param bool $alias Default false. Se false trova i nomi delle tabelle originali oppure i nomi dei campi del risultato della query
     */
    public function get_pirmaries($alias = false) {
        // Trovo tutte le chiavi primari autoincrement di tutte le tabelle.
        if ($alias) {
            if (count( $this->get_primaries_alias ) > 0) {
                return  $this->get_primaries_alias;
            }
        } else {
            if (count( $this->get_primaries ) > 0) {
                return  $this->get_primaries;
            }
        }
        $primaries = [];
        $this->add_primary_ids();
        $tables = $this->get_partial_query_from(true);
        if (!$alias) {
            foreach ($tables as $table) {
                $orgtable = array_shift($table);
               
                $pri = dbp_fn::get_primary_key($orgtable);
                if ($pri != "") {
                    $primaries[$orgtable] = $pri;
                }
            }
        } else {
            //TODO Se di una tabella non trovo la chiave primaria? blocco tutto?
            $schema = $this->get_schema();
            foreach ($tables as $table) {
                $orgtable = array_shift($table);
                $pri = $this->get_primary_key($orgtable);
                if ($pri != "") {
                    foreach ($schema as $sc) {
                        if ($sc->orgtable == $orgtable && $sc->orgname == $pri) {
                            $primaries[$sc->table] = $sc->name;
                        }
                    }
                }
            }
        }
        if ($alias) {
            $this->get_primaries_alias = $primaries;
        } else {
            $this->get_primaries = $primaries;
        }
        return $primaries;
    }

     /**
     * Ritorna un array con tutti gli alias delle tabelle e le rispettive tabelle
     * @todo Uso get_partial_query_from per avere l'elenco delle tabelle?
     */
    public function get_query_tables() {
        $tables = [];
        if (is_array($this->items) && count($this->items) > 0) {
            $table_header = reset($this->items); 
            foreach ($table_header as $k=>$th) {
                if (is_object($th)) {
                    $tables[$th->table] = $th->original_table;
                } else {
                    return false;
                }
            }
           
        }
        $tables = array_filter($tables);
        return  $tables ;
    }

    /**
     * Aggiunge ai select le chiavi primarie per ogni tabella inserita 
     * così da poter gestire form di modifica ed inserimento. Questa funzione viene richiamata più volte!
     * @todo mettere in qualche modo queste colonne come hidden di default.
     * @return Array all primaries
     */
    public function add_primary_ids() {

        $current_query_select = $this->get_partial_query_select();
        if (count($this->primary_added) > 0) {
            return $this->all_primaries;
        }
        if ($this->query_before_primary_added == "") {
            $this->query_before_primary_added = $this->current_query;
        }
        $schema = $this->get_schema();
        // Preparo i dati:
        // Trovo tutte le chiavi primarie di ogni tabella interessata
        // e raggruppo i campi per tabella
        $all_pri_ids = [];
        $field_group = [];
        if (is_array($schema)) {
            foreach ($schema as $sc) {
                if ($sc->orgtable != "") {
                    // mi segno la chiave primaria della tabella
                    if (!array_key_exists($sc->orgtable, $all_pri_ids)) {
                        $all_pri_ids[$sc->orgtable] = self::get_primary_key($sc->orgtable);
                    }
                    // Raggruppo i campi per tabella (alias)
                    if ($sc->table != "") {
                        if (!isset($field_group[$sc->table])) {
                            $field_group[$sc->table] = ['table'=>$sc->orgtable, 'alias_table'=>$sc->table, 'fields'=>[]];
                        }
                        $field_group[$sc->table]['fields'][] = $sc;
                    }
                }
            }
        }
        $all_pri_ids = array_filter($all_pri_ids);
        // verifico se c'è la chiave primaria, oppure segno che deve essere aggiunta
        $add_select_pri = [];
        $all_primaries = [];
        foreach ( $field_group as $group) {
          
            // group [table:String, fields:[]]
            $exist_pri = false;
            if (isset($all_pri_ids[$group['table']])) {
                // verifico se è già presente la chiave primaria
                foreach ($group['fields'] as $fields) {
                    if ($fields->orgname == $all_pri_ids[$group['table']]) {
                        $exist_pri = true;
                        $all_primaries[$fields->table] = $all_pri_ids[$group['table']];
                        break;
                    }
                }
                if (!$exist_pri) {
                    $alias = dbp_fn::get_column_alias($group['alias_table']."_".$all_pri_ids[$group['table']], $current_query_select);
                    $add_select_pri[] =  '`'. $group['alias_table'].'`.`'.$all_pri_ids[$group['table']].'` AS `'.$alias.'`';
                    $this->primary_added[] = (object)['table_alias'=>$group['alias_table'], 'orgname'=>$all_pri_ids[$group['table']], 'name' => $alias];
                    $current_query_select .= " ".$alias;
                    $all_primaries[$fields->table] = $all_pri_ids[$group['table']];
                }
            }
        }
        
        // aggiungo i nuovi select, ripeto la query e aggiorno table_items
        if (count($add_select_pri) > 0) {
            $this->list_add_select(implode(", ", $add_select_pri));
        }
        $this->all_primaries = $all_primaries;
        return $all_primaries;
        
    }

     /**
     * Data una stringa WHERE ne ritorna l'array di campi = valori
     * @return Array
     */
    private function where_to_values( $string, $base_table ) {
       // print $string." \n";
        $exp = explode("=", $string);
        if (count($exp) == 2) {
            $field_temp = explode(".", $exp[0]);
                if (count($field_temp) == 2) {
                    $table = trim(str_replace(['`','(',')'],'', $this->ulitities_marks->restore($field_temp[0])));
                    $field = trim(str_replace(['`','(',')'],'', $this->ulitities_marks->restore($field_temp[1])));
                    $temp_value =   $this->ulitities_marks->restore($exp[1]);
                if (substr(str_replace(["("," "],"",$temp_value),0,1) == "'" || substr(str_replace(["("," "],"",$temp_value),0,1) == '"') {
                    while(substr($temp_value,0,1) == "(" || substr($temp_value,0,1) == " ") {
                        $temp_value = substr($temp_value, 1);
                    }
                    while(substr($temp_value,-1,1) == ")" || substr($temp_value,-1,1) == " ") {
                        $temp_value = substr($temp_value,0, -1);
                    }
                    return [$table, $field, substr(trim($temp_value),1,-1), $table, $field];
                } else {
                    $value_temp = explode(".", $this->ulitities_marks->restore($exp[1]));
                    if (count($value_temp) == 2) {
                        $value_table = trim(str_replace(['`','(',')'],'', $this->ulitities_marks->restore($value_temp[0])));
                        $value_field = trim(str_replace(['`','(',')'],'', $this->ulitities_marks->restore($value_temp[1])));
                        if ($base_table == $table) {
                            if (strpos($value_field, " ") !== false) {
                                return [$table, $field, "[%".dbp_fn::clean_string($value_table). ' get="'.addslashes($value_field).'"]', $value_table,  $value_field];
                            } else {
                                return [$table, $field, "[%".dbp_fn::clean_string($value_table).".".$value_field."]", $value_table,  $value_field];
                            }
                        } else if ($base_table == $value_table) {
                            if (strpos($value_field, " ") !== false) {
                                return [$table, $field, "[%".dbp_fn::clean_string($table). ' get="'.addslashes($field).'"]', $table,  $field];
                            } else {
                                return [$value_table, $value_field, "[%".dbp_fn::clean_string($table).".".$field."]", $table, $field];
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    


    /**
     * Trova una sottostringa dividendo per una stringa di inizia e una di fine
     * esempio: substr_with_strings($sql, "from",  ['where','order','group','limit']);
     * @param String $string La stringa da cui trovare la sottostringa
     * @param String $string_start La porzione di stringa da trovare all'inizio della stringa
     * @param String $array_needles_end L'array delle sottostringe che delimitano la fine della stringa (trova la più vicina)
     * @param Integer $offset Il numero di caratteri da cui parte la stringa.
     * @return String La sottostringa trovata
     */
    private function substr_with_strings($string, $string_start, $array_needles_end, $offset = 0) {
        $string = str_replace(["\t","\r","\n"]," ", $string);
        $first_occurrance = stripos($string, $string_start, $offset);
        if ($first_occurrance === false) return "";
        list($found_string, $pos) = $this->strpos_array($string, $array_needles_end,  $first_occurrance);
        if ($found_string != "") {
            return substr($string, $first_occurrance + strlen($string_start), $pos - $first_occurrance - strlen($string_start));
        } else {
            return substr($string, $first_occurrance + strlen($string_start));
        }
    }

    /**
     * Trova la prima occorrenza di una serie di parole all'interno di una stringa
     * @param String $string La stringa da cui trovare la sottostringa
     * @param Array $array_needles L'array delle sottostringe che delimitano la fine della stringa (trova la più vicina)
     * @param Integer $offset Il numero di caratteri da cui parte la stringa.
     * @return Array [string, integer] la stringa trovata e la posizione (dall'inizio della stringa, l'offset non viene calcolato). Se non trova nulla ritorna una stringa vuota
     */
    private function strpos_array($string, $array_needles, $offset = 0) {
        $min_pos = strlen($string);
        $min_occurrence = "";
        if (trim($string) != "" && strlen($string) > $offset) {
            foreach ($array_needles as $aff) {
                $current = stripos($string, $aff, $offset);
                if ($current !== false && $current <= $min_pos) {
                    $min_pos = $current;
                    $min_occurrence = $aff;
                }
            } 
        }
        return [$min_occurrence, $min_pos];
    }

    /**
     * Torna la chiave primaria 
     */
    private function  get_primary_key($table = "") {
        if ($table == "") {
            $table = $this->table_name;
        }
        return dbp_fn::get_primary_key($table);
    }

     /**
     * Converte i filtri in una stringa SQL
     * @param Array $filter [[op:'', column:'',value:'', required:0|1], ... ]
     * @return String
     */
    private function convert_filter_to_string($filter, $convert_filter_to_string) {
        global $wpdb;
        $array_where = [];
      
        if (is_array($filter) && count($filter) > 0) {
            foreach ($filter as $f) {
                if (is_object($f)) $f = (array)$f;
                if (!is_array($f)) continue;
                if (is_string($f['value'])) {
                    //print ($f['value']);
                   // print (PinaCode::execute_shortcode($f['value']));
                    //die;
                    $f['value'] = esc_sql(PinaCode::execute_shortcode($f['value']));
                    if (isset($f['required']) && $f['required'] == 1 && $f['value'] == '') {
                        return '(1 = 2)';
                    }
                    if (is_string($f['value']) && trim($f['value']) == "" && !in_array($f['op'],["NOT NULL",'NULL'])) continue;
                    if (is_string($f['value']) && trim($f['value']) == "_EMPTY_") $f['value'] = "";
                }
                if ($f['op'] == "=" && (is_array($f['value']) || is_object($f['value']))) {
                    $f['op'] = "IN";
                }
                if ($f['op'] == "!=" && (is_array($f['value']) || is_object($f['value']))) {
                    $f['op'] = "NOT IN";
                }

                switch ($f['op']) {
                    case "=":
                        $val = $f['value'];
                        if (isset($f['table']) && substr($val,0,1) == '#') {
                            $table_temp =  explode('`.`', $f['column']);
                            $table = str_replace("`",'',$table_temp[0]);
                            $column_name = str_replace("`",'',$table_temp[1]);
                            $pri = $this->get_primary_key($f['table']);

                            $val = $wpdb->get_var('SELECT `'.$column_name.'` FROM `'.$f['table'].'` WHERE `'. $f['table'] .'`.`'.$pri.'` = "'.esc_sql(substr($val,1)).'"');
                        }

                        $array_where[] = $f['column']." = '".esc_sql($val)."'";
                        break;
                    case ">=":
                        $array_where[] = $f['column']." >= '".esc_sql($f['value'])."'";
                        break;
                    case ">=cast":
                        $array_where[] = $f['column']." >= CAST('".esc_sql($f['value'])."' AS UNSIGNED)";
                        break;
                    case ">":
                        $array_where[] = $f['column']." > '".esc_sql($f['value'])."'";
                        break;
                    case ">cast":
                        $array_where[] = $f['column']." > CAST('".esc_sql($f['value'])."' AS UNSIGNED)";
                        break;
                    case "<":
                        $array_where[] = $f['column']." < '".esc_sql($f['value'])."'";
                        break;
                    case "<cast":
                        $array_where[] = $f['column']." < CAST('".esc_sql($f['value'])."' AS UNSIGNED)";
                        break;
                    case "<=":
                        $array_where[] = $f['column']." <= '".esc_sql($f['value'])."'";
                        break;
                    case "<=cast":
                        $array_where[] = $f['column']." <= CAST('".esc_sql($f['value'])."' AS UNSIGNED)";
                        break;
                    case "!=":
                        $array_where[] = $f['column']." != '".esc_sql($f['value'])."'";
                        break;
                    case "BETWEEN":
                    case "NOT BETWEEN":
                        $val = dbp_fn::set_sql_where_between_value($f['column'], $f['value'], $f['op']);
                        if ($val != "") {
                            $array_where[] = $val;
                        }  
                        break;
                    case "LIKE%":
                        if (is_array($f['value']) || is_object($f['value'])) {
                            $f['value'] = (array)$f['value'];
                            $array_temp_where = [];
                            foreach ($f['value'] as $val) {
                                $array_temp_where[] = $f['column']." LIKE '".( esc_sql($val))."%'";
                            }
                            $array_where[] = "(".implode("OR", $array_temp_where).")";
                        } else {
                            $array_where[] = $f['column']." LIKE '".(esc_sql($f['value']))."%'";
                        }
                        break;
                    case "LIKE":
                    case "NOT LIKE":
                        $op = $f['op'];
                        if (!is_array(($f['value'])) && !is_object($f['value'])) {
                            $f['value'] = [$f['value']];
                        }
                        if (isset($f['table'])) {
                            $table_temp =  explode('`.`', $f['column']);
                            $table = str_replace("`",'',$table_temp[0]);
                            $column_name = str_replace("`",'',$table_temp[1]);
                            $pri = $this->get_primary_key($f['table']);
                        }
                      
                        $f['value'] = (array)$f['value'];
                        $array_temp_where = [];
                        foreach ($f['value'] as $val) {
                            if (isset($f['table']) && substr($val,0,1) == '#') {
                                $val = $wpdb->get_var('SELECT `'.$column_name.'` FROM `'.$f['table'].'` WHERE `'. $f['table'] .'`.`'.$pri.'` = "'.esc_sql(substr($val,1)).'"');
                            }
                            $array_temp_where[] = $f['column']." ".$op." '%".(esc_sql($val))."%'";
                        }
                        $array_where[] = "(".implode("OR", $array_temp_where).")";
                        break;
                   
                    case "IN":
                    case "NOT IN":
                        if (is_string($f['value'])) {
                            $value = explode(",", $f['value']);
                        } else {
                            $value = (array)$f['value'];
                        }
                        $array_or = $new_v = $new_ids = $new_sel = [];
                        // qui divido per vari tipi: 
                        // #cerca il testo con un SELECT 
                        // ^ primary IN ()
                        $empty = false;
                        foreach ($value as $v) {
                            $this->private_filter_choose($new_ids, $new_sel, $empty, $new_v, $v);
                        }
                        if ($empty) {
                            if ($f['op'] == "NOT IN") {
                                $array_or[] = "(".$f['column']." IS NOT NULL AND ".$f['column']." != '')";
                            } else {
                                $array_or[] = "(".$f['column']." IS NULL OR ".$f['column']." = '')";
                            }
                        }
                        $op = "IN";
                        if ($f['op'] == "NOT IN") {
                            $op = "NOT IN";
                        }
                        $table_temp =  explode('`.`', $f['column']);
                        $table = str_replace("`",'',$table_temp[0]);
                        $column_name = str_replace("`",'',$table_temp[1]);
                        if (isset($f['table'])) {
                        $pri = $this->get_primary_key($f['table']);
                        
                        
                            $new_sel = array_filter(array_unique($new_sel));
                            if (count ($new_sel) > 0 && strpos($f['column'],'`.`') !== -1) {
                                $array_or[] =  $f['column'].' '.$op.' (SELECT `'.$column_name.'` FROM `'.$f['table'].'` WHERE `'. $f['table'] .'`.`'.$pri.'` IN ('.implode(', ', $new_sel).'))';
                            }
                        }
                        $new_v = array_filter(array_unique($new_v));
                        if (count ($new_v) > 0) {
                            $array_or[] = $f['column']." ".$op." (".implode(", ",$new_v).")";
                        }

                        $new_ids = array_filter(array_unique($new_ids));
                        if (count ($new_ids) > 0 && strpos($f['column'],'`.`') !== -1) {
                            $array_or[] = "`" . Dbp_fn::sanitize_key($table) ."`.`". Dbp_fn::sanitize_key($pri) ."` ".$op." (".implode(", ",$new_ids).")";
                        }
                        if (count($array_or) == 1) {
                            $array_where[] = implode("", $array_or);
                        } else  if (count($array_or) >1) {
                            $array_where[] = "(".implode(" OR ", $array_or).")";
                        }

                        break;
                    case "NULL":
                        $array_where[] = "(".$f['column']." IS NULL OR ".$f['column']." = '')";
                        break;
                    case "NOT NULL":
                        $array_where[] = "(".$f['column']." IS NOT NULL AND ".$f['column']." != '')";
                        break;
                }
                
            }
        }
        if (count($array_where) > 0) {
           // print "<p>(".trim(implode(" ". $convert_filter_to_string." ", $array_where)).")</p>";
            return "(".trim(implode(" ". $convert_filter_to_string." ", $array_where)).")";
        } else {
            return "";
        }
    }

    /**
     * Divide il tipo di filtro di ricerca a seconda del carattere_speciale che lo precede
     */
    private function  private_filter_choose(&$new_ids, &$new_sel, &$empty, &$new_v, $v) {
        if (substr($v,0,1) == '^') {
            $new_ids[] = "'".esc_sql(str_replace('^','', trim($v)))."'";
        } else if (substr($v,0,1) == '#') {
            $new_sel[] = "'".esc_sql(str_replace('#','', trim($v)))."'";
        }else if ($v == '_##Empty values##_') {
            $empty = true;
        } else {
            $new_v[] = "'".esc_sql((trim($v)))."'";
        }
    }

    /**
     * Verifica se la query è di modifica tabella. In quel caso è permessa solo se la tabella è in DRAFT mode
     * @param String $sql
     * @return Boolean
     */
    private function check_is_ok_for_draft($sql) {
        $is_ok_for_draft = true;
        // Se aggiunge una primary key glielo permetto!
        if (stripos($sql, 'INT AUTO_INCREMENT PRIMARY KEY')) {
            return true;
        }
        if (stripos($sql, 'DROP ') !== false && stripos($sql, 'DATABASE ') !== false)  {
            $this->last_error = __('You cannot remove the entire database','db_press');
            return false;
        }
        if (stripos($sql, 'TRUNCATE ') !== false && stripos($sql, 'TABLE ') === false) {
            $temp_sql = preg_split("/truncate/i", $sql);
            array_shift($temp_sql);
            $sql = "TRUNCATE TABLE ".trim($temp_sql[1]);
        }
        if ((stripos($sql, 'ALTER ') !== false && stripos($sql, 'TABLE ') !== false) || (stripos($sql, 'DROP ') !== false && stripos($sql, 'TABLE ') !== false) || (stripos($sql, 'TRUNCATE ') !== false && stripos($sql, 'TABLE ') !== false)) {
            $is_ok_for_draft = false;
            $temp_exp =  preg_split("/table/i", $sql);
            $temp_exp2 = explode(" ", $temp_exp[1]);
            $temp_exp2 = array_filter($temp_exp2);
            $alter_table = str_replace("`", "", array_shift($temp_exp2));
            $info = dbp_fn::get_dbp_option_table($alter_table);
            if ($info['status'] == 'DRAFT') {
                $is_ok_for_draft = true;
            } else {
                $this->last_error = __('Only tables in draft can be Altered. Go to structure and change the status of the table','db_press');
            }
        }
        return $is_ok_for_draft;
    }


    /**
     * Verifica se la query è di modifica dati. In quel caso non è permessa se la tabella è in stato CLOSE
     * TODO non testata!
     * @param String $sql
     */
    private function check_is_ok_for_close($sql) {
        $sql =  str_ireplace(["delete from", 'insert into', 'update'],'', $sql);
        $temp_sql = explode(" ", $sql);
        $temp_sql = array_filter($temp_sql);
       
        if (count($temp_sql) == 0) {
            return true;
        }
        $info = dbp_fn::get_dbp_option_table(array_shift($temp_sql));
        if ($info['status'] == 'CLOSE' && in_array($this->sql_type(), ['insert','update','delete','alter','drop','truncate'])) {
           
            return false;
        } else {
            return true;
        }
    }

    /**
     * Verifica che la query non abbia due colonne con lo stesso nome
     */
    private function get_list_check_same_column($list_last_get_col_info) {
        $temp_names = [];
        foreach ($list_last_get_col_info as $val ) {
            if (in_array($val->name, $temp_names)) {
                $this->last_error = sprintf(__('The query has multiple columns (<b>%s</b>) with the same name.', 'db_press'), $val->name);
                $this->effected_row = 0;
                $this->items = [];
               
                 return false;
            }
            $temp_names[] = $val->name;
        }
        return true;
    }
}