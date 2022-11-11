<?php
/**
 * Toglie da una stringa tutte le parentesi tonde e i testi tra virgolette. 
 * In questo modo eventuali subquery o comandi SQL dentro testi non verranno elaborati in fase di modifica della query
 *
 * @package  DbPress
 */
namespace DbPress;

class  Dbp_util_marks_parentheses 
{
    private $variables = array();  
    /**
        * trova le stringe tra virgolette doppie (") non prende in considerazione \" e le sostituisce con delle variabili
        * trova le stringe tra virgolette  (') non prende in considerazione \' e le sostituisce con delle variabili
        * trova le stringe tra parentesi tonde e le sostituisce con delle variabili
        * @param String $string
        * @return String
        */
    public function replace($string) {
        $ori_string = $string;
        $re = '/(\"(\\\"|\s|.)*?\")/m';
        preg_match_all($re, $string, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match) {
            $string = $this->convert_single($string, $match);
        }

        $re = '/(\'(\\\\\'|\s|.)*?\')/m';
        preg_match_all($re, $string, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match) {
            $string = $this->convert_single($string, $match);
        }

        $re = '/\([^()]*\)/m';
        preg_match_all($re, $string, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match) {
            $string = $this->convert_single($string, $match);
        }

        $re = '/\`(\s|.)*?\`/m';
        preg_match_all($re, $string, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match) {
            $string = $this->convert_single($string, $match);
        }
        
        if ($ori_string != $string) {
            $string = $this->replace($string);
        }
        return $string;
    }

    /**
    * Ripristina un testo con le il contenuto originale con le virgolette
    * @param String $string
    * @return String
    */
    public function restore($string) {
        $ori_string = $string;
        foreach ($this->variables as $key=>$value) {
            $string = str_replace($key, $value, $string);
        }
        if ($ori_string != $string) {
            $string = $this->restore($string);
        }
        return $string;
    }

    /**
     * Converte in variabile una singola occorrenza
     */
    private function convert_single($string, $match) {
        $uniqueId =  "{{".uniqid("var")."}}";
        while(strpos($string, $uniqueId) != false) {
            $uniqueId = "{{".uniqid("var", true)."}}";
        }
        $this->variables[$uniqueId] = $match[0];
        return str_replace($match[0], $uniqueId, $string);
    }
}