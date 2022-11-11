<?php
/**
 * Gestisco la tabella amministrativa
 * 
 * @since      1.0.0
 *
 * @package  DbPress
 */
namespace DbPress;
if (!defined('WPINC')) die;

class  Dbp_html_simple_table {
	
	/**
	 * @var String $table_class Una o più classi css da aggiungere al tag table 
	 */
	var $table_class = "";

	/**
	 * Ritorna l'html Un div con un messaggio se c'è stato un errore o se il totale dei risultati è 0 oppure la tabella
	 * @param Class $table_model
	 * @return String
	 */
	public function template_render($table_model) {
		ob_start();
		if ($table_model->last_error !== false) {
			?><div class="dbp-alert-sql-error"><h2>Query error:</h2><?php echo $table_model->last_error; ?></div><?php 
		} else  {
			$this->render($table_model->items);
		}
		return ob_get_clean();
	}

	/**
	 * Stampa una tabella a partire da un array inserendo come nomi delle colonne le chiavi dell'array stesso
	 * Per personalizzare le colonne puoi creae i filtri
	 * call_gp_view_table_th_[nome_colonna]() : per cambiare il titolo
	 * call_gp_view_table_tr_[nome_colonna]($item) : per cambiare il valore della colonna $item è la riga
	 * @param Array $items [{info_schema}{item},{item},...] Accetta un array di oggetti o un array di array. La prima riga ha le informazioni della tabell
	 * @return void  
	 */
	public function render($items) {
		if (!is_array($items) || count ($items) == 0) return;
		$array_thead = array_shift($items);
		?>
		<table class="<?php echo esc_attr($this->table_class); ?>">
		<thead>
			<tr>
				<?php 
				foreach ($array_thead as $key => $value) {
					?>
					<th class="dbp-table-th">
						<div class="dbp-table-th-content">
							<div class="dbp-table-title"><?php echo $key; ?></div> 
						</div>
					</th>
					<?php 
				} 
				?>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($items as $item) : 
			if (is_array($item)) {
				$item = (object)$item;
			}
			?>
			<tr>
				<?php 
				foreach ($array_thead as $key=>$_) { 
					?><td class="dbp-table-td><div class="btn-div-td"><?php echo $item->$key; ?></div></td> <?php
				} 
				?>
			</tr>
		<?php endforeach; ?>
		</tbody>
		</table>
		<?php
	}

	
	/**
	 * Imposta una classe css per la tabella
	 * @param string $class
	 * @return void
	 */
	public function add_table_class($class) {
		$this->table_class = $class;
	}

}