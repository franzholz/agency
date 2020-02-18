<?php
defined('TYPO3_MODE') || die('Access denied.');
if (!defined ('AGENCY_EXT')) {
    define('AGENCY_EXT', 'agency');
}

$result = false;

$table = 'fe_groups_language_overlay';

$queryResult =
    $GLOBALS['TYPO3_DB']->admin_query(
        'SELECT * FROM INFORMATION_SCHEMA.TABLES ' .
        'WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'' . $table . '\''
    );
$tableExists = $GLOBALS['TYPO3_DB']->sql_num_rows($queryResult) > 0;
if (!$tableExists) {
    return $result;
}

$result = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xlf:fe_groups_language_overlay',
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
                'type' => 'check'
            )
        ),
        'fe_group' => array(
            'exclude' => 0,
            'label'  => DIV2007_LANGUAGE_LGL . 'fe_group',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'fe_groups'
            )
        ),
        'sys_language_uid' => array(
            'exclude' => 0,
            'label' => DIV2007_LANGUAGE_LGL . 'language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title'
            )
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xlf:fe_groups_language_overlay.title',
            'config' => array(
                'type' => 'input',
                'size' => '50',
                'max' => '70',
                'eval' => 'trim,required',
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
    unset($result['ctrl']['typeicon_classes'];
    if (
        version_compare(TYPO3_version, '7.0.0', '>')
    ) {
        $result['ctrl']['iconfile'] = 'EXT:t3skin/icons/gfx/i/fe_groups.gif',
    } else {
        $result['ctrl']['iconfile'] = 'gfx/i/fe_groups.gif',
    }
}

return $result;

