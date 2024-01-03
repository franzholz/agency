<?php
defined('TYPO3') || die('Access denied.');

use JambageCom\Agency\Constants\Extension;


call_user_func(function($extensionKey, $table): void
{
    $listType = $extensionKey;
    $languageSubpath = '/Resources/Private/Language/';

    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . $extensionKey  . '/Configuration/FlexForms/flexform_ds.xml');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_content.list_type',
            $listType
        ],
        'list_type',
        $extensionKey
    );
}, Extension::KEY, basename(__FILE__, '.php'));

