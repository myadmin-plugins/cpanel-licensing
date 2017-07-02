<?php
/**
 * License Related Functionality
 * Last Changed: $LastChangedDate: 2017-05-26 04:46:46 -0400 (Fri, 26 May 2017) $
 * @author detain
 * @version $Revision: 24805 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

function cpanel_list() {
	page_title('CPanel License List');
	if ($GLOBALS['tf']->ima == 'admin') {
		$table = new TFTable;
		$table->set_title('CPanel License List');
		$header = FALSE;
		function_requirements('get_cpanel_licenses');
		$licenses = get_cpanel_licenses();
		$licensesValues = array_values($licenses['lienses']);
		foreach ($licensesValues as $data) {
			if (!$header) {
				foreach (array_keys($data) as $field)
					$table->add_field(ucwords(str_replace('_', ' ', $field)));
				$table->add_row();
				$header = TRUE;
			}
			foreach ($data as $key => $field)
				$table->add_field($field);
			$table->add_row();
		}
		add_output($table->get_table());
	}
	//add_output('<div style="text-align: left;"><pre>'.var_export(get_softaculous_licenses(), TRUE).'</pre></div>');
}
