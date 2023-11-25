<?php

namespace JambageCom\Agency\Domain;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the agency (Agency Registration) extension.
 *
 * setup configuration functions. former class tx_agency_tca
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Constants\Mode;

use JambageCom\Div2007\Utility\TableUtility;

class Tca implements \TYPO3\CMS\Core\SingletonInterface {

    public function init ($extKey, $theTable)
    {
        // nothing
    }

    /**
     * Fix contents of $GLOBALS['TCA']['tt_address']['feInterface']['fe_admin_fieldList']
     * The list gets broken when EXT:tt_address/tca.php is included twice
     *
     * @return void
     */
    protected function fixAddressFeAdminFieldList ($theTable)
    {
        if (
            $theTable == 'tt_address' &&
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_address') &&
            isset($GLOBALS['TCA']['tt_address']['feInterface']['fe_admin_fieldList'])
        ) {
            $fieldArray = array_unique(GeneralUtility::trimExplode(',', $GLOBALS['TCA']['tt_address']['feInterface']['fe_admin_fieldList'], 1));
            $fieldArray = array_diff($fieldArray, ['middle_first_name', 'last_first_name']);
            $fieldList = implode(',', $fieldArray);
            $fieldList = str_replace('first_first_name', 'first_name', $fieldList);
            $GLOBALS['TCA']['tt_address']['feInterface']['fe_admin_fieldList'] = $fieldList;
        }
    }

    public function getForeignTable ($theTable, $colName) {

        $result = false;

        if (
            isset($GLOBALS['TCA'][$theTable]) &&
            isset($GLOBALS['TCA'][$theTable]['columns']) &&
            isset($GLOBALS['TCA'][$theTable]['columns'][$colName])
        ) {
            $colSettings = $GLOBALS['TCA'][$theTable]['columns'][$colName];
            $colConfig = $colSettings['config'];
            if ($colConfig['foreign_table']) {
                $result = $colConfig['foreign_table'];
            }
        }
        return $result;
    }

    /**
    * Adds the fields coming from other tables via MM tables
    *
    * @param array  $dataArray: the record array
    * @return array  the modified data array
    */
    public function modifyTcaMMfields (
        $theTable,
        $dataArray,
        &$modArray
    )
    {
        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }

        $rcArray = $dataArray;

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $colName => $colSettings) {
            $colConfig = $colSettings['config'];

            // Configure preview based on input type
            switch ($colConfig['type']) {
                case 'select':
                    if (isset($colConfig['MM']) && isset($colConfig['foreign_table'])) {
                        $where = 'uid_local = ' . $dataArray['uid'];
                        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                            'uid_foreign',
                            $colConfig['MM'],
                            $where
                        );
                        $valueArray = [];

                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                            $valueArray[] = $row['uid_foreign'];
                        }
                        $rcArray[$colName] = implode(',', $valueArray);
                        $modArray[$colName] = $rcArray[$colName];
                    }
                    break;
            }
        }
        return $rcArray;
    }

    /**
    * Modifies the incoming data row
    * Adds checkboxes which have been unset. This means that no field will be present for them.
    * Fetches the former values of select boxes
    *
    * @param array  $dataArray: the input data array will be changed
    * @return void
    */
    public function modifyRow (
        $staticInfoObj,
        $theTable,
        &$dataArray,
        $fieldList,
        $bColumnIsCount = true
    )
    {
        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns']) ||
            !is_array($dataArray)
        ) {
            return false;
        }

        
        $dataFieldList = array_keys($dataArray);
        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $colName => $colSettings) {
            $colConfig = $colSettings['config'];
            if (
                !$colConfig ||
                !is_array($colConfig) ||
                !GeneralUtility::inList($fieldList, $colName)
            ) {
                continue;
            }

            if (
                isset($colConfig['maxitems']) &&
                $colConfig['maxitems'] > 1
            ) {
                $bMultipleValues = true;
            } else {
                $bMultipleValues = false;
            }

            switch ($colConfig['type']) {
                case 'group':
                    $bMultipleValues = true;
                    break;
                case 'select':
                    $value = $dataArray[$colName] ?? '';
                    if ($value == 'Array') {    // checkbox from which nothing has been selected
                        $dataArray[$colName] = $value = '';
                    }

                    if (
                        in_array($colName, $dataFieldList) &&
                        !empty($colConfig['MM']) &&
                        isset($value)
                    ) {
                        if ($value == '' || is_array($value)) {
                            // the value contains the count of elements from a mm table
                        } else if ($bColumnIsCount) {
                            $valuesArray = [];
                            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                                'uid_local,uid_foreign,sorting',
                                $colConfig['MM'],
                                'uid_local=' . intval($dataArray['uid']),
                                '',
                                'sorting'
                            );
                            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                                $valuesArray[] = $row['uid_foreign'];
                            }
                            $dataArray[$colName] = $valuesArray;
                        } else {
                            // the values from the mm table are already available as an array
                            $dataArray[$colName] = GeneralUtility::trimExplode (',', $value, 1);
                        }
                    }
                    break;
                case 'check':
                    if (
                        isset($colConfig['items']) &&
                        is_array($colConfig['items'])
                    ) {
                        $value = $dataArray[$colName] ?? '';
                        if(is_array($value)) {
                            $dataArray[$colName] = 0;
                            foreach ($value as $dec) {  // Combine values to one hexidecimal number
                                $dataArray[$colName] |= (1 << $dec);
                            }
                        }
                    } else {
                        if (
                            isset($dataArray[$colName]) &&
                            (
                                $dataArray[$colName] == 1 ||
                                (string) $dataArray[$colName] == 'on'
                            )
                        ) {
                            $dataArray[$colName] = 1;
                        } else {
                            $dataArray[$colName] = 0;
                        }
                    }
                    break;
                default:
                    // nothing
                    break;
            }

            if ($bMultipleValues) {
                $value = $dataArray[$colName] ?? '';

                if (!empty($value) && !is_array($value)) {
                    $dataArray[$colName] = GeneralUtility::trimExplode (',', $value, 1);
                }
            }
        }

        if (
            is_object($staticInfoObj) &&
            !empty($dataArray['static_info_country'])
        ) {
                // empty zone if it does not fit to the provided country
            $zoneArray = $staticInfoObj->initCountrySubdivisions($dataArray['static_info_country']);
            if (!isset($zoneArray[$dataArray['zone']])) {
                $dataArray['zone'] = '';
            }
        }
        return true;
    } // modifyRow

    /**
    * Replaces the markers in the foreign table where clause
    *
    * @param string  $whereClause: foreign table where clause
    * @param array  $colConfig: $TCA column configuration
    * @return string    foreign table where clause with replaced markers
    */
    public function replaceForeignWhereMarker (
        $whereClause,
        $colConfig
    )
    {
        $foreignWhere = $colConfig['foreign_table_where'] ?? '';

        if ($foreignWhere) {
            $pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
            $TSconfig = $pageTSConfig['TCEFORM.'][$theTable . '.'][$colName . '.'] ?? '';

            if ($TSconfig) {

                    // substitute whereClause
                $foreignWhere = str_replace('###PAGE_TSCONFIG_ID###', intval($TSconfig['PAGE_TSCONFIG_ID']), $foreignWhere);
                $foreignWhere =
                    str_replace(
                        '###PAGE_TSCONFIG_IDLIST###',
                        $GLOBALS['TYPO3_DB']->cleanIntList($TSconfig['PAGE_TSCONFIG_IDLIST']),
                        $foreignWhere
                    );
            }

            // have all markers in the foreign where been replaced?
            if (strpos($foreignWhere, '###') === false) {
                $orderbyPos = stripos($foreignWhere, 'ORDER BY');
                if ($orderbyPos !== false) {
                    $whereClause .= ' ' . substr($foreignWhere, 0, $orderbyPos);
                } else {
                    $whereClause .= ' ' . $foreignWhere;
                }
            }
        }

        return $whereClause;
    }
    
    protected function mergeItems(
        $itemArray,
        $labelItemArray
    ) {
        $result = [];
        if (empty($itemArray)) {
            $result = $labelItemArray;
        } else if (empty($labelItemArray)) {
            $result = $itemArray;
        } else {
            $keyArray = [];
            foreach ($itemArray as $valuesArray) {
                $keyArray[$valuesArray['value']] = $valuesArray['label'];
            }
            foreach ($labelItemArray as $labelValuesArray) {
                $keyArray[$labelValuesArray['value']] = $labelValuesArray['label'];
            }
            foreach ($keyArray as $key => $value) {
                $result[] = ['label' => $value, 'value' => $key];
            }
        }
        return $result;
    }

    /**
    * Adds form element markers from the Table Configuration Array to a marker array
    *
    * @param array $markerArray: the input and output marker array
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param array $row: the updated record
    * @param array $origRow: the original record as before the updates
    * @param string $cmd: the command CODE
    * @param string $cmdKey: the command key
    * @param string $theTable: the table in use
    * @param string $prefixId: the extension prefix id
    * @param boolean $viewOnly: whether the fields are presented for view only or for input/update
    * @param string $activity: 'preview', 'input' or 'email': parameter of stdWrap configuration
    * @param boolean $bChangesOnly: whether only updated fields should be presented
    * @param boolean $HSC: whether content should be htmlspecialchar'ed or not
    * @return void . $markerArray is filled with new markers
    */
    public function addMarkers (
        &$markerArray,
        $conf,
        \JambageCom\Agency\Api\Localization $languageObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        $row,
        $origRow,
        $cmd,
        $cmdKey,
        $theTable,
        $prefixId,
        $viewOnly = false,
        $activity = '',
        $bChangesOnly = false,
        $HSC = true
    )
    {
        $cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
        $xhtmlFix = \JambageCom\Div2007\Utility\HtmlUtility::determineXhtmlFix();
        $useXHTML = \JambageCom\Div2007\Utility\HtmlUtility::useXHTML();
        $css = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\Css::class);

        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }
        $useMissingFields = false;

        if ($activity == 'email') {
            $useMissingFields = true;
        }

        $mode = $controlData->getMode();
        $tablesObj = GeneralUtility::makeInstance(\JambageCom\Agency\Domain\Tables::class);
        $addressObj = $tablesObj->get('address');

        if ($bChangesOnly && is_array($origRow)) {
            $mrow = [];
            foreach ($origRow as $k => $v) {
                if ($v != $row[$k]) {
                    $mrow[$k] = $row[$k];
                }
            }
            $mrow['uid'] = $row['uid'];
            $mrow['pid'] = $row['pid'];
            $mrow['tstamp'] = $row['tstamp'];
            $mrow['username'] = $row['username'];
        } else {
            $mrow = $row;
        }

        $fields = !empty($cmdKey) && isset($conf[$cmdKey . '.']['fields']) ? $conf[$cmdKey . '.']['fields'] : '';

        if ($mode == Mode::PREVIEW) {
            if ($activity == '') {
                $activity = 'preview';
            }
        } else if (!$viewOnly && $activity != 'email') {
            $activity = 'input';
        }

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $colName => $colSettings) {
            if (
                GeneralUtility::inList($fields, $colName) ||
                $useMissingFields
            ) {
                $colConfig = $colSettings['config'];
                $colContent = '';

                if (!$bChangesOnly || isset($mrow[$colName])) {
                    $type = $colConfig['type'];

                    // check for a setup of wraps:
                    $stdWrap = [];
                    $bNotLast = false;
                    $bStdWrap = false;
                    // any item wraps set?
                    if (
                        isset($conf[$type . '.']) &&
                        isset($conf[$type . '.'][$activity . '.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$colName . '.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$colName . '.']['item.'])
                    ) {
                        $stdWrap = $conf[$type . '.'][$activity . '.'][$colName . '.']['item.'];
                        $bStdWrap = true;
                        if ($conf[$type . '.'][$activity . '.'][$colName . '.']['item.']['notLast']) {
                            $bNotLast = true;
                        }
                    }
                    $listWrap = [];
                    $bListWrap = false;

                    // any list wraps set?
                    if (
                        isset($conf[$type . '.']) &&
                        isset($conf[$type . '.'][$activity.'.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$colName . '.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$colName . '.']['list.'])
                    ) {
                        $listWrap = $conf[$type . '.'][$activity . '.'][$colName . '.']['list.'];
                        $bListWrap = true;
                    } else {
                        $listWrap['wrap'] = '<ul class="agency-multiple-checked-values">|</ul>';
                    }

                    if ($theTable == 'fe_users' && $colName == 'usergroup') {
                        $userGroupObj = $addressObj->getFieldObj('usergroup');
                    }

                    if (
                        $mode == Mode::PREVIEW ||
                        $viewOnly
                    ) {
                        // Configure preview based on input type

                        switch ($type) {
                            case 'input':
                            case 'text':
                                if (
                                    isset($mrow[$colName]) &&
                                    $mrow[$colName] != ''
                                ) {
                                    $colContent = ($HSC ? nl2br(htmlspecialchars($mrow[$colName])) : $mrow[$colName]);
                                }
                                break;

                            case 'check':
                                if (
                                    isset($colConfig['items'])
                                ) {
                                    if (!$bStdWrap) {
                                        $stdWrap['wrap'] = '<li>|</li>';
                                    }

                                    if (!$bListWrap) {
                                        $listWrap['wrap'] = '<ul class="agency-multiple-checked-values">|</ul>';
                                    }
                                    $bCheckedArray = [];
                                    if (
                                        isset($mrow[$colName]) &&
                                        (string) $mrow[$colName] != ''
                                    ) {
                                        if (
                                            isset($mrow[$colName]) &&
                                            is_array($mrow[$colName])
                                        ) {
                                            foreach($mrow[$colName] as $key => $value) {
                                                $bCheckedArray[$value] = true;
                                            }
                                        } else {
                                            foreach($colConfig['items'] as $key => $value) {
                                                $checked = ($mrow[$colName] & (1 << $key));
                                                if ($checked) {
                                                    $bCheckedArray[$key] = true;
                                                }
                                            }
                                        }
                                    }

                                    $count = 0;
                                    $checkedCount = 0;
                                    foreach($colConfig['items'] as $key => $value) {
                                        $count++;
                                        $checked = (!empty($bCheckedArray[$key]));

                                        if ($checked) {
                                            $checkedCount++;
                                            $label = $languageObj->getLabelFromString($colConfig['items'][$key]['label']);
                                            if ($HSC) {
                                                $label =
                                                    htmlspecialchars($label);
                                            }
                                            $label = ($checked ? $label : '');
                                            $colContent .= ((!$bNotLast || $checkedCount < count($bCheckedArray)) ?  $cObj->stdWrap($label, $stdWrap) : $label);
                                        }
                                    }
                                    $colContent = $cObj->stdWrap($colContent, $listWrap);
                                } else {
                                    if (
                                        isset($mrow[$colName]) &&
                                        (string) $mrow[$colName] != '' &&
                                        (string) $mrow[$colName] != '0'
                                    ) {
                                        $label = $languageObj->getLabel('yes');
                                    } else {
                                        $label = $languageObj->getLabel('no');
                                    }

                                    if ($HSC) {
                                        $label = htmlspecialchars($label);
                                    }
                                    $colContent = $label;
                                }
                                break;

                            case 'radio':
                                if (
                                    isset($mrow[$colName]) &&
                                    (string) $mrow[$colName] != ''
                                ) {
                                    $valuesArray = is_array($mrow[$colName]) ? $mrow[$colName] : explode(',', $mrow[$colName]);
                                    $textSchema = $theTable . '.' . $colName . '.I.';
                                    $labelItemArray = $languageObj->getItemsLL($textSchema, true);

                                    if (isset($conf['mergeLabels']) || !count($labelItemArray)) {
                                        if (isset($colConfig['itemsProcFunc'])) {
                                            $itemArray = GeneralUtility::callUserFunction($colConfig['itemsProcFunc'], $colConfig, $this, '');
                                        }
                                        $itemArray = $colConfig['items'];
                                        if (isset($conf['mergeLabels'])) {
                                            $itemArray = $this->mergeItems($itemArray, $labelItemArray);
                                        }
                                    } else {
                                        $itemArray = $labelItemArray;
                                    }

                                    if (is_array($itemArray)) {
                                        $itemKeyArray = $this->getItemKeyArray($itemArray);

                                        if (!$bStdWrap) {
                                            $stdWrap['wrap'] = '| ';
                                        }

                                        for ($i = 0; $i < count ($valuesArray); $i++) {
                                            $label = $languageObj->getLabelFromString($itemKeyArray[$valuesArray[$i]]['label']);
                                            if ($HSC) {
                                                $label = htmlspecialchars($label);
                                            }
                                            $colContent .= ((!$bNotLast || $i < count($valuesArray) - 1 ) ?  $cObj->stdWrap($label, $stdWrap) : $label);
                                        }
                                    }
                                }
                                break;

                            case 'select':
                                if (
                                    isset($mrow[$colName]) &&
                                    (
                                        !empty($mrow[$colName]) ||
                                        is_string($mrow[$colName]) &&
                                        $mrow[$colName] == '0'
                                    )
                                ) {
                                    $valuesArray = is_array($mrow[$colName]) ? $mrow[$colName] : explode(',', $mrow[$colName]);
                                    $textSchema = $theTable . '.' . $colName . '.I.';
                                    $labelItemArray = $languageObj->getItemsLL($textSchema, true);

                                    if (isset($conf['mergeLabels']) || !count($labelItemArray)) {
                                        if (isset($colConfig['itemsProcFunc'])) {
                                            $itemArray = GeneralUtility::callUserFunction($colConfig['itemsProcFunc'], $colConfig, $this, '');
                                        }
                                        $itemArray = $colConfig['items'] ?? [];
                                        if (isset($conf['mergeLabels'])) {
                                            $itemArray = $this->mergeItems($itemArray, $labelItemArray);
                                        }
                                    } else {
                                        $itemArray = $labelItemArray;
                                    }

                                    if (!$bStdWrap) {
                                        $stdWrap['wrap'] = '|<br' . $xhtmlFix . '>';
                                    }

                                    if (is_array($itemArray)) {
                                        $itemKeyArray = $this->getItemKeyArray($itemArray);
                                        for ($i = 0; $i < count($valuesArray); $i++) {
                                            if (empty($itemKeyArray)) {
                                                $label = $valuesArray[$i];
                                            } else {
                                                $label = $languageObj->getLabelFromString($itemKeyArray[$valuesArray[$i]]['label']);
                                            }
                                            if ($HSC) {
                                                $label = htmlspecialchars($label);
                                            }
                                            $colContent .= ((!$bNotLast || $i < count($valuesArray) - 1 ) ?  $cObj->stdWrap($label,$stdWrap) : $label);
                                        }
                                    }

                                    if (isset($colConfig['foreign_table'])) {
                                        $reservedValues = [];
                                        if (isset($userGroupObj) && is_object($userGroupObj)) {
                                            $reservedValues = $userGroupObj->getReservedValues($conf);
                                            $valuesArray = array_diff($valuesArray, $reservedValues);
                                        }
                                        $valuesArray = array_filter($valuesArray, 'strlen'); // removes null values
                                        $firstValue = current($valuesArray);

                                        if (!empty($firstValue) || count($valuesArray) > 1) {
                                            $titleField = $GLOBALS['TCA'][$colConfig['foreign_table']]['ctrl']['label'];
                                            $where = 'uid IN (' . implode(',', $valuesArray) . ')';

                                            $foreignRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                                '*',
                                                $colConfig['foreign_table'],
                                                $where
                                            );

                                            $languageUid = $controlData->getSysLanguageUid(
                                                $conf,
                                                'ALL',
                                                $colConfig['foreign_table']
                                            );

                                            if (
                                                is_array($foreignRows) &&
                                                count($foreignRows) > 0
                                            ) {
                                                for ($i = 0; $i < count($foreignRows); $i++) {
                                                    if ($theTable == 'fe_users' && $colName == 'usergroup') {
                                                        $foreignRows[$i] = $this->getUsergroupOverlay($conf, $controlData, $foreignRows[$i]);
                                                    } else if ($localizedRow = $GLOBALS['TSFE']->sys_page->getRecordOverlay($colConfig['foreign_table'], $foreignRows[$i], $languageUid)) {
                                                        $foreignRows[$i] = $localizedRow;
                                                    }
                                                    $text = $foreignRows[$i][$titleField];
                                                    if ($HSC) {
                                                        $text = htmlspecialchars($text);
                                                    }

                                                    $colContent .=
                                                        (($bNotLast || $i < count($foreignRows) - 1 ) ?
                                                            $cObj->stdWrap($text, $stdWrap) :
                                                            $text
                                                        );
                                                }
                                            }
                                        }
                                    }
                                }
                                break;

                            default:
                                // unsupported input type
                                $label = $languageObj->getLabel('unsupported');
                                if ($HSC)   {
                                    $label = htmlspecialchars($label);
                                }
                                $colContent .= $colConfig['type'] . ':' . $label;
                                break;
                        }
                    } else {
                        $itemArray = '';
                        // Configure inputs based on TCA type
                        if (in_array($type, ['check', 'radio', 'select'])) {
                            $valuesArray = [];

                            if (isset($mrow[$colName])) {
                                $valuesArray = is_array($mrow[$colName]) ? $mrow[$colName] : explode(',', $mrow[$colName]);
                            }

                            if (empty($valuesArray['0']) && isset($colConfig['default'])) {
                                $valuesArray[] = $colConfig['default'];
                            }
                            $textSchema = $theTable . '.' . $colName . '.I.';                            
                            $labelItemArray = $languageObj->getItemsLL($textSchema, true);

                            if ($conf['mergeLabels'] || !count($labelItemArray)) {
                                if (isset($colConfig['itemsProcFunc'])) {
                                    $itemArray = GeneralUtility::callUserFunction($colConfig['itemsProcFunc'], $colConfig, $this, '');
                                }
                                $itemArray = $colConfig['items'] ?? [];
                                if (isset($conf['mergeLabels'])) {
                                    $itemArray =
                                        $this->mergeItems($itemArray, $labelItemArray);
                                }
                            } else {
                                $itemArray = $labelItemArray;
                            }
                        }

                        switch ($type) {
                            case 'input':
                                $colContent = '<input ' .
                                'class="' . $css->getClassName($colName, 'input') . '" ' .
                                'type="input" name="FE[' . $theTable . '][' . $colName . ']"' .
                                    ' title="###TOOLTIP_' . (($cmd == 'invite') ? 'INVITATION_' : '') . $cObj->caseshift($colName, 'upper') . '###"' .
                                    ' size="' . ($colConfig['size'] ? $colConfig['size'] : 30) . '"';
                                if ($colConfig['max']) {
                                    $colContent .= ' maxlength="' . $colConfig['max'] . '"';
                                }
                                if (isset($colConfig['default'])) {
                                    $label = $languageObj->getLabelFromString($colConfig['default']);
                                    $label = htmlspecialchars($label);
                                    $colContent .= ' value="' . $label . '"';
                                }
                                $colContent .= $xhtmlFix . '>';
                                break;

                            case 'text':
                                $label = (isset($colConfig['default']) ? $languageObj->getLabelFromString($colConfig['default']) : '');
                                $label = htmlspecialchars($label);
                                $colContent = '<textarea id="' .                                    
                                    FrontendUtility::getClassName(
                                        $colName,
                                        $prefixId
                                    ) .
                                    '" class="' . $css->getClassName($colName, 'input') .
                                    '" name="FE[' . $theTable . '][' . $colName . ']"' .
                                    ' title="###TOOLTIP_' . (($cmd == 'invite') ? 'INVITATION_' : '') . $cObj->caseshift($colName, 'upper') . '###"' .
                                    ' cols="' . ($colConfig['cols'] ? $colConfig['cols'] : 30) . '"' .
                                    ' rows="' . ($colConfig['rows'] ? $colConfig['rows'] : 5) . '"' .
                                    '>' . $label . '</textarea>';
                                break;

                            case 'check':
                                $label = $languageObj->getLabel('tooltip_' . $colName);
                                $label = htmlspecialchars($label);

                                if (
                                    isset($itemArray) &&
                                    is_array($itemArray) &&
                                    !empty($itemArray)
                                ) {
                                    $uidText =
                                        FrontendUtility::getClassName(
                                            $colName,
                                            $prefixId
                                        );
                                    if (
                                        isset($mrow) &&
                                        is_array($mrow) &&
                                        !empty($mrow['uid'])
                                    ) {
                                        $uidText .= '-' . $mrow['uid'];
                                    }
                                    $colContent = '<ul id="' . $uidText . '" class="' . $css->getClassName('agency-multiple-checkboxes', 'ul') . '">';

                                    if (
                                        isset($mrow[$colName]) &&
                                        (
                                            $controlData->getSubmit() ||
                                            $controlData->getDoNotSave() ||
                                            $cmd == 'edit'
                                        )
                                    ) {
                                        $startVal = $mrow[$colName];
                                    } else {
                                        $startVal = $colConfig['default'] ?? '0';
                                    }

                                    $i = 0;
                                    foreach ($itemArray as $key => $value) {
                                        $i++;
                                        $checked = false;

                                        if (is_array($startVal)) {
                                            $checked = in_array($key, $startVal);
                                        } else {
                                            $checked = ($startVal & (1 << $key)) ? true : false;
                                        }
                                        $checkedHtml = '';
                                        if ($checked) {
                                            $checkedHtml = ($useXHTML ? ' checked="checked"' : ' checked');
                                        }

                                        $label = $languageObj->getLabelFromString($value['label']);
                                        $label = htmlspecialchars($label);
                                        $newContent = '<li><input type="checkbox"' .
                                            ' id="' . $uidText . '-' . $key .
                                            '" class="' . $css->getClassName($colName, 'input-' . $i) .
                                            '" name="FE[' . $theTable . '][' . $colName . '][]" value="' . $key . '"' .
                                            $checkedHtml . $xhtmlFix . '><label for="' . $uidText . '-' . $key . $xhtmlFix . '">' .
                                            $label .
                                            '</label></li>';
                                        $colContent .= $newContent;
                                    }
                                    $colContent .= '</ul>';
                                } else {
                                    $checkedHtml = ($useXHTML ? ' checked="checked"' : ' checked');
                                    $colContent =
                                        '<input type="checkbox"' .
                                        ' id="' .
                                        FrontendUtility::getClassName(
                                            $colName,
                                            $prefixId
                                        ) .
                                        '" class="' . $css->getClassName($colName, 'input') .
                                        '" name="FE[' . $theTable . '][' . $colName . ']" title="' .
                                        $label . '"' . (isset($mrow[$colName]) && $mrow[$colName] != '' ? ' value="on"' . $checkedHtml : '') .
                                        $xhtmlFix . '>';
                                }
                                break;

                            case 'radio':
                                if (
                                    isset($mrow[$colName]) &&
                                    (
                                        $controlData->getSubmit() ||
                                        $controlData->getDoNotSave() ||
                                        $cmd == 'edit'
                                    )
                                ) {
                                    $startVal = $mrow[$colName];
                                } else {
                                    $startVal = $colConfig['default'] ?? '';
                                }

                                if (empty($startVal) && isset($colConfig['items'])) {
                                    reset($colConfig['items']);
                                    list($startConf) = $colConfig['items'];
                                    $startVal = $startConf['value'];
                                }

                                if (!$bStdWrap) {
                                    $stdWrap['wrap'] = '| ';
                                }

                                if (isset($itemArray) && is_array($itemArray)) {
                                    $i = 0;
                                    $checkedHtml = ($useXHTML ? ' checked="checked"' : ' checked');

                                    foreach($itemArray as $key => $confArray) {
                                        $value = $confArray['value'];
                                        $label = $languageObj->getLabelFromString($confArray['label']);
                                        $label = htmlspecialchars($label);
                                        $itemOut = '<input type="radio"' .
                                        ' id="'.
                                        FrontendUtility::getClassName(
                                            $colName,
                                            $prefixId
                                        ) .
                                        '-' . $i . 
                                        '" class="' . $css->getClassName($colName, 'input') .
                                        '" name="FE[' . $theTable . '][' . $colName . ']"' .
                                            ' value="' . $value . '" ' . ($value == $startVal ? $checkedHtml : '') . $xhtmlFix . '>' .
                                            '<label for="' .
                                            FrontendUtility::getClassName(
                                                $colName,
                                                $prefixId
                                            ) .
                                            '-' . $i . '">' . $label . '</label>';
                                        $i++;
                                        $colContent .=
                                            ((!$bNotLast || $i < count($itemArray) - 1 ) ?
                                            $cObj->stdWrap($itemOut, $stdWrap) :
                                            $itemOut);
                                    }
                                }
                                break;

                            case 'select':
                                $colContent ='';
                                $attributeMultiple = '';
                                $attributeClassName = '';
                                $checkedHtml =  ($useXHTML ? ' checked="checked"' : ' checked');
                                $selectedHtml = ($useXHTML ? ' selected="selected"' : ' selected');

                                if (
                                    isset($colConfig['maxitems']) &&
                                    $colConfig['maxitems'] > 1 &&
                                    (
                                        $colName != 'usergroup' ||
                                        !empty($conf['allowMultipleUserGroupSelection']) ||
                                        $theTable != 'fe_users'
                                    )
                                ) {
                                    if ($useXHTML) {
                                        $attributeMultiple = ' multiple="multiple"';
                                    } else {
                                        $attributeMultiple = ' multiple';
                                    }
                                }

                                $attributeIdName = ' id="' .
                                    FrontendUtility::getClassName(
                                        $colName,
                                        $prefixId
                                    ) .
                                    '" name="FE[' . $theTable . '][' . $colName . ']';

                                if ($attributeMultiple != '') {
                                    $attributeIdName .= '[]';
                                }
                                $attributeIdName .= '"';

                                $attributeTitle = ' title="###TOOLTIP_' . (($cmd == 'invite') ? 'INVITATION_' : '') . $cObj->caseshift($colName, 'upper') . '###"';

                                $attributeClassName = $css->getClassName($colName, 'input');

                                if (
                                    isset($colConfig['renderMode']) &&
                                    $colConfig['renderMode'] == 'checkbox'
                                ) {
                                    $attributeClass = ' class="' . $attributeClassName . '"';
                                    $colContent .= '
                                        <input' . $attributeIdName . ' value="" type="hidden"' . $attributeClass . $xhtmlFix . '>';

                                    $attributeClass = '';
                                    if (
                                        $attributeMultiple != ''
                                    ) {
                                        $attributeClassName = $css->getClassName('multiple-checkboxes', '');
                                        $attributeClass = ' class="' . $attributeClassName . '"';
                                    }

                                    $colContent .= '
                                        <div ';
                                    if ($attributeClass != '') {
                                        $colContent .= $attributeClass;
                                    }
                                    $colContent .=
                                        $attributeTitle .
                                        $xhtmlFix . '>';
                                } else {
                                    if (
                                        $attributeMultiple != ''
                                    ) {
                                        $attributeClassName .= ' ' . $css->getClassName('multiple-select', '');
                                    }
                                    $attributeClass = ' class="' . $attributeClassName . '"';
                                    $colContent .= '<select' . $attributeIdName;
                                    if ($attributeClass != '') {
                                        $colContent .= $attributeClass;
                                    }

                                    $colContent .=
                                        $attributeMultiple .
                                        $attributeTitle .
                                        '>';
                                }

                                if (is_array($itemArray)) {
                                    $itemArray = $this->getItemKeyArray($itemArray);
                                    $i = 0;

                                    foreach ($itemArray as $k => $item) {
                                        $label = $languageObj->getLabelFromString($item['label'], true);
                                        $label = htmlspecialchars($label);
                                        if (
                                            isset($colConfig['renderMode']) &&
                                            $colConfig['renderMode'] == 'checkbox'
                                        ) {
                                            $colContent .= '<div class="' . $css->getClassName($colName, 'divInput-' . ($i + 1)) . '">' .
                                            '<input class="' .
                                            $css->getClassName('checkbox-checkboxes', 'input') . ' ' . $css->getClassName($colName, 'input-' . ($i + 1)) .
                                             '" id="' .
                                            FrontendUtility::getClassName(
                                                $colName,
                                                $prefixId
                                            ) .
                                            '-' . $i . '" name="FE[' . $theTable . '][' . $colName . '][' . $k . ']" value="' . $k .
                                            '" type="checkbox"  ' . (in_array($k, $valuesArray) ? $checkedHtml : '') . $xhtmlFix . '></div>' .
                                                '<div class="viewLabel ' . $css->getClassName($colName, 'divLabel') . '">' . 
                                                '<label for="' .
                                                FrontendUtility::getClassName(
                                                    $colName,
                                                    $prefixId
                                                ) .
                                                '-' . $i . '"' .
                                                ' class="' .  $css->getClassName($colName, 'label') . '"' .
                                                '>' . $label . '</label></div>';
                                        } else {
                                            $colContent .= '<option value="' . $k . '" ' . (in_array($k, $valuesArray) ? $selectedHtml : '') . '>' . $label . '</option>';
                                        }
                                        $i++;
                                    }

                                    if (
                                        isset($colConfig['renderMode']) &&
                                        $colConfig['renderMode'] == 'checkbox'
                                    ) {
                                        $colContent .= '</div>';
                                    }
                                }

                                if (
                                    !empty($colConfig['foreign_table']) &&
                                    isset($GLOBALS['TCA'][$colConfig['foreign_table']])
                                ) {
                                    $titleField = $GLOBALS['TCA'][$colConfig['foreign_table']]['ctrl']['label'];
                                    $reservedValues = [];
                                    $whereClause = '1=1';

                                    if (
                                        isset($userGroupObj) &&
                                        is_object($userGroupObj)
                                    ) {
                                        $reservedValues = $userGroupObj->getReservedValues($conf);
                                        $foreignTable = $this->getForeignTable($theTable, $colName);
                                        $whereClause = $userGroupObj->getAllowedWhereClause(
                                            $foreignTable,
                                            $controlData->getPid(),
                                            $conf,
                                            $cmdKey
                                        );
                                    }

                                    if (
                                        $conf['useLocalization'] &&
                                        $GLOBALS['TCA'][$colConfig['foreign_table']] &&
                                        $GLOBALS['TCA'][$colConfig['foreign_table']]['ctrl']['languageField'] &&
                                        $GLOBALS['TCA'][$colConfig['foreign_table']]['ctrl']['transOrigPointerField']
                                    ) {
                                        $whereClause .= ' AND ' . $GLOBALS['TCA'][$colConfig['foreign_table']]['ctrl']['transOrigPointerField'] . '=0';
                                    }

                                    if (
                                        $colName == 'module_sys_dmail_category' &&
                                        $colConfig['foreign_table'] == 'sys_dmail_category' &&
                                        $conf['module_sys_dmail_category_PIDLIST']
                                    ) {
                                        $languageUid =
                                            $controlData->getSysLanguageUid(
                                                $conf,
                                                'ALL',
                                                $colConfig['foreign_table']
                                            );
                                        $tmpArray =
                                            GeneralUtility::trimExplode(
                                                ',',
                                                $conf['module_sys_dmail_category_PIDLIST']
                                            );
                                        $pidArray = [];
                                        foreach ($tmpArray as $v) {
                                            if (is_numeric($v)) {
                                                $pidArray[] = $v;
                                            }
                                        }
                                        $whereClause .= ' AND sys_dmail_category.pid IN (' . implode(',', $pidArray) . ')' . ($conf['useLocalization'] ? ' AND sys_language_uid=' . intval($languageUid) : '');
                                    }
                                    $whereClause .= TableUtility::enableFields($colConfig['foreign_table']);
                                    $whereClause = $this->replaceForeignWhereMarker($whereClause,  $colConfig);
                                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $colConfig['foreign_table'], $whereClause, '', $GLOBALS['TCA'][$colConfig['foreign_table']]['ctrl']['sortby'] ?? '');

                                    if (
                                        !in_array(
                                            $colName,
                                            $controlData->getRequiredArray()
                                        )
                                    ) {
                                        if (
                                            $colConfig['renderMode'] == 'checkbox' ||
                                            $colContent
                                        ) {
                                            // nothing
                                        } else {
                                            $colContent .= '<option value=""' . ($valuesArray[0] ? '' : $selectedHtml) . '></option>';
                                        }
                                    }

                                    $selectedValue = false;
                                    $i = 0;

                                    while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                                        $i++;
                                            // Handle usergroup case
                                        if (
                                            $colName == 'usergroup' &&
                                            isset($userGroupObj) &&
                                            is_object($userGroupObj)
                                        ) {
                                            if (!in_array($row2['uid'], $reservedValues)) {
                                                $row2 = $this->getUsergroupOverlay($conf, $controlData, $row2);
                                                $titleText = htmlspecialchars($row2[$titleField]);
                                                $selected = (in_array($row2['uid'], $valuesArray) ? $selectedHtml : '');
                                                if (
                                                    !$conf['allowMultipleUserGroupSelection'] &&
                                                    $selectedValue
                                                ) {
                                                    $selected = '';
                                                }
                                                $selectedValue = ($selected ? true: $selectedValue);

                                                if (
                                                    isset($colConfig['renderMode']) &&
                                                    $colConfig['renderMode'] == 'checkbox'
                                                ) {
                                                    $colContent .= '<div class="' . $css->getClassName($colName, 'divInput-' . $i) . '">';
                                                    $colContent .= '<input  class="' .
                                                    $css->getClassName($colName, 'input-' . ($i)) .
                                                    '" id="'.
                                                    FrontendUtility::getClassName(
                                                        $colName,
                                                        $prefixId
                                                    ) .
                                                    '-' . $row2['uid'] . '" name="FE[' . $theTable . '][' . $colName . '][' . $row2['uid'] . ']" value="' . $row2['uid'] .
                                                    '" type="checkbox"' . ($selected ? $checkedHtml : '') . $xhtmlFix . '></div>' .
                                                    '<div class="viewLabel ' . $css->getClassName($colName, 'divLabel-' . $i) . '"><label for="' .
                                                    FrontendUtility::getClassName(
                                                        $colName,
                                                        $prefixId
                                                    ) . '-' . $row2['uid'] . 
                                                    '" class="' . $css->getClassName($colName, 'label') . '">' . $titleText . '</label></div>';
                                                } else {
                                                    $colContent .= '<option value="' . $row2['uid'] . '"' . $selected . '>' . $titleText . '</option>';
                                                }
                                            }
                                        } else {
                                            $languageUid = $controlData->getSysLanguageUid(
                                                $conf,
                                                'ALL',
                                                $colConfig['foreign_table']
                                            );
                                            if ($localizedRow =
                                                $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                                                    $colConfig['foreign_table'],
                                                    $row2,
                                                    $languageUid
                                                )
                                            ) {
                                                $row2 = $localizedRow;
                                            }
                                            $titleText = htmlspecialchars($row2[$titleField]);

                                            if ($colConfig['renderMode'] == 'checkbox') {
                                                $colContent .= '<div class="' . $css->getClassName($colName, 'divInput-' . $i) . '">';
                                                $colContent .= '<input class="' .
                                                $css->getClassName(
                                                    'checkbox'
                                                ) . 
                                                '" id="'.
                                                FrontendUtility::getClassName(
                                                    $colName,
                                                    $prefixId
                                                ) .
                                                '-' . $row2['uid'] . '" name="FE[' . $theTable . '][' .  $colName . '][' . $row2['uid'] . ']" value="' . $row2['uid'] . '" type="checkbox"' . (in_array($row2['uid'],  $valuesArray) ? $checkedHtml : '') . $xhtmlFix . '></div>' .
                                                '<div class="viewLabel ' . $css->getClassName($colName, 'divLabel-' . $i) . '"><label for="' .
                                                FrontendUtility::getClassName(
                                                    $colName,
                                                    $prefixId
                                                ) . '-' . $row2['uid'] . '">' . $titleText . '</label></div>';
                                            } else {
                                                $colContent .= '<option value="' . $row2['uid'] . '"' . (in_array($row2['uid'], $valuesArray) ? $selectedHtml : '') . '>' . $titleText . '</option>';
                                            }
                                        }
                                    }
                                }

                                if (
                                    isset($colConfig['renderMode']) &&
                                    $colConfig['renderMode'] == 'checkbox'
                                ) {
                                    $colContent .= '</div>';
                                } else {
                                    $colContent .= '</select>';
                                }
                                break;

                            default:
                                $colContent .= $colConfig['type'] . ':' . $languageObj->getLabel('unsupported');
                                break;
                        }
                    }

                    if (isset($userGroupObj)) {
                        unset($userGroupObj);
                    }
                } else {
                    $colContent = '';
                }

                if ($mode == Mode::PREVIEW || $viewOnly) {
                    $markerArray['###TCA_INPUT_VALUE_' . $colName . '###'] = $colContent;
                }
                $markerArray['###TCA_INPUT_' . $colName . '###'] = $colContent;
            } else {
                // field not in form fields list
            }
        }
    }

    /**
    * Transfers the item array to one where the key corresponds to the value
    * @param    array   array of selectable items like found in TCA
    * @ return  array   array of selectable items with correct key
    */
    public function getItemKeyArray ($itemArray)
    {
        $rc = [];

        if (is_array($itemArray)) {
            foreach ($itemArray as $k => $row) {
                $key = $row['value'];
                $rc[$key] = $row;
            }
        }
        return $rc;
    }   // getItemKeyArray

    /**
    * Returns the relevant usergroup overlay record fields
    * Adapted from t3lib_page.php
    *
    * @param array $controlData: the object of the control data
    * @param    mixed       If $usergroup is an integer, it's the uid of the usergroup overlay record and thus the usergroup overlay record is returned. If $usergroup is an array, it's a usergroup record and based on this usergroup record the language overlay record is found and gespeichert.OVERLAYED before the usergroup record is returned.
    * @param    integer     Language UID if you want to set an alternative value to $this->controlData->sys_language_content which is default. Should be >=0
    * @return   array       usergroup row which is overlayed with language_overlay record (or the overlay record alone)
    */
    public function getUsergroupOverlay (
        $conf,
        \JambageCom\Agency\Request\Parameters $controlData,
        $usergroup,
        $languageUid = ''
    )
    {
        $row = false;

        // Initialize:
        if ($languageUid == '') {
            $languageUid =
                $controlData->getSysLanguageUid(
                    $conf,
                    'ALL',
                    'fe_groups_language_overlay'
                );
        }

        // If language UID is different from zero, do overlay:
        if ($languageUid) {
            $fieldArr = ['title'];
            if (is_array($usergroup)) {
                $fe_groups_uid = $usergroup['uid'];
                // Was the whole record
                $fieldArr = array_intersect($fieldArr, array_keys($usergroup));
                // Make sure that only fields which exist in the incoming record are overlaid!
            } else {
                $fe_groups_uid = $usergroup;
                // Was the uid
            }

            if (count($fieldArr)) {
                $cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();

                $whereClause = 'fe_group=' . intval($fe_groups_uid) . ' ' .
                    'AND sys_language_uid=' . intval($languageUid) . ' ' .
                     TableUtility::enableFields('fe_groups_language_overlay');
                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',', $fieldArr), 'fe_groups_language_overlay', $whereClause);
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
                    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                }
            }
        }

            // Create output:
        if (is_array($usergroup)) {
            return is_array($row) ? array_merge($usergroup, $row) : $usergroup;
            // If the input was an array, simply overlay the newfound array and return...
        } else {
            return is_array($row) ? $row : []; // always an array in return
        }
    }   // getUsergroupOverlay
}


