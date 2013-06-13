<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id$
 */
$agencyExtensionPath = t3lib_extMgm::extPath('agency');
return array(
	'tx_agency_statusreport' => $agencyExtensionPath . 'hooks/statusreport/class.tx_agency_statusReport.php',
	'tx_agency_captcha' => $agencyExtensionPath . '/hooks/captcha/class.tx_agency_captcha.php',
	'tx_agency_freecap' => $agencyExtensionPath . '/hooks/freecap/class.tx_agency_freecap.php',
	'tx_agency_hooks_cms' => $agencyExtensionPath . 'hooks/class.tx_agency_hooks_cms.php',
	'tx_agency_hookshandler' => $agencyExtensionPath . 'hooks/class.tx_agency_hooksHandler.php',
	'tx_agency' => $agencyExtensionPath . 'class.tx_agency.php',
	'tx_agency_pi_base' => $agencyExtensionPath . 'pi/class.tx_agency_pi_base.php',
	'tx_agency_control_main' => $agencyExtensionPath . 'control/class.tx_agency_control_main.php',
	'tx_agency_control' => $agencyExtensionPath . 'control/class.tx_agency_control.php',
	'tx_agency_setfixed' => $agencyExtensionPath . 'control/class.tx_agency_setfixed.php',
	'tx_agency_auth' => $agencyExtensionPath . 'lib/class.tx_agency_auth.php',
	'tx_agency_conf' => $agencyExtensionPath . 'lib/class.tx_agency_conf.php',
	'tx_agency_email' => $agencyExtensionPath . 'lib/class.tx_agency_email.php',
	'tx_agency_lang' => $agencyExtensionPath . 'lib/class.tx_agency_lang.php',
	'tx_agency_lib_tables' => $agencyExtensionPath . 'lib/class.tx_agency_lib_tables.php',
	'tx_agency_tca' => $agencyExtensionPath . 'lib/class.tx_agency_tca.php',
	'tx_agency_marker' => $agencyExtensionPath . 'marker/class.tx_agency_marker.php',
	'tx_agency_controldata' => $agencyExtensionPath . 'model/class.tx_agency_controldata.php',
	'tx_agency_data' => $agencyExtensionPath . 'model/class.tx_agency_data.php',
	'tx_agency_model_feusers' => $agencyExtensionPath . 'model/class.tx_agency_model_feusers.php',
	'tx_agency_model_setfixed' => $agencyExtensionPath . 'model/class.tx_agency_model_setfixed.php',
	'tx_agency_model_table_base' => $agencyExtensionPath . 'model/class.tx_agency_model_table_base.php',
	'tx_agency_storage_security' => $agencyExtensionPath . 'model/class.tx_agency_storage_security.php',
	'tx_agency_transmission_security' => $agencyExtensionPath . 'model/class.tx_agency_transmission_security.php',
	'tx_agency_url' => $agencyExtensionPath . 'model/class.tx_agency_url.php',
	'tx_agency_model_field_base' => $agencyExtensionPath . 'model/field/class.tx_agency_model_field_base.php',
	'tx_agency_model_field_usergroup' => $agencyExtensionPath . 'model/field/class.tx_agency_model_field_usergroup.php',
	'tx_agency_dmstatic' => $agencyExtensionPath . 'scripts/class.tx_agency_dmstatic.php',
	'tx_agency_select_dmcategories' => $agencyExtensionPath . 'scripts/class.tx_agency_select_dmcategories.php',
	'tx_agency_display' => $agencyExtensionPath . 'view/class.tx_agency_display.php',
);
unset($agencyExtensionPath);
?>