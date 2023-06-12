<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    $table = 'fe_users';

    $temporaryColumns = array(
        'cnum' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.cnum',
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
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.static_info_country',
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
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.zone',
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
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.language',
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
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.date_of_birth',
            'config' => array(
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'dbType' => 'date',
                'size' => '10',
                'max' => '20',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => ''
            )
        ),
        'gender' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.gender',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.gender.I.99', '99'),
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.gender.I.0', '0'),
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.gender.I.1', '1')
                ),
                'default' => 99
            )
        ),
        'status' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.status',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.status.I.0', '0'),
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.status.I.1', '1'),
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.status.I.2', '2'),
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.status.I.3', '3'),
                    array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.status.I.4', '4'),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => 0
            )
        ),
        'comments' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.comments',
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
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.by_invitation',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'has_privileges' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.has_privileges',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'terms_acknowledged' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.terms_acknowledged',
            'config' => array(
                'type' => 'check',
                'default' => '0',
                'readOnly' => '1',
            )
        ),
        'token' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.token',
            'config' => array(
                'type' => 'text',
                'rows' => '1',
                'cols' => '32',
                'default' => null
            )
        ),
        'tx_agency_password' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.tx_agency_password',
            'config' => array (
                'type' => 'passthrough',
            )
        ),
        'house_no' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.house_no',
            'config' => array(
                'type' => 'input',
                'eval' => 'trim',
                'size' => '20',
                'max' => '20',
                'default' => ''
            )
        ),
        'lost_password' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.lost_password',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'privacy_policy_acknowledged' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.privacy_policy_acknowledged',
            'config' => array(
                'type' => 'check',
                'default' => '0',
                'readOnly' => '1',
            )
        ),
        'privacy_policy_date' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.privacy_policy_date',
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

    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['forceGender']) {
        $temporaryColumns['gender']['config']['items'] = array(
            array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.gender.I.0', '0'),
            array('LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.gender.I.1', '1')
        );
    }

    $directMailTemporaryColumns = array();

    if ( // Direct Mail tables exist but Direct Mail shall not be used
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] &&
        !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
    ) {
        // fe_users modified
        $directMailTemporaryColumns = array(
            'module_sys_dmail_newsletter' => array(
                'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.module_sys_dmail_newsletter',
                'exclude' => '1',
                'config' => array(
                        'type' => 'check',
                        'default' => '0'
                    )
                ),
            'module_sys_dmail_category' => array(
                'label'=>'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.module_sys_dmail_category',
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
                    'default' => '0'
                )
            ),
            'module_sys_dmail_html' => array(
                'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:fe_users.module_sys_dmail_html',
                'exclude' => '1',
                'config' => array(
                    'type'=>'check',
                    'default' => '0'
                )
            )
        );
    }

    $columns = array_keys($temporaryColumns);

    foreach ($columns as $column) {
        if (isset($GLOBALS['TCA'][$table]['columns'][$column])) {
            unset($temporaryColumns[$column]);
        }
    }

    $columns = array_keys($temporaryColumns);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        implode(',', $columns),
        '',
        'after:www,'
    );

    if ( // Direct Mail tables exist but Direct Mail shall not be used
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] &&
        !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
    ) {
        $columns = array_keys($directMailTemporaryColumns);

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            $table,
            '--div--;Direct mail, module_sys_dmail_newsletter;;;;1-1-1, ' . implode(',', $columns)
        );
    }

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
    $GLOBALS['TCA'][$table]['columns']['image']['config']['uploadfolder'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['uploadfolder'];
    $GLOBALS['TCA'][$table]['columns']['image']['config']['max_size'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageMaxSize'];
    $GLOBALS['TCA'][$table]['columns']['image']['config']['allowed'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageTypes'];


    $temporaryColumns['country'] = '';
    $columns = array('zone', 'static_info_country', 'country', 'language');
    $validColumns = array();
    foreach ($columns as $column) {
        if (isset($temporaryColumns[$column])) {
            $validColumns[] = $column;
        }
    }

    if (isset($GLOBALS['TCA'][$table]['interface']['showRecordFieldList'])) {
        $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] =
            preg_replace(
                '/(^|,)\s*country\s*(,|$)/', '$1' .  implode(',', $validColumns) . '$2',
                $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']
            );
    }

    $GLOBALS['TCA'][$table]['types']['0']['showitem'] =
        preg_replace(
            '/(^|,)\s*country\s*(,|$)/', '$1 ' .  implode(',', $validColumns) . '$2',
            $GLOBALS['TCA'][$table]['types']['0']['showitem']
        );
            
    $temporaryColumns['title'] = '';
        
    $columns = array('gender', 'status', 'date_of_birth', 'house_no', 'title');
    $validColumns = array();
    foreach ($columns as $column) {
        if (isset($temporaryColumns[$column])) {
            $validColumns[] = $column;
        }
    }

    if (isset($GLOBALS['TCA'][$table]['interface']['showRecordFieldList'])) {
        $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] =
            preg_replace(
                '/(^|,)\s*title\s*(,|$)/',
                '$1' .  implode(',', $validColumns) . '$2',
                $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']
            );
    }

    $temporaryColumns['address'] = '';
    $columns = array('cnum', 'status', 'date_of_birth', 'house_no', 'address');
    $validColumns = array();
    foreach ($columns as $column) {
        if (isset($temporaryColumns[$column])) {
            $validColumns[] = $column;
        }
    }

    $GLOBALS['TCA'][$table]['types']['0']['showitem'] =
        preg_replace(
            '/(^|,)\s*address\s*(,|$)/',
            '$1 ' .  implode(',', $validColumns) . '$2',
            $GLOBALS['TCA'][$table]['types']['0']['showitem']
        );


    $GLOBALS['TCA'][$table]['palettes']['2']['showitem'] = 'gender,--linebreak--,' . $GLOBALS['TCA'][$table]['palettes']['2']['showitem'];
    $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image';



    $searchFields = explode(',', $GLOBALS['TCA'][$table]['ctrl']['searchFields'] . ',cnum,comments');
    $searchFields = array_unique($searchFields);
    $GLOBALS['TCA'][$table]['ctrl']['searchFields'] = implode(',', $searchFields);

}, 'agency', basename(__FILE__, '.php'));

