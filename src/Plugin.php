<?php

namespace MyAdmin\Licenses\Cpanel;

//use Detain\Cpanel\Cpanel;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Cpanel Licensing';
	public static $description = 'Allows selling of Cpanel Server and VPS License Types.  More info at https://cpanel.com/';
	public static $help = 'cPanel monthly license.  WHM/cPanel allows you to administer individual accounts, reseller accounts &amp; performing basic system and control panel maintenance via a secure interface. cPanel Control Panel (Client Interface) cPanel is designed for the end users of your system and allows them to control everything from adding / removing email accounts to administering MySQL databases.';
	public static $module = 'licenses';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.change_ip' => [__CLASS__, 'getChangeIp'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_CPANEL) {
			myadmin_log(self::$module, 'info', 'Cpanel Activation', __LINE__, __FILE__);
			function_requirements('activate_cpanel');
			activate_cpanel($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function getDeactivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_CPANEL) {
			myadmin_log(self::$module, 'info', 'CPanel Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_cpanel');
			deactivate_cpanel($license->get_ip());
			$serviceExtra = @myadmin_unstringify($license->get_extra());
			if ($serviceExtra !== FALSE && isset($serviceExtra['ksplice']) && $serviceExtra['ksplice'] == 1 && isset($serviceExtra['ksplice_uuid']) && $serviceExtra['ksplice_uuid'] != '') {
				function_requirements('deactivate_ksplice');
				deactivate_ksplice((is_uuid($serviceExtra['ksplice_uuid']) ? $serviceExtra['ksplice_uuid'] : $license->get_ip()));
			}
			if ($serviceExtra !== FALSE && isset($serviceExtra['kcare']) && $serviceExtra['kcare'] == 1) {
				function_requirements('deactivate_kcare');
				deactivate_kcare($license->get_ip());
			}
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_CPANEL) {
			$license = $event->getSubject();
			$settings = get_module_settings(self::$module);
			function_requirements('deactivate_cpanel');
			function_requirements('activate_cpanel');
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			if (deactivate_cpanel($license->get_ip()) == TRUE) {
				activate_cpanel($event['newip'], $event['field1']);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			} else {
				$event['status'] = 'error';
				$event['status_text'] = 'Error occurred during deactivation.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = self::$module;
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.unbilled_cpanel', 'icons/database_warning_48.png', 'Unbilled CPanel');
			$menu->add_link(self::$module.'api', 'choice=none.cpanel_list', 'whm/createacct.gif', 'List all CPanel Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('activate_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('deactivate_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('verify_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('get_cpanel_license_data_by_ip', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('get_cpanel_licenses', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('cpanel_list', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('unbilled_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/unbilled_cpanel.php');
		$loader->add_requirement('unbilled_cpanel_old', '/../vendor/detain/myadmin-cpanel-licensing/src/unbilled_cpanel_old.php');
		$loader->add_requirement('cpanel_ksplice_addon', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel_ksplice_addon.php');
		$loader->add_requirement('cpanel_kcare_addon', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel_kcare_addon.php');
		$loader->add_requirement('class.Cpanel', '/../vendor/detain/cpanel-licensing/Cpanel.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'CPanel', 'cpanel_licensing_username', 'Cpanel Licensing Username:', 'Cpanel Licensing Username', $settings->get_setting('CPANEL_LICENSING_USERNAME'));
		$settings->add_text_setting(self::$module, 'CPanel', 'cpanel_licensing_password', 'Cpanel Licensing Password:', 'Cpanel Licensing Password', $settings->get_setting('CPANEL_LICENSING_PASSWORD'));
		$settings->add_text_setting(self::$module, 'CPanel', 'cpanel_licensing_group', 'Cpanel Licensing Group:', 'Cpanel Licensing Group', $settings->get_setting('CPANEL_LICENSING_GROUP'));
		$settings->add_dropdown_setting(self::$module, 'CPanel', 'outofstock_licenses_cpanel', 'Out Of Stock CPanel Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_CPANEL'), array('0', '1'), array('No', 'Yes',));
	}

}
