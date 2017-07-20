#!/usr/bin/php -q
<?php
/**
* Updates our data with whats in cpanels db
* @author Joe Huss <detain@corpmail.interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @subpackage update_cpanel_data
* @copyright 2017
*/

require_once __DIR__.'/../../../../include/functions.inc.php';
$webpage = FALSE;
define('VERBOSE_MODE', FALSE);
$show_help = FALSE;
$endprog = FALSE;
$GLOBALS['tf']->session->create(160307, 'services');
$GLOBALS['tf']->session->verify();
$db = get_module_db('licenses');
$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
$status = $cpl->fetchLicenses();
echo 'Got ' .count($status['licenses'])." Licenses\n";
foreach ($status['licenses'] as $key => $license2) {
	$license = [];
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
	$query = "update licenses set license_extra='".$db->real_escape($line)."' where license_ip='$license[ip]' and license_type in (1,2,3,9,10,15)";
	$db->query($query, __LINE__, __FILE__);
	if ($license['hostname'] != '') {
		$query = "update licenses set license_hostname='".$db->real_escape($license['hostname'])."' where license_ip='$license[ip]'";
		$db->query($query, __LINE__, __FILE__);
		//echo '('.$license['hostname'].' = '.$license['ip'].") ";
		echo '.';
	}
}
echo "\n";
$GLOBALS['tf']->session->destroy();
