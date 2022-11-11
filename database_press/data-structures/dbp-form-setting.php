<?php
/**
 * Qui le classi per la gestione dei form. 
 * dbpDs_field_param è Il table_params del form
 * dbpDs_table_param sono invece i parametri di un singola tabella di inserimento dei form
 */

 /**
  * dbpDs_field_param è Il table_params del form
  */
namespace DbPress;

class  DbpDs_field_param extends dbpDs_data_structures
{
    /** @var string $name Il nome del campo nella query */
    protected $name;
    /** @var string $orgtable   */
    protected $orgtable;
     /** @var string $table  */
     protected $table;
    /** @var string $id  */
    protected $id;
    /** @var string $label  */
    protected $label;
    /** @var string $type Il type del campo nel db (int unsigned) */
    protected $type;
    /** @var string $note  */
    protected $note = "";
    /** @var string $field_name Il nome del campo nell'html es: edit_table[7][wpp_postmeta][meta_key][] */
    protected $field_name;
    /** @var string $js_rif Il riferimento js  */
    protected $js_rif;
    /** @var string $form_type Il tipo di campo  */
    protected $form_type;
    /** @var object $options   */
    protected $options;
    /** @var string $required   */
    protected $required;
    /** @var string $custom_css_class   */
    protected $custom_css_class = "";
    /** @var string $default_value   */
    protected $default_value;
     /** @var string $js_script Javascript personalizzato  */
    protected $js_script = "";
    /** @var string $custom_value Un campo per i valori aggiuntivi  */
    protected $custom_value = "";
    /** @var string $edit_view HIDE|SHOW  */
    protected $edit_view;
    /** @var string $post_types Solo per i form_type  post  */
    protected $post_types;
    /** @var string $post_cats Solo per i form_type  post  */
    protected $post_cats;
    /** @var string $user_roles Solo per i form_type  user  */
    protected $user_roles;
    /** @var string $lookup_id Solo per i form_type lookup  */
    protected $lookup_id;
    /** @var string $lookup_sel_val Solo per i form_type lookup  */
    protected $lookup_sel_val;
    /** @var string $lookup_sel_txt Solo per i form_type lookup  */
    protected $lookup_sel_txt;
    /** @var string $lookup_where Solo per i form_type lookup filtra le proposte  */
    protected $lookup_where;
    /** @var int $is_pri */
    protected $is_pri;
    /** @var int $where_precompiled */
    protected $where_precompiled = 0;
    /** @var int $order */
    protected $order = 0;
    /** @var int $autocomplete Per i campi testo se far apparire i suggerimenti mentre si scrive */
    protected $autocomplete = 1;
     /** @var string $custom_value_calc_when Quando deve essere rigenerato il campo calcolato EMPTY|EVERY_TIME */
     protected $custom_value_calc_when = "EMPTY";

    public function __construct($array = "")
    {
        if (is_array($array)) {
            $this->set_from_array($array);
        }
        if (is_object($array)) {
            $this->set_from_array((array)$array);
        }
        if (empty($this->id)) {
            $this->id = 'id'.dbp_fn::get_uniqid();
        }
    }
    
}

/**
 * sempre per dbp-form descrive i dati dei gruppi delle tabelle
 */
class  DbpDs_table_param extends dbpDs_data_structures
{
     /** @var string $allow_create  SHOW|HIDE Mostra se è possibile non creare il record di una singola tabella */
    protected $allow_create = "SHOW";
    
     /** @var string $show_title  SHOW|HIDE  */
    protected $show_title = "SHOW";
     /** @var string $frame_style white|green|yellow|blue|red|purple|brown  */
    protected $frame_style = "WHITE";
     /** @var string $title   */
    protected $title = "";
     /** @var string $description   */
    protected $description = "";
     /** @var string $module_type EDIT (creo la form con i campi modificabili) VIEW (mostro i dati)   */
    protected $module_type = "EDIT";
     /** @var string $table_compiled   */
    protected $table_compiled = "";
     /** @var string $table_status   */
    protected $table_status = "";
    /** @var string $pri_name   */
    protected $pri_name = "";
    /** @var string $pri_orgname   */
    protected $pri_orgname = "";
    /** @var string $pri_value   */
    protected $pri_value = "";
    /** @var string $count_form_block   */
    protected $count_form_block = "";
    /** @var string $orgtable   */
    protected $orgtable = "";
    /** @var string $table */
    protected $table = "";
    /** @var string $precompiled_primary_id */
    protected $precompiled_primary_id;
    /**
     * Setta un frame_style casuale
     *
     * @return void
     */
    public function set_rand_frame_style() {
       $colors = array('WHITE','GREEN','YELLOW','BLUE','RED','PURPLE','BROWN');
       $this->frame_style = $colors[rand(0,count($colors)-1)];
    }
}