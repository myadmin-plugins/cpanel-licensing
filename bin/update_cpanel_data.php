#!/usr/bin/env php
<?php
/**
* Updates our data with whats in cpanels db
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @category update_cpanel_data
* @copyright 2019
*/

require_once __DIR__.'/../../../../include/functions.inc.php';
$webpage = false;
define('VERBOSE_MODE', false);
$show_help = false;
$endprog = false;
$GLOBALS['tf']->session->create(160307, 'services');
$GLOBALS['tf']->session->verify();
$module = 'licenses';
$db = get_module_db($module);
$settings = get_module_settings($module);
$db2 = get_module_db($module);
$serviceTypes = run_event('get_service_types', false, $module);
$db->query("select * from services where services_module='licenses' and services_type=500");
$cpanelPackages = [];
while ($db->next_record(MYSQL_ASSOC)) {
	$cpanelPackages[$db->Record['services_field1']] = $db->Record;
}
$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
echo 'Loading cPanel Licenses...';
$status = $cpl->fetchLicenses();
echo 'Got '.count($status['licenses'])." Licenses\n";
$out = [
	'licenses' => count($status['licenses']),
	'updates' => [],
	'problems' => [
		'multiple' => [],
		'null_repeat' => [],
	], 
];
foreach ($status['licenses'] as $key => $license2) {
	$license = [];
	$license['accounts'] = $license2['accounts'];
	$license['ip'] = $license2['ip'];
	$license['liscid'] = $license2['licenseid'];
	$license['hostname'] = $license2['hostname'];
	$license['os'] = $license2['os'];
	$license['distro'] = $license2['distro'];
	$license['version'] = $license2['version'];
	$license['envtype'] = $license2['envtype'];
	$license['host_type'] = $license2['host_type'];
	$license['osver'] = $license2['osver'];
	$license['package'] = (int)$license2['packageid'];
	$license['status'] = $license2['status'];
	$line = implode(',', [$license['ip'], $license['liscid'], $license['hostname'], $license['os'], $license['distro'], $license['version'], $license['envtype'], $license['osver'], $license['package'], $license['status'], $license['accounts']]);
	$isExternal = in_array($license['package'], [559, 560, 401, 21175, 21179, 21183, 21187, 21897, 31365]) ? true : false;
	$isServer = $license['host_type'] == 'dedicated' ? true : false;
	$costData = getCpanelCost($license['accounts'], $isServer, $isExternal);
	$package = array_key_exists($license['package'], $cpanelPackages) ? $cpanelPackages[$license['package']] : false;
	$oldService = $package !== false ? $package['services_id'] : false;
	$newService = $costData['service'];
	$db->query("select * from licenses left join repeat_invoices on repeat_invoices_module='licenses' and repeat_invoices_id=license_invoice and repeat_invoices_service=license_id left join services on license_type=services_id and services_type=500 where services_id is not null and license_ip='{$license['ip']}'", __LINE__, __FILE__);
	if ($db->num_rows() > 1) {
		$db->query("select * from licenses left join repeat_invoices on repeat_invoices_module='licenses' and repeat_invoices_id=license_invoice and repeat_invoices_service=license_id left join services on license_type=services_id and services_type=500 where license_status='active' and services_id is not null and license_ip='{$license['ip']}'", __LINE__, __FILE__);
	}
	if ($db->num_rows() > 1) {
		echo "Multiple Entries found for {$license['ip']}".PHP_EOL;
		$ids = [];
		while ($db->next_record(MYSQL_ASSOC)) {
			$ids[] = $db->Record['license_id'];
			echo "	Found ".json_encode($db->Record).PHP_EOL;
		}
		$out['problems']['multiple'][$license['ip']] = $ids; 
	} elseif ($db->num_rows() == 1) {
		$db->next_record(MYSQL_ASSOC);
		if (is_null($db->Record['repeat_invoices_id'])) {
			echo "Null Repeat Invoice found for License {$license['ip']}".PHP_EOL;
			$out['problems']['null_repeat'][$license['ip']] = $db->Record['license_id']; 
		} else {
			$changes = [];
			if ($db->Record['repeat_invoices_frequency'] != 1) {
				$changes[] = ['repeat_invoices_frequency', $db->Record['repeat_invoices_frequency'], 1];
			}
			if ((float)$db->Record['repeat_invoices_cost'] != (float)$costData['cost']) {
				$changes[] = ['repeat_invoices_cost', $db->Record['repeat_invoices_cost'], (float)$costData['cost']];
			}
			if ($db->Record['repeat_invoices_description'] != "{$serviceTypes[$newService]['services_name']} {$license['accounts']} Accounts") {
				$changes[] = ['repeat_invoices_description', $db->Record['repeat_invoices_description'], "{$serviceTypes[$newService]['services_name']} {$license['accounts']} Accounts"];
			}
			if (count($changes) > 0) {
				$repeatObj = new \MyAdmin\Orm\Repeat_Invoice();
				$repeatObj->load_real($db->Record['license_id']);
				foreach ($changes as $change) {
					echo "Making Changes to {$license['ip']} Repeat Invoice {$db->Record['repeat_invoices_id']} setting {$change[0]} from '{$change[1]}' to '{$change[2]}'".PHP_EOL;
					$out['updates'][] = [$license['ip'], $db->Record['license_id'], $change[0], $change[1], $change[2], $db->Record['repeat_invoices_id']];
					$func = 'set'.ucwords(str_replace('repeat_invoices_', '', $change[0]));
					$repeatObj->$func($change[2]);
				}
				$repeatObj->save();
			}
			$changes = [];
			if ($db->Record['license_frequency'] != 1) {
				$changes[] = ['license_frequency', $db->Record['license_frequency'], 1];
			}
			if ((float)$db->Record['license_cost'] != (float)$costData['cost']) {
				$changes[] = ['license_cost', $db->Record['license_cost'], (float)$costData['cost']];
			}
			if ($db->Record['license_type'] != $newService) {
				$changes[] = ['license_type', $db->Record['license_type'], $newService];
			}
			if (count($changes) > 0) {
				$licenseObj = new \MyAdmin\Orm\License();
				$licenseObj->load_real($db->Record['license_id']);
				foreach ($changes as $change) {
					echo "Making Changes to {$license['ip']} License {$db->Record['license_id']} setting {$change[0]} from '{$change[1]}' to '{$change[2]}'".PHP_EOL;
					$out['updates'][] = [$license['ip'], $db->Record['license_id'], $change[0], $change[1], $change[2], $db->Record['license_id']];
					$func = 'set'.ucwords(str_replace('license_', '', $change[0]));
					$licenseObj->$func($change[2]);
				}
				$licenseObj->save();
			}
		}
	} else {
		// here are most likely vps/qs addon type orders in our system
		//echo "Nothing found for ".json_encode($license).PHP_EOL;
		//$out .= "Nothing found for ".json_encode($license).PHP_EOL;
		//exit;
	}
	$query = "update licenses set license_extra='".$db->real_escape($line)."' where license_ip='{$license['ip']}' and license_type in (5000,5001,5002,5005,5008,5009,5014,10682,10683)";
	$db2->query($query, __LINE__, __FILE__);
	if ($license['hostname'] != '') {
		$query = "update licenses set license_hostname='".$db->real_escape($license['hostname'])."' where license_ip='{$license['ip']}'";
		$db2->query($query, __LINE__, __FILE__);
		//echo '('.$license['hostname'].' = '.$license['ip'].") ";
		//echo '.';
	}
}
$smarty = new TFSmarty();
$smarty->assign('out', $out);
(new \MyAdmin\Mail())->adminMail('CPanel License Updates', $smarty->fetch('email/admin/cpanel_updates.tpl'), 'my@interserver.net', 'admin/cpanel_updates.tpl');
echo "\n";
$GLOBALS['tf']->session->destroy();
