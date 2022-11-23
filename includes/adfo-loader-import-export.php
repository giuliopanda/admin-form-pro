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
    //    add_action( 'adfo_partial_list_add_tabs', [$this, 'partial_list_add_tabs']);
        // gestisce il controller per la visualizzazione di una nuova pagina
    //    add_action( 'adfo_list_admin_controller', [$this, 'controller']);
        // questo filtro dice se deve inibile (return false) la visualizzazione della pagina di default di list-all
     //   add_filter('adfo_list_admin_controller_show_list_all', [$this, 'is_import_export'], 10, 2 );
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
        wp_enqueue_script( 'adfo-js-import-export-js',  __DIR__  . '/js/adfo-js-import-export.js',[],ADFO_PRO_VERSION);
        $msg = '';
        $msg_error = '';
        
        require (__DIR__."/../admin/af-page-import-export.php");
    }
    
    public function is_import_export($bool, $section) {
        return ($section == 'import-export') ? false : $bool;
    }

}
new Dbp_pro_import_export_list();