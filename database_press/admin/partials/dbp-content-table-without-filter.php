<?php
/**
 * Stampa i risultati di una o più query contenute dentro $items. le tabelle non hanno jabascript. 
 * è chiamata da 
 * 
 * @var Class $dbp function
 * @var Array $items è l'elenco delle tabelle da stampare [{model:table-model, content}, ...]
 * @var Array $list_of_tables
 * Tutti questi parametri dovrei portarli dentro table_model ??
 * @var database_press_model_base $table_model  
 * 
 * @todo il bottone export non funziona
 */
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;

foreach ($items as $item) {
    echo '<div class="dbp-result-query dbp-css-mb-1 js-dbp-mysql-query-text">'.$item->model->get_default_query().'</div>';
    ?>
    <div class="dbp-multiquery-action">
        <form method="post" action="<?php echo add_query_arg('page', 'database_press',  admin_url("admin.php")); ?>" class="dbp-form-single-query">
            <input type="hidden" name="section"  value="table-browse">
            <input type="hidden" name="action_query"  value="custom_query">
            <input type="hidden" name="custom_query"  value="<?php echo esc_attr($item->model->get_current_query()); ?>">
            <input type="submit" value="Run again" class="button button-primary">
        </form>
        <span class="dbp-multiquery-single-query-info">
            <?php 
            if ($item->model->sql_type() == "select") {
                echo $item->model->total_items." total, ";
            } 
            echo " Query took ".$item->model->time_of_query." seconds.";
            ?>
        </span>
    </div>
    <?php
    echo $item->content; 

}
