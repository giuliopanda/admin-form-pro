<?php
 /**
  * Classe statica per l'esecuzione dello script. 
  * È una classe statica perché in questo modo da qualsiasi punto del codice si può riavviare un nuovo shortcode o valutare una condizione
  * o usare la calcolatrice.
  */
namespace DbPress;

class PinaCode
{
	public static $registry 	= null;

	public static function init() {
		// Resetto tutto
		self::$registry = new PcRegistry();
	}

	/**
	 * Esegue uno shortcode
	 */
	public static function execute_shortcode($string) {
		if (self::$registry == null) PinaCode::init(); 
		self::$registry->current_return = false;
		return self::$registry->short_code($string);
	}
	/**
	 * ritorna una viariabile
	 */ 
	public static function get_var($path = "main", $default = NULL) {
		if (self::$registry == null) PinaCode::init(); 
		if ($path == "*") {
			return self::$registry->registry;
		}
		return self::$registry->get($path, $default);
	}
	/**
	 * imposta una variabile
	 */ 
	public static function set_var($path, $data) {
		if (self::$registry == null) PinaCode::init(); 
		self::$registry->set($path, $data);
	}

	/**
	 * verifica se una variabile esiste
	 */ 
	public static function has_var($path) {
		if (self::$registry == null) PinaCode::init(); 
		return self::$registry->has($path);
	}

	/**
	 * Elabora una condizione logica o matematica (tipo calcolatrice + espressioni logiche)
	 * @param String $string
	 * @return String|Object if string is a json. 
	 */
	public static function math_and_logic($string) {
		if (self::$registry == null) PinaCode::init(); 
		$string = PinaCode::get_registry()->short_code($string); //IMPORTANTE
		if (!is_string($string)) return $string;
		
		while (pina_has_brackets($string)) {
			$string = pina_execute_brackets($string);
		}		
		return pina_calculator($string);
	}

	/**
	 * Dato uno shortcode ne restituisce il risultato
	 */
	public static function get_shortcode_and_stransform($string) {
		if (self::$registry == null) PinaCode::init(); 
		return self::$registry->get_and_stransform($string);
	}

	/**
	 * Dato uno shortcode ne restituisce il risultato
	 */
	public static function get_registry() {
		if (self::$registry == null) PinaCode::init(); 
		return self::$registry;
	}

	/**
	 * TODO Trova il blocco successivo 
	 */
	public static function find_next_block() {

	}
}