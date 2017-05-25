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
	'type' => 'licenses',
	'hooks' => [
		'function.requirements' => ['Detain\MyAdminCpanel\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminCpanel\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminCpanel\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminCpanel\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminCpanel\Plugin', 'Menu']
	],
];
