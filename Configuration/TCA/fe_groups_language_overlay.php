<?php
defined('TYPO3') || die('Access denied.');

use JambageCom\Agency\Constants\Extension;

$extensionKey = Extension::KEY;
$result = false;
$tableExists = true;
$table = 'fe_groups_language_overlay';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';
        
if (is_object($GLOBALS['TYPO3_DB'])) {
    $queryResult =
        $GLOBALS['TYPO3_DB']->admin_query(
            'SELECT * FROM INFORMATION_SCHEMA.TABLES ' .
            'WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'' . $table . '\''
        );
    $tableExists = $GLOBALS['TYPO3_DB']->sql_num_rows($queryResult) > 0;
}

if (!$tableExists) {
    return $result;
}

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_groups_language_overlay',
        'label' => 'title',
        'default_sortby' => 'ORDER BY fe_groups_uid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'typeicon_classes' => [
            'default' => 'status-user-group-frontend'
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,fe_group,title'
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 0,
            'label'  => $languageLglPath . 'hidden',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label'  => $languageLglPath . 'fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        $languageLglPath . 'hide_at_login',
                        -1
                    ],
                    [
                        $languageLglPath . 'any_login',
                        -2
                    ],
                    [
                        $languageLglPath . 'usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true,
                'default' => 0,
            ]
        ],
        'sys_language_uid' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'language',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'default' => 0
            ]
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_groups_language_overlay.title',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '70',
                'eval' => 'trim,required',
                'default' => null,
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, fe_group, sys_language_uid, title']
    ]
];

return $result;

