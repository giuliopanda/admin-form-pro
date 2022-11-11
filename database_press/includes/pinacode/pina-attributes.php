<?php
/**
 * PinaAttributes
 * Gestione degli attributi di pinacode
 * Qui vengono caricati tutti gli attributi standard usabili negli shortcode
 */
namespace DbPress;

class PinaAttributes
{
	private static $attributes 	= [];
	/**
	 * Memorizza una nuova funzione di pinacode.	
	 * @param   String action_name
	 * @param   String function_name
	 * @return  	void
	**/
	static function set($attribute_name, $function_name) { 
		if(self::$attributes == null) {   
			self::$attributes = [];
		}
		$function_name_all = $function_name."_all";
		$array_ris = [];
		
		if (function_exists($function_name)) {	
			$array_ris['function'] =  $function_name;
		} else if (function_exists(__NAMESPACE__ . '\\'.$function_name)) {
			$array_ris['function'] =  __NAMESPACE__ . '\\'.$function_name;
		} else {
			$array_ris['function'] = false;
		}
		if (function_exists($function_name_all)) {	
			$array_ris['all'] =  $function_name_all;
		} else if (function_exists(__NAMESPACE__ . '\\'.$function_name_all)) {
			$array_ris['all'] =  __NAMESPACE__ . '\\'.$function_name_all;
		} else {
			$array_ris['all'] = false;
		}
		if (count ($array_ris) > 0) {
			self::$attributes[trim(strtolower($attribute_name))] = $array_ris;
		}

	}
	/**
	 * @return String
	 */
	static function execute($gvalue, $shortcode_obj) {
		if (isset($shortcode_obj['attributes'])) {
			//$inizio = microtime(true);
			foreach ($shortcode_obj['attributes'] as $cmd=>$param) {
				$cmd = trim(strtolower($cmd));
				
				//if (is_string($param) && ((substr($param,0,1) == "[" && substr($param,1,1) != ":") || strpos($param, " ")) ) {
				//	print "PARAM : ".$param." = ";
					$param =  pina_remove_quotes($param);
				//	print $param." |";
					
					
				//} 
				if (is_string($param) && (strpos($param, "[") !== false || (isset($param[0]) && $param[0] == "{"))) {
					$origin_value = PinaCode::get_var('item');
					$origin_key = PinaCode::get_var('key');
				}
				$origin_param = $param;
				/* var_dump (self::$attributes);
				die;
				*/
				if (array_key_exists($cmd, self::$attributes)) {
					$function_name = self::$attributes[$cmd]['function'];
					$function_name_all = self::$attributes[$cmd]['all'];
			
					$shortcode_obj['current_attr'] = $cmd;
					if ((is_array($gvalue) || is_object($gvalue))) {
						$gvalue = (array)$gvalue;
						$count_for = 0;
						$array_block = [];
						if ($function_name) {
							foreach ($gvalue as $key=>$val) {
								if (strpos($origin_param, "[") !== false || (isset($origin_param[0]) && $origin_param[0] == "{")) {
									PinaCode::set_var('item', $val);
									PinaCode::set_var('key', $key);
									$param = pina_execute_attribute_param($origin_param);
									// $param = PinaCode::get_registry()->short_code($origin_param); non elabora i json								
								}
								//print ("<p>PARAM: ".$param."</p>");
								
								$array_block[$key] = call_user_func_array($function_name, array($val, $param, $shortcode_obj, $count_for, count($gvalue)));
								if (strpos($origin_param, "[") !== false || (isset($origin_param[0]) && $origin_param[0] == "{")) {
									PinaCode::set_var('item', $origin_value);	
									PinaCode::set_var('key', $origin_key);	
								}
								$count_for++;
								if ($count_for > 10000) break;
							} 
						} else {
							$array_block = $gvalue;
						}
						if ($function_name_all) {
							//print ("<p>PARAM: ".$param."</p>");
							$gvalue = call_user_func_array($function_name_all, array($array_block, $param, $shortcode_obj));	
						} else {
							$gvalue = $array_block;	
						}
					} else {
						//$inizio2 = microtime(true);
						if (is_string($param) && (strpos($param, "[") !== false  || (isset($param[0]) && $param[0] == "{"))) {
							PinaCode::set_var('item', $gvalue);
							PinaCode::set_var('key', 0);
							$param = pina_execute_attribute_param($param);
						}
						if ($function_name) {
							$gvalue = call_user_func_array($function_name, array($gvalue, $param, $shortcode_obj));
						}
						if ($function_name_all) {
							$gvalue = call_user_func_array($function_name_all, array($gvalue, $param, $shortcode_obj));	
						}
						if (is_string($param) && (strpos($param, "[") !== false || (isset($param[0]) && $param[0] == "{"))) {
							PinaCode::set_var('item', $origin_value);	
							PinaCode::set_var('key', $origin_key);	
						}
						
					}
					
				} elseif (substr($cmd,0,1) == ".") {
					$origin_param = $param;
					if (!is_array($origin_param) ) {
						$origin_param = [$origin_param];
					}
					
					foreach ($origin_param as $sigle_param) {
						if (is_array($gvalue) || is_object($gvalue)) {
							$gvalue = (array)$gvalue;
							// è un array di array/oggetti o è un array di un solo livello?
							$lev = 1;
							foreach ($gvalue as $val) {
								if (is_array($val) || is_object($val)) {
									$lev = 2;
									break;
								}
							}
							if ($lev == 1) {
								PinaCode::set_var('item', $gvalue);
								PinaCode::set_var('key', 0);
								$sigle_param = pina_execute_attribute_param($sigle_param);
								if (strlen($cmd) == 1) {
									PinaCode::set_var($shortcode_obj['shortcode'].".[]",  pina_remove_quotes($sigle_param));
									$gvalue =  PinaCode::get_var($shortcode_obj['shortcode']);
								} else {
									PinaCode::set_var($shortcode_obj['shortcode'].$cmd,  pina_remove_quotes($sigle_param));
									$gvalue =  PinaCode::get_var($shortcode_obj['shortcode']);
								}
								if (strpos($param, "[") !== false || (isset($param[0]) && $param[0] == "{")) {
									PinaCode::set_var('item', $origin_value);	
									PinaCode::set_var('key', $origin_key);
								}
							} else {
								foreach ($gvalue as $key=>$item) {
									PinaCode::set_var('item', $item);
									PinaCode::set_var('key', $key);
									$sigle_new_param = pina_execute_attribute_param($sigle_param);
									if (is_array($item)) {
										if (strlen($cmd) == 1) {
											PinaCode::set_var($shortcode_obj['shortcode'].".".$key.".[]",  pina_remove_quotes($sigle_new_param));
										} else {
											PinaCode::set_var($shortcode_obj['shortcode'].".".$key.$cmd,  pina_remove_quotes($sigle_new_param));
										}
									}
								}
								$gvalue =  PinaCode::get_var($shortcode_obj['shortcode']);
								if (strpos($param, "[") !== false || (isset($param[0]) && $param[0] == "{")) {
									PinaCode::set_var('item', $origin_value);	
									PinaCode::set_var('key', $origin_key);
								}
							}
						} else {
							PinaCode::set_var($shortcode_obj['shortcode'], [$gvalue]);
							PinaCode::set_var('item', $gvalue);
							PinaCode::set_var('key', 0);
							$sigle_param = pina_execute_attribute_param($sigle_param);
							if (strlen($cmd) == 1) {
								PinaCode::set_var($shortcode_obj['shortcode'].".[]",  pina_remove_quotes($sigle_param));
								$gvalue =  PinaCode::get_var($shortcode_obj['shortcode']);
							} else {
								PinaCode::set_var($shortcode_obj['shortcode'].$cmd,  pina_remove_quotes($sigle_param));
								$gvalue =  PinaCode::get_var($shortcode_obj['shortcode']);
							}
							PinaCode::set_var('item', $origin_value);	
							PinaCode::set_var('key', $origin_key);	
						}
					}
				}
			}
			//print "<p>attributes tot:".round((microtime(true) - $inizio), 6)."</p>";
		} 
		return $gvalue;
	}
}

/**
* Interfaccia in stile wp per aggiungere una funzione a pinacode.
* @param String $action_name
* @param String $function_name
*/
function pinacode_set_attribute($attributes_name, $function_name) {
	if (is_array($attributes_name)) {
		foreach ($attributes_name as $at) {
			PinaAttributes::set($at, $function_name);
		}
	} else {
		PinaAttributes::set($attributes_name, $function_name);
	}
}

/**
 * [% strtoupper|uppercase|upper]
 */
if (!function_exists('pinacode_attr_fn_upper')) {
	function pinacode_attr_fn_upper($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = strtoupper($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['upper','strtoupper','uppercase'], 'pinacode_attr_fn_upper');

/**
 * [% strtolower|lowercase|lower]
 */
if (!function_exists('pinacode_attr_fn_lower')) {
	function pinacode_attr_fn_lower($gvalue, $param, $shortcode_obj) {
		
		if (is_string($gvalue)) {
			$gvalue = strtolower($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['lower','strtolower','lowercase'], 'pinacode_attr_fn_lower');

/**
 * [% ucfirst|capitalize]
 */
if (!function_exists('pinacode_attr_fn_ucfirst')) {
	function pinacode_attr_fn_ucfirst($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = ucfirst($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['ucfirst','capitalize'], 'pinacode_attr_fn_ucfirst');

/**
 * [% get|show|fields=string|array]
 */
$get_executed = false;
if (!function_exists('pinacode_attr_fn_get')) {
	function pinacode_attr_fn_get($gvalue, $param, $shortcode_obj) {
		global $get_executed;
		if (is_string($param)) {
			if (is_array($gvalue) && array_key_exists($param, $gvalue)) {	
				$get_executed = true;
				$gvalue = $gvalue[$param];
			} else if (is_array($gvalue)) {
				$ris = [];
				foreach ($gvalue as $k => $gv) {
					if (is_array($gv) && array_key_exists($param, $gv)) {
						$get_executed = true;
						$ris[] = $gv[$param];
					} else if (is_object($gv) && property_exists($gv, $param)) {
						$get_executed = true;
						$ris[] = $gv->$param;
					}
				}
				if (count($ris) == 0) {
					PcErrors::set('GET return null value <b>'.$param."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
				}
				return $ris;
			} else if (is_object($gvalue) && property_exists($gvalue, $param)) {
				$get_executed = true;
				$gvalue = $gvalue->$param;
			} 
		} else if (is_array($param) || is_object($param)) {
			if (is_object($param)) {
				$param = (array)$param;
			}
			$ris = [];
			foreach ($param as $custom_key=>$prm) {
				if (is_array($gvalue) && array_key_exists($prm, $gvalue)) {
					if (!is_numeric($custom_key)) {
						$ris[$custom_key] = $gvalue[$prm];
					} else {
						$ris[$prm] = $gvalue[$prm];
					}
					$get_executed = true;
				} else if (is_object($gvalue) && property_exists($gvalue, $prm)) {
					if (!is_numeric($custom_key)) {
						$ris[$custom_key] = $gvalue->$prm;
					} else {
						$ris[$prm] = $gvalue->$prm;
					}
					$get_executed = true;
				} else if (is_array($gvalue) || is_object($gvalue)) {
					$gvalue = (array)$gvalue;
					foreach ($gvalue as $k => $gv) {
						if (is_numeric($custom_key)) {
							$new_prm = $prm;
						} else {
							$new_prm = $custom_key;
						}
						//print ($custom_key);
					
						if (is_array($gv) && array_key_exists($prm, $gv)) {
							if (!isset($ris[$k])) {
								$ris[$k] = [];
							}
							$ris[$k][$new_prm] = $gv[$prm];
						} else if (is_object($gv) && property_exists($gv, $prm)) {
							if (!isset($ris[$k])) {
								$ris[$k] = [];
							}
							$ris[$k][$new_prm] = $gv->$prm;
						}
					}
					$get_executed = true;
				} 
			}
			if (count($ris) == 0) {
				$param = implode(", ",$param);
				PcErrors::set('GET return null value <b>'.substr($shortcode_obj['shortcode'],0, 15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			return $ris;
		}
		return $gvalue;
	}
	
	function pinacode_attr_fn_get_all($gvalue, $param, $shortcode_obj) {
		global $get_executed;
		
		if (!$get_executed) {
		
			// Se non è stato filtrato nulla verifico se l'array è ad una sola dimensione e nel caso la eseguo
			if (is_array($gvalue)) {
				foreach ($gvalue as $v) {
					if (is_array($v) || is_object($v)) {
						return $gvalue;
					}
					if (is_string($param)) {
						if (array_key_exists($param, $gvalue)) {
							return $gvalue[$param];
						}
					} else if (is_array($param)) {
						$result = [];
						foreach ($param as $pm) {
							if (array_key_exists($pm, $gvalue)) {
								$result[$pm] =  $gvalue[$pm];
							}
						}
						return $result;
					}
					// TODO
				}
			}
		
		}
		$get_executed = false;
		return $gvalue;
	}
	
}
pinacode_set_attribute(['get','show','fields'], 'pinacode_attr_fn_get');

/**
 * [% strip-comment|strip_comment|stripcomment]
 */
if (!function_exists('pinacode_attr_fn_stripcomment')) {
	function pinacode_attr_fn_stripcomment($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = preg_replace('/<!--(.*)-->/Uis', '', $gvalue);
			$gvalue = preg_replace('~//?\s*\*[\s\S]*?\*\s*//?~m', '', $gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['strip-comment','strip_comment','stripcomment'], 'pinacode_attr_fn_stripcomment');

/**
 * [% htmlentities]
 */
if (!function_exists('pinacode_attr_fn_htmlentities')) {
	function pinacode_attr_fn_htmlentities($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = htmlentities($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('htmlentities', 'pinacode_attr_fn_htmlentities');

/**
 * [% strip-tags|strip_tags|striptags]
 */
if (!function_exists('pinacode_attr_fn_striptags')) {
	function pinacode_attr_fn_striptags($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = strip_tags($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['strip-tags','strip_tags','striptags'], 'pinacode_attr_fn_striptags');

/**
 * [% nl2br]
 */
if (!function_exists('pinacode_attr_fn_nl2br')) {
	function pinacode_attr_fn_nl2br($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = nl2br($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('nl2br', 'pinacode_attr_fn_nl2br');

/**
 * [% left=number]
 */
if (!function_exists('pinacode_attr_fn_left')) {
	function pinacode_attr_fn_left($gvalue, $param, $shortcode_obj) {
		if ((is_string($gvalue) || is_numeric($gvalue) ) && (int)$param > 0) {
			if (strlen($gvalue) > (int)$param) {
				$gvalue = substr($gvalue, 0, (int)$param);
				if (array_key_exists('more', $shortcode_obj['attributes'])) {
					$gvalue = $gvalue.$shortcode_obj['attributes']['more'];
				}
			}
		}
		return $gvalue;
	}
}
pinacode_set_attribute('left', 'pinacode_attr_fn_left');

/**
 * [% right=number]
 */
if (!function_exists('pinacode_attr_fn_right')) {
	function pinacode_attr_fn_right($gvalue, $param, $shortcode_obj) {
		if ((is_string($gvalue) || is_numeric($gvalue) ) && (int)$param > 0) {
			$gvalue = substr($gvalue, strlen($gvalue)  - (int)$param, strlen($gvalue));
		}
		return $gvalue;
	}
}
pinacode_set_attribute('right', 'pinacode_attr_fn_right');

/**
 * [% trim-words|trim_words|trimwords=number]
 */
if (!function_exists('pinacode_attr_fn_trimwords')) {
	function pinacode_attr_fn_trimwords($gvalue, $param, $shortcode_obj) {
		if ($param == 0) $param = 30;
		if ((is_string($gvalue) || is_numeric($gvalue)) && (int)$param > 0) {
			$more = "";
			if (array_key_exists('more', $shortcode_obj['attributes'])) {
				$more = $shortcode_obj['attributes']['more'];
			}
			$gvalue = wp_trim_words($gvalue, $param, $more);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['trim-words','trim_words','trimwords'], 'pinacode_attr_fn_trimwords');


/**
 * [% sanitize]
 */
if (!function_exists('pinacode_attr_fn_sanitize')) {
	function pinacode_attr_fn_sanitize($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = sanitize_text_field($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('sanitize', 'pinacode_attr_fn_sanitize');

/**
 * [% esc_url]
 */
if (!function_exists('pinacode_attr_fn_esc_url')) {
	function pinacode_attr_fn_esc_url($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = esc_url($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('esc_url', 'pinacode_attr_fn_esc_url');

/**
 * [% sep=]
 */
if (!function_exists('pinacode_attr_fn_sep')) {
	function pinacode_attr_fn_sep($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue =implode ($param, $gvalue);
		}
		return $gvalue;
	}
	function pinacode_attr_fn_sep_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue =implode ($param, $gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('sep', 'pinacode_attr_fn_sep');

/**
 * [% qsep]
 */
if (!function_exists('pinacode_attr_fn_qsep')) {
	function pinacode_attr_fn_qsep($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue = array_filter($gvalue);
			foreach ($gvalue as &$gv) {
				$gv = "'".str_replace("'","\\'", str_replace("\\'", "'", $gv))."'";
			}
			$gvalue = implode ($param, $gvalue);
		}
		return $gvalue;
	}
	function pinacode_attr_fn_qsep_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue = array_filter($gvalue);
			foreach ($gvalue as &$gv) {
				$gv = "'".str_replace("'","\\'", str_replace("\\'", "'", $gv))."'";
			}
			$gvalue = implode ($param, $gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('qsep', 'pinacode_attr_fn_qsep');

/**
 * [% trim]
 */
if (!function_exists('pinacode_attr_fn_esc_trim')) {
	function pinacode_attr_fn_esc_trim($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue) || is_numeric($gvalue) ) {
			$gvalue = trim($gvalue);
			$k = 0;
			while ($gvalue != trim($gvalue) && $k < 100) {
				$gvalue = trim($gvalue);
				$k++;
			}
		} else if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue = array_filter($gvalue);
			foreach ($gvalue as &$gv) {
				if (is_string ($gv)) {
					$gv = trim($gv);
				}
			}
		}
		return $gvalue;
	}
}
pinacode_set_attribute('trim', 'pinacode_attr_fn_esc_trim');

/**
 * [% date-format]
 */
if (!function_exists('pinacode_attr_fn_dateformat')) {
	function pinacode_attr_fn_dateformat($gvalue, $param, $shortcode_obj) {
		$date = pina_get_date_to_string($gvalue);
	
		if ($date !== false && is_string ($param)) {
			try {
				$gvalue = $date->format($param);
			} catch (\Exception $e) {
				$errorFormat = (is_string($param)) ? $param : "";
				PcErrors::set('DATE-FORMAT not valid format: <b>'.$param."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
		} else if (!is_string ($param)) {
			PcErrors::set('DATE-FORMAT format must be a string, object given!. <b>'.substr($shortcode_obj['shortcode'],0, 30)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		} else if ($date === false) {
			PcErrors::set('DATE error: <b>'.$gvalue."</b> is not a valid date.", '', -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('date-format', 'pinacode_attr_fn_dateformat');

/**
 * [% date-modify]
 */
if (!function_exists('pinacode_attr_fn_datemodify')) {
	function pinacode_attr_fn_datemodify($gvalue, $param, $shortcode_obj) {
		$date = pina_get_date_to_string($gvalue);
		if ($date !== false  && is_string($param)) {
			try {
				$date->modify($param);
			} catch (\Exception $e) {
				PcErrors::set('DATE-MODIFY param is not correct <b>'.$param."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			if (strlen($gvalue) < 11) {
				$gvalue = $date->format('Y-m-d');
			} else if (strlen($gvalue) == 13) {
				$gvalue = $date->format('Y-m-d H');
			} else if (strlen($gvalue) == 16) {
				$gvalue = $date->format('Y-m-d H:i');
			} else {
				$gvalue = $date->format('Y-m-d H:i:s');
			}
		} else  if (!is_string ($param)) {
			PcErrors::set('DATE-MODIFY error: param must be a string <b>'.substr($shortcode_obj['shortcode'],0, 30)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('date-modify', 'pinacode_attr_fn_datemodify');



/**
 * [% last-day]
 */
if (!function_exists('pinacode_attr_fn_lastday')) {
	function pinacode_attr_fn_lastday($gvalue, $param, $shortcode_obj) {
		$date = pina_get_date_to_string($gvalue);
		if ($date !== false) {
			if (strlen($gvalue) < 11) {
				$gvalue = $date->format('Y-m-t');
			} else if (strlen($gvalue) == 13) {
				$gvalue = $date->format('Y-m-t H');
			} else if (strlen($gvalue) == 16) {
				$gvalue = $date->format('Y-m-t H:i');
			} else {
				$gvalue = $date->format('Y-m-t H:i:s');
			}
		}
		return $gvalue;
	}
}
pinacode_set_attribute('last-day', 'pinacode_attr_fn_lastday');


/**
 * Ritorna 1 o '' se è una data, oppure se è settato un parametro ritorna il parametro se è una data valida
 * [% if-is-date]
 */
if (!function_exists('pinacode_attr_fn_if_is_date_all')) {

	function pinacode_attr_fn_if_is_date_all($gvalue, $param, $shortcode_obj) {
		$date = "";
		if (is_string($gvalue) ) {
			try {
				$date = new \DateTime($gvalue, wp_timezone());
			} catch (\Exception $e) {
				
			}
		
			if (strlen($gvalue) < 11 && is_numeric($gvalue)) {
				try {
					$date = (new \DateTime('now',  wp_timezone()))->setTimestamp($gvalue);
				} catch (\Exception $e) {
				
				}
			}
		}
		if (is_object($date)) {	
			if ($param != "") {
				return $param;
			} else {
				return 1;
			}
			
		} else {
			return '';
		}
	}
}
pinacode_set_attribute('if-is-date', 'pinacode_attr_fn_if_is_date');

/**
 * Ritorna 1 o '' se è una stringa, oppure se è settato un parametro ritorna il parametro se è una data valida
 * [% if-is-string]
 */
if (!function_exists('pinacode_attr_fn_if_is_string_all')) {

	function pinacode_attr_fn_if_is_string_all($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {	
			if ($param != "") {
				return $param;
			} else {
				return 1;
			}	
		} else {
			return '';
		}
	}
}
pinacode_set_attribute('if-is-string', 'pinacode_attr_fn_if_is_string');

/**
 * [% one|singolar=]
 */
if (!function_exists('pinacode_attr_fn_singular_all')) {
	function pinacode_attr_fn_singular_all($gvalue, $param, $shortcode_obj) {
		if (is_countable($gvalue)) {
			if (count($gvalue) == 1 && $param != "") {
				return $param;
			}
		} else if (floatval($gvalue) == 1 && $param != "") {	
			return $param;
		} 
		return $gvalue ;
		
	}
}
pinacode_set_attribute(['one','singular'], 'pinacode_attr_fn_singular');


/**
 * [% plural=]
 */
if (!function_exists('pinacode_attr_fn_plural_all')) {
	function pinacode_attr_fn_plural_all($gvalue, $param, $shortcode_obj) {
		if (is_countable($gvalue)) {
			if (count($gvalue) > 1 && $param != "") {
				return $param;
			}
		} else if (floatval($gvalue) > 1 && $param != "") {	
			return $param;
		} 
		return $gvalue ;
	}
}
pinacode_set_attribute('plural', 'pinacode_attr_fn_plural');
/**
 * [% zero=]
 */
if (!function_exists('pinacode_attr_fn_zero_all')) {
	function pinacode_attr_fn_zero_all($gvalue, $param, $shortcode_obj) {
		if ((is_string($gvalue) && ($gvalue == 0 || $gvalue == "" || $gvalue == false ||  $gvalue == NULL)) || (is_array($gvalue) && count($gvalue)== 0)) {	
			if ($param != "") {
				return $param;
			} 
		} 
		return $gvalue ;
	}
}
pinacode_set_attribute(['zero','empty'], 'pinacode_attr_fn_zero');

/**
 * [% negative=]
 */
if (!function_exists('pinacode_attr_fn_negative_all')) {
	function pinacode_attr_fn_negative_all($gvalue, $param, $shortcode_obj) {
		if (!is_object($gvalue) && !is_array($gvalue) && $param != "" && floatval($gvalue) < 0) {	
			return $param;
		} 
		return $gvalue ;
	}
}
pinacode_set_attribute('negative', 'pinacode_attr_fn_negative');


/**
 * [% datediff-minute=]
 */
if (!function_exists('pinacode_attr_fn_datediff_minute')) {
	function pinacode_attr_fn_datediff_minute($gvalue, $param, $shortcode_obj) {
		$gvalue = pina_get_date_to_string($gvalue);
		$new_date = pina_get_date_to_string($param);
		if ($gvalue !== false && $new_date !== false) {
			$timestamp = $gvalue->getTimestamp() - $new_date->getTimestamp();
			return number_format(($timestamp / (60)),0);
		} else {
			PcErrors::set('DATEDIFF error: one date is not valid', substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return 0;
	}
	
}
pinacode_set_attribute('datediff-minute', 'pinacode_attr_fn_datediff_minute');

/**
 * [% datediff-hour=]
 */
if (!function_exists('pinacode_attr_fn_datediff_hour')) {
	function pinacode_attr_fn_datediff_hour($gvalue, $param, $shortcode_obj) {
		$gvalue = pina_get_date_to_string($gvalue);
		$new_date = pina_get_date_to_string($param);
		if ($gvalue !== false && $new_date !== false) {
			$timestamp = $gvalue->getTimestamp() - $new_date->getTimestamp();
			return number_format(($timestamp / (60*60)),0);
		} else {
			PcErrors::set('DATEDIFF error: one date is not valid', substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return 0;
	}
	
}
pinacode_set_attribute('datediff-hour', 'pinacode_attr_fn_datediff_hour');

/**
 * [% datediff-day=]
 */
if (!function_exists('pinacode_attr_fn_datediff_day')) {
	function pinacode_attr_fn_datediff_day($gvalue, $param, $shortcode_obj) {
		$gvalue = pina_get_date_to_string($gvalue);
		$new_date = pina_get_date_to_string($param);
		if ($gvalue !== false && $new_date !== false) {
			$timestamp = $gvalue->getTimestamp() - $new_date->getTimestamp();
			return number_format(($timestamp / (24*60*60)),0);
		} else {
			PcErrors::set('DATEDIFF error: one date is not valid', substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return 0;
	}
	
}
pinacode_set_attribute('datediff-day', 'pinacode_attr_fn_datediff_day');

/**
* [% datediff-month=]
 */
if (!function_exists('pinacode_attr_fn_datediff_month')) {
	function pinacode_attr_fn_datediff_month($gvalue, $param, $shortcode_obj) {
		$gvalue = pina_get_date_to_string($gvalue);
		$new_date = pina_get_date_to_string($param);
		if ($gvalue !== false && $new_date !== false) {
			$diff  = $gvalue->diff($new_date);
			return $diff->format('%y') * 12 + $diff->format('%m');
		} else {
			PcErrors::set('DATEDIFF error: one date is not valid', substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return 0;
	}
	
}
pinacode_set_attribute('datediff-month', 'pinacode_attr_fn_datediff_month');

/**
* [% datediff-year=]
 */
if (!function_exists('pinacode_attr_fn_datediff_year')) {
	function pinacode_attr_fn_datediff_year($gvalue, $param, $shortcode_obj) {
		$gvalue = pina_get_date_to_string($gvalue);
		$new_date = pina_get_date_to_string($param);
		if ($gvalue !== false && $new_date !== false) {
			$diff  = $gvalue->diff($new_date);
			return $diff->format('%y');
		} else {
			PcErrors::set('DATEDIFF error: one date is not valid', substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return 0;
	}
	
}
pinacode_set_attribute('datediff-year', 'pinacode_attr_fn_datediff_year');


/**
 * Ritorna 1 o 0 se è un oggetto, oppure se è settato un parametro ritorna il parametro se è una data valida
 * [% if-is-object]
 */
if (!function_exists('pinacode_attr_fn_if_is_object')) {
	function pinacode_attr_fn_if_is_object($gvalue, $param, $shortcode_obj) {
		return $gvalue;
	}
	function pinacode_attr_fn_if_is_object_all($gvalue, $param, $shortcode_obj) {
		if ((is_array($gvalue) || is_object($gvalue)) && count($gvalue) > 0) {	
			if ($param != "") {
				return $param;
			} else {
				return 1;
			}	
		} else {
			return '';
		}
	}
}
pinacode_set_attribute('if-is-object', 'pinacode_attr_fn_if_is_object');


/**
 * non stampa nulla
 * [% no-print]
 */
if (!function_exists('pinacode_attr_fn_no_print')) {
	function pinacode_attr_fn_no_print($gvalue, $param, $shortcode_obj) {
		return $gvalue;
	}
	function pinacode_attr_fn_no_print_all($gvalue, $param, $shortcode_obj) {
		return '';
	}
}
pinacode_set_attribute('no-print', 'pinacode_attr_fn_no_print');

/**
 * [% timestamp]
 */
if (!function_exists('pinacode_attr_fn_timestamp')) {
	function pinacode_attr_fn_timestamp($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			try {
				$date = new \DateTime($gvalue);
			} catch (\Exception $e) {
				$date = false;
				PcErrors::set('TIMESTAMP error: <b>'.$gvalue."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			} 
			if (is_object($date)) {
				$gvalue = @$date->getTimestamp();
			}
		}
		return $gvalue;
	}
}
pinacode_set_attribute('timestamp', 'pinacode_attr_fn_timestamp');

/**
 * [% euro]
 */
if (!function_exists('pinacode_attr_fn_euro')) {
	function pinacode_attr_fn_euro($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue) || is_numeric($gvalue)) {
			$gvalue = str_replace(",", ".", $gvalue);
			try {
				$gvalue = number_format($gvalue, 2, ",", ".");
			} catch (\Exception $e) {
				PcErrors::set('EURO attribute works with Number. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
		}
		return $gvalue;
	}
}
pinacode_set_attribute('euro', 'pinacode_attr_fn_euro');

/**
 * [% floor]
 */
if (!function_exists('pinacode_attr_fn_floor')) {
	function pinacode_attr_fn_floor($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue) || is_numeric($gvalue) ) {
			$gvalue = str_replace(",", ".", $gvalue);
			$gvalue = @floor($gvalue);
		} else {
			PcErrors::set('FLOOR attribute works with Number. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('floor', 'pinacode_attr_fn_floor');

/**
 * [% round]
 */
if (!function_exists('pinacode_attr_fn_round')) {
	function pinacode_attr_fn_round($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue) || is_numeric($gvalue) ) {
			$gvalue = str_replace(",", ".", $gvalue);
			$gvalue = @round($gvalue);
		} else {
			PcErrors::set('ROUND attribute works with Number. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('round', 'pinacode_attr_fn_round');

/**
 * [% ceil]
 */
if (!function_exists('pinacode_attr_fn_ceil')) {
	function pinacode_attr_fn_ceil($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue) || is_numeric($gvalue) ) {
			$gvalue = str_replace(",", ".", $gvalue);
			$gvalue = @ceil($gvalue);
		} else {
			PcErrors::set('CEIL attribute works with String. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('ceil', 'pinacode_attr_fn_ceil');


/**
 * [% abs]
 */
if (!function_exists('pinacode_attr_fn_abs')) {
	function pinacode_attr_fn_abs($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue) || is_numeric($gvalue) ) {
			$gvalue = str_replace(",", ".", $gvalue);
			$gvalue = @abs($gvalue);
		} else {
			PcErrors::set('ABS attribute works with String. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('abs', 'pinacode_attr_fn_abs');


/**
 * [% sum]
 */
if (!function_exists('pinacode_attr_fn_vectorsum')) {
	/*
	function pinacode_attr_fn_vectorsum($gvalue, $param, $shortcode_obj) {
		return $gvalue;
	}
	*/
	function pinacode_attr_fn_vectorsum_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$sum = 0;
			foreach ($gvalue as $gv) {
				if (is_string($gv) || is_numeric($gv) ) {
					$sum = $sum + $gv;
				}
			}
			$gvalue = $sum ;
		} else {
			PcErrors::set('SUM attribute works with Object. String given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('sum', 'pinacode_attr_fn_vectorsum');


/**
 * [% length|count]
 */
if (!function_exists('pinacode_attr_fn_length')) {
	function pinacode_attr_fn_length($gvalue, $param, $shortcode_obj) {
		return $gvalue;
	}
	function pinacode_attr_fn_length_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$count = 0;
			foreach ($gvalue as $gv) {
				//if (is_string($gv) || is_numeric($gv) ) {
					$count++;
				//}
			}
			$gvalue = $count;
		} else if (is_string($gvalue) || is_numeric($gvalue)) {
			$gvalue = strlen($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute(['length','count'], 'pinacode_attr_fn_length');


/**
 * [% decimal]
 */
if (!function_exists('pinacode_attr_fn_decimal')) {
	function pinacode_attr_fn_decimal($gvalue, $param, $shortcode_obj) {
		if ($param == "") $param = 2;
		if (is_string($gvalue) || is_numeric($gvalue)) {
			if (array_key_exists('dec_point', $shortcode_obj['attributes'])) {
				$dec_point = $shortcode_obj['attributes']['dec_point'];
			} else if (array_key_exists('dec-point', $shortcode_obj['attributes'])) {
				$dec_point = $shortcode_obj['attributes']['dec-point'];
			} else {
				$dec_point = ".";
			}
			if (array_key_exists('thousands_sep', $shortcode_obj['attributes'])) {
				$thousands_sep = $shortcode_obj['attributes']['thousands_sep'];
			} else if (array_key_exists('thousands-sep', $shortcode_obj['attributes'])) {
				$thousands_sep = $shortcode_obj['attributes']['thousands-sep'];
			} else {
				$thousands_sep = "";
			}
			$gvalue = str_replace(",", ".", $gvalue);
			try {
				$gvalue = number_format($gvalue, $param, $dec_point, $thousands_sep);
			} catch (\Exception $e) {
				PcErrors::set('DECIMAL error format '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			
		} else {
			PcErrors::set('DECIMAL attribute works with Number. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('decimal', 'pinacode_attr_fn_decimal');


/**
 * [% vector-mean]
 */
if (!function_exists('pinacode_attr_fn_vectormean')) {
	function pinacode_attr_fn_vectormean($gvalue, $param, $shortcode_obj) {
		return $gvalue;
	}
	function pinacode_attr_fn_vectormean_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$sum = 0;
			$count = 0;
			foreach ($gvalue as $gv) {
				if (is_string($gv) || is_numeric($gv) ) {
					$sum = $sum + $gv;
					$count++;
				}
			}
			if ($sum > 0 && $count > 0) {
				$gvalue = $sum / $count;
			}
		} else {
			PcErrors::set('MEAN attribute works with Object. String given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('mean', 'pinacode_attr_fn_vectormean');

/**
 * [% search= replace=]
 */
if (!function_exists('pinacode_attr_fn_search')) {
	function pinacode_attr_fn_search($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			if (array_key_exists('replace', $shortcode_obj['attributes']) && is_string($shortcode_obj['attributes']['replace'])) {
			
				$replace = pina_remove_quotes($shortcode_obj['attributes']['replace']);
				$gvalue = str_ireplace($param, $replace, $gvalue);
			} else {
				$gvalue = (stripos($gvalue, $param) !== false) ? 1 : 0;
			}
		} else {
			PcErrors::set('SEARCH attribute works with string. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('search', 'pinacode_attr_fn_search');

/**
 * [% set+=]
 */
if (!function_exists('pinacode_attr_fn_setp')) {
	function pinacode_attr_fn_setp($gvalue, $param, $shortcode_obj) {
		if  (!is_numeric ($gvalue)) {
			if (is_string($gvalue)) {
				PcErrors::set('SET+= attribute works with number. String given <b>'.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			if (is_array($gvalue) || is_object($gvalue)) {
				PcErrors::set('SET+= attribute works with number. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			$gvalue=0;
		}
		if (is_numeric($param) ) {
			pinaCode::set_var($shortcode_obj['shortcode'],  $gvalue + $param);
			$gvalue =  $gvalue + $param;
		}
		return $gvalue;
	}
}
pinacode_set_attribute('set+', 'pinacode_attr_fn_setp');

/**
 * [% set-=]
 */
if (!function_exists('pinacode_attr_fn_setm')) {
	function pinacode_attr_fn_setm($gvalue, $param, $shortcode_obj) {
		if  (!is_numeric ($gvalue)) {
			if (is_string($gvalue)) {
				PcErrors::set('SET+= attribute works with number. String given <b>'.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			if (is_array($gvalue) || is_object($gvalue)) {
				PcErrors::set('SET+= attribute works with number. Object given '.substr($shortcode_obj['shortcode'],0,15)."</b>", substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
			}
			$gvalue=0;
		}
		if (is_numeric($param) ) {
			pinaCode::set_var($shortcode_obj['shortcode'],  $gvalue - $param);
			$gvalue =  $gvalue - $param;
		}
		return $gvalue;
	}
	
}
pinacode_set_attribute('set-', 'pinacode_attr_fn_setm');

/**
 * [% set=]
 */
if (!function_exists('pinacode_attr_fn_set')) {
	function pinacode_attr_fn_set($gvalue, $param, $shortcode_obj) {
		if (is_object($param) || is_array($param)) {
			pinaCode::set_var($shortcode_obj['shortcode'], $param);
			$gvalue = $param;
		} else {
			if ((substr($param,0,1) == "[" && substr($param,1,1) != "%") || substr($param,0,1) == "{") {
				$json = true;
				try {
					$jsonObj  = json_decode($param, true);
				} catch (\Exception $e) {
					$json = false;
				}
				if (!$json || $jsonObj == null || !is_array($jsonObj)) {
					if (json_last_error() != JSON_ERROR_NONE) {
						PcErrors::set('JSON PARSE - '.get_json_error_decode().' IN <b>'.substr($param,0,15)."</b>", substr($param,0, 30), -1, 'warning');
					}
				} else {
					$param = $jsonObj;
				}
			}
			pinaCode::set_var($shortcode_obj['shortcode'],  pina_remove_quotes($param));
			$gvalue =  pina_remove_quotes($param);
		}
		return $gvalue;
	}
	
}
pinacode_set_attribute('set', 'pinacode_attr_fn_set');


/**
 * [% print|tmpl|for=] //foreach
 * 
 */
if (!function_exists('pinacode_attr_fn_tmpl_all')) {
	function pinacode_attr_fn_tmpl_all($gvalue, $param, $shortcode_obj) {
		
		$temp_gvalue = $gvalue;
		if (is_string($param) && $param != NULL && strpos(" ",$param) === false && strpos("[", $param) === false) {
			$gvalue = apply_filters( 'pinacode_attribute_tmpl_'.$param, $gvalue, $param, $shortcode_obj);
			if ($gvalue != $temp_gvalue) {
				return $gvalue;
			}
		}
	
		$result = [];
		
		if (is_array($gvalue) || is_object($gvalue)) {
			$origin_value = PinaCode::get_var('item');
			$origin_key = PinaCode::get_var('key');
			foreach ($gvalue as $key=>$val) {
					PinaCode::set_var('item', $val);
					PinaCode::set_var('key', $key);
					$ris = pina_execute_attribute_param($param);
					if (is_array($ris) || is_object($ris)) {
						//$ris = (array)$ris;
						$ris = json_encode($ris, 2);
					}
					$result[] = $ris;
					// $param = PinaCode::get_registry()->short_code($origin_param); non elabora i json								
			}
			PinaCode::set_var('item', $origin_value);
			PinaCode::set_var('key', $origin_key);
		}
		return implode("\n", (array)$result);
	}
}
pinacode_set_attribute(['print','tmpl','for'], 'pinacode_attr_fn_tmpl');

/**
 * [% if=]
 */
if (!function_exists('pinacode_attr_fn_if')) {
	function pinacode_attr_fn_if($gvalue, $param, $shortcode_obj) {
		//print "<p>pinacode_attr_fn_if: |".$param."|</p>";
		$param = PinaCode::math_and_logic($param);
		//print "<p>".$param."</p>";
		if (!$param) {
			$gvalue = "_pinacode_attr_fn_if_to_del";
		}	
		return $gvalue;
	}
	function pinacode_attr_fn_if_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			foreach ($gvalue as $key=>$val) {
				if ($val === "_pinacode_attr_fn_if_to_del") {
					unset($gvalue[$key]);
				}
			} 
			$gvalue = array_values($gvalue);
		} else if ($gvalue === "_pinacode_attr_fn_if_to_del") {
			$gvalue = "";
		}
		return $gvalue;
	}
}

pinacode_set_attribute('if', 'pinacode_attr_fn_if');

/**
 * [% unserialize]
 */
if (!function_exists('pinacode_attr_fn_unserialize')) {
	function pinacode_attr_fn_unserialize($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = maybe_unserialize($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('unserialize', 'pinacode_attr_fn_unserialize');

/**
 * [% serialize]
 */
if (!function_exists('pinacode_attr_fn_serialize_all')) {
	function pinacode_attr_fn_serialize_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue = serialize($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('serialize', 'pinacode_attr_fn_serialize');

/**
 * [% split=]
 */
if (!function_exists('pinacode_attr_fn_split')) {
	function pinacode_attr_fn_split($gvalue, $param, $shortcode_obj) {
		if (is_object($param) || is_array($param)) {
			return $gvalue;
		} 
		if (!empty($param) && $param != "") {
			$gvalue =  explode($param, $gvalue);
		} else {
			PcErrors::set('Split attribute cannot be null.', substr($shortcode_obj['shortcode'],0, 30), -1, 'notice');
		}
		return $gvalue;
	}
}
pinacode_set_attribute('split', 'pinacode_attr_fn_split');



/**
 * [% json_decode]
 */
if (!function_exists('pinacode_attr_fn_json_decode')) {
	function pinacode_attr_fn_json_decode($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			$gvalue = json_decode($gvalue, true);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('json_decode', 'pinacode_attr_fn_json_decode');

/**
 * [% json_encode]
 */
if (!function_exists('pinacode_attr_fn_json_encode_all')) {
	function pinacode_attr_fn_json_encode_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			$gvalue = json_encode($gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('json_encode', 'pinacode_attr_fn_json_encode');

/**
 * [% is_object]
 */
if (!function_exists('pinacode_attr_fn_is_object_all')) {
	function pinacode_attr_fn_is_object_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			return 1;
		}
		return 0;
	}
}
pinacode_set_attribute('is_object', 'pinacode_attr_fn_is_object');

/**
 * [% is_string]
 */
if (!function_exists('pinacode_attr_fn_is_string_all')) {
	function pinacode_attr_fn_is_string_all($gvalue, $param, $shortcode_obj) {
		if (is_string($gvalue)) {
			return 1;
		}
		return 0;
	}
}
pinacode_set_attribute('is_string', 'pinacode_attr_fn_is_string');

/**
 * [% is_date]
 */
if (!function_exists('pinacode_attr_fn_is_date_all')) {
	function pinacode_attr_fn_is_date($gvalue, $param, $shortcode_obj) {
		if (is_object(pina_get_date_to_string($gvalue))) {
			return 1;
		}
		return 0;
	}
}
pinacode_set_attribute('is_date', 'pinacode_attr_fn_is_date');

/**
 * [% order_reverse]
 */
if (!function_exists('pinacode_attr_fn_order_reverse_all')) {
	function pinacode_attr_fn_order_reverse_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			return array_reverse((array)$gvalue);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('order_reverse', 'pinacode_attr_fn_order_reverse');



/**
 * [%default]
 */
if (!function_exists('pinacode_attr_fn_default_all')) {
	function pinacode_attr_fn_default_all($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			if (count($gvalue) == 0) {
				return PinaCode::execute_shortcode($param);
			}
		} else if ($gvalue == 0 || trim($gvalue) == "" || $gvalue == NULL) {
			return PinaCode::execute_shortcode($param);
		}
		return $gvalue;
	}
}
pinacode_set_attribute('default', 'pinacode_attr_fn_default');



/**
 * [% decode_ids] ritorna gli id di un record a partire dalla stringa compressa con ids_url_encode
 */
if (!function_exists('pinacode_attr_fn_decode_ids')) {
	function pinacode_attr_fn_decode_ids($gvalue, $param, $shortcode_obj) {
		if (is_array($gvalue) || is_object($gvalue)) {
			return $gvalue;
		} else {
			return dbp_fn::ids_url_decode($gvalue);
		}
	}
}
pinacode_set_attribute('decode_ids', 'pinacode_attr_fn_decode_ids');