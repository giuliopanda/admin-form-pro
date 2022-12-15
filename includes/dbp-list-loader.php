<?php
/**
 * Gestisco il filtri e hook della form 
 *
 * @package    DATABASE TABLE
 * @subpackage DATABASE TABLE/INCLUDES
 * @internal
 */
namespace admin_form;

class  Dbp_pro_list_loader {
	/**
	 * @var Object $saved_queries le ultime query salvate per tipo
	 */

	public function __construct() {
        //add_action( 'dbp_create_list_override', [$this, 'create_list']);
		add_action( 'admin_post_dbp_create_list_from_sql', [$this, 'create_list']);	
        // ???

        // add_action( 'dbp_list_form_single_table_query_part',  [$this, 'form_single_table_query_part'], 10, 2 );
    
        // nel tab list view formatting aggiungo tutti gli special fields
        add_filter('dbp_list_structure_fields_type', [$this, 'list_structure_fields_type']);
        // sempre in tab list view formatting per il salvataggio della query custom quando si clicca 'choose column to show'
        add_filter('dbp_list_structure_save', [$this, 'list_structure_save']);

        add_action( 'dbp_list_structure_after_btns', [$this, 'list_structure_after_btns'], 10, 1);
      
        // l'ajax mostra l'elenco delle colonne di una query pee permettere di scegliere quali visualizzare
		add_action( 'wp_ajax_af_columns_sql_query_edit', [$this, 'columns_sql_query_edit']);
        // l'ajax Una volta scelto l'elenco delle colonne da visualizzare modifica la query con il nuovo select
		add_action( 'wp_ajax_af_edit_sql_query_select', [$this, 'edit_sql_query_select']);
		// l'ajax per generare il csv che salva sui file temporanei e poi li puoi scaricare
		add_action( 'wp_ajax_af_download_csv', [$this, 'af_download_csv']);

		// l'ajax viasualizzare la form per fare un marge con un'altra tabella
		add_action( 'wp_ajax_af_merge_sql_query_edit', [$this, 'merge_sql_query_edit']);
		// trova le colonne di una tabella
		add_action('wp_ajax_af_merge_sql_query_get_fields', [$this, 'merge_sql_query_get_fields']);
		// Genera la query con il nuovo left join
		add_action('wp_ajax_af_edit_sql_query_merge', [$this, 'edit_sql_query_merge']);
		// Apre la sidebar per aggiungere i metadata e seleziona la tabella
		add_action('wp_ajax_af_metadata_sql_query_edit', [$this, 'metadata_sql_query_edit']);
		// Sempre per i metadata trova i campi da visualizzare
		add_action('wp_ajax_af_metadata_sql_query_edit_step2', [$this, 'metadata_sql_query_edit_step2']);
		// Genera la query con l'aggiunta dei metadata
		add_action('wp_ajax_dbp_edit_sql_addmeta', [$this, 'edit_sql_addmeta']);
		
		// Verifico una query mentre la si sta scrivendo
		add_action('wp_ajax_af_check_query', [$this, 'check_query']);

		// l'ajax per generare le query per eliminare tutti i record di una query
		add_action( 'wp_ajax_af_check_delete_from_sql', [$this, 'af_check_delete_from_sql']);
		// l'ajax preparare gli id da rimuovere successiva a af_check_delete_from_sql
		add_action( 'wp_ajax_af_prepare_query_delete', [$this, 'prepare_query_delete']);
		// Dopo aver preparato i dati da rimuovere, li rimuovo tutti.
		add_action( 'wp_ajax_af_sql_query_delete', [$this, 'sql_query_delete']);
		// l'ajax per confermare l'eliminazione di uno o più record 
		add_action( 'wp_ajax_af_delete_confirm', [$this, 'af_delete_confirm']);

		add_action('dbp_list_browse_after_content', [$this, 'list_browse_after_content'], 10, 2);
		add_action('dbp_page_admin_menu_after_title', [$this, 'list_browse_after_content'], 10, 2);

		add_action( 'wp_ajax_af_test_formula', [$this, 'test_formula']);
        add_action( 'wp_ajax_af_recalculate_formula', [$this, 'recalculate_formula']);

    }


    /**
     * Permette di creare una lista sia a partire da una query che da una tabella
     */
    function create_list() {
        global $wpdb;
		
        ADFO_fn::require_init();
        // SE c'è una query la scrivo
        if (!isset($_REQUEST['new_sql']) || $_REQUEST['new_sql'] == "") {
            wp_redirect( admin_url("admin.php?page=admin_form&section=list-all&msg=create_list_error"));
			die();
        }
        $title = wp_strip_all_tags( ADFO_fn::get_request('new_title'));
       // TODO: if (!is_admin()) return;
	    $sql = html_entity_decode ( wp_kses_post(wp_unslash($_REQUEST['new_sql'])));
		$post = ADFO_functions_list::get_post_dbp($id);
		$table_model = new ADFO_model();
		$table_model->prepare($sql);
		if ($table_model->sql_type() != "select") {
			//TODO Al momento il messaggio di errore non è usato da impostare con i cookie !!!!
			$msg = sprintf(__('1 Only a single select query is allowed in the lists %s', 'admin_form'), $table_model->get_current_query());

			wp_redirect( admin_url("admin.php?page=admin_form&section=list-sql-edit&msg=list_created&dbp_id=".$id));
			die();
		}
        $create_list = array(
            'post_title'    => $title,
            'post_content'  => '',
			'post_excerpt'  => wp_kses_post(wp_unslash($_REQUEST['new_description'])),
            'post_status'   => 'publish',
            'comment_status' =>'closed',
            'post_author'   => get_current_user_id(),
            'post_type' => 'dbp_list'
        );
        $id = wp_insert_post($create_list);
        if (is_wp_error($id) || $id == 0) {
            wp_redirect( admin_url("admin.php?page=admin_form&section=list-all&msg=create_list_error"));
			die();
        } 
		$limit = $table_model->remove_limit();
		if ($limit > 0) {
			$post->post_content['sql_limit'] = $limit;
		}
		$table_model->list_add_limit(0, 1);
		$items = $table_model->get_list();

		$from_query = $table_model->get_partial_query_from(true);
		$from = [];
		foreach ($from_query as $f) {
			$from[$f[1]] = $f[0]; 
		}
		$post->post_content['sql_from'] = $from;

		if (isset($post->post_content['list_setting'])) {
			$list_setting = $post->post_content['list_setting'];
		} else {
			$list_setting = [];
		}
		$setting_custom_list =  ADFO_functions_list::get_list_structure_config($items, $list_setting);
		$post->post_content['sql'] = $sql;

		$post->post_content['list_setting'] = [];
		foreach ($setting_custom_list as $column_key => $list) {
			$post->post_content['list_setting'][$column_key] =  $list->get_for_saving_in_the_db();
		}

		// Salvo le chiavi primarie e lo schema
		$post->post_content['primaries'] = $table_model->get_pirmaries();	
		$post->post_content['schema'] = reset($table_model->items);

		$dbp_admin_show  = ['page_title'=>sanitize_text_field($title), 'menu_title'=>sanitize_text_field($title), 'menu_icon'=>'dashicons-database', 'menu_position' => 120, 'capability'=>'dbp_manage_'.$id, 'slug'=> 'dbp_'.$id, 'show' => 1, 'status' => 'publish'];
		add_post_meta($id,'_dbp_admin_show', $dbp_admin_show, false);
		update_post_meta($id, '_dbp_list_config', $post->post_content, true);
		$role = get_role( 'administrator' );
		$role->add_cap( 'dbp_manage_'.$id, true );
		// ridirigo alla gestione della form 
		wp_redirect( admin_url("admin.php?page=admin_form&section=list-form&msg=list_created&dbp_id=".$id));
        
        die;
    }

    

    /**
     * Nella creazione della form se c'è un lookup è possibile aggiungere parti di query where
     */
    function form_lookup_test_query() {
		global $wpdb;
		ob_start();
		ADFO_fn::require_init();
		$table_model = new ADFO_model(sanitize_text_field($_REQUEST['table']));
		if (isset($_REQUEST['where']) && $_REQUEST['where'] != "") {
			$sql = $table_model->get_current_query()." WHERE ".sanitize_text_field($_REQUEST['where']);
			$table_model->prepare($sql);
		}
		$table_model->list_change_select('`'.esc_sql(sanitize_text_field($_REQUEST['field_id'])).'` AS val, `'.esc_sql(sanitize_text_field($_REQUEST['label'])).'` AS txt');

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



    /**
     * Aggiunge i tipi da visualizzare in list_structure
     */
    public function list_structure_fields_type($fields) {
        $fields['Special Fields']['LOOKUP'] = 'Lookup';
		return $fields;
    }

    /**
     * Salvo la query in list_structure_save per 
     * scegliere quali campi mostrare e quali no
     */
    public function list_structure_save($post_post_content) {
        // Choose columns to show
        if (isset($_REQUEST['custom_query']) && $_REQUEST['custom_query'] !== '') {
            // aggiungo tutti i primary id e li salvo a parte 
            $table_model = new ADFO_model();
            $custom_query = wp_kses_post( wp_unslash($_REQUEST['custom_query']));
            $table_model->prepare($custom_query);
            
            if ($table_model->sql_type() != "select") {
               // return [ __('Only a single select query is allowed in the lists', 'admin_form'), true];
            } else {
                $table_model->get_list();
                if ($table_model->last_error == "") {
                    $post_post_content['sql'] = html_entity_decode($table_model->get_current_query());
                } else {
                 //   return [sprintf(__("I didn't save the query because it was wrong!.<br><h3>Error:</h3>%s<h3>Query:</h3>%s",'admin_form'), $table_model->last_error, $post_post_content['sql']), true];
                }
            }
        }
        return $post_post_content;
    }


    public function list_structure_after_btns($table_model) {
        ?>
        <div id="dbp-bnt-columns-query" class="button js-show-only-select-query" onclick="af_columns_sql_query_edit()"><?php _e('Choose column to show*', 'admin_form'); ?></div>
        <div style="display:none">
            <?php ADFO_html_sql::render_sql_from($table_model, false); ?>
        </div>
        <p>* <?php _e('After modifying the query columns, the form will be saved automatically to allow you to view the modifications made', 'admin_form'); ?></p>
        <?php
    }


    /**
	 *  Estraggo tutte le colonne possibili che si possono visualizzare da una query.
	 *  Chiamato dal bottone ORGANIZE COLUMNS
	 */
	function columns_sql_query_edit() {
		ADFO_fn::require_init();
		$table_model = new ADFO_model();
		$sql = html_entity_decode(ADFO_fn::get_request('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$all_fields = $table_model->get_all_fields_from_query();
			//Todo trovo le colonne originali della query per impostare i checkbox checked.
			$table_model->prepare($sql);
			
			$header = $table_model->get_schema();
			if ($table_model->last_error != "") {
				wp_send_json(['msg' => sprintf(__("Ops Query Error: %s ",'admin_form'), $table_model->last_error)]);
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
				wp_send_json(['msg' => __("I'm sorry, but I can't extract the query columns",'admin_form'),  'html'=>'']);
			}
		}  else {
			wp_send_json(['msg' => __("I'm sorry, but I can't extract the query columns",'admin_form'),  'html'=>'']);
		}
		die();
	}


    /**
	 * Ricevo una query e un elenco di colonne da visualizzare. Ritorna la query con il nuovo select
	 * Chiamato dal bottone ORGANIZE COLUMNS
	 */
	function edit_sql_query_select() {
		ADFO_fn::require_init();
		$table_model = new ADFO_model();
		$table_model->prepare(ADFO_fn::get_request('sql', ''));
		if (ADFO_fn::get_request('sql') != "" && $table_model->sql_type() == "select") {
			// preparo la stringa con il nuovo select
			$choose_columns = ADFO_fn::get_request('choose_columns');
			$columns_as = ADFO_fn::get_request('label');
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
			$html = ADFO_html_sql::get_html_fields($table_model);

			wp_send_json(['sql' => $new_query, 'html'=>$html]);
		} else {
			//TODO ERROR!
			wp_send_json(['msg' => __("I'm sorry, but I can't extract the query columns",'admin_form')]);
		}
		die();
	}

    /**
	 * Prepara il csv 
	 */
	function af_download_csv() {
		ADFO_fn::require_init();
		$temporaly_files = new ADFO_temporaly_files();
		$csv_filename 	= ADFO_fn::get_request('csv_filename', '');
		$request_ids 	= ADFO_fn::get_request('ids', false);
		$limit_start 	= ADFO_fn::get_request('limit_start', 0);
		$dbp_id		 	= isset($_REQUEST['dbp_id']) ? absint($_REQUEST['dbp_id']) : 0;
		$data_type		= isset($_REQUEST['data_type']) ? sanitize_text_field($_REQUEST['data_type']) : '';
		if ($dbp_id > 0) {
			$post = ADFO_functions_list::get_post_dbp($dbp_id);
		}
		if ($limit_start == 0) {
			$temporaly_files->clear_old();
		}
		if ($request_ids == false || !is_countable($request_ids)) {
			// estraggo i dati dalla query
			$line = 2000;
			$next_limit_start = $limit_start + $line;
			
			$sql = ADFO_fn::get_request('sql', '');
			if ($data_type == 'raw') {
				$form = new ADFO_class_form($dbp_id);
				$table_model = $form->table_model;
				
			} else {
				$table_model = new ADFO_model();
				if ($sql == '' && $dbp_id > 0) {
					$sql = $post->post_content['sql'];
				} 
				$table_model->prepare($sql);
			}
			$table_model->list_add_limit($limit_start, $line);
			if ($dbp_id > 0) {
				if (isset($post->post_content['sql_order']['sort']) &&  isset($post->post_content['sql_order']['field'])) {
					$_REQUEST['sort']['field'] = $post->post_content['sql_order']['field'] ;
					$table_model->list_add_order($post->post_content['sql_order']['field'], $post->post_content['sql_order']['sort']);
				}
			}
			$table_items = $table_model->get_list();
			$count = $table_model->get_count();
			if ($dbp_id > 0 && $data_type != 'raw') {
				$table_model->update_items_with_setting($post);
				ADFO_fn::remove_hide_columns($table_model);
				$table_items = [];
				$table_items = $table_model->items;
			}
			// verifico che la query non abbia dato errore
			if ($table_model->last_error ) {
				$error =  $table_model->last_error."<br >".$table_model->get_current_query();
				wp_send_json(['error'=>$error]);
				die();
			}
			if (count($table_items) < 2 && $limit_start+2 < $count) {
				wp_send_json(['error'=>__("There was an unexpected problem", 'admin_form')]);
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
				$table_model = new ADFO_model();
				$table_model->prepare(ADFO_fn::get_request('sql', ''));
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
	 * Estraggo i parametri per preparare la form per aggiungere un join ad una query
	 * Chiamato dal bottone MERGE
	 */
	function merge_sql_query_edit() {
		ADFO_fn::require_init();
		$table_model = new ADFO_model();
		$sql = html_entity_decode(ADFO_fn::get_request('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$all_fields = $table_model->get_all_fields_from_query();
			$all_tables = ADFO_fn::get_table_list();
			if (is_countable($all_fields) && count($all_fields) > 0 && is_countable($all_tables) && count($all_tables) > 0) {
				wp_send_json(['all_fields' => $all_fields, 'all_tables' => $all_tables['tables']]);
			} else {
				wp_send_json(['msg' => __('The current query cannot be joined to other tables','admin_form')]);
			}
		} else {
			wp_send_json(['msg' => __('The current query cannot be joined to other tables','admin_form')]);
		}
		die();
	}

	/**
	 * Estraggo i parametri per preparare la form per aggiungere un join ad una query
	 * Chiamato dal bottone MERGE
	 */
	function merge_sql_query_get_fields() {
		ADFO_fn::require_init();
		$table = esc_sql(ADFO_fn::get_request('table'));
		$all_columns = ADFO_fn::get_table_structure($table, true);
		wp_send_json(['all_columns' => $all_columns]);
		die();
	}

	/**
	 * Genero la query con il  join
	 */
	function edit_sql_query_merge() {
		global $wpdb;
		ADFO_fn::require_init();
		if (!isset($_REQUEST['dbp_merge_table']) || !isset($_REQUEST['dbp_merge_column']) ||  !isset($_REQUEST['dbp_ori_field'])) {
			wp_send_json(['msg' => __('All fields are required','admin_form')]);
			die();
		}
		//var_dump ($_REQUEST);
		$table_model = new ADFO_model();
		$sql = html_entity_decode(ADFO_fn::get_request('sql'));
		$table_model->prepare($sql);
		if ($sql != "" && $table_model->sql_type() == "select") {
			$sql_schema = $table_model->get_schema();
			$temp_curr_query = $table_model->get_current_query();
			// trovo l'alias della tabella di cui si sta facendo il join
			// TODO ho una funzione apposta per questo da sostituire
			$table_alias_temp  = substr(ADFO_fn::clean_string(str_replace($wpdb->prefix, "", sanitize_text_field($_REQUEST['dbp_merge_table']))),0 ,3);
			if (strlen($table_alias_temp) < 3 ) {
				$table_alias_temp = $table_alias_temp.substr(md5($table_alias_temp),0 , 2);
			}
			$table_alias = $table_alias_temp;
			$count_ta = 1;
			while(stripos($temp_curr_query, $table_alias.'`') !== false || stripos($temp_curr_query, $table_alias.' ') !== false) {
				$table_alias = $table_alias_temp.''.$count_ta;
				$count_ta++;
			}
			$table_alias = ADFO_fn::sanitize_key($table_alias);
			// compongo la nuova porzione di query
			$join = strtoupper(sanitize_text_field($_REQUEST['dbp_merge_join']));
			if (!in_array($join, ['INNER JOIN','LEFT JOIN','RIGHT JOIN'])) {
				$join ='INNER JOIN';
			}
			$join = $join.' `'.ADFO_fn::sanitize_key(sanitize_text_field($_REQUEST['dbp_merge_table'])).'` `'.$table_alias.'`';
			$join .= " ON `" . $table_alias . "`.`" . ADFO_fn::sanitize_key(sanitize_text_field($_REQUEST['dbp_merge_column'])) . '` = '. ADFO_fn::sanitize_key(sanitize_text_field($_REQUEST['dbp_ori_field']));
			// la unisco alla query originale
			//$table_model->list_add_select(''); // serve per convertire l'* in table.*
			$table_model2 = new ADFO_model();
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
						
						$new_name = ADFO_fn::get_column_alias(strtolower($table_alias . '_' .substr(str_replace(" ", "_", $field->name), 0, 50)), $sql_query_temp);
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
		$html = ADFO_html_sql::get_html_fields($table_model);

		wp_send_json(['sql' => $table_model->get_current_query(), 'html'=>$html]);
		die();
	}

	/**
	 * Ritorna i dati per generare la form per l'aggiunta dei metadati alla query
	 * Chiamato dal bottone Add metadata
	 */
	function metadata_sql_query_edit() {
		ADFO_fn::require_init();
		$table_model = new ADFO_model();
		$sql = html_entity_decode(ADFO_fn::get_request('sql'));
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
							$pris[$field->orgtable] = ADFO_fn::get_primary_key($field->orgtable);
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
				wp_send_json(['msg' => __("I can't find any linkable metadata",'admin_form')]);
			}
		
			$all_tables = ADFO_fn::get_table_list();
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
				wp_send_json(['msg' => __("I can't find any linkable metadata",'admin_form')]);
			}

		} else {
			wp_send_json(['msg' => __("The current query cannot be linked to metadata",'admin_form')]);
		}
		die();
	}

	/**
	 * Estraggo i meta_key, meta_value dalla tabella meta 
	 * Chiamato dopo il bottone Add metadata dal select della tabella
	 */
	function metadata_sql_query_edit_step2() {
		global $wpdb;
		ADFO_fn::require_init();
		$table2 = ADFO_fn::get_request('table2');
		$sql_table_temp = explode("::", $table2);
		$sql = html_entity_decode(ADFO_fn::get_request('sql'));
		$sql_table = array_pop($sql_table_temp);
	
		$structure = ADFO_fn::get_table_structure($sql_table);
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
			$list_db = $wpdb->get_results('SELECT DISTINCT meta_key FROM `'.ADFO_fn::sanitize_key($sql_table).'` ORDER BY meta_key ASC');
			if (is_countable($list_db)) {
				foreach ($list_db as $d) {
					$list[] = $d->meta_key;
				} 
			}
		}
		// cerco di capire quali metadata sono stati già aggiunti 
		$table_model = new ADFO_model();
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
		ADFO_fn::require_init();
		$choose_meta = array_map('sanitize_text_field', $_REQUEST['choose_meta']);
		$choose_meta = ADFO_fn::sanitize_text_recursive($_REQUEST['choose_meta']);
		$already_checked_meta =  (isset($_REQUEST['altreadychecked_meta']) && is_array($_REQUEST['altreadychecked_meta'])) ? array_filter(ADFO_fn::sanitize_text_recursive($_REQUEST['altreadychecked_meta'])) : []; 

		$pri = sanitize_text_field($_REQUEST['pri_key']);
		$parent_id = sanitize_text_field($_REQUEST['parent_id']);
		$table2 =  ADFO_fn::get_request('dbp_meta_table');
		$_sql_table_temp = array_map('sanitize_text_field', explode("::", $table2));
		$_parent_table_temp = array_shift($_sql_table_temp); // la tabella.primary_id su cui sono collegati i meta 
		$_parent_table_temp =  explode(".", $_parent_table_temp);
		$parent_table_id = array_pop($_parent_table_temp); // l'id della tabella originale
		$parent_table = implode('.', $_parent_table_temp); // la tabella originale
		// manca il primary_id della tabella principale!
		$table = array_shift($_sql_table_temp); // la tabella dei meta

		$sql = wp_kses_post( wp_unslash($_REQUEST['sql']));
		$table_model = new ADFO_model();
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
					$alias = ADFO_fn::get_table_alias($table, $sql." ".implode(", ",$temp_sql_from), str_replace("_","",$meta));
					$temp_sql_from[] = ' LEFT JOIN `'.$table.'` `'.$alias.'` ON `'.$alias.'`.`'.$parent_id.'` = `'.$parent_table.'`.`'.$parent_table_id.'` AND `'.$alias.'`.`meta_key` = \''.esc_sql($meta).'\'';
					$temp_sql_select[] = '`'.$alias.'`.`meta_value` AS `'.ADFO_fn::get_column_alias($meta, $sql).'`';
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
		$html = ADFO_html_sql::get_html_fields($table_model);
		wp_send_json(['sql' => $table_model->get_current_query(), 'html'=>$html]);
		die();
	}

	

	/**
	 * Testa una query. Verifica se è un select.
	 *
	 * @return void
	 */
	function check_query() {
		global $wpdb;
		
		ADFO_fn::require_init();
		$response = ['is_select' => 0, 'error' => ''];
		$sql = ADFO_fn::get_request('sql','','remove_slashes');
		$table_model = new ADFO_model();
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
	 * Calcola quali record sta per eliminare a seconda della query e delle primary ID
	 */
	public function af_delete_confirm() {
		ADFO_fn::require_init();
		$json_send = [];
		//$json_send = ['error' => '', 'items' => '', 'checkboxes'];
		if (!isset($_REQUEST['ids']) || !is_countable($_REQUEST['ids'])) {
			$json_send['error'] = __('I have not found any results. Verify that the primary key of each selected table is always displayed in the MySQL SELECT statement.', 'admin_form');
			wp_send_json($json_send);
			die();
		}
		if (isset($_REQUEST['dbp_id']) && $_REQUEST['dbp_id']  > 0) {
			$json_send = ADFO_fn::prepare_delete_rows($_REQUEST['ids'],'', $_REQUEST['dbp_id']);
        } else if ($_REQUEST['sql'] != "") {
			$ids = ADFO_fn::sanitize_absint_recursive($_REQUEST['ids']);
			//TODO security nessun sql deve passare su request!
			$json_send = ADFO_fn::prepare_delete_rows($ids, wp_kses_post( wp_unslash($_REQUEST['sql'])) );
        } else {
			$json_send['error'] = __('Something wrong', 'admin_form');
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
	function af_check_delete_from_sql() {
		ADFO_fn::require_init();
		$errors = [];
		$table_model = new ADFO_model();
		$table_model->prepare(ADFO_fn::get_request('sql', ''));
		$table_items = $table_model->get_list();
		if ($table_model->last_error ) {
            $error =  $table_model->last_error."<br >".$table_model->get_current_query();
			wp_send_json(['items'=>[],'error'=>$error]);
			die();
        }
        if (count($table_items) < 2) {
			wp_send_json(['items'=>[],'error'=>__("There are no records to delete", 'admin_form')]);
			die();
        }
		
		$header = array_shift($table_items);
		// trovo le tabelle interessate
		$temp_groups = [];
		foreach ($header as $key=>$th) {
			if ($th['schema']->table == '' OR $th['schema']->orgtable == '') continue;
			$id = ADFO_fn::get_primary_key($th['schema']->orgtable);
			$option = ADFO_fn::get_dbp_option_table($th['schema']->orgtable);
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
		ADFO_fn::require_init();
		$errors = [];
		$table_model = new ADFO_model();
		$tables = ADFO_fn::get_request('tables', 0);
		$limit_start = ADFO_fn::get_request('limit_start', 0);
		$limit = 1000;
		$total = ADFO_fn::get_request('total', 0);
		$filename = ADFO_fn::get_request('dbp_filename', '');
		$table_model->prepare(ADFO_fn::get_request('sql', ''));
		$table_model->add_primary_ids();
		$table_model->list_add_limit($limit_start, $limit);
		$table_model->get_list();
		$table_model->update_items_with_setting();

		if ($total == 0) {
			$total = $table_model->get_count();
		}
		$data_to_delete = [];
		$table_items = $table_model->items;
		$temporaly_file = new ADFO_temporaly_files();
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
		ADFO_fn::require_init();
		$filename = ADFO_fn::get_request('dbp_filename', '');
		$temporaly_file = new ADFO_temporaly_files();
		$data_to_delete = $temporaly_file->read($filename);
		$total = ADFO_fn::get_request('total', 0);
		$base_executed = $executed = ADFO_fn::get_request('executed', 0);
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
	 * mostra i bottoni per il bulk
	 */
	function list_browse_after_content($table_bulk_ok, $table_model) {
		if ($table_model->last_error === false) : ?>
			<?php 
			$max_input_vars = (int)ADFO_fn::get_max_input_vars();
			?>
			<div class="dbp-table-footer">
				<div class="tablenav-pages dbp-table-footer-left">
					<div class="alignleft actions bulkactions">
						<select id="dbp_bulk_action_selector_bottom">
						<option value="-1"><?php _e('Bulk actions', 'admin_form'); ?></option>
							<option value="download" class="hide-if-no-js"><?php _e('Download'); ?></option>
							<?php if  ($table_bulk_ok) : ?>
							<option value="delete"><?php _e('Delete'); ?></option>
							<?php endif; ?>
						</select>
						<select id="dbp_bulk_on_selector_bottom">
						<?php if ($max_input_vars-50 > count($table_model->items) && $table_bulk_ok) : ?>
							<option value="checkboxes" class="hide-if-no-js"><?php _e('On selected records','admin_form'); ?></option>
							<?php endif;?>
							<option value="sql"><?php _e('Query results operations','admin_form'); ?></option>
						</select>
					
						<div class="button" onclick="dbp_bulk_actions()"><?php _e('Apply'); ?></div>
					</div>
				</div>
			
				<br class="clear">
			</div>
		<?php endif;
	}


	/**
      * Testo una formula pinacode
      */
	  function test_formula() {
        ADFO_fn::require_init();
		$formula = (isset($_REQUEST['formula'])) ? wp_kses_post( wp_unslash($_REQUEST['formula'])) : '';
     
        $post_id = absint($_REQUEST['dbp_id']);
        $row     = absint($_REQUEST['row']);
        $json_result = ['formula'=>$formula, 'id'=>$post_id, 'row'=>$row, 'error'=>[], 'warning'=>[],'notice'=>[], 'response'=>'', 'typeof'=>'NULL', 'pinacode_data'=>[] ];
        if ($formula != "" && $post_id > 0 && $row > 0) {
            $post        = ADFO_functions_list::get_post_dbp($post_id);
            $table_model = new ADFO_model();
            if (isset($post->post_content['sql'])) {
                $table_model->prepare($post->post_content['sql']);
            } else {
                $table_model = false;
            }
            if ($table_model != false && $table_model->sql_type() == "select") {
                $table_model->list_add_limit($row -1 ,1);
                $table_model->add_primary_ids();
                $table_items = $table_model->get_list();
                //ADFO_fn::add_primary_ids_to_sql($table_model, $table_items);
                // Preparo i dati da editare a seconda di quanti sono i risultati
                if (is_countable($table_items) && count($table_items) > 1) {
                    $header = reset($table_items);
                    $items = ADFO_fn::convert_table_items_to_group($table_items, false);
                    //var_dump ($items);
                    foreach ($items as $item_key=>$item) {
                        foreach ($item as $key=>$value_item) {
                            //echo " SET PINACODE:".ADFO_fn::clean_string($header[$key]['schema']->table).".".ADFO_fn::clean_string($header[$key]['schema']->name)." = ".$value_item."\n ";
							//@TODO da decidere se il secondo parametro deve avere il clean_string (ma allora lo devo mettere ovunque!!!)
                            PinaCode::set_var(ADFO_fn::clean_string($header[$key]['schema']->table).".".$header[$key]['schema']->name, $value_item);
                          //  PinaCode::set_var("data.".ADFO_fn::clean_string($header[$key]['schema']->name), $value_item);
                        }
                    }
                }
                $json_result['pinacode_data'] = PinaCode::get_var('*');
                $json_result['response'] =  PinaCode::execute_shortcode( $formula );
                $json_result['typeof'] = gettype( $json_result['response']);
                $json_result['error'] = PcErrors::get('error', true);
                $json_result['warning'] = PcErrors::get('warning', true);
                $json_result['notice'] = PcErrors::get('notice', true);
            }
        }
        wp_send_json($json_result);
		die();
    }
    /**
      * Rieseguo una formula e salvo il risultato nel db
      */
    function recalculate_formula() {
        global $wpdb;
        ADFO_fn::require_init();
        $formula = (isset($_REQUEST['formula'])) ? wp_kses_post( wp_unslash($_REQUEST['formula'])) : '';
        $el_id    =   ADFO_fn::get_request('el_id');
		$post_id  = 	absint($_REQUEST['dbp_id']);
        $limit_start = isset($_REQUEST['limit_start']) ? absint($_REQUEST['limit_start']) : 0;
        $limit = 3;
        $errors = [];
        $json_result = ['formula'=>$formula, 'el_id'=>$el_id, 'total'=>absint(@$_REQUEST['total']), 'limit_start'=>$limit_start+$limit, 'msgs'=>[],  'success_count'=>absint(@$_REQUEST['success_count']), 'error_count' => ADFO_fn::get_request('error_count', 0, 'absint') ];
        if ($formula != "" && $post_id > 0) {
            $post        = ADFO_functions_list::get_post_dbp($post_id);
            $table_model = new ADFO_model();
            if (isset($post->post_content['sql'])) {
                $table_model->prepare($post->post_content['sql']);
            } else {
                $table_model = false;
            }
            if ($table_model != false && $table_model->sql_type() == "select") {
                $table_model->list_add_limit($limit_start, $limit);
                $table_model->add_primary_ids();
                $table_items = $table_model->get_list();
                $json_result['get_list'] = $table_model->get_current_query();
                if (!isset($_REQUEST['insert_table']) || !isset($_REQUEST['field_name'])) {
                    $json_result['error'] = __('Ops this looks like a bug, parameters are missing.', 'admin_form');
                    wp_send_json($json_result);
                    die();
                }
                // Preparo i dati da editare a seconda di quanti sono i risultati
                if (is_countable($table_items) && count($table_items) > 1) {
                    $header = array_shift($table_items);
                   // $items = ADFO_fn::convert_table_items_to_group($table_items, false);
                    
                    $row = $limit_start;
                    foreach ($table_items as $item_key=>$itemt) {
                        $item = ADFO_fn::convert_table_items_to_group([$header, $itemt], false);
                        $row++;
                        //PinaCode::clean_var();
                        $primary_value = -1;
                        $primary_key = "";
                        $insert_field = "";
                        $insert_table = "";
                        PinaCode::set_var('row', $row);
                        foreach ($item as $vkey=>$v_item) {
                          
                            foreach ($v_item as $key=>$value_item) {
                               // print ($key."=".$value_item."\n");
                                //echo " SET PINACODE:".ADFO_fn::clean_string($header[$key]['schema']->table).".".ADFO_fn::clean_string($header[$key]['schema']->name)." = ".$value_item."\n ";
                              
                                PinaCode::set_var(ADFO_fn::clean_string($header[$key]['schema']->table).".".ADFO_fn::clean_string($header[$key]['schema']->name), $value_item);
                                PinaCode::set_var("data.".ADFO_fn::clean_string($header[$key]['schema']->name), $value_item);

                                $primary_key = ADFO_fn::get_primary_key($header[$key]['schema']->orgtable);
                                if ($_REQUEST['field_name'] == $header[$key]['schema']->orgname) {
                                    $insert_field = $header[$key]['schema']->orgname;
                                }
                            
                                if ($_REQUEST['insert_table'] == $header[$key]['schema']->orgtable) {
                                    $insert_table = $header[$key]['schema']->orgtable;
                                
                                    if ($header[$key]['schema']->orgname == $primary_key) {
                                        $primary_value = $value_item;
                                    }
                                }
                            
                            }
                        }
                        $json_result['ids'][] = $primary_key.':'.$primary_value;
                        //print "insert_field: ".$insert_field."\n";
                        //print "insert_table: ".$insert_table."\n";
                        //print "primary_value: ".$primary_value."\n";
                        if ($insert_field != "" && $insert_table != "" && $primary_value > 0) {
                            $response =  PinaCode::execute_shortcode( $formula );
                            $pina_error = PcErrors::get('error', true);
                            if (count($pina_error) > 0) {
                                $errors[] = sprintf(__("row ID %s: The template engine gave an error: %s", 'admin_form'), $primary_value, array_shift($pina_error));
                                $json_result['error_count']++;
                            } else {
                                $sql = 'UPDATE `'. ADFO_fn::sanitize_key($insert_table) . '` SET `'.$insert_field.'` = "' . esc_sql($response) . '" WHERE `' . ADFO_fn::sanitize_key($primary_key) .'` = "'.absint($primary_value).'" LIMIT 1; ';
                                $json_result['sql'][] =  "row ID ".$row.": ".$sql ;
                                if ($wpdb->query($sql) !== false) {
                                    $json_result['success_count']++;
                                } else {
                                    $errors[] = "row ID ".$primary_value.":".$wpdb->last_error;
                                    $json_result['error_count']++;
                                }
                            }
                        } else  if ($insert_table != "") { 
                            if ($insert_field == "" ) {
                                $errors[] = sprintf(__("For row %s I can't find the field  in which to insert the data.", 'admin_form'), $row);
                            } else if($primary_value == 0) {
                                $errors[] = sprintf(__("For row %s I can't find the primary key  in which to insert the data.", 'admin_form'), $row);
                            }
                        
                            $json_result['error_count']++;
                        }
                        //$json_result['typeof'] = gettype( $json_result['response']);
                        //$json_result['error'] = PcErrors::get('error', true);

                    }
                }
            }
        }
        $json_result['error'] = implode("<br>", $errors);
        wp_send_json($json_result);
		die();
    }

    
}
new Dbp_pro_list_loader();