<?php
/**
 * License Related Functionality
 * Last Changed: $LastChangedDate: 2017-05-26 04:46:46 -0400 (Fri, 26 May 2017) $
 * @author detain
 * @version $Revision: 24805 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * activate_cpanel()
 * activates a cpanel license
 *
 * @param string $ip ip address to activate
 * @param integer $package the package type to activate
 * @return string the response and command sent to activate cpanel
 */
function activate_cpanel($ip, $package) {
	$module = 'licenses';
	$package = (int)$package;
	myadmin_log('licenses', 'info', "activate_cpanel($ip, $package) Called", __LINE__, __FILE__);
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	//$groups = $cpl->fetchGroups();
	//myadmin_log('licenses', 'info', json_encode($groups));
	//$groupid = array_search(CPANEL_LICENSING_GROUP, $groups['groups']);
	//myadmin_log('licenses', 'info', $groupid, __LINE__, __FILE__);
	$request = array(
		'ip' => $ip,
		'groupid' => CPANEL_LICENSING_GROUP,
		'packageid' => $package,
		'force' => 1,
		'reactivateok' => 1,
	);
	$response = $cpl->activateLicense($request);
	request_log($module, convert_custid($GLOBALS['tf']->session->account_id, $module), __FUNCTION__, 'cpanel', 'activateLicense', $request, $response);
	myadmin_log('licenses', 'info', json_encode($response['attr']), __LINE__, __FILE__);
	return $response['attr'];
}

/**
 * deactivate_cpanel()
 *
 * @param string|false $ip the ip to deactivate, or false to use the request variable ip
 * @return bool true if successfull, flase otherwise
 */
function deactivate_cpanel($ip = false) {
	if ($GLOBALS['tf']->ima == 'admin' && $ip === false && isset($GLOBALS['tf']->variables->request['ip'])) {
		$ip = $GLOBALS['tf']->variables->request['ip'];
	}
	if (trim($ip) == '')
		return true;
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	$request = array('ip' => $ip);
	$response = $cpl->fetchLicenseId($request);
	request_log('licenses', false, __FUNCTION__, 'cpanel', 'fetchLicenseId', $request, $response);
	if (isset($response['licenseid']) && isset($response['licenseid']['value'])) {
		$liscid = $response['licenseid']['value'];
		$request = array('liscid' => $liscid);
		$response = $cpl->expireLicense($request);
		request_log('licenses', false, __FUNCTION__, 'cpanel', 'expireLicense', $request, $response);
		myadmin_log('licenses', 'info', "deactivate_cpanel({$ip}) returned " . json_encode($response['attr']), __LINE__, __FILE__);
		if ($response['attr']['reason'] == 'OK')
			return true;
		else
			return false;
	}
	return false;
}

/**
 * verify_cpanel()
 * verifies a cpanel license with cpanel
 *
 * @param mixed $ip ip address
 * @return string the response from cpanel or 'Not Active' if no response
 */
function verify_cpanel($ip) {
	if (!valid_ip($ip, false))
		return false;
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	$request = array('ip' => $ip);
	$status = $cpl->fetchLicenseRaw($request);
	request_log('licenses', false, __FUNCTION__, 'cpanel', 'expireLicense', $request, $status);
	if ($status['attr']['status'] == 1) {
		$response = 'active';
	} else {
		$response = 'not active';
	}
	/*
	$response = trim(`curl --connect-timeout 60 --max-time 60 -L 'http://verify.cpanel.net/index.cgi?ip=$ip' 2>/dev/null | grep -A18 tblheader | cut -d\> -f3 | cut -d\< -f1 | grep -v "^$" | tail -n 1`);
	if ($response == '}') {
	$response = 'Not Active';
	}
	*/
	return $response;
}

/**
 * get_cpanel_license_data_by_ip()
 * gets cpanel license data
 *
 * Sample output
 * [ip] => 209.67.63.2
 * [hostname] => neo.internetwebserver.net
 * [valid] => 1
 * [os] => Linux
 * [expiredon] =>
 * [groupid] => 30
 * [company] => Interserver, Inc.
 * [licenseid] => 1039954
 * [adddate] => 1251993592
 * [expirereason] =>
 * [distro] => centos enterprise 5.3
 * [version] => 11.24.5-RELEASE_38506
 * [packageid] => 560
 * [reason] => OK
 * [envtype] => virtuozzo
 * [osver] => 2.6.18-128.2.1.el5.028stab064.4.aviPAE
 * [updateexpiretime] => 0
 *
 * @param string $ip ip address
 * @return array the array of cpanel data
 */
function get_cpanel_license_data_by_ip($ip) {
	if (!valid_ip($ip, false))
		return false;
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	$request = array('ip' => $ip);
	$status = $cpl->fetchLicenseRaw($request);
	request_log('licenses', false, __FUNCTION__, 'cpanel', 'fetchLicenseRaw', $request, $status);
	if (!isset($status['license'])) {
		return false;
	}
	$data = $status['license'];
	return $data;
}

/**
 * @return array|mixed
 */
function get_cpanel_licenses() {
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	$status = $cpl->fetchLicenses();
	request_log('licenses', false, __FUNCTION__, 'cpanel', 'fetchLicenses', '', $status);
	return $status;
	//return $status['licenses'];
}
