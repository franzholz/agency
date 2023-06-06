<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'fe_groups_language_overlay');

    if ( // Direct Mail tables exist but Direct Mail shall not be used
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] &&
        !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
    ) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'sys_dmail_category');
    }
});
