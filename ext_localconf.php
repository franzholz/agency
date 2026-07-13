<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Agency\Constants\Extension;
use JambageCom\Agency\Hooks\RegistrationProcessHooks;
use JambageCom\Agency\Hooks\StatusProvider;
use JambageCom\Agency\Upgrades\PluginListTypeToCTypeUpdate;


call_user_func(function ($extensionKey): void {
    $languageSubpath = '/Resources/Private/Language/';

    /* Example of configuration of hooks */
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'][] = \MyWebsiteCom\MyExtension\Hooks\Handler::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess'][] = RegistrationProcessHooks::class;

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
