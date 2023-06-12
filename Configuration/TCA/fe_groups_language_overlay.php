<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'agency';
$result = false;
$tableExists = true;
$table = 'fe_groups_language_overlay';

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

$result = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_groups_language_overlay',
        'label' => 'title',
        'default_sortby' => 'ORDER BY fe_groups_uid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden'
        ),
        'typeicon_classes' => [
            'default' => 'status-user-group-frontend'
        ],
    ),
    'interface' => array (
        'showRecordFieldList' => 'hidden,fe_group,title'
    ),
    'columns' => array(
        'hidden' => array (
            'exclude' => 0,
            'label'  => DIV2007_LANGUAGE_LGL . 'hidden',
            'config' => array (
                'type' => 'check',
                'default' => 0
            )
        ),
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label'  => DIV2007_LANGUAGE_LGL . 'fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        DIV2007_LANGUAGE_LGL . 'hide_at_login',
                        -1
                    ],
                    [
                        DIV2007_LANGUAGE_LGL . 'any_login',
                        -2
                    ],
                    [
                        DIV2007_LANGUAGE_LGL . 'usergroups',
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
        'sys_language_uid' => array(
            'exclude' => 0,
            'label' => DIV2007_LANGUAGE_LGL . 'language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'default' => 0
            )
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_groups_language_overlay.title',
            'config' => array(
                'type' => 'input',
                'size' => '50',
                'max' => '70',
                'eval' => 'trim,required',
                'default' => '',
            )
        ),
    ),
    'types' => array(
        '0' => array( 'showitem' => 'hidden;;;;1-1-1, fe_group, sys_language_uid, title')
    )
);

if (
    version_compare(TYPO3_version, '8.0.0', '<')
) {
    unset($result['ctrl']['typeicon_classes']);
    $result['ctrl']['iconfile'] = 'EXT:t3skin/icons/gfx/i/fe_groups.gif';
}

return $result;

