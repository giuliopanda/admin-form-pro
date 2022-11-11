<?php
/**
 * PcError
 * Memorizza gli errori di pinacode
*/

namespace DbPress;

class PcErrors
{

	/*
	 * @var 		array  			Tutti i dati del Registro
	*/
	private static $errors 				= array();

	/**
	 * Ritorna il singleton della classe
	 * @return  	singleton
	**/
	
	/**
	 * Memorizza una nuova variabile. Se si vuole salvare su sessione il path sarà session.path . 
	 * Se si vuole fare l'override dei request il path sarà request.miopath (esempio request.option).
	 * Per rimuovere una variabile basta passare al path il varole NULL.	
	 * @param   data		mixed		I dati da salvare
	 * @param   path		string		Dove salvare i dati all'intero dell'array registry es: main.mieidati.
	 * @return  	void
	**/
	public static function set($msg, $code_ref, $offset = 0, $error_type="warning") 
	{    
		self::$errors[] = array($msg, $code_ref, $offset, $error_type);
	}
	/**
	 * Ritorna una variabile. 
	 *@param   path		string		Il percorso in cui sono stati memorizzati i dati all'intero dell'array registry es: main.miavar
	 *@param   default	mixed	Se non ci sono dati nel path ritorna il valore impostato
	 *@param   return  	mixed
	**/
	public static function get($show_type = 'all', $only_msg = false) 
	{
	
		if ($show_type == "all") {
			return self::$errors;
		}
		if (is_string($show_type)) {
			$show_type = explode(" ", $show_type);
		} 
		$new_errors = [];
		foreach (self::$errors as $error) {
			if (in_array($error[3], $show_type)) {
				if ($only_msg) {
					$new_errors[] = reset($error);
				} else {
					$new_errors[] = $error;
				}
			}
		}
		return $new_errors;
	}

	/**
	 * Stampa l'elenco degli errori in html
	 * @paran String $show_type Dice il tipo di errori da mostrare, se è all sono tutti altrimenti l'elenco dei tipi separati da spazio 
	 */
	public static function echo($show_type = 'all') 
	{
		$errors = PcErrors::get($show_type);
		?><div class="pina_errors_container"><?php
		$x = 0;
		foreach ($errors as $error) {
			if ($x > 50 && count($errors) - $x > 20) {
				?><div class="pina_error_box pina_error_color_info">other <?php echo count($errors) - $x; ?> errors</div><?php
				break;
			} else {
				?><div class="pina_error_box pina_error_color_<?php echo $error[3]; ?>"><?php echo $error[0]; ?></div><?php
			}
			$x++;
		}
		?></div><?php
	}
	/**
	 * ritorna l'html con  l'elenco degli errori e svuota l'array degli error
	 * @param String $show_type Dice il tipo di errori da mostrare, se è all sono tutti altrimenti l'elenco dei tipi separati da spazio 
	 */
	public static function get_html($show_type = 'all') 
	{
		ob_start();
		$show_errors = false;
		$errors = PcErrors::get($show_type);
		?><div class="pina_errors_container"><?php
		$x = 0;
		foreach ($errors as $error) {
			$show_errors = true;
			if ($x > 50 && count($errors) - $x > 20) {
				?><div class="pina_error_box pina_error_color_info">other <?php echo count($errors) - $x; ?> errors</div><?php
				break;
			} else {
			
				?><div class="pina_error_box pina_error_color_<?php echo $error[3]; ?>"><?php echo $error[0]; ?></div><?php
			}
			$x++;
		}
		?></div><?php
		self::$errors = [];
		if ($show_errors) {
			return ob_get_clean();
		} else {
			ob_get_clean();
			return "";
		}
	}
		
}