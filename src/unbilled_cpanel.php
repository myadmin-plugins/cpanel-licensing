<?php
/**
 * Licensing Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Licenses
 */
/**
 * unbilled_cpanel()
 *
 * @return FALSE|null
 * @throws \Exception
 * @throws \SmartyException
 */
function unbilled_cpanel()
{
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('view_service')) {
		dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
		return false;
	}
	$db = get_module_db('licenses');
	$dbVps = get_module_db('vps');
	$dbVps2 = get_module_db('vps');
	$dbCms = get_module_db('mb');
	$type = get_service_define('CPANEL');
	if (!isset($GLOBALS['webpage']) || $GLOBALS['webpage'] != false) {
		page_title('Unbilled CPanel Licenses');
		if (class_exists('TFTable')) {
			$outType = 'tftable';
			$table = new \TFTable;
			$table->alternate_rows();
		} else {
			$outType = 'table';
		}
	} else {
		$outType = 'text';
	}
	//209.159.155.230,4893465,Printnow.Gr,Linux,centos enterprise 5.8,11.32.3.19,virtuozzo,2.6.18-238.19.1.el5.028stab092.2PAE,INTERSERVER-INTERNAL-VZZO,1
	$whitelist = explode("\n", trim(`cat /home/interser/public_html/misha/cpanel_whitelist.txt`));
	$licenses = [];
	$tocheck = [];
	$goodIps = [];
	$ipOutput = [];
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	$status = $cpl->fetchLicenses();
	$statusValues = array_values($status['licenses']);
	foreach ($statusValues as $license2) {
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
	$db->query("select assets.primary_ipv4 from servers left join assets on assets.order_id=servers.server_id where servers.server_status='active' and assets.primary_ipv4 is not NULL and (servers.server_dedicated_tag like '%,%,%,%,%,%,%,6,%' or servers.server_dedicated_tag like '%,%,%,%,%,%,%,1,%' or servers.server_dedicated_cp=1 or servers.server_dedicated_cp=6) and assets.primary_ipv4 in ('".implode("','", array_keys($tocheck))."')", __LINE__, __FILE__);
	while ($db->next_record(MYSQL_ASSOC)) {
		$goodIps[] = $db->Record['primary_ipv4'];
		unset($tocheck[$db->Record['primary_ipv4']]);
	}
	$db->query("select services_field1, services_name from services where services_module='licenses'");
	$services = [];
	while ($db->next_record(MYSQL_ASSOC)) {
		$services[$db->Record['services_field1']] = $db->Record['services_name'];
	}
	/*
	$db->query("select license_ip from licenses left join services on services_id=license_type where services_category=1 and license_status='active' and license_ip in ('" . implode("','", array_keys($tocheck)) . "')", __LINE__, __FILE__);
	while ($db->next_record(MYSQL_ASSOC))
		unset($tocheck[$db->Record['license_ip']]);
	*/
	/*
	$dbVps->query("select vps_ip from vps, repeat_invoices where vps_status='active' and concat('CPanel for VPS ', vps.vps_id)=repeat_invoices.repeat_invoices_description and vps_ip in ('" . implode("','", array_keys($tocheck)) . "')");
	while ($dbVps->next_record(MYSQL_ASSOC))
		unset($tocheck[$dbVps->Record['vps_ip']]);
	*/
	foreach ($tocheck as $ipAddress => $license) {
		if (!isset($ipOutput[$license['ip']])) {
			$ipOutput[$license['ip']] = [];
		}
		$dbCms->query("select * from client_package, package_type where client_package.pack_id=package_type.pack_id and cp_comments like '%{$license['ip']}%' and pack_name like '%Cpanel%' and cp_status=2");
		if ($dbCms->num_rows() > 0) {
			$goodIps[] = $license['ip'];
		}
		if (!in_array($license['ip'], $goodIps)) {
			$db->query("select licenses.*, services_name, services_field1 from licenses left join services on services_id=license_type where license_ip='{$license['ip']}' and services_category={$type}");
			if ($db->num_rows() > 0) {
				while ($db->next_record(MYSQL_ASSOC)) {
					if ($db->Record['license_status'] == 'active' && $db->Record['services_field1'] == $license['package']) {
						$goodIps[] = $license['ip'];
					} elseif ($db->Record['license_status'] != 'active' && $db->Record['services_field1'] == $license['package']) {
						$ipOutput[$license['ip']][] = 'CPanelDirect License '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_license&id='.$db->Record['license_id']).'" target=_blank>'.$db->Record['license_id'].'</a>'.' Found but status is '.$db->Record['license_status'];
					// $db->query("update licenses set license_type=$license_type where license_id='{$db->Record['license_id']}'");
					} elseif ($db->Record['license_status'] == 'active' && $db->Record['services_field1'] != $license['package']) {
						$ipOutput[$license['ip']][] = 'CPanelDirect License '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_license&id='.$db->Record['license_id']).'" target=_blank>'.$db->Record['license_id'].'</a>'.' Found but type is '.str_replace('INTERSERVER-', '', $db->Record['services_name']).' instead of '.str_replace('INTERSERVER-', '', $services[$license['package']]);
					} else {
						$ipOutput[$license['ip']][] = 'CPanelDirect License '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_license&id='.$db->Record['license_id']).'" target=_blank>'.$db->Record['license_id'].'</a>'.' Found but status is '.$db->Record['license_status'].' and type is '.str_replace('INTERSERVER-', '', $db->Record['services_name']).' instead of '.str_replace('INTERSERVER-', '', $services[$license['package']]);
					}
				}
			}
		}
		if (!in_array($license['ip'], $goodIps)) {
			$dbVps->query("select * from vps left join repeat_invoices on concat('CPanel for VPS ', vps.vps_id) = repeat_invoices.repeat_invoices_description where vps_ip='{$license['ip']}'");
			if ($dbVps->num_rows() > 0) {
				while ($dbVps->next_record()) {
					$vps = $dbVps->Record;
					if ($vps['vps_status'] == 'active' && $vps['repeat_invoices_id'] != null) {
						$dbVps2->query(
							'select * from invoices where invoices_extra='.$vps['repeat_invoices_id']." and invoices_type=1 and invoices_paid=1 and invoices_date >= date_sub('".mysql_now()."', INTERVAL ".
							(1 + $vps['repeat_invoices_frequency']).' MONTH)'
						);
						if ($dbVps2->num_rows() > 0) {
							$goodIps[] = $license['ip'];
						} else {
							$ipOutput[$license['ip']][] = 'VPS '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_vps&id='.$vps['vps_id']).'" target=_blank>'.$vps['vps_id'].'</a>'.' Has Cpanel But Has not Paid In 2+ Months';
						}
					} elseif ($vps['vps_status'] == 'active' && $vps['repeat_invoices_id'] == null) {
						$ipOutput[$license['ip']][] = 'VPS '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_vps&id='.$vps['vps_id']).'" target=_blank>'.$vps['vps_id'].'</a>'.' Found but no CPanel';
					} elseif ($vps['vps_status'] != 'active' && $vps['repeat_invoices_id'] != null) {
						$ipOutput[$license['ip']][] = 'VPS '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_vps&id='.$vps['vps_id']).'" target=_blank>'.$vps['vps_id'].'</a>'.' Found with CPanel but VPS status is '.$vps['vps_status'];
					} else {
						$ipOutput[$license['ip']][] = 'VPS '.'<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.view_vps&id='.$vps['vps_id']).'" target=_blank>'.$vps['vps_id'].'</a>'.' Found But Status '.$vps['vps_status'].' and no CPanel';
					}
				}
			}
		}
		if (!in_array($license['ip'], $goodIps)) {
			$db->query("select vlans_comment from ips, vlans where ips_ip='$license[ip]' and ips_vlan=vlans_id");
			if ($db->num_rows() > 0) {
				$db->next_record(MYSQL_ASSOC);
				$server = str_replace(['append ', 'Append '], ['', ''], trim($db->Record['vlans_comment']));
				$db->query("select * from servers where server_hostname like '%$server%' order by server_status");
				if ($db->num_rows() > 0) {
					$db->next_record(MYSQL_ASSOC);
					$dedicatedTag = explode(',', $db->Record['server_dedicated_tag']);
					if ($db->Record['server_custid'] == 2304) {
						if ((count($dedicatedTag) > 8 && ($dedicatedTag[7] == 1 || $dedicatedTag[7] == 6)) || $db->Record['server_dedicated_cp'] == 1 || $db->Record['server_dedicated_cp'] == 6) {
							$goodIps[] = $license['ip'];
						} else {
							$ipOutput[$license['ip']][] = 'Used By '.$db->Record['server_hostname'];
						}
					} elseif ($db->Record['server_status'] == 'active') {
						if ((count($dedicatedTag) > 8 && ($dedicatedTag[7] == 1 || $dedicatedTag[7] == 6)) || $db->Record['server_dedicated_cp'] == 1 || $db->Record['server_dedicated_cp'] == 6) {
							$goodIps[] = $license['ip'];
						} else {
							$ipOutput[$license['ip']][] = 'Innertell Order '.'<a href="'.$GLOBALS['tf']->link('view_server_order', 'id='.$db->Record['id']).'">'.$db->Record['id'].'</a>'.' found but no CPanel';
						}
					} else {
						if ((count($dedicatedTag) > 8 && ($dedicatedTag[7] == 1 || $dedicatedTag[7] == 6)) || $db->Record['server_dedicated_cp'] == 1 || $db->Record['server_dedicated_cp'] == 6) {
							$ipOutput[$license['ip']][] = 'Innertell Order '.'<a href="'.$GLOBALS['tf']->link('view_server_order', 'id='.$db->Record['id']).'" target=_blank>'.$db->Record['id'].'</a>'.' found but status '.$db->Record['server_status'];
						} else {
							$ipOutput[$license['ip']][] = 'Innertell Order '.'<a href="'.$GLOBALS['tf']->link('view_server_order', 'id='.$db->Record['id']).'" target=_blank>'.$db->Record['id'].'</a>'.' found but status '.$db->Record['server_status'].' and no CPanel';
						}
					}
				} else {
					$ipOutput[$license['ip']][] = 'VLAN for '.$server.' found but no orders match';
				}
			}
		}
	}
	if ($outType == 'table') {
		add_output('<table border=1>');
	} elseif ($outType == 'tftable') {
		$table->set_title('Unbilled CPanel Licenses');
	} else {
		echo "Unbilled CPanel Licenses\n";
	}
	$errors = 0;
	foreach ($tocheck as $ipAddress => $license) {
		if (!in_array($ipAddress, $goodIps)) {
			$errors++;
			if ($outType == 'table') {
				add_output('<tr style="vertical-align: top;"><td>
				<a href="search.php?comments=no&search='.$ipAddress.'&expand=1" target=_blank>'.$ipAddress.'</a>
				(<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.deactivate_cpanel&ip='.$ipAddress).'" target=_blank>cancel</a>)</td>
				<td>'.$license['hostname'].'</td><td>'.str_replace(['INTERSERVER-', ' License'], ['', ''], $services[$license['package']]).'</td><td>'
				);
			} elseif ($outType == 'tftable') {
				$table->set_col_options('style="width: 210px;"');
				$table->add_field('<a href="search.php?comments=no&search='.$ipAddress.'&expand=1" target=_blank>'.$ipAddress.'</a> (<a href="'.$GLOBALS['tf']->link('index.php', 'choice=none.deactivate_cpanel&ip='.$ipAddress).'" target=_blank>cancel</a>)', 'r');
				$table->set_col_options('');
				//					$table->set_col_options('style="width: 225px;"');
				$table->add_field($license['hostname'], 'r');
				$table->set_col_options('style="min-width: 135px; max-width: 150px;"');
				$table->add_field(str_replace(['INTERSERVER-', ' License'], ['', ''], $services[$license['package']]), 'r');
				$table->set_col_options('style="min-width: 350px;"');
			} else {
				echo "$ipAddress	".$license['hostname'].'	'.str_replace(['INTERSERVER-', ' License'], ['', ''], $services[$license['package']]).'	';
			}
			if (count($ipOutput[$ipAddress]) > 0) {
				if ($outType == 'table') {
					add_output(implode('<br>', $ipOutput[$ipAddress]));
				} elseif ($outType == 'tftable') {
					$table->add_field(implode('<br>', $ipOutput[$ipAddress]), 'r');
				} else {
					echo strip_tags(implode(', ', $ipOutput[$ipAddress]));
				}
			} elseif ($outType == 'table') {
				add_output("I was unable to find this IP {$ipAddress} anywhere.");
			} elseif ($outType == 'tftable') {
				$table->add_field("I was unable to find this IP {$ipAddress} anywhere.", 'r');
			} else {
				echo "I was unable to find this IP {$ipAddress} anywhere.";
			}
			if ($outType == 'table') {
				add_output('</td></tr>');
			} elseif ($outType == 'tftable') {
				$table->add_row();
			} else {
				echo "\n";
			}
		}
	}
	if ($outType == 'table') {
		add_output('<tr><td colspan=4 align=center>'.$errors.'/'.count($licenses).' Licenses have matching problems</td></tr></table>');
		add_output('</body></html>');
	} elseif ($outType == 'tftable') {
		$table->set_colspan(4);
		$table->add_field($errors.'/'.count($licenses).' Licenses have matching problems');
		$table->add_row();
		add_output($table->get_table());
		add_output('</body></html>');
	} else {
		echo $errors.'/'.count($licenses)." Licenses have matching problems\n";
	}
	//echo $GLOBALS['output'];
}
