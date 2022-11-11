<?php
/**
 * La classe serve come base per tutte strutture oggetti che creerò
 */
namespace DbPress;

abstract class  DbpDs_data_structures
{
    public function __construct($array = ""){
        if (is_array($array)) {
            $this->set_from_array($array);
        }
        if (is_object($array)) {
            $this->set_from_array((array)$array);
        }
    }
    
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            $backtrace = debug_backtrace();
            $page = array_shift( $backtrace );
            trigger_error(get_class($this) . ': GET '.$property. " NOT EXISTS ".$page['file']." LINE: ".$page['line'], E_USER_WARNING);
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }else {
            /*
            print "<h1>".$property."</h1>";
            $trace=debug_backtrace();
            var_dump($trace);
            die;
            trigger_error(get_class($this).': GET "' . $property. '" NOT EXISTS ', E_USER_WARNING);
            */
        }
    return $this;
    }

    public function isset($property) {
        return  (property_exists($this, $property) && $this->$property != null && $this->$property != '');
    }

    /**
     * Setta le variabili a partire da un array
     *```php
     * (new DbpDs_list_setting())->set_from_array($vars);
     *```
     * @param array $array
     * @return Class
     */
    public function set_from_array($array) {
        foreach ($array as $key=>$value) {
            $this->$key = $value;
        }
        return $this;
    }

    public function get_array() {
        $vars = get_object_vars($this);
        foreach ($vars as $key=>$v) {
            if (empty($v)) {
                unset($vars[$key]);
            }
        }
        return $vars;
    }
}