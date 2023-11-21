<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey)
{
    if (!defined ('AGENCY_EXT')) {
        define('AGENCY_EXT', $extensionKey);
    }

    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get($extensionKey);

    if (!defined('STATIC_INFO_TABLES_EXT')) {
        define('STATIC_INFO_TABLES_EXT', 'static_info_tables');
    }
    if (!defined('DIV2007_LANGUAGE_SUBPATH')) {
        define('DIV2007_LANGUAGE_SUBPATH', '/Resources/Private/Language/');
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($extensionKey, 'class.tx_agency.php', '', 'list_type', 0);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['uploadfolder'] = $extensionConfiguration['uploadFolder'] ? $extensionConfiguration['uploadFolder'] : 'uploads/tx_agency';

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageMaxSize'] = $extensionConfiguration['imageMaxSize'] ? $extensionConfiguration['imageMaxSize'] : 250;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageTypes'] = $extensionConfiguration['imageTypes'] ? $extensionConfiguration['imageTypes'] : 'png,jpeg,jpg,gif,tif,tiff';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] = $extensionConfiguration['enableDirectMail'] ? $extensionConfiguration['enableDirectMail'] : 0;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['forceGender'] = $extensionConfiguration['forceGender'] ? $extensionConfiguration['forceGender'] : 0;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear'] = $extensionConfiguration['endtimeYear'] ? $extensionConfiguration['endtimeYear'] : 2030;

        /* Example of configuration of hooks */
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'][] = \MyWebsiteCom\MyExtension\Hooks\Handler::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess'][] = \JambageCom\Agency\Hooks\RegistrationProcessHooks::class;

    $EM_CONF = [];
    $_EXTKEY = $extensionKey;

        // Take note of conflicting extensions
        // Save extension version and constraints
    include \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'ext_emconf.php';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['version'] = $EM_CONF[$extensionKey]['version'];
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints'] = $EM_CONF[$extensionKey]['constraints'];
    unset($EM_CONF);

    // Configure captcha hooks
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'][] = \JambageCom\Div2007\Captcha\Captcha::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'][] = \JambageCom\Div2007\Captcha\Freecap::class;
    }

        // Scheduler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_agency_feusergroup_scheduler'] = [
        'extension' => $extensionKey,
        'title' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db_layout.xlf:feUserGroupScheduler.name',
        'description' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db_layout.xlf:feUserGroupScheduler.description',
    ];

    // replace the output of the former CODE field with the flexform
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$extensionKey . '_pi'][] =
        'JambageCom\\Agency\\Hooks\\CmsBackend->pmDrawItem';
    // Register Status Report Hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Agency Registration'][] = \JambageCom\Agency\Hooks\StatusProvider::class;

}, 'agency');
