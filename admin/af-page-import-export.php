<?php
/**
 * Il template della pagina amministrativa
 * @var String $render_content Il file dentro partial da caricare 
 */
namespace admin_form;
if (!defined('WPINC')) die;
?>
<div class="wrap">
    <div id="dbp_container" class="dbp-grid-container" style="display:none; position:fixed; ">
        <div class="dbp-column-content">

        <div class="af-content-header">
            <?php require(ADFO_DIR.'/admin/partials/af-partial-tabs.php'); ?>
        </div>
        <div class="af-content-table js-id-dbp-content" >
        <?php if (ADFO_fn::echo_html_title_box('IMPORT EXPORT', '', $msg, $msg_error)) : ?>
            <div class="af-content-margin">
                <?php _e ("Export", 'admin_form'); ?>
                l'esportazione in teoria ce l'ho già scritta, devo solo ricollegarlo.<br>
                action: 
af_download_csv
section: 
table-browse
dbp_id: 
53
                per l'importazione devo usare save_data
                <form id="list_form" method="POST" action="<?php echo admin_url("admin.php?page=admin_form&section=list-form&dbp_id=".$id); ?>">
                    <input type="hidden" name="action" value="list-form-save" />
                    <input type="hidden" name="table" value="<?php echo (isset($import_table)) ? $import_table : ''; ?>" />
                    <input type="hidden" name="dbp_id" value="<?php echo esc_html($id); ?>" id="dbp_id_list" />
                </form>
            </div>


        <?php endif; ?>
        </div>

        </div>
        <div id="dbp_sidebar_popup" class="dbp-sidebar-popup">
            <div id="dbp_dbp_title" class="dbp-dbp-title">
                <div id="dbp_dbp_close" class="dbp-dbp-close" onclick="dbp_close_sidebar_popup()">&times;</div>
            </div>
            <div id="dbp_dbp_loader" ><div class="dbp-sidebar-loading"><div  class="dbp-spin-loader"></div></div></div>
            <div id="dbp_dbp_content" class="dbp-dbp-content"></div>
        </div>
    </div>
</div>

<?php require(ADFO_DIR.'/admin/js/admin-form-footer-script.php');
