<?php
defined('TYPO3_MODE') || die('Access denied.');

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
    TYPO3_MODE == 'BE' &&
    !$loadTcaAdditions
) {
    call_user_func($emClass . '::addStaticFile', AGENCY_EXT, 'Configuration/TypoScript/PluginSetup/', 'Agency Registration');
}

call_user_func($emClass . '::allowTableOnStandardPages', 'fe_groups_language_overlay');
call_user_func($emClass . '::addToInsertRecords', 'fe_groups_language_overlay');

if ( // Direct Mail tables exist but Direct Mail shall not be used
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] &&
    !call_user_func($emClass . '::isLoaded', 'direct_mail')
) {
    call_user_func($emClass . '::allowTableOnStandardPages', 'sys_dmail_category');
    call_user_func($emClass . '::addToInsertRecords', 'sys_dmail_category');
}


