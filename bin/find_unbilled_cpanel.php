#!/usr/bin/php -q
<?php
/************************************************************************************\
* Trouble Free CPanel/VPS Services                                                   *
* (c)2012 Interserver                                                                *
\************************************************************************************/

require_once(__DIR__ . '/../../../include/functions.inc.php');
$webpage = false;
define('VERBOSE_MODE', false);

$db = get_module_db('licenses');
$db_vps = get_module_db('vps');
$db_vps2 = get_module_db('vps');
$db_innertell = get_module_db('innertell');
$db_cms = get_module_db('mb');
$GLOBALS['tf']->session->create(160308, 'services');
$GLOBALS['tf']->session->verify();
$whitelist = explode("\n", trim(`export PATH="\$PATH:/bin:/usr/bin:/sbin:/usr/sbin"; cat /home/interser/public_html/misha/cpanel_whitelist.txt`));
$licenses = [];
$tocheck = [];
$good_ips = [];
$ip_output = [];
$session_id = $GLOBALS['tf']->session->sessionid;
$service_types = get_license_types();
$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
$status = $cpl->fetchLicenses();
foreach ($status['licenses'] as $key => $license2)
{
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
	if (in_array($license['ip'], $whitelist))
	{
		$good_ips[] = $license['ip'];
		continue;
	}
	$tocheck[$license['ip']] = $license;
}

$db_innertell->query("select primary_ipv4 from servers left join location on order_id=servers.server_id where servers.server_status='active' and primary_ipv4 is not null and (server_dedicated_tag like '%,%,%,%,%,%,%,6,%' or server_dedicated_tag like '%,%,%,%,%,%,%,1,%' or server_dedicated_cp=1 or server_dedicated_cp=6) and primary_ipv4 in ('" . implode("','", array_keys($tocheck)) . "')", __LINE__, __FILE__);
while ($db_innertell->next_record(MYSQL_ASSOC))
{
	$good_ips[] = $db_innertell->Record['primary_ipv4'];
	unset($tocheck[$db_innertell->Record['primary_ipv4']]);
}
/*
$db->query("select license_ip from licenses left join services on services_id=license_type where services_module='licenses' and services_category=1 and license_status='active' and license_ip in ('" . implode("','", array_keys($tocheck)) . "')", __LINE__, __FILE__);
while ($db->next_record(MYSQL_ASSOC))
{
	unset($tocheck[$db->Record['license_ip']]);
}
*/
/*
$db_vps->query("select vps_ip from vps, repeat_invoices where vps_status='active' and concat('CPanel for VPS ', vps.vps_id)=repeat_invoices.repeat_invoices_description and vps_ip in ('" . implode("','", array_keys($tocheck)) . "')");
while ($db_vps->next_record(MYSQL_ASSOC))
{
	unset($tocheck[$db_vps->Record['vps_ip']]);
}
*/
foreach ($tocheck as $ip => $license)
{
	if (!isset($ip_output[$license['ip']]))
	{
		$ip_output[$license['ip']] = [];
	}
	$db_cms->query("select * from client_package, package_type where client_package.pack_id=package_type.pack_id and cp_comments like '%$license[ip]%' and pack_name like '%Cpanel%' and cp_status=2");
	if ($db_cms->num_rows() > 0)
	{
		$good_ips[] = $license['ip'];
	}
	if (!in_array($license['ip'], $good_ips))
	{
		$db->query("select licenses.*, services_name from licenses left join services on services_id=license_type where services_module='licensese' and license_ip='{$license['ip']}' and services_category=1");
		if ($db->num_rows() > 0)
		{
			while ($db->next_record())
			{
				//$url = 'https://cpaneldirect.net/index.php?choice=none.view_license&id=' . $db->Record['license_id'] . '&sessionid=' . $session_id;
				$url = false;
				if ($db->Record['license_status'] == 'active' && $db->Record['services_name'] == $license['package'])
				{
					$good_ips[] = $license['ip'];
				}
				elseif ($db->Record['license_status'] != 'active' && $db->Record['services_name'] == $license['package'])
				{
					$ip_output[$license['ip']][] = 'CPanelDirect License ' . $db->Record['license_id'] . ' Found but status is ' . $db->Record['license_status'];
					// $db->query("update licenses set license_type=$license_type where license_id='{$db->Record['license_id']}'");
				}
				elseif ($db->Record['license_status'] == 'active' && $db->Record['services_name'] != $license['package'])
				{
					$ip_output[$license['ip']][] = 'CPanelDirect License ' . $db->Record['license_id'] . ' Found but type is ' . str_replace('INTERSERVER-', '', $db->Record['services_name']) . ' instead of ' . str_replace('INTERSERVER-', '', $license['package']);
				}
				else
				{
					$ip_output[$license['ip']][] = 'CPanelDirect License ' . $db->Record['license_id'] . ' Found but status is ' . $db->Record['license_status'] . ' and type is ' . str_replace('INTERSERVER-', '', $db->Record['services_name']) . ' instead of ' . str_replace('INTERSERVER-', '', $license['package']);
				}
			}
		}
	}
	if (!in_array($license['ip'], $good_ips))
	{
		$db_vps->query("select * from vps left join repeat_invoices on concat('CPanel for VPS ', vps.vps_id) = repeat_invoices.repeat_invoices_description where vps_ip='{$license['ip']}'");
		if ($db_vps->num_rows() > 0)
		{
			while ($db_vps->next_record())
			{
				$vps = $db_vps->Record;
				if ($vps['vps_status'] == 'active' && $vps['repeat_invoices_id'] != null)
			{
					$db_vps2->query("select * from invoices where invoices_extra=" . $vps['repeat_invoices_id'] . " and invoices_type=1 and invoices_paid=1 and invoices_date >= date_sub('" . mysql_now() . "', INTERVAL 2 MONTH)");
					if ($db_vps2->num_rows() > 0)
					{
						$good_ips[] = $license['ip'];
					}
					else
					{
						$ip_output[$license['ip']][] = 'VPS ' . $vps['vps_id'] . ' Has Cpanel But Hasnt Paid In 2+ Months';
					}
				}
				elseif ($vps['vps_status'] == 'active' && $vps['repeat_invoices_id'] == null)
				{
					$ip_output[$license['ip']][] = 'VPS ' . $vps['vps_id'] . ' Found but no CPanel';
				}
				elseif ($vps['vps_status'] != 'active' && $vps['repeat_invoices_id'] != null)
				{
					$ip_output[$license['ip']][] = 'VPS ' . $vps['vps_id'] . ' Found with CPanel but VPS status is ' . $vps['vps_status'];
				}
				else
				{
					$ip_output[$license['ip']][] = "VPS " . $vps['vps_id'] . " Found But Status " . $vps['vps_status'] . ' and no CPanel';
				}
			}
		}
	}
	if (!in_array($license['ip'], $good_ips))
	{
		$db_innertell->query("select vlans_comment from ips, vlans where ips_ip='{$license['ip']}' and ips_vlan=vlans_id");
		if ($db_innertell->num_rows() > 0)
		{
			$db_innertell->next_record();
			$server = str_replace(array('append ', 'Append '), array('', ''), trim($db_innertell->Record['vlans_comment']));
			$db_innertell->query("select * from servers where server_hostname like '%$server%' order by status");
			if ($db_innertell->num_rows() > 0)
			{
				$db_innertell->next_record();
				$server_dedicated_tag = explode(',', $db_innertell->Record['server_dedicated_tag']);
				if ($db_innertell->Record['server_username'] == 'john@interserver.net')
				{
					$ip_output[$license['ip']][] = 'Used By ' . $db_innertell->Record['server_hostname'];
				}
				elseif ($db_innertell->Record['status'] == 'active')
				{
					if ((sizeof($dedicated_tag) > 8 && ($dedicated_tag[7] == 1 || $dedicated_tag[7] == 6 )) || $db_innertell->Record['server_dedicated_cp'] == 1 || $db_innertell->Record['server_dedicated_cp'] == 6)
					{
						$good_ips[] = $license['ip'];
					}
					else
					{
						$ip_output[$license['ip']][] = 'Innertell Order ' . $db_innertell->Record['id'] . ' found but no CPanel';
					}
				}
				else
				{
					if ((sizeof($dedicated_tag) > 8 && ($dedicated_tag[7] == 1 || $dedicated_tag[7] == 6 )) || $db_innertell->Record['server_dedicated_cp'] == 1 || $db_innertell->Record['server_dedicated_cp'] == 6)
					{
						$ip_output[$license['ip']][] = 'Innertell Order ' . $db_innertell->Record['id'] . ' found but status ' . $db_innertell->Record['status'];
					}
					else
					{
						$ip_output[$license['ip']][] = 'Innertell Order ' . $db_innertell->Record['id'] . ' found but status ' . $db_innertell->Record['status'] . ' and no CPanel';
					}
				}
			}
			else
			{
				$ip_output[$license['ip']][] = 'VLAN for ' . $server . ' found but no servers match';
			}
		}
	}
}
$errors = 0;
foreach ($tocheck as $ip => $license)
{
	if (!in_array($ip, $good_ips))
	{
		$errors++;
		echo 'IP ' . $ip . ' Has errors (' . $license['hostname'] . ' ' . $license['package'] . ")\n";
		if (sizeof($ip_output[$ip]) > 0)
		{
			foreach ($ip_output[$ip] as $error)
			{
				echo '	' . $error . "\n";
			}
		}
		else
		{
			echo 'I was unable to find this IP anywhere, so not sure where it might have come from.';
		}
	}
}
echo $errors . '/' . sizeof($licenses) . ' Licenses have matching problems' . "\n";
$GLOBALS['tf']->session->destroy();
