<?php

defined('TYPO3') || die('Access denied.');

use JambageCom\Agency\Constants\Extension;

call_user_func(function ($extensionKey, $table): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
}, Extension::KEY, basename(__FILE__, '.php'));
