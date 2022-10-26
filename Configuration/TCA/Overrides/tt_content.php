<?php
defined('TYPO3_MODE') || die('Access denied.');
if (!defined ('AGENCY_EXT')) {
    define('AGENCY_EXT', 'agency');
}

$table = 'tt_content';

$listType = AGENCY_EXT;

$GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
$GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . AGENCY_EXT  . '/Configuration/FlexForms/flexform_ds.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array(
        'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_content.list_type',
        $listType,
        'EXT:' . AGENCY_EXT . '/ext_icon.gif'
    ),
    'list_type',
    AGENCY_EXT
);

