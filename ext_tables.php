<?php
defined('TYPO3') || die('Access denied.');

use JambageCom\Agency\Constants\Extension;

call_user_func(function($extensionKey): void
{
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'fe_groups_language_overlay');

    if ( // Direct Mail tables exist but Direct Mail shall not be used
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] &&
        !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
    ) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'sys_dmail_category');
    }
}, Extension::KEY);
