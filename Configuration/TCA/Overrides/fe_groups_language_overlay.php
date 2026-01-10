<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

use JambageCom\Agency\Constants\Extension;

call_user_func(function ($extensionKey, $table): void {
    ExtensionManagementUtility::addToInsertRecords($table);
}, Extension::KEY, basename(__FILE__, '.php'));
