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
$db = get_module_db('licenses');
$db2 = get_module_db('licenses');
$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
$status = $cpl->fetchLicenses();
echo 'Got '.count($status['licenses'])." Licenses\n";
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
	$license['osver'] = $license2['osver'];
	$license['package'] = $license2['packageid'];
	$license['status'] = $license2['status'];
	$line = implode(',', [$license['ip'], $license['liscid'], $license['hostname'], $license['os'], $license['distro'], $license['version'], $license['envtype'], $license['osver'], $license['package'], $license['status']]);
    $db->query("select * from repeat_invoices, licenses, services where repeat_invoices_service=license_id and repeat_invoices_module='licenses' and license_type=services_id and services_module='licenses' and services_category=500 and license_ip='{$license['ip']}' and license_status='active' and services_field1='{$license['package']}'", __LINE__, __FILE__);
    if ($db->num_rows() > 0) {
        while ($db->next_record(MYSQL_ASSOC)) {
            $query = "update repeat_invoices set repeat_invoices_description='{$db->Record['services_name']} {$license['accounts']} Accounts' where repeat_invoices_id={$db->Record['repeat_invoices_id']}";
            //echo $query.PHP_EOL;
            $db2->query($query, __LINE__, __FILE__);
        }
    }
    $query = "update licenses set license_extra='".$db->real_escape($line)."' where license_ip='{$license['ip']}' and license_type in (5000,5001,5002,5005,5008,5009,5014)";
	$db->query($query, __LINE__, __FILE__);
	if ($license['hostname'] != '') {
		$query = "update licenses set license_hostname='".$db->real_escape($license['hostname'])."' where license_ip='{$license['ip']}'";
		$db->query($query, __LINE__, __FILE__);
		//echo '('.$license['hostname'].' = '.$license['ip'].") ";
		echo '.';
	}
}
echo "\n";
$GLOBALS['tf']->session->destroy();
