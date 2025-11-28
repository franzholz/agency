<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Agency\Constants\Extension;
use JambageCom\Agency\Hooks\RegistrationProcessHooks;
use JambageCom\Agency\Hooks\StatusProvider;
use JambageCom\Agency\Upgrades\PluginListTypeToCTypeUpdate;


call_user_func(function ($extensionKey): void {
    $languageSubpath = '/Resources/Private/Language/';
    $extensionConfiguration = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    )->get($extensionKey);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['uploadfolder'] = $extensionConfiguration['uploadFolder'] ?? 'uploads/tx_agency';

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageMaxSize'] = $extensionConfiguration['imageMaxSize'] ?? 250;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageTypes'] = $extensionConfiguration['imageTypes'] ?? 'png,jpeg,jpg,gif,tif,tiff';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['forceGender'] = $extensionConfiguration['forceGender'] ?? 0;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear'] = $extensionConfiguration['endtimeYear'] ?? 2030;

    /* Example of configuration of hooks */
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'][] = \MyWebsiteCom\MyExtension\Hooks\Handler::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess'][] = RegistrationProcessHooks::class;

    $EM_CONF = [];
    $_EXTKEY = $extensionKey;

    // Take note of conflicting extensions
    // Save extension version and constraints
    include ExtensionManagementUtility::extPath($extensionKey) . 'ext_emconf.php';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['version'] = $EM_CONF[$extensionKey]['version'];
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints'] = $EM_CONF[$extensionKey]['constraints'];
    unset($EM_CONF);

    // Configure captcha hooks
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'][] = \JambageCom\Div2007\Captcha\Freecap::class;
    }

    // Scheduler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_agency_feusergroup_scheduler'] = [
        'extension' => $extensionKey,
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db_layout.xlf:feUserGroupScheduler.name',
        'description' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db_layout.xlf:feUserGroupScheduler.description',
    ];

    // Register Status Report Hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Agency Registration'][] = StatusProvider::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['agencyPluginListTypeToCTypeUpdate']
        = PluginListTypeToCTypeUpdate::class;
}, Extension::KEY);
