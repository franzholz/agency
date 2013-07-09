<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (!defined ('AGENCY_EXT')) {
	define('AGENCY_EXT', $_EXTKEY);
}

if (!defined ('PATH_BE_AGENCY')) {
	define('PATH_BE_AGENCY', t3lib_extMgm::extPath($_EXTKEY));
}

if (!defined ('PATH_BE_AGENCY_REL')) {
	define('PATH_BE_AGENCY_REL', t3lib_extMgm::extRelPath($_EXTKEY));
}

if (!defined ('PATH_FE_AGENCY_REL')) {
	define('PATH_FE_AGENCY_REL', t3lib_extMgm::siteRelPath($_EXTKEY));
}

if (!defined(STATIC_INFO_TABLES_EXT)) {
	define('STATIC_INFO_TABLES_EXT', 'static_info_tables');
}

	// Add Status Report
$typoVersion =
	class_exists('TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility') ?
		\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) :
		t3lib_div::int_from_ver(TYPO3_version);

if ($typo3Version >= 4006000) {
	require_once(PATH_BE_AGENCY . 'hooks/statusreport/ext_localconf.php');
}
unset($typo3Version);

t3lib_extMgm::addPItoST43($_EXTKEY, 'class.tx_agency.php', '', 'list_type', 0);

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['uploadfolder'] = $_EXTCONF['uploadFolder'] ? $_EXTCONF['uploadFolder'] : 'uploads/tx_agency';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['imageMaxSize'] = $_EXTCONF['imageMaxSize'] ? $_EXTCONF['imageMaxSize'] : 250;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['imageTypes'] = $_EXTCONF['imageTypes'] ? $_EXTCONF['imageTypes'] : 'png,jpeg,jpg,gif,tif,tiff';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['enableDirectMail'] = $_EXTCONF['enableDirectMail'] ? $_EXTCONF['enableDirectMail'] : 0;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['forceGender'] = $_EXTCONF['forceGender'] ? $_EXTCONF['forceGender'] : 0;


	/* Example of configuration of hooks */
/*
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['confirmRegistrationClass'][] = 'EXT:agency/hooks/class.tx_agency_hooksHandler.php:&tx_agency_hooksHandler';
*/

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['registrationProcess'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_agency_hooksHandler.php:&tx_agency_hooksHandler';

	// Save extension version and constraints
require_once(t3lib_extMgm::extPath($_EXTKEY) . 'ext_emconf.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['version'] = $EM_CONF[$_EXTKEY]['version'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['constraints'] = $EM_CONF[$_EXTKEY]['constraints'];

	// Set path to extension static_info_tables
if (t3lib_extMgm::isLoaded('static_info_tables')) {
	if (!defined ('PATH_BE_static_info_tables')) {
		define('PATH_BE_static_info_tables', t3lib_extMgm::extPath('static_info_tables'));
	}
}


if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['loginSecurityLevels'])) {

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['loginSecurityLevels'] = array('normal', 'rsa');
}

	// Captcha marker hook
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['registrationProcess'][] = 'EXT:' . $_EXTKEY . '/hooks/captcha/class.tx_agency_captcha.php:&tx_agency_captcha';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['model'][] = 'EXT:' . $_EXTKEY . '/hooks/captcha/class.tx_agency_captcha.php:&tx_agency_captcha';
	// Freecap marker hook
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['registrationProcess'][] = 'EXT:' . $_EXTKEY . '/hooks/freecap/class.tx_agency_freecap.php:&tx_agency_freecap';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['model'][] = 'EXT:' . $_EXTKEY . '/hooks/freecap/class.tx_agency_freecap.php:&tx_agency_freecap';

if (TYPO3_MODE == 'BE') {

	if (t3lib_extMgm::isLoaded(DIV2007_EXT)) {
		// replace the output of the former CODE field with the flexform
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$_EXTKEY . '_pi'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_agency_hooks_cms.php:&tx_agency_hooks_cms->pmDrawItem';
	}

	if (!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['fe_users']['MENU'])) {
		$tableArray = array('fe_users', 'fe_groups', 'fe_groups_language_overlay');
		foreach ($tableArray as $theTable) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['LLFile'][$theTable] = 'EXT:' . $_EXTKEY . '/locallang.xml';
		}

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['fe_users'] = array (
			'default' => array(
				'MENU' => 'm_default',
				'fList' =>  'username,usergroup,name,cnum,zip,city,email,telephone,gender,uid',
				'icon' => TRUE
			),
			'ext' => array (
				'MENU' => 'm_ext',
				'fList' =>  'username,first_name,middle_name,last_name,title,date_of_birth,comments',
				'icon' => TRUE
			),
			'country' => array(
				'MENU' => 'm_country',
				'fList' =>  'username,static_info_country,zone,language',
				'icon' => TRUE
			),
			'other' => array(
				'MENU' => 'm_other',
				'fList' =>  'username,www,company,status,image,lastlogin,by_invitation,terms_acknowledged,is_online,module_sys_dmail_html',
				'icon' => TRUE
			)
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['fe_groups'] = array (
			'default' => array(
				'MENU' => 'm_default',
				'fList' =>  'title,description',
				'icon' => TRUE
			),
			'ext' => array(
				'MENU' => 'm_ext',
				'fList' =>  'title,subgroup,lockToDomain,TSconfig',
				'icon' => TRUE
			)
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['fe_groups_language_overlay'] = array (
			'default' => array(
				'MENU' => 'm_default',
				'fList' =>  'title,fe_group,sys_language_uid',
				'icon' => TRUE
			)
		);
	}
}

if (
	isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'])
) {
	// TYPO3 4.5 with livesearch
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'] = array_merge(
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'],
		array(
			'fe_users' => 'fe_users'
		)
	);
}


if (t3lib_extMgm::isLoaded('tt_products') && TYPO3_MODE == 'FE') {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['extendingTCA'][] = $_EXTKEY;
}

?>