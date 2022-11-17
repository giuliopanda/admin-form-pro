<?php
namespace DbPress;
if (!defined('WPINC')) die;

add_filter('pinacode_attribute_tmpl_table', 'DbPress\pinacode_attribute_tmpl_table', 10, 4 );
function pinacode_attribute_tmpl_table(  $gvalue,  $shortcode_obj ) {
	
		//$item = PinaCode::get_var('item');

	
	PinaAfterAttributes::wrap("<table>", "</table>", ['class'=>'pina-table']);
	$first = reset($gvalue);
	if (is_array($first) || is_object($first)) {
		$html[] = "<thead class=\"pina-thead\"><tr class=\"pina-thead-tr\">";
		foreach ($first as $k=>$v) {
			$html[] = "<td class=\"pina-thead-td\">".$k."</td>";
		}
		$html[] = '</tr></thead>';
	}
	$html[] ="<tbody class=\"pina-tbody\">";
	foreach ($gvalue as $item) {	
		$html[] = "<tr class=\"pina-tbody-tr\">";
		if (is_array($item) || is_object($item)) {
			foreach ($item as $k=>$v) {
				if (is_array($v) || is_object($v)) {
					$v = implode($v);
				}
				$html[] = "<td class=\"pina-tbody-td\">".wp_trim_words($v)."</td>";
			}
		} else {
			$html[] = "<td class=\"pina-tbody-td\">".wp_trim_words($item)."</td>";
		}
		$html[] = '</tr>';
	}
	$html[] = '</tbody>';
	

	$result = implode("", $html);

	return  $result;
 }
