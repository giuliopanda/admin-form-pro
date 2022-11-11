<?php
/**
 * Caricato da includes/dbp-html-table.php 
 * Disegno il dropdown con i filtri di ricerca e tutte le opzioni della colonna
 */
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
if ($sort !== false || ($original_field_name != "" && $filter !== false)) : ?>
<div class="dbp-dropdown-container-scroll">
<?php endif; 

if ($sort !== false) :
/**
 * Non è l'alias (alias_column), ma il nome della colonna
 */
?>
    <div class="dbp-table-sort <?php echo $sort_desc_class ; ?>" data-dbp_sort_key="<?php echo esc_attr($original_field_name); ?>" data-dbp_sort_order="DESC"><?php _e('Sort Desending', 'db_press'); ?></div>
    <div class="dbp-table-sort <?php echo $sort_asc_class ; ?>" data-dbp_sort_key="<?php echo esc_attr($original_field_name); ?>" data-dbp_sort_order="ASC"><?php _e('Sort Ascending', 'db_press'); ?></div>
    <div class="dbp-table-sort <?php echo $sort_remove_class ; ?>" ><?php _e('Remove sort', 'db_press'); ?></div>
<?php endif; ?>
<?php /* ricerca */ ?>
<?php if ($original_field_name != "" && $filter !== false) : ?>
    <?php // Il campo in cui salvo filtro si sta per fare ?>
    <input type="hidden"  name="filter[search][<?php echo esc_attr($name_column); ?>][op]" id="filter_<?php echo esc_attr($name_column); ?>_op"  class="js-table-filter-select-op" value="<?php echo esc_attr($def_op); ?>">
    <?php // Rimuove i filtri ?>
    <div class="dbp-dropdown-hr"></div>
    <?php if ($def_input_value != "" || @$def_input_value_2 != "" || $default_value != "") : ?>
        <div class="js-remove-filter dbp-dropdown-line-click" data-rif="<?php echo esc_attr($name_column); ?>"><?php _e('Remove Filter', 'db_press'); ?></div>
    <?php else: ?>
        <div class="dbp-dropdown-line-disable" data-rif="<?php echo esc_attr($name_column); ?>"><?php _e('Remove Filter', 'db_press'); ?></div>
    <?php endif; ?>
    <div class="dbp-dropdown-hr"></div>
    <div class="dbp-dropdown-line-flex">
      
            <span class="dbp-filter-label">
                <input type="radio" name="filter[search][<?php echo esc_attr($name_column); ?>][r]" value="1" class="js-filter-search-radio" id="radio_<?php echo esc_attr($name_column); ?>_1" data-rif="<?php echo esc_attr($name_column); ?>"<?php echo (!in_array($def_op, ['IN','NOT IN'])) ? ' checked="checked"' : '' ; ?>>  
            </span>
            <span id="js_tf_select_label_<?php echo $name_column; ?>" >Filter operators</span>
            <?php
            dbp_fn::html_select($html_select_array, true, 'class="js-table-filter-select-op-partial"  id="js_tf_select_op_'.$name_column.'_1" data-rif="'.$name_column.'"', $def_op);
            ?>

    </div>
    <?php // la textarea che tiene i valori della ricerca; ?>
    <textarea name="filter[search][<?php echo esc_attr($name_column); ?>][value]" id="dbp_dropdown_search_value_<?php echo $name_column; ?>" style="display:none"><?php echo esc_textarea(wp_unslash($default_value)); ?></textarea>
    <input type="hidden" id="filter_search_original_column<?php echo $name_column; ?>" name="filter[search][<?php echo esc_attr($name_column); ?>][column]" value="<?php echo esc_attr($original_field_name); ?>">
    <input type="hidden" id="filter_search_orgtable_<?php echo esc_attr($name_column); ?>" name="filter[search][<?php echo esc_attr($name_column); ?>][table]" value="<?php echo esc_attr($original_table); ?>">
    <input type="hidden" id="filter_search_filter_<?php echo esc_attr($name_column); ?>" value="<?php echo esc_attr(($default_value != "")); ?>">
    <input type="hidden" id="filter_search_type_<?php echo esc_attr($name_column); ?>" value="<?php echo esc_attr($symple_type); ?>">
    <?php // L'input che accetta i valori per le ricerche = > < ecc...; ?>
    <div class="dbp-dropdown-line-flex" id="dbp_input_value_box_<?php echo esc_attr($name_column); ?>">
        <span class="dbp-filter-label"><?php _e('Value', 'db_press'); ?></span>
        <?php if ($symple_type == "DATE") : ?>
            <input class="dbp-table-filter js-table-filter-input-value" id="dbp_input_value_<?php echo $name_column; ?>" type="date" data-rif="<?php echo esc_attr($name_column); ?>"  value="<?php echo esc_attr(str_replace(" ", "T", $def_input_value)); ?>">
        <?php else : ?>
            <input class="dbp-table-filter js-table-filter-input-value" data-rif="<?php echo esc_attr($name_column); ?>"  id="dbp_input_value_<?php echo esc_attr($name_column); ?>" type="text"  value="<?php echo esc_attr($def_input_value); ?>" >
        <?php endif; ?>
        
    </div>
    <?php // Il secondo input per il beetwen che accetta i valori per le ricerche = > < ecc...; ?>
    <div class="dbp-dropdown-line-flex" id="dbp_input_value2_box_<?php echo esc_attr($name_column); ?>">
        <span class="dbp-filter-label"><?php _e('Value 2', 'db_press'); ?></span>
        <?php if ($symple_type == "DATE") : ?>
            <input class="dbp-table-filter js-table-filter-input-value2" id="dbp_input_value2_<?php echo esc_attr($name_column); ?>" type="date" data-rif="<?php echo esc_attr($name_column); ?>"  value="<?php echo esc_attr(str_replace(" ", "T", $def_input_value_2)); ?>">
        <?php else : ?>
            <input class="dbp-table-filter js-table-filter-input-value2" data-rif="<?php echo esc_attr($name_column); ?>"  id="dbp_input_value2_<?php echo esc_attr($name_column); ?>" type="text"  value="<?php echo esc_attr($def_input_value_2); ?>" >
        <?php endif; ?>
        
    </div>
    <?php // I Checkboxes; ?>
    <div class="dbp-dropdown-hr"></div>
    <label  class="dbp-dropdown-line-flex">
        <span class="dbp-filter-label">
            <input type="radio" name="filter[search][<?php echo esc_attr($name_column); ?>][r]" value="2" class="js-filter-search-radio" id="radio_<?php echo esc_attr($name_column); ?>_2" data-rif="<?php echo esc_attr($name_column); ?>"<?php echo(in_array($def_op,['IN','NOT IN'])) ? ' checked="checked"' : '' ; ?>>  
            <?php _e('Search', 'db_press'); ?>
        </span>
     
        <input class="dbp-table-filter js-table-filter-input_filter_checkboxes" data-rif="<?php echo esc_attr($name_column); ?>" id="dbp_input_filter_checkboxes_<?php echo esc_attr($name_column); ?>" type="text"  >
      
    </label>
    <div id="dbp_choose_values_box_<?php echo esc_attr($name_column); ?>">
      

        <?php /*
        <div class="dbp-dropdown-line-flex" id="dbp_input_filter_checkboxes_row_<?php echo $name_column; ?>">
            <span class="dbp-filter-label"><?php _e('fast filter', 'db_press'); ?></span>
            <input class="dbp-table-filter js-table-filter-input_filter_checkboxes" data-rif="<?php echo $name_column; ?>"  id="dbp_input_filter_checkboxes_<?php echo $name_column; ?>" type="text"  >
        </div>
        */ ?>

        <div class="dbp-drowpdown-click-box">
        <?php
        dbp_fn::html_select(['IN'=>'Choose values', 'NOT IN'=>'Exclude values'], true, ' class="js-table-filter-select-op-partial dbp-small-select-for-checkbox" id="js_tf_select_op_'.esc_attr($name_column).'_2" data-rif="'.esc_attr($name_column).'"', $def_op);
        ?>
        <div class="dbp-dropdown-de-select-click js-dropdown-select-all-checkboxes" data-rif="<?php echo esc_attr($name_column); ?>"><?php _e('Select all', 'db_press'); ?></div>
        <div class="dbp-dropdown-de-select-click js-dropdown-deselect-all-checkboxes" data-rif="<?php echo esc_attr($name_column); ?>"><?php _e('Deselect all', 'db_press'); ?></div>
        </div>

        <div class="js-table-filter-checkbox-values" id="dbp_checkboxes_value_<?php echo esc_attr($name_column); ?>" data-rif="<?php echo esc_attr($name_column); ?>" data-column="<?php echo esc_attr($original_field_name); ?>">
            <ul class="dbp_dropdown_line_checkboxes_search" id="dbp_checkboxes_ul_<?php echo esc_attr($name_column); ?>" data-rif="<?php echo esc_attr($name_column); ?>">
                <li><label>Loading ...<label></li>
            </ul>
        </div>
        <div class="dbp-dropdown-info-count-checkboxes" id="dbp_dd_count-cb_<?php echo esc_attr($name_column); ?>">
            <span class="js-dbp-cb-count-selected"></span> / <span class="js-dbp-cb-count-total"></span>
        </div>
    </div>
    <div class="dbp-dropdown-hr"></div>
    <div class="dbp-dropdown-line dbp-dropdown-line-right">
        <div class="button dbp-btn-search js-dbp-btn-search"  data-rif="<?php echo esc_attr($name_column); ?>"><?php _e('OK', 'db_press'); ?></div>
        <div class="button dbp-btn-search js-dbp-dropdown-btn-cancel"><?php _e('Cancel', 'db_press'); ?></div>
    </div>
<?php endif;

if ($sort !== false || ($original_field_name != "" && $filter !== false)) : ?>
    </div>
<?php endif;