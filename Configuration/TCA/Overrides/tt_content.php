<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

use JambageCom\Agency\Constants\Extension;

call_user_func(function ($extensionKey, $table): void {
    $pluginSignature = $extensionKey;
    $languageSubpath = '/Resources/Private/Language/';

    ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_content.list_type',
            $pluginSignature,
            '',
            'plugin'
        ],
        'CType',
        $extensionKey
    );

    +// Activate the display of the FlexForm field
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;Configuration,pi_flexform,',
        $pluginSignature,
        'after:subheader',
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:' . $extensionKey  . '/Configuration/FlexForms/flexform_ds.xml',
        $pluginSignature,
    );
}, Extension::KEY, basename(__FILE__, '.php'));
