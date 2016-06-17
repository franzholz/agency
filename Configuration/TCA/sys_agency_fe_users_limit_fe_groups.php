<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}



$result = array(
	'ctrl' => array (
		'title' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_agency_fe_users_limit_fe_groups',
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
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath(AGENCY_EXT) . 'ext_icon.gif',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,starttime,endtime,codes,status'
	),
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array (
				'type' => 'check'
			)
		),
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:tstamp',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'crdate' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:crdate',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'starttime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array (
					'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['endtimeYear']),
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				)
			)
		),
		'codes' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_agency_fe_users_limit_fe_groups.codes',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'status' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_agency_fe_users_limit_fe_groups.status',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'eval' => 'int',
				'default' => '0',
			)
		),
	),
	'types' => array(
		'0' => array( 'showitem' => 'hidden;;;;1-1-1, codes, status, starttime, endtime')
	)
);


return $result;

