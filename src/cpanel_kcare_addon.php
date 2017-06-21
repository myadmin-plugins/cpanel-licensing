<?php
/**
 * Licensing Functionality
 * Last Changed: $LastChangedDate: 2017-05-30 21:16:52 -0400 (Tue, 30 May 2017) $
 * @author detain
 * @version $Revision: 24920 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * cpanel_kcare_addon()
 *
 * @return void
 * @throws \XmlRpcException
 */
function cpanel_kcare_addon() {
	page_title('CPanel KCare Addon');
	$settings = get_module_settings('licenses');
	$db = get_module_db('licenses');
	$id = (int)$GLOBALS['tf']->variables->request['id'];
	$services_cpanel_type = SERVICE_TYPES_CPANEL;
	if ($GLOBALS['tf']->ima == 'admin') {
		$db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_type in (select services_id from services where services_type={$services_cpanel_type})", __LINE__, __FILE__);
	} else {
		$db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_type in (select services_id from services where services_type={$services_cpanel_type}) and {$settings['PREFIX']}_custid='" . get_custid($GLOBALS['tf']->session->account_id, 'licenses') . "'", __LINE__, __FILE__);
	}
	if ($db->num_rows() > 0) {
		$db->next_record(MYSQL_ASSOC);
		$license_info = $db->Record;
		$ipAddress = $db->Record[$settings['PREFIX'] . '_ip'];
		if ($license_info[$settings['PREFIX'] . '_status'] != 'active') {
			add_output('Only Active ' . $settings['TBLNAME']);
			return;
		}
		if (!isset($GLOBALS['tf']->variables->request['submitbutton'])) {
			$table = new TFTable;
			$table->add_hidden('choice', 'none.cpanel_kcare_addon');
			$table->add_hidden('id', $id);
			$table->set_title('KCare');
			//$table->add_field('', 'l');
			//$table->add_row();
			$table->add_field($table->make_submit('Activate My License'));
			$table->add_row();
			add_output($table->get_table());
		} else {
			$license_extra = @myadmin_unstringify($license_info['license_extra']);
			if ($license_extra === false) {
				$license_extra = [];
			}
			$cl = new \Detain\Cloudlinux\Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
			$type = 16;
			//if (!$cl->isLicensed($db->Record[$settings['PREFIX'] . '_ip'], $serviceTypes[$db->Record[$settings['PREFIX'] . '_type']]['services_field1']))
			$response = $cl->isLicensed($ipAddress, true);
			myadmin_log('licenses', 'info', 'Response: ' . json_encode($response), __LINE__, __FILE__);
			if (!is_array($response) || !in_array($type, array_values($response))) {
				$response = $cl->license($ipAddress, $type);
				//$license_extra = $response['mainKeyNumber'] . ',' . $response['productKey'];
				myadmin_log('licenses', 'info', 'Response: ' . json_encode($response), __LINE__, __FILE__);
			}
			$license_extra['kcare'] = 1;
			$db->query("update licenses set license_extra='" . $db->real_escape(myadmin_stringify($license_extra)) . "' where license_id=$id", __LINE__, __FILE__);
			add_output('KCare License activated');
		}
	}
}
