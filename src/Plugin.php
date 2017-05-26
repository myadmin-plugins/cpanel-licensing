<?php

namespace Detain\MyAdminCpanel;

use Detain\Cpanel\Cpanel;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_CPANEL) {
			myadmin_log('licenses', 'info', 'Cpanel Activation', __LINE__, __FILE__);
			function_requirements('activate_cpanel');
			activate_cpanel($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$cpanel = new Cpanel(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $cpanel->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Cpanel editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_cpanel', 'icons/database_warning_48.png', 'ReUsable Cpanel Licenses');
			$menu->add_link($module, 'choice=none.cpanel_list', 'icons/database_warning_48.png', 'Cpanel Licenses Breakdown');
			$menu->add_link('licensesapi', 'choice=none.cpanel_licenses_list', 'whm/createacct.gif', 'List all Cpanel Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('crud_cpanel_list', '/../vendor/detain/crud/src/crud/crud_cpanel_list.php');
		$loader->add_requirement('crud_reusable_cpanel', '/../vendor/detain/crud/src/crud/crud_reusable_cpanel.php');
		$loader->add_requirement('get_cpanel_licenses', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('get_cpanel_list', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('cpanel_licenses_list', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('cpanel_list', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('get_available_cpanel', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('activate_cpanel', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('get_reusable_cpanel', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('reusable_cpanel', '/licenses/cpanel.functions.inc.php');
		$loader->add_requirement('class.cpanel', '/../vendor/detain/cpanel/class.cpanel.inc.php');
		$loader->add_requirement('vps_add_cpanel', '/vps/addons/vps_add_cpanel.php');
	}

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('apisettings', 'cpanel_licensing_username', 'Cpanel Licensing Username:', 'Cpanel Licensing Username', $settings->get_setting('CPANEL_LICENSING_USERNAME'));
		$settings->add_text_setting('apisettings', 'cpanel_licensing_password', 'Cpanel Licensing Password:', 'Cpanel Licensing Password', $settings->get_setting('CPANEL_LICENSING_PASSWORD'));
		$settings->add_text_setting('apisettings', 'cpanel_licensing_group', 'Cpanel Licensing Group:', 'Cpanel Licensing Group', $settings->get_setting('CPANEL_LICENSING_GROUP'));
		$settings->add_dropdown_setting('stock', 'outofstock_licenses_cpanel', 'Out Of Stock CPanel Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_CPANEL'), array('0', '1'), array('No', 'Yes', ));
	}

}
