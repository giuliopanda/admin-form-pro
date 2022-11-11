<?php
/**
 * Mostra i risultati dell'esecuzione multipla di query scritte direttamente importate (dalla pagina import).
 * Se non ha fatto in tempo ad eseguire tutte le query continua a farlo tramite ajax (class-database-press-loader.php > dbp_multiqueries_ajax()
 * 
 * Per il rendering delle tabelle chiama: dirname(__FILE__)."/dbp-content-table-without-filter.php" 
 * 
 * @var Boolean $ajax_continue 
 * @var Array $info
 * @var $queries
 * 
 * @package  DbPress
 */
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
?>
<div class="dbp-content-header">
    <?php require dirname(__FILE__) . '/dbp-partial-tabs.php'; ?> 
</div>
<div class="dbp-content-table js-id-dbp-content">
    <h2><?php _e('Multi Queries executed','db_press'); ?></h2>
    <?php if ($ajax_continue != false) {
        $link = admin_url('admin-post.php?action=dbp_download_multiquery_report&fnid=' . $ajax_continue);  
        printf(__('%s out of %s queries were performed', 'db_press'),  '<span id="dbp_count_queries_executed">'.$info['executed_queries'].'</span>', $info['total_queries']);
        ?>
        <div id="multiqueries_end_ok" class="dbp-alert-info" style="display:none">
            <?php _e('all done! everything went fine', 'db_press'); ?> <a href="<?php echo esc_attr($link); ?>"> <?php _e('Download result', 'db_press'); ?></a>
        </div>           
        <div id="multiqueries_end_no_ok" class="dbp-alert-sql-error" style="display:none">
            <?php _e('Some queries gave an error', 'db_press'); ?> <a href="<?php echo esc_attr($link); ?>"> <?php _e('Download result', 'db_press'); ?></a>
        </div>  
        <div id="multiqueries_cancel" class="dbp-alert-sql-error" style="display:none">
            <?php _e('Query execution was interrupted by the user.', 'db_press'); ?> <a href="<?php echo esc_attr($link); ?>"> <?php _e('Download the report for more information', 'db_press'); ?></a>
        </div>  
        <div id="multiqueries_continue" class="dbp-alert-sql-error" style="display:<?php echo ($info['last_error'] == "") ? "none": "block"; ?>">
            <h2>Query error:</h2>
            <p id="multiqueries_last_error_msg"><?php echo $info['last_error']; ?></p>
            <?php _e('A query have failed. Do you want to continue?', 'db_press'); ?> 
            <div class="button" onclick="dbp_multiqueries_ajax('<?php echo esc_attr($ajax_continue); ?>')"> <?php _e('Continue', 'db_press'); ?></div> 
            
            <div class="button" onclick="dbp_multiqueries_cancel('<?php echo esc_attr($ajax_continue); ?>')"> <?php _e('Cancel', 'db_press'); ?></div>

            <label class="dbp-label-ignore-erros"><input type="checkbox" id="dbp_ignore_errors" value="1"><?php _e('Ignore errors', 'db_press'); ?></label>
        </div>  
        <?php 
        if ($info['last_error'] == "") { ?>
            <script>
                jQuery(document).ready(function ($) {
                    dbp_multiqueries_ajax('<?php echo esc_attr($ajax_continue); ?>');
                });
            </script>
        <?php 
        }
    } else {
        ?><p><?php echo (sprintf(__('%s Queries executed', 'db_press'), count($queries))); ?></p><?php
        require dirname(__FILE__) . "/dbp-content-table-without-filter.php";
    } 
    ?>
</div>