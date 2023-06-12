<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    $listType = $extensionKey;

    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . $extensionKey  . '/Configuration/FlexForms/flexform_ds.xml');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        array(
            'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_content.list_type',
            $listType,
            'EXT:' . $extensionKey . '/ext_icon.gif'
        ),
        'list_type',
        $extensionKey
    );
}, 'agency', basename(__FILE__, '.php'));

