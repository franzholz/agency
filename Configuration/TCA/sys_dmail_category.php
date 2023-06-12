<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'agency';
$result = false;
$tableExists = false;
$table = 'sys_dmail_category';


if ( // Direct Mail tables exist but Direct Mail shall not be used
    !$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] ||
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail')
) {
    return $result;
}


if ( // Direct Mail tables exist but Direct Mail shall not be used
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['enableDirectMail'] &&
    !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail') &&
    is_object($GLOBALS['TYPO3_DB'])
) {
    $queryResult =
        $GLOBALS['TYPO3_DB']->admin_query(
            'SELECT * FROM INFORMATION_SCHEMA.TABLES ' .
            'WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'' . $table . '\''
        );
    $tableExists = $GLOBALS['TYPO3_DB']->sql_num_rows($queryResult) > 0;
}

if ($tableExists) {
    // ******************************************************************
    // Categories
    // ******************************************************************
    $result = array (
        'ctrl' => array (
            'title' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_dmail_category',
            'label' => 'category',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'languageField' => 'sys_language_uid',
            'sortby' => 'sorting',
            'delete' => 'deleted',
            'enablecolumns' => array (
                'disabled' => 'hidden',
            ),
            'iconfile' => 'EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'icon_tx_directmail_category.gif',
        ),
        'interface' => array (
                'showRecordFieldList' => 'hidden,category'
        ),
        'columns' => array (
            'sys_language_uid' => array(
                'label' => DIV2007_LANGUAGE_LGL . 'language',
                'config' => array(
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'sys_language',
                    'foreign_table_where' => 'ORDER BY sys_language.title',
                    'items' => array(
                        array(DIV2007_LANGUAGE_LGL . 'allLanguages', -1),
                        array(DIV2007_LANGUAGE_LGL . 'default_value', 0)
                    ),
                    'default' => 0
                )
            ),
            'l18n_parent' => array(
                'displayCond' => 'FIELD:sys_language_uid:>:0',
                'label' => DIV2007_LANGUAGE_LGL . 'l18n_parent',
                'config' => array(
                    'type' => 'select',
                    'items' => array(
                        array('', 0),
                    ),
                    'foreign_table' => 'sys_dmail_category',
                    'foreign_table_where' => 'AND sys_dmail_category.pid=###CURRENT_PID### AND sys_dmail_category.sys_language_uid IN (-1,0)',
                    'default' => 0
                )
            ),
            'l18n_diffsource' => array(
                'config' => array(
                    'type' => 'passthrough'
                )
            ),
            'hidden' => array(
                'label' => DIV2007_LANGUAGE_LGL . 'hidden',
                'config' => array(
                    'type' => 'check',
                    'default' => '0'
                )
            ),
            'category' => array (
                'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_dmail_category.category',
                'config' => array (
                    'type' => 'input',
                    'size' => '30',
                    'default' => ''
                )
            ),
            'old_cat_number' => array (
                'label' => 'LLL:EXT:' . $extensionKey . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_dmail_category.old_cat_number',
                'l10n_mode' => 'exclude',
                'config' => array (
                    'type' => 'input',
                    'size' => '2',
                    'eval' => 'trim',
                    'max' => '2',
                    'default' => ''
                )
            ),
        ),
        'types' => array(
            '0' => array('showitem' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden,--palette--;;1, category')
        ),
        'palettes' => array(
            '1' => array('showitem' => '')
        )
    );

    if (
        version_compare(TYPO3_version, '8.6.0', '<')
    ) {
        $result['ctrl']['transOrigPointerField'] = 'l18n_parent';
        $result['ctrl']['transOrigDiffSourceField'] = 'l18n_diffsource';
    }
}

return $result;
