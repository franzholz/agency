<?php
defined('TYPO3') || die('Access denied.');

use JambageCom\Agency\Constants\Extension;

$extensionKey = Extension::KEY;
$result = false;
$tableExists = false;
$table = 'sys_dmail_category';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';


if ( // Direct Mail tables exist but Direct Mail shall not be used
    !$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] ||
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
) {
    return $result;
}


if ( // Direct Mail tables exist but Direct Mail shall not be used
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] &&
    !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail') &&
    is_object($GLOBALS['TYPO3_DB'])
) {
    $queryResult =
        $GLOBALS['TYPO3_DB']->admin_query(
            'SELECT * FROM INFORMATION_SCHEMA.TABLES ' .
            'WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'' . $table . '\''
        );
    $tableExists = $GLOBALS['TYPO3_DB']->sql_num_rows($queryResult) > 0;
}

if ($tableExists) {
    // ******************************************************************
    // Categories
    // ******************************************************************
    $result = [
        'ctrl' => [
            'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_dmail_category',
            'label' => 'category',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'languageField' => 'sys_language_uid',
            'sortby' => 'sorting',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
            'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/icon_tx_directmail_category.gif'
        ],
        'interface' => [
                'showRecordFieldList' => 'hidden,category'
        ],
        'columns' => [
            'sys_language_uid' => [
                'label' => $languageLglPath . 'language',
                'config' => ['type' => 'language']
            ],
            'l18n_parent' => [
                'displayCond' => 'FIELD:sys_language_uid:>:0',
                'label' => $languageLglPath . 'l18n_parent',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['', 0],
                    ],
                    'foreign_table' => 'sys_dmail_category',
                    'foreign_table_where' => 'AND sys_dmail_category.pid=###CURRENT_PID### AND sys_dmail_category.sys_language_uid IN (-1,0)',
                    'default' => 0
                ]
            ],
            'l18n_diffsource' => [
                'config' => [
                    'type' => 'passthrough'
                ]
            ],
            'hidden' => [
                'label' => $languageLglPath . 'hidden',
                'config' => [
                    'type' => 'check',
                    'default' => '0'
                ]
            ],
            'category' => [
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_dmail_category.category',
                'config' => [
                    'type' => 'input',
                    'size' => '30',
                    'default' => null
                ]
            ],
            'old_cat_number' => [
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_dmail_category.old_cat_number',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'size' => '2',
                    'eval' => 'trim',
                    'max' => '2',
                    'default' => null
                ]
            ],
        ],
        'types' => [
            '0' => ['showitem' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden,--palette--;;1, category']
        ],
        'palettes' => [
            '1' => ['showitem' => '']
        ]
    ];
}

return $result;
