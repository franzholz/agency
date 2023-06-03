<?php
defined('TYPO3') || die('Access denied.');
if (!defined ('AGENCY_EXT')) {
    define('AGENCY_EXT', 'agency');
}

$result = array(
    'ctrl' => array (
        'title' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_agency_fe_users_limit_fe_groups',
        'label' => 'codes',
        'default_sortby' => 'ORDER BY codes',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),
        'iconfile' => 'EXT:' . AGENCY_EXT . '/ext_icon.gif',
    ),
    'interface' => array (
        'showRecordFieldList' => 'hidden,starttime,endtime,codes,status'
    ),
    'columns' => array (
        'hidden' => array (
            'exclude' => 0,
            'label'  => DIV2007_LANGUAGE_LGL . 'hidden',
            'config' => array (
                'type' => 'check',
                'default' => 0
            )
        ),
        'tstamp' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tstamp',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0
            )
        ),
        'crdate' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:crdate',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'renderType' => 'inputDateTime',
                'default' => 0
            )
        ),
        'starttime' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'starttime',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0
            )
        ),
        'endtime' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'endtime',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'range' => array (
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
                )
            )
        ),
        'codes' => array (
            'exclude' => 0,
            'label' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_agency_fe_users_limit_fe_groups.codes',
            'config' => array (
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'null',
                'default' => NULL,
            )
        ),
        'status' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . AGENCY_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_agency_fe_users_limit_fe_groups.status',
            'config' => array (
                'type' => 'input',
                'size' => '10',
                'eval' => 'int',
                'default' => '0',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, codes, status, starttime, endtime')
    )
);

return $result;

