<?php
/**
 * Il controller dell'amministrazione che gestisce tutte le chiamate alle pagine del plugin (quindi quando stai dentro page=db_press).
 * Le chiamate POST e Ajax sono gestite dal loader.
 * 
 * Le funzioni della classe chiamano direttamente i vari file del template quindi dentro i template vengono chiamate le variabili delle funzioni stesse. 
 * @internal 
 */
namespace DbPress;

class database_press_admin 
{
	/**
	 * @var Int $max_show_items Numero massimo di elementi da caricare per un select
	 */
	var $max_show_items = 500; 
	/**
	 * @var String $last_error
	 */
	var $last_error = "";
	/**
	 * @var Array $get_table_list L'elenco delle tabelle e delle viste
	 */
	var $table_list = [];
	/**
	 * @var String $msg
	 */
	var $msg = "";
    /**
	 * Viene caricato alla visualizzazione della pagina
     */
    function controller() {
		global $wpdb;
		if (!current_user_can('administrator'))  {
			_e('Only site administrators can access this plugin', 'db_press');
			return;
		}
		
		wp_enqueue_style('database-press-css', plugin_dir_url( __FILE__ ) . 'css/database-press.css',[] , DB_PRESS_VERSION);
		wp_enqueue_script('database-press-all-js', plugin_dir_url( __FILE__ ) . 'js/database-press-all.js',[] , DB_PRESS_VERSION);

		// $dbp = new Dbp_fn();
		dbp_fn::require_init();
		$temporaly_files = new Dbp_temporaly_files();
	    /**
		 * @var $section Definisce il tab che sta visualizzando
		 */
        $section =  dbp_fn::req('section', 'home');
         /**
		 * @var $action Definisce l'azione
		 */
       	$action = dbp_fn::req('action', '', 'string');
		//print $section." ".$action;	
		$msg =  $msg_error = '';
		if (isset($_COOKIE['dbp_msg'])) {
			$msg = $_COOKIE['dbp_msg'];
		}
		if (isset($_COOKIE['dbp_error'])) {
			$msg_error = $_COOKIE['dbp_error'];
		}	
		switch ($section) {
			case 'information-schema' :
				$this->information_schema();
				break;
			case 'table-structure' :
				$this->table_structure();
				break;
			case 'table-import' :
				$this->table_list = dbp_fn::get_table_list();
				
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'database-press-import-js', plugin_dir_url( __FILE__ ) . 'js/database-press-import.js',[], DB_PRESS_VERSION);
				wp_localize_script( 'database-press-import-js', 'dbi_vars', array(
					'upload_file_nonce' => wp_create_nonce( 'dbi-file-upload' ),
					)
				);
				if ($action =='import-sql-file') {
					$this->import_sql_file();
				} else if ($action =='import-csv-file') {
					$this->import_csv_file();
				} else if ($action == 'execute-csv-data' ) {
					$this->execute_csv_data();
				} else if ($action == 'create-table-csv-data') {
					$this->create_table_csv_data();
				} else {
					$max_row_allowed = floor(dbp_fn::get_max_input_vars()/10);
					$temporaly_files->clear_old();
					$import_table = dbp_fn::req('table', '');
					$render_content = "/dbp-content-table-import.php";
					require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
				}
				break;
			case 'table-sql' :
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'database-sql-editor-js', plugin_dir_url( __FILE__ ) . 'js/database-sql-editor.js',[], DB_PRESS_VERSION);
				$this->table_list = dbp_fn::get_table_list();
				// TODO: $list_of_columns 				= dbp_fn::get_all_columns();
				add_filter( 'dbp_render_sql_btns', [$this, 'filter_render_sql_btns'] );

				$render_content = "/dbp-content-sql.php";
				$table = (isset($_REQUEST['table'])) ? sanitize_text_field($_REQUEST['table']): ''; 
				$table_model 				= new Dbp_model($table);
				$table_model->prepare();
				require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
				break;
			case 'table-browse' :
				$this->table_browse();
				break;
			case 'home' :
				//TODO Aggiungere un popup introduttivo
				// https://www.designbombs.com/adding-modal-windows-in-the-wordpress-admin/

				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'database-sql-editor-js', plugin_dir_url( __FILE__ ) . 'js/database-sql-editor.js',[], DB_PRESS_VERSION);
				add_filter('dbp_render_sql_height', function () {return 100;} );

				add_filter( 'dbp_render_sql_btns', [$this, 'home_render_sql_btns'] );

				$permission_list = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'RELOAD', 'INDEX', 'ALTER', 'SHOW DATABASES', 'CREATE TEMPORARY TABLES', 'CREATE VIEW', 'SHOW VIEW'];
				$user_permission = $wpdb->get_results("SHOW GRANTS");
				if (!is_array($user_permission) || count($user_permission) >0) {
					$user_permission = $wpdb->get_results("SHOW GRANTS FOR '".esc_sql(DB_USER)."'@'localhost'");
				} 
				if (is_array($user_permission) && count($user_permission) > 0) {
				
					foreach ($user_permission as $up1) {
						foreach ($up1 as $up) {
							foreach ($permission_list as $k=>$pl) {
								if (stripos($up, $pl) !== false) {
									unset($permission_list[$k]);
								}
							}
						}
					}
				} else {
					$permission_list = false;
				}
				$processlist = [];
				$processlist_sql = $wpdb->get_results("SHOW processlist;");
				foreach ($processlist_sql as $pl) {
					if ($pl->Time > 30 && $pl->Command == "Query" && $pl->Info != Null) {
						$processlist[$pl->Id] = $pl->Info;
					}
				}

				$variables = $wpdb->get_row('SHOW VARIABLES WHERE Variable_name = "version_comment";');
				$info_db = "";
				if (stripos($variables->Value, 'MySQL ') !== false) {
					$info_db = "MYSQL ".$wpdb->get_var('SELECT VERSION();');
				} else {
					$vers = $wpdb->get_var('SELECT VERSION();');
					if (stripos($variables->Value, 'MariaDB') > 0) {
						$info_db = $vers; 
					}
				}
				$database_size = 0;
				$database_name = $wpdb->get_var('SELECT DATABASE();');
				if ($database_name != "") {
					$database_size = $wpdb->get_var('SELECT  sys.FORMAT_BYTES(SUM(data_length + index_length)) `size` FROM information_schema.tables WHERE table_schema = "'.$database_name.'" GROUP BY table_schema');
				}
				$temporaly = new Dbp_temporaly_files();
				$dir = $temporaly->get_dir();
				
				$is_writable_dir = false;
				if ($dir != "") {
					$is_writable_dir = wp_is_writable($dir);
				}

				require dirname( __FILE__ ) . "/partials/dbp-page-home.php";
				break;
		}
    }

	/**
	 * 
	 */
	private function information_schema() {
		global $wpdb;
		if (!current_user_can('administrator')) return;
		// $dbp = new Dbp_fn();
		$temporaly_files = new Dbp_temporaly_files();
        $section =  dbp_fn::req('section', 'home');
       	$action = dbp_fn::req('action', '', 'string');
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'database-sql-editor-js', plugin_dir_url( __FILE__ ) . 'js/database-information-schema.js',[], DB_PRESS_VERSION);
		
		require dirname( __FILE__ ) . "/../includes/dbp-model-information-schema.php";
		$temporaly_files->clear_old();
		$table_model 				= new Dbp_model_information_schema();
	
		$table_model->get_list();
		$html_table 				= new Dbp_html_simple_table();	
		$html_table->add_table_class('wp-list-table widefat striped dbp-table-view-list');
		$render_content = "/dbp-content-information-schema.php";
		
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
	}
	/**
	 * Il tab structure
	 */
	private function table_structure() {
		if (!current_user_can('administrator')) return;
		// $dbp = new Dbp_fn();
		// da ricordarsi: il salvataggio è in post (dbp-loader-structure.php)
        $section =  dbp_fn::req('section', 'home');
       	$action = dbp_fn::req('action', '', 'string');
		$msg =  $msg_error = $table = $table_new_name = '';	
		if ($action == 'edit-index') {
			wp_enqueue_script( 'database-press-structure-js', plugin_dir_url( __FILE__ ) . 'js/database-press-structure.js',[], DB_PRESS_VERSION);
			wp_enqueue_script( 'jquery-ui-sortable' );
			
			$table = sanitize_text_field($_REQUEST['table']);
			$id = sanitize_text_field($_REQUEST['dbp_id']);
			$table_fields = dbp_fn::get_table_structure($table, true);
			$table_model = new Dbp_model_structure($table);
			$indexes = $table_model->get_index($id);
			$index_table = new Dbp_html_simple_table();
			$render_content = "/dbp-content-table-structure-indexes.php";
		} else {
			$render_content = "/dbp-content-table-structure.php";
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'database-press-structure-js', plugin_dir_url( __FILE__ ) . 'js/database-press-structure.js',[], DB_PRESS_VERSION);
			wp_enqueue_script( 'database-press-alter-table-js', plugin_dir_url( __FILE__ ) . 'js/database-press-alter-table.js',[], DB_PRESS_VERSION);
			if (!isset($_REQUEST['table'])) {
				// Nuova tabella
				$tables_list = dbp_fn::get_table_list();
				$count_table_name = 1;
				$table_new_name = dbp_fn::get_prefix(). "dbp_table_".str_pad($count_table_name, 3, '0', STR_PAD_LEFT) ;
				while (array_key_exists($table_new_name, $tables_list["tables"]) && $count_table_name < 999) {
					$count_table_name++;
					$table_new_name = dbp_fn::get_prefix(). "dbp_table_".str_pad($count_table_name, 3, '0', STR_PAD_LEFT) ;
				}
				$table = "";
				$table_model = new Dbp_model_structure();
			} else {
				$table = sanitize_text_field($_REQUEST['table']);
				$table_model = new Dbp_model_structure($table);
			}
			if ($action == 'save_metadata') {
				//options[status], options[description]
				dbp_fn::update_dbp_option_table_status($table, sanitize_text_field($_REQUEST['options']['status']), sanitize_textarea_field($_REQUEST['options']['description']));
			}
			if ($action == 'save_index') {
				if (isset($_REQUEST['index']['columns'])) {
					$columns = Dbp_fn::sanitize_text_recursive($_REQUEST['index']['columns']);
					$name =  (isset($_REQUEST['index']['name'])) ?  sanitize_text_field($_REQUEST['index']['name']) : '';
					if ($table_model->alter_index(
						$columns ,
						sanitize_text_field($_REQUEST['original_name']),
						$name,
						sanitize_text_field($_REQUEST['original_index']),
						sanitize_text_field($_REQUEST['index']['type'])))
					{
						$msg =__('Altered Index success', 'db_press');
					} else {
						$msg_error = $table_model->last_error;
					}
				} else {
					$msg_error = __('You must select at least one column', 'db_press');
				}
			}
			if ($action == 'delete-index') {
				if ($table_model->delete_index(sanitize_text_field($_REQUEST['dbp_id']))) {
					$msg =__('Delete Index success', 'db_press');
				} else {
					$msg_error = __('Error Delete Index success', 'db_press');
				}
			}
			// $_REQUEST['table'] c'è di sicuro perché altrimenti si esegue il redirect nel loader ad information-schema
			
			$table_model->get_structure();
			$old_primaries = [];
			$is_old_primaries_type_numeric = false;
			foreach ($table_model->items as $cs) {
				if (is_object($cs)) {
					if ($cs->Key == "PRI" ) {
						$old_primaries[] = '`'.$cs->Field.'`';
						if (substr($cs->Type,0,3) == "int" || substr($cs->Type,0,6) == "bigint") {
							$is_old_primaries_type_numeric = true;
						}
					}
					
				}
			}
			$table_options = dbp_fn::get_dbp_option_table($table);

			if ($table_model->error_primary) {
				$msg_error = __('This system works better with tables that have only one field set as the autoincrement primary key.','db_press');
				$msg_error .= '<br><br>';
				if ($table_options['status'] != 'DRAFT') {
					$msg_error .= __('<b>To solve the problem follow the instructions:</b><br>
					<p>1. Click on Edit status and put the table in DRAFT MODE</p><p>2. Copy and run the queries</p>','db_press');
				} else {
					$msg_error .= __('<b>Copy and run the queries to correct the problem.</b>','db_press');
				}
				if (count($old_primaries) == 1 &&  $is_old_primaries_type_numeric ) {
					$msg_error .= sprintf(__('<p style="background:#F2F2F2; border:1px solid #EEE; padding:.5rem">ALTER TABLE `%s` MODIFY %s INT NOT NULL AUTO_INCREMENT;<br></p>', 'db_press'), $table, implode(", ", $old_primaries));
				} else if (count($old_primaries) >1) {
					$msg_error .= sprintf(__('<p style="background:#F2F2F2; border:1px solid #EEE; padding:.5rem">ALTER TABLE `%s` drop primary key;<br>
					CREATE UNIQUE INDEX old_primary_key ON `%s` (%s);<br>
					ALTER TABLE `%s` ADD dbp_id BIGINT AUTO_INCREMENT PRIMARY KEY;<br></p>','db_press'), $table, $table, implode(", ", $old_primaries), $table);
				} else {
					$msg_error .= sprintf(__('<p style="background:#F2F2F2; border:1px solid #EEE; padding:.5rem">ALTER TABLE `%s` ADD dbp_id BIGINT AUTO_INCREMENT PRIMARY KEY;<br></p>', 'db_press'), $table);
				}
			}

			if ($action == 'show_create_structure') {
				$sql_sctructure = $this->show_create_structure($table);
			}
			
		
			$indexes = $table_model->get_indexes();
		
			$index_table = new Dbp_html_simple_table();
			if ($table != "" && count($table_model->items) > 0) {
				$this->last_error = dbp_fn::get_max_input_vars(count($table_model->items)*15)	;
			}
			$max_row_allowed = floor(dbp_fn::get_max_input_vars()/15);
		}
		
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
	}
	/**
	 * Mostro il risultato di una query
	 */
	private function table_browse() {
		if (!current_user_can('administrator')) return;
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'database-form2-js', plugin_dir_url( __FILE__ ) . 'js/database-form2.js',[], DB_PRESS_VERSION);
		wp_register_script( 'dbp_database_press_js',  plugin_dir_url( __FILE__ ) . 'js/database-press.js',false, DB_PRESS_VERSION);
		//	wp_add_inline_script( 'mytheme-typekit', 'try{Typekit.load({ async: true });}catch(e){}' );
		wp_add_inline_script( 'dbp_database_press_js', 'dbp_admin_post = "'.esc_url( admin_url("admin-post.php")).'";', 'before' );
		wp_enqueue_script( 'dbp_database_press_js' );

		wp_enqueue_script( 'database-sql-editor-js', plugin_dir_url( __FILE__ ) . 'js/database-sql-editor.js',[], DB_PRESS_VERSION);
		wp_enqueue_script( 'database-press-js-multiqueries', plugin_dir_url( __FILE__ ) . 'js/database-press-multiqueries.js',[], DB_PRESS_VERSION);
		
		$msg =  $msg_error = '';
		if (isset($_COOKIE['dbp_msg'])) {
			$msg =  wp_kses_post(wp_unslash($_COOKIE['dbp_msg']));
		}
		if (isset($_COOKIE['dbp_error'])) {
			$msg_error =  wp_kses_post(wp_unslash($_COOKIE['dbp_error']));
		}	
		$temporaly_files = new Dbp_temporaly_files();
		// $dbp = new Dbp_fn();
        $section =  dbp_fn::req('section', 'home');
       	$action  = dbp_fn::req('action_query', '', 'string');
		$table = (isset($_REQUEST['table'])) ? sanitize_text_field($_REQUEST['table']) : '';
		$table_model 				= new Dbp_model($table);
		$list_of_columns 			= dbp_fn::get_all_columns();
		$show_query = false;
		// cancello le righe selezionate!
		if ($action == "delete_rows" && isset($_REQUEST["remove_ids"]) && is_array($_REQUEST["remove_ids"])) {
			$ids =  dbp_fn::req("remove_ids");
			$result_delete = dbp_fn::delete_rows($ids, wp_kses_post(wp_unslash($_REQUEST['custom_query'])));
			if ($result_delete['error'] != "") {
				$msg_error = $result_delete;
			} else {
				$msg = sprintf(__('The data has been removed. <br> %s', 'db_press'), $result_delete['sql']);
			}
		}

		if ($action == "delete_from_sql") {
			$result_delete = dbp_fn::dbp_delete_from_sql(dbp_fn::req('sql_query_executed'), dbp_fn::req('remove_table_query'));
			if ($result_delete != "") {
				$msg_error = $result_delete;
			} else {
				$msg = __('The data has been removed', 'db_press');
			}
		}

		$custom_query = wp_kses_post(wp_unslash(dbp_fn::req('custom_query', '')));

		// dbp_util_marks_parentheses diventa lentissimo con testi troppo lunghi > 1.000.000 chr 
	
		if (strlen($custom_query) > 150000) {
			$mysqli = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die ('DB CONNECTION ERROR?!');
			$mysqli->multi_query($custom_query);
			$render_content = "/dbp-content-table-import.php";

			$msg_error = "";	
			//Make sure this keeps php waiting for queries to be done
			$count_query = 0;
			do {
				/* store the result set in PHP */
				if ($result = $mysqli->store_result()) {
					while ($row = $result->fetch_row()) {
						$msg_error .= '<p>'.$row[0].'</p>' ;
					}
				}
				$count_query++;
			} while ($mysqli->next_result());

			if ($msg_error == "") {
				if (is_countable($mysqli->error_list) && count($mysqli->error_list) > 0) {
					foreach ($mysqli->error_list as $el) {
						$msg_error .= '<p>'.$el['error'].'</p>' ;
					}
				} else {
					$msg = __('The queries were performed successfully.', 'db_press');
				}
			} 

			$mysqli->close();	
			dbp_fn::get_table_list(false);
		
			$render_content = "/dbp-content-table-with-filter.php";
		
			add_filter( 'dbp_render_sql_btns', [$this, 'browse_table_filter_render_sql_btns'] );
			require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
			return;
		}
		
		$table_model->prepare(dbp_fn::req('custom_query', ''));
		
		$_REQUEST['table'] = $table_model->get_table();
		
		if ($table_model->sql_type() == "multiqueries") {
			$queries = $table_model->get_current_query();
			$ajax_continue = $temporaly_files->store(['total_queries' => count($queries), 'queries_filename' => $temporaly_files->store($queries), 'last_error' => '', 'error_count' => 0, 'report_queries' => [] ]); 
			$info = dbp_fn::execute_multiqueries($ajax_continue);
			$items = $info['items'];
			if ($info['executed_queries'] == $info['total_queries']) {
				$ajax_continue = false;
			}
			$render_content = "/dbp-content-multiquery.php";
		} else {

			// SEARCH in all columns
			$search = dbp_fn::req('search', false, 'remove_slashes'); 
			if ($search && $search != "" &&  in_array($action, ['search','order','limit_start','change_limit'])) {
				$schemas = $table_model->get_schema();
				$filter =[] ; //[[op:'', column:'',value:'' ], ... ];
				foreach ($schemas as $schema) {
					if ($schema->orgtable != ""  && $schema->table != ""  && $schema->name != "") {
						$filter[] = ['op'=>'LIKE', 'column'=> '`'.Dbp_fn::sanitize_key($schema->table).'`.`'.esc_attr($schema->orgname).'`', 'value' => $search];
					}
				}
				if (count($filter) > 0) {
					$table_model->list_add_where($filter, 'OR');
				}
			} else {
				$_REQUEST['search'] = '';
			}
			
			dbp_fn::add_request_filter_to_model($table_model, $this->max_show_items);
			$table_model->add_primary_ids();

			$table_items = $table_model->get_list(true, false);
			$table_model->update_items_with_setting();
		
			dbp_fn::items_add_action($table_model);
		
			$table_model->check_for_filter();
			$table_model->remove_primary_added();
			dbp_fn::remove_hide_columns($table_model);
			
			if ( count($table_model->get_pirmaries()) == 0 && count($table_model->get_query_tables()) > 0 && $msg_error == '' && $table_model->sql_type() == "select") {
				$msg_error = __('This system works better with tables that have only one field set as the autoincrement primary key.','db_press');
				$msg_error .= '<br>'.__('Most of the features have been disabled.','db_press').'<br>';
				$msg_error .= '<br>'.__('If you can modify the table, go to the structure tab and follow the proposed instructions.','db_press').'<br>';
				if ($table_model->table_status() == "DRAFT")  {
					$msg_error .= '<b>'.__('If you can alter the table, go to Structure and follow the instructions.','db_press').'</b>';
				}
			}
			if ($table_model->table_status() == 'CLOSE' && $msg == '' && $msg_error == '') {
				$msg = __('The table can no longer be modified because it is in the "CLOSE" state.', 'db_press');
			}
		

			//var_dump($table_model->items);
			$html_table   = new Dbp_html_table();
			$html_content = $html_table->template_render($table_model); // lo uso nel template
			//print (get_class($table_model) );
			$render_content = "/dbp-content-table-with-filter.php";
		}

		add_filter( 'dbp_render_sql_btns', [$this, 'browse_table_filter_render_sql_btns'] );
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";

	}

	/**
	 * Importa un file sql
	 */
	function import_sql_file() {
		if (!current_user_can('administrator')) return;
		// $dbp = new Dbp_fn();
		$section =  dbp_fn::req('section', 'home');
		$action = dbp_fn::req('action_query', '', 'string');
		$import_table = dbp_fn::req('table', '');
		$this->msg = '';
		$this->last_error = "";
		$render_content = "/dbp-content-table-import.php";
		if (!isset($_FILES['sql_file']['tmp_name']) || $_FILES['sql_file']['tmp_name'] == "") {
			$this->last_error = __('No file uploaded', 'database-press');
			$action = "";
		} else {
			$sql = file_get_contents($_FILES['sql_file']['tmp_name']);
			$mysqli = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die ('DB CONNECTION ERROR?!');
			$mysqli->multi_query($sql);
			//Make sure this keeps php waiting for queries to be done
			$count_query = 0;
			do {
				/* store the result set in PHP */
				if ($result = $mysqli->store_result()) {
					while ($row = $result->fetch_row()) {
						
						$this->last_error .= '<p>'.$row[0].'</p>' ;
					}
				}
				$count_query++;
			} while ($mysqli->next_result());

			if ($this->last_error == "") {
				if (is_countable($mysqli->error_list) && count($mysqli->error_list) > 0) {
					foreach ($mysqli->error_list as $el) {
						$this->last_error .= '<p>'.$el['error'].'</p>' ;
					}
				} else {
					$this->msg = sprintf(__('%s queries executed successfully.', 'db_press'), $count_query );
				}
			} 

			$mysqli->close();	
			dbp_fn::get_table_list(false);
		}
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";

	}
	/**
	 * Quando apri la pagina e carichi un csv
	 */
	function import_csv_file() {
		if (!current_user_can('administrator')) return;
		// carico il csv e ne mostro le opzioni
		if (!isset($_FILES['sql_file'])) {
			wp_redirect( add_query_arg(['section'=>'table-import'], admin_url("admin.php?page=database_press")));
			die();
		}
		$max_row_allowed = floor(dbp_fn::get_max_input_vars()/10);
		// $dbp = new Dbp_fn();
		dbp_fn::require_init();
		$section =  dbp_fn::req('section', 'home');
       	$action = dbp_fn::req('action', '', 'string');
		$import_table = dbp_fn::req('table', '');
		if ($import_table != "" && dbp_fn::exists_table($import_table)) {
			$select_action = "insert_records";
			$current_table = $import_table;
		}
		$temporaly_files = new Dbp_temporaly_files();
		$model_structure = new Dbp_model_structure();
		$name_of_file = $model_structure->change_unique_table_name($_FILES['sql_file']['name']);

		$csv_filename = $temporaly_files->move_uploaded_file('sql_file');
		if ($csv_filename == "") {
			$csv_structure = [];
			$this->last_error = $temporaly_files->last_error;
			$action = '';
		} else {
			$csv_delimiter = $temporaly_files->find_csv_delimiter($csv_filename);
			$csv_items = $temporaly_files->read_csv($csv_filename, $csv_delimiter, true, 20);
			$csv_first_row_as_headers = $allow_use_first_row = $temporaly_files->csv_allow_to_use_first_line(reset($csv_items));
			if (!$allow_use_first_row) {
				$csv_items = $temporaly_files->read_csv($csv_filename, $csv_delimiter, false, 20);
			}

			$csv_structure = $temporaly_files->csv_structure($csv_filename, $csv_delimiter, $csv_first_row_as_headers);

			$csv_structure = self::csv_create_table_add_primary($csv_structure);
			$csv_structure = self::csv_change_name_if_duplicate($csv_structure);
			
			if ($csv_structure == false) {
				$this->last_error = __("It doesn't look like a valid csv", 'db_press');
				$action = '';
			}
		}
		$render_content = "/dbp-content-table-import.php";
	
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
	}

	/**
	 * SU IMPORT CSV quando premo il bottone UPDATE PREVIEW
	 * Aggiorno le opzioni del csv Mostra le impostazioni per l'importazione del csv
	 */
	function execute_csv_data() {
		if (!current_user_can('administrator')) return;
		// $dbp = new Dbp_fn();
		dbp_fn::require_init();
		$max_row_allowed = floor(dbp_fn::get_max_input_vars()/10);
		$section =  dbp_fn::req('section', 'home');
       	$action = dbp_fn::req('action', '', 'string');
		$import_table = dbp_fn::req('table', '');
		$temporaly_files = new Dbp_temporaly_files();
		
		$csv_filename = dbp_fn::req('csv_temporaly_filename');
		$csv_delimiter = dbp_fn::req('csv_delimiter','');
		if ($csv_delimiter == '') {
			$csv_delimiter = $temporaly_files->find_csv_delimiter($csv_filename);
		}
		$allow_use_first_row = dbp_fn::req('allow_use_first_row', 1);
		$model_structure = new Dbp_model_structure();
		$name_of_file = $model_structure->change_unique_table_name(dbp_fn::req('csv_name_of_file'));

		$csv_first_row_as_headers = dbp_fn::req('csv_first_row_as_headers', false, 'boolean');
		$csv_items = $temporaly_files->read_csv($csv_filename, $csv_delimiter, $csv_first_row_as_headers, 20);
		
		if (is_array($csv_items)) {
			foreach ($csv_items as &$item) {
				foreach ($item as &$i) {
					if (!is_array($i) && !is_object($i)) {
						$i = htmlentities($i);
					}
				}
			} 
		}
		$csv_structure = $temporaly_files->csv_structure($csv_filename, $csv_delimiter, $csv_first_row_as_headers);
		$csv_structure = self::csv_create_table_add_primary($csv_structure);
		$csv_structure = self::csv_change_name_if_duplicate($csv_structure);
		$render_content = "/dbp-content-table-import.php";
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
	}

	/**
	 * Verifica se bisogna aggiungere una chiave primaria alla tabella
	 * @param Array $csv_strcture
	 * @return Array
	 */
	private function csv_create_table_add_primary($csv_structure) {
		$has_primary = false;
		foreach ($csv_structure as $struct) {
			//var_dump ($struct);
			if ($struct->primary == "t") {
				$has_primary = true;
				break;
			}
		}
		if (!$has_primary && is_array($csv_structure)) {
			array_unshift($csv_structure, json_decode('{"field_name":"dbp_id", "field_type":"INT", "auto_increment":"t", "field_length":"11", "attributes":"UNSIGNED", "null":"f", "default":"", "primary": "t", "ai":"t", "comment":"", "preset":"pri"}'));
		}
		return $csv_structure;
	}
	/**
	 * Verifica se bisogna aggiungere una chiave primaria alla tabella
	 * @param Array $csv_strcture
	 */
	private function csv_change_name_if_duplicate($csv_structure) {
		$name_unique =[];
		foreach ($csv_structure as $struct) {
			//var_dump ($struct);
			$count = 0;
			$ori_name = substr($struct->field_name,0,60);
			while(in_array($struct->field_name, $name_unique)) {
				$count++;
				$struct->field_name = $ori_name . "_" . $count;
			}
			$name_unique[] = $struct->field_name;
		}
		return $csv_structure;
	}

	/**
	 * Crea la tabella dal csv
	 */
	function create_table_csv_data() {
		global $wpdb;
		if (!current_user_can('administrator')) return;
		// $dbp = new Dbp_fn();
		dbp_fn::require_init();
		$max_row_allowed = floor(dbp_fn::get_max_input_vars()/10);
		$section =  dbp_fn::req('section', 'home');
       	$action = dbp_fn::req('action', '', 'string');
		$import_table = dbp_fn::req('table', '');
		$temporaly_files = new Dbp_temporaly_files();
		$csv_filename = sanitize_text_field($_REQUEST['csv_temporaly_filename']);
		$csv_delimiter = sanitize_text_field($_REQUEST['csv_delimiter']);
		$csv_first_row_as_headers = dbp_fn::req('csv_first_row_as_headers', false, 'boolean');
		$csv_items = $temporaly_files->read_csv($csv_filename, $csv_delimiter, $csv_first_row_as_headers, 20);
		
		$csv_structure = $temporaly_files->csv_structure($csv_filename, $csv_delimiter, $csv_first_row_as_headers);

		$model_structure = new Dbp_model_structure(sanitize_text_field($_REQUEST['csv_name_of_file']));
		$name_of_file = $model_structure->change_unique_table_name();
		if (isset($_REQUEST['use_prefix']) && $_REQUEST['use_prefix'] == 1) {
			//print "<p>USEPREFIX ".$_REQUEST['use_prefix']."</p>";
			$model_structure->use_prefix = true;
		}

		$execute_query = true;
		$csv_structure_table_created = [];
		if (isset($_REQUEST['form_create']["field_name"]) && is_countable($_REQUEST['form_create']["field_name"])) {
			foreach ($_REQUEST['form_create']["field_name"] as $key => $column_name) {
				$column_name = sanitize_text_field($column_name);
				$key = sanitize_text_field($key);
				if ($column_name != "") {
					switch ($_REQUEST['form_create']["field_type"][$key]) {
						case 'pri':
							$model_structure->insert_column($column_name, 'BIGINT', '', '', true, 'SIGNED', true );
							break;
						case 'varchar':
							$model_structure->insert_column($column_name, 'VARCHAR', '255');
							break;
						case 'text':
							$model_structure->insert_column($column_name, 'TEXT');
							break;
						case 'int':
							$model_structure->insert_column($column_name, 'INT');
							break;
						case 'decimal':
							$model_structure->insert_column($column_name, 'DECIMAL','9,2');
							break;
						case 'date':
							$model_structure->insert_column($column_name, 'DATE');
							break;
						case 'datetime':
							$model_structure->insert_column($column_name, 'DATETIME');
							break;
					}
					
					$csv_structure_table_created[] = (object)[
						'name'=> sanitize_text_field($_REQUEST['form_create']["csv_name"][$key]),
						'field_name'=>$column_name,
						'preset'=> sanitize_text_field($_REQUEST['form_create']["field_type"][$key]),
					];
				
					if ($model_structure->last_error != "") {
						$execute_query = false;
						$this->last_error = $model_structure->last_error;
						$csv_structure = self::csv_create_table_add_primary($csv_structure);
						$csv_structure = self::csv_change_name_if_duplicate($csv_structure);
					}
				}
			}
		}
		$sql = "";
		// TODO: Devo centralizzare la creazione delle tabelle
		$select_action = "create_database";
		if ($execute_query) {
			$sql = $model_structure->get_create_sql();
			if ($model_structure->last_error != "") {
				$this->last_error = $model_structure->last_error;
				$csv_structure = $csv_structure_table_created;
				unset($csv_structure_table_created);
				$csv_structure = self::csv_create_table_add_primary($csv_structure);
				$csv_structure = self::csv_change_name_if_duplicate($csv_structure);
			} else {
				$result = $wpdb->query($sql);
				if (is_wp_error($result) || !empty($wpdb->last_error)) {
					$this->last_error = $wpdb->last_error;
					$csv_structure = $csv_structure_table_created;
					unset($csv_structure_table_created);
				} else {
					$import_table = $model_structure->get_table_name();
					$this->msg = __('Table <b>'. $model_structure->get_table_name(). '</b> created','db_press');
					dbp_fn::update_dbp_option_table_status($model_structure->get_table_name(), 'DRAFT', 'Table created with the csv import procedure');
				}
				dbp_fn::$table_list = [];
				$this->table_list = dbp_fn::get_table_list();
				$select_action = "insert_records";
				$current_table = $model_structure->get_table_name();
			}
		}
		$render_content = "/dbp-content-table-import.php";
		require dirname( __FILE__ ) . "/partials/dbp-page-base.php";
	}

	/**
	 * @param string $table
	 */
	function show_create_structure($table) {
		global $wpdb;
		if (!current_user_can('administrator')) return;
		$sql = 'SHOW CREATE TABLE `' . Dbp_fn::sanitize_key($table) . '`';
		$result = $wpdb->get_row($sql, 'ARRAY_A');
		if (isset($result['Create Table'])) {
			return ($result['Create Table']);
		} else {
			return '';
		}
	}

	function filter_render_sql_btns($btns) {
		unset($btns['cancel']);
		$btns = array_merge(['go_custom' =>
		'<div id="dbp-bnt-go-query"  class="dbp-submit dbp-btn-show-sql-edit"  onclick="dbp_submit_custom_query()">'. __('Go','db_press').'</div>'],$btns) ;
		
		return $btns;
	}
	function home_render_sql_btns($btns) {
		unset($btns['cancel']);	
		$btns = array_merge(['go_custom' =>
		'<div id="dbp-bnt-go-query"  class="dbp-submit dbp-btn-show-sql-edit"  onclick="jQuery(\'#table_sql_home\').submit()">'. __('Go','db_press').'</div>'],$btns) ;
		return $btns;
	}

	function browse_table_filter_render_sql_btns($btns) {
		$btns = array_merge(['save_query' =>
		'<div class="dbp-right-query-btns">
		<div id="dbp-bnt-save-query" class="button js-show-only-select-query"  onclick="dbp_show_save_sql_query()">'. __('Create list from query','db_press').'</div></div>'], $btns) ;
		
		$btns = array_merge(['go_custom' =>
		'<div id="dbp-bnt-go-query" class="dbp-submit" onclick="dbp_submit_table_filter(\'custom_query\')">'. __('Go','db_press').'</div>'], $btns) ;
		$btns['search'] =  '<div  class="button js-show-only-select-query  dbp-btn-disabled js-btn-disabled" onclick="dbp_search_sql()">'. __('Search','db_press').'</div>';

		return $btns;
	}

}
