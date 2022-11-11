<?php
/**
 * Gestisco il filtri e hook (prevalentemente le chiamate ajax amministrative)
 * php version 7.2
 * 
 * @category Plugins
 * @package  DbPress
 * @author   Giulio Pandolfelli <giuliopanda@gmail.com>
 * @license  GPL v2 or later
 * @link     https://www.gnu.org/licenses/gpl-2.0.html
 * 
 */
namespace DbPress;

class  Dbp_loader {
	/**
	 * @var Object $saved_queries le ultime query salvate per tipo
	 */
	public static $saved_queries;

	public function __construct() {
		self::$saved_queries = (object)[];
		add_action( 'admin_menu', [$this, 'add_menu_page'],11 );
		
		add_action('admin_enqueue_scripts', [$this, 'codemirror_enqueue_scripts']);
		// L'ajax per la richiesta dell'elenco dei valori unici per mostrarli nei filtri di ricerca
		add_action( 'wp_ajax_dbp_distinct_values', [$this, 'dbp_distinct_values']);
		add_action( 'wp_ajax_dbp_autocomplete_values', [$this, 'dbp_autocomplete_values']);
		// L'ajax per l'esecuzione delle multiqueries quando sono troppe
		add_action( 'wp_ajax_dbp_multiqueries_ajax', [$this, 'dbp_multiqueries_ajax']);
		// l'ajax per vedere il dettaglio di una sola riga estratta da una query
		add_action( 'wp_ajax_dbp_view_details', [$this, 'dbp_view_details']);
		// l'ajax per confermare l'eliminazione di uno o più record 
		add_action( 'wp_ajax_dbp_delete_confirm', [$this, 'dbp_delete_confirm']);
		// l'ajax per l'edit record
		add_action( 'wp_ajax_dbp_edit_details_v2', [$this, 'dbp_edit_details_v2']);
		// l'ajax per il salvataggio di un record
		add_action( 'wp_ajax_dbp_save_details', [$this, 'dbp_save_details']);
		// l'ajax per generare le query per eliminare tutti i record di una query
		add_action( 'wp_ajax_dbp_check_delete_from_sql', [$this, 'dbp_check_delete_from_sql']);
		// l'ajax preparare gli id da rimuovere successiva a dbp_check_delete_from_sql
		add_action( 'wp_ajax_dbp_prepare_query_delete', [$this, 'prepare_query_delete']);
		// Dopo aver preparato i dati da rimuovere, li rimuovo tutti.
		add_action( 'wp_ajax_dbp_sql_query_delete', [$this, 'sql_query_delete']);

		// l'ajax per generare il csv che salva sui file temporanei e poi li puoi scaricare
		add_action( 'wp_ajax_dbp_download_csv', [$this, 'dbp_download_csv']);
		// l'ajax mostra l'elenco delle colonne di una query pee permettere di scegliere quali visualizzare
		add_action( 'wp_ajax_dbp_columns_sql_query_edit', [$this, 'columns_sql_query_edit']);
		// l'ajax Una volta scelto l'elenco delle colonne da visualizzare modifica la query con il nuovo select
		add_action( 'wp_ajax_dbp_edit_sql_query_select', [$this, 'edit_sql_query_select']);
		// l'ajax viasualizzare la form per fare un marge con un'altra tabella
		add_action( 'wp_ajax_dbp_merge_sql_query_edit', [$this, 'merge_sql_query_edit']);
		// trova le colonne di una tabella
		add_action('wp_ajax_dbp_merge_sql_query_get_fields', [$this, 'merge_sql_query_get_fields']);
		// Genera la query con il nuovo left join
		add_action('wp_ajax_dbp_edit_sql_query_merge', [$this, 'edit_sql_query_merge']);
		// Apre la sidebar per aggiungere i metadata e seleziona la tabella
		add_action('wp_ajax_dbp_metadata_sql_query_edit', [$this, 'metadata_sql_query_edit']);
		// Sempre per i metadata trova i campi da visualizzare
		add_action('wp_ajax_dbp_metadata_sql_query_edit_step2', [$this, 'metadata_sql_query_edit_step2']);
		// Genera la query con l'aggiunta dei metadata
		add_action('wp_ajax_dbp_edit_sql_addmeta', [$this, 'edit_sql_addmeta']);
		
		// Verifico una query mentre la si sta scrivendo
		add_action('wp_ajax_dbp_check_query', [$this, 'check_query']);

		// Questa una chiamata che deve rispondere un csv
		add_action( 'admin_post_dbp_download_multiquery_report', [$this, 'dbp_download_multiquery_report']);
		// Nell'init (backend gestisco eventuali redirect)
		add_action('init',  [$this, 'template_redirect']);
		add_action('init',  [$this, 'init_get_msg_cookie'] );
	
		//add_filter('query_vars', [$this, 'add_rewrite_rule']);
		add_action ('wp_ajax_dbp_sql_test_replace', [$this,'sql_test_replace']);
		
		add_action ('wp_ajax_dbp_sql_search_replace', [$this,'sql_search_replace']);
		//registro il voto.
		add_action ('wp_ajax_dbp_record_preference_vote', [$this,'record_preference_vote']);

		// nel tab form nei lookup c'è il bottone test query
		add_action ('wp_ajax_dbp_form_lookup_test_query', [$this,'form_lookup_test_query']);


		add_action('in_admin_header', function () {
			if (is_admin() && isset($_REQUEST['page'])  && 
				in_array($_REQUEST['page'],['database_press']) ) { 
				remove_all_actions('admin_notices');
				remove_all_actions('all_admin_notices');
			}
		}, 1000);

		if (is_admin())  {
			// Memorizzo le ultime query eseguite per tipo (insert, delete) altrimenti quando provo a mostrarle usando last_query mi capita di vedere una query al posto di un'altra.
			add_filter ( 'query', [$this, 'store_query'] );
		}
		
		require_once DBP_DIR . "includes/dbp-loader-information-schema.php";
		$database_press_loader_structure = new Dbp_loader_information_schema();

		require_once DBP_DIR . "includes/dbp-loader-structure.php";
		$database_press_loader_structure = new Dbp_loader_structure();

		require_once DBP_DIR . "includes/dbp-loader-import.php";
		$database_press_loader_import = new Dbp_loader_import();
					
		add_filter('dbp_table_status', [$this, 'publish_wp_tables'], 2, 2);
	}

	/**
	 * Aggiunge la voce di menu e carica la classe che gestisce la pagina amministrativa
	 */
	public function add_menu_page() {
		require_once DBP_DIR . "admin/class-dbp-table-admin.php";
		$db_admin = new database_press_admin();
		add_submenu_page(  'admin_form', 'Database', 'Database', 'manage_options', 'database_press', [$db_admin, 'controller']);
	}
	/**
	 * Gli script per far funzionare l'editor
	 */
	public function codemirror_enqueue_scripts() {
		if ((isset($_REQUEST['page'] ) && $_REQUEST['page'] != "database_press") || !isset($_REQUEST['page'])) return;
		if (!current_user_can('administrator')) die('no_access');
		if ( ! class_exists( '_WP_Editors', false ) ) {
			require ABSPATH . WPINC . '/class-wp-editor.php';
		}
		wp_enqueue_editor();

		$settings = wp_get_code_editor_settings([]);
		// copio wp_enqueue_code_editor per escludere 'false' === wp_get_current_user()->syntax_highlighting
		
		if ( empty( $settings ) || empty( $settings['codemirror'] ) ) {
			return;
		}

		wp_enqueue_script( 'code-editor' );
		wp_enqueue_style( 'code-editor' );

		wp_enqueue_script( 'csslint' );
		wp_enqueue_script( 'htmlhint' );
		wp_enqueue_script( 'jshint' );
		wp_add_inline_script( 'code-editor', sprintf( 'jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode( $settings ) ) );
		
	}

	/**
	 * L'ajax per la richiesta dell'elenco dei valori unici per mostrarli nei filtri di ricerca 
	 *  $_REQUEST = array(5) {
	 *	     ["sql"]=> string(26) "SELECT * FROM  `wpx_posts`"["rif"]=> string(21) "wpx_posts_post_author", ["column"]=> string(25) "`wpx_posts`.`post_author`", ["action"]=> string(19) "dbp_distinct_values", ["table"]=> string(9) "wpx_posts"
	 *  }
	 *  [{c=>il testo del campo distinct, p=>l'id se serve di filtrare per id oppure -1 n il numero di volte che compare},{}] | false if is not a select query 
	 *
	 */
	public function dbp_distinct_values() {
		global $wpdb;
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		if (!isset($_REQUEST['column'])) {
			wp_send_json(['error' => 'no_column_selected']);
			die();
		}
		$table = isset($_REQUEST['table']) ? sanitize_text_field($_REQUEST['table']) : '';
		$model = new Dbp_model($table); // è la tabella a cui appartiene il singolo campo!
		if (isset($_REQUEST['sql'])) {
			$req_sql = html_entity_decode(dbp_fn::req('sql'));
			$model->prepare($req_sql);
			if (!isset($_REQUEST['dbp_id'])) {
				$model->removes_column_from_where_sql(sanitize_text_field($_REQUEST['column']));
			}
		}
		$filter_distinct = (isset($_REQUEST['filter_distinct'])) ? sanitize_textarea_field($_REQUEST['filter_distinct']) : '';
		$result = $model->distinct(sanitize_text_field($_REQUEST['column']), $filter_distinct);
		
		$error = "";
		$count = 0;
		if ($model->last_error != "" || !is_countable($result)) {
			$error = __('Option not available for this query '.$model->last_error." ".$model->get_current_query(),'db_press');
			$result = [];
		} else if ( count($result) >= 1000) {
			//$error = __('The column has too many values to show.<br><i style="font-size:.8em">You can use the field above to filter the results</i>','db_press');
			$count = count ($result);
			if (count($result) >= 5000) {
				$count = "5000+";
			} 
			$result = [];
			
		} else {
			$count = count ($result);
		}
		
		wp_send_json(['error' => $error, 'result' => $result, 'rif' => sanitize_text_field($_REQUEST['rif']), 'count'=>$count, 'filter_distinct'=> $filter_distinct ]);
		die();
	}
	
	/**
	 * L'ajax per la richiesta dell'elenco dei valori unici per mostrarli nei filtri di ricerca 
	 *  $_REQUEST = array(5) {
	 *	     ["sql"]=> string(26) "SELECT * FROM  `wpx_posts`"["rif"]=> string(21) "wpx_posts_post_author", ["column"]=> string(25) "`wpx_posts`.`post_author`", ["action"]=> string(19) "dbp_distinct_values", ["table"]=> string(9) "wpx_posts"
	 *  }
	 *  [{c=>il testo del campo distinct, p=>l'id se serve di filtrare per id oppure -1 n il numero di volte che compare},{}] | false if is not a select query 
	 */
	public function dbp_autocomplete_values() {
		global $wpdb;
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		if (!isset($_REQUEST['params'])) {
			wp_send_json(['error' => 'no_params_selected']);
			die();
		}
		$params = array_map('sanitize_textarea_field', $_REQUEST['params']);
		$result = [];
		$error = "";
		$filter_distinct = (isset($_REQUEST['filter_distinct'])) ? sanitize_textarea_field($_REQUEST['filter_distinct']) : '';
		$model = new Dbp_model($params['table']); // è la tabella a cui appartiene il singolo campo!
		//TODO questo lo prendo da dbp_id se è presente altrimenti dalla query
		if (isset($_REQUEST['sql'])) {

		$model->prepare(wp_kses_post((html_entity_decode($_REQUEST['sql']))));
		}
		$result = $model->distinct($params['column'], $filter_distinct);
		
		$error = "";
		$count = 0;
		if ($model->last_error != "" || !is_countable($result)) {
			$error = __('Option not available for this query','db_press');
			$result = [];
		} else if ( count($result) >= 1000) {
			//$error = __('The column has too many values to show.<br><i style="font-size:.8em">You can use the field above to filter the results</i>','db_press');
			$count = count ($result);
			if (count($result) >= 5000) {
				$count = "5000+";
			} 
			$result = [];
			
		} else {
			$count = count ($result);
		}
		
		wp_send_json(['error' => $error, 'result' => $result, 'rif' => sanitize_text_field($_REQUEST['rif']), 'count'=>$count, 'filter_distinct'=> $filter_distinct]);
		die();
	}
	
	/**
	 * L'ajax per la eseguire altre multiqueries. Output Json
	 * I dati vengono ricevuti dal $_REQUEST { ["action"]=> string(21) "dbp_multiqueries_ajax" ["filename"]=> string(13) "612245f28a809"} 
	 */
	public function dbp_multiqueries_ajax() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$filename = dbp_fn::sanitaze_request('filename','');
		$ignore_errors = dbp_fn::req('ignore_errors', false, 'boolean');
		$ris = dbp_fn::execute_multiqueries($filename, $ignore_errors);
		unset($ris['model']);
		unset($ris['items']);
		wp_send_json($ris);
	}

	/**
	 * Scarica il report delle multiqueri
	 */
	public function dbp_download_multiquery_report() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$temporaly = new Dbp_temporaly_files();
		$fnid = dbp_fn::sanitaze_request('fnid','');
		$data = $temporaly->read($fnid);
	//	print $temporaly->get_dir();
		$query_to_execute = $temporaly->read($data['queries_filename']);
		foreach ($data['report_queries'] as $key=>$query) {
			$data['report_queries'][$key] = [@$query[0], @$query[1][1]["effected_row"], @$query[2] , @$query[3] ];
		}
	
		if ($temporaly->last_error == "" && $query_to_execute != false && (is_array($query_to_execute) || is_object($query_to_execute))) {
			foreach ($query_to_execute as $query) {
				$data['report_queries'][] =  [$query[0], 0, 0, __('Query not executed', 'db_press')];
			}
		}

		dbp_fn::export_data_to_csv($data['report_queries'], sanitize_text_field($_REQUEST['fnid']),  ';', '"',  false);
	}

	/**
	 * I casi del template redirect
	 */
	public function template_redirect() {
		if (is_admin() && isset($_REQUEST['page'])  && $_REQUEST['page'] == 'database_press') {
			if (isset($_REQUEST['section']) && ($_REQUEST['section'] == 'table-structure') ) {
				if (!isset($_REQUEST['action']) && !isset($_REQUEST['table'])) {
					wp_redirect(admin_url('?page=database_press&section=information-schema') );
					die();
				}
			}
			if (isset($_REQUEST['section']) && ($_REQUEST['section'] == 'table-browse') ) {
				if (!isset($_REQUEST['table']) || $_REQUEST['table'] == "") {
					if (!$_REQUEST['custom_query']) {
						wp_redirect(admin_url('?page=database_press&section=information-schema') );
						die();
					}
				}
			}

		}
	}
   
	/**
	 * Restituisce il risultato di una query per una riga
	 */
	public function dbp_view_details() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$json_send = ['error' => '', 'items' => ''];
		
		if (!isset($_REQUEST['ids']) || !is_countable($_REQUEST['ids'])) {
			$json_send['error'] = __('I have not found any results. Verify that the primary key of each selected table is always displayed in the MySQL SELECT statement.', 'db_press');
			wp_send_json($json_send);
			die();
		}
		$table_model = $this->get_table_model_for_sidebar();
		$table_model->remove_limit();
		$table_items = $table_model->get_list();
		//var_dump ($table_items);
		if (is_countable($table_items) && count($table_items) == 2) {
			$item = array_pop($table_items);
			foreach ($item as &$val) {
				$val = dbp_fn::format_single_detail_value($val);
			}
			$json_send['items'] = [$item]; 	
		} else if (is_countable($table_items) && count($table_items) > 2 && count($table_items) < 200) {
			// Sono più risultati quindi raggruppo i risultati per tabella e mostro solo i gruppi differenti. 
			$items = dbp_fn::convert_table_items_to_group($table_items);
			if (count($items) > 1) {
				$json_send['error'] = __('The query responded with multiple lines. Verify that the primary key of each selected table is always displayed in the MySQL SELECT statement', 'db_press');
			}
		
			$json_send['items'] = $items;
		} else if (is_countable($table_items) && count($table_items) > 2 && count($table_items) >= 200) {
			$json_send['error'] = __('I am sorry but I cannot show the requested details because I have found more than 200 results!. Verify that the primary key of each selected table is always displayed in the MySQL SELECT statement. Check that the tables have a unique auto increment primary key.', 'db_press');
		}else {
			$json_send['error'] = __('Strange, I have not found any results. Verify that the primary key of each selected table is always displayed in the MySQL SELECT statement. Check that the tables have a unique auto increment primary key.', 'db_press');
		}
		wp_send_json($json_send);
		die();
	}

	/**
	 * Genera i parametri per la creazione della form (add o edit) nella sidebar
	 */
	public function dbp_edit_details_v2() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$json_send = ['error' => '', 'items' => ''];
		if (isset($_REQUEST['div_id'])) {
			$json_send['div_id'] = $_REQUEST['div_id'];
		}
		
		if (!isset($_REQUEST['dbp_id']) && !isset($_REQUEST['sql'])) {
			$json_send['error'] = __('There was an unexpected problem, please try reloading the page.', 'db_press');
			wp_send_json($json_send);
			die();
		}

		// aggiungo eventuali dbp_extra_attr (deve essere una funzione!)
		if (isset($_REQUEST['dbp_extra_attr'])) {
			$extra_attr = json_decode(base64_decode($_REQUEST['dbp_extra_attr']), true);
			if (json_last_error() == JSON_ERROR_NONE) {
				if (isset($extra_attr['request'])) {
					foreach ($extra_attr['request'] as $key=>$val) {
						if (!isset($_REQUEST[$key])) {
							$_REQUEST[sanitize_text_field($key)] = sanitize_textarea_field($val);
						}
					}
				}
				if (isset($extra_attr['params'])) {
				$params = (array)PinaCode::get_var('params');
				$params = array_merge($extra_attr['params'], $params);
				PinaCode::set_var('params', $params);
				}
				if (isset($extra_attr['data'])) {
					pinacode::set_var('data', $extra_attr['data']);
				}
			} 
		} 

		$json_send['edit_ids'] = dbp_fn::req('ids', 0);
		
		if (dbp_fn::req('sql') != "") {
            $form = new Dbp_class_form(dbp_fn::req('sql'));
			$json_send['sql'] = dbp_fn::req('sql');
        } else {
            $form = new Dbp_class_form(dbp_fn::req('dbp_id'));
			$json_send['dbp_id'] = dbp_fn::req('dbp_id');
        }
		if (isset($_REQUEST['ids']) && is_countable($_REQUEST['ids'])) {
			$items = $form->get_data(dbp_fn::req('ids'));
		} else {
			$items = [];
		}

        list($settings, $table_options) = $form->get_form();
        $json_send['items'] = $form->convert_items_to_groups($items, $settings, $table_options);
		$json_send['params'] = $form->data_structures_to_array($settings);
		$json_send['table_options'] = $form->data_structures_to_array($table_options);	
		$json_send['buttons'] =  $form->get_btns_allow(); //['save'=>false,'delete'=>true];
		wp_send_json($json_send);
		die();
	}

	/**
	 * Salva un record
	 */
	public function dbp_save_details() {
		global $wpdb;
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$json_result = ['reload'=>0,'msg'=>'','error'=>''];
		if (isset($_REQUEST['div_id'])) {
			$json_result['div_id'] = sanitize_text_field($_REQUEST['div_id']);
		}
		$queries_executed = [];
		$query_to_execute = [];
		$request_edit_table = dbp_fn::req('edit_table',[]);

		// se è una lista ok, altrimenti solo gli amministratori possono salvare dati
		if( !current_user_can('administrator') ) {
			$json_result['result'] = 'nook';
			$json_result['error'] = __('You do not have permission to access this content!', 'db_press');
			wp_send_json($json_result);
			die();
		}
		
		
		foreach ($request_edit_table as $form_value) {
			$alias_table = "";
			foreach ($form_value as $table=>$rows) {
				//print $table;
				$primary_key = dbp_fn::get_primary_key(sanitize_text_field($table));
				$primary_field = $fields_names = [];
				
				foreach ($rows as $key=>$row) {
					$key = sanitize_text_field($key);
					if ($key == $primary_key) {
						$primary_field = $row;
					} else {
						$fields_names[$key] = $row;
					}
				}
				// ciclo per più query.
				$exists = 0;
				$primary_value = "";
				// ciclo quante volte si ripete la chiave primaria per la tabella (ogni volta è una nuova riga)
				foreach ($primary_field as $key => $pri) {
					$sql = [];
					$exists = 0;
					$primary_value = $pri;
					// l'alias della tabella sta in un campo nascosto e serve per definire i pinacode
					if (isset($fields_names["_dbp_alias_table_"][$key]))  {
						$alias_table = $fields_names["_dbp_alias_table_"][$key];
					}
					if ($alias_table == "") {
						$alias_table = $table;
					}
					//print "ALIAS TABLE:" . $alias_table;
					$pri_name = dbp_fn::clean_string($alias_table).'.'.$primary_key;
					PinaCode::set_var($pri_name, $primary_value);
					// preparo i campi da salvare 
					// Setto le variabili per i campi calcolati // DA TESTARE
					foreach ($fields_names as $kn=>$fn) {
						if ($kn != "_dbp_alias_table_") {
							// ?
							if (is_countable($fn[$key])) {
								$fn[$key] = maybe_serialize($fn[$key]);
							}
							$fn[$key] = wp_kses_post( wp_unslash( $fn[$key] ));
							//$sql[$kn] = $fn[$key];

							PinaCode::set_var(dbp_fn::clean_string($alias_table).".".$kn, $fn[$key]);
							PinaCode::set_var("data.".$kn, $fn[$key]);
						}
					}
				
					foreach ($fields_names as $kn=>$fn) {
						if ($kn != "_dbp_alias_table_") {
							$sql[$kn] = wp_kses_post( wp_unslash( $fn[$key] ));
						}
					}
					
					// se primary key è un valore 
					if ($primary_value != "") {
						$exists = $wpdb->get_var('SELECT count(*) as tot FROM `'.Dbp_fn::sanitize_key($table).'` WHERE `'.Dbp_fn::sanitize_key($primary_key).'` = \''.absint($primary_value).'\'');
						if ($exists == 0) {
							$sql[$primary_key] = $primary_value;
						}
					} else {
						$sql[$primary_key] = $primary_value;
					}
					$setting = false;
					$option = false;
					
					if ($exists == 1) {
						if (count($sql) > 0) {
							$query_to_execute[] = ['action'=>'update', 'table'=>$table, 'sql_to_save'=>$sql, 'id'=> [$primary_key=>$primary_value], 'table_alias'=>$alias_table, 'pri_val'=>$primary_value, 'pri_name'=>$primary_key, 'setting' => $setting];
							
						}
					} else if ($exists == 0) {
						$json_result['reload'] = 1;
						if (isset($sql[$primary_key])) {
							unset($sql[$primary_key]);
						}
						
						if (count($sql) > 0 &&  !(isset($sql['_dbp_leave_empty_']) && $sql['_dbp_leave_empty_'] == 1 )) {
							$query_to_execute[] = ['action'=>'insert', 'table'=>$table, 'sql_to_save'=>$sql, 'table_alias'=>$alias_table, 'pri_val'=>$primary_value, 'pri_name'=>$primary_key, 'setting' => $setting];
						}
					} else {
						// ha trovaro risultati doppi?
					}
				}
				//die($pri);
			}
		}

		$ris = Dbp_class_form::execute_query_savedata($query_to_execute);

		foreach ($ris as $r) {
			if (!($r['result'] == true || ($r['result'] == false && $r['error'] == "" && $r['action']=="update"))) {	
				$json_result['error'] = ($r['error'] != "") ? $r['error'] : 'the data could not be saved';
				dbp_fn::set_cookie('error', $json_result['error']);
				if (is_countable($queries_executed) && count($queries_executed) > 0) {
					$queries_executed = array_filter($queries_executed);
					$json_result['msg'] =sprintf( __('%s queries were executed successfully:','db_press'), count($queries_executed))."<br>".implode("<br>", $queries_executed);
					dbp_fn::set_cookie('msg', $json_result['msg']);
				}
				wp_send_json($json_result);
				die();
			} else if ($r['query'] != "") {
				$queries_executed[] =  $r['query'];
			} 
		}

		if (is_countable($queries_executed) && count($queries_executed) > 0) {
			$queries_executed = array_filter($queries_executed);
			$json_result['msg'] = sprintf(__('%s queries were executed successfully:','db_press'), count($queries_executed))."<br>".implode("<br>", $queries_executed);
			dbp_fn::set_cookie('msg', $json_result['msg']);
		}
		// preparo i dati da inviare per aggiornare la tabella nel frontend!
		
		$table_model = $this->get_table_model_for_sidebar();
		if ($table_model != false) {

	// preparo i dati da inviare per aggiornare la tabella nel frontend!
			
			$table_model->get_list();
			
			dbp_fn::remove_hide_columns($table_model);
			$table_items = $table_model->items;
			if (count($table_model->items) == 2) {
				$json_result['table_item_row'] = array_pop($table_items);
			} else {
				$json_result['reload'] = 1;
			}
		}
		//$json_result['error'] = 'OPS ERRORE!!!';
		wp_send_json($json_result);
		die();
	}
	
	/**
	 * Calcola quali record sta per eliminare a seconda della query e delle primary ID
	 */
	public function dbp_delete_confirm() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$json_send = [];
		//$json_send = ['error' => '', 'items' => '', 'checkboxes'];
		if (!isset($_REQUEST['ids']) || !is_countable($_REQUEST['ids'])) {
			$json_send['error'] = __('I have not found any results. Verify that the primary key of each selected table is always displayed in the MySQL SELECT statement.', 'db_press');
			wp_send_json($json_send);
			die();
		}
		if (isset($_REQUEST['dbp_id']) && $_REQUEST['dbp_id']  > 0) {
			$json_send = dbp_fn::prepare_delete_rows(dbp_fn::req('ids',[]),'', absint($_REQUEST['dbp_id']));
        } else if ($_REQUEST['sql'] != "") {
			$json_send = dbp_fn::prepare_delete_rows(dbp_fn::req('ids',[]), dbp_fn::req('sql'));
        } else {
			$json_send['error'] = __('Something wrong', 'db_press');
			wp_send_json($json_send);
			die();
		}
		unset($json_send['sql']);
		wp_send_json($json_send);
		die();
		
	}


	/**
	 * bulk delete on sql: 
	 * Scelgo da quali tabelle rimuovere i dati
	 */
	function dbp_check_delete_from_sql() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$errors = [];
		$table_model = new Dbp_model();
		$table_model->prepare(dbp_fn::req('sql', ''));
		$table_items = $table_model->get_list();
		if ($table_model->last_error ) {
            $error =  $table_model->last_error."<br >".$table_model->get_current_query();
			wp_send_json(['items'=>[],'error'=>$error]);
			die();
        }
        if (count($table_items) < 2) {
			wp_send_json(['items'=>[],'error'=>__("There are no records to delete", 'db_press')]);
			die();
        }
		
		$header = array_shift($table_items);
		// trovo le tabelle interessate
		$temp_groups = [];
		foreach ($header as $key=>$th) {
			if ($th['schema']->table == '' OR $th['schema']->orgtable == '') continue;
			$id = dbp_fn::get_primary_key($th['schema']->orgtable);
			$option = dbp_fn::get_dbp_option_table($th['schema']->orgtable);
			if ($option['status'] != "CLOSE" && $id != "") {
				if ($th['schema']->table == $th['schema']->orgtable) {
					$temp_groups[$th['schema']->table] =  $th['schema']->table;
				} else {
					$temp_groups[$th['schema']->table] = $th['schema']->orgtable." AS ". $th['schema']->table;
				}
			}
		}

		wp_send_json(['items'=>$temp_groups, 'error'=>implode("<br>", array_unique($errors))]);
		die();
	}

	/**
	 * Preparo gli id da rimuovere in delete from query
	 */
	function prepare_query_delete() {
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$errors = [];
		$table_model = new Dbp_model();
		$tables = dbp_fn::req('tables', 0);
		$limit_start = dbp_fn::req('limit_start', 0);
		$limit = 1000;
		$total = dbp_fn::req('total', 0);
		$filename = dbp_fn::req('dbp_filename', '');
		$table_model->prepare(dbp_fn::req('sql', ''));
		$table_model->add_primary_ids();
		$table_model->list_add_limit($limit_start, $limit);
		$table_model->get_list();
		$table_model->update_items_with_setting();

		if ($total == 0) {
			$total = $table_model->get_count();
		}
		$data_to_delete = [];
		$table_items = $table_model->items;
		$temporaly_file = new Dbp_temporaly_files();
		if ($filename != "") {
			$data_to_delete = $temporaly_file->read($filename);
		} else {
			$temporaly_file->read($filename);
		}
		if (count($table_items) > 1) {
			$header = array_shift($table_items);
			$header_pris = [];
			foreach ($header as $key=>$th) {
				if ($th->pri && in_array($th->table, $tables)) {
					if (!isset($data_to_delete[$th->original_table."|".$th->original_name])) {
						$data_to_delete[$th->original_table."|".$th->original_name] = [];
					}
					$header_pris[$key] = $th;
				}
			}
			//var_dump ($table_items);
			foreach($table_items as $item) {
				foreach ($header_pris as $key => $hpri) {
					if (!in_array( $item->$key, $data_to_delete[$hpri->original_table."|".$hpri->original_name])) {
						$data_to_delete[$hpri->original_table."|".$hpri->original_name][] = $item->$key;
					}
				}
			}
			$filename = $temporaly_file->store($data_to_delete, $filename);
		}
		wp_send_json(['executed'=>$limit_start+$limit, 'total'=>$total, 'filename'=>$filename]);
		die();
	}

	function sql_query_delete() {
		global $wpdb;
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		$filename = sanitize_text_field(dbp_fn::req('dbp_filename', ''));
		$temporaly_file = new Dbp_temporaly_files();
		$data_to_delete = $temporaly_file->read($filename);
		$total = absint(dbp_fn::req('total', 0));
		$base_executed = $executed = dbp_fn::req('executed', 0);
		$limit = 1000;
		//$data_to_delete[$th->original_table."|".$th->original_name] 
		if ($total == 0) {
			
			foreach ($data_to_delete as $dtd) {
				foreach ($dtd as $id) {
					$total++;
				}
			}
		}
		//ob_start();
		$count = 0;
		foreach ($data_to_delete as $key => $dtd) {
			list($table,$field) = explode("|", $key);
			$query = "DELETE FROM `".esc_sql($table)."` WHERE `".esc_sql($field)."` = '%s';";
			foreach ($dtd as $id) {
				$count++;
				if ($count <= $base_executed) continue;
				if ($count > $base_executed + $limit) break;
				$executed++;
				//print sprintf($query, absint($id));
				if (absint($id) > 0) {
					$wpdb->query(sprintf($query, absint($id)));
				}
			}
		}
		//$html = ob_get_clean();
		wp_send_json(['executed'=>$executed, 'total'=>$total, 'filename'=>$filename]);
		die();
	}
	
	/**
	 * Prepara il csv 
	 */
	function dbp_download_csv() {
		dbp_fn::require_init();
		if (!current_user_can('administrator')) die('no_access');
		$temporaly_files = new Dbp_temporaly_files();
		$csv_filename = sanitize_text_field(dbp_fn::req('csv_filename', ''));
		$request_ids = dbp_fn::req('ids', false);
		$limit_start = absint(dbp_fn::req('limit_start', 0));
		if ($limit_start == 0) {
			$temporaly_files->clear_old();
		}
		if ($request_ids == false || !is_countable($request_ids)) {
			// estraggo i dati dalla query
			$line = 2000;
			$next_limit_start = $limit_start + $line;
			$table_model = new Dbp_model();
			$table_model->prepare(dbp_fn::req('sql', ''));
			$table_model->list_add_limit($limit_start, $line);
			$table_items = $table_model->get_list();
			$count = $table_model->get_count();
			// verifico che la query non abbia dato errore
			if ($table_model->last_error ) {
				$error =  $table_model->last_error."<br >".$table_model->get_current_query();
				wp_send_json(['error'=>$error]);
				die();
			}
			if (count($table_items) < 2 && $limit_start+2 < $count) {
				wp_send_json(['error'=>__("There was an unexpected problem", 'db_press')]);
				die();
			}
			 array_shift($table_items);
		} else {
			$line = 200;
			// estraggo i dati dalla query solo per i checkbox selezionati
			$table_items = [];
			$next_limit_start = $limit_start + $line;
			$count = count($request_ids);
			$foreach_count = 0;
			foreach ($request_ids as $ids) {
				$foreach_count++;
				if ($foreach_count <= $limit_start) continue;
				if ($foreach_count > $next_limit_start) break;
				$filter = [];
				$table_model = new Dbp_model();
				$table_model->prepare(dbp_fn::req('sql', ''));
				foreach ($ids as $column=>$id) {
					$temp_col = explode(".",$column);
					$table = '`'.esc_sql(array_shift($temp_col)).'`';
					$field = '`'.esc_sql(implode(".", $temp_col)).'`';
					$filter[] = ['op'=>"=", 'value'=>$id, 'column'=>$table.'.'.$field];
				}
				$table_model->list_add_where($filter);
				$table_items_temp = $table_model->get_list();
				if (count($table_items_temp) == 2) {
					$table_items[] = array_pop($table_items_temp);
				}

			}
		}
		// rimuovo la prima riga che è l'header con lo schema della tabella.
		$csv_filename = $temporaly_files->store_csv($table_items, $csv_filename, ";", true);
		//
		$link = add_query_arg(['section'=>'table-import', 'action'=>'dbp_download_csv_report','filename'=>$csv_filename],  admin_url("admin-post.php"));
		wp_send_json(['link' => $link,  'msg' => '', 'error' => '', 'count' => $count, 'next_limit_start' => $next_limit_start, 'filename' => $csv_filename]);
		die();
	}

	/**
	 *  Estraggo tutte le colonne possibili che si possono visualizzare da una query.
	 *  Chiamato dal bottone ORGANIZE COLUMNS
	 */
	function columns_sql_query_edit() {
		dbp_fn::require_init();
		if (!current_user_can('administrator')) die('no_access');
		$table_model = new Dbp_model();
		$sql = html_entity_decode(dbp_fn::req('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$all_fields = $table_model->get_all_fields_from_query();
			//Todo trovo le colonne originali della query per impostare i checkbox checked.
			$table_model->prepare($sql);
			
			$header = $table_model->get_schema();
			if ($table_model->last_error != "") {
				wp_send_json(['msg' => sprintf(__("Ops Query Error: %s ",'db_press'), $table_model->last_error)]);
				die;
			}
			// data without as serve per capire se ci sono funzioni nella query
			// trovo un array con le colonne della query che non fanno parte dei campi del db tipo CONCAT()
			$new_fields = $table_model->get_original_column_name();
			//var_dump ($new_fields);
			$sql_fields = [];
			$all_fields2 = [];
			$unique_names = [];
			if (is_countable($header)) {
				foreach ($header as $k=>$h) {
					if (isset($h->table) && isset($h->orgname) && array_key_exists('`'.$h->table.'`.`'.$h->orgname.'`', $all_fields)) {
						$as_name =  $h->name;
						$count = 1;
						while (in_array($as_name, $unique_names) && $as_name != "" && $count < 9999) {
							$as_table =  ($h->table != $h->orgtable) ? $h->table : $h->orgtable;
							$as_name = strtolower(str_replace(" ","", $as_table."_".$h->name ."_".$count));
							$count++;
						}
						$unique_names[] = $as_name;
						$as_name = ($as_name != $h->orgname) ? $as_name : '';
						$sql_fields['`'.$h->table.'`.`'.$h->orgname.'`'] = $as_name;
						$all_fields2['`'.$h->table.'`.`'.$h->orgname.'`'] = $all_fields['`'.$h->table.'`.`'.$h->orgname.'`'];
						unset($all_fields['`'.$h->table.'`.`'.$h->orgname.'`']);
					} else if (isset($new_fields[$h->name])) {
						
						$sql_fields[$new_fields[$h->name]] = $h->name;
						$all_fields2[$new_fields[$h->name]] = $h->name;
					}
				}
			}
			$all_fields2 = array_merge($all_fields2, $all_fields);
			if (is_countable($all_fields2) && count($all_fields2) > 0) {
				wp_send_json(['all_fields' => $all_fields2, 'sql_fields' => $sql_fields ]);
			} else {
				wp_send_json(['msg' => __("I'm sorry, but I can't extract the query columns",'db_press'),  'html'=>'']);
			}
		}  else {
			wp_send_json(['msg' => __("I'm sorry, but I can't extract the query columns",'db_press'),  'html'=>'']);
		}
		die();
	}

	/**
	 * Ricevo una query e un elenco di colonne da visualizzare. Ritorna la query con il nuovo select
	 * Chiamato dal bottone ORGANIZE COLUMNS
	 */
	function edit_sql_query_select() {
		dbp_fn::require_init();
		if (!current_user_can('administrator')) die('no_access');
		$table_model = new Dbp_model();
		$table_model->prepare(dbp_fn::req('sql', ''));
		if (dbp_fn::req('sql') != "" && $table_model->sql_type() == "select") {
			// preparo la stringa con il nuovo select
			$choose_columns = dbp_fn::req('choose_columns');
			$columns_as = dbp_fn::req('label');
			$select = [];
			$as_unique = [];
			foreach ($choose_columns as $key => $value) {
				if (isset($columns_as[$key]) && trim($columns_as[$key]) != "" ) {
					$as = str_replace("`","'", wp_unslash(trim($columns_as[$key])));
					$count_while = 0;
					while (in_array($as, $as_unique) && $count_while < 999) {
						$as = $columns_as[$key] ."_".$count_while;
						$count_while++;
					}
					$select[] = $value." AS `".$as."`";
					$as_unique[] = $as;
				} else {
					$select[] = $value;
					$val = explode(".", $value);
					$val = array_pop($val);
					$as_unique[] = trim(str_replace('`','',$val));
				}
			}
			$table_model->list_change_select(implode(", ", $select));
			$new_query = $table_model->get_current_query();
			/**
			 * Ricarico l'html del box della query
			 */
			$table_model->remove_limit();
			$html = dbp_html_sql::get_html_fields($table_model);

			wp_send_json(['sql' => $new_query, 'html'=>$html]);
		} else {
			//TODO ERROR!
			wp_send_json(['msg' => __("I'm sorry, but I can't extract the query columns",'db_press')]);
		}
		die();
	}

	/**
	 * Estraggo i parametri per preparare la form per aggiungere un join ad una query
	 * Chiamato dal bottone MERGE
	 */
	function merge_sql_query_edit() {
		dbp_fn::require_init();
		if (!current_user_can('administrator')) die('no_access');
		$table_model = new Dbp_model();
		$sql = html_entity_decode(dbp_fn::req('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$all_fields = $table_model->get_all_fields_from_query();
			$all_tables = dbp_fn::get_table_list();
			if (is_countable($all_fields) && count($all_fields) > 0 && is_countable($all_tables) && count($all_tables) > 0) {
				wp_send_json(['all_fields' => $all_fields, 'all_tables' => $all_tables['tables']]);
			} else {
				wp_send_json(['msg' => __('The current query cannot be joined to other tables','db_press')]);
			}
		} else {
			wp_send_json(['msg' => __('The current query cannot be joined to other tables','db_press')]);
		}
		die();
	}

	/**
	 * Estraggo i parametri per preparare la form per aggiungere un join ad una query
	 * Chiamato dal bottone MERGE
	 */
	function merge_sql_query_get_fields() {
		dbp_fn::require_init();
		$table = esc_sql(dbp_fn::req('table'));
		$all_columns = dbp_fn::get_table_structure($table, true);
		wp_send_json(['all_columns' => $all_columns]);
		die();
	}

	/**
	 * Genero la query con il  join
	 */
	function edit_sql_query_merge() {
		global $wpdb;
		if (!current_user_can('administrator')) die('no_access');
		dbp_fn::require_init();
		if (!isset($_REQUEST['dbp_merge_table']) || !isset($_REQUEST['dbp_merge_column']) ||  !isset($_REQUEST['dbp_ori_field'])) {
			wp_send_json(['msg' => __('All fields are required','db_press')]);
			die();
		}
		//var_dump ($_REQUEST);
		$table_model = new Dbp_model();
		$sql = html_entity_decode(dbp_fn::req('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$sql_schema = $table_model->get_schema();
			$temp_curr_query = $table_model->get_current_query();
			// trovo l'alias della tabella di cui si sta facendo il join
			// TODO ho una funzione apposta per questo da sostituire
			$table_alias_temp  = substr(dbp_fn::clean_string(str_replace($wpdb->prefix, "", $_REQUEST['dbp_merge_table'])),0 ,3);
			if (strlen($table_alias_temp) < 3 ) {
				$table_alias_temp = $table_alias_temp.substr(md5($table_alias_temp),0 , 2);
			}
			$table_alias = $table_alias_temp;
			$count_ta = 1;
			while(stripos($temp_curr_query, $table_alias.'`') !== false || stripos($temp_curr_query, $table_alias.' ') !== false) {
				$table_alias = $table_alias_temp.''.$count_ta;
				$count_ta++;
			}
			$table_alias = Dbp_fn::sanitize_key($table_alias);
			// compongo la nuova porzione di query
			$join = strtoupper(sanitize_text_field($_REQUEST['dbp_merge_join']));
			if (!in_array($join, ['INNER JOIN','LEFT JOIN','RIGHT JOIN'])) {
				$join ='INNER JOIN';
			}
			$join = $join.' `'.Dbp_fn::sanitize_key($_REQUEST['dbp_merge_table']).'` `'.$table_alias.'`';
			$join .= " ON `" . $table_alias . "`.`" . Dbp_fn::sanitize_key($_REQUEST['dbp_merge_column']) . '` = '. Dbp_fn::sanitize_key($_REQUEST['dbp_ori_field']);
			// la unisco alla query originale
			//$table_model->list_add_select(''); // serve per convertire l'* in table.*
			$table_model2 =  new Dbp_model();
			$table_model->list_add_from($join);
			// Modifico il select aggiungo i nuovi campi:
			// duplico il model per avere lo schema dei dati da inserire. Non uso l'* perché 
			// genera colonne duplicate!
			$table_model2->prepare($table_model->get_current_query());
			$table_model2->list_add_select('`' . $table_alias . '`.*');
			$sql_schema2 = $table_model2->get_schema();
			
			$add_select = [];
			$sql_query_temp = $table_model->get_partial_query_select(); // Il select per evitare colonne duplicate
			if (is_countable($sql_schema2)) {
				foreach ($sql_schema2 as $field) {
				
					if (isset($field->orgtable) && $field->orgtable != "" && isset($field->table) && $field->table == $table_alias) {
						
						$new_name = dbp_fn::get_column_alias(strtolower($table_alias . '_' .substr(str_replace(" ", "_", $field->name), 0, 50)), $sql_query_temp);
						$sql_query_temp .= " ".$new_name;
						
						$add_select[] = '`' . $table_alias . '`.`' .$field->name . '` AS `'.$new_name.'`';
					}
				}
				
				if (count($add_select) > 0) {
					$table_model->list_add_select(implode(", ", $add_select));
				}
			} else {
				// annullo tutto!
				$table_model->remove_limit();
				$html = dbp_html_sql::get_html_fields($table_model);
				wp_send_json(['sql' => $table_model->get_current_query(), 'error'=>$table_model->last_error, 'html'=>$html]);
				die();
			}
		}
		$table_model->remove_limit();
		$html = dbp_html_sql::get_html_fields($table_model);

		wp_send_json(['sql' => $table_model->get_current_query(), 'html'=>$html]);
		die();
	}

	/**
	 * Ritorna i dati per generare la form per l'aggiunta dei metadati alla query
	 * Chiamato dal bottone Add metadata
	 */
	function metadata_sql_query_edit() {
		dbp_fn::require_init();
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		$table_model = new Dbp_model();
		$sql = html_entity_decode(dbp_fn::req('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$tables = [];
			$sql_schema = $table_model->get_schema();
			$pris = [];
			$already_inserted = [];
			if (is_countable($sql_schema)) {
				foreach ($sql_schema as $field) {
					if (isset($field->orgtable) && $field->orgtable != "" && isset($field->table)) {
						$table = $field->orgtable;
						// devo trovare la primary key
						if (!isset($pris[$field->orgtable])) {
							$pris[$field->orgtable] = dbp_fn::get_primary_key($field->orgtable);
						}
						// se non è già stata inserita
						if (!in_array($table, $already_inserted) && $pris[$field->orgtable] != "") {
							$already_inserted[] = $table;
							$tables[] = [$table  ."meta", $field->table.".".$pris[$field->orgtable]];
							$tables[] = [$table  ."_meta", $field->table.".".$pris[$field->orgtable]];
							if (substr($table,-1) == "s") {
								if (substr($table,-4) == "ches" || substr($table,-4) == "shes" || substr($table,-3) == "ses" || substr($table,-3) == "xes" || substr($table,-3) == "zes") {
									$singular = substr($table,0, -2);
								} else {
									$singular = substr($table,0, -1);
								}
								$tables[] = [$singular ."meta", $field->table.".".$pris[$field->orgtable]];
								$tables[] = [$singular ."_meta", $field->table.".".$pris[$field->orgtable]];
							}
						}
					}
				}
			} else {
				wp_send_json(['msg' => __("I can't find any linkable metadata",'db_press')]);
			}
		
			$all_tables = dbp_fn::get_table_list();
			$return_table = [];
			foreach ($all_tables['tables'] as $sql_table) {
				
				$sql_table_name = '';
				foreach ($tables as $val_tab) {
					if ($sql_table == $val_tab[0]) {
						$sql_table_name =  $val_tab[1]."::".$sql_table;
						break;
					}
				}
				if ($sql_table_name != "") {
					$return_table[$sql_table_name] = $sql_table;
				}
			} 
			if (is_countable($return_table) && count($return_table) > 0) {
				wp_send_json(['all_tables' => $return_table]);
			} else {
				wp_send_json(['msg' => __("I can't find any linkable metadata",'db_press')]);
			}

		} else {
			wp_send_json(['msg' => __("The current query cannot be linked to metadata",'db_press')]);
		}
		die();
	}

	/**
	 * Estraggo i meta_key, meta_value dalla tabella meta 
	 * Chiamato dopo il bottone Add metadata dal select della tabella
	 */
	function metadata_sql_query_edit_step2() {
		global $wpdb;
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		dbp_fn::require_init();
		$table2 = dbp_fn::req('table2');
		$sql_table_temp = explode("::", $table2);
		$sql = html_entity_decode(dbp_fn::req('sql'));
		$sql_table = array_pop($sql_table_temp);
	
		$structure = dbp_fn::get_table_structure($sql_table);
		$table = substr($sql_table,strlen($wpdb->prefix));
		$table = str_replace(["_meta","meta"],"", $table);
		if (substr($table,-1) == "s") {
			$table = substr($sql_table,0, -2);
		}
	//	print $table." ";
		$columns = ['pri'=>'','parent_id'=>''];
		if (count($structure) > 3) {
			foreach ($structure as $field) {
				//var_dump ($field->Field);
				//var_dump (stripos($field->Field, $table));
				if ($field->Key == "PRI") {
					$columns['pri'] = $field->Field;
				} elseif ($field->Field != "meta_key" && $field->Field != "meta_value" && stripos($field->Field, $table) !== false) {
					$columns['parent_id'] = $field->Field;
				}
			}
		}
		$list = [];
		// Aggiungo all'elenco ($list) i meta_key
		if ($columns['pri'] != "" && $columns['parent_id'] != "") {
			$list_db = $wpdb->get_results('SELECT DISTINCT meta_key FROM `'.$sql_table.'` ORDER BY meta_key ASC');
			if (is_countable($list_db)) {
				foreach ($list_db as $d) {
					$list[] = $d->meta_key;
				} 
			}
		}
		// cerco di capire quali metadata sono stati già aggiunti 
		$table_model = new Dbp_model();
		$table_model->prepare($sql);
		$selected = [];
		$from_sql = $table_model->get_partial_query_from(true);
	
		foreach ($from_sql as $from) {
			// sto in una condizione
			if (stripos($from[2], 'meta_key') !== false && str_replace(["`", ' '], '', $sql_table) == str_replace(["`",' '], '',$from[0])) {
				$from2 = explode("meta_key", $from[2]) ;
				if (count($from2) == 2) {
					$from_selected = array_pop($from2);
					$from_selected_temp = explode(" AND ", str_ireplace(" OR ", " AND ", $from_selected));
					$selected[] = str_replace(["=","`",'"',"'", ' '], '', array_shift($from_selected_temp));
				}
			}
			
		}

		wp_send_json(['distinct' => $list, 'pri'=>$columns['pri'], 'parent_id'=>$columns['parent_id'], 'selected'=>$selected]);
		die;
	}

	/**
	 * Genero la query con l'aggiunta dei metadati
	 */
	function edit_sql_addmeta() {
		dbp_fn::require_init();
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		$choose_meta = Dbp_fn::sanitize_text_recursive($_REQUEST['choose_meta']);
		$already_checked_meta =  (isset($_REQUEST['altreadychecked_meta']) && is_array($_REQUEST['altreadychecked_meta'])) ? array_filter(array_map('sanitize_text_field',$_REQUEST['altreadychecked_meta'])) : []; 
				
		$pri = sanitize_text_field($_REQUEST['pri_key']);
		$parent_id = sanitize_text_field($_REQUEST['parent_id']);
		$table2 =  dbp_fn::req('dbp_meta_table');
		$_sql_table_temp = array_map('sanitize_text_field', explode("::", $table2));
		$_parent_table_temp = array_shift($_sql_table_temp); // la tabella.primary_id su cui sono collegati i meta 
		$_parent_table_temp =  explode(".", $_parent_table_temp);
		$parent_table_id = array_pop($_parent_table_temp); // l'id della tabella originale
		$parent_table = implode('.', $_parent_table_temp); // la tabella originale
		// manca il primary_id della tabella principale!
		$table = Dbp_fn::sanitize_key(array_shift($_sql_table_temp)); // la tabella dei meta
		$sql = $_REQUEST['sql'];
		$table_model = new Dbp_model();
		$table_model->prepare($sql);
		$from_sql = $table_model->get_partial_query_from();
		if ($sql != "" && $table_model->sql_type() == "select") {
			$temp_sql_from = [];
			$temp_sql_select = [];
			foreach ($choose_meta as $meta) {
				// verifico se non è stato già inserito il join
				$check_string = '.`meta_key` = \''.esc_sql($meta).'\'';
				if (in_array($meta, $already_checked_meta)) {
					$key = array_search($meta, $already_checked_meta);
					unset($already_checked_meta[$key]);
				} elseif (stripos($from_sql, $check_string) === false) {
					$alias = Dbp_fn::sanitize_key(dbp_fn::get_table_alias($table, $sql." ".implode(", ",$temp_sql_from), str_replace("_","",$meta)));
					$temp_sql_from[] = ' LEFT JOIN `'.$table.'` `'.$alias.'` ON `'.$alias.'`.`'.$parent_id.'` = `'.$parent_table.'`.`'.$parent_table_id.'` AND `'.$alias.'`.`meta_key` = \''.esc_sql($meta).'\'';
					$temp_sql_select[] = '`'.$alias.'`.`meta_value` AS `'.dbp_fn::get_column_alias($meta, $sql).'`';
				}
			}

			if (count($temp_sql_select) > 0) {
				$table_model->list_add_select(implode(", ", $temp_sql_select));
			}

			$table_model->list_add_from(implode(" ", $temp_sql_from));
			
			if (count($already_checked_meta) > 0) {
				$select_sql = $table_model->get_partial_query_select(true);
				$from_sql = $table_model->get_partial_query_from(true);
				//var_dump ($from_sql);
				$select_to_remove = [];
				$new_from = [];
				foreach ($from_sql as $from) {
					$add = true;
					foreach ($already_checked_meta as $meta_to_search) {
						if (stripos($from[2], $meta_to_search) !== false && str_replace(["`", ' '], '', $table) == str_replace(["`",' '], '',$from[0])) {
							//print "DEVO RIMUOVERE ".$meta_to_search;
							$select_to_remove[] =  str_replace(["`", ' '], '', $from[1]);
							$add = false;
						} 
					} 
					if ($add) {
						$new_from[] = $from[3];
					}
				}
				// Ricostruisco il select
				$new_select = [];
				foreach ($select_sql as $rebuild_select) {
					if (!in_array($rebuild_select[0],$select_to_remove)) {
						$new_select[] =  $rebuild_select[3];
					}
				}
				$table_model->list_change_select(implode(", ", $new_select));
				$table_model->list_change_from(implode(' ', $new_from));
			}

		}
		$table_model->remove_limit();
		$html = dbp_html_sql::get_html_fields($table_model);
		wp_send_json(['sql' => $table_model->get_current_query(), 'html'=>$html]);
		die();
	}

	/**
	 * mostra come cambierebbero i dati dopo il replace 
	 */
	function sql_test_replace() {
		global $wpdb;
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		dbp_fn::require_init();
		$table_model = new Dbp_model();
		$prepare_model = dbp_fn::req('sql');
		$table_model->prepare($prepare_model);    
		$search = wp_unslash(dbp_fn::req('search', false)); 
		$schemas = $table_model->get_schema();
		$filter =[] ; //[[op:'', column:'',value:'' ], ... ];
		foreach ($schemas as $schema) {
			if ($schema->orgtable != ""  && $schema->table != ""  && $schema->name != "") {
				$filter[] = ['op'=>'LIKE', 'column'=> '`'.esc_attr($schema->table).'`.`'.esc_attr($schema->orgname).'`', 'value' =>$search];
			}
		}
		if (count($filter) > 0) {
			$table_model->list_add_where($filter, 'OR');
		}
		$table_model->list_add_limit(0, 100);
		$items = $table_model->get_list();
		$replace = wp_unslash(dbp_fn::req('replace', false)); 
		if (count ($items) > 0) {
			ob_start();
			$first_row = array_shift($items);
			?>
			<h2><?php _e('Text the first 100 records', 'db_press'); ?></h2>
			<table class="wp-list-table widefat striped dbp-table-view-list">
				<thead>
					<tr>
						<?php foreach ($first_row as $key=>$_) : ?>
							<th><?php echo $key; ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
				<?php 
				if (count($items) > 0) {
					foreach ($items as $item) {
						pinacode::set_var('data', $item);
						$temp_replace = PinaCode::execute_shortcode($replace);
						?><tr><?php
						// sostituisco i dati
						foreach ($item as $value) {
							if (stripos($value, $search) !== false) {
								if (is_serialized($value)) {
									$value_ser = maybe_unserialize($value);
									$value_ser2 = dbp_fn::search_and_resplace_in_serialize($value_ser, $search, $temp_replace);
									$value_serialized = maybe_serialize($value_ser2);
									if (strlen($value) > 100) {
										$temp_pos = stripos($value, $search);
										if ($temp_pos > 20) {
											$value = "... ". substr($value,$temp_pos - 10);	
											$value_serialized = "... ". substr($value_serialized,$temp_pos - 10);
										}
										if (strlen($value) > 100) {
											$value =  substr($value,0,80)." ...";
											$value_serialized  =  substr($value_serialized,0,80)." ...";
										}
									}

									$value = "<b>Change serialized data (only values):</b><br >".str_ireplace(htmlentities($search),'<span style="text-decoration: line-through; color:red">'.htmlentities($search)."</span>", $value)."<br>".$value_serialized;
								} else {
									$value = htmlentities($value);
									if (strlen($value) > 100) {
										$temp_pos = stripos($value, $search);
										if ($temp_pos > 20) {
											$value = "... ". substr($value,$temp_pos - 10);	
										}
										if (strlen($value) > 100) {
											$value =  substr($value,0,80)." ...";
										}
									}
									$value = str_ireplace(htmlentities($search),'<span style="text-decoration: line-through; color:red">'.htmlentities($search)."</span>", $value)."<br>".str_ireplace(htmlentities($search), '<b style="color:#259">'.htmlentities($temp_replace).'</b>', $value );
								}
							} else {
								$value = htmlentities($value);
								if (strlen($value) > 100) {
									$value =  substr($value,0,80)." ...";
								}
							}
							?><td><?php echo $value; ?></td><?php
						}
						?></tr><?php
					}
				}
				?>
				</tbody>
			</table>
			<?php
			$html = ob_get_clean();
		} else{
			$html = _e('Non ho trovato nulla da sostituire'); 
		}
		wp_send_json(['html'=>$html]);
		die();
	}

	/**
	 * sostituisce i dati dopo il replace 
	 */
	function sql_search_replace() {
		global $wpdb;
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		dbp_fn::require_init();
		$table_model = new Dbp_model();
		$sql = wp_unslash(dbp_fn::req('sql'));
		$table_model->prepare($sql);
		$table_model->add_primary_ids();
		$search = wp_unslash(dbp_fn::req('search', false)); 

		$limit_start = dbp_fn::req('limit_start', 0);
		
		$total = dbp_fn::req('total', 0);
		$replaced = dbp_fn::req('row_replaced', 0);
		if ($total == 0) {
			$total = $table_model->get_count();
		}

		$table_model->list_add_limit($limit_start, 200);
		$items = $table_model->get_list();
		if (count ($items) > 1) {
			$replace = wp_unslash(dbp_fn::req('replace', false)); 
			$executed = $limit_start + count($items) - 1;
		
			$first_row = array_shift($items);
	
			foreach ($items as $item) {
				pinacode::set_var('data', $item);
				$temp_replace = PinaCode::execute_shortcode($replace);
				// sostituisco i dati
				// TODO: DEVO PREVENIRE LE CHIAVI PRIMARIE!!!!
				$update = false;
				foreach ($item as &$value) {
					if (stripos($value, $search) !== false && $value != "") {
						$update =true;
						$replaced++;
						if (is_serialized($value)) {
							$old_val = $value;
							$value_ser = maybe_unserialize($value);
							
							$value_ser2 = dbp_fn::search_and_resplace_in_serialize($value_ser, $search, $temp_replace);
							
							$value = maybe_serialize($value_ser2);
							//var_dump ($value);
							$value_ser = maybe_unserialize($value);
							if ($value_ser === false) {
								//var_dump ($value);
								//die();
								$value = $old_val;
							}
						} else {
							$value = str_ireplace($search, $temp_replace, $value );
						}
					}	
				}
				if ($update) {
					
					// aggiorno i dati!

					//print "\nSAVE\n";
					$form = new Dbp_class_form($sql);
					$ris = $form->save_data($item, false);

					var_dump ($ris);
					die;
				}
			}
		} else {
			$executed = $total;
		}
		wp_send_json(['total'=>$total, 'executed' => $executed, 'replaced' => $replaced ]);
		die();

		$replace = wp_unslash(dbp_fn::req('replace', false)); 

	}

	/**
	 * Testa una query. Verifica se è un select.
	 *
	 * @return void
	 */
	function check_query() {
		global $wpdb;
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		dbp_fn::require_init();
		$response = ['is_select' => 0, 'error' => ''];
		$sql = dbp_fn::req('sql','','remove_slashes');
		$table_model = new Dbp_model();
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$ris = $wpdb->get_var("EXPLAIN ".$sql );
			if (!$ris && $wpdb->last_error != "" ) {
				$response['error'] =  $wpdb->last_error;
			} else {
				$response['is_select'] = 1;
			}
		}
		wp_send_json($response);
		die();
	}
	
	/**
     *  Imposta i cookie in una variabile statica e li rimuove dai cookie
     */
	function init_get_msg_cookie() {
		if (current_user_can('administrator')) {
       		dbp_fn::init_get_msg_cookie();
		}
    }

	/**
	 * Raggruppo questo pezzettino di codice solo perché usato di continuo per preparare la query sull'edit, view, ecc..
	 */
	private function get_table_model_for_sidebar() {
		if (!current_user_can('administrator')) {
			wp_send_json(['msg' => __("No access", 'db_press')]);
			die();
		}
		if (!isset($_REQUEST['ids']) || !isset($_REQUEST['sql'])) {
			return false;
		}
	
		if (isset($_REQUEST['sql'])) {
			$table_model 				= new Dbp_model();
			$table_model->prepare(html_entity_decode (dbp_fn::req('sql', '')));
		}
		$filter = [];
		
		$json_send['edit_ids'] = dbp_fn::req('ids', 0);
		foreach ($_REQUEST['ids'] as $column => $id) {
			$column = str_replace("`", "", $column );
			$column = "`".str_replace(".", "`.`", $column )."`";
			$filter[] = ['op' => "=", 'column' => $column, 'value' => $id];
		}
		$table_model->list_add_where($filter);
		return $table_model;
	}

	/**
	 * Mette tutte le tabelle di wordpress in stato pubblicato
	 */
	function publish_wp_tables($status, $table) {
		global $wpdb; 
		if (in_array($table, [$wpdb->posts, $wpdb->users, $wpdb->prefix.'usermeta', $wpdb->prefix.'terms' , $wpdb->prefix.'termmeta', $wpdb->prefix.'term_taxonomy', $wpdb->prefix.'term_relationships', $wpdb->prefix.'postmeta', $wpdb->prefix.'options', $wpdb->prefix.'links', $wpdb->prefix.'comments', $wpdb->prefix.'commentmeta'])) {
			$status = 'PUBLISH';
		}
		return $status;
	}

	/**
	 * Memorizzo le query di modifica che vengono eseguite dentro change
	 */
	function store_query($query) {
		
		if (!isset(self::$saved_queries->change) || !is_array(self::$saved_queries->change)) {
			self::$saved_queries->change = [];
		}
		switch (strtolower(substr(trim($query), 0, 6))) {

			case 'update':
				self::$saved_queries->change[] = $query;
				break;
			case 'insert':
				self::$saved_queries->change[] = $query;
				break;
			case 'delete':
				self::$saved_queries->change[] = $query;
				break;
		}
		return $query;
	}

	/**
	 * Quando cliccano il popup per votare il plugin registro la scelta (già votato o non mi piace)
	 */
	function record_preference_vote() {
		$vote = esc_sql($_REQUEST['msg']);
		$info = get_option('_dbp_activete_info');
		$info['voted'] = $vote;
		update_option('_dbp_activete_info', $info, false);
		wp_send_json(['done']);
		die();
	}

	function form_lookup_test_query() {
		global $wpdb;
		ob_start();
		dbp_fn::require_init();
		$table_model = new Dbp_model(Dbp_fn::sanitize_key($_REQUEST['table']));
		if ($_REQUEST['where'] != "") {
			$model_prepare = $table_model->get_current_query()." WHERE ".dbp_fn::req('where');
			$table_model->prepare($model_prepare);
		}
		$table_model->list_change_select('`'.Dbp_fn::sanitize_key($_REQUEST['field_id']).'` AS val, `'.Dbp_fn::sanitize_key($_REQUEST['label']).'` AS txt');

		$sql = $table_model->get_current_query();
		$response['id'] = sanitize_text_field($_REQUEST['id']);
		$response['error'] = "";
		$response['count'] = 0;
		if ($sql != "" && $table_model->sql_type() == "select") {
			$ris = $wpdb->get_var("EXPLAIN ".$sql );
			if (!$ris && $wpdb->last_error != "" ) {
				$response['error'] =  $wpdb->last_error;
			} else {
				$response['count'] = $table_model->get_count();
				$response['is_select'] = 1;
			}
		}
		ob_get_clean();
		wp_send_json($response);
		die();
	}

}

$database_press_loader = new Dbp_loader();