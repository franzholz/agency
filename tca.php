<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (!t3lib_extMgm::isLoaded('sr_feuser_register')) {

	$GLOBALS['TCA']['fe_groups_language_overlay'] = array(
		'ctrl' => $GLOBALS['TCA']['fe_groups_language_overlay']['ctrl'],
		'interface' => array(
			'showRecordFieldList' => 'hidden,fe_group,sys_language_uid,title'
		),
		'columns' => array(
			'hidden' => array(
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
				'config' => array(
					'type' => 'check',
					'default' => '0'
				)
			),
			'fe_group' => array(
				'exclude' => 0,
				'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_groups',
				'config' => array(
					'type' => 'select',
					'foreign_table' => 'fe_groups'
				)
			),
			'sys_language_uid' => array(
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
				'config' => array(
					'type' => 'select',
					'foreign_table' => 'sys_language',
					'foreign_table_where' => 'ORDER BY sys_language.title'
				)
			),
			'title' => array(
				'exclude' => 0,
				'label' => 'LLL:EXT:' . AGENCY_EXT . '/locallang_db.xml:fe_groups_language_overlay.title',
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
}

