<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    if (!defined ('AGENCY_EXT')) {
        define('AGENCY_EXT', 'agency');
    }

    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get(AGENCY_EXT);

    if (!defined(STATIC_INFO_TABLES_EXT)) {
        define('STATIC_INFO_TABLES_EXT', 'static_info_tables');
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(AGENCY_EXT, 'class.tx_agency.php', '', 'list_type', 0);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['uploadfolder'] = $extensionConfiguration['uploadFolder'] ? $extensionConfiguration['uploadFolder'] : 'uploads/tx_agency';

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageMaxSize'] = $extensionConfiguration['imageMaxSize'] ? $extensionConfiguration['imageMaxSize'] : 250;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageTypes'] = $extensionConfiguration['imageTypes'] ? $extensionConfiguration['imageTypes'] : 'png,jpeg,jpg,gif,tif,tiff';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] = $extensionConfiguration['enableDirectMail'] ? $extensionConfiguration['enableDirectMail'] : 0;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['forceGender'] = $extensionConfiguration['forceGender'] ? $extensionConfiguration['forceGender'] : 0;

        /* Example of configuration of hooks */
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['confirmRegistrationClass'][] = \MyWebsiteCom\MyExtension\Hooks\Handler::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['registrationProcess'][] = \JambageCom\Agency\Hooks\RegistrationProcessHooks::class;

        // Take note of conflicting extensions
        // Save extension version and constraints
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(AGENCY_EXT) . 'ext_emconf.php';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['version'] = $EM_CONF[AGENCY_EXT]['version'];
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['constraints'] = $EM_CONF[AGENCY_EXT]['constraints'];

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['loginSecurityLevels'])) {

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['loginSecurityLevels'] = array('normal');
    }

    // Configure captcha hooks
    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'] = array();
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'][] = \JambageCom\Div2007\Captcha\Captcha::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['captcha'][] = \JambageCom\Div2007\Captcha\Freecap::class;
    }

        // Scheduler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_agency_feusergroup_scheduler'] = array(
        'extension' => AGENCY_EXT,
        'title' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db_layout.xlf:feUserGroupScheduler.name',
        'description' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db_layout.xlf:feUserGroupScheduler.description',
    );

    if (TYPO3_MODE == 'BE') {

        // replace the output of the former CODE field with the flexform
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][AGENCY_EXT . '_pi'][] =
            'JambageCom\\Agency\\Hooks\\CmsBackend->pmDrawItem';
        // Register Status Report Hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Agency Registration'][] = \JambageCom\Agency\Hooks\StatusProvider::class;
    }
});
