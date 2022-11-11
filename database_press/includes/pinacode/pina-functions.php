<?php
namespace DbPress;
/**
 * Le funzioni di servizio di pinacode
 */
 $get_and_transform_total = 0;
 $pina_find_block = 0;
// chace per velocizzare l'esecuzione
$splittable = [];
$found_block = [];
/**
 * Aggiunge gli attributi ad un elemento html
 */
function pina_add_html_attributes($attributes) {
	$add = array();
	if (@array_key_exists('class', $attributes)) { 
		if (is_array($attributes['class'])) {
			$attributes['class'] = array_unique($attributes['class']);
			$attributes['class'] = implode(" ", $attributes['class']);
		}
		$add[] = 'class="'.$attributes['class'].'"';
	}
	if (@array_key_exists('style', $attributes)) { 
		if (is_array($attributes['style'])) {
			$attributes['style'] =array_unique($attributes['style']);
			$attributes['style'] = implode("; ", $attributes['style']);
		}
		$add[] = 'style="'.$attributes['style'].'"';
	}
	if (@array_key_exists('attrs', $attributes)) { 
		$add[] = $attributes['attrs'];
	}
	if (@array_key_exists('target', $attributes)) { 
		$add[] ='target="'.$attributes['target'].'"';
	} elseif (@array_key_exists('blank', $attributes)) { 
		$add[] = 'target="_blank"';
	}
	if (count($add) > 0) {
		$add_string = " ".implode(" ",$add);
	} else {
		$add_string = "";
	}
	return $add_string;
}

/**
 * Aggiunge una classe agli attributi
 * @param String $add_class 
 */
function pina_add_class_to_attributes($attributes, $add_class) {
	if (isset($attributes['class'])) {
		if (is_array($attributes['class'])) {
			$attributes['class'][] = $add_class;
		} else {
			$attributes['class'] = str_replace($add_class, "", $attributes['class'])." ".$add_class;
		}
	} else {
		$attributes['class'] = $add_class;
	}
	return $attributes;
}

/**
 * Aggiunge argomenti ad un link
 * @param Mixed $args Può essere un oggetto, un array o una stringa 
 * @param String $link
 * @return String (il link)
 */
function pina_add_query_arg($args, $link) {
	$query_args = [];
	
	if (is_array($args) || is_object($args)) {
		$query_args = (array)$args;
	} else {
		$args = pina_remove_quotes($args);
		if (substr($args,0,1) == "?") {
			$args = substr($args,1);
		}
		try {
			$json = true;
			$jsonObj  = json_decode($args, true);
		} catch (\Exception $e) {
			$json = false;
		}
		if (json_last_error() != JSON_ERROR_NONE) {
			PcErrors::set('JSON PARSE - '.get_json_error_decode().' IN <b>'.substr($args,0,15)."</b>", substr($args,0, 30), -1, 'warning');
		}
		if (!$json || $jsonObj == null || !is_array($jsonObj)) {
			$list_q = explode("&", html_entity_decode($args));
			if (count ($list_q) > 0) {
				foreach ($list_q as $lq) {
					$lq_exp = explode("=", $lq);
					if (count($lq_exp) == 2) {
						$query_args[$lq_exp[0]] = $lq_exp[1];
					}
				}
			}
		} else {
			$query_args = (array)$jsonObj;
		}
		
		if (is_array($query_args) && count($query_args) > 0) {
			$link = add_query_arg($query_args, $link);
		}
	}
	
	return $link;
}

/**
 * Rimuove le virgolette da una stringa
 */

function pina_remove_quotes($string) {
	if (is_string($string)) {
		$b_string = trim($string);
		if ((substr($b_string,0,1) == '"' && substr($b_string,-1) == '"') || substr($b_string,0,1) == "'" && substr($b_string,-1) == "'") {
			$string = wp_unslash(substr($b_string,1,-1));
		} 
	}
	return $string;
}

/**
 * trasforma uno shortcode con annessi parametri [var cmd cmd=param cmd="param mio" cmd="[""A"", ""B""]"]
 * @return String
 */
function pina_get_and_stransform($var) {
	global $get_and_transform_total;
	$inizio = microtime(true);
	// I return dentro gli attributi esauriscono la loro funzione dentro l'attributo stesso
	//$external_return = $this->current_return;

	$split = pina_split_shortcode_attributes($var);
	//var_dump ($split);
	if ($split['shortcode'] == "" || $split['shortcode'] == false) return $var;
	$get_var = PinaAfterAttributes::reset();
	//print "<p>shortcode: ".$split['shortcode']." ".$split['type']."</p>";
	
	// Se lo shortcode è a sua volta uno shortcode 
	if (in_array(substr($split['shortcode'],0,2), ["[%", "[^"]) && substr($split['shortcode'],-1) == ']' ) {
			//print ("<p>SH ".  $split['shortcode']."</p>");
			//$split['shortcode'] = "[%". $this->short_code( $split['shortcode'])."]";
			$split['shortcode'] =  PinaCode::get_registry()->short_code( $split['shortcode']);
			//print ("<p>SPLIT ".$split['shortcode']."</p>");
	}
	// provo a vedere se è un json
	if ((substr($split['shortcode'],0,1) == '[' && !in_array(substr($split['shortcode'],1,1), ["%", "^", ":"]) && substr($split['shortcode'],-1) == ']') ||  (substr($split['shortcode'],0,1) == "{" && substr($split['shortcode'],-1) == "}")) {
		// è un array
		//$get_var = $split['shortcode'];
		$json = true;
		try {
			$jsonObj  = json_decode( $split['shortcode'], true);
		} catch (\Exception $e) {
			$json = false;
		}
		if (json_last_error() != JSON_ERROR_NONE) {
			PcErrors::set('JSON PARSE - '.get_json_error_decode().' IN <b>'.substr($split['shortcode'],0,15)."</b>", substr($split['shortcode'],0, 30), -1, 'warning');
		}
		if (!$json || $jsonObj == null || !is_array($jsonObj)) {
			$get_var = $split['shortcode'];
		} else {
			$get_var = $jsonObj;
		}
		$split['shortcode'] = uniqid();
		
		//se lo shortcode è tra virgolette allora lo prendo come testo puro
	} else if ((substr($split['shortcode'],0,1) == '"' && substr($split['shortcode'],-1) == '"') ||  (substr($split['shortcode'],0,1) == "'" && substr($split['shortcode'],-1) == "'")) {
		$get_var = pina_remove_quotes($split['shortcode']);
		$split['shortcode'] = uniqid();
		PinaCode::set_var($split['shortcode'], $get_var);	
	} else if ($split['type']=="function") {
		// verifico se lo shortcode è una funzione speciale
		$split['shortcode'] = trim(strtolower($split['shortcode']));
		$split['string_shortcode'] = $var;
		$get_var = PinaActions::execute($split);
		//print "<p>split['shortcode']".$split['shortcode']. " = ".$get_var."</p>";
	} else {
		$get_var = PinaCode::get_var($split['shortcode']);
	}
	//print "<p>".$var." = ".round((microtime(true) - $inizio), 6)."</p>";
	if (count($split['attributes']) == 0) {
		$get_and_transform_total += (microtime(true) - $inizio);
		return $get_var;
	}
	// il pezzetto sotto rallenta molto il codice!!!!
	$search = $replace = "";
	
	$get_var = PinaAttributes::execute($get_var, $split);
	// ATTRIBUTI	
	$get_var = PinaAfterAttributes::execute($get_var);
	//$this->current_return = $external_return;
	$get_and_transform_total += (microtime(true) - $inizio);
	return $get_var;
}





/**
 * Trova il primo blocco rispetto ai parametri dati. 
 * Uso una funzione invece dell'espressioni regolari perché:
 * 1 voglio trovare a prescindere dalla ricerca i blocchi nello stesso ordine in cui sono scritti nel codice
 * 2 voglio poter gestire le occorrenze annidate.
 * @param String $string la stringa con i tag
 * @param Number $start_offset In numero di caratteri da cui partire
 * @param Array needles Opzionale {type:['open_tag','close_tag']} se non impostato elabora i blocchi di base 
 * 				Se metti tre parametri nell'array dopo aver trovato il primo le aperture e chiusure le calcola tra il terzo e il secondo 
 * @return Array [la stringa lavorata, l'offset di dove è stata trovata la stringa, il carattere dove è stato trovato, il tipo]
 */
function pina_find_block($string, $start_offset = 0, $needles_def = false) {
	global $pina_find_block, $found_block;
	
	$inizio = microtime(true);
	if ($needles_def === false) {
		if (strpos($string, "[^") === false && strpos($string, "[%") === false && strpos($string, "[:") === false) {
			$pina_find_block += (microtime(true) - $inizio);
			return array($string, "", "", "");
		}
		$md5 = md5($string);
		if (array_key_exists($md5, $found_block) &&  array_key_exists($start_offset, $found_block[$md5])) {
			$pina_find_block += (microtime(true) - $inizio);
			return $found_block[$md5][$start_offset];
		}
	}
	$pre_string = "";
	/*
	if (is_array($string) || is_object($string)) {
		return array('', $string, '', '');
	}
	*/
	//print "<p><small>String(".htmlentities($string).")</small></p>";
	if ($start_offset > 0) {
		$pre_string = substr($string, 0, $start_offset); 
		$string = substr($string, $start_offset, strlen($string) - $start_offset);
	}
	if ($needles_def === false) {
		$needles = array('if'=>["[^if", "[^endif]"], 'for'=>["[^for", "[^endfor]"], 'break'=>["[^break", "]","["], 'return'=>["[^return", "]","["],  'while'=>["[^while","[^endwhile]"], 'set'=>["[^set","]","["], 'math'=>["[^math","]","["], 'ob'=>["[^block","[^endblock]"],'ex'=>["[:",":]"] ,'sc'=>["[%","]","["], 'scfn'=>["[^","]","["]);
		
	} else {
		$needles = $needles_def;
	}
	$find = strlen($string); 
	$find_key = "";
	$found = false;

	//print "<p>FINDNEEDLE</p>";
	foreach ($needles as $k=>$needle) {
		$temp_find = stripos($string, $needle[0]);
		if ($temp_find < $find && $temp_find !== false) {
			$find = $temp_find;
			$find_key = $k;
		}
		if ($find < 2) break;
	}
	if ($find_key == "") {	
		$pina_find_block += (microtime(true) - $inizio);
		PcErrors::set('No found block <b>'. htmlentities(substr( $pre_string,0, 60)).'</b>', strlen($pre_string), -1,'debug');
		return array($pre_string, "", $string, "");
	}
	$curr_needle = $needles[$find_key];
	$offset = $find + strlen($curr_needle[0]);
	$countif = 1;
	$count_while = 0;
	$old_end = 0;
	//ob_start();
	//print ("<p>FIND: ".substr($string, $find)."</p>");
	//print $countif;
	while($countif > 0 && $count_while < 20 && $offset < strlen($string) ) {
		if (count($curr_needle) == 3) {
			$start = stripos($string, $curr_needle[2], $offset);
		} else {
			$start  = stripos($string, $curr_needle[0], $offset);
		}
		$end = stripos($string, $curr_needle[1], $offset);
	//	print "<p>curr_needle ". $curr_needle[1]." start: ".$start." end: ".$end." offset ".$offset." ".$countif."</p>";
		if ($end === $start && $start === false) break; 
		$old_end = $end;
		if (($end < $start && $start !== false) || $start === false) {
			// ho trovato end2
			$countif --;
			$offset = $end + strlen($curr_needle[1]);
			if ($countif == 0) {
				//print "<p>END2 = ".$curr_needle[1]." offset: " .$offset."</p>";
				$found = true;
				break;
			}
		} else {
			$countif ++;
			if (count($curr_needle) == 3) {
				$offset = $start + strlen($curr_needle[2]);
			} else {
				$offset = $start + strlen($curr_needle[0]);
			}
		}
		//print ("<p>countIf: ".$countif."</p>");
		$count_while++;
	}
	
	if ($found) {
		$pre_string = $pre_string. substr($string, 0, $find);
		$block = substr($string, $find, $offset - $find);
		$post_string = substr($string, $offset, strlen($string) - $offset);
		if ($needles_def === false) {
			if (!array_key_exists($md5, $found_block)) {
				$found_block[$md5] = [];
			}
			$found_block[$md5][$start_offset] = array($pre_string, $block,  $post_string, $find_key);
		}
		$pina_find_block += (microtime(true) - $inizio);
		//print "<p><small>BLOCK: ".$block."</small></p>";
		PcErrors::set('Found block <b>'. htmlentities(substr( $block,0, 90)).'</b>', strlen($block),-1, 'debug');
		return array($pre_string, $block,  $post_string, $find_key);
	} else if ($find_key != "") {	
		//print "OK";
		$fn = $needles[$find_key][0];
		if ($fn == "[%") {
			$fn = substr($string,0, 10);
		}
		if (strlen($string) > 30) {
			$error_string = substr($string,0, 30)." ...";
		}
		PcErrors::set('Syntax error <b>'. htmlentities(substr($string,0, 90)).'</b> doesn\'t have correct close', htmlentities(substr($string,0, 30)), strlen($pre_string)+ $find, 'error');
		$offset = max($offset, $old_end);
		return array($pre_string, $string, '', 'error');
		//die;
	}

	$offset = $start_offset + $offset;
	if ($start_offset  == $offset) {
		$offset +=1;
	}
	$pina_find_block += (microtime(true) - $inizio);
	PcErrors::set('Not found block <b>'. htmlentities(substr($string,0, 90)).'</b>', strlen($pre_string),-1, 'debug');
	return array($pre_string, '', $string, '');
}
/**
 * Elimina da un testo le parti tra virgolette sostituendole con variabili esadecimali uniqid()
 */
if (!class_exists('GpUtilitiesMarks')) {
    class GpUtilitiesMarks 
    {
        private $variables = array();  
        /**
         * trova le stringe tra virgolette doppie (") non prende in considerazione \" e le sostituisce con delle variabili
         * @param string $string
         * @return string
         */
        public function replace($string) {
            $re = '/(\"(\\\"|\s|.)*?\")/m';
            preg_match_all($re, $string, $matches, PREG_SET_ORDER, 0);
            foreach ($matches as $match) {
                $uniqueId = uniqid("var");
                while(strpos($string, $uniqueId) != false) {
                    $uniqueId = uniqid("var", true);
                }
                $this->variables[$uniqueId] = $match[0];
                $string = str_replace($match[0], $uniqueId, $string);
            }
            return $string;
        }
        /**
         * Ripristina un testo con le il contenuto originale con le virgolette
         * @param String $string
         * @return Array
         */
        public function restore($string) {
            foreach ($this->variables as $key=>$value) {
                $string = str_replace($key, $value, $string);
            }
            return $string;
        }
    }
}


/**
 * Eseguo eventuali shortcode dei valori degli attributi 
 * A differenza di execute_shortcode se è un json lo converte
 * @param String $param
 */
function pina_execute_attribute_param($param) {
	if (is_string($param)) {
		if (strpos($param, "[%") !== false || strpos($param, "[^") !== false || substr($param,0,2) == "[:") {
			$registry = PinaCode::get_registry();
			$default_return = $registry->current_return;
			$param = PinaCode::get_registry()->short_code($param);
			$registry->current_return = $default_return;
			$param = pina_remove_quotes($param);
		} elseif (substr($param,0,1) == "[" || substr($param,0,1) == "{") {
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
				// ha dato errore
			} else {
				$param = $jsonObj;
			}	
		} else {
			$param = pina_remove_quotes($param);
		}	
	}
	return $param;
}


/**
 * Prende uno shortcode e ne riorna i valori e gli attributi. Deve avere le parentesi quadre ad inizio fine
 * attributes sono gli attributi con shortcode convertiti, attrs_name sono invece i nomi originali ancora non convertiti
 * @return Array [%shortcode=>'' , attributes=>[attributes_name=>value, ...], attrs_name=>[attributes_name=>value, ...], type=>Sting(function|variable)]
 */
function pina_split_shortcode_attributes($short_code) {
	global $splittable;
	if (array_key_exists($short_code, $splittable)) {
		return $splittable[$short_code]; // velocizza i cicli di un centinaio di volte
	}
	$start_shortcode = $short_code;
	if (!is_string($short_code)) return $short_code;
	$short_code = trim($short_code);
	if ( in_array(substr($short_code,0,2), ["[%", "[^"]) && substr($short_code,-1) == "]") {
		$type = (substr($short_code,1,1) == "^") ? "function" : "variable" ;
		$short_code = substr($short_code,2,strlen($short_code)-3);
	} else {
		return ['shortcode'=>false, 'attributes'=>[], 'attrs_name'=>[], 'type'=>''];
	}
	$matches = [];
	$start = 0;
	$open_bracket1 = $open_bracket2 = $open_bracket3 = $open_quote1 = $open_quote2 = $open_special  = 0;
	$short_code = str_replace(["&#8220;", "&#8221;",  "&#8243;"], '"', $short_code);
	$short_code = str_replace(array("&#8217;","&#8216;", "&#39;") , "'", $short_code);
	//print "shortCode:";
	//var_dump($short_code);

	$short_array = str_split($short_code);
	foreach ($short_array as $x=>$v) {
	//for($x =1; $x < strlen($short_code); $x++) {
		//print "<p> " . $v.@$short_array[$x+1]."</p>";
		if (( $x > 0 && @$short_array[$x-1] != "\\") || $x == 0 ) {
			//print " OK ";
			if ($v == "(") {
				$open_bracket1++;
			} else if ($v == "[" && @$short_array[$x+1] == ":") {
				$open_special++;
			} else if ($v == "["  && @$short_array[$x+1] != ":") {
				$open_bracket2++;
			} else if ($v == "{") {
				$open_bracket3++;
			//} else if (($v == '"' && $x > 0 && (@$short_array[$x-1] != '"' || (@$short_array[$x-1] == '"' && $open_quote1  == 0))) || ($v == '"' && $x == 0)) {
			} else if ($v == '"' && $open_quote2 == 0) {
			// TODO accetto la doppia virgoletta come lo slash? per ora non riesco a gestirlo
				$open_quote1 = 1 - $open_quote1;
			//} else if (($v == "'" && $x > 0 && (@$short_array[$x-1] != "'" || (@$short_array[$x-1] == "'" && $open_quote2  == 0))) || ($v == "'" && $x == 0)) {
			} else if ($v == "'" && $open_quote1 == 0) {
				$open_quote2= 1 - $open_quote2;
			} elseif ($v == ")") {
				$open_bracket1--;
			} elseif ($v == ":" && @$short_array[$x+1] == "]") {
				$open_special--;
			} elseif ($v == "]" && @$short_array[$x-1] != ":") {
				$open_bracket2--;
			} elseif ($v == "}") {
				$open_bracket3--;
			}
		}
		//	print "<p>open_special ".$open_special."</p>";
		if (($v == " " || $v == "\n"  || $v == "\r" || $v == "\t" ) && $open_bracket1 <= 0 && $open_bracket2 <= 0 && $open_bracket3 <= 0 && $open_quote1 <= 0 && $open_quote2 <= 0 && $open_special <= 0 ) {
		//	print "<p>TAKE ".substr($short_code, $start, $x- $start)."</p>";
			$matches[] = substr($short_code, $start, $x- $start);
			$start = $x+1;
		}
	}
	if ($start > 0 && $start < strlen($short_code)-1) {
		$matches[] = substr($short_code, $start, strlen($short_code) - $start);
	}
	
	
	//var_dump ($matches);
	if (count($matches) == 0) {
		$splittable[$start_shortcode] = ['shortcode'=>$short_code, 'attributes'=>[], 'attrs_name'=>[], 'type'=>$type];
		//print "<p>".$start_shortcode." = ".round((microtime(true) - $inizio), 6)."</p>";
		return ['shortcode'=>$short_code, 'attributes'=>[], 'attrs_name'=>[], 'type'=>$type];
	} else {
		$short_code= trim(array_shift($matches));
		$attributes = $attrs_name = [];
		
		foreach ($matches as $match) {
			if (trim($match) == "") continue;
			if (substr($match,0,1) == "=" || substr($match,-1) == "=" ) {
				PcErrors::set('ATTRIBUTES error: <b>'.$start_shortcode.'</b> The equal sign (=) between the attribute name and the attribute value must never have a space! <br>name=value // is correct<br>name = value // is not correct!', '', -1, 'error');

				return ['shortcode'=>false, 'attributes'=> [] , 'attrs_name'=> [], 'type'=>$type];

			} else {
				$explode = explode("=", $match);
				$cmd = strtolower(trim(array_shift($explode)));
				/*
				if ($cmd == "") {
					$cmd = "set";
				}
				*/
				$param = "";
				if (count($explode) > 0) {
					$param = implode("=", $explode);			
				}
				if (array_key_exists($cmd, $attrs_name )) {
					if (is_string($attrs_name[$cmd])) {
						$attrs_name[$cmd] = [$attrs_name[$cmd]];
						$attributes[$cmd] = [$attributes[$cmd]];
					}
					$attrs_name[$cmd][] = $param;
					$attributes[$cmd][] = $param;
				} else {
					$attrs_name[$cmd] = $param;
					$attributes[$cmd] = $param;
				}
			}			
		}
		//var_dump ($attributes);
		$splittable[$start_shortcode] = ['shortcode'=>$short_code, 'attributes'=>$attributes , 'attrs_name'=> $attrs_name, 'type'=>$type];
		//print "<p>2 ".$start_shortcode." = ".round((microtime(true) - $inizio), 6)."</p>";
		return ['shortcode'=>$short_code, 'attributes'=>$attributes , 'attrs_name'=> $attrs_name, 'type'=>$type];
	}
}


/**
 * Torna l'utlimo errore json
 */
function get_json_error_decode() {
	 switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return ' No errors';
        break;
        case JSON_ERROR_DEPTH:
            return ' Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            return ' Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            return ' Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            return ' Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            return ' Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            return ' Unknown error';
        break;
    }

}

/**
 * Elabora una stringa e cerca di trasformarla in datetime
 * @param String $gvalue
 * @return Date
 */

function pina_get_date_to_string($gvalue) {
	if (is_string($gvalue) ) {
		try {
			$date = new \DateTime($gvalue);
		} catch (\Exception $e) {
			//print_r(\DateTime::getLastErrors());
			$date = false;
		}
		if (is_object($date)) {
			return $date;
		}
		if (strlen($gvalue) == 10 && is_numeric($gvalue)) {
			// timestamp
			try {
				$date = (new \DateTime('now',  \wp_timezone()))->setTimestamp($gvalue);
			} catch (\Exception $e) {
				$date = false;
			}
		} else if (strlen($gvalue) == 14 && is_numeric($gvalue)) {
			//AAAAMMGGHHIISS
			$a = substr($gvalue,0,4);
			$m = substr($gvalue,4,2);
			$g = substr($gvalue,6,2);
			$h = substr($gvalue,8,2);
			$i = substr($gvalue,10,2);
			$s = substr($gvalue,12,2);
			if ( $m <= 12 && $g <=31 && $h < 24 && $i < 60 && $s < 60) {
				try {
					$date = new \DateTime($a."-".$m."-".$g." ".$h.":".$i.":".$s);
				} catch (\Exception $e) {
					$date = false;
				}
			} 
		} else if (strlen($gvalue) == 8 && is_numeric($gvalue)) {
			//AAAAMMGG
			$a = substr($gvalue,0,4);
			$m = substr($gvalue,4,2);
			$g = substr($gvalue,6,2);
			if ($m <= 12 && $g <=31 ) {
				try {
					$date = new \DateTime($a."-".$m."-".$g);
				} catch (\Exception $e) {
					$date = false;
				}
			} 
		}
		
		if (is_object($date)) {
			return $date;
		}
	} 
	return false;
	
}


/**
 * Aggiunge i parametri dei filtraggi del frontend ai link
 * Lo uso su pagination, order, filter
 * le query possono essere passate in $path_parametro oppure $path[parametro]
 * @param String $link 
 * @param String $path Il prefisso dei parametri
 * @param String $exclude Se c'è un parametro da non inserire
 * @return String Il nuovo link
 */
function pina_frontend_add_query_args($link, $path, $exclude = "") {
	$length = strlen($path);
	if (isset($_REQUEST) && is_array($_REQUEST)) {
		foreach ($_REQUEST as $key=>$value) {
			if (substr($key,0, $length) == $path && substr($key, $length) != "_".$exclude) {
				$link = add_query_arg($key, $value, $link);
			}
		}
	}
	return $link;
}

/**
 * Verifica se gli attributi sono scritti bene nello shortcode. 
 * @param Array $attributes L'elenco degli attributi
 * @param Array $allow_attr l'array con le verifiche. Le chiavi sono i nomi degli attributi, il valore il tipo
 * @param String $short_code_name
 * @param Array $required l'array con gli attributi obbligatori. I nomi degli attributi sono nei value.
 */
function pina_check_attributes($attributes, $allow_attr,  $short_code_name, $required = []) {
	
	foreach ($required as $req) {
		if (!in_array($req, $attributes)) {
			$msg = sprintf('%s: The <b>%s</b> attribute is required',  $short_code_name, $req);
			PcErrors::set($msg , $short_code_name,-1, 'error');
		}
	}
	foreach ($attributes as $k=>$val) {
		
		if (!isset($allow_attr[$k])) {
			$msg = sprintf('The "%s" attribute is not valid for the "<b>%s</b>" shortcode', $k, $short_code_name);
			PcErrors::set($msg , $short_code_name, -1, 'warning');
		} else {
			if ($allow_attr[$k] == "[string]") {
				if (!is_string($val)) {
					$msg = sprintf('%s: The value of the "%s" attribute must be a string', $short_code_name. $k);
					PcErrors::set($msg , $short_code_name,-1, 'warning');
				}
			}
			if ($allow_attr[$k] == "[numeric]") {
				if (!is_numeric($val)) {
					$msg = sprintf('%s: The value of the "%s" attribute must be a numeric',  $short_code_name, $k);
					PcErrors::set($msg , $short_code_name,-1, 'warning');
				}
			}
			if ($allow_attr[$k] == "[array]") {
				if (!is_array($val) && !is_object($val)) {
					$msg = sprintf('%s: The value of the "%s" attribute must be an array', $short_code_name,  $k);
					PcErrors::set($msg , $short_code_name,-1, 'warning');
				}
			}
			if (is_array($allow_attr[$k]) && !in_array($val, $allow_attr[$k])) {
				$msg = sprintf('%s: The "<b>%s</b>" attribute accepts the following values: (%s)',  $short_code_name, $k, implode(",", $allow_attr[$k]));
					PcErrors::set($msg , $short_code_name,-1, 'warning');
			}
		}
	}
}