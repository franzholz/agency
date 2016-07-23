<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

if (
	class_exists($divClass)
) {
	// nothing
} else {
	$divClass = 't3lib_div';
}

if (
	TYPO3_MODE == 'BE' &&
	!$loadTcaAdditions
) {
	call_user_func($emClass . '::addStaticFile', AGENCY_EXT, 'Configuration/TypoScript/PluginSetup/', 'Agency Registration');

	if (version_compare(TYPO3_version, '6.1.0', '<')) {

		call_user_func($divClass . '::loadTCA', 'tt_content');
	}

	$listType = AGENCY_EXT . '';
	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
	call_user_func($emClass . '::addPiFlexFormValue', $listType, 'FILE:EXT:' . AGENCY_EXT . '/pi/flexform_ds_pi.xml');
	call_user_func($emClass . '::addPlugin', array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:tt_content.list_type', $listType), 'list_type');
}

if (
	version_compare(TYPO3_version, '6.2.0', '<') &&
	!$loadTcaAdditions &&
	!call_user_func($emClass . '::isLoaded', 'sr_feuser_register')
) {
	if (version_compare(TYPO3_version, '6.1.0', '<')) {

		/**
		* Setting up country, country subdivision, preferred language, first_name and last_name in fe_users table
		* Adjusting some maximum lengths to conform to specifications of payment gateways (ref.: Authorize.net)
		*/
		call_user_func($divClass . '::loadTCA', 'fe_users');
	}

	$GLOBALS['TCA']['fe_users']['columns']['username']['config']['eval'] = 'nospace,uniqueInPid,required';
	$GLOBALS['TCA']['fe_users']['columns']['name']['config']['max'] = '100';
	$GLOBALS['TCA']['fe_users']['columns']['company']['config']['max'] = '50';
	$GLOBALS['TCA']['fe_users']['columns']['city']['config']['max'] = '40';
	$GLOBALS['TCA']['fe_users']['columns']['country']['config']['max'] = '60';
	$GLOBALS['TCA']['fe_users']['columns']['zip']['config']['size'] = '15';
	$GLOBALS['TCA']['fe_users']['columns']['zip']['config']['max'] = '20';
	$GLOBALS['TCA']['fe_users']['columns']['email']['config']['max'] = '255';
	$GLOBALS['TCA']['fe_users']['columns']['telephone']['config']['max'] = '25';
	$GLOBALS['TCA']['fe_users']['columns']['fax']['config']['max'] = '25';
	$GLOBALS['TCA']['fe_users']['columns']['image']['config']['uploadfolder'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['uploadfolder'];
	$GLOBALS['TCA']['fe_users']['columns']['image']['config']['max_size'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageMaxSize'];
	$GLOBALS['TCA']['fe_users']['columns']['image']['config']['allowed'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['imageTypes'];

	$addColumnarray = array(
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
				'cols' => '48'
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
	);

	if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['forceGender']) {
		$addColumnarray['gender']['config']['items'] = array(
			array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.0', '0'),
			array('LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_users.gender.I.1', '1')
		);
	}

	call_user_func($emClass . '::addTCAcolumns', 'fe_users', $addColumnarray);

	$GLOBALS['TCA']['fe_users']['interface']['showRecordFieldList'] =
		preg_replace(
			'/(^|,)\s*country\s*(,|$)/', '$1zone,static_info_country,country,language$2',
			$GLOBALS['TCA']['fe_users']['interface']['showRecordFieldList']
		);
	$GLOBALS['TCA']['fe_users']['interface']['showRecordFieldList'] =
		preg_replace(
			'/(^|,)\s*title\s*(,|$)/',
			'$1gender,status,date_of_birth,house_no,title$2',
			$GLOBALS['TCA']['fe_users']['interface']['showRecordFieldList']
		);

	if (version_compare(TYPO3_version, '6.2.0', '<')) {
		$additionalFields = '';
		if (strpos($GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList'], 'first_name') === FALSE) {
			$additionalFields = 'first_name,middle_name,last_name,';
		}

		$GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList'] =
			preg_replace(
				'/(^|,)\s*title\s*(,|$)/', '$1gender,' . $additionalFields . 'cnum,status,title$2',
				$GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList']
			);
		$GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList'] .=
			',image,disable,date_of_birth,house_no,by_invitation,terms_acknowledged,tx_agency_password,lost_password';
		$GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList'] =
			preg_replace(
				'/(^|,)\s*country\s*(,|$)/', '$1zone,static_info_country,country,language,comments$2',
				$GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList']
			);
	}

	$GLOBALS['TCA']['fe_users']['types']['0']['showitem'] =
		preg_replace(
			'/(^|,)\s*country\s*(,|$)/', '$1 zone, static_info_country, country, language$2',
			$GLOBALS['TCA']['fe_users']['types']['0']['showitem']
		);
	$GLOBALS['TCA']['fe_users']['types']['0']['showitem'] =
		preg_replace(
			'/(^|,)\s*address\s*(,|$)/',
			'$1 cnum, status, date_of_birth, house_no, address$2',
			$GLOBALS['TCA']['fe_users']['types']['0']['showitem']
		);

	call_user_func(
		$emClass . '::addToAllTCAtypes',
		'fe_users',
		'comments, by_invitation, terms_acknowledged, lost_password',
		'',
		'after:www,'
	);

	$GLOBALS['TCA']['fe_users']['palettes']['2']['showitem'] = 'gender,--linebreak--,' . $GLOBALS['TCA']['fe_users']['palettes']['2']['showitem'];

	$GLOBALS['TCA']['fe_users']['ctrl']['thumbnail'] = 'image';

	$GLOBALS['TCA']['sys_agency_fe_users_limit_fe_groups'] = Array (
		'ctrl' => Array (
			'title' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_agency_fe_users_limit_fe_groups',
			'label' => 'codes',
			'default_sortby' => 'ORDER BY codes',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'delete' => 'deleted',
			'enablecolumns' => Array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
			),
			'iconfile' => call_user_func($emClass . '::extRelPath', AGENCY_EXT) . 'ext_icon.gif',
		)
	);

	if ( // Direct Mail tables exist but Direct Mail shall not be used
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] &&
		!call_user_func($emClass . '::isLoaded', 'direct_mail')
	) {
		if (!$GLOBALS['TCA']['sys_dmail_category']['columns']) {
			$GLOBALS['TCA']['sys_dmail_category'] = array(
				'ctrl' => array(
					'title' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_dmail_category',
					'label' => 'category',
					'tstamp' => 'tstamp',
					'crdate' => 'crdate',
					'cruser_id' => 'cruser_id',
					'languageField' => 'sys_language_uid',
					'transOrigPointerField' => 'l18n_parent',
					'transOrigDiffSourceField' => 'l18n_diffsource',
					'sortby' => 'sorting',
					'delete' => 'deleted',
					'enablecolumns' => array(
						'disabled' => 'hidden',
					),
					'iconfile' => call_user_func($emClass . '::extRelPath', AGENCY_EXT) . 'icon_tx_directmail_category.gif',
				)
			);

			// ******************************************************************
			// Categories
			// ******************************************************************
			$GLOBALS['TCA']['sys_dmail_category'] = Array (
				'ctrl' => $GLOBALS['TCA']['sys_dmail_category']['ctrl'],
				'interface' => Array (
						'showRecordFieldList' => 'hidden,category'
				),
				'feInterface' => $GLOBALS['TCA']['sys_dmail_category']['feInterface'],
				'columns' => Array (
					'sys_language_uid' => Array (
						'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
						'config' => Array (
							'type' => 'select',
							'foreign_table' => 'sys_language',
							'foreign_table_where' => 'ORDER BY sys_language.title',
							'items' => Array(
								Array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
								Array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
							)
						)
					),
					'l18n_parent' => Array (
						'displayCond' => 'FIELD:sys_language_uid:>:0',
						'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
						'config' => Array (
							'type' => 'select',
							'items' => Array (
								Array('', 0),
							),
							'foreign_table' => 'sys_dmail_category',
							'foreign_table_where' => 'AND sys_dmail_category.pid=###CURRENT_PID### AND sys_dmail_category.sys_language_uid IN (-1,0)',
						)
					),
					'l18n_diffsource' => Array (
						'config' => Array (
								'type' => 'passthrough'
						)
					),
					'hidden' => Array (
						'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
						'config' => Array (
							'type' => 'check',
							'default' => '0'
						)
					),
					'category' => Array (
						'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_dmail_category.category',
						'config' => Array (
							'type' => 'input',
							'size' => '30',
						)
					),
					'old_cat_number' => Array (
						'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:sys_dmail_category.old_cat_number',
						'l10n_mode' => 'exclude',
						'config' => Array (
							'type' => 'input',
							'size' => '2',
							'eval' => 'trim',
							'max' => '2',
						)
					),
				),
				'types' => Array (
					'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource,hidden;;1;;1-1-1, category')
				),
				'palettes' => Array (
					'1' => Array('showitem' => '')
				)
			);
		}

		// fe_users modified
		$tempCols = array(
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
					'allowed' => 'sys_dmail_category',
					'MM' => 'sys_dmail_feuser_category_mm',
					'foreign_table' => 'sys_dmail_category',
					'foreign_table_where' =>
						'AND sys_dmail_category.pid IN ' .
						'(###PAGE_TSCONFIG_IDLIST###) ORDER BY sys_dmail_category.sorting',
					'size' => 10,
// 					'selectedListStyle' => 'width:450px',
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

		call_user_func(
			$emClass . '::addTCAcolumns',
			'fe_users',
			$tempCols
		);
		if (version_compare(TYPO3_version, '6.2.0', '<')) {
			$GLOBALS['TCA']['fe_users']['feInterface']['fe_admin_fieldList'] .=
				',module_sys_dmail_newsletter,module_sys_dmail_category,module_sys_dmail_html';
		}
		call_user_func(
			$emClass . '::addToAllTCATypes',
			'fe_users','--div--;Direct mail,module_sys_dmail_newsletter;;;;1-1-1,module_sys_dmail_category,module_sys_dmail_html'
		);
	}

	$GLOBALS['TCA']['fe_groups_language_overlay'] = array(
		'ctrl' => array(
			'title' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_groups_language_overlay',
			'label' => 'title',
			'default_sortby' => 'ORDER BY fe_groups_uid',
			'sortby' => 'sorting',
			'delete' => 'deleted',
			'enablecolumns' => array(
				'disabled' => 'hidden'
			),
			'dynamicConfigFile' => call_user_func($emClass . '::extPath', AGENCY_EXT) . 'tca.php',
			'iconfile' => 'gfx/i/fe_groups.gif',
		)
	);
}


call_user_func($emClass . '::allowTableOnStandardPages', 'fe_groups_language_overlay');
call_user_func($emClass . '::addToInsertRecords', 'fe_groups_language_overlay');

if ( // Direct Mail tables exist but Direct Mail shall not be used
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AGENCY_EXT]['enableDirectMail'] &&
	!call_user_func($emClass . '::isLoaded', 'direct_mail')
) {
	call_user_func($emClass . '::allowTableOnStandardPages', 'sys_dmail_category');
	call_user_func($emClass . '::addToInsertRecords', 'sys_dmail_category');
}


