<?php
/**
 * Gestisco il filtri e hook per il tab structure
 * 
 *
 * @package  DbPress
 */
namespace DbPress;


class  Dbp_loader_structure {

	public function __construct() {
		// Questa una chiamata che deve rispondere un csv
		add_action( 'wp_ajax_dbp_update_table_structure_test', [$this, 'dbp_update_table_structure_test']);
		add_action( 'wp_ajax_dbp_update_table_structure', [$this, 'dbp_update_table_structure']);
		// creo una nuova tabella		
		add_action( 'wp_ajax_dbp_create_table_structure', [$this, 'dbp_create_table_structure']);		
	}

	/**
	 * Testo il 'Cambio la struttura'
	 * REQUEST['table'];
	 * REQUEST['table_update']; [field_original_name, field_action, field_name, field_type ,field_length, null, primary]
	 */
	public function dbp_update_table_structure_test() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$result = ['result'=>'ok'];
		$model_structure = new Dbp_model_structure(sanitize_text_field($_REQUEST['table']));
		$model_structure->get_structure();
		array_shift($model_structure->items);
		$primary_name = "";
		foreach ($model_structure->items as $cs) {
			if ($cs->Key == "PRI") {
				$primary_name = $cs->Field;
			}
			$column[$cs->Field] = dbp_fn_structure::convert_show_column_mysql_row_to_form_data($cs);
		}
		$table_temp = dbp_fn_structure::create_temporaly_table_from(sanitize_text_field($_REQUEST['table']));
		$model_structure_temp = new Dbp_model_structure($table_temp);
		$pre_edit = dbp_fn_structure::load_rows($table_temp, $primary_name);
		$req_update = Dbp_fn::sanitize_text_recursive($_REQUEST['table_update']);
		$row_table = [];
		$position = "FIRST!"; // questo servirà per l'ordinamento delle colonne. Ancora non è usato!.
		// Aggiorno tutte le colonne sulla tabella temporanea
		foreach ($req_update['field_original_name'] as $key=>$field_update) {
			if (($field_update != "" && array_key_exists($field_update, $column)) || $req_update['field_action'][$key] == "add") {
				if ( $req_update['field_action'][$key] == "add") {
					$field_update = uniqid();
				}
				if ($field_update != $req_update['field_name'][$key]) {
					$req_update['field_name'][$key] = dbp_fn_structure::clean_column_name($req_update['field_name'][$key]);
				}
				if ($req_update['field_action'][$key] == "add") {
					// nuova colonna!
					$row_table[$field_update] = (object)['action'=>__(sprintf('Add new column: %s', $req_update['field_name'][$key]), 'db_press'),  'curr_action' =>'NEW',  'fields_errors'=>[]];
					$row_table[$field_update]->sql = $model_structure_temp->get_sql_add_column($column, $req_update, $key, $position);
				} else {
					// aggiorno o elimino la colonna
					$row_table[$field_update] = (object)['action'=>'', 'curr_action' =>''];
					if ($req_update['field_action'][$key] == "delete") {
						$row_table[$field_update]->sql =$model_structure_temp->get_sql_drop_column($column[$field_update]);
						$row_table[$field_update]->action = 'DELETE ' . $field_update;
						$row_table[$field_update]->curr_action = "DELETE";
					} else {
						$row_table[$field_update]->sql = $model_structure_temp->get_sql_alter_column($column, $field_update, $req_update, $key, $position);
						$row_table[$field_update]->action = 'UPDATE ' . $field_update;
						$row_table[$field_update]->curr_action = "UPDATE";
					}
				}
				$row_table[$field_update]->new_field =  $req_update['field_name'][$key];
				// testo la query sulla tabella temporanea
				if (count ($row_table[$field_update]->sql) > 0) {
					$row_table[$field_update]->query_error = "";
					$row_table[$field_update]->query_result = true;
					foreach ($row_table[$field_update]->sql as &$sql) {
						list($query_result, $query_error)  = $model_structure_temp->exec_query($sql);
						if ($query_error != "") {
							$row_table[$field_update]->query_error = $query_error;
							break;
						}
						if ($query_result != true) {
							$row_table[$field_update]->query_result = $query_result;
						}
					}
					$row_table[$field_update]->sql = str_replace($table_temp, sanitize_text_field($_REQUEST['table']), implode ("<br>", $row_table[$field_update]->sql));
				} else {
					unset($row_table[$field_update]);
				}
				if ($req_update['field_name'][$key] != "") {
					$position = $req_update['field_name'][$key];
				}
			} 
			
		}
		
		$new_primary_name = $primary_name;
		$model_structure_temp->get_structure();
		array_shift($model_structure_temp->items);
		foreach ($model_structure_temp->items as $cs) {
			if ($cs->Key == "PRI") {
				$new_primary_name = $cs->Field;
			}
		}
		
		// verifico che i dati non siano cambiati
		$post_edit = dbp_fn_structure::load_rows($table_temp, $new_primary_name);
		foreach ($row_table as $cfield => &$ctype) {
			$ctype->count_pre = count($pre_edit);
			$ctype->fields_errors = [];
			if ($new_primary_name != $primary_name) {
				$ctype->query_msg = __('If you change the primary key I cannot check if there will be any data loss', 'db_press');
			} else {
				if (isset($ctype->curr_action) && $ctype->curr_action == "UPDATE") {
					foreach ($pre_edit as $key_pre=>$val_pre) {
						//TODO se cambia il nome della colonna bisogna fixare 
						if ($ctype->query_error != "") continue;
						$cfield2 = $ctype->new_field;
						if (@$val_pre->$cfield != @$post_edit[$key_pre]->$cfield2) {
							if (count($ctype->fields_errors) > 49) {
								break;
							} else {
								$pre = htmlentities($val_pre->$cfield);
								$pre = (substr($pre,0,100) != $pre ) ? substr($pre,0,97)."..." : substr($pre,0,100);
								if (!isset($post_edit[$key_pre]->$cfield2)) {
									$post = "";
								} else {
									$post = htmlentities($post_edit[$key_pre]->$cfield2);
									$post = (substr($post,0,100) != $post ) ? substr($post,0,97)."..." : substr($post,0,100);
								}
								$ctype->fields_errors[] = [$pre, $post];
							}
						}
					}
				} 
			}
		}
		
		dbp_fn_structure::drop_temporaly_table($table_temp);
		$result['row_table'] = $row_table;
		wp_send_json($result);
		die;
	}


	/**
	 * Cambio la struttura
	 * $_REQUEST['table'];
	 * $_REQUEST['table_update']; [field_original_name, field_action, field_name, field_type ,field_length, null, primary]
	 */
	public function dbp_update_table_structure() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$table = sanitize_text_field($_REQUEST['table']);
		$model_structure = new Dbp_model_structure($table);
		$table_link = add_query_arg(['section'=>'table-browse', 'table'=>$model_structure->get_table_name()], admin_url("admin.php?page=database_press"));
		$result = ['result'=>'ok','table_link'=>$table_link];
		$model_structure->get_structure();
		array_shift($model_structure->items);
		
		foreach ($model_structure->items as $cs) {
			if ($cs->Key == "PRI") {
				$primary_name = $cs->Field;
			}
			$column[$cs->Field] = dbp_fn_structure::convert_show_column_mysql_row_to_form_data($cs);
		}
		$table_options = dbp_fn::get_dbp_option_table($table);
		
		$row_table = [];
		if ($table_options['status'] == "DRAFT") {
			$model_structure= new Dbp_model_structure($table);
			$req_update = Dbp_fn::sanitize_text_recursive($_REQUEST['table_update']);
			$position = "FIRST!"; // questo servirà per l'ordinamento delle colonne. Ancora non è usato!.
			// Aggiorno tutte le colonne sulla tabella
			foreach ($req_update['field_original_name'] as $key=>$field_update) {
				if (($field_update != "" && array_key_exists($field_update, $column)) || $req_update['field_action'][$key] == "add") {
					if ( $req_update['field_action'][$key] == "add") {
						$field_update = uniqid();
					}
					if ($field_update != $req_update['field_name'][$key]) {
						$req_update['field_name'][$key] = dbp_fn_structure::clean_column_name($req_update['field_name'][$key]);
					}
					if ($req_update['field_action'][$key] == "add") {
						// nuova colonna!
						$row_table[$field_update] = (object)['action'=>__(sprintf('Add new column: %s', $req_update['field_name'][$key]), 'db_press'),  'curr_action' =>'NEW',  'fields_errors'=>[]];
						$row_table[$field_update]->sql = $model_structure->get_sql_add_column($column, $req_update, $key, $position);
					} else {
						// aggiorno o elimino la colonna
						$row_table[$field_update] = (object)['action'=>'', 'curr_action' =>''];
						if ($req_update['field_action'][$key] == "delete") {
							$row_table[$field_update]->sql =$model_structure->get_sql_drop_column($column[$field_update]);
							$row_table[$field_update]->action = 'DELETE ' . $field_update;
							$row_table[$field_update]->curr_action = "DELETE";
						} else {
							$row_table[$field_update]->sql = $model_structure->get_sql_alter_column($column, $field_update, $req_update, $key, $position);
							$row_table[$field_update]->action = 'UPDATE ' . $field_update;
							$row_table[$field_update]->curr_action = "UPDATE";
						}
					}
					$row_table[$field_update]->new_field =  $req_update['field_name'][$key];
					// Eseguo la query sulla tabella temporanea
					if (count ($row_table[$field_update]->sql) > 0) {
						$row_table[$field_update]->query_error = "";
						$row_table[$field_update]->query_result = true;
						foreach ($row_table[$field_update]->sql as &$sql) {
							list($query_result, $query_error)  = $model_structure->exec_query($sql);
							if ($query_error != "") {
								$row_table[$field_update]->query_error = $query_error;
								break;
							}
							if ($query_result != true) {
								$row_table[$field_update]->query_result = $query_result;
							}
						}
						$row_table[$field_update]->sql = implode ("<br>", $row_table[$field_update]->sql);
					} else {
						unset($row_table[$field_update]);
					}
					if ($req_update['field_name'][$key] != "") {
						$position = $req_update['field_name'][$key];
					}
				}			
			}		
			// se è stato cambiato il nome della tabella
			if (isset($_REQUEST['structure_table_name']) && $_REQUEST['structure_table_name'] != $_REQUEST['table']) {
				$new_name = dbp_fn_structure::clean_column_name($_REQUEST['structure_table_name']);
				$sql = 'RENAME TABLE `'.Dbp_fn::sanitize_key($_REQUEST['table']).'` TO `'.Dbp_fn::sanitize_key($new_name). '`;';
				$row_table_temp = (object)['action'=>'RENAME TABLE', 'curr_action' =>'renametable', 'sql'=>$sql, 'new_table'=>Dbp_fn::sanitize_key($new_name), 'old_table'=>sanitize_text_field($_REQUEST['table'])];
				list($query_result, $row_table_temp->query_error)  = $model_structure->exec_query($sql);
				if ($row_table_temp->query_error != "") {
					$row_table_temp->query_result = 0;
				} else {
					$row_table_temp->query_result = true;
					// cambio il nome dell'option;
					delete_option('dbp_'.$table);
					$table = $new_name;
				}
				$row_table[] = $row_table_temp;
			}
		}	
		// FINE table_options['status'] == DRAFT
		// aggiorno le opzioni
		dbp_fn::update_dbp_option_table_status($table, sanitize_text_field($_REQUEST['options']['status']), sanitize_textarea_field($_REQUEST['options']['description']));
		//$table_options = $_REQUEST['options'];
		$result['row_table'] = $row_table;
		wp_send_json($result);
		die;
	}

	/**
	 * Cambio la struttura
	 * _REQUEST['table'];
	 * _REQUEST['table_update']; [field_original_name, field_action, field_name, field_type ,field_length, null, primary]
	 */
	public function dbp_create_table_structure() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		// TODO manca l'update dei post

		$model_structure = new Dbp_model_structure($_REQUEST['structure_table_name']);
		$table_link = add_query_arg(['section'=>'table-browse', 'table'=>$model_structure->get_table_name()], admin_url("admin.php?page=database_press"));
		$result = ['result'=>'ok','table_link'=>$table_link];
		$row_table = (object)[];
	
		$table_update = Dbp_fn::sanitize_text_recursive($_REQUEST['table_update']);
		$row_table->query_result = $model_structure->sql_create_table_row($table_update);
		// REQUEST[structure_table_name] = nome tabella
		// row: field_name, field_type, field_length, attributes, default, null primary (f|t)
		if ($row_table->query_result) {
			dbp_fn::update_dbp_option_table_status($model_structure->get_table_name(), 'DRAFT', sanitize_textarea_field($_REQUEST['options']['description']));
		} else {
			$result['table_link'] = "";
			$result['ok'] = "no";
		}

		$row_table->query_error = ($row_table->query_result) ? '' : $model_structure->last_error;
		$row_table->action =" CREATE TABLE `".$model_structure->get_table_name()."`";
		$row_table->sql  = "";
		$result['row_table'] = [$row_table];
		wp_send_json($result);
		die;
	}
}