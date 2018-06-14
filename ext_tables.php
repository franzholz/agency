<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
    class_exists($emClass) &&
    method_exists($emClass, 'extPath')
) {
    // nothing
} else {
    $emClass = 't3lib_extMgm';
}

$divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

if (
    class_exists($divClass)
) {
    // nothing
} else {
    $divClass = 't3lib_div';
}

if (!isset($_EXTKEY)) {
    $_EXTKEY = AGENCY_EXT;
}

if (
    TYPO3_MODE == 'BE' &&
    !$loadTcaAdditions
) {
    call_user_func($emClass . '::addStaticFile', $_EXTKEY, 'Configuration/TypoScript/PluginSetup/', 'Agency Registration');

    $listType = $_EXTKEY . '';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
    call_user_func($emClass . '::addPiFlexFormValue', $listType, 'FILE:EXT:' . $_EXTKEY . '/pi/flexform_ds_pi.xml');
    call_user_func($emClass . '::addPlugin', array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:tt_content.list_type', $listType), 'list_type');
}

call_user_func($emClass . '::allowTableOnStandardPages', 'fe_groups_language_overlay');
call_user_func($emClass . '::addToInsertRecords', 'fe_groups_language_overlay');

if ( // Direct Mail tables exist but Direct Mail shall not be used
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['enableDirectMail'] &&
    !call_user_func($emClass . '::isLoaded', 'direct_mail')
) {
    call_user_func($emClass . '::allowTableOnStandardPages', 'sys_dmail_category');
    call_user_func($emClass . '::addToInsertRecords', 'sys_dmail_category');
}


