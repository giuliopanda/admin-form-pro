<?php 
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;

function dbp_partial_tabs() {
    $current_page = 'browse';
    $page = dbp_fn::req('page', '');
    $section = dbp_fn::req('section', 'home');
    $base_link = admin_url("admin.php?page=".$page); 

    
    $table  = dbp_fn::req('table', '');
    $var_name = 'table';
    if ($table != "") {
        $array_tabs = ['table-browse' => 'Browse', 'table-structure' => 'Structure', 'table-sql' => 'SQL', 'table-import' => 'IMPORT'];
        $array_icons = ['table-browse' => '<span class="dashicons dashicons-visibility"></span>', 'table-structure' => '<span class="dashicons dashicons-editor-table"></span>', 'table-sql' => '<span class="dashicons dashicons-edit-page"></span>', 'table-import' => '<span class="dashicons dashicons-database-import"></span>'];
    } else {
    
        $array_tabs = ['information-schema' => 'Show tables', 'table-sql' => 'SQL', 'table-structure|structure-edit' => 'New Table', 'table-import' => 'IMPORT'];
        $array_icons = ['information-schema' => '<span class="dashicons dashicons-admin-site-alt3"></span>', 'table-structure' => '<span class="dashicons dashicons-editor-table"></span>', 'table-sql' => '<span class="dashicons dashicons-edit-page"></span>', 'table-import' => '<span class="dashicons dashicons-database-import"></span>'];
    }
    
    ?>
    <div class="dbp-tabs-container">
        <?php foreach ($array_tabs as $key=>$value) : ?>
            <?php
            $action  = "";
            if (strpos($key, "|") != "") {
                $temp = explode("|", $key);
                $key = array_shift($temp);
                $action =  array_shift($temp);
            }
            if ( $$var_name != "" ) {
                $link = add_query_arg(['section' => $key, $var_name => $$var_name ], $base_link);
            } else {
                if ($key == "table-browse") continue;
                $link = add_query_arg(['section'=>$key ], $base_link);
            }
            if ($action != "") {
                $link = add_query_arg(['action' => $action], $link);
            } 
            if ($section == $key) : ?>
                <a href="<?php echo $link; ?>" class="dbp-tab dbp-tab-active">
                    <?php echo $array_icons[$key]; ?>
                    <?php _e($value, 'database-press'); ?>
                </a>
            <?php else :?>
                <a href="<?php echo $link; ?>" class="dbp-tab">
                    <?php echo $array_icons[$key]; ?>
                    <?php _e($value, 'database-press'); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="dbp-tab" id="dbp_collapse_btn" style="margin-left: auto; cursor:pointer" onclick="dbp_click_toggle_sidebar()">
        <span class="dashicons dashicons-arrow-right" style="background: #1d2327; border-radius: 50%; color: #FFF;" ></span> Collapse sidebar
        </div>
       
    </div>
<?php 
}

dbp_partial_tabs();