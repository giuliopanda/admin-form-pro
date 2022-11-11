<?php
/**
 * La struttura dei dati per la gestione degli elenchi.
 * 
 * 
 */
namespace DbPress;

class  DbpDs_list_setting 
{
    /** @var string $name Il nome del campo nella query */
    protected  $name;
    /** @var string $orgname Il nome del campo */
    protected  $orgname; 
    /** @var string $table Il nome della tabella nella query */
    protected  $table;
     /** @var string $orgtable Il nome della tabella originale */
    protected  $orgtable; 
     /** @var string $def; */
    protected  $def; 
    /** @var string $db Il database da cui estrarre i dati */
    protected  $db; 
    /** @var string $catalog */
    protected  $catalog;
    /** @var int $max_length */
    protected  $max_length; 
    /** @var int $length */
    protected  $length; 
    /** @var int $charsetnr */
    protected  $charsetnr; 
    /** @var string $flags */
    protected  $flags;
    /** @var string $type è il tipo di campo del database */
    protected  $type;
    /** @var string $decimals */
    protected  $decimals;
    /** @var string $name_request */
    protected  $name_request;
    /** 
     *@var string $title Il nome della colonna nella tabella da spampare
     */
    protected  $title;
    /** @var string $toggle */
    protected  $toggle;
    /** @var string $view è il tipo di campo che verrà visualizzato e scelto quindi dal select columns type */
    protected  $view;
    /** @var string $custom_code */
    protected  $custom_code; 
    /** @var string $order */
    protected  $order;
    /** @var string $origin FIELD|CUSTOM  */
    protected  $origin;
    /** @var string $searchable; */
    protected  $searchable;
    /** @var string $mysql_name; `table`.`field` */
    protected  $mysql_name;
    /** @var string $mysql_table; table */
    protected  $mysql_table;
    /** @var string $width  // la classe che definisce la larghezza della colonna small|regular|large|extra-large */
    protected  $width = 'small'; 
    /** @var string $align  // L'allineamento delle celle */
    protected  $align = 'center-left'; 
    /** @var string $custom_param */
    protected  $custom_param; 
    /** @var string $format_values */
    protected  $format_values; 
    /** 
     * @param string $format_styles 
     */
    protected  $format_styles; 
    /** 
     * @param int $lookup_id Se il campo è di tipo lookup
     */
    protected  $lookup_id; 
    /** 
     * @param string lookup_sel_val Se il campo è di tipo lookup
     */
    protected  $lookup_sel_val; 
    /** 
     * @param string lookup_sel_txt Se il campo è di tipo lookup
     */
    protected  $lookup_sel_txt; 

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            trigger_error('dbpDs_list_setting: GET '.$property. " NOT EXISTS ", E_USER_WARNING);
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }else {
            /*
            trigger_error('dbpDs_list_setting: GET '.$property. " NOT EXISTS ", E_USER_WARNING);
            */
        }
    return $this;
    }

    /**
     * la funzione isset del php nelle variabili delle classi non può essere usato.
     * @param String $property
     */
    public function isset($property) {
        return  (property_exists($this, $property) && $this->$property != null && $this->$property != '');
    }

    /**
     * Setta le variabili a partire da un array
     *```php
     * (new DbpDs_list_setting())->set_from_array($vars);
     *```
     * @param array $array
     * @return \dbpDs_list_setting
     */
    public function set_from_array($array) {
        foreach ($array as $key=>$value) {
            $this->$key = $value;
        }
        return $this;
    }
    /**
     * Ritorna l'array per il salvataggio nel db
     *
     * @return array
     */
    public function get_for_saving_in_the_db() {
        $vars = get_object_vars($this);
        unset($vars['def']);
        unset($vars['db']);
        unset($vars['catalog']);
        unset($vars['max_length']);
        unset($vars['length']);
        unset($vars['charsetnr']);
        unset($vars['flags']);
        unset($vars['decimals']);
        if ($vars['view'] != 'LOOKUP') {
            unset($vars['lookup_id']);
            unset($vars['lookup_sel_val']);
            unset($vars['lookup_sel_txt']);
        }
        return $vars;
    }

    public function get_array() {
        $vars = get_object_vars($this);
        if ($vars['view'] != 'LOOKUP') {
            unset($vars['lookup_id']);
            unset($vars['lookup_sel_val']);
            unset($vars['lookup_sel_txt']);
        }
        return $vars;
    }
}


/**
  * dbpDs_list_delete La struttura per la rimozione di un campo
  * Qui si può aprire un mondo sulle condizioni per poter cancellare un campo, 
  * eventuali tabelle collegate ecc...
  */
class  DbpDs_list_delete_params extends dbpDs_data_structures
{
    /** @var string $allow Se da questa view è permesso cancellare un record */
      protected $allow;

    /** @var array $remove_tables_alias Se l'elenco delle tabelle che è ammesso rimuovere */
     protected $remove_tables_alias = [];
     
    /** @var string $field_title Il campo da mostrare quando si chiede la conferma di rimozione */
    //protected $field_title;
    /** @var string $soft_delete Il campo che gestisce il soft delete */
    //protected $soft_delete_field;
    /** @var string $soft_delete Il campo che gestisce il soft delete */
    //protected $soft_delete_value;
    /** @var string %sql_allow La query di preparazione per verificare se un campo si può rimuovere ? */

    public function __construct($array = ""){
        if (is_array($array)) {
            $this->set_from_array($array);
        }
        if (is_object($array)) {
            $this->set_from_array((array)$array);
        }
        $this->allow = false;
        if (is_array($this->remove_tables_alias) && count($this->remove_tables_alias) > 0 ) {
            foreach ($this->remove_tables_alias as $value) {
                if ($value == 1) {
                    $this->allow = true;
                }
            }
        } else {
            // se non impostato 
            $this->allow = true;
        }
    }
}