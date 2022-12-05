<?php
/**
 * Gestisco il filtri e hook della form 
 *
 * @package    DATABASE TABLE
 * @subpackage DATABASE TABLE/INCLUDES
 * @internal
 */
namespace admin_form;

class  Dbp_pro_import_export_list {
	/**
	 * @var Object $saved_queries le ultime query salvate per tipo
	 */

	public function __construct() {
        // aggiunge il tab 
        add_action( 'adfo_partial_list_add_tabs', [$this, 'partial_list_add_tabs']);
        // gestisce il controller per la visualizzazione di una nuova pagina
        add_action( 'adfo_list_admin_controller', [$this, 'controller']);
        // questo filtro dice se deve inibile (return false) la visualizzazione della pagina di default di list-all
        add_filter('adfo_list_admin_controller_show_list_all', [$this, 'is_import_export'], 10, 2 );
        // verifica se l'importazione può essere fatta.
        add_action( 'wp_ajax_adfo_check_import_data', [$this, 'ajax_check_import_data']);
        add_action( 'wp_ajax_adfo_list_import_data', [$this, 'ajax_list_import_data']);
    }

    public function partial_list_add_tabs() {
        if (isset($_REQUEST['dbp_id']) && absint($_REQUEST['dbp_id']) > 0) {
            $base_link = admin_url("admin.php?page=admin_form"); 
            $link = add_query_arg(['section' => 'import-export','dbp_id' => absint($_REQUEST['dbp_id'])], $base_link);
            $selected_class = ($_REQUEST['section'] == 'import-export') ? 'dbp-tab-active' : '';
            ?>
            <a href="<?php echo  $link; ?>" class="dbp-tab <?php echo $selected_class; ?>">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Import/Export', 'admin_form'); ?>
            </a>
            <?php
        }
    }

    public function controller($section) {
        if ($section != 'import-export') return;
     
        wp_enqueue_script( 'adfo-js-import-export-js',  dirname(plugin_dir_url( __FILE__ ))   . '/admin/js/adfo-js-import-export.js',[],ADFO_PRO_VERSION);
        wp_localize_script( 'adfo-js-import-export-js', 'dbi_vars', array(
            'upload_file_nonce' => wp_create_nonce( 'dbi-file-upload' ),
            )
        );
        $msg = '';
        $msg_error = '';
        
        require (__DIR__."/../admin/af-page-import-export.php");
    }
    /**
     * Verifica se la sezione è import/export lo si usa in un filtro
     */
    public function is_import_export($bool, $section) {
        return ($section == 'import-export') ? false : $bool;
    }
    /**
     * Prova a verificafe l'importazione del csv.
     */
    public function ajax_check_import_data() {
        ADFO_fn::require_init();
        $json_send = ['error'=>''];
        if (!isset($_REQUEST['dbp_id']) || !isset($_REQUEST['filename']) || !isset($_REQUEST['orgname'])) {
            $json_send['error'] = __('c\'è stato un problema inatteso', 'admin_form');
			wp_send_json($json_send);
			die();
        }
        $limit_start = isset($_REQUEST['limit_start']) ? absint($_REQUEST['limit_start']) : 0;
        $limit = 10;
      
        //var_dump ($tables_strucutes);
        $temporaly_files = new ADFO_temporaly_files();
        $csv_items = $temporaly_files->read_csv(sanitize_text_field($_REQUEST['filename']), ';', true, $limit, $limit_start);
        
       // var_dump ($csv_items);
        if (!is_array($csv_items)) {
            $json_send['error'] = __('Il file non era un csv valido', 'admin_form');
			wp_send_json($json_send);
			die();
        }
        $form = new ADFO_class_form(absint($_REQUEST['dbp_id']));
        $result = [];
        $res_data = [[]];
        $result[] = array_shift($csv_items);
        $import_table = true;
        foreach ($csv_items as $item) {
            $temp_result = $form->check_data_to_save($item);
            if ($temp_result['___result___'] == false ) $import_table = false;
            if ($temp_result['___result___'] == false || $temporaly_files->csv_total_row < 300 ) {
                $result[] = $temp_result;
                $temp_data = [];
                foreach ($item as $t) {
                    if (is_array($t) || is_object($t)) {
                        $t = json_encode($t);
                    }
                    $t = htmlentities($t);
                    if (strlen($t) > 100) {
                        $t = substr($t,0,95)." ...";
                    }
                    $temp_data[] = $t;
                }
                $res_data[] = $item;
                if (!ADFO_fn::get_max_execution_time()) {
                    break;
                }
            }
        }
		wp_send_json(['import_table'=> $import_table, 'table_array' => $result, 'table_data' => $res_data, 'total_row' => $temporaly_files->csv_total_row, 'checked' => count($result), 'limit_start'=>$limit_start, 'limit'=>$limit]);
        die;
    }

    function ajax_list_import_data() {
        ADFO_fn::require_init();
        $limit_start = isset($_REQUEST['limit_start']) ? absint($_REQUEST['limit_start']) : 0;
        $limit = 10;
        $temporaly_files = new ADFO_temporaly_files();
        $csv_items = $temporaly_files->read_csv(sanitize_text_field($_REQUEST['filename']), ';', true, $limit, $limit_start);
       // var_dump ($csv_items);
        if (!is_array($csv_items)) {
            $json_send['error'] = __('Il file non era un csv valido', 'admin_form');
			wp_send_json($json_send);
			die();
        }
        $form = new ADFO_class_form(absint($_REQUEST['dbp_id']));
        $result = [];
        $res_data = [[]];
        $total = count ($csv_items);
        array_shift($csv_items);
        foreach ($csv_items as $item) {
            $res_save_data = $form->save_data([(object)$item]);
            $report = array_merge($item, $res_save_data);
            $result[] = $res_save_data;
        }
        wp_send_json(['result' => $result, 'limit_start'=>$limit_start, 'limit'=>$limit, 'total_row' => $temporaly_files->csv_total_row, 'error'=>'']);
        die;
    }

}
new Dbp_pro_import_export_list();