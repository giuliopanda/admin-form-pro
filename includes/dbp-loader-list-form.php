<?php
/**
 * Gestisco il filtri e hook della form 
 *
 * @package    DATABASE TABLE
 * @subpackage DATABASE TABLE/INCLUDES
 * @internal
 */
namespace admin_form;

class  Dbp_pro_loader_list_form {
	/**
	 * @var Object $saved_queries le ultime query salvate per tipo
	 */
	public static $saved_queries;

	public function __construct() {
		// nel tab form aggiunge il lookup
		add_action ('dbp_list_form_add_field_config', [$this,'form_single_table_add_lookup_config_form'], 10, 4);
		add_filter('form_single_table_type_fields', [$this, 'form_single_table_type_fields'], 10, 3);
		add_action ('dbp_list_form_pre_form', [$this,'pre_form']);

    }

	/**
     * Dentro la gestione delle form aggiungo le parti di form che mi servono per impostare i campi speciali
     */
    public function form_single_table_add_lookup_config_form($count_fields, $item, $total_row, $select_array_test) {
		// CALCULATED_FIELD
        ?>
		<div class="dbp-structure-grid js-calculated-field-block" style="display:<?php echo (in_array(@$item->form_type, ['CALCULATED_FIELD'])) ? 'grid' : 'none'; ?>">
			<div class="dbp-form-row-column">
				<div>
					<label><span class="dbp-form-label"><?php _e('Calculated Field: formula','admin_form'); ?>
					<?php ADFO_fn::echo_html_icon_help('dbp_list-list-form','calc_field'); ?></span></label>
					<textarea class="js-name-with-count dbp-input js-fields-custom-value-calc" style="width:100%" rows="3" name="fields_custom_value_calc[<?php echo absint($count_fields); ?>]"><?php echo esc_textarea(@$item->custom_value); ?></textarea>
					<div><span class="dbp-link-click" onclick="show_pinacode_vars()">show shortcode variables</span></div>
				</div>
				<div style="margin-top:1rem">
				<?php echo ADFO_fn::html_select(['EMPTY'=>'Calculate the formula only when the field is empty.','EVERY_TIME'=>'Recalculate the formula each time you save'], true, ' name="fields_custom_value_calc_when['.absint($count_fields).']" class="js-name-with-count"', @$item->custom_value_calc_when); ?>
			
				</label>
				</div>
			</div>
			<div class="dbp-form-row-column"> 
			<br>
			<p>
				<?php if ($total_row > 0) : ?>
					<div class="dbp-form-row-column" style="margin-bottom:.5rem">
					<label>Choose the record: <?php echo ADFO_fn::html_select($select_array_test, true, ' class="js-choose-test-row"'); ?>
					</label>
					<div class="button js-test-formula" onClick="click_af_test_formula(this);"><?php _e('Test formula', 'admin_form'); ?></div>
				</div>
					<div class="button" id="dbp_<?php  echo ADFO_fn::get_uniqid(); ?>" onClick="click_af_recalculate_formula(jQuery(this).prop('id'), 0, <?php echo $total_row ; ?>);"><?php _e('Recalculate and save all records', 'admin_form'); ?></div>
				<?php endif; ?>
			</p>
			</div>
		</div>
		
		<?php
		if ( $item->lookup_id != '') {
            $lookup_col_list = ADFO_fn::get_table_structure($item->lookup_id, true);
            $primary = ADFO_fn::get_primary_key($item->lookup_id);
            $pos = array_search($primary, $lookup_col_list);
            if ($pos !== false) {
                unset($lookup_col_list[$pos]);
            }

        } else {
            $lookup_col_list = [];
        }
        $list_of_tables = ADFO_fn::get_table_list();
        ?>
        <div class="js-dbp-lookup-data"<?php echo (@$item->form_type != 'LOOKUP') ? ' style="display:none"' : ''; ?> id="id<?php echo ADFO_fn::get_uniqid(); ?>">
            <h3><?php _e('Lookup params','admin_form'); ?>
            <?php ADFO_fn::echo_html_icon_help('dbp_list-list-form','lookup'); ?>
            </h3>
            <div class="dbp-structure-grid">
                <div class="dbp-form-row-column">
                    <label class="dbp-label-grid dbp-css-mb-0"><span class="dbp-form-label"><?php _e('Choose Table','admin_form'); ?></span>
                    <?php echo ADFO_fn::html_select($list_of_tables['tables'], true, 'name="fields_lookup_id['. absint($count_fields) . ']" onchange="dbp_change_lookup_id(this)" class="js-name-with-count js-select-fields-lookup js-prevent-exceeded-1000"', @$item->lookup_id); ?>
                    </label>
                </div>
                <div class="dbp-form-row-column">
                    <label class="dbp-label-grid dbp-css-mb-0"><span class="dbp-form-label"><?php _e('Label','admin_form'); ?></span>
                    <?php echo ADFO_fn::html_select($lookup_col_list, false, 'name="fields_lookup_sel_txt['. absint($count_fields) . ']"  class="js-name-with-count js-lookup-select-text js-prevent-exceeded-1000"', @$item->lookup_sel_txt); ?>
                    </label>
                    <input type="hidden" name="fields_lookup_sel_val[<?php echo absint($count_fields); ?>]" class="js-name-with-count js-lookup-select-value js-prevent-exceeded-1000" value="<?php echo esc_attr(@$item->lookup_sel_val); ?>">
                </div>
            </div>
            <div class="dbp-form-row dbp-label-grid">
                <label><span class="dbp-form-label"><?php _e('Query WHERE part (optional)','admin_form'); ?></span></label>
                <div>
                <textarea class="js-name-with-count dbp-input js-lookup-where js-prevent-exceeded-1000" style="width:100%; margin-bottom:.5rem" rows="1" name="fields_lookup_where[<?php echo absint($count_fields); ?>]"><?php echo esc_textarea((isset($item->lookup_where) ? $item->lookup_where : '')); ?></textarea>
                <?php $id_test_lookup = 'dbpl_' . ADFO_fn::get_uniqid() ;?>
                <span class="dbp-link-click" onclick="btn_lookup_test_query(this,'<?php echo esc_attr($id_test_lookup); ?>')"><?php _e('Query test','admin_form'); ?></span>
                <span id="<?php echo esc_attr($id_test_lookup); ?>" style="margin-left:1rem"></span>
                </div>
            </div>
        
            <hr>
        </div>
        <?php
    }

	 /**
     * Nel tab form nella scelta del tipo di campo aggiunge i campi speciali della versione pro
     */
    public function form_single_table_type_fields($form_type_fields, $item_type_txt, $item_is_pri) {
		if ($item_is_pri) return $form_type_fields;

        if (isset($form_type_fields['Special fields']) && !in_array($item_type_txt,["DATE","DATETIME"])) {
            $form_type_fields['Special fields']['LOOKUP'] = 'Lookup';
        }
		if (!isset($form_type_fields['Special fields'])) {
			$form_type_fields['Special fields'] = [];
		}
		$form_type_fields['Special fields']['CALCULATED_FIELD'] = 'Calculated field';
        return  $form_type_fields;
    }

	/**
	 * Qui inietto il codice prima che inizi a renderizzare la form 
	 */
	public function pre_form() {
		?>
		<script>
			jQuery(document).ready(function () {
				update_field_type_for_dup('Special fields', 'CALCULATED_FIELD', 'Calculated field');
				update_field_type_for_dup('Special fields', 'LOOKUP', 'Lookup');
			});
		</script>
		<?php
	}


}
new Dbp_pro_loader_list_form();