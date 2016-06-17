<?php
/*
 * Register necessary class names with autoloader
 *
 */

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$extensionPath = call_user_func($emClass . '::extPath', 'agency');

return array(
	'tx_agency_statusreport' => $extensionPath . 'hooks/statusreport/class.tx_agency_statusReport.php',
	'tx_agency_captcha' => $extensionPath . '/hooks/captcha/class.tx_agency_captcha.php',
	'tx_agency_freecap' => $extensionPath . '/hooks/freecap/class.tx_agency_freecap.php',
	'tx_agency_hooks_cms' => $extensionPath . 'hooks/class.tx_agency_hooks_cms.php',
	'tx_agency_hookshandler' => $extensionPath . 'hooks/class.tx_agency_hooksHandler.php',
	'tx_agency_feusergroup_scheduler' => $extensionPath . 'hooks/class.tx_agency_feusergroup_scheduler.php',
	'tx_agency' => $extensionPath . 'class.tx_agency.php',
	'tx_agency_pi_base' => $extensionPath . 'pi/class.tx_agency_pi_base.php',
	'tx_agency_control_main' => $extensionPath . 'control/class.tx_agency_control_main.php',
	'tx_agency_control' => $extensionPath . 'control/class.tx_agency_control.php',
	'tx_agency_setfixed' => $extensionPath . 'control/class.tx_agency_setfixed.php',
	'tx_agency_auth' => $extensionPath . 'lib/class.tx_agency_auth.php',
	'tx_agency_conf' => $extensionPath . 'lib/class.tx_agency_conf.php',
	'tx_agency_email' => $extensionPath . 'lib/class.tx_agency_email.php',
	'tx_agency_lang' => $extensionPath . 'lib/class.tx_agency_lang.php',
	'tx_agency_lib_tables' => $extensionPath . 'lib/class.tx_agency_lib_tables.php',
	'tx_agency_tca' => $extensionPath . 'lib/class.tx_agency_tca.php',
	'tx_agency_marker' => $extensionPath . 'marker/class.tx_agency_marker.php',
	'tx_agency_controldata' => $extensionPath . 'model/class.tx_agency_controldata.php',
	'tx_agency_data' => $extensionPath . 'model/class.tx_agency_data.php',
	'tx_agency_model_feusers' => $extensionPath . 'model/class.tx_agency_model_feusers.php',
	'tx_agency_model_setfixed' => $extensionPath . 'model/class.tx_agency_model_setfixed.php',
	'tx_agency_model_table_base' => $extensionPath . 'model/class.tx_agency_model_table_base.php',
	'tx_agency_storage_security' => $extensionPath . 'model/class.tx_agency_storage_security.php',
	'tx_agency_transmission_security' => $extensionPath . 'model/class.tx_agency_transmission_security.php',
	'tx_agency_url' => $extensionPath . 'model/class.tx_agency_url.php',
	'tx_agency_model_field_base' => $extensionPath . 'model/field/class.tx_agency_model_field_base.php',
	'tx_agency_model_field_usergroup' => $extensionPath . 'model/field/class.tx_agency_model_field_usergroup.php',
	'tx_agency_display' => $extensionPath . 'view/class.tx_agency_display.php',
	'JambageCom\\Agency\\View\\CreateView' => $extensionPath . 'Classes/View/CreateView.php',
);
