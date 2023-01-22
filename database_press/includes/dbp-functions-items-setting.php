<?php 
/**
 * Processa un elenco di dati secondo uno schema (setting generato su list-setting) 
 * e ritorna l'array di righe da stampare.
 * 
 * Nelle liste una volta presi i dati dal post ed eseguita la query questa viene post elaborata qui
 * per restituire i dati prima della loro visualizzazione
 * 
 * dbp_items_setting::execute_list_settings($items, $settings_fields, $general_settings);
 * ritorna l'array con la prima riga strutturata nel seguente modo:
 * ['name', 'name_column', 'field_key', 'original_field_name', 'type', 'sorting', 'dropdown']
 * 
 * pagina dei test fatta
 */
namespace DbPress;

class  Dbp_items_list_setting {
    /**
	 * @var dbpDs_list_setting[] $settings_fields L'array dei setting dei singoli campi
	 */
	var $settings_fields = [];
    /**
     * @var Array|Boolean $general_settings L'array dei settaggi generali tipo quanti caratteri da visualizzare al massimo per il testo
     */
    var $general_settings = false;

    /**
     * @param Array table_model 
	 * @param dbpDs_list_setting[] $settings_fields
	 * @return Array
	 */
	public function execute_list_settings($table_model, $settings_fields = false, $general_settings = []) {
		$original_items = $table_model->items;
        if (!is_array($original_items) || count ($original_items) == 0) return false;
		
        $this->settings_fields = $settings_fields;
		
		$this->general_settings = $general_settings;
		
		$items = array_map(function ($object) {
			if (is_object($object)) {
				return clone $object; 
			} else {
				return $object;
			}
		}, $original_items);
        $items = $this->filter_by_edit_variables($items);
		$array_thead = array_shift($items);
		
        /**
         * @var Array $first_row
         */
        $first_row = [];
		$primaries = $table_model->get_pirmaries();
        foreach ($array_thead as $key => $value) {
            $row_sorting = true;
			$schema = (isset($value['schema'])) ? $value['schema'] : $key;
			if (is_object($schema)) {
				if (@$schema->type != "CUSTOM") {
					$field_key = $this->get_column_name($schema, 'alias');
					$simple_type = $schema->type;
				} else {
					$field_key = $schema->name;
					$row_sorting = false;	
					$simple_type = "gen";
				}
			} else {
				$row_sorting = false;	
				$simple_type = "gen";
				$field_key = '';
			}
			$original_field_name =  $this->get_column_name($schema, 'column');
			$name_column = dbp_fn::clean_string($field_key);
		
			$orgtable = (isset($schema->orgtable)) ? $schema->orgtable : '';
			$orgname = (isset($schema->orgname)) ? $schema->orgname : '';
			$table = (isset($schema->table)) ? $schema->table : '';
			if (is_array($value) && array_key_exists('setting', $value) && isset($value['setting']->title)) {
				$print_column_name = $value['setting']->title;
			} else {
				$print_column_name = $key;
			}
			
			if (is_array($value) && array_key_exists('setting', $value) ) {
				$width = $this->get_width_class($value['setting']);
			} else {
				$width = "";
			}
			
			$drop_down = is_object($schema);
			
			
			$pri = ($table != "" && isset($primaries[$orgtable]) && strtolower($primaries[$orgtable]) == strtolower($orgname));
			
			$first_row[$key] = (object)['name'=>$print_column_name, 'original_table' => $orgtable,  'table' => $table, 'name_column'=>$name_column, 'original_name' => $orgname,'field_key'=>$field_key, 'original_field_name'=>$original_field_name,'toggle'=>(isset($value['toggle']) ? $value['toggle'] : 'SHOW'), 'type'=> $simple_type, 'sorting'=>$row_sorting, 'dropdown' => $drop_down, 'width'=>$width, 'align'=>@$value['align'], 'mysql_name' => @$value['mysql_name'], 'name_request' => @$value['name_request'], 'searchable' => @$value['searchable'], 'custom_param' => @$value['custom_param'], 'format_values' => @$value['format_values'], 'format_styles' => @$value['format_styles'], 'pri'=>$pri];
        } 
        $count = 0;
	
        foreach ($items as $count=>&$item) {
			$item = (object)$item;
			//PinaCode::set_var('data',  $item);
            foreach ($array_thead as $key=>$setting) { 
				$count++;
				if (isset($setting['schema']) && ($setting['schema']->type =="WP_HTML" || $setting['schema']->type =="CHECKBOX")) {
					if (is_object($item)) {
						if (isset($item->$key)) {
							$value = $item->$key;
						} else {
							$value = "";
						}
					} else {
						if (isset($item[$key])) {
							$value = $item[$key];
						} else {
							$value = "";
						}
					} 
					$item->$key = $value;
				} else {
             		$item->$key = $this->edit_singe_cell($item, $key, $setting, $count, $table_model);
				}
            } 
        }
		
        array_unshift($items, $first_row);
        return $items;

	}

    /**
	 * Nell'elenco di una lista gestisce i parametri di visualizzazione dei campi della tabella
	 * aggiunge i filtri e converte il type
	 */
    private function filter_by_edit_variables($items) {
		// la prima riga posso togliere i campi che non voglio visualizzare
		reset($items);
		$first_key = key($items);
		$new_first_key = [];
		if (is_array($this->settings_fields) && count ($this->settings_fields) > 0) {
			foreach ( $this->settings_fields as $key=>$setting) {
				if (array_key_exists($key, $items[$first_key])) {
					$new_first_key[$key] = $items[$first_key][$key];
				} else {
					$new_first_key[$key] = ['schema'=>(object)['type'=>'CUSTOM', 'name'=>$key]];
				}
				$new_first_key[$key]['setting'] = $setting;
				if ($setting->isset('view') && $setting->view != "") {
					$new_first_key[$key]['schema']->type =  $setting->view;
				} else {
					$new_first_key[$key]['schema']->type = dbp_fn::h_type2txt($new_first_key[$key]['schema']->type);
				}
				$new_first_key[$key]['align'] =  $setting->align;
				$new_first_key[$key]['order'] =  $setting->order;
				$new_first_key[$key]['toggle'] =   $setting->toggle;
				$new_first_key[$key]['name_request'] =  $setting->name_request;
				$new_first_key[$key]['mysql_name'] =  $setting->mysql_name;
				$new_first_key[$key]['searchable'] =  $setting->searchable;
				$new_first_key[$key]['custom_param'] =  $setting->custom_param;
				$new_first_key[$key]['format_values'] =  $setting->format_values;
				$new_first_key[$key]['format_styles'] =  $setting->format_styles;
				
			}
			$items[$first_key] = $new_first_key;
		
			$columns = array_column($items[$first_key], 'order');
			array_multisort($columns, SORT_ASC, $items[$first_key]);
		} else {
			foreach ($items[$first_key] as $key=>$value) {
				if (isset($value['schema']) && is_object($value['schema'])) {
					$items[$first_key][$key]['schema']->type =   dbp_fn::h_type2txt($value['schema']->type);
				}
			}
		}
		return $items;
	}
 
    
	/**
	 * Fa il rendering dei singoli valori
	 */
	private function edit_singe_cell($item, $key, $setting, $count, $table_model) {
		/**
         * @var String $value
         */

		if (is_object($item)) {
			if (isset($item->$key)) {
				$value = $item->$key;
			} else {
				$value = "";
			}
		} else {
			if (isset($item[$key])) {
				$value = $item[$key];
			} else {
				$value = "";
			}
		}
		$max_char_show = $this->max_text_length();

		$value = $this->html_entities($value);

		if (strlen($value) > $max_char_show && $max_char_show > -1) {
			if (isset($this->general_settings['htmlentities']) && $this->general_settings['htmlentities'] == true) {
				$value = substr($value,0 , floor($max_char_show))." ..."; 
			} else {
				$value = substr(strip_tags($value),0 , floor($max_char_show))." ..."; 
			}
		}
		
		return $value;
	}

    /**
	 * Stampo un array o un oggetto in una cella
	 */
	static public function show_obj($obj, $depth = 1, $max_char_show = 1000, $max_depth = 10, $max_count = 10) {
		if (is_object($obj) || is_array($obj)) {
			$new_v = [];
			$count_row = 0;
			foreach ($obj as $k=>$v) {
				$count_row++;
				if ($count_row > $max_count) {
					if (is_object($obj)) {
						$obj = (array)$obj;
					}
					$new_v[] = '<div class="dbp-serialize-row dbp-depth-' . $depth . '">['.sprintf(__("Other %s elements",'db_press'), (count($obj)-$max_count)).'] ...</div>';
					break;
				}
				if (is_object($v) || is_array($v)) {
					if ($depth > $max_depth) {
						if (is_object($v)) {
							$v = "Object(".count($v).")";
						}
						if (is_array($v)) {
							$v = "Array(".count($v).")";
						}
						$new_v[] = '<div class="dbp-serialize-row dbp-depth-' . $depth . '"><span class="dbp-serialize-label">'.$k.':</span><span class="dbp-serialize-value">'.htmlentities($v).'</span></div>';
					} else {
						
						if ($depth < $max_depth) {
							$new_v[] = '<div class="dbp-serialize-row dbp-depth-' . $depth . '"><span class="dbp-serialize-label">'.$k. ' ('.gettype($v).')</span><span class="dbp-serialize-value">:</span>';
							$tt = $depth + 1; 
							$new_v[] = self::show_obj($v, $tt, $max_char_show, $max_depth, $max_count);	
						}  else {
							if (is_object($v)) {
								$v = (array)$v;
								$new_v[] = '<div class="dbp-serialize-row dbp-depth-' . $depth . '"><span class="dbp-serialize-label">'.$k.':</span><span class="dbp-serialize-value">Object('.count($v).')';
							}
							if (is_array($v)) {
								$new_v[] = '<div class="dbp-serialize-row dbp-depth-' . $depth . '"><span class="dbp-serialize-label">'.$k.':</span><span class="dbp-serialize-value">Array('.count($v).')';
							}
							if (is_array($v)) {
								$v = "Array(".count($v).")";
							}
						}
						$new_v[] = '</div>';
					}
					
				} else {
					if (strlen($v) > $max_char_show*2) {
						$v = substr($v,0 , floor($max_char_show*1.8))." ..."; 
					}
					$new_v[] = '<div class="dbp-serialize-row dbp-depth-' . $depth . '"><span class="dbp-serialize-label">'.$k.':</span><span class="dbp-serialize-value">'.htmlentities($v).'</span></div>';
				}
			}
			$value = implode("",$new_v);
		} else {
			$value = htmlentities($obj);
		}
		return $value;
	}

	/**
	 * Trovo la lunghezza massima dei testi da visualizzare
	 */
	private function max_text_length() {
		if (isset($this->general_settings['text_length']) && absint($this->general_settings['text_length']) > 1) {
			return absint($this->general_settings['text_length']);
		} else {
			return 80;
		}
	}

	private function html_entities($text) {
		if (isset($this->general_settings['htmlentities']) && $this->general_settings['htmlentities'] == true) {
			return htmlentities($text);
		} else {
			return $text;	
		} 
	}

	/**
	 * Trovo la profondità massima degli array da visualizzare
	 */
	private function max_depth() {
		if (is_array($this->general_settings)) {
			$obj_depth = (int)$this->general_settings['obj_depth'];
		} 
		if (!isset($obj_depth) || $obj_depth == 0 || $obj_depth > 10) {
			$obj_depth = 3;
		}
		return $obj_depth;
	}

    /**
	 * Ritorna il tipo di field
	 * @param Object|String $schema 
	 * @param String $field_type alias|column
	 */
	private function get_column_name($schema, $field_type="column") {

		$field_key = $original_field_name = "";
		if (isset ($schema) && is_object($schema)) {
			if ($field_type == "alias") {
				// l'alias o il nome della colonna
				if ( $schema->name == addslashes($schema->name)) {
					if (isset ($schema->table) && $schema->table != "" && $schema->orgname == $schema->name ) {
						$field_key = '`'.$schema->table.'`.`'.$schema->orgname.'`';	
					} else {
						if (@$schema->orgname != "") {							
							$field_key = '`'.$schema->name.'`';
						} else {
							$field_key = $schema->name;
						}
					}
				}
				return $field_key;
			}
			if ($field_type == "column") {
				// il nome della colonna
				if (@$schema->orgname != "" && $schema->table != "" ) {							
					$original_field_name = '`'.$schema->table.'`.`'.$schema->orgname.'`';
				} else if (@$schema->orgname != "" && $schema->orgtable ) {							
					$original_field_name = '`'.$schema->orgtable.'`.`'.$schema->orgname.'`';
				} else if (@$schema->orgname != "" ) {							
					$original_field_name = '`'.$schema->orgname.'`';
				} else {
					$original_field_name = '';
				}
				return $original_field_name;
			}
		} else if(is_string($schema) && $schema != "") {
			return '`'.$schema."`";
		}
		return '';

	}

    private function get_width_class($setting) {
		if (isset($setting) && is_object($setting)) {
			if ($setting->isset('width') && $setting->width != "") {
				return " dbp-td-width-".$setting->width;
			}
		}
		return '';
	}

	
}