<?php
/**
 * Licensing Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Licenses
 */
/**
 * cpanel_ksplice_addon()
 *
 * @return void
 * @throws \Exception
 * @throws \SmartyException
 */
function cpanel_ksplice_addon()
{
    page_title('CPanel KSplice Addon');
    $settings = \get_module_settings('licenses');
    $db = get_module_db('licenses');
    $id = (int) $GLOBALS['tf']->variables->request['id'];
    $servicesCpanelType = get_service_define('CPANEL');
    if ($GLOBALS['tf']->ima == 'admin') {
        $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_type in (select services_id from services where services_type={$servicesCpanelType})", __LINE__, __FILE__);
    } else {
        $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_type in (select services_id from services where services_type={$servicesCpanelType}) and {$settings['PREFIX']}_custid='".get_custid($GLOBALS['tf']->session->account_id, 'licenses')."'", __LINE__, __FILE__);
    }
    if ($db->num_rows() > 0) {
        $db->next_record(MYSQL_ASSOC);
        $serviceInfo = $db->Record;
        if ($serviceInfo[$settings['PREFIX'].'_status'] != 'active') {
            add_output('Only Active '.$settings['TBLNAME']);
            return;
        }
        if (!isset($GLOBALS['tf']->variables->request['submitbutton'])) {
            $table = new \TFTable();
            $table->add_hidden('choice', 'none.cpanel_ksplice_addon');
            $table->add_hidden('id', $id);
            $table->set_title('KSplice');
            $table->add_field('Before activating KSplice it must first be installed on your server.<br>
		<br>
		Script for installing KSplice at<br>
		<a href="http://wiki.cpaneldirect.net/wiki/index.php?title=Ksplice_installer_script">http://wiki.cpaneldirect.net/wiki/index.php?title=Ksplice_installer_script</a><br>
		<br>
		 or you can just run<br>
		 <br>
		 rsync -a rsync://mirror.trouble-free.net /admin /admin && /admin/kspliceinstall<br>
		 <br>
		 manual install instructions at <a href="http://www.ksplice.com/uptrack/manual-installation">http://www.ksplice.com/uptrack/manual-installation</a><br>
		 <br>
		 Use Key 3e6f6fb143b0088b2b8ad6f714d3b4a340d465f885cfa868ef4e46a77d1bb1ee<br>
		 <br>
		 Once Installed Click continue to activate your KSplice license.', 'l');
            $table->add_row();
            $table->add_field($table->make_submit('Activate My License'));
            $table->add_row();
            add_output($table->get_table());
        } else {
            $serviceExtra = @myadmin_unstringify($serviceInfo['license_extra']);
            if ($serviceExtra === false) {
                $serviceExtra = [];
            }
            $ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
            $uuid = $ksplice->ipToUuid($db->Record[$settings['PREFIX'].'_ip']);
            myadmin_log('licenses', 'info', "Got UUID $uuid from IP ".$db->Record[$settings['PREFIX'].'_ip'], __LINE__, __FILE__);
            $ksplice->authorizeMachine($uuid, true);
            myadmin_log('licenses', 'info', 'Response: '.$ksplice->responseRaw, __LINE__, __FILE__);
            myadmin_log('licenses', 'info', 'Response: '.json_encode($ksplice->response), __LINE__, __FILE__);
            $serviceExtra['ksplice_uuid'] = $uuid;
            $serviceExtra['ksplice'] = 1;
            $db->query("update licenses set license_extra='".$db->real_escape(myadmin_stringify($serviceExtra))."' where license_id=$id", __LINE__, __FILE__);
            add_output('KSplice License activated');
        }
    }
}
