<?php
/**
 * Gestisco il filtri e hook della form 
 *
 * @package    DATABASE TABLE
 * @subpackage DATABASE TABLE/INCLUDES
 * @internal
 */
namespace admin_form;

class  Dbp_pro_loader_list_sql_edit {
	/**
	 * @var Object $saved_queries le ultime query salvate per tipo
	 */
	public static $saved_queries;

	public function __construct() {
		self::$saved_queries = (object)[];
		
		add_action( 'dbp_list_sql_edit_html_after_table', [$this, 'action_list_sql_edit_html_sql'],10, 2 );
		add_action( 'dbp_list_sql_edit_html_bottom', [$this, 'action_list_sql_edit_html_delete'],10, 2 );
        add_filter('dbp_list_sql_save', [$this, 'filter_list_sql_edit_html'],10, 1) ;
    }

    /**
	 * Aggiunge le parti di html della pagina dbp_content_list_sql_edit.php
	 */
	public function action_list_sql_edit_html_sql($table_model, $show_query) {
		?>
         <div>
            <h3 class="dbp-h3 dbp-margin-top"><?php _e('Query', 'admin_form'); ?></h3>
            <p class="dbp-alert-gray" style="margin-top:-1rem">
                <?php _e('How the data is extracted','admin_form'); 
                ADFO_fn::echo_html_icon_help('dbp_list-list-sql-edit','admin_query');
                ?>
            </p>
            <?php ADFO_html_sql::render_sql_from($table_model, $show_query); ?>
        </div>
        <?php
	}

	/**
	 * Aggiungo le opzioni di rimozione 
	 */
	public function action_list_sql_edit_html_delete($table_model, $post_allow_delete) {
		?>
		<br>
		<h3 class="dbp-h3"><?php _e('Delete options', 'admin_form'); ?></h3>
		<p class="dbp-alert-gray" style="margin-top:-1rem"><?php _e('When one or more records are deleted, you choose which tables in the query you want to be deleted.', 'admin_form');
		ADFO_fn::echo_html_icon_help('dbp_list-list-sql-edit','delete_options');?></p>
		<?php
		
		/** @var array $delete_tables [[table, as, where, la parte di stringa elaborata], ...] */
		$delete_tables = $table_model->get_partial_query_from(true);
		if (is_countable($delete_tables) && count($delete_tables) > 0) {
			foreach ($delete_tables as $k=>$dt) {
				if (!array_key_exists($dt[1], $post_allow_delete)) {
					$post_allow_delete[$dt[1]] = 1;
				}
				?>
				<?php if (! ($k%2) ) : ?><div class="dbp-structure-grid"><?php endif; ?>
					<div class="dbp-form-row-column">
						<label>
							<span class="dbp-form-label "><?php echo $dt[0].' AS '.$dt[1].' '; ?></span>
							<?php echo ADFO_fn::html_select(['1'=>'Yes', 0=>'No'], true, 'name="remove_tables_alias['.$dt[1].']"', $post_allow_delete[$dt[1]]); ?>
						</label>
					</div>
				<?php if ($k%2) : ?></div><?php endif; 
			}
			if (! ($k%2) ) : ?></div><?php endif;
		}
	}


    /**
	 * Gestisce il salvataggio alternativo in class-dbp-list-admin.php 
     */
	public function filter_list_sql_edit_html($custom_save) {
        global $wp_roles;
        $id = $custom_save['dbp_id'];
		$return = $custom_save['return'];
        $custom_save['saved'] = true;
		$show_query = false;
		$error_query = "";
		if ($id > 0) {
			$post = ADFO_functions_list::get_post_dbp($id);
			if (isset($_REQUEST['custom_query']) && $_REQUEST['custom_query'] !== '') {
				// aggiungo tutti i primary id e li salvo a parte 
				$table_model = new ADFO_model();
				$table_model->prepare(wp_unslash($_REQUEST['custom_query']));
				if ($table_model->sql_type() != "select") {
					$error_query = sprintf(__('Only a single select query is allowed in the lists %s', 'admin_form'), $table_model->get_current_query());
					$show_query = true;
				} else {
					$table_model->add_primary_ids();
					// TODO se aggiungo qualche valore dovrei metterlo hidden in list view formatting!
					$table_model->list_add_limit(0, 1);
					$items = $table_model->get_list();
					if ($table_model->last_error == "") {
						$post->post_content['sql'] = html_entity_decode($table_model->get_current_query());
					} else {
						$error_query = sprintf(__("I didn't save the query because it was wrong!.<br><h3>Error:</h3>%s<h3>Query:</h3>%s",'admin_form'), $table_model->last_error, nl2br(wp_kses_post( wp_unslash( $_REQUEST['custom_query'] )) ));
					}
				}
			} else {
				$error_query = __('The query is required', 'admin_form');
			}
			// TODO se metto il limit nella query vorrei che passasse qui!
			$post->post_content['sql_limit'] = absint($_REQUEST['sql_limit']);
			if ($_REQUEST['sql_order']['field'] != "") {
				$post->post_content['sql_order'] = ['field'=>sanitize_text_field($_REQUEST['sql_order']['field']),'sort'=>sanitize_text_field($_REQUEST['sql_order']['sort'])] ;
			} else {
				if (isset($post->post_content['sql_order'])) {
					unset($post->post_content['sql_order']);
				}
			}

			// DEVO RICALCOLARE form_table che mi serve per capire se ci sono i bottoni dell'edit e del delete
			// la configurazione delle tabelle
			if (!isset($post->post_content['form_table']) || !is_array($post->post_content['form_table'])) {
				$post->post_content['form_table'] = [];
			}

			$post->post_content['show_desc'] = isset($_REQUEST['show_desc']) ? absint($_REQUEST['show_desc']) : 0;
		
			$fields_from = $table_model->get_partial_query_from(true);
		
			$style_list = ['WHITE','BLUE','GREEN','RED','YELLOW','PURPLE','BROWN'];
			foreach ($fields_from as $single_from) {
				if (!isset($single_from[1]) || array_key_exists($single_from[1], $post->post_content['form_table'])) {
					continue;
				} 
				$post->post_content['form_table'][$single_from[1]] = [
					'allow_create' => 'SHOW',	
					'show_title' => 'SHOW',
					'frame_style' => $style_list[rand(0,6)],
					'title' => '',
					'description' => '', 	
					'module_type' =>'EDIT'
				];
			}
			
			$post->post_content['sql_filter'] = [];
			
			if (isset($_REQUEST['sql_filter_field']) && is_countable($_REQUEST['sql_filter_field'])) {
				foreach ($_REQUEST['sql_filter_field'] as $key=>$field) {
					$field = sanitize_text_field($field);
					$key = sanitize_text_field($key);

					if ($field != "" && $_REQUEST['sql_filter_val'][$key] != "") {
						$post->post_content['sql_filter'][] = [
							'column' => $field, 
							'op' => sanitize_text_field($_REQUEST['sql_filter_op'][$key]), 'value' => wp_kses_post( wp_unslash($_REQUEST['sql_filter_val.'.$key])),
							'required' => sanitize_text_field($_REQUEST['sql_filter_required.'.$key])];
					} else {
						if ($_REQUEST['sql_filter_val'][$key] != "") {
							$return[] = __('a filter could not be saved because a field was not chosen to associate it with', 'admin_form');
						} else if ($field != "") {
							$return[] = sprintf(__("I have not saved the filter associated with the <b>%s</b> field because it has no parameters to pass. If you want to filter the list by shortcode attributes use %s.", 'admin_form'), $field, '[%params.attr_name]');
						}
					}
				}
			} 

			$post->post_content['delete_params'] = ['remove_tables_alias'=>[]];
			if (!isset($_REQUEST['remove_tables_alias']) || !is_array($_REQUEST['remove_tables_alias'])) {
				$remove_tables_alias_request = [];
			} else {
				$remove_tables_alias_request = ADFO_fn::sanitize_text_recursive($_REQUEST['remove_tables_alias']);	
			}
			foreach ($remove_tables_alias_request as $remove_tables_alias=>$allow) {
				$post->post_content['delete_params']['remove_tables_alias'][sanitize_text_field($remove_tables_alias)] = absint($allow);
			}

			// Verifico che nella query non vengano cambiati gli alias delle tabelle
			if ($error_query == "") {
				$from_query = $table_model->get_partial_query_from(true);
				if (isset($post->post_content['sql_from'])) {
					foreach ($post->post_content['sql_from'] as $table_alias => $table) {
						$find = false;
						foreach ($from_query as $f) {
							// Ho invertito $f[1] con $f[0] così funziona, da verificare.
							if ($f[1] == $table_alias && $f[0] == $table) {
								$find = true;
								break;
							}
						}
						if (!$find) {
							$return[] = sprintf(__('The settings have been saved, but you have changed the name of a query table (%s as %s). <br>This can cause an unexpected operation in the management of the list. <br>In these cases it is preferable to create a new form.', 'admin_form'), $table, $table_alias);
						}
					}
				} 
				$from = [];
				foreach ($from_query as $f) {
					$from[$f[1]] = $f[0]; 
				}
				$post->post_content['sql_from'] = $from;
			
				// Salvo le chiavi primarie e lo schema
				$post->post_content['primaries'] = $table_model->get_pirmaries();	
				$post->post_content['schema'] = reset($table_model->items);
			} else {
				if (isset($post->post_content['primaries'])) unset($post->post_content['primaries']);
				if (isset($post->post_content['schema'])) unset($post->post_content['schema']);
				if (isset($post->post_content['sql_from'])) unset($post->post_content['sql_from']);
			}
		
			$show_query = false;
			/**
			 * @var dbpDs_list_setting[] $setting_custom_list
			 */
			$setting_custom_list =  ADFO_functions_list::get_list_structure_config($items, $post->post_content['list_setting']);
			foreach ($setting_custom_list as $key_list=>$list) {
				$post->post_content['list_setting'][$key_list] = $list->get_for_saving_in_the_db();
			}

			$post_title = (!isset($_REQUEST['post_title'])) ? '' : sanitize_text_field($_REQUEST['post_title']);
			
			if ($post_title != "") {
				wp_update_post(array(
					'ID'           => $id,
					'post_title' 	=> $post_title,
					'post_excerpt' 	=> wp_kses_post( wp_unslash($_REQUEST['post_excerpt']))
				));
				ADFO_fn::save_list_config($id, $post->post_content);
			} else {
				$return[] = __('The title is required', 'admin_form');
			}
			

			// permessi e menu admin
			$old = get_post_meta($id,'_dbp_admin_show', true);
			$title =  ($post_title != "") ? $post_title : sanitize_text_field($_REQUEST['menu_title']);

			$dbp_admin_show  = [
				'page_title'    => $title, 
				'menu_title'    => sanitize_text_field($_REQUEST['menu_title']),
				'menu_icon'     => sanitize_text_field(trim($_REQUEST['menu_icon'])),
				'menu_position' => absint($_REQUEST['menu_position']),
				'capability'    => 'dbp_manage_'.$id,
				'slug'			=> 'dbp_'.$id,
				'show'			=> (isset($_REQUEST['show_admin_menu']) && $_REQUEST['show_admin_menu'] == 1) ? 1 : 0,
				'status'		=> 'publish'
			];
		
			if (isset($_REQUEST['show_admin_menu']) && $_REQUEST['show_admin_menu']) {
				if ($old != false) {
					update_post_meta($id, '_dbp_admin_show', $dbp_admin_show);
				} else {
					add_post_meta($id,'_dbp_admin_show', $dbp_admin_show, false);
				}
				foreach ($wp_roles->get_names() as $role_key => $_role_label) { 
					$role = get_role( $role_key );
					
					if (isset( $_REQUEST['add_role_cap']) && in_array($role_key, $_REQUEST['add_role_cap'])) {
						$role->add_cap( 'dbp_manage_'.$id, true );
					} else {
						$role->remove_cap('dbp_manage_'.$id);
					}
				}
			} else {
				delete_post_meta($id, '_dbp_admin_show');
			}
			
			
		} else {
			$return[] = __('You have not selected any list', 'admin_form');
		}
		if ($error_query != "") {
			$return[] = $error_query;
			$show_query = true;
		}
		$custom_save['return'] = (count($return) == 0) ? true : implode("<br>", $return);
		$custom_save['show_query'] = $show_query;
		return $custom_save;
    }

}
new Dbp_pro_loader_list_sql_edit();