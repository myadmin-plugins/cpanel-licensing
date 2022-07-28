<?php
/**
 * License Related Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Licenses
 */

function cpanel_list()
{
    page_title('CPanel License List');
    if ($GLOBALS['tf']->ima == 'admin') {
        $table = new \TFTable();
        $table->set_title('CPanel License List');
        $header = false;
        function_requirements('get_cpanel_licenses');
        $licenses = get_cpanel_licenses();
        $licensesValues = array_values($licenses['lienses']);
        foreach ($licensesValues as $data) {
            if (!$header) {
                $dataKeys = array_keys($data);
                foreach ($dataKeys as $field) {
                    $table->add_field(ucwords(str_replace('_', ' ', $field)));
                }
                $table->add_row();
                $header = true;
            }
            $dataValues = array_values($data);
            foreach ($dataValues as $field) {
                $table->add_field($field);
            }
            $table->add_row();
        }
        add_output($table->get_table());
    }
}
