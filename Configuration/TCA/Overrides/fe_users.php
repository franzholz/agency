<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}


$table = 'fe_users';


$temporaryColumns = array(
    'cnum' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.cnum',
        'config' => array(
            'type' => 'input',
            'size' => '20',
            'max' => '50',
            'eval' => 'trim',
            'default' => ''
        )
    ),
    'static_info_country' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.static_info_country',
        'config' => array(
            'type' => 'input',
            'size' => '5',
            'max' => '3',
            'eval' => '',
            'default' => ''
        )
    ),
    'zone' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.zone',
        'config' => array(
            'type' => 'input',
            'size' => '20',
            'max' => '40',
            'eval' => 'trim',
            'default' => ''
        )
    ),
    'language' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.language',
        'config' => array(
            'type' => 'input',
            'size' => '4',
            'max' => '2',
            'eval' => '',
            'default' => ''
        )
    ),
    'date_of_birth' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.date_of_birth',
        'config' => array(
            'type' => 'input',
            'size' => '10',
            'max' => '20',
            'eval' => 'date',
            'checkbox' => '0',
            'default' => ''
        )
    ),
    'gender' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender',
        'config' => array(
            'type' => 'radio',
            'items' => array(
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.99', '99'),
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.0', '0'),
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.1', '1')
            ),
        )
    ),
    'status' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.status',
        'config' => array(
            'type' => 'select',
            'items' => array(
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.status.I.0', '0'),
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.status.I.1', '1'),
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.status.I.2', '2'),
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.status.I.3', '3'),
                array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.status.I.4', '4'),
            ),
            'size' => 1,
            'maxitems' => 1,
        )
    ),
    'comments' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.comments',
        'config' => array(
            'type' => 'text',
            'rows' => '5',
            'cols' => '48',
            'eval' => 'null',
            'default' => NULL,
        )
    ),
    'by_invitation' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.by_invitation',
        'config' => array(
            'type' => 'check',
            'default' => '0'
        )
    ),
    'has_privileges' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.has_privileges',
        'config' => array(
            'type' => 'check',
            'default' => '0'
        )
    ),
    'terms_acknowledged' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.terms_acknowledged',
        'config' => array(
            'type' => 'check',
            'default' => '0',
            'readOnly' => '1',
        )
    ),
    'token' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.token',
        'config' => array(
            'type' => 'text',
            'rows' => '1',
            'cols' => '32'
        )
    ),
    'tx_agency_password' => array (
        'exclude' => 1,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.tx_agency_password',
        'config' => array (
            'type' => 'passthrough',
        )
    ),
    'house_no' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.house_no',
        'config' => array(
            'type' => 'input',
            'eval' => 'trim',
            'size' => '20',
            'max' => '20'
        )
    ),
    'lost_password' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.lost_password',
        'config' => array(
            'type' => 'check',
            'default' => '0'
        )
    ),
    'privacy_policy_acknowledged' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.privacy_policy_acknowledged',
        'config' => array(
            'type' => 'check',
            'default' => '0',
            'readOnly' => '1',
        )
    ),
    'privacy_policy_date' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.privacy_policy_date',
        'config' => array(
            'type' => 'input',
            'size' => '10',
            'max' => '20',
            'eval' => 'date',
            'checkbox' => '0',
            'default' => '',
            'readOnly' => '1'
        )
    ),
);

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['forceGender']) {
    $temporaryColumns['gender']['config']['items'] = array(
        array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.0', '0'),
        array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.1', '1')
    );
}

if ( // Direct Mail tables exist but Direct Mail shall not be used
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] &&
    !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
) {
    // fe_users modified
    $directMailTemporaryColumns = array(
        'module_sys_dmail_newsletter' => array(
            'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.module_sys_dmail_newsletter',
            'exclude' => '1',
            'config'=>array(
                'type'=>'check'
                )
            ),
        'module_sys_dmail_category' => array(
            'label'=>'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.module_sys_dmail_category',
            'exclude' => '1',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'allowed' => 'sys_dmail_category',
                'MM' => 'sys_dmail_feuser_category_mm',
                'foreign_table' => 'sys_dmail_category',
                'foreign_table_where' =>
                    'AND sys_dmail_category.pid IN ' .
                    '(###PAGE_TSCONFIG_IDLIST###) ORDER BY sys_dmail_category.sorting',
                'size' => 10,
                'renderMode' => 'check',
                'minitems' => 0,
                'maxitems' => 1000,
            )
        ),
        'module_sys_dmail_html' => array(
            'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.module_sys_dmail_html',
            'exclude' => '1',
            'config' => array(
                'type'=>'check'
            )
        )
    );

    $temporaryColumns = array_merge($temporaryColumns, $directMailTemporaryColumns);
}

$columns = array_keys($temporaryColumns);

foreach ($columns as $column) {
    if (isset($GLOBALS['TCA'][$table]['column'][$column])) {
        unset($temporaryColumns[$column]);
    }
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    $table,
    'comments, by_invitation, has_privileges, terms_acknowledged, privacy_policy_acknowledged, privacy_policy_date, lost_password',
    '',
    'after:www,'
);

if ( // Direct Mail tables exist but Direct Mail shall not be used
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] &&
    !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        '--div--;Direct mail, module_sys_dmail_newsletter;;;;1-1-1, module_sys_dmail_category, module_sys_dmail_html'
    );
}
+
$GLOBALS['TCA'][$table]['columns']['username']['config']['eval'] = 'nospace,uniqueInPid,required';
$GLOBALS['TCA'][$table]['columns']['name']['config']['max'] = '100';
$GLOBALS['TCA'][$table]['columns']['company']['config']['max'] = '50';
$GLOBALS['TCA'][$table]['columns']['city']['config']['max'] = '40';
$GLOBALS['TCA'][$table]['columns']['country']['config']['max'] = '60';
$GLOBALS['TCA'][$table]['columns']['zip']['config']['size'] = '15';
$GLOBALS['TCA'][$table]['columns']['zip']['config']['max'] = '20';
$GLOBALS['TCA'][$table]['columns']['email']['config']['max'] = '255';
$GLOBALS['TCA'][$table]['columns']['telephone']['config']['max'] = '25';
$GLOBALS['TCA'][$table]['columns']['fax']['config']['max'] = '25';
$GLOBALS['TCA'][$table]['columns']['image']['config']['uploadfolder'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['uploadfolder'];
$GLOBALS['TCA'][$table]['columns']['image']['config']['max_size'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageMaxSize'];
$GLOBALS['TCA'][$table]['columns']['image']['config']['allowed'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageTypes'];


$GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] =
    preg_replace(
        '/(^|,)\s*country\s*(,|$)/', '$1zone,static_info_country,country,language$2',
        $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']
    );
$GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] =
    preg_replace(
        '/(^|,)\s*title\s*(,|$)/',
        '$1gender,status,date_of_birth,house_no,title$2',
        $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']
    );

$GLOBALS['TCA'][$table]['types']['0']['showitem'] =
    preg_replace(
        '/(^|,)\s*country\s*(,|$)/', '$1 zone, static_info_country, country, language$2',
        $GLOBALS['TCA'][$table]['types']['0']['showitem']
    );
$GLOBALS['TCA'][$table]['types']['0']['showitem'] =
    preg_replace(
        '/(^|,)\s*address\s*(,|$)/',
        '$1 cnum, status, date_of_birth, house_no, address$2',
        $GLOBALS['TCA'][$table]['types']['0']['showitem']
    );

$GLOBALS['TCA'][$table]['palettes']['2']['showitem'] = 'gender,--linebreak--,' . $GLOBALS['TCA'][$table]['palettes']['2']['showitem'];

$GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image';



$searchFields = explode(',', $GLOBALS['TCA'][$table]['ctrl']['searchFields'] . ',cnum,comments');
$searchFields = array_unique($searchFields);
$GLOBALS['TCA'][$table]['ctrl']['searchFields'] = implode(',', $searchFields);

