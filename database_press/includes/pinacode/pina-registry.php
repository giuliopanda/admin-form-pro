<?php
/**
 * GP Registry
 * Memorizza e restituisce dati passati tra le classi, in sessione o attraverso le query dell'url.
 * php version 7.2
 * 
 * @category template_engine
 * @package  DbPress
 * @subpackage Pinacode
 */

namespace DbPress;

class PcRegistry
{
	/*
	 * @var 		PcRegistry  	L'istanza della classe per il singleton
	*/
	private static $instance 	= null;
	/*
	 * @var 		array  			Tutti i dati del Registro
	*/
	var $registry 				= array();
	/*
	 * @var 		array  			I puntatori dei foreach
	*/
	var $arrayPoint 			= array();
	/*
	 * @var 		String  			Il foreach corrente
	*/
	var $currentFor 			= false;
	/** 
	 * @var Boolean  $current_return	La gestione del return
	*/
	var $current_return			= false;

	/**
	 * Ritorna il singleton della classe
	 * @return  	singleton
	**/
	public static function getInstance() 
	{
   	   if(self::$instance == null) 
	   {   
   	      $c = __CLASS__;
   	      self::$instance = new $c;
		}
		return self::$instance;
   	}
	/**
	 * Memorizza una nuova variabile. Se si vuole salvare su sessione il path sarà session.path . 
	 * Se si vuole fare l'override dei request il path sarà request.miopath (esempio request.option).
	 * Per rimuovere una variabile basta passare al path il varole NULL.	
	 * @param   data		mixed		I dati da salvare
	 * @param   path		string		Dove salvare i dati all'intero dell'array registry es: main.mieidati.
	 * @return  	void
	**/
	function set($path, $data) 
	{    
		if (is_string($path) && $path != "") {
			if (strpos( $path, ".") === false) {
				$this->registry[$path] = $data;
				return;
			}
 			$path = explode(".", $path);
			$pointer = &$this->registry;
			foreach ($path as $k=>$p) {
				$p = trim($p);
				if ($p == "[]") {
					if (!is_array($pointer)) {
						$pointer = array($data);
					} else {
						$pointer[] = $data;
					}
					return;
				} else {
					if (!is_array($pointer) && !is_object($pointer)) {
						$pointer = array($pointer);
					}
					if (!isset($pointer[$p])) {
						$pointer[$p] = array();
					}
					if ($k == count($path)-1 && $data === NULL) {
						unset($pointer[$p]);
						return;
					} else {
						$pointer = &$pointer[$p];
					}
				}
			}
			$pointer = $data;	
		}
	}
	/**
	 * Ritorna una variabile. 
	 *@param   path		string		Il percorso in cui sono stati memorizzati i dati all'intero dell'array registry es: main.miavar
	 *@param   default	mixed	Se non ci sono dati nel path ritorna il valore impostato
	 *@param   return  	mixed
	**/
	function get($path = "main", $default = NULL) 
	{
		if ($path != "") {
			if (strpos( $path, ".") === false && $path !== "request") {
				return @$this->registry[$path];
			}
			$path = trim($path);
			$path = explode(".", $path);
			if ($path[0] == "request") {
				$pointer = $_REQUEST;
				array_shift($path);
			} else {
				$pointer = &$this->registry;
			}
			foreach ($path as $p) {
				if (is_array($pointer)) {
					// NON TROVO IL VALORE RICHIESTO, MA SE È UN ARRAY DI OGGETTI CERCO TRA LORO E RITORNO UN ARRAY
					if (!array_key_exists($p, $pointer)) {
						$return = [];
						foreach ($pointer as $key=>$row) {
							if (is_array($row)) {	
								if (array_key_exists($p, $row)) {
									$return[] = $row[$p];
								}
							}
							if (is_object($row)) {	
								if (property_exists($row, $p)) {
									$return[] = $row->$p;
								}
							}
						}
						if (count($return) > 0) {
							if (count($return) == 1) {
								return array_shift($return);
							}
							return $return; 
						} else {
							return $default;
						}
						return $default;
					} else {
						$pointer = &$pointer[$p];
					}
				}  else if (is_object($pointer)) {
					if (!property_exists($pointer, $p)) {
						return $default;
					}
					$pointer = &$pointer->$p;
				} else {
					return $default;
				}
			}
		
			return $pointer;
		} else {
			return NULL;
		} 	
	}
	/**
	 * Verifica se una variabile è settata . 
	 *@param   path		string		Il percorso in cui sono stati memorizzati i dati all'intero dell'array registry es: main.miavar
	 *@param   return  	Boolean
	**/
	function has($path = "main") 
	{
		if ($path != "") {
			$path = explode(".", $path);
			if (@strtolower(trim($path[0])) == "request") {
				$pointer = $_REQUEST;
				array_shift($path);
			} else {
				$pointer = &$this->registry;
			}
			foreach ($path as $p) {
				$p = trim($p);
				if (!is_array($pointer) && !is_object($pointer)) {
					return false;
				}
				if (is_array($pointer) && !array_key_exists($p, $pointer)) {
					return false;
				} else if (is_object($pointer) && !property_exists($pointer, $p)) {
					return false;
				}
				if (is_array($pointer)) {
					$pointer = &$pointer[$p];
				} else if (is_object($pointer)) {
					$pointer = &$pointer->$p;
				}
			}
			return (isset($pointer) || is_bool($pointer));
		} else {
			return false;
		} 	
	}

	/** 
	 * Elabora una stringa sostituendo i campi tra parentesi [] con le rispettive variabili trovate.
	 *@param   String		una stringa da elaborare
	 *@return		mixed
	*/
	function short_code($string) 
	{
		if (trim($string) == "") return "";
		if (is_array($string) || is_object($string)) {
			return (array)$string;
		}
		if ( $this->current_return ) return $string;
		// rimuovo i commenti
		preg_match_all('/\[\/\/[.\s\S]+?\/\/]/m', $string, $matches, PREG_SET_ORDER, 0);
		if (count($matches) > 0) {
			foreach ($matches as $m) {
				$string = str_replace($m, "", $string);
			}
		}
		// Evito di lavorare i json!?!?!
		if (substr($string,0,2) == '["' && substr(trim($string),-2,2) == '"]') return $string;
		// se è un solo elemento da sostituire ritorna l'oggetto dell'elemento stesso
		$single_block = false;
		
		if (is_string($string) && substr($string,0,2) == "[:" && substr($string,-2) == ":]") {
			// se ci sono i [: :] il codice all'interno lo eseguo e poi lo rieseguo... così se c'è una variabile la trasformo e poi elaboro la trasformazione
			$string = trim(substr($string,2,-2));
			$string = $this->short_code($string);
			if ( $this->current_return ) return $string;
		} else if (in_array(substr($string,0,2), ["[%", "[^"]) && substr(trim($string),-1,1) == "]" ) {
			list($pre_string, $block, $post_string, $type) = pina_find_block($string, 0);
			$curr_block = $block;
			if ($type == "sc" || $type == "scfn") {
				list($pre_string, $block2, $post_string, $type) = pina_find_block($post_string, 0);
				if ($type == "") {
					// OK è un blocco unico ritorna sempre il risultato senza incappare in esecuzioni di esecuzioni...
					// se passo una variabile senza altre informazioni ritorna la variabile senza che venga convertita in una stringa
					$block = pina_get_and_stransform($string);
					return  $block; 

				}
			} 
		}
		if (is_array($string) || is_object($string)) {
			return (array)$string;
		}
		$offset = 0;
		$max_while = 0;
		do  {
			// TODO pina_find_block DEVE TORNARE block = compilato non post_string!
			list($pre_string, $block, $post_string, $type) = pina_find_block($string, $offset);
			if ($type == "error") {
				return '';
			}
			$curr_block = $block;
			//print ($type);
			if ($type == "")  {
				$curr_block = "";
				//$block = "";
			}
			
			//print "<p>BLOCK: ".$block. " TYPE ".$type."</p>";
			/* NON CAPISCO A CHE SERVE? Lo rimuovo alla versione 1.0
			if ($type == "") {
				//print "<p>string: ".$pre_string." | ". $block. " | ". $post_string."</p>";	
				if (in_array(substr($string,0,2), ["[%", "[^"]) && substr($string,-1) == ']') {
						list($pre_string, $block, $post_string, $type) = pina_find_block(substr($string,2,-1), $offset);
						$pre_string = substr($string,0,2) . $pre_string;
						$post_string = $post_string."]";
				}
			}
			*/
			//elaboro il blocco
			if ($type == "sc" || $type == "scfn") {
				$get_var = pina_get_and_stransform($block);	
				if ($get_var !== NULL) {
					if (is_array($get_var) || is_object($get_var)) {
						$get_var = json_encode($get_var);
					}
					$block = $get_var;
				} else {
					//$block = "";
				}
			}

			if ($type == "ex") {
			 	//$block = trim(substr($block,2,-2));
				$block = $this->short_code($block);
			}
		
			if ($type == "set") {
				$get_var = pina_get_and_stransform($block);	// ??
				$set = pina_split_shortcode_attributes($block);
				//var_dump ($set);
				if (count($set['attributes']) > 0) {
					foreach ($set['attributes'] as $attk => $data) {
						if ($attk != "") {
							$attk = $this->short_code($attk);
							$this->set($attk, pina_execute_attribute_param($data));
						}
					}
				}
				
				$block = "";
			}
			if ($type == "math") {
				//print "<p>BLOCK: ".$block."</p>";
				$params = trim(substr($block,strlen("[^math"),-1));
				//print "<p>MATH".$params."</p>";
				$block = PinaCode::math_and_logic($params);
			}
			
			// output buffering [BLOCK] in pratica mette su variabile tutto il blocco contenuto senza elaborare le variabili al suo interno.
			if ($type == "ob") {
				//Trovo la prima riga
				list($pre_string2, $block2, $post_string2, $type2) = pina_find_block($block, 0, ['ob'=>['[',']']]);
				if ($type2 == "error") {
					return '';
				}
				$var_name = $this->short_code(trim(substr($block2,strlen("[^block"),-1)));
				$block =  substr($block,strlen($block2),-strlen("[^endblock]"));
			//	$var_value = $this->short_code($block) ;
				$this->set($var_name, $block);
				$block = "";
			}

			if ($type == "if") {
				//Trovo la prima riga
				list($pre_string2, $block2, $post_string2, $type2) = pina_find_block($block, 0, ['if'=>['[',']']]);
				if ($type2 == "error") {
					return '';
				}
				$block3 = substr($block2,strlen("[^if"),-1);
				// CERCO L'ELSE
				$if = stripos($block, "[^if", 3);
				$else =  stripos($block, "[^else]", 3);
				$endif = stripos($block, "[^endif]", 3);
				$offset = 3;
				$ifcount = 0;
				$end_if_true = -strlen("[^endif]");
				if ($else != false) {
					while ($if < $else && $if != false && $else != false) {
						$ifcount ++;
						$offset = stripos($block, "[^endif]", $offset)+strlen('[^endif]');
						if ($else < $offset) {
							$else =  stripos($block, "[^else]", $offset);
						}
						$if = stripos($block, "[^if", $offset);
					}
				}
				if ($else != false) {
				//	echo "<h2>ELSE : ".$else."</h2>";
					$end_if_true = $else - strlen($block2);
					$else_block = substr($block, $else+strlen('[^else]'), -strlen('[^endif]'));
					//echo substr($block, $else+strlen('[else]'), -strlen('[endif]'));
				//	die;
				} else {
					$else_block = "";
				}

				if (PinaCode::math_and_logic($block3)) {
					$block =  substr($block,strlen($block2), $end_if_true);
					$block = $this->short_code($block) ;
					if ( $this->current_return ) return $block;
				} else {
					$block = $this->short_code($else_block) ;
					if ( $this->current_return ) return $block;
				}
			}
			if ($type == "break") {
				list($pre_string2, $block2, $post_string2, $type2) = pina_find_block($block, 0, ['break'=>['[',']']]);
				if ($type2 == "error") {
					return '';
				}
				$block3 = substr($block2, strlen('[^break'),-1);
				//print "<p>".$block3." = ".PinaCode::math_and_logic($block3)."</p>";
				$block = "";
				if (trim($block3) == "" || PinaCode::math_and_logic($block3)) {
					$this->current_for = false;
					$this->current_while = false;
					
					$post_string = "";
					$curr_block = "";
				}
				//print "CURR BLOCK ";
			}
			if ($type == "return") {
				list($pre_string2, $block2, $post_string2, $type2) = pina_find_block($block, 0, ['return'=>['[',']']]);
				if ($type2 == "error") {
					return '';
				}
				$block3 = trim(substr($block2, strlen('[^return'),-1));
				$this->current_for = false;
				$this->current_while = false;
				$this->execute_shortcode = false;
				//.PinaCode::math_and_logic($block3)."</p>";
				$ris = $this->short_code($block3);
				$this->current_return = true;
				return pina_remove_quotes($ris);
				//print "CURR BLOCK ";
			}
			// Cicla un oggetto o un array
			if ($type == "for") {
				//Trovo la prima riga
				//print "<p>BLOCK ".$block."</p>";
				list($pre_string2, $block2, $post_string2, $type2) = pina_find_block($block, 0, ['for'=>['[',']']]);
				if ($type2 == "error") {
					return '';
				}
				//print "<p>block2 ".$block2."</p>";
				$for = pina_split_shortcode_attributes($block2);
				$attr = $for['attributes'];
				$attrs_name = $for['attrs_name'];
				if (isset($attr['each']))  {
					$reset_data = false;
					if (isset($attrs_name['val']) && $attrs_name['val'] != "") {
						$name = $attrs_name['val'];
					}  else {
						$name = "item";
					}
					$getKey = 'key';
					if (isset($attrs_name['key']) && $attrs_name['key'] != "") {
						$get_key = $attrs_name['key'];
					} else {
						$get_key = 'key';
						
					}
					$original_item = $this->get($name);
					$original_key = $this->get($get_key);
					//echo "<p>".$attr['each']."</p>";
					$data = pina_execute_attribute_param($attr['each']) ;
					//var_dump ($data);
					$array_block =[];
					$start_micro = microtime(true);
					if ((is_array($data) || is_object($data)) && @count($data) > 0) {
						$this->current_for = true;
						$count_for = 0;
						
						foreach ($data as $kd => $d) {
							
							$count_for++;
							if (!$this->current_for) break;
							if ($count_for > 10000) {
								PcErrors::set('Break loop!. too muck iteration (>10000)', substr($block2,0, 30), strlen($pre_string), 'warning');
								break;
							}
							if ( $start_micro > 0 && (microtime(true) - $start_micro) > 3) {
								PcErrors::set('Slow Loop: <b>'.substr($block2,0, 15)."</b>", substr($block2,0, 30), strlen($pre_string), 'notice');
								$start_micro =0;
							}
						
							// print "<p>SET NAME ";
							// var_dump($name);
							// print " D ";
							// var_dump ($d);
							// print "</p>";
							
							$this->set($name,  $d);
							$this->set($get_key,  $kd);
							$temp_block =  substr($block,strlen($block2),-strlen("[^endfor]"));
							//print "<p>temp_block ".$temp_block."</p>";
							//var_dump ($this->short_code($temp_block) );
							$temp_shortcode = $this->short_code($temp_block) ;
							if ( $this->current_return ) return $temp_shortcode;
							if ($this->current_for == false && $this->current_return == true) {
								$this->current_return == false;
								return $temp_shortcode;
							}
							if (is_array($temp_shortcode) || is_object($temp_shortcode)) {
								$temp_shortcode = json_encode($temp_shortcode);
							}
							
							$array_block[] = $temp_shortcode;
						} 
						$block = implode("", $array_block);
						
						$this->set($name, $original_item);
						$this->set($get_key, $original_key);
						
					} else {
						$block = "";
					}
				} else {
					//$block = "";
				}
			}
			if ($type == "while") {
				// Cicla fintanto che la condizione all'interno è true o raggiungo il massimo di cicli consentiti (100.000?)
				//Trovo la prima riga
				list($pre_string2, $block2, $post_string2, $type2) = pina_find_block($block, 0, ['while'=>['[',']']]);
				if ($type2 == "error") {
					return '';
				}
				//print "<p>BLOCK WHILE ".$block."</p>";
				$params = substr($block2,strlen("[^while"),-1);
				//print "<p>params ".$params."</p>";
				$boolean = PinaCode::math_and_logic($params);
				$count_while = 0;
				$array_block =[];
				$temp_block = substr($block,strlen($block2),-strlen("[^endwhile]"));
				$this->current_while = true;
				$this->current_return = false;
				//$this->short_code($temp_block) ;
				$start_micro = microtime(true);
				while ($boolean  && $this->current_while) {
					$count_while++;
					if ($count_while > 10000) {
						PcErrors::set('Break loop!. too muck iteration (>10000)', substr($block2,0, 30), strlen($pre_string), 'warning');
						break;
					}
					if ( $start_micro > 0 && (microtime(true) - $start_micro) > 3) {
						PcErrors::set('Slow Loop: <b>'.substr($block2,0, 15)."</b>", substr($block2,0, 30), strlen($pre_string), 'notice');
						$start_micro =0;
					}
					$temp_shortcode = $this->short_code($temp_block) ;
					if ( $this->current_return ) return $temp_shortcode;
					if ($this->current_while == false && $this->current_return == true) {
						$this->current_return = false;
						$this->current_while = true;
						return $temp_shortcode;
					}
					if (is_array($temp_shortcode) || is_object($temp_shortcode)) {
						$temp_shortcode = json_encode($temp_shortcode);
					}
					$array_block[] =$temp_shortcode;
					$boolean = PinaCode::math_and_logic($params);
				}
				
				if (count ($array_block) > 0) {
					foreach ($array_block as &$ab) {
						if (is_array($ab) || is_object($ab)) {
							$ab = array_shift($ab);
						}
					}
					$block = implode("", $array_block);
				} else {
					$block = "";
				}
				
				$this->current_while = true; 
			}
			/*
				è un disastro di eventi ricorsivi!!!
				if (stripos($block, $curr_block) !== false) {
					// Prevengo loop infiniti sta stampando il blocco che stampa il blocco...
					$offset = strlen($pre_string.$block);
				} else {
					$offset = strlen($pre_string);
				}
			*/
			if ($this->current_return == true) {
				$this->current_return = false;
				$curr_block == "";
				//return $block;
			}

			$offset = strlen($pre_string.$block);
			
			$short_code_converted = $block;
			$string = $pre_string.$block.$post_string;
			$max_while++;
		} while ($curr_block != "" AND $max_while < 500);	
		return $string;
		
	}
	
	/**
	 * Questa funzione 
	 */
	private function  replace_shortcode_to_unique_var($string) {
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
}