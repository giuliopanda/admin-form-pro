<?php
/**
 * PinaActions
 * Gestione Statica delle azioni di pinacode
 * Qui vengono caricate tutte le funzioni speciali e poi eseguite a seconda del nome dello shortcode
 */
namespace DbPress;

class PinaActions
{
	private static $action 	= [];
	/**
	 * Memorizza una nuova funzione di pinacode.	
	 * @param   String action_name
	 * @param   String function_name
	 * @return  	void
	**/
	static function set($action_name, $function_name) { 
		if(self::$action == null) {   
			self::$action = [];
		}
		self::$action[trim(strtolower($action_name))] =  $function_name;
	}
	/**
	 * @param Array $short_code_transformed [shortcode_name:Str,attributes:[]]
	 * @return Array [found:Boolean dice se ha trovato il risultato, result:dice il nuovo valore]
	 */
	static function execute($short_code_transformed) {
		
		if (isset($short_code_transformed['shortcode']) && isset($short_code_transformed['attributes'])) {
			$short_code_transformed['shortcode'] = $shortcode = $short_code_transformed['shortcode'];
			$function_name = "";
			
			if (array_key_exists($shortcode , self::$action)) {
				$function_name = self::$action[$shortcode];
			} else {
				foreach (self::$action as $key=>$act) {
					if (substr($shortcode,0, strlen($key)) == $key && strlen($shortcode) > strlen($key) && substr($shortcode, strlen($key),1) == ".") {
						$function_name = $act;
					}
				}
			}
			
			if ($function_name != "") {
				if (function_exists($function_name) || function_exists(__NAMESPACE__ . '\\'.$function_name)) {
					if (is_array($short_code_transformed['attributes'])) {
						foreach ($short_code_transformed['attributes'] as &$sct) {
							$sct =  pina_remove_quotes($sct);
						}
					}
					if (function_exists($function_name)) {
						$ris = call_user_func_array($function_name, ['short_code_name'=>$short_code_transformed['shortcode'], 'attributes'=>$short_code_transformed['attributes']]);
					} else if (function_exists(__NAMESPACE__ . '\\'.$function_name)) {
						$ris = call_user_func_array(__NAMESPACE__ . '\\'.$function_name, ['short_code_name'=>$short_code_transformed['shortcode'], 'attributes'=>$short_code_transformed['attributes']]);
					}
					if ($ris !== false) {
						return $ris;
					} else {
						PcErrors::set(sprintf('%s function not found (shortcode: %s)',$function_name, $short_code_transformed['shortcode']), '', -1, 'debug');
						if (isset($short_code_transformed['string_shortcode'])) {
							return $short_code_transformed['string_shortcode'];
						} else {
							return '';
						}
					}
				}
			}
		} else {
			PcErrors::set('You have to pass an array with the following structures ["shortcode":string,"attributes":Array]', '', -1, 'notice');
		}
		if (isset($short_code_transformed['string_shortcode'])) {
			return $short_code_transformed['string_shortcode'];
		} else {
			return '';
		}
	}
}

/**
* Interfaccia in stile wp per aggiungere una funzione a pinacode.
* @param String $action_name
* @param String $function_name
*/
function pinacode_set_functions($action_name, $function_name) {
	PinaActions::set($action_name, $function_name);
}

/**
 * [^NOW]
 */
if (!function_exists('pinacode_fn_now')) {
	function pinacode_fn_now($short_code_name, $attributes) {
		return (new \DateTime('now',  wp_timezone()))->format('Y-m-d H:i:s');
	}
	
}
pinacode_set_functions('now', 'pinacode_fn_now');

/**
 * [^post id=""]
 * [^image]
 */
if (!function_exists('pinacode_fn_wp_post')) {
	function pinacode_fn_wp_post($short_code_name, $attributes) {
		global $_wp_additional_image_sizes;
		$post_fields = array("ID"=>"id", "post_author" => "author", "post_date"=>"date", "post_content" => "content", "post_title" => "title",  "post_status" => "status", "comment_status" => "comment_status",  "post_excerpt" => "excerpt", "post_name" => "name", "post_modified" => "modified", "post_parent" => "parent", "guid" =>  "guid", "menu_order" => "menu_order",  "post_type" => "type", "post_mime_type" => "mime_type", "comment_count" => "comment_count", "filter" => "filter");
		$get_var = "";
		$posts = null;
		$light_load = false;
		if (isset($attributes['light_load']) && $attributes['light_load'] != 0 ) {
			$light_load = true;
		}
		if (strpos($short_code_name, ".") !== false) {
			$shortcode_command = explode(".", $short_code_name);
			$shortcode_command = array_shift($shortcode_command);
		} else {
			$shortcode_command = $short_code_name;
		}
		
		$array_query = [];

		if (isset($attributes['post_status'])) {
			$array_query['post_status'] = PinaCode::get_registry()->short_code($attributes['post_status']);
		} else	if (isset($attributes['status'])) {
			$array_query['post_status'] = PinaCode::get_registry()->short_code($attributes['status']);
		} else {
			$array_query['post_status'] = 'publish';
		}

		if (isset($attributes['year'])) {
			$array_query['year'] = PinaCode::get_registry()->short_code($attributes['year']);
		}
		if (isset($attributes['month'])) {
			$array_query['monthnum'] = PinaCode::get_registry()->short_code($attributes['month']);
		}
		if (isset($attributes['week'])) {
			$array_query['w'] = PinaCode::get_registry()->short_code($attributes['week']);
		}
		if (isset($attributes['day'])) {
			$array_query['day'] = PinaCode::get_registry()->short_code($attributes['day']);
		}
		$array_query['post_type'] = "post";
		if (isset($attributes['type'])) {
			$array_query['post_type'] = PinaCode::get_registry()->short_code($attributes['type']);
		}
		if (isset($attributes['post_type'])) {
			$array_query['post_type'] = PinaCode::get_registry()->short_code($attributes['post_type']);
		}
		if (isset($attributes['mime_type'])) {
			$array_query['post_mime_type'] = PinaCode::get_registry()->short_code($attributes['mime_type']);
		}
		if (isset($attributes['parent_id'])) {
			$array_query['post_parent'] = PinaCode::get_registry()->short_code($attributes['parent_id']);
		}
		if (isset($attributes['slug'])) {
			$array_query['pagename'] = PinaCode::get_registry()->short_code($attributes['slug']);
		}
		$forse_single = false;
		if (isset($attributes['id'])) {
			$attributes['id'] = PinaCode::get_registry()->short_code($attributes['id']);
			if (is_array($attributes['id']) || is_object($attributes['id'])) {
				$array_query['post__in'] = (array)$attributes['id'];
			} else {
				$forse_single = true;
				$array_query['p'] = $attributes['id'];
			}
		} 

		if ($shortcode_command == "image") {
			//$array_query['post_mime_type'] = 'image';
			$array_query['post_type'] = "attachment";
			$array_query['post_status'] = "inherit";
			if (isset($attributes['post_id']) && is_string($attributes['post_id'])) {
				$array_query['post_parent'] = PinaCode::get_registry()->short_code($attributes['post_id']);
			}
			if (isset($attributes['light_load']) && $attributes['light_load'] == 0 ) {
				$light_load = false;
			} else {
				$light_load = true;
			}
		}

		if (isset($attributes['first'])) {
			if (isset($array_query['order']) ||  isset($attributes['asc']) || isset($attributes['desc']) || isset($attributes['last']) ) {
				PcErrors::set('^POST if you use <b>\'last\'</b> attribute don\'t use order, last, asc, desc ', '', -1, 'notice');
			}
			if (isset($attributes['desc'])) {
				unset($attributes['desc']);
			}
			$attributes['asc'] = '';
			$attributes['order'] ='date';
			if (!is_numeric($attributes['first'])) {
				$attributes['limit'] = 5;
			} else {
				$attributes['limit'] = $attributes['first'];
			}
		}

		if (isset($attributes['last'])) {
			if (isset($array_query['order']) || isset($attributes['first']) || isset($attributes['asc']) || isset($attributes['desc']) ) {
				PcErrors::set('^POST if you use <b>\'last\'</b> attribute don\'t use order,  first, asc, desc ', '', -1, 'notice');
			}
			if (isset($attributes['asc'])) {
				unset($attributes['asc']);
			}
			$attributes['desc'] = '';
			$attributes['order'] ='date';
			if (!is_numeric($attributes['last'])) {
				$attributes['limit'] = 5;
			} else {
				$attributes['limit'] = $attributes['last'];
			}
		}
		if (isset($attributes['rand'])) {
			if (isset($array_query['order']) || isset($attributes['first']) || isset($attributes['asc']) || isset($attributes['desc']) ) {
				PcErrors::set('^POST if you use <b>\'last\'</b> attribute don\'t use order,  first, asc, desc ', '', -1, 'notice');
			}
			if (isset($attributes['asc'])) {
				unset($attributes['asc']);
			}
			if (isset($attributes['desc'])) {
				unset($attributes['desc']);
			}
			$attributes['order'] ='rand';
			if (!is_numeric($attributes['rand'])) {
				$attributes['limit'] = 5;
			} else {
				$attributes['limit'] = $attributes['rand'];
			}
		}
		if (isset($attributes['limit'])) {
			$array_query['posts_per_page'] = PinaCode::get_registry()->short_code($attributes['limit']);
		}
		if (isset($attributes['posts_per_page'])) {
			$array_query['posts_per_page'] = PinaCode::get_registry()->short_code($attributes['posts_per_page']);
		}
		if (isset($attributes['offset'])) {
			$array_query['offset'] = PinaCode::get_registry()->short_code($attributes['offset']);
		}
		if (isset($attributes['paged'])) {
			$array_query['paged'] = PinaCode::get_registry()->short_code($attributes['paged']);
		}

		if (isset($attributes['asc'])) {
			$array_query['order'] = 'ASC';
			if (!isset($attributes['order'])) {
				$attributes['order'] = "title";
			}
		}
		if (isset($attributes['desc'])) {
			$array_query['order'] = 'DESC';
			if (!isset($attributes['order'])) {
				$attributes['order'] = "title";
			}
		}
		if (isset($attributes['order'])) {
			$attributes['order'] = PinaCode::get_registry()->short_code($attributes['order']);
			if (false !== $key = array_search($attributes['order'], $post_fields)) {
				$attributes['order'] = $key; 
			}
			if (array_key_exists($attributes['order'], $post_fields) || $attributes['order'] == "rand" ) {
				$array_query['orderby'] = $attributes['order'];
			} else {
				$array_query['orderby'] = 'meta_value';
				$array_query['meta_key'] =  $attributes['order'];
			}
		}
		
		if (isset($attributes['cat'])) {
			$attributes['cat'] = PinaCode::get_registry()->short_code($attributes['cat']);
			if (is_array($attributes['cat']) || is_object($attributes['cat'])) {
				$array_query['category__in'] =   (array)$attributes['cat'];
			} else if (is_numeric(trim($attributes['cat']))) {
				$array_query['cat'] =  $attributes['cat'];
			} else {
				$array_query['category_name'] = $attributes['cat'];
			}
		}
		if (isset($attributes['!cat'])) {
			$attributes['!cat'] = PinaCode::get_registry()->short_code($attributes['!cat']);
			if (is_array($attributes['!cat']) || is_object($attributes['!cat'])) {
				$array_query['category__not_in'] =   (array)$attributes['!cat'];
			} else if (is_numeric(trim($attributes['!cat']))) {
				$array_query['category__not_in'] =  [trim($attributes['!cat'])];
			} else if (is_string($attributes['!cat'])) {
				$cat_obj = get_category_by_slug(trim($attributes['!cat']));
				if (isset($cat_obj->cat_ID)) {
					$array_query['category__not_in'] =  [$cat_obj->cat_ID];
				} else {
					PcErrors::set('^POST category not found <b>'.$attributes['!cat']."</b>", '', -1, 'notice');
				}
			}
		}

		if (isset($attributes['tag'])) {
			$attributes['tag'] = PinaCode::get_registry()->short_code($attributes['tag']);
			if (is_array($attributes['tag']) || is_object($attributes['tag'])) {
				$array_query['tag'] =  @implode(",",$attributes['tag']);
			} else {
				$array_query['tag'] = $attributes['tag'];
			}
		}
		
		if (isset($attributes['author'])) {
			$attributes['author'] = PinaCode::get_registry()->short_code($attributes['author']);
			if (is_array($attributes['author'])) {
				$array_query['author'] = implode(",",$attributes['author']);
			} else if (is_numeric(trim($attributes['author']))) {
				$array_query['author'] =  $attributes['author'];
			} else {
				$array_query['author_name'] = $attributes['author'];
			}
		}

		if (isset($attributes['meta_query'])) {
			$array_query['meta_query'] = parse_query_string_fn(pina_remove_quotes(PinaCode::get_registry()->short_code($attributes['meta_query'])));
		}
		//var_dump ($array_query);
		$query = new \WP_Query($array_query);
		if ( $query->have_posts() ) {
			$posts = $query->posts;  
		} else {
			return NULL;
		}  
		
		$get_var = [];
		if ($posts != null && count ($posts) > 0 && (!$forse_single || ($forse_single && count ($posts) == 1))) {
			foreach ($posts as $post) {
				if (!isset($post->ID)) continue;
				if (!$light_load) {
					$permalink = get_permalink($post);
				}
				$post = (array) $post;
				
				$temp_post = [];
				foreach ($post as $k=>$p) {
					if (array_key_exists($k, $post_fields)) {
						$temp_post[$post_fields[$k]] = $p;
					}
				}
				if (!$light_load) {
					$temp_post['permalink'] = $permalink;
					$temp_post['title_link'] = '<a href="'.$permalink.'" class="pina_link pina_link_title">'.$post['post_title'].'</a>';
					if (isset($attributes['read_more'])) {
						$read_more = PinaCode::get_registry()->short_code($attributes['read_more']);
					} else {
						$read_more = "...";
					}
					$temp_post['read_more_link'] = '<a href="'.$permalink.'" class="pina_link pina_link_title">' . $read_more . '</a>';
				
					$author = \get_user_by('ID', $post['post_author']);
					if (@is_object($author)) {
						$temp_post['author_id']		= $author->data->ID;
						$temp_post['author_email']	= $author->data->user_email;
						$temp_post['author_name']	= $author->display_name;
						$temp_post['author_roles'] 	= $author->roles;
						$temp_post['author_link']	= get_author_posts_url($author->data->ID);
						$temp_post['author']	= '<a href="'.$temp_post['author_link'].'" class="pina_link pina_link_author">' . $author->display_name . '</a>';
					} else {
						$temp_post['author'] = "";
					}
				
					$post_meta = get_post_meta($post['ID']);
					foreach ($post_meta as $k=>$pm) {
						if (in_array($k, ['_edit_lock','_edit_last','_pingme','_encloseme'])) continue;
						if ($k == "_thumbnail_id" && is_array($pm) && ($temp_post['type'] != 'attachment' || substr($temp_post['mime_type'],0,5) != 'image')) {
							$attachment_id = array_shift($pm);
							if ($attachment_id > 0) {
								if (isset($attributes['image_size'])) {
									$attributes['image_size'] = PinaCode::get_registry()->short_code($attributes['image_size']);
								} else {
									$attributes['image_size'] = 'post-thumbnail';
								}
								$temp_post['image'] =  wp_get_attachment_image($attachment_id, @$attributes['image_size'] );
								$temp_post['image_link'] ='<a href="'.$permalink.'" class="pina_link pina_link_image">' . $temp_post['image'] . '</a>';
								$temp_post['image_id'] = $attachment_id;
							}
						} else {
							if (array_key_exists($k, $temp_post)) {
								$k = "meta_".$k;
							}
							if (count($pm) == 1) {
								if ($k == "_wp_attachment_metadata") {
									$pm = array_shift($pm);
									$temp_pm = maybe_unserialize($pm);
									if (is_array($temp_pm)) {
										foreach ($temp_pm as $ktemp=>$vtemp) {
											$temp_post['attachment_'.$ktemp] = $vtemp;
										}
									}
								} else {
									$temp_post[$k] = array_shift($pm);
								}
							} else {
								$temp_post[$k] = $pm;
							}
						}
					}
					
				}
				// se sono immagini
				if ($temp_post['type'] == 'attachment' && substr($temp_post['mime_type'],0,5) == 'image') {
					$permalink = get_permalink($temp_post['id']);
					if (isset($attributes['image_size']) && $attributes['image_size'] == "fit") {
						$temp_post['image'] =  '<img src="'.wp_get_attachment_url($temp_post['id']).'" title="'.esc_attr($temp_post['title']).'" class="dbp-image-fit">';
					} else if (isset($attributes['image_size']) && $attributes['image_size'] == "winfit") {
						$permalink = get_permalink($temp_post['id']);
						$temp_post['image'] =  '<img src="'.wp_get_attachment_url($temp_post['id']).'" title="'.esc_attr($temp_post['title']).'" class="dbp-image-win-fit">';
					} else {
						$temp_post['image'] = wp_get_attachment_image($temp_post['id'], @$attributes['image_size'],'', ['class'=>'pina-image']  );
					}
					$temp_post['url'] = wp_get_attachment_image_url($temp_post['id'], @$attributes['image_size']);
					$temp_post['original_url'] = wp_get_attachment_url($temp_post['id']);

					$temp_post['image_link'] ='<a href="'.$permalink.'" class="pina_link pina_link_image">' . $temp_post['image'] . '</a>';
				}


				$get_var[] = $temp_post;   
			}
		} else {
			return '';
		}
		PinaCode::set_var($shortcode_command, $get_var);
		if (substr($short_code_name, 0, strlen($shortcode_command)+1) == $shortcode_command.".") {	
			$get_var = PinaCode::get_var($short_code_name);
			PinaCode::set_var("post", $get_var);
		}
		return $get_var;
	}
}
pinacode_set_functions('post', 'pinacode_fn_wp_post');
pinacode_set_functions('image', 'pinacode_fn_wp_post');

/**
 * [^USER id|slug|email|login]
 */
if (!function_exists('pinacode_fn_wp_user')) {
	function pinacode_fn_wp_user($short_code_name, $attributes) { 
		$get_var = null;
		if (@array_key_exists('id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['id']);
			$get_var = \get_user_by('ID', $id);
		} else if (@array_key_exists('slug', $attributes)) {
			$slug = PinaCode::get_registry()->short_code($attributes['slug']);
			$get_var = \get_user_by('slug', $slug); 
		} else if (@array_key_exists('email', $attributes)) {
			$email = PinaCode::get_registry()->short_code($attributes['email']);
			$get_var = \get_user_by('email', $email); 
		} else if (@array_key_exists('login', $attributes)) {
			$login = PinaCode::get_registry()->short_code($attributes['login']);
			$get_var = \get_user_by('login', $login);
		} else {
			$get_var = \get_user_by('ID', get_current_user_id());
		}
		if (@is_object($get_var)) {
			$get_var2 = [];
			$shift_array = ["rich_editing","syntax_highlighting","comment_shortcuts","admin_color","use_ssl","show_admin_bar_front","wp_capabilities","wp_user_level","dismissed_wp_pointers","show_welcome_panel","wp_dashboard_quick_press_last_post_id","wp_user-settings","wp_user-settings-time","session_tokens", "frm_reviewed","screen_layout_acf-field-group","closedpostboxes_acf-field-group", "meta-box-order_acf-field-group", "metaboxhidden_acf-field-group", "managenav-menuscolumnshidden", "metaboxhidden_nav-menus", 'meta-box-order_dashboard'];
			$get_var2['id'] 		= $get_var->ID;
			$get_var2['login'] 		= $get_var->user_login;
			$get_var2['email']		= $get_var->user_email;
			$get_var2['roles'] 		= $get_var->roles;
			$get_var2['registered']	= $get_var->user_registered;
			$meta = \get_user_meta($get_var->ID);
			$array_names = ['login','email','roles','registered'];
			foreach ($meta as $key=>$m) {
				if (in_array($key, $shift_array)) continue;
				if (in_array($key, $array_names)) {
					$key = "meta_".$key;
				}
				if (is_array($m) && count($m) == 1) {
					$m = array_shift($m);
					if ($m !== "") {
						$get_var2[$key] = \maybe_unserialize($m);
					}
				} else {
					$get_var2[$key] = $m;
				}
			}
			$get_var = $get_var2;
		} else {
			PcErrors::set('<b>[^'.$short_code_name.'</b> I haven\'t found any users. Filter users using id or slug or email or login attributes. If not, search for the active user.', '', -1, 'warning');
		}
		PinaCode::set_var("user", $get_var);
		if (substr($short_code_name, 0, 5) == "user.") {	
			if (!PinaCode::has_var($short_code_name)) {
				PcErrors::set('The variable <b>'.$short_code_name.'</b> does not exist', '', -1, 'error');
				$get_var = '';
			} else {
				$get_var = PinaCode::get_var($short_code_name);
			}
		}
		return $get_var;
	}
}
pinacode_set_functions('user', 'pinacode_fn_wp_user');


/**
 * [^get_tag id|slug|name|term_id]
 * @since 0.9
 */
if (!function_exists('pinacode_fn_wp_get_tag')) {
	function pinacode_fn_wp_get_tag($short_code_name, $attributes) { 
		$get_var = null;
		if (@array_key_exists('id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['id']);
			$get_var = \get_term_by('ID', $id);
		} else if (@array_key_exists('slug', $attributes)) {
			$slug = PinaCode::get_registry()->short_code($attributes['slug']);
			$get_var = \get_term_by('slug', $slug, 'post_tag'); 
		} else if (@array_key_exists('name', $attributes)) {
			$name = PinaCode::get_registry()->short_code($attributes['name']);
			$get_var = \get_term_by('name', $name, 'post_tag'); 
		} else if (@array_key_exists('term_id', $attributes)) {
			$term_id = PinaCode::get_registry()->short_code($attributes['term_id']);
			$get_var = \get_term_by('term_id', $term_id, 'post_tag');
		} else {
			return '';
		}
		if (@is_object($get_var)) {
			$get_var2 = [];
			$get_var2['id'] 			= $get_var->term_id;
			$get_var2['term_id'] 		= $get_var->term_id;
			$get_var2['name'] 			= $get_var->name;
			$get_var2['slug'] 			= $get_var->slug;
			$get_var2['term_group']		= $get_var->term_group;
			$get_var2['taxonomy'] 		= $get_var->taxonomy;
			$get_var2['parent']			= $get_var->parent;
			$get_var2['link'] = get_term_link($get_var);
			$get_var2['html'] ='<a href="'.$get_var2['link'].'" class="dbp-post-category">'. $get_var2['name'].'</a>';
			$get_var = $get_var2;
		} else {
			PcErrors::set('<b>[^'.$short_code_name.'</b> I haven\'t found any tags.', '', -1, 'warning');
			return '';
		}
		PinaCode::set_var("get_tag", $get_var);
		if (substr($short_code_name, 0, 8) == "get_tag.") {	
			if (!PinaCode::has_var($short_code_name)) {
				PcErrors::set('The variable <b>'.$short_code_name.'</b> does not exist', '', -1, 'error');
				$get_var = '';
			} else {
				$get_var = PinaCode::get_var($short_code_name);
			}
		}
		return $get_var;
	}
}
pinacode_set_functions('get_tag', 'pinacode_fn_wp_get_tag');


/**
 * [^get_tag id|slug|name|term_id]
 * @since 0.9
 */
if (!function_exists('pinacode_fn_wp_get_cat')) {
	function pinacode_fn_wp_get_cat($short_code_name, $attributes) { 
		$get_var = null;
		if (@array_key_exists('id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['id']);
			$get_var = \get_term_by('ID', $id);
		} else if (@array_key_exists('slug', $attributes)) {
			$slug = PinaCode::get_registry()->short_code($attributes['slug']);
			$get_var = \get_term_by('slug', $slug, 'category'); 
		} else if (@array_key_exists('name', $attributes)) {
			$name = PinaCode::get_registry()->short_code($attributes['name']);
			$get_var = \get_term_by('name', $name, 'category'); 
		} else if (@array_key_exists('term_id', $attributes)) {
			$term_id = PinaCode::get_registry()->short_code($attributes['term_id']);
			$get_var = \get_term_by('term_id', $term_id, 'category');
		} else {
			return '';
		}
		if (@is_object($get_var)) {
			$get_var2 = [];
			$get_var2['id'] 			= $get_var->term_id;
			$get_var2['term_id'] 		= $get_var->term_id;
			$get_var2['name'] 			= $get_var->name;
			$get_var2['slug'] 			= $get_var->slug;
			$get_var2['term_group']		= $get_var->term_group;
			$get_var2['taxonomy'] 		= $get_var->taxonomy;
			$get_var2['parent']			= $get_var->parent;
			$get_var2['link'] = get_term_link($get_var);
			$get_var2['html'] ='<a href="'.$get_var2['link'].'" class="dbp-post-category">'. $get_var2['name'].'</a>';
			$get_var = $get_var2;
		} else {
			PcErrors::set('<b>[^'.$short_code_name.'</b> I haven\'t found any tags.', '', -1, 'warning');
			return '';
		}
		PinaCode::set_var("get_cat", $get_var);
		if (substr($short_code_name, 0, 8) == "get_cat.") {	
			if (!PinaCode::has_var($short_code_name)) {
				PcErrors::set('The variable <b>'.$short_code_name.'</b> does not exist', '', -1, 'error');
				$get_var = '';
			} else {
				$get_var = PinaCode::get_var($short_code_name);
			}
		}
		return $get_var;
	}
}
pinacode_set_functions('get_cat', 'pinacode_fn_wp_get_cat');


/**
 * [^get_post_tags post_id]
 * @since 0.9
 */
if (!function_exists('pinacode_fn_wp_get_post_tags')) {
	function pinacode_fn_wp_get_post_tags($short_code_name, $attributes) { 
		$get_var = null;
		if (@array_key_exists('post_id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['post_id']);
			$get_var = \wp_get_post_tags($id);
		}  else {
			return '';
		}
		if (!is_array($get_var)) {
			PcErrors::set('<b>[^'.$short_code_name.'</b> I haven\'t found any tags', '', -1, 'warning');
			return '';
		}  else {
			$get_var2 = [];
			foreach ($get_var as $g) {
				$temp = [];
				$temp['id'] 			= $g->term_id;
				$temp['term_id'] 		= $g->term_id;
				$temp['name'] 			= $g->name;
				$temp['slug'] 			= $g->slug;
				$temp['term_group']		= $g->term_group;
				$temp['taxonomy'] 		= $g->taxonomy;
				$temp['parent']			= $g->parent;
				$temp['link'] = get_term_link($g);
				$temp['html'] ='<a href="'.$g->link.'" class="dbp-post-category">'. $g->name.'</a>';
				$get_var2[] = $temp;
			}
			$get_var = $get_var2;
			
		}
		PinaCode::set_var("get_post_tags", $get_var);
		//var_dump (PinaCode::get_var("get_post_tags"));
		if (substr($short_code_name, 0, 14) == "get_post_tags.") {	
			if (!PinaCode::has_var($short_code_name) && PinaCode::get_var($short_code_name) == "") {
				PcErrors::set('The variable <b>'.$short_code_name.'</b> does not exist', '', -1, 'error');
				$get_var = '';
			} else {
				//$get_var = PinaCode::get_var($short_code_name);
				$get_var = PinaCode::get_var($short_code_name);
			}
		}
		return $get_var;
	}
}
pinacode_set_functions('get_post_tags', 'pinacode_fn_wp_get_post_tags');


/**
 * [^get_post_cats post_id]
 * @since 0.9
 */
if (!function_exists('pinacode_fn_wp_get_post_cats')) {
	function pinacode_fn_wp_get_post_cats($short_code_name, $attributes) { 
		$get_var = null;
		if (@array_key_exists('post_id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['post_id']);
			$get_var = \wp_get_post_categories($id);
		}  else {
			return '';
		}
		if (!is_array($get_var)) {
			PcErrors::set('<b>[^'.$short_code_name.'</b> I haven\'t found any categories.', '', -1, 'warning');
			return '';
		} else {
			$get_var2 = [];
			foreach ($get_var as $g) {
				$g = get_category(absint($g));
				$temp = [];
				$temp['id'] 			= $g->term_id;
				$temp['term_id'] 		= $g->term_id;
				$temp['name'] 			= $g->name;
				$temp['slug'] 			= $g->slug;
				$temp['term_group']		= $g->term_group;
				$temp['taxonomy'] 		= $g->taxonomy;
				$temp['parent']			= $g->parent;
				$temp['link'] = get_term_link($g);
				$temp['html'] ='<a href="'.$g->link.'" class="dbp-post-category">'. $g->name.'</a>';
				$get_var2[] = $temp;
			}
			$get_var = $get_var2;
		}
		PinaCode::set_var("get_post_cats", $get_var);
		if (substr($short_code_name, 0, 14) == "get_post_cats.") {	
			if (!PinaCode::has_var($short_code_name) && PinaCode::get_var($short_code_name) == "") {
				PcErrors::set('The variable <b>'.$short_code_name.'</b> does not exist', '', -1, 'error');
				$get_var = '';
			} else {
				$get_var = PinaCode::get_var($short_code_name);
			}
		}
		return $get_var;
	}
}
pinacode_set_functions('get_post_cats', 'pinacode_fn_wp_get_post_cats');



/**
 * [^current_post]
 * @since 0.9
 */
if (!function_exists('pinacode_fn_wp_get_current_post')) {
	function pinacode_fn_wp_get_current_post($short_code_name, $attributes) { 
		$get_var = get_post();
	
		if ( empty( $get_var )) {
			return '';
		}
		$get_var->id = $get_var->ID;
		PinaCode::set_var("current_post", $get_var);
		if (substr($short_code_name, 0, 13) == "current_post.") {	
			if (!PinaCode::has_var($short_code_name) ) {
				PcErrors::set('The variable <b>'.$short_code_name.'</b> does not exist', '', -1, 'error');
				$get_var = '';
			} else {
				$get_var = PinaCode::get_var($short_code_name);
			}
		}
		return $get_var;
	}
}
pinacode_set_functions('current_post', 'pinacode_fn_wp_get_current_post');

/**
 * [^AUTHOR id]
 * TODO  $reg->get($short_code_name); dà errore!!!!!! DA CORREGGERE!
 */
/*
if (!function_exists('pinacode_fn_wp_author')) {
	function pinacode_fn_wp_author($short_code_name, $attributes) { 
 		$get_var = "";
		// ID | slug | email | login
		if (@array_key_exists('id', $attributes)) {
			$id = $attributes['id'];
			$post = get_post($id);
			if (@is_object($post)) {
				$author = get_user_by('ID', $post->post_author);
			
				if (@is_object($author)) {
					$get_var 			= $author->data;
					$get_var->name 		= $author->display_name;
					$get_var->roles 	= $author->roles;
					$get_var->allcaps 	= $author->allcaps;
					$get_var->link = get_author_posts_url($get_var->ID);
					if (@array_key_exists('args', $attributes)) { 
						$get_var->link =pina_add_query_arg($attributes['args'], $get_var->link);
					}
					PinaCode::set_var("author", $get_var);
					if (substr($short_code_name, 0, 7) == "author.") {
						$get_var = $reg->get($short_code_name);
					} else {
						if ($get_var->link) {
							$add = pina_add_html_attributes($attributes);
							PinaAfterAttributes::wrap("<a href=\"".$get_var->link."\"".$add.">", "</a>");

							$get_var = $get_var->name;
						} else {
							$get_var = $get_var->name;
						}
					}
				}
			} else {
				$get_var = "";
			}
		} else {
			$get_var = "";
		}
	}	
}
pinacode_set_functions('author', 'pinacode_fn_wp_author');
*/


/**
 * [^LINK id=PAGEID]
 */
/*
if (!function_exists('pinacode_fn_wp_link')) {
	function pinacode_fn_wp_link($short_code_name, $attributes) { 
	
		
		$get_var = "";
		$text = "";
	
		if (@array_key_exists('id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['id']);
			$link = get_permalink($id);
		} else if (@array_key_exists('post', $attributes) ) {
			$post = PinaCode::get_registry()->short_code($attributes['post']);
			if (is_numeric($post)) {
				$post = get_post($post);
			}
			if (is_array($post) || is_object($post)) {
				if (is_array(reset($post)) || is_object(reset($post))) {
					$post = reset($post);
				} 
				$link = get_permalink($post['id']);
				$attributes['post'] = (array)$post;
				
				if (array_key_exists('title', $attributes['post'])) {
					$text = $attributes['post']['title'];
				} else if (array_key_exists('post_title', $attributes['post'])) {
					$text = $attributes['post']['post_title'];
				}
			} 
		}  else {
			$item = PinaCode::get_var('item');
			if (is_array($item) && array_key_exists('title', $item)  && array_key_exists('id', $item)) {
				$link = get_permalink($item['id']);
				$text = $item['title'];
			} else {
				$link = get_permalink();
			}
		}
		if (@array_key_exists('text', $attributes)) { 
			// eventuali altri attributi dovrebbero essere associati a questo testo
			$text = PinaCode::get_registry()->short_code($attributes['text']);
			$text = strip_tags($text);
			
		}
		if ($text == "") {
			$text = $link;
		}
		$add = pina_add_html_attributes($attributes);
		
		if (@array_key_exists('args', $attributes)) { 
			$attributes['args'] = PinaCode::get_registry()->short_code($attributes['args']);
			$link = pina_add_query_arg($attributes['args'], $link);
		}
		if ($short_code_name == "link.url") {
			$get_var = $link;
		} else if ($short_code_name == "link.text") {
			$get_var = $text;
		} else {
			PinaAfterAttributes::wrap("<a href=\"".$link."\"".$add.">", "</a>");
			$get_var = $text;
		}
		return $get_var;
	}
}
pinacode_set_functions('link', 'pinacode_fn_wp_link');
*/

/**
 * [^LINK id=post_id]
 */
if (!function_exists('pinacode_fn_wp_link')) {
	function pinacode_fn_wp_link($short_code_name, $attributes) { 

		if (@array_key_exists('page_id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['page_id']);
			$link = \get_permalink($id);
			unset($attributes['page_id']);
		} elseif (@array_key_exists('post_id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['post_id']);
			$link = \get_permalink($id);
			unset($attributes['post_id']);
		} elseif (@array_key_exists('id', $attributes)) {
			$id = PinaCode::get_registry()->short_code($attributes['id']);
			$link = \get_permalink($id);
			unset($attributes['id']);
		} else {
			$link = \get_permalink();
		}
		
		if (count ($attributes) > 0) {
			foreach ($attributes as $key=>$attr) {
				$attributes[$key] = PinaCode::get_registry()->short_code($attr);
			}
			$link = add_query_arg($attributes, $link);
		}
		return $link;
	}
}
pinacode_set_functions('link', 'pinacode_fn_wp_link');
/**
 * [^get_data ]
 */
$GLOBAL['_pina_meta_data'] = [];
if (!function_exists('pinacode_fn_get_metadata')) {
	function pinacode_fn_get_metadata($short_code_name, $attributes) { 
		// Attributi:  type=post|user|other post_id user_id=
		// get|field funziona già dagli attributi classici
		
		// è un post se non esiste l'attributo user_id
		if (!isset($attributes['user_id'])) {
			if (isset($attributes['post_id'])) {
				$id = $attributes['post_id']; 
			} else {
				$id = get_the_ID();
			}
			
			if ($id > 0) {
				$result = [];
				$get_var = get_post_meta($id);
				
				foreach ($get_var as &$pm) {
					if (count($pm) == 1) {
						$pm = array_pop($pm);
					} 
				}
			
			} else {
				//ERROR
				return false;
			}
		} else if (isset($attributes['user_id'])) { 
			$id = $attributes['user_id']; 
			$get_var = get_user_meta($id);
			foreach ($get_var as &$pm) {
				if (count($pm) == 1) {
					$pm = array_pop($pm);
				} 
			}
		
		}

		PinaCode::set_var("get_field", $get_var);
		if (substr($short_code_name, 0, 10) == "get_field.") {	
			$get_var = PinaCode::get_var($short_code_name);
		}
		return $get_var;
	}
}
pinacode_set_functions('get_field', 'pinacode_fn_get_metadata');

/**
 * [^get_the_id ]
 */
if (!function_exists('pinacode_fn_get_the_id')) {
	function pinacode_fn_get_the_id($short_code_name, $attributes) { 
		return get_the_ID();
	}
}
pinacode_set_functions('get_the_id', 'pinacode_fn_get_the_id');


/**
 * [^is_page_author ]
 */
if (!function_exists('pinacode_fn_is_page_author')) {
	function pinacode_fn_is_page_author($short_code_name, $attributes) { 
		return (is_author()) ? 1 : 0;
	}
}
pinacode_set_functions('is_page_author', 'pinacode_fn_is_page_author');

/**
 * [^is_page_archive]
 */
if (!function_exists('pinacode_fn_is_page_archive')) {
	function pinacode_fn_is_page_archive($short_code_name, $attributes) { 
		return (is_archive()) ? 1 : 0;
	}
}
pinacode_set_functions('is_page_archive', 'pinacode_fn_is_page_archive');

/**
 * [^is_page_tag]
 */
if (!function_exists('pinacode_fn_is_page_tag')) {
	function pinacode_fn_is_page_tag($short_code_name, $attributes) { 
		return (is_tag()) ? 1 : 0;
	}
}
pinacode_set_functions('is_page_tag', 'pinacode_fn_is_page_tag');

/**
 * [^is_page_date]
 */
if (!function_exists('pinacode_fn_is_page_date')) {
	function pinacode_fn_is_page_date($short_code_name, $attributes) { 
		return (is_date()) ? 1 : 0;
	}
}
pinacode_set_functions('is_page_date', 'pinacode_fn_is_page_date');

/**
 * [^is_page_tax]
 */
if (!function_exists('pinacode_fn_is_page_tax')) {
	function pinacode_fn_is_page_tax($short_code_name, $attributes) { 
		return (is_tax()) ? 1 : 0;
	}
}
pinacode_set_functions('is_page_tax', 'pinacode_fn_is_page_tax');




/**
 * [^is_page]
 */
if (!function_exists('pinacode_fn_is_page')) {
	function pinacode_fn_is_page($short_code_name, $attributes) { 
		return (is_page()) ? 1 : 0;
	}
}
pinacode_set_functions('is_page', 'pinacode_fn_is_page');

/**
 * [^is_single]
 */
if (!function_exists('pinacode_fn_is_single')) {
	function pinacode_fn_is_single($short_code_name, $attributes) { 
		return (is_single()) ? 1 : 0;
	}
}
pinacode_set_functions('is_single', 'pinacode_fn_is_single');

/**
 * [^is_user_logged_in]
 */
if (!function_exists('pinacode_fn_is_user_logged_in')) {
	function pinacode_fn_is_user_logged_in($short_code_name, $attributes) { 
		return (is_user_logged_in()) ? 1 : 0;
	}
}
pinacode_set_functions('is_user_logged_in', 'pinacode_fn_is_user_logged_in');

/**
 * [^is_admin]
 */
if (!function_exists('pinacode_fn_is_admin')) {
	function pinacode_fn_is_admin($short_code_name, $attributes) { 
		return (is_admin()) ? 1 : 0;
	}
}
pinacode_set_functions('is_admin', 'pinacode_fn_is_admin');


/**
 * [^counter name= start=0 step=1]
 */
if (!function_exists('pinacode_fn_counter')) {
	function pinacode_fn_counter($short_code_name, $attributes) {

		if (@array_key_exists('start', $attributes)) {
			$start = absint(PinaCode::get_registry()->short_code($attributes['start']));
		} else {
			$start = 0;
		}
		$step = 1;
		if (@array_key_exists('step', $attributes)) {
			$step = (float)PinaCode::get_registry()->short_code($attributes['step']);
			if (!is_numeric($step)) {
				$step = 1;
			}
		}
		
		if (@array_key_exists('name', $attributes)) {
			$name = PinaCode::get_registry()->short_code($attributes['name']);
		} else {
			$name = "_main";
		}
		$pina_counter = get_option('dbp_pina_counter');
		if (!is_array($pina_counter)) {
			$pina_counter = [];
		}
		if (!isset($pina_counter[$name]) || ($pina_counter[$name] < $start && $step > 0) ||  ($pina_counter[$name] > $start && $step < 0) ) {
			$pina_counter[$name] = (float)$start;
		} else {
			$pina_counter[$name] = (float)$pina_counter[$name];
		}
		if ($step != 0) {
			$pina_counter[$name] = $pina_counter[$name]+$step;
			update_option('dbp_pina_counter',$pina_counter, false);
		}

		return $pina_counter[$name];
	}
}
pinacode_set_functions('counter', 'pinacode_fn_counter');