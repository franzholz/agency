<?php
defined('TYPO3_MODE') || die('Access denied.');

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
    class_exists($emClass) &&
    method_exists($emClass, 'extPath')
) {
    // nothing
} else {
    $emClass = 't3lib_extMgm';
}

if (!defined ('AGENCY_EXT')) {
    define('AGENCY_EXT', 'agency');
}

if (!defined ('PATH_BE_AGENCY')) {
    define('PATH_BE_AGENCY', call_user_func($emClass . '::extPath', AGENCY_EXT));
}

if (!defined ('PATH_BE_AGENCY_REL')) {
    define('PATH_BE_AGENCY_REL', call_user_func($emClass . '::extRelPath', AGENCY_EXT));
}

if (!defined ('PATH_FE_AGENCY_REL')) {
    define('PATH_FE_AGENCY_REL', call_user_func($emClass . '::siteRelPath', AGENCY_EXT));
}

if (!defined(STATIC_INFO_TABLES_EXT)) {
    define('STATIC_INFO_TABLES_EXT', 'static_info_tables');
}

call_user_func($emClass . '::addPItoST43', AGENCY_EXT, 'class.tx_agency.php', '', 'list_type', 0);

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['uploadfolder'] = $_EXTCONF['uploadFolder'] ? $_EXTCONF['uploadFolder'] : 'uploads/tx_agency';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageMaxSize'] = $_EXTCONF['imageMaxSize'] ? $_EXTCONF['imageMaxSize'] : 250;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageTypes'] = $_EXTCONF['imageTypes'] ? $_EXTCONF['imageTypes'] : 'png,jpeg,jpg,gif,tif,tiff';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] = $_EXTCONF['enableDirectMail'] ? $_EXTCONF['enableDirectMail'] : 0;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['forceGender'] = $_EXTCONF['forceGender'] ? $_EXTCONF['forceGender'] : 0;

    /* Example of configuration of hooks */
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['agency']['confirmRegistrationClass'][] = 'JambageCom\\Agency\\Hooks\\Handler';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['agency']['registrationProcess'][] = 'JambageCom\\Agency\\Hooks\\RegistrationProcessHooks';

    // Take note of conflicting extensions
    // Save extension version and constraints
require_once(call_user_func($emClass . '::extPath', AGENCY_EXT) . 'ext_emconf.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['version'] = $EM_CONF[AGENCY_EXT]['version'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['constraints'] = $EM_CONF[AGENCY_EXT]['constraints'];

if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['loginSecurityLevels'])) {

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['loginSecurityLevels'] = array('normal', 'rsa');
}

// Configure captcha hooks
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'] = array();
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'][] = 'JambageCom\\Div2007\\Captcha\\Captcha';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'][] = 'JambageCom\\Div2007\\Captcha\\Freecap';
}

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['languageResource']) {
        // Scheduler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_agency_feusergroup_scheduler'] = array(
        'extension' => AGENCY_EXT,
        'title' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db_layout.xlf:feUserGroupScheduler.name',
        'description' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db_layout.xlf:feUserGroupScheduler.description',
    );
} else {
        // Scheduler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_agency_feusergroup_scheduler'] = array(
        'extension' => AGENCY_EXT,
        'title' => 'LLL:EXT:' . AGENCY_EXT . '/locallang.xlf:feUserGroupScheduler.name',
        'description' => 'LLL:EXT:' . AGENCY_EXT . '/locallang.xlf:feUserGroupScheduler.description',
    );
}


if (TYPO3_MODE == 'BE') {

    if (call_user_func($emClass . '::isLoaded', DIV2007_EXT)) {
        // replace the output of the former CODE field with the flexform
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][AGENCY_EXT . '_pi'][] =
            'JambageCom\\Agency\\Hooks\\CmsBackend->pmDrawItem';
        // Register Status Report Hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Agency Registration'][] = \JambageCom\Agency\Hooks\StatusProvider::class;
    }
}


