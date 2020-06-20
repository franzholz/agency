<?php
defined('TYPO3_MODE') || die('Access denied.');

if (!defined ('AGENCY_EXT')) {
    define('AGENCY_EXT', 'agency');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    AGENCY_EXT,
    'Configuration/TypoScript/PluginSetup/',
    'Agency Registration'
);

