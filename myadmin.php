<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_cpanel define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Cpanel Licensing',
	'description' => 'Allows selling of Cpanel Server and VPS License Types.  More info at https://cpanel.com/',
	'help' => 'cPanel monthly license.  WHM/cPanel allows you to administer individual accounts, reseller accounts &amp; performing basic system and control panel maintenance via a secure interface. cPanel Control Panel (Client Interface) cPanel is designed for the end users of your system and allows them to control everything from adding / removing email accounts to administering MySQL databases.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-cpanel-licensing',
	'repo' => 'https://github.com/detain/myadmin-cpanel-licensing',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		'licenses.settings' => ['MyAdmin\Licenses\Cpanel\Plugin', 'Settings'],
		'licenses.activate' => ['MyAdmin\Licenses\Cpanel\Plugin', 'Activate'],
		'licenses.deactivate' => ['MyAdmin\Licenses\Cpanel\Plugin', 'Deactivate'],
		'licenses.change_ip' => ['MyAdmin\Licenses\Cpanel\Plugin', 'ChangeIp'],
		'function.requirements' => ['MyAdmin\Licenses\Cpanel\Plugin', 'Requirements'],
		'ui.menu' => ['MyAdmin\Licenses\Cpanel\Plugin', 'Menu']
	],
];
