<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript/PluginSetup/',
        'Agency Registration'
    );
}, 'agency', basename(__FILE__, '.php'));
