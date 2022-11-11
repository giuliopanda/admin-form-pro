<?php
/**
 * Classe che gestisce i file temporanei di db_press
 * 
 */
namespace DbPress;

class  Dbp_temporaly_files
{
    /**
     * @var String $temporay_dir
     */
    protected $temporay_dir = "/temp/";
     /**
     * @var String $last_error L'utlimo errore della classe
     */
    public $last_error = "";
    /**
     * Ritorna la directory dove sono salvati i file temporanei
     * o false In quel caso viene settato $this->last_error
     * TODO metterla privata?
     */
    public function get_dir() {
        $dir = get_temp_dir();
        if (is_dir($dir) && is_writable($dir)) {
            $dir = $dir."admin-form/";
            if (!is_dir($dir)) {
                mkdir($dir);
            } 
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            } 
        }
        $dir = dirname(__FILE__, 2) . "/temp/";
        if (!is_dir($dir)) {
            mkdir($dir);
        } 
        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        } 
        $this->last_error = sprintf(__("I cannot access a temporaly folder: %s", 'db_press'), $dir);
        return false;
    }

    /**
     * Salva i dati e ritorna il nome del file creato Se esisteva, lo sovrascrive
     * @return String|False $filename 
     */
    public function store($data, $filename = "") {
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        $this->last_error = "";
        $temp_file = $this->new_temp_file($filename);
        if ($temp_file == "") return false;
        $data = maybe_serialize($data);
        file_put_contents($temp_file, $data);
        return basename($temp_file);
    }

    /**
     * Aggiunge dati ad un file già creato. Se non esiste comunque lo crea
     * @param  string|array|object $data
     * @return String|False $filename 
     */
    public function append($data, $filename = "") {
        $this->last_error = "";
        $dir = $this->get_dir();
        if ($dir == false) return false;
        $data = maybe_serialize($data);
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        if ($filename == "" || !is_file($dir.$filename)) {
            $temp_file = $this->new_temp_file($filename);
        } else {
            $temp_file = $dir.$filename;
        }
        if ($temp_file == "") return false;
        file_put_contents($temp_file, $data, FILE_APPEND );
        return basename($temp_file);
    }

    /**
     * Read file and return data
     * @param String $filename
     * @param Boolena $maybe_unserialize
     * return Object|Array|String
     */
    public function read($filename, $maybe_unserialize = true) {
        $dir = $this->get_dir();
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        if ($dir == false) return false;
        if (!is_file($dir.$filename)) {
            $this->last_error = __("Temporary file cannot be read", 'db_press');
            return false;
        }
        $data = file_get_contents($dir.$filename);
        if ($maybe_unserialize) {
            return maybe_unserialize($data);
        } else {
            return $data;
        }
    }
    /**
     * Detete a temporaly file
     */
    public function delete($filename = "") {
        $dir = $this->get_dir();
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        if ($dir == false) return false;
        if (!is_file($dir.$filename)) {
            return false;
        } else {
            unlink ($dir.$filename);
            if (is_file($dir.$filename)) {
                $this->last_error = sprintf(__("I can't delete the file: %", 'db_press'), $dir.$filename);
                return false;
            }
            return true;
        }
    }

    /**
     * Ritorna il file temporaneo con percorso e nome oppure una stringa vuota
     * @param String $filename
     * @return String Il nome del file
     */
    private function new_temp_file($filename = "") {
        $dir = $this->get_dir();
        if ($dir == false) return false;
        if ($filename == "") {
            $filename = $filename_temp = uniqid();
            $count = 1;
            while(is_file($dir.$filename) && $count < 100) {
                $filename = $filename_temp."_".$count;
                $count++;
            }
        } else if (is_file($dir.$filename)) {
            $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
            unlink($dir.$filename);
        }
       // print " NOME FILE ".$filename; 
        return $dir.$filename;
    }
    /**
     * Cancello tutti i file temporanei più vecchi di 48 ore
     */
    public function clear_old() {
        $dir = $this->get_dir();
        if ($dir == false) return false;
        $files = scandir($dir);
        $time = time() - (60 * 60 * 48);
        foreach ($files as $f) {
            if (!in_array($f, [".", ".."]) && is_file($dir.$f)) {
               if (filemtime($dir.$f) < $time) {
                   //print "<p>UNLINK: ".$f." ".filemtime($dir.$f)."</p>";
                   unlink ($dir.$f);
               }
            }
        }
    }

    /**
     * muove un file nella directory temporanea e ne ritorna il nuovo nome
     * @param String $post_file_name il nome del campo input type upload passato dalla form che si vuole copiare nei file temporanei es $_FILES['myfile'][] => $post_file_name = 'myfile'
     * @return String
     */
    public function move_uploaded_file($post_file_name) {
        $post_file_name = sanitize_text_field(str_replace(["/","\\"],"", $post_file_name ));
        if (!isset($_FILES[$post_file_name]['tmp_name']) || $_FILES[$post_file_name]['tmp_name'] == "") {
            $this->last_error = __('No file uploaded', 'db_press');
            return false;
        }
        $file = $_FILES[$post_file_name]['tmp_name'];
        $filename = $this->new_temp_file();
        if ($filename == "") return '';
        // questa è una funzione php
        $ris = move_uploaded_file($file, $filename);
        if ($ris == false) {
            $this->last_error = __('Move uploaded file error', 'db_press');
            return '';
        }
        return basename($filename);
    }

    /**
     * Ritorna l'array dal csv.
     * @param String $filename
     * @param String $delimiter
     * @param Boolean $first_row_key Se true imposta la prima riga come chiavi dell'array
     * @param Integer $row_read Il numero di righe da caricare. Se 0 le carica tutte
     * @return Array
     */
    public function read_csv($filename, $delimiter = "", $first_row_key = true, $row_read = 0) {
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        $dir = $this->get_dir();
        if ($dir == false) return false;
        if ($delimiter == "") {
            $delimiter = $this->find_csv_delimiter($filename);
        } else {
            $delimiter = Dbp_fn::convert_special_to_char($delimiter);
        }
        $result = [];
        $count_row = 0;
        $keys_column = [];
        if (!is_file($dir.$filename)) {
            $this->last_error = __('The file you are trying to upload does not exist', 'db_press');
            return false;
        }
        if (($handle = fopen($dir.$filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 262144, $delimiter)) !== FALSE) {
                if ($row_read > 0 && $count_row >= $row_read) {
                    break;
                } 
                $count_row++;
                if ($first_row_key && $count_row == 1) {   
                    foreach ($data as $k=>$v) {
                        $keys_column[$v] = $v;
                    }
                    $result[] = $keys_column;
                }  else if ($count_row == 1) {
                    $keys_column = [];
                    foreach ($data as $k=>$v) {
                        $keys_column[$k] = __('Column ','db_press').$k;
                    }
                    $result[] = $keys_column;
                    $result[] = $data;
                }
                if ($count_row > 1) {
                    $temp_data = [];
                    $kcount = 0;
                    foreach ($keys_column as $k=>$v) {
                        if (array_key_exists($kcount, $data)) {
                            $temp_data[$k] = $data[$kcount];
                            $kcount++;
                        }
                    }
                    $result[] = $temp_data;
                }
            }
            fclose($handle);
        }        
        return $result;
    }

      /**
     * Ritorna la struttura dell'array del csv
     * TODO da verificare DECIMAL, Da gestire le date, e il NULL!
     * @param String $filename
     * @param String $delimiter
     * @param Boolean $first_row_key Se true imposta la prima riga come chiavi dell'array
     * @param Integer $row_read Il numero di righe da caricare. Se 0 le carica tutte
     * @return Array
     */
    public function csv_structure($filename, $delimiter = "", $first_row_key = true) {
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        $array = $this->read_csv($filename, $delimiter, $first_row_key , 1000);
        $keys_column = array_shift($array);
        $result = [];
        foreach ($array as $data) {
            foreach ($keys_column as $k=>$v) {
                if (!array_key_exists($k, $data)) {
                    return false;
                }
                if (isset($result[$k]->max)) {
                    $max = max(strlen((String)$data[$k]), $result[$k]->max);
                } else {
                    $max  = strlen((string)$data[$k]);
                }
                if (is_numeric($data[$k]) && ( !isset($result[$k]) || $result[$k]->type == "numeric" || $result[$k]->type == "decimal"  ) ) {
                    if ( (floor($data[$k]) != $data[$k] || (isset( $result[$k]->type ) && $result[$k]->type == "decimal" ) )) {
                        $decimal = explode(".", $data[$k]);
                        if (count ($decimal) == 2) {
                            $prec2 = strlen((string)array_pop($decimal));
                        } else {
                            $prec2 = 0;
                        }
                        if (isset($result[$k]->precision)) {
                            $prec1 = $result[$k]->precision;
                        } else {
                            $prec1 = 0;
                        }
                        $result[$k] = (object)['name' => $v, 'type' => 'decimal', 'max'=>$max, 'precision' =>max($prec1, $prec2)];
                    } else {
                        $result[$k] = (object)['name' => $v, 'type' => 'numeric', 'max'=>$max];
                    }
                    if ($data[$k] < 0 || (!isset($result[$k]->attributes) || $result[$k]->attributes == 'UNSIGNED') ) {
                        $result[$k]->attributes = 'UNSIGNED';
                    } else if ((!isset($result[$k]->attributes) || $result[$k]->attributes == 'SIGNED')) {
                        $result[$k]->attributes = 'SIGNED';
                    }
                } else {
                    $result[$k] = (object)['name' => $v, 'type' => 'text', 'max'=>$max];
                }
               
            }
        }
    
        foreach ($result as &$r) {
            $r->field_name = Dbp_fn::clean_string(strtolower(trim($r->name)));
            $r->field_length = "";
            $r->primary = "f";
            $r->attributes = "";
            $r->ai = "f";
            $r->null = "f";
             // vado in difetto per evitare problemi
            if ($r->type == "numeric" && $r->max < 10 ) {
                $r->field_type = "INT";
                $r->field_length = 11;
            } else if ($r->type == "numeric") {
                $r->field_type = "BIGINT";
                $r->field_length = 20;
            } else if ($r->type == "decimal") {
                $r->field_type = "DECIMAL";
                $r->field_length = $r->max ."," . $r->precision;
            } else if ($r->type == "text" && $r->max <= 250 ) {
                $r->field_type = "VARCHAR";
                $r->field_length = 255;
            } else {
                $r->field_type = "TEXT"; 
            }
            if (strtolower(trim($r->field_name)) == "id") {
                $r->field_type = "INT";
                $r->attributes = "UNSIGNED";
                $r->auto_increment = "t";
                $r->field_length = 11;
                $r->primary = "t";
                $r->ai = "t";
            } 
            $r->preset = Dbp_fn_structure::get_preset($r);
        }
       // var_dump ($result);
        return $result;
    }

    /**
     * Trova il delimitatore di un csv.
     * @todo DA RISCRIVERE!!!!
     * @param String $filename
     * @return String
     */
    public function find_csv_delimiter($filename) {   
        $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
        $dir = $this->get_dir();
        if ($dir == false) return false;
        if (!is_file($dir.$filename)) {
            $this->last_error = __("Temporary file cannot be read", 'db_press');
            return false;
        }
        $csv_list = [];
        if (($handle = fopen($dir.$filename, "r")) !== FALSE) {
            
            $delimiters = [';',',',"\t"];
            
            $row = 0;
            while (($buffer = fgets($handle, 131072)) !== false && $row < 50) {
                $row++;
                if ($row == 1) {
                    foreach ($delimiters as $key=>$del) {
                        $count = count(str_getcsv($buffer, $del));
                        if ($count > 1) {
                            $csv_list[$key] = [count(str_getcsv($buffer, $del)), $del];
                        }
                    }
                } else {
                    foreach ($delimiters as $key=>$del) {
                        if (@$csv_list[$key][0] != count(str_getcsv($buffer, $del))) {
                            unset($csv_list[$key]);
                        }
                    }
                }
                if (count($csv_list) == 1) {
                    break;
                }
            }
            fclose($handle);
            $max =  $key_ok = -1;
            if (count($csv_list) > 1) {
                foreach ($csv_list as $key=>$cl) {
                    if ($cl[0] > $max) {
                        $key_ok = $key;
                        $max = $cl[0];
                    }
                    if ($cl[1] == ";" || $cl[1] == ",") {
                        $key_ok = $key;
                        break;
                    }
                }
            }
            if ($key_ok > -1) {
                $csv_list = array($csv_list[$key_ok]);
            }
           
        }
        if (count($csv_list) == 1) {
            $ris = array_shift($csv_list);
            return $ris[1];
        } else {
            return ',';
        }
    }

    /**
     * Verifica se si può usare la prima riga come intestazioni
     * Non ci devono essere colonne ripetute o vuote
     * @param String $filename
     * @return String
     */
    public function csv_allow_to_use_first_line($first_line) {
        $added = [];
        foreach ($first_line as $value) {
            if (trim($value) == ""){
                return false;
            }
            if (isset($added[$value])){
                return false;
            }
            $added[$value] = 1;
        }
        return true;
    }

    /**
     * Salva un csv
     * @param Array $data
     * @param String $filename
     * @param Char $delimiter
     * @param Boolean $append Se il file esiste già aggiunge le righe senza l'header.
     * @return String il nome del file appena creato
     */
    public function store_csv($data, $filename = "", $delimiter = ";", $append = false) {
        $this->last_error = "";
        // copiato new_temp_file ma aggiunto append perché altrimenti rimuoveva sempre il file se esisteva
        $dir = $this->get_dir();
        if ($dir == false) return false;
        if ($filename == "") {
            $filename = $filename_temp = uniqid();
            $count = 1;
            while(is_file($dir.$filename) && $count < 100) {
                $filename = $filename_temp."_".$count;
                $count++;
            }
        } else if (!$append && is_file($dir.$filename)) {
            $filename = sanitize_text_field(str_replace(["/","\\"],"", $filename ));
            unlink($dir.$filename);
        }
        $temp_file = $dir.$filename;
        if ($temp_file == "") return false;

        // Non vengono aggiunti i nomi delle colonne se il file esiste già e append è true.
        $add_heaer = true;
        if ($append && is_file($dir.$filename)) {
            $add_heaer = false;
        }

        $mode = ($append) ? 'a' : 'w';
        $fp = fopen($temp_file, $mode);
        // I add the array keys as CSV headers
        if ( $add_heaer) {
            $first = reset($data);
            if (is_object($first)) {
                $first = (array)$first;
            }
            fputcsv($fp,array_keys($first),$delimiter);
        }
        // Add all the data in the file
        foreach ($data as $fields) {
            if (is_object($fields)) {
                $fields = (array)$fields;
            }
            fputcsv($fp, $fields,$delimiter);
        }
        // Close the file
        fclose($fp);
        return basename($temp_file);
    }

}