<?php

namespace MyAdmin\Licenses\Cpanel;

//use Detain\Cpanel\Cpanel;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package MyAdmin\Licenses\Cpanel
 */
class Plugin
{
	public static $name = 'Cpanel Licensing';
	public static $description = 'Allows selling of Cpanel Server and VPS License Types.  More info at https://cpanel.com/';
	public static $help = 'cPanel monthly license.  WHM/cPanel allows you to administer individual accounts, reseller accounts &amp; performing basic system and control panel maintenance via a secure interface. cPanel Control Panel (Client Interface) cPanel is designed for the end users of your system and allows them to control everything from adding / removing email accounts to administering MySQL databases.';
	public static $module = 'licenses';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivateIp'],
			self::$module.'.change_ip' => [__CLASS__, 'getChangeIp'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			'ui.menu' => [__CLASS__, 'getMenu']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('CPANEL')) {
			myadmin_log(self::$module, 'info', 'Cpanel Activation', __LINE__, __FILE__);
			function_requirements('activate_cpanel');
			activate_cpanel($serviceClass->getIp(), $event['field1']);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('CPANEL')) {
			myadmin_log(self::$module, 'info', 'CPanel Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_cpanel');
			deactivate_cpanel($serviceClass->getIp());
			$serviceExtra = @myadmin_unstringify($serviceClass->getExtra());
			if ($serviceExtra !== false && isset($serviceExtra['ksplice']) && $serviceExtra['ksplice'] == 1 && isset($serviceExtra['ksplice_uuid']) && $serviceExtra['ksplice_uuid'] != '') {
				function_requirements('deactivate_ksplice');
				deactivate_ksplice((is_uuid($serviceExtra['ksplice_uuid']) ? $serviceExtra['ksplice_uuid'] : $serviceClass->getIp()));
			}
			if ($serviceExtra !== false && isset($serviceExtra['kcare']) && $serviceExtra['kcare'] == 1) {
				function_requirements('deactivate_kcare');
				deactivate_kcare($serviceClass->getIp());
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivateIp(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('CPANEL')) {
			myadmin_log(self::$module, 'info', 'CPanel Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_cpanel');
			deactivate_cpanel($serviceClass->getIp());
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event)
	{
		if ($event['category'] == get_service_define('CPANEL')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			function_requirements('deactivate_cpanel');
			function_requirements('activate_cpanel');
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			if (deactivate_cpanel($serviceClass->getIp()) == true) {
				activate_cpanel($event['newip'], $event['field1']);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			} else {
				$event['status'] = 'error';
				$event['status_text'] = 'Error occurred during deactivation.';
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.unbilled_cpanel', '/images/myadmin/payment-history.png', __('Unbilled CPanel'));
			$menu->add_link(self::$module.'api', 'choice=none.cpanel_list', '/images/myadmin/list.png', __('List all CPanel Licenses'));
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
		$loader->add_requirement('activate_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_page_requirement('deactivate_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('verify_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('get_cpanel_license_data_by_ip', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_requirement('get_cpanel_licenses', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_page_requirement('cpanel_list', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
		$loader->add_page_requirement('unbilled_cpanel', '/../vendor/detain/myadmin-cpanel-licensing/src/unbilled_cpanel.php');
		$loader->add_page_requirement('unbilled_cpanel_old', '/../vendor/detain/myadmin-cpanel-licensing/src/unbilled_cpanel_old.php');
		$loader->add_page_requirement('cpanel_ksplice_addon', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel_ksplice_addon.php');
		$loader->add_page_requirement('cpanel_kcare_addon', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel_kcare_addon.php');
		$loader->add_requirement('class.Cpanel', '/../vendor/detain/myadmin-cpanel-licensing/Cpanel.php', '\\Detain\\Cpanel\\');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
		$settings->add_text_setting(self::$module, __('CPanel'), 'cpanel_licensing_username', __('Cpanel Licensing Username'), __('Cpanel Licensing Username'), $settings->get_setting('CPANEL_LICENSING_USERNAME'));
		$settings->add_text_setting(self::$module, __('CPanel'), 'cpanel_licensing_password', __('Cpanel Licensing Password'), __('Cpanel Licensing Password'), $settings->get_setting('CPANEL_LICENSING_PASSWORD'));
		$settings->add_text_setting(self::$module, __('CPanel'), 'cpanel_licensing_group', __('Cpanel Licensing Group'), __('Cpanel Licensing Group'), $settings->get_setting('CPANEL_LICENSING_GROUP'));
		$settings->add_dropdown_setting(self::$module, __('CPanel'), 'outofstock_licenses_cpanel', __('Out Of Stock CPanel Licenses'), __('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_LICENSES_CPANEL'), ['0', '1'], ['No', 'Yes']);
	}
}
