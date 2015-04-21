<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "agency".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Agency Registration',
	'description' => 'An improved variant of Kasper Skårhøj\'s Front End User Admin extension.',
	'category' => 'plugin',
	'shy' => 0,
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'dependencies' => '',
	'internal' => 0,
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'fe_users',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.2.4',
	'_md5_values_when_last_written' => 'a:59:{s:9:"ChangeLog";s:4:"f198";s:19:"class.tx_agency.php";s:4:"1adf";s:16:"contributors.txt";s:4:"9971";s:16:"ext_autoload.php";s:4:"affa";s:21:"ext_conf_template.txt";s:4:"5c9c";s:12:"ext_icon.gif";s:4:"cfa0";s:17:"ext_localconf.php";s:4:"8c47";s:14:"ext_tables.php";s:4:"0949";s:14:"ext_tables.sql";s:4:"e706";s:13:"locallang.xml";s:4:"9cf1";s:16:"locallang_db.xml";s:4:"948b";s:20:"phpDebugErrorLog.txt";s:4:"12fd";s:7:"tca.php";s:4:"a29c";s:35:"control/class.tx_agency_control.php";s:4:"a87d";s:40:"control/class.tx_agency_control_main.php";s:4:"fb9b";s:36:"control/class.tx_agency_setfixed.php";s:4:"d364";s:14:"doc/manual.sxw";s:4:"e43d";s:35:"hooks/class.tx_agency_hooks_cms.php";s:4:"be6f";s:38:"hooks/class.tx_agency_hooksHandler.php";s:4:"de59";s:41:"hooks/captcha/class.tx_agency_captcha.php";s:4:"12ce";s:41:"hooks/freecap/class.tx_agency_freecap.php";s:4:"9956";s:51:"hooks/statusreport/class.tx_agency_statusReport.php";s:4:"588e";s:36:"hooks/statusreport/ext_localconf.php";s:4:"f3c1";s:32:"hooks/statusreport/locallang.xlf";s:4:"cf3a";s:28:"lib/class.tx_agency_auth.php";s:4:"2b40";s:28:"lib/class.tx_agency_conf.php";s:4:"cded";s:29:"lib/class.tx_agency_email.php";s:4:"9934";s:28:"lib/class.tx_agency_lang.php";s:4:"9fc4";s:34:"lib/class.tx_agency_lib_tables.php";s:4:"8917";s:27:"lib/class.tx_agency_tca.php";s:4:"d773";s:33:"marker/class.tx_agency_marker.php";s:4:"dc49";s:37:"model/class.tx_agency_controldata.php";s:4:"54c6";s:30:"model/class.tx_agency_data.php";s:4:"e3be";s:39:"model/class.tx_agency_model_feusers.php";s:4:"bd0a";s:40:"model/class.tx_agency_model_setfixed.php";s:4:"b564";s:42:"model/class.tx_agency_model_table_base.php";s:4:"d056";s:42:"model/class.tx_agency_storage_security.php";s:4:"a60c";s:47:"model/class.tx_agency_transmission_security.php";s:4:"cc7d";s:29:"model/class.tx_agency_url.php";s:4:"84aa";s:48:"model/field/class.tx_agency_model_field_base.php";s:4:"7bc3";s:53:"model/field/class.tx_agency_model_field_usergroup.php";s:4:"d06f";s:30:"pi/class.tx_agency_pi_base.php";s:4:"ad68";s:21:"pi/flexform_ds_pi.xml";s:4:"2a89";s:16:"pi/locallang.xml";s:4:"217a";s:28:"res/icons/fe/icon_delete.gif";s:4:"f914";s:30:"res/icons/fe/internal_link.gif";s:4:"12b9";s:41:"res/icons/fe/internal_link_new_window.gif";s:4:"402a";s:36:"scripts/class.tx_agency_dmstatic.php";s:4:"492f";s:47:"scripts/class.tx_agency_select_dmcategories.php";s:4:"f7ff";s:28:"scripts/jsfunc.updateform.js";s:4:"4fc9";s:18:"scripts/rsaauth.js";s:4:"ec71";s:20:"static/constants.txt";s:4:"2f10";s:16:"static/setup.txt";s:4:"c6af";s:32:"template/tx_agency_css_tmpl.html";s:4:"f74e";s:37:"template/tx_agency_htmlmail_xhtml.css";s:4:"4199";s:30:"template/tx_agency_members.xml";s:4:"4e42";s:29:"template/tx_agency_sample.txt";s:4:"d407";s:28:"template/tx_agency_terms.txt";s:4:"a20c";s:32:"view/class.tx_agency_display.php";s:4:"f253";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-5.6.99',
			'typo3' => '4.5.0-6.2.99',
			'div2007' => '1.1.5-0.0.0',
		),
		'conflicts' => array(
			'sr_feuser_register' => '',
		),
		'suggests' => array(
			'felogin' => '',
			'rsaauth' => '',
			'saltedpasswords' => '',
			'agency_tt_address' => '0.0.2-0.5.0',
			'static_info_tables' => '2.3.1-6.2.99',
		),
	),
);

?>