<?php
/**
* Gestisco la parte di form per la creazione/modifica di una tabella sql
*/
namespace DbPress;
if (!defined('WPINC')) die;
if (!current_user_can('administrator'))  return;
?>
<table class="wp-list-table widefat striped dbp-table-view-list js-dragable-table">
    <thead>
        <tr>
            <th><?php _e('Name','db_press'); ?></th>
            <th><?php _e('Preset','db_press'); ?></th>
            <th><?php _e('Type','db_press'); ?></th>
            <th><?php _e('Length','db_press'); ?></th>
            <th><?php _e('Attributes','db_press'); ?></th>
            <th><?php _e('Default','db_press'); ?></th>
            <th><?php _e('Null','db_press'); ?></th>
        </tr>
    </thead>
    <?php
   
    array_shift($table_model->items);
    $preset = ['varchar'=>'String (1 line)', 'text'=>'Text (Multiline)','int_signed'=>'Number', 'decimal'=>'Decimal (9,2)', 'date'=>'Date', 'datetime'=>'Date Time', 'pri'=>'Primary Key','advanced'=>'Advanced'];
    foreach ($table_model->items as $cs) {
        $column = dbp_fn_structure::convert_show_column_mysql_row_to_form_data($cs);
        ?>
        <tr class="js-dragable-tr">
            <td>
                <b><?php echo $column->field_name; ?></b>
                <?php if ($cs->Key == "PRI" && !$table_model->error_primary) : ?>
                    <span class="dashicons dashicons-admin-network" style="color:#e2c447" title="Primary"></span>
                <?php elseif ($cs->Key == "PRI" && $table_model->error_primary) : ?>
                    <span class="dashicons dashicons-admin-network" style="color:#CCC" title="Primary NOT AUTO INCREMENT!"></span>
                    <span class="dashicons dashicons-warning" style="color:#CC0000"  title="Primary NOT AUTO INCREMENT!"></span>
                <?php endif; ?>
            </td>
            <td> <?php echo @$preset[$column->preset]; ?></td>
            <td><?php echo $column->field_type; ?></td>
            <td><?php echo $column->field_length; ?></td>
            <td><?php echo $column->attributes; ?></td>
            <td><?php echo $column->default; ?></td>
            <td><?php echo ($column->null == 't') ? 'NULL': 'NO'; ?> </td>
           
        </tr>
        <?php 
    }
    ?>
</table>



