<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$typoVersion = tx_div2007_core::getTypoVersion();

if (
	TYPO3_MODE == 'BE' &&
	!$loadTcaAdditions
) {
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/', 'Agency Registration');

	if ($typoVersion < 6001000) {

		t3lib_div::loadTCA('tt_content');
	}

	$listType = $_EXTKEY . '';
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
	t3lib_extMgm::addPiFlexFormValue($listType, 'FILE:EXT:' . $_EXTKEY . '/pi/flexform_ds_pi.xml');
	t3lib_extMgm::addPlugin(array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:tt_content.list_type', $listType), 'list_type');
}

if (!t3lib_extMgm::isLoaded('sr_feuser_register')) {

	if ($typoVersion < 6001000) {

		/**
		* Setting up country, country subdivision, preferred language, first_name and last_name in fe_users table
		* Adjusting some maximum lengths to conform to specifications of payment gateways (ref.: Authorize.net)
		*/
		t3lib_div::loadTCA('fe_users');
	}

	$TCA['fe_users']['columns']['username']['config']['eval'] = 'nospace,uniqueInPid,required';
	$TCA['fe_users']['columns']['name']['config']['max'] = '100';
	$TCA['fe_users']['columns']['company']['config']['max'] = '50';
	$TCA['fe_users']['columns']['city']['config']['max'] = '40';
	$TCA['fe_users']['columns']['country']['config']['max'] = '60';
	$TCA['fe_users']['columns']['zip']['config']['size'] = '15';
	$TCA['fe_users']['columns']['zip']['config']['max'] = '20';
	$TCA['fe_users']['columns']['email']['config']['max'] = '255';
	$TCA['fe_users']['columns']['telephone']['config']['max'] = '25';
	$TCA['fe_users']['columns']['fax']['config']['max'] = '25';
	$TCA['fe_users']['columns']['image']['config']['uploadfolder'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['uploadfolder'];
	$TCA['fe_users']['columns']['image']['config']['max_size'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['imageMaxSize'];
	$TCA['fe_users']['columns']['image']['config']['allowed'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['imageTypes'];

	$addColumnarray = array(
		'cnum' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.cnum',
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
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.static_info_country',
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
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.zone',
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
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.language',
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
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.date_of_birth',
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
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.gender',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.gender.I.99', '99'),
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.gender.I.0', '0'),
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.gender.I.1', '1')
				),
			)
		),
		'status' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.status',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.status.I.0', '0'),
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.status.I.1', '1'),
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.status.I.2', '2'),
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.status.I.3', '3'),
					array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.status.I.4', '4'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'comments' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.comments',
			'config' => array(
				'type' => 'text',
				'rows' => '5',
				'cols' => '48'
			)
		),
		'by_invitation' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.by_invitation',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'terms_acknowledged' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.terms_acknowledged',
			'config' => array(
				'type' => 'check',
				'default' => '0',
				'readOnly' => '1',
			)
		),
		'token' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.token',
			'config' => array(
				'type' => 'text',
				'rows' => '1',
				'cols' => '32'
			)
		),
		'tx_agency_password' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.tx_agency_password',
			'config' => array (
				'type' => 'passthrough',
			)
		),
		'house_no' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.house_no',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '20'
			)
		),
		'lost_password' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.lost_password',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
	);

	if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['forceGender']) {
		$addColumnarray['gender']['config']['items'] = array(
			array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.gender.I.0', '0'),
			array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.gender.I.1', '1')
		);
	}

	t3lib_extMgm::addTCAcolumns('fe_users', $addColumnarray);

	$TCA['fe_users']['interface']['showRecordFieldList'] =
		preg_replace(
			'/(^|,)\s*country\s*(,|$)/', '$1zone,static_info_country,country,language$2',
			$TCA['fe_users']['interface']['showRecordFieldList']
		);
	$TCA['fe_users']['interface']['showRecordFieldList'] =
		preg_replace(
			'/(^|,)\s*title\s*(,|$)/',
			'$1gender,status,date_of_birth,house_no,title$2',
			$TCA['fe_users']['interface']['showRecordFieldList']
		);

	if ($typoVersion < 6002000) {
		$additionalFields = '';
		if (strpos($TCA['fe_users']['feInterface']['fe_admin_fieldList'], 'first_name') === FALSE) {
			$additionalFields = 'first_name,middle_name,last_name,';
		}

		$TCA['fe_users']['feInterface']['fe_admin_fieldList'] =
			preg_replace(
				'/(^|,)\s*title\s*(,|$)/', '$1gender,' . $additionalFields . 'cnum,status,title$2',
				$TCA['fe_users']['feInterface']['fe_admin_fieldList']
			);
		$TCA['fe_users']['feInterface']['fe_admin_fieldList'] .=
			',image,disable,date_of_birth,house_no,by_invitation,terms_acknowledged,tx_agency_password,lost_password';
		$TCA['fe_users']['feInterface']['fe_admin_fieldList'] =
			preg_replace(
				'/(^|,)\s*country\s*(,|$)/', '$1zone,static_info_country,country,language,comments$2',
				$TCA['fe_users']['feInterface']['fe_admin_fieldList']
			);
	}

	$TCA['fe_users']['types']['0']['showitem'] =
		preg_replace(
			'/(^|,)\s*country\s*(,|$)/', '$1 zone, static_info_country, country, language$2',
			$TCA['fe_users']['types']['0']['showitem']
		);
	$TCA['fe_users']['types']['0']['showitem'] =
		preg_replace(
			'/(^|,)\s*address\s*(,|$)/',
			'$1 cnum, status, date_of_birth, house_no, address$2',
			$TCA['fe_users']['types']['0']['showitem']
		);

	t3lib_extMgm::addToAllTCAtypes(
		'fe_users',
		'comments, by_invitation, terms_acknowledged, lost_password',
		'',
		'after:www,'
	);

	$TCA['fe_users']['palettes']['2']['showitem'] = 'gender,--linebreak--,' . $TCA['fe_users']['palettes']['2']['showitem'];


	$TCA['fe_users']['ctrl']['thumbnail'] = 'image';

	if ( // Direct Mail tables exist but Direct Mail shall not be used
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['enableDirectMail'] &&
		!t3lib_extMgm::isLoaded('direct_mail')
	) {
		if (!$GLOBALS['TCA']['sys_dmail_category']['columns']) {
			$GLOBALS['TCA']['sys_dmail_category'] = array(
				'ctrl' => array(
					'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:sys_dmail_category',
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
					'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_directmail_category.gif',
					)
			);

			// ******************************************************************
			// Categories
			// ******************************************************************
			$GLOBALS['TCA']['sys_dmail_category'] = Array (
				'ctrl' => $TCA['sys_dmail_category']['ctrl'],
				'interface' => Array (
						'showRecordFieldList' => 'hidden,category'
				),
				'feInterface' => $TCA['sys_dmail_category']['feInterface'],
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
						'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:sys_dmail_category.category',
						'config' => Array (
							'type' => 'input',
							'size' => '30',
						)
					),
					'old_cat_number' => Array (
						'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:sys_dmail_category.old_cat_number',
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
				'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.module_sys_dmail_newsletter',
				'exclude' => '1',
				'config'=>array(
					'type'=>'check'
					)
				),
			'module_sys_dmail_category' => array(
				'label'=>'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.module_sys_dmail_category',
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
				'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_users.module_sys_dmail_html',
				'exclude' => '1',
				'config' => array(
					'type'=>'check'
				)
			)
		);

		t3lib_extMgm::addTCAcolumns('fe_users', $tempCols);
		if ($typoVersion < 6002000) {
			$TCA['fe_users']['feInterface']['fe_admin_fieldList'] .=
				',module_sys_dmail_newsletter,module_sys_dmail_category,module_sys_dmail_html';
		}
		t3lib_extMgm::addToAllTCATypes(
			'fe_users','--div--;Direct mail,module_sys_dmail_newsletter;;;;1-1-1,module_sys_dmail_category,module_sys_dmail_html'
		);
	}

	$TCA['fe_groups_language_overlay'] = array(
		'ctrl' => array(
			'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:fe_groups_language_overlay',
			'label' => 'title',
			'default_sortby' => 'ORDER BY fe_groups_uid',
			'sortby' => 'sorting',
			'delete' => 'deleted',
			'enablecolumns' => array(
				'disabled' => 'hidden'
			),
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
			'iconfile' => 'gfx/i/fe_groups.gif',
		)
	);
	t3lib_extMgm::allowTableOnStandardPages('fe_groups_language_overlay');
	t3lib_extMgm::addToInsertRecords('fe_groups_language_overlay');
}

?>