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
                <?php require_once(ADFO_DIR.'/admin/partials/af-partial-tabs.php'); ?>
            </div>
            <div class="af-content-table js-id-dbp-content" >
            <?php if (ADFO_fn::echo_html_title_box('IMPORT EXPORT', '', $msg, $msg_error)) : ?>
                <div class="af-content-margin">
                    <h3><?php _e ("Export all data" , 'admin_form'); ?></h3>
                    <p><?php _e ("The data is exported in a raw format so that it can be modified and re-imported into the table. All the data in the tables are extracted, not just the displayed ones." , 'admin_form'); ?></p>
                    <div class="dbp-submit" onclick="adfo_download_raw_csv(<?php echo esc_attr(sanitize_text_field($_REQUEST['dbp_id'])); ?>)"><?php _e ("Export all RAW data", 'admin_form'); ?></div>
                    
                    <p><?php _e("The data displayed by the table is imported", 'admin_form'); ?></p>
                    <div class="dbp-submit" onclick="adfo_download_csv(<?php echo esc_attr(sanitize_text_field($_REQUEST['dbp_id'])); ?>)"><?php _e("Export list data", 'admin_form'); ?></div>


                    <h3><?php _e("Import data", 'admin_form'); ?></h3>
                    <p><?php _e("Use the raw data export as a starting point for editing or creating new records.", 'admin_form'); ?><br><?php _e("Imported data must be in UTF-8", 'admin_form'); ?></p>
                    <div class="dbp-content-margin">
                        <h2 class="dbp-h2"><?php _e('File to import', 'admin_form'); ?></h2>
                        <p class="dbp-p"><?php  _e('Upload <b>.CSV</b> file', 'admin_form');   ?></p>
                        <form enctype="multipart/form-data" >
                            <div id="dbpUploadProgress"></div>
                            <input id="dbi_import_file" name="sql_file" type="file" accept=".csv,.sql" />
                            <input id="dbi-file-upload-submit" type="button" class="dbp-submit" value="<?php _e('Go ahead', 'db_press'); ?>" onclick="dbp_start_upload(<?php echo esc_attr(sanitize_text_field($_REQUEST['dbp_id'])); ?>)" />
                        </form>
                        <hr>
                        <div id="container_step2"></div> 
                        <br><br><br>
                    </div>
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

<?php require(ADFO_DIR.'admin/js/admin-form-footer-script.php');
