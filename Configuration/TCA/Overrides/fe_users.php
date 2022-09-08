<?php
defined('TYPO3') || die();

if (!isset($GLOBALS['TCA']['fe_users']['ctrl']['type'])) {
    // no type field defined, so we define it here. This will only happen the first time the extension is installed!!
    $GLOBALS['TCA']['fe_users']['ctrl']['type'] = 'tx_extbase_type';
    $tempColumnstx_agency_fe_users = [];
    $tempColumnstx_agency_fe_users[$GLOBALS['TCA']['fe_users']['ctrl']['type']] = [
        'exclude' => true,
        'label' => 'LLL:EXT:agency/Resources/Private/Language/locallang_db.xlf:tx_agency.tx_extbase_type',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['', ''],
                ['FrontendUser', 'Tx_Agency_FrontendUser']
            ],
            'default' => 'Tx_Agency_FrontendUser',
            'size' => 1,
            'maxitems' => 1,
        ]
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumnstx_agency_fe_users);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    $GLOBALS['TCA']['fe_users']['ctrl']['type'],
    '',
    'after:' . $GLOBALS['TCA']['fe_users']['ctrl']['label']
);

// inherit and extend the show items from the parent class
if (isset($GLOBALS['TCA']['fe_users']['types']['0']['showitem'])) {
    $GLOBALS['TCA']['fe_users']['types']['Tx_Agency_FrontendUser']['showitem'] = $GLOBALS['TCA']['fe_users']['types']['0']['showitem'];
} elseif (is_array($GLOBALS['TCA']['fe_users']['types'])) {
    // use first entry in types array
    $fe_users_type_definition = reset($GLOBALS['TCA']['fe_users']['types']);
    $GLOBALS['TCA']['fe_users']['types']['Tx_Agency_FrontendUser']['showitem'] = $fe_users_type_definition['showitem'];
} else {
    $GLOBALS['TCA']['fe_users']['types']['Tx_Agency_FrontendUser']['showitem'] = '';
}
$GLOBALS['TCA']['fe_users']['types']['Tx_Agency_FrontendUser']['showitem'] .= ',--div--;LLL:EXT:agency/Resources/Private/Language/locallang_db.xlf:tx_agency_domain_model_frontenduser,';
$GLOBALS['TCA']['fe_users']['types']['Tx_Agency_FrontendUser']['showitem'] .= '';

$GLOBALS['TCA']['fe_users']['columns'][$GLOBALS['TCA']['fe_users']['ctrl']['type']]['config']['items'][] = [
    'LLL:EXT:agency/Resources/Private/Language/locallang_db.xlf:fe_users.tx_extbase_type.Tx_Agency_FrontendUser',
    'Tx_Agency_FrontendUser'
];
