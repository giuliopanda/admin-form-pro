<?php
/**
 * Le funzioni di servizio di pinacode per la gestione della matematica e logica
 * @example ```php 
 *	echo PinaCode::math_and_logic('(3+5) * (3^2) / (38-2)'); // 2
 *	echo PinaCode::math_and_logic('4 > 2'); // 1
 *	echo PinaCode::math_and_logic("foo == bar"); // 0
 *	echo PinaCode::math_and_logic("foo == foo"); // 1 
 * ```
 */

namespace DbPress;

/**
 * @var Array $pina_bracket_array l'elenco dei gruppi tra parentesi tonde
 */
$pina_bracket_array = []; 

/**
 * Ritorna true se ci sono ancora parentesi tonde da elaborare all'interno della stringa passata.
 * @param String $string
 * @return Boolean
 */
function pina_has_brackets($string) {
	global $pina_bracket_array;
	if (count ($pina_bracket_array) > 0) {
		return true;
	} 
	$bracket_array = [];
	preg_match_all("|\([^\(\)]*\)|U", $string, $brackets , PREG_PATTERN_ORDER);	
	if (is_array($brackets) && count($brackets) > 0) {
		foreach ($brackets as $bracket) {
			if (isset($bracket[0]) ) {
				$pina_bracket_array[] = $bracket[0];
			}
		}
	} 
	return (count ($pina_bracket_array) > 0) ? true : false;
}

/**
 * Esegue una singola parte della stringa tra parentesi tonde
 * @param String $string
 * @return String
 */
function pina_execute_brackets($string) {
	global $pina_bracket_array;
	if (count ($pina_bracket_array) > 0) {
		$bracket = array_shift($pina_bracket_array);
		$ris = pina_calculator(str_replace(["(",")"],"", $bracket));
		$string = str_replace($bracket, $ris, $string);
	}
	return $string;
}

/**
 * Esegue una stringa con gli operatori logici 
 * @param String $left
 * @return String $right
 * @return String $operator
 * 
 */

function pina_logical_operation ($left, $right, $operator) {
	if (is_string($left) && is_string($right)) {
		$string = $left.$operator.$right;
	} else {
		$string = 0;
	}
	switch ($operator) {
		case "+":
			if (is_numeric($left) && is_numeric($right) ) {
				$string = $left + $right; 
			}  else if ( is_numeric($right) ) {
				$string =  $right;
			} else {
				$string = 0;
			}
			break;
		case "-":
			if (is_numeric($left) && is_numeric($right) ) {
				$string = $left - $right;
			} else if ( is_numeric($right) ) {
				$string = - $right;
			} else {
				$string = 0;
			}
			break;
		case "*":
			if (is_numeric($left) && is_numeric($right) ) {
				$string = $left * $right;
			} else {
				$string = 0;
			}
			break;
		case "/":
			if ((is_numeric($left)  && $left > 0 ) && (is_numeric($right) && $right > 0 )) {
				$string = $left / $right;
			} else {
				$string = 0; 
			}
			break;
		case "^":
			if (is_numeric($left) && is_numeric($right) ) {
				$string =pow($left, $right);
			} else {
				$string = 0; 
			}
			break;
		case "%":
			if (is_numeric($left) && is_numeric($right) ) {
				$string = $left % $right;
			} else {
				$string = 0; 
			}
			break;
		case "==":
			//print "<p>-- ".$left. "==".$right."</p>";
			if (is_string($left) && is_string($right)) {
				$string =  (strtolower(trim($left)) == strtolower(trim($right))) ? 1 : 0;
			} else {
				$string =  ($left == $right) ? 1 : 0;
			}
			break;
		case "<>":
		case "!=":
			$string =  ($left != $right) ? 1 : 0;
			break;
		case "<=":
			$string =  ($left <= $right) ? 1 : 0;
			break;
		case "<":
			$string =  ($left <$right) ? 1 : 0;
			break;
		case ">":
			$string =  ($left >$right) ? 1 : 0;
			break;
		case ">=":
			$string =  ($left >= $right) ? 1 : 0;
			break;
		case " in ":
			
			if (is_array($right) || is_object($right)) {
				$string =  (in_array($left, (array)$right)) ? 1 : 0;
			} else if (is_string($right)) {
				$string = (stripos($right , $left)) ? 1 : 0;
			} else  {
				$string = 0;
			}
			break;
		case " not in ":
			if (is_array($right) || is_object($right)) {
				$string =  (!in_array($left, (array)$right)) ? 1 : 0;
			} else if (is_string($right)) {
				$string = (stripos($right , $left) === false) ? 1 : 0;
			} else  {
				$string = 0;
			}
			break;
		case "&&":	
		case "and":	
			$string =  ($left and $right) ? 1 : 0;
			break;
		case "||":
		case "or":
			$string =  ($left or $right) ? 1 : 0;
			break;
		case "!":
			$string = !$right; 
			break;
	}
	return $string;
}




/**
 * Divide una stringa per gli operatori e li esegue
 * @param String $string
 */
function pina_calculator($string) {
	$op = array( "and","or", "&&", "||","==","!=",">=","<=","<>"," not in "," in ", ">", "<", "!", "+", "-", "*", "/", "^", "%");
	//print "<p>pina_logical_operation: ".$string."</p>";
	$string = trim($string);
	// escludo eventuali testi con virgolette
	$um = new GpUtilitiesMarks();
	$string = $um->replace($string);
	foreach ($op as $o) {
		$find = stripos($string, $o);
		if ($find !== false && substr($string, $find-1,1) != "[") {
			$left = $um->restore(trim(substr($string, 0, $find)));
			$right = $um->restore(trim(substr($string, $find+strlen($o))));
			//print "<p>".$left." |||| ".$right."</p>";
			$left = pina_remove_quotes(pina_calculator($left));
			$right = pina_remove_quotes(pina_calculator($right)); 
			$string = pina_logical_operation($left, $right, $o);
			//print "<p>".$left." ".$o." ".$right." = ".$string."</p>";
		}
	}
	$string = $um->restore($string);
	return $string;
}
