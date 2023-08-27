<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'agency';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_agency_fe_users_limit_fe_groups',
        'label' => 'codes',
        'default_sortby' => 'ORDER BY codes',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/Extension.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,starttime,endtime,codes,status'
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
        'tstamp' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tstamp',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0
            ]
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:crdate',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'renderType' => 'inputDateTime',
                'default' => 0
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'starttime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0
            ]
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'endtime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
                ]
            ]
        ],
        'codes' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_agency_fe_users_limit_fe_groups.codes',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ]
        ],
        'status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_agency_fe_users_limit_fe_groups.status',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'int',
                'default' => '0',
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, codes, status, starttime, endtime']
    ]
];

return $result;

