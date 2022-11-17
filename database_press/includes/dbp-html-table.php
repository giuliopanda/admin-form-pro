<?php
/**
 * Gestisco la tabella amministrativa
 * 
 * @package  DbPress
 */
namespace DbPress;

if (!defined('WPINC')) die;
class  Dbp_html_table {
	/**
	 * @var $table_name Il nome della tabella che si sta visualizzando
	 */
	var $table_name = "";
	/**
	 * @var Array $filter_columns L'array delle funzioni per personalizzare la visualizzazione delle colonne
	 */
	var $filter_columns = [];
	/**
	 * @var Array $filter_title L'array delle funzioni per personalizzare la visualizzazione dei titoli delle colonne
	 */
	var $filter_title   = [];
	/**
	 * @var String $table_class Una o più classi css da aggiungere al tag table 
	 */
	var $table_class = "";
	/**
	 *  * @var Array $add_attributes [key=>value, ...] sono gli attributi aggiuntivi
	 */
	var $add_attributes = [];
	
	/**
	 * Funziona solo se la classe viene estesa
	 * Quando viene renderizzata la tabella prima vengono cercate eventuali funzioni all'interno della classe 
	 * che personalizzino la visualizzazione della colonna stessa.
	 * @param Array $items
	 * @return void;
	 */
	private function set_items($items) {
		if (!is_array($items)) return;
		$first_row = (array)reset($items);
		foreach ($first_row as $key=>$item) {
			if (!array_key_exists($key, $this->filter_columns) && method_exists($this, "tr_".$key)) {
				$this->filter_columns[$key] = [$this, "tr_".$key];
			}
			if (!array_key_exists($key, $this->filter_title) && method_exists($this, "th_".$key)) {
				$this->filter_title[$key] = [$this, "th_".$key];
			}
		}
	}

	/**
	 * Ritorna l'html Un div con un messaggio se c'è stato un errore o se il totale dei risultati è 0 oppure la tabella
	 * @param \DbPress\dbp_model $table_model
	 * @return String
	 */
	public function template_render($table_model) {
		ob_start();
		if ($table_model->last_error !== false) {
			?><div class="dbp-alert-sql-error"><h2>Query error:</h2><?php echo $table_model->last_error; ?></div><?php 
		} else if($table_model->items != false && is_countable($table_model->items) && count($table_model->items) > 0) {
			$this->render($table_model->items, $table_model->sort, $table_model->filter);
		} else  {
			?><div class="dbp-alert-gray"><?php echo sprintf(__('MySQL returned %s rows. (Query took %s seconds.)', 'db_press'), $table_model->effected_row,  $table_model->time_of_query ); ?></div><?php 
		}
		if (count($this->add_attributes) > 0) { 
			?>
			<textarea style="display:none" name="dbp_extra_attr" id="dbp_extra_attr"><?php echo esc_textarea(base64_encode(json_encode($this->add_attributes))); ?></textarea>	
			<?php 
		}
		return ob_get_clean();
	}

	/**
	 * Stampa una tabella a partire da un array inserendo come nomi delle colonne le chiavi dell'array stesso
	 * Per personalizzare le colonne puoi creae i filtri
	 * call_gp_view_table_th_[nome_colonna]() : per cambiare il titolo
	 * call_gp_view_table_tr_[nome_colonna]($item) : per cambiare il valore della colonna $item è la riga
	 * @param Array $items [{info_schema}{item},{item},...] Accetta un array di oggetti o un array di array. La prima riga ha le informazioni della tabell
	 * @param Array $sorting ['key'=>'String','order'=>'ASC|DESC'] | false
	 * @param Array $searching [[op:'',column:'',value:''],...] | false
	 * @return void  
	 */
	public function render($items, $sorting = false, $searching = false) {
		if (!is_array($items) || count ($items) == 0) return;
		$this->set_items($items);
		$array_thead = array_shift($items);
		$max_input_vars = dbp_fn::get_max_input_vars();
		?>
		<script>var dbp_tb_id = [];var dbp_tb_id_del = [];</script>
		<table class="wp-list-table widefat striped dbp-table-view-list <?php echo esc_attr($this->table_class); ?>">
		<thead>
			<tr>
				<?php 
				foreach ($array_thead as $key => $value) {
					$row_sorting = $this->check_sorting($sorting, $value->sorting, $value->original_field_name);
					if ($value->type == "CHECKBOX"  ) {
						if ($max_input_vars - 50 > count($items)) {
							?>
							<th class="dbp-table-th dbp-th-dim-<?php echo strtolower($value->type); ?>" ><input type="checkbox" onclick="dbp_table_checkboxes(this)"></th>
							<?php
						}
					}  else { 
						?>
						<th class="dbp-table-th dbp-th-dim-<?php echo strtolower($value->type). $value->width; ?>">
							<?php 
							$dropdwon_html = "";
							if ($value->dropdown) {
								ob_start();
								$this->dropdown($value->field_key, $value->original_field_name, $value->name_column, $value->type, $value->original_table, $row_sorting, $searching);
								$dropdwon_html = ob_get_clean();
							}
							?>
							<div class="dbp-table-th-content">
								<?php if ($dropdwon_html != "") : ?>
									<div class="dbp-table-title<?php echo ($value->field_key != "" && ($row_sorting !== false || $searching !== false)) ? " js-dbp-table-show-dropdown": ""; ?>" data-fieldkey="<?php echo $value->name_column; ?>"><?php echo $value->name; ?></div> 
								<?php $this->icons($value->field_key, $value->original_field_name, $row_sorting, $searching, $value->name_column); ?>
								<?php else : ?>
									<div class="dbp-table-title"><?php echo $value->name; ?></div> 
								<?php endif; ?>
							</div>
							<?php
							if ($dropdwon_html != "") {
								?><div class="js-dbp-dropdown-header dbp-dropdown-header" id="dbp_dropdown_<?php echo $value->name_column; ?>"><?php
								echo $dropdwon_html;
								?></div><?php
							}
							?>
						</th>
						<?php 
					}
				} 
				?>
			</tr>
		</thead>
		<tbody>
		<?php 
		$id_base = "dbp_".uniqid();
		$count_row = 1;
		foreach ($items as $item) : ?>
			
			<tr id="<?php echo $id_base.'_'.$count_row; ?>">
				<?php 
				$count_row++;
				foreach ($array_thead as $key=>$setting) { 
					//$css_name = strtolower($setting->type);
					$formatting_class = dbp_fn::column_formatting_convert($setting->format_styles, $item->$key, '');
					$item->$key = dbp_fn::column_formatting_convert($setting->format_values, $item->$key, $item->$key);
					$css_name = (strlen(html_entity_decode($item->$key)) < 30 || in_array(strtolower($setting->type), ["number","checkbox", "wp_html"])) ? strtolower($setting->type) : 'text';
					if ($setting->type == "CHECKBOX" && $max_input_vars - 50 <= count($items) ) continue;
					?><td class="dbp-table-td<?php echo $setting->width.' '.$formatting_class; ?>"><div class="btn-div-td btn-div-td-<?php echo $css_name; ?>" data-dbp_rif_value="<?php echo esc_attr($key); ?>"><?php echo $item->$key; ?></div></td> <?php
				} 
				?>
			</tr>
		<?php endforeach; ?>
		</tbody>
		</table>
		<?php
	}

	/**
	 * Verifica se una colonna è ordinabile oppure no
	 * @param Array|false $sorting ['key'=>'String','order'=>'ASC|DESC'] | false
	 */
	private function check_sorting($global_sort, $column_sort, $field_key) {
		if (is_array($global_sort) && $global_sort['field'] == $field_key ) {
			return $global_sort;
		} else {
			return ($global_sort == true) ? $column_sort : $global_sort;
		}
		
	}

	/**
	 * Imposta una funzione per una colonna. Questa funzione verrà chiamata per renderizzare la colonna
	 * @param String $class
	 * @return void 
	 */
	public function add_table_class($class) {
		$this->table_class = $class;
	}

	/**
	 * Aggiunge parametri da passare nella paginazione, ordinamento o nei filtri in generale
	 * @param array $attributes [key=>value, ...]
	 * @return void
	 */
	public function add_extra_params($attributes) {
		if (is_countable($attributes)) {
			$this->add_attributes = array_merge($this->add_attributes, $attributes);
		}
	}

	/**
	 * Imposta una funzione per una colonna. Questa funzione verrà chiamata per renderizzare la colonna
	 */
	public function add_filter_column($column_name, $function) {
		$this->filter_columns[$column_name] = $function;
	}

	/**
	 * Imposta una funzione per renderizzare il titolo di una colonna.
	 */
	public function add_filter_title($column_name, $function) {
		$this->filter_title[$column_name] = $function;
	}


	/**
	 * Disegna le icone accanto al titolo delle colonne della tabella
	 * è statica perché può essere richiamata anche dall'esterno per disegnare una singola colonna se ad esempio viene chiamato un add_filter
	 * 
	 * @param String $alias_column il nome o l'alias della colonna
	 * @param String $original_field_name Il nome originale della colonna
	 * @return String  l'html dell'ordinamento delle colonne
	 */
	static function icons($alias_column, $original_field_name, $sort, $filter, $name_column) {
		if ($alias_column == "") return ;
		if ($sort != false && is_array($sort)) {
			if (strtolower(@$sort['field']) == strtolower($alias_column) || strtolower(@$sort['field']) == strtolower($original_field_name)) {
				if (strtolower(@$sort['order'])  == "asc") {
					?><span class="dashicons dashicons-arrow-down dbp-table-sort js-dbp-table-sort" data-dbp_sort_key="<?php echo esc_attr($alias_column); ?>" data-dbp_sort_order="DESC"></span><?php
				} else {
					?><span class="dashicons dashicons-arrow-up dbp-table-sort js-dbp-table-sort" data-dbp_sort_key="<?php echo esc_attr($alias_column); ?>" data-dbp_sort_order="ASC"></span><?php
				}
				
			}
		}
		if (is_array($filter)) {
			foreach ($filter as $f) {
				$between =  dbp_fn::is_correct_between_value($f['value'], $f['op']);
				if (strtolower($f['column']) == strtolower($original_field_name) && (($f['value'] != "" && strpos($f['op'], 'BETWEEN') === false) || $between !== false)) {
					?><span class="dashicons dashicons-filter js-click-dashicons-filter" data-rif="<?php echo esc_attr($name_column); ?>"></span><?php
					break;
				}
			}
		}
	}

	/**
	 * Disegna il popup al click del titolo delle colonne delle tabelle
	 * è statica perché può essere richiamata anche dall'esterno per disegnare una singola colonna se ad esempio viene chiamato un add_filter
	 * @param String $alias_column il nome o l'alias della colonna
	 * @param String $original_field_name table.orgname
	 * 
	 * @return String  l'html dell'ordinamento delle colonne
	 */
	static function dropdown($alias_column, $original_field_name, $name_column, $type, $original_table, $sort, $filter) {
		if ($alias_column == "") return "";
		if ($sort !== false)  {
			$sort_asc_class = 'js-dbp-table-sort dbp-dropdown-line-click';
			$sort_desc_class = 'js-dbp-table-sort dbp-dropdown-line-click';
			$sort_remove_class = 'dbp-dropdown-line-disable';
			if (is_array($sort) ) {
				if (strtolower(@$sort['field']) == strtolower($original_field_name)) {
					$sort_desc_class = (strtolower(@$sort['order'])  == "desc") ? 'dbp-dropdown-line-disable' : 'js-dbp-table-sort dbp-dropdown-line-click';	
					$sort_asc_class = (strtolower(@$sort['order'])  == "asc") ? 'dbp-dropdown-line-disable' : 'js-dbp-table-sort dbp-dropdown-line-click';
					$sort_remove_class = 'js-dbp-table-sort dbp-dropdown-line-click';
				}
			} 
		}
			// ricerca
		if ($filter !== false)  {	
			$symple_type = $type;
			$name_column = dbp_fn::clean_string($name_column);
			$def_op = "=";
			$def_input_value = "";
			list($html_select_array, $def_op) =  dbp_fn::get_array_for_select_in_drowdown_filter($type);
			$default_value = $def_input_value_2 = "";
			
			if (@is_array($filter)) {
				foreach ($filter as $f) {
					if ( strtolower($f['column']) == strtolower($original_field_name) && $f['value'] != "" && $f['value'] != "#AND#") {
						$default_value = $f['value'];
						$between = dbp_fn::is_correct_between_value($f['value'], $f['op']);
						if ($between !== false) {
							$def_input_value = $between[0];
							$def_input_value_2 = $between[1];
						} else if ($f['op'] != "IN" && $f['op'] != "NOT IN") {
							$def_input_value = $f['value'];
						} 
						$def_op = $f['op'];
					}
				}
			} 
		}
		if ($sort !== false || $filter !== false)  {	
			require DBP_DIR."/admin/partials/dbp-partial-dropdown.php";
		}
	}
}