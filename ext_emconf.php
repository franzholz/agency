<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "agency".
 *
 * Auto generated 07-10-2015 12:05
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
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
	'version' => '0.3.0',
	'_md5_values_when_last_written' => 'a:58:{s:9:"ChangeLog";s:4:"434b";s:19:"class.tx_agency.php";s:4:"7b76";s:16:"contributors.txt";s:4:"9971";s:16:"ext_autoload.php";s:4:"facf";s:21:"ext_conf_template.txt";s:4:"1e67";s:12:"ext_icon.gif";s:4:"ff39";s:17:"ext_localconf.php";s:4:"8bb0";s:14:"ext_tables.php";s:4:"58d3";s:14:"ext_tables.sql";s:4:"737f";s:31:"icon_tx_directmail_category.gif";s:4:"9398";s:13:"locallang.xml";s:4:"8f1b";s:16:"locallang_db.xml";s:4:"be08";s:7:"tca.php";s:4:"a29c";s:35:"control/class.tx_agency_control.php";s:4:"742b";s:40:"control/class.tx_agency_control_main.php";s:4:"f1ae";s:36:"control/class.tx_agency_setfixed.php";s:4:"6b22";s:14:"doc/manual.sxw";s:4:"ec13";s:47:"hooks/class.tx_agency_feusergroup_scheduler.php";s:4:"f72f";s:35:"hooks/class.tx_agency_hooks_cms.php";s:4:"be6f";s:38:"hooks/class.tx_agency_hooksHandler.php";s:4:"62fe";s:41:"hooks/captcha/class.tx_agency_captcha.php";s:4:"fc6d";s:41:"hooks/freecap/class.tx_agency_freecap.php";s:4:"19a1";s:51:"hooks/statusreport/class.tx_agency_statusReport.php";s:4:"bd96";s:36:"hooks/statusreport/ext_localconf.php";s:4:"f3c1";s:32:"hooks/statusreport/locallang.xlf";s:4:"cf3a";s:28:"lib/class.tx_agency_auth.php";s:4:"a757";s:28:"lib/class.tx_agency_conf.php";s:4:"e3b9";s:29:"lib/class.tx_agency_email.php";s:4:"e2a1";s:28:"lib/class.tx_agency_lang.php";s:4:"95ad";s:34:"lib/class.tx_agency_lib_tables.php";s:4:"570a";s:27:"lib/class.tx_agency_tca.php";s:4:"e943";s:33:"marker/class.tx_agency_marker.php";s:4:"3494";s:37:"model/class.tx_agency_controldata.php";s:4:"c005";s:30:"model/class.tx_agency_data.php";s:4:"f796";s:39:"model/class.tx_agency_model_feusers.php";s:4:"bd0a";s:40:"model/class.tx_agency_model_setfixed.php";s:4:"b564";s:42:"model/class.tx_agency_model_table_base.php";s:4:"d056";s:42:"model/class.tx_agency_storage_security.php";s:4:"f61c";s:47:"model/class.tx_agency_transmission_security.php";s:4:"2dd9";s:29:"model/class.tx_agency_url.php";s:4:"84aa";s:48:"model/field/class.tx_agency_model_field_base.php";s:4:"7bc3";s:53:"model/field/class.tx_agency_model_field_usergroup.php";s:4:"97ed";s:30:"pi/class.tx_agency_pi_base.php";s:4:"0ad7";s:21:"pi/flexform_ds_pi.xml";s:4:"f97c";s:16:"pi/locallang.xml";s:4:"3462";s:28:"res/icons/fe/icon_delete.gif";s:4:"f914";s:30:"res/icons/fe/internal_link.gif";s:4:"12b9";s:41:"res/icons/fe/internal_link_new_window.gif";s:4:"402a";s:28:"scripts/jsfunc.updateform.js";s:4:"4fc9";s:18:"scripts/rsaauth.js";s:4:"530f";s:20:"static/constants.txt";s:4:"0261";s:16:"static/setup.txt";s:4:"41d4";s:32:"template/tx_agency_css_tmpl.html";s:4:"ad30";s:37:"template/tx_agency_htmlmail_xhtml.css";s:4:"c42f";s:30:"template/tx_agency_members.xml";s:4:"4e42";s:29:"template/tx_agency_sample.txt";s:4:"d407";s:28:"template/tx_agency_terms.txt";s:4:"a20c";s:32:"view/class.tx_agency_display.php";s:4:"d7b8";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-7.99.99',
			'typo3' => '4.5.0-7.99.99',
			'div2007' => '1.6.10-0.0.0',
		),
		'conflicts' => array(
			'sr_feuser_register' => '',
		),
		'suggests' => array(
			'felogin' => '',
			'rsaauth' => '',
			'saltedpasswords' => '',
			'static_info_tables' => '2.3.1-6.99.99',
		),
	),
	'dependencies' => 'div2007',
	'suggests' => array(
	),
	'conflicts' => 'sr_feuser_register',
);

