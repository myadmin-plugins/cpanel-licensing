#!/usr/bin/php -q
<?php
/************************************************************************************\
* Trouble Free CPanel/VPS Services                                                   *
* (c)2012 Interserver                                                                *
\************************************************************************************/

require_once(__DIR__.'/../../../include/functions.inc.php');
$webpage = FALSE;
define('VERBOSE_MODE', FALSE);

$db = get_module_db('licenses');
$dbVps = get_module_db('vps');
$dbVps2 = get_module_db('vps');
$dbInnertell = get_module_db('innertell');
$dbCms = get_module_db('mb');
$GLOBALS['tf']->session->create(160308, 'services');
$GLOBALS['tf']->session->verify();
$whitelist = explode("\n", trim(`export PATH="\$PATH:/bin:/usr/bin:/sbin:/usr/sbin"; cat /home/interser/public_html/misha/cpanel_whitelist.txt`));
$licenses = [];
$tocheck = [];
$goodIps = [];
$ipOutput = [];
$session_id = $GLOBALS['tf']->session->sessionid;
$serviceTypes = get_license_types();
$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
$status = $cpl->fetchLicenses();
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
	$licenses[$license['ip']] = $license;
	if (in_array($license['ip'], $whitelist)) {
		$goodIps[] = $license['ip'];
		continue;
	}
	$tocheck[$license['ip']] = $license;
}

$dbInnertell->query("select primary_ipv4 from servers left join location on order_id=servers.server_id where servers.server_status='active' and primary_ipv4 is not NULL and (server_dedicated_tag like '%,%,%,%,%,%,%,6,%' or server_dedicated_tag like '%,%,%,%,%,%,%,1,%' or server_dedicated_cp=1 or server_dedicated_cp=6) and primary_ipv4 in ('".implode("','", array_keys($tocheck))."')", __LINE__, __FILE__);
while ($dbInnertell->next_record(MYSQL_ASSOC)) {
	$goodIps[] = $dbInnertell->Record['primary_ipv4'];
	unset($tocheck[$dbInnertell->Record['primary_ipv4']]);
}
/*
$db->query("select license_ip from licenses left join services on services_id=license_type where services_module='licenses' and services_category=1 and license_status='active' and license_ip in ('" . implode("','", array_keys($tocheck)) . "')", __LINE__, __FILE__);
while ($db->next_record(MYSQL_ASSOC))
{
	unset($tocheck[$db->Record['license_ip']]);
}
*/
/*
$dbVps->query("select vps_ip from vps, repeat_invoices where vps_status='active' and concat('CPanel for VPS ', vps.vps_id)=repeat_invoices.repeat_invoices_description and vps_ip in ('" . implode("','", array_keys($tocheck)) . "')");
while ($dbVps->next_record(MYSQL_ASSOC))
{
	unset($tocheck[$dbVps->Record['vps_ip']]);
}
*/
foreach ($tocheck as $ipAddress => $license) {
	if (!isset($ipOutput[$license['ip']])) {
		$ipOutput[$license['ip']] = [];
	}
	$dbCms->query("select * from client_package, package_type where client_package.pack_id=package_type.pack_id and cp_comments like '%$license[ip]%' and pack_name like '%Cpanel%' and cp_status=2");
	if ($dbCms->num_rows() > 0) {
		$goodIps[] = $license['ip'];
	}
	if (!in_array($license['ip'], $goodIps)) {
		$db->query("select licenses.*, services_name from licenses left join services on services_id=license_type where services_module='licensese' and license_ip='{$license['ip']}' and services_category=1");
		if ($db->num_rows() > 0) {
			while ($db->next_record()) {
				//$url = 'https://cpaneldirect.net/index.php?choice=none.view_license&id='.$db->Record['license_id'].'&sessionid='.$session_id;
				$url = FALSE;
				if ($db->Record['license_status'] == 'active' && $db->Record['services_name'] == $license['package']) {
					$goodIps[] = $license['ip'];
				} elseif ($db->Record['license_status'] != 'active' && $db->Record['services_name'] == $license['package']) {
					$ipOutput[$license['ip']][] = 'CPanelDirect License '.$db->Record['license_id'].' Found but status is '.$db->Record['license_status'];
					// $db->query("update licenses set license_type=$license_type where license_id='{$db->Record['license_id']}'");
				} elseif ($db->Record['license_status'] == 'active' && $db->Record['services_name'] != $license['package']) {
					$ipOutput[$license['ip']][] = 'CPanelDirect License '.$db->Record['license_id'].' Found but type is '.str_replace('INTERSERVER-', '', $db->Record['services_name']).' instead of '.str_replace('INTERSERVER-', '', $license['package']);
				} else {
					$ipOutput[$license['ip']][] = 'CPanelDirect License '.$db->Record['license_id'].' Found but status is '.$db->Record['license_status'].' and type is '.str_replace('INTERSERVER-', '', $db->Record['services_name']).' instead of '.str_replace('INTERSERVER-', '', $license['package']);
				}
			}
		}
	}
	if (!in_array($license['ip'], $goodIps)) {
		$dbVps->query("select * from vps left join repeat_invoices on concat('CPanel for VPS ', vps.vps_id) = repeat_invoices.repeat_invoices_description where vps_ip='{$license['ip']}'");
		if ($dbVps->num_rows() > 0) {
			while ($dbVps->next_record()) {
				$vps = $dbVps->Record;
				if ($vps['vps_status'] == 'active' && $vps['repeat_invoices_id'] != NULL) {
					$dbVps2->query("select * from invoices where invoices_extra=".$vps['repeat_invoices_id']." and invoices_type=1 and invoices_paid=1 and invoices_date >= date_sub('".mysql_now()."', INTERVAL 2 MONTH)");
					if ($dbVps2->num_rows() > 0) {
						$goodIps[] = $license['ip'];
					} else {
						$ipOutput[$license['ip']][] = 'VPS '.$vps['vps_id'].' Has Cpanel But Hasnt Paid In 2+ Months';
					}
				} elseif ($vps['vps_status'] == 'active' && $vps['repeat_invoices_id'] == NULL) {
					$ipOutput[$license['ip']][] = 'VPS '.$vps['vps_id'].' Found but no CPanel';
				} elseif ($vps['vps_status'] != 'active' && $vps['repeat_invoices_id'] != NULL) {
					$ipOutput[$license['ip']][] = 'VPS '.$vps['vps_id'].' Found with CPanel but VPS status is '.$vps['vps_status'];
				} else {
					$ipOutput[$license['ip']][] = "VPS ".$vps['vps_id']." Found But Status ".$vps['vps_status'].' and no CPanel';
				}
			}
		}
	}
	if (!in_array($license['ip'], $goodIps)) {
		$dbInnertell->query("select vlans_comment from ips, vlans where ips_ip='{$license['ip']}' and ips_vlan=vlans_id");
		if ($dbInnertell->num_rows() > 0) {
			$dbInnertell->next_record();
			$server = str_replace(array('append ', 'Append '), array('', ''), trim($dbInnertell->Record['vlans_comment']));
			$dbInnertell->query("select * from servers where server_hostname like '%$server%' order by status");
			if ($dbInnertell->num_rows() > 0) {
				$dbInnertell->next_record();
				$serverDedicatedTag = explode(',', $dbInnertell->Record['server_dedicated_tag']);
				if ($dbInnertell->Record['server_username'] == 'john@interserver.net') {
					$ipOutput[$license['ip']][] = 'Used By '.$dbInnertell->Record['server_hostname'];
				} elseif ($dbInnertell->Record['status'] == 'active') {
					if ((sizeof($dedicatedTag) > 8 && ($dedicatedTag[7] == 1 || $dedicatedTag[7] == 6)) || $dbInnertell->Record['server_dedicated_cp'] == 1 || $dbInnertell->Record['server_dedicated_cp'] == 6) {
						$goodIps[] = $license['ip'];
					} else {
						$ipOutput[$license['ip']][] = 'Innertell Order '.$dbInnertell->Record['id'].' found but no CPanel';
					}
				} else {
					if ((sizeof($dedicatedTag) > 8 && ($dedicatedTag[7] == 1 || $dedicatedTag[7] == 6)) || $dbInnertell->Record['server_dedicated_cp'] == 1 || $dbInnertell->Record['server_dedicated_cp'] == 6) {
						$ipOutput[$license['ip']][] = 'Innertell Order '.$dbInnertell->Record['id'].' found but status '.$dbInnertell->Record['status'];
					} else {
						$ipOutput[$license['ip']][] = 'Innertell Order '.$dbInnertell->Record['id'].' found but status '.$dbInnertell->Record['status'].' and no CPanel';
					}
				}
			} else {
				$ipOutput[$license['ip']][] = 'VLAN for '.$server.' found but no servers match';
			}
		}
	}
}
$errors = 0;
foreach ($tocheck as $ipAddress => $license) {
	if (!in_array($ipAddress, $goodIps)) {
		$errors++;
		echo 'IP '.$ipAddress.' Has errors ('.$license['hostname'].' '.$license['package'].")\n";
		if (sizeof($ipOutput[$ipAddress]) > 0) {
			foreach ($ipOutput[$ipAddress] as $error) {
				echo '	'.$error."\n";
			}
		} else {
			echo 'I was unable to find this IP anywhere, so not sure where it might have come from.';
		}
	}
}
echo $errors.'/'.sizeof($licenses).' Licenses have matching problems'."\n";
$GLOBALS['tf']->session->destroy();
