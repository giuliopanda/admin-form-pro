<?php
/**
 * PinaAttributes
 * Gestione degli attributi di pinacode relativi al wrapping html
 * l'inserimento e modifica di codice html che contiene l'output
 */


/**
 * aggiunge uno o più testi (tag html per lo più) prima e dopo la stringa elaborata. 
 * 
 */
namespace DbPress;

class PinaAfterAttributes
{
	private static $wrap = [];
	private static $attributes = [];
	/**
	 * Ripulisce i testi da aggiungere
	 */
	static function reset() {
		self::$wrap = [];
	}
	/**
	 * Aggiunge un tag html (gli attributi devono essere messi a parte)
	 */
	static function wrap($start_tag, $end_tag, $attributes="") {
		self::$wrap[] = [$start_tag, $end_tag, $attributes];
	}
	/**
	 * Aggiunge gli attributi ad un tag html
	 * @param Number $wrap_index definisce a quale wrap fanno riferimento gli attributi 
	 */
	static function add_attributes($attribute, $value, $wrap_index=0) {
		if (!array_key_exists($wrap_index, self::$attributes)) {
			self::$attributes[$wrap_index] = [];
		}
		if ($attribute == "style" || $attribute == "class") { 	
			if (!array_key_exists($attribute, self::$attributes[$wrap_index])) {
				self::$attributes[$wrap_index][$attribute] = [$value];
			} else {
				self::$attributes[$wrap_index][$attribute][] = $value;
			}
		} else {		
			self::$attributes[$wrap_index][$attribute] = $value;
		}
	}

	/**
	 * Ritorna l'array degli attributi da stampare nell'html con style e class implosi
	 */
	static function get_attributes($index) {
		$attributes = [];
		if (array_key_exists($index, self::$attributes)) {
			$attributes =  self::$attributes[$index];
		
			if (array_key_exists('class', self::$attributes[$index])) {
				$attributes['class'] = implode(" ",array_unique(self::$attributes[$index]['class']));
			}
			if (array_key_exists('style', self::$attributes[$index])) {
				$attributes['class'] = implode(";",array_unique(self::$attributes[$index]['style']));
			}
		}
		return $attributes;
	
	}

	/**
	 *   aggiunge gli attributi all'html e fa il wrapping dell'html 
	 * */

	static function execute($var) {
		if (is_string($var)) {
			if (count(self::$attributes) > 0 && count(self::$wrap) == 0) {
				PinaAfterAttributes::wrap('<div>','</div>');
			} 
			foreach (self::$wrap as $k=> $wrap) {
				$add = "";
				if (isset($wrap[2]) && is_array($wrap[2])) { 
					foreach ($wrap[2] as  $kw=>$vw) { 
						PinaAfterAttributes::add_attributes($kw, $vw, $k);
					}
				}
				$adds = PinaAfterAttributes::get_attributes($k);
				
				if (is_array($adds)) {
					$temp_add = [];
					foreach ($adds as $add_k=>$add_v) {
						$temp_add[] = $add_k."=\"".addslashes($add_v)."\"";
					}
					$add = implode(" ", $temp_add);
				}
				if ($add != "") {
						$wrap[0] = str_replace(">", " ".$add.">", $wrap[0]);
				}
				
				$var =  $wrap[0].$var.$wrap[1];
			}
		}
		self::reset();
		return $var;
	}

}

/**
 * [% class=]
 */

if (!function_exists('pinacode_attr_fn_class_all')) {
	function pinacode_attr_fn_class_all($gvalue, $param, $shortcode_obj) {
		if (is_array($param)) {
			$param= implode(" ", $param);
		}
		$param =  pina_remove_quotes($param);
		PinaAfterAttributes::add_attributes("class", $param);
		return $gvalue;
	}
}
pinacode_set_attribute('class', 'pinacode_attr_fn_class');

/**
 * [% style=]
 */

if (!function_exists('pinacode_attr_fn_style_all')) {
	function pinacode_attr_fn_style_all($gvalue, $param, $shortcode_obj) {
		if (is_array($param)) {
			$param= implode(" ", $param);
		}
		$param =  pina_remove_quotes($param);
		PinaAfterAttributes::add_attributes("style", $param);
		return $gvalue;
	}
}
pinacode_set_attribute('style', 'pinacode_attr_fn_style');


/**
 * [% attr=]
 */

if (!function_exists('pinacode_attr_fn_attr_all')) {
	function pinacode_attr_fn_attr_all($gvalue, $param, $shortcode_obj) {
		$param = pina_execute_attribute_param($param);
		
		if (is_array($param) || is_object($param)) {
			$param = (array)$param;
			foreach ($param as $k=>$v) {
				if (is_array($v)) {
					$v= implode(" ", $v);
				}
				PinaAfterAttributes::add_attributes($k, $v);
			}
		} else {
			// ??
			$param =  pina_remove_quotes($param);
			PinaAfterAttributes::add_attributes("attr", $param);
		}
		
		return $gvalue;
	}
}
pinacode_set_attribute('attr', 'pinacode_attr_fn_attr');