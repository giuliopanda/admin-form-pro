<?php
namespace admin_form;
class Dbp_fn_pro
{

    /**
     * in dbp-list-admin non posso usare i request sql.
     */
    static function dbp_delete_from_sql_from_request() {
        $sql_query_executed = wp_kses_post( wp_unslash($_REQUEST["sql_query_executed"]));
		$remove_table_query = sanitize_text_field($_REQUEST["remove_table_query"]);
        return dbp_fn_pro::dbp_delete_from_sql($sql_query_executed, $remove_table_query);
    }
    /**
	 * bulk delete on sql: 
	 * Elimino i dati rispetto ad una tabella scelta dalla query 
	 * (solo una perché per più potrebbe non funzionare)
     * @param String $sql La query da cui partire per eliminare i dati
     * @param String $table_choose la tabella su cui eliminare i dati 
     * (potrebbe esserci più di una tabella interessata nella query, si eliminano i dati da una sola tabella)
     * @return String error 
	 */

	static function dbp_delete_from_sql($sql, $table_choose) {
		global $wpdb;
		ADFO_fn::require_init();
		$table_model = new ADFO_model();
        $error = __("There was an unexpected problem", 'admin_form'); 
		$table_model->prepare($sql);
		$table_items = $table_model->get_list();
        if ($table_model->last_error ) {
            return $table_model->last_error."<br >".$table_model->get_current_query();
        }
        if (count($table_items) < 2) {
            return $error;
        }
		$header = array_shift($table_items);
		// trovo le tabelle interessate
		$temp_groups = [];
		foreach ($header as $key=>$th) {
			if (isset($th['schema']->table) && isset($th['schema']->orgtable) && $th['schema']->table != "" && $th['schema']->orgtable != "") {
				if (!isset($temp_groups[$th['schema']->table])) {
					//$temp_groups[$th['schema']->table] =['table'=>$th['schema']->orgtable, 'pri' => ADFO_fn::get_primary_key($th['schema']->orgtable)];
					$id = ADFO_fn::get_primary_key($th['schema']->orgtable);
					$new_id_schema = ADFO_fn::find_primary_key_from_header($header, $th['schema']->table, $id);
					if ($id != "" && $new_id_schema != false) {
						$table_model->list_change_select('`'.$new_id_schema->table.'`.`'.$new_id_schema->name.'`');
                        $table_model->remove_limit();
                        $new_query = 	$table_model->get_current_query();
						if ($new_query != "" && $new_id_schema->table == $table_choose) {
                            $error = '';
                            $option = ADFO_fn::get_dbp_option_table($th['schema']->orgtable);
                            if ($option['status'] != "CLOSE") {
                                $sql_to_del = 'DELETE FROM `'.$th['schema']->orgtable.'` WHERE `'.esc_sql($id).'` IN ('.$new_query.')';
                                if (!$wpdb->query($sql_to_del)) {
                                    $error = __(sprintf('Query "%s" returned an error', $sql_to_del), 'admin_form');
                                } 
                            } else {
                                $error = __(sprintf('Records in the "%s" table cannot be removed because they are in a closed state. If you want to be able to remove the data, change the status from the table structure to "published"', $th['schema']->orgtable), 'admin_form');
                            }
							break;
						}
					}
				}
			}
		}
        return $error;
	}

}