<?php

declare(strict_types=1);

namespace JambageCom\Agency\Database;

/***************************************************************
*  Copyright notice
*
*  (c) 2025 Franz Holzinger (franz@ttproducts.de)
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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use SJBR\StaticInfoTables\PiBaseApi;

use JambageCom\Div2007\Api\Css;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;
use JambageCom\Div2007\Utility\TableUtility;

use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Constants\Mode;
use JambageCom\Agency\Database\Field\Category;
use JambageCom\Agency\Database\Field\UserGroup;
use JambageCom\Agency\Domain\Repository\FrontendGroupRepository;
use JambageCom\Agency\Domain\Repository\FrontendUserRepository;
use JambageCom\Agency\Request\Parameters;


class Tca implements SingletonInterface
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly FrontendUserRepository $frontendUserRepository,
        protected readonly FrontendGroupRepository $frontendGroupRepository,
    ) {
    }

    public function init($extKey, $theTable): void
    {
        // nothing
    }

    public function getForeignTable($theTable, $columnName)
    {
        $result = false;

        if (
            isset($GLOBALS['TCA'][$theTable]) &&
            isset($GLOBALS['TCA'][$theTable]['columns']) &&
            isset($GLOBALS['TCA'][$theTable]['columns'][$columnName])
        ) {
            $columnSettings = $GLOBALS['TCA'][$theTable]['columns'][$columnName];
            $columnConfig = $columnSettings['config'];
            if ($columnConfig['foreign_table']) {
                $result = $columnConfig['foreign_table'];
            }
        }
        return $result;
    }

    public function getRelatedUids(
        string $table,
        string $foreignField,
        string $localField,
        array $uidArray,
        string $orderBy = ''
    ): array {
        $valueArray = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $result = $queryBuilder
            ->select($localField)
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    $foreignField,
                    $queryBuilder->createNamedParameter(
                        $uidArray,
                        ArrayParameterType::INTEGER)
                )
            );

        if ($orderBy != '') {
            $queryBuilder->orderBy($orderBy);
        }

        $result = $queryBuilder->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $valueArray[] = $row[$localField];
        }

        return $valueArray;
    }

    public function getRowsByUids(string $table, string $selectFields, array $uidArray, string $orderBy = '')
    {
        $rows = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $result = $queryBuilder
            ->select($selectFields)
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $uidArray,
                        ArrayParameterType::INTEGER)
                )
            );

        if ($orderBy != '') {
            $queryBuilder->orderBy($orderBy);
        }

        $result = $queryBuilder->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $rows[] = $row;
        }

        return $rows;
    }


    /**
    * Adds the fields coming from other tables via MM tables
    *
    * @param array  $dataArray: the record array
    * @return array  the modified data array
    */
    public function modifyTcaMMfields(
        $theTable,
        $dataArray,
        &$modArray
    ) {
        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }

        $rcArray = $dataArray;

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $columnName => $columnSettings) {
            $columnConfig = $columnSettings['config'];

            // Configure preview based on input type
            switch ($columnConfig['type']) {
                case 'select':
                    if (
                        isset($columnConfig['MM']) &&
                        isset($columnConfig['foreign_table'])
                    ) {
                        // $where = 'uid_local = ' . $dataArray['uid'];
                        $valueArray =
                            $this->getRelatedUids(
                                $columnConfig['MM'],
                                'uid_foreign',
                                'uid_local',
                                [
                                    'uid_foreign' => $dataArray['uid']
                                ]
                            );
                        $rcArray[$columnName] = implode(',', $valueArray);
                        $modArray[$columnName] = $rcArray[$columnName];
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
    public function modifyRow(
        array &$dataArray,
        ?PiBaseApi $staticInfoObj,
        string $theTable,
        string $fieldList,
        bool $usePrivacyPolicy = false,
        bool $bColumnIsCount = true
    ): bool {
        debug ($dataArray, 'modifyRow Start $dataArray');
        debug ($fieldList, 'modifyRow $fieldList');
        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns']) ||
            !is_array($dataArray)
        ) {
            return false;
        }


        $dataFieldList = array_keys($dataArray);
        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $columnName => $columnSettings) {
            $columnConfig = $columnSettings['config'];
            if (
                !$columnConfig ||
                !is_array($columnConfig) ||
                !GeneralUtility::inList($fieldList, $columnName)
            ) {
                continue;
            }

            if (
                isset($columnConfig['maxitems']) &&
                $columnConfig['maxitems'] > 1
            ) {
                $bMultipleValues = true;
            } else {
                $bMultipleValues = false;
            }

            switch ($columnConfig['type']) {
                case 'group':
                    $bMultipleValues = true;
                    break;
                case 'category':
                case 'select':
                    $value = $dataArray[$columnName] ?? '';
                    if ($value == 'Array') {    // checkbox from which nothing has been selected
                        $dataArray[$columnName] = $value = '';
                    }

                    if (
                        in_array($columnName, $dataFieldList) &&
                        !empty($columnConfig['MM']) &&
                        isset($value)
                    ) { // getAssignedToRecord($uid, $table)
                        if ($value == '' || is_array($value)) {
                            // the value contains the count of elements from a mm table
                        } elseif ($bColumnIsCount) {
                            $valuesArray =
                                $this->getRelatedUids(
                                    $columnConfig['MM'],
                                    'uid_foreign',
                                    'uid_local',
                                    ['uid_foreign' => $dataArray['uid']],
                                    'sorting_foreign'
                                );
                            $dataArray[$columnName] = $valuesArray;
                        } else {
                            // the values from the mm table are already available as an array
                            $dataArray[$columnName] = GeneralUtility::trimExplode(',', $value, true);
                        }
                    }
                    break;
                case 'check':
                    if (
                        isset($columnConfig['items']) &&
                        is_array($columnConfig['items'])
                    ) {
                        $value = $dataArray[$columnName] ?? '';
                        debug ($value, '$value');
                        if(is_array($value)) {
                            $dataArray[$columnName] = 0;
                            foreach ($value as $dec) {  // Combine values to one hexidecimal number
                                $dataArray[$columnName] |= (1 << $dec);
                            }
                            debug ($dataArray[$columnName], 'Pos 1 $dataArray['.$columnName.']');
                        }
                    } else {
                        debug ($dataArray[$columnName] ?? '', '$dataArray['.$columnName.']');
                        if (
                            isset($dataArray[$columnName]) &&
                            (
                                $dataArray[$columnName] == 1 ||
                                (string) $dataArray[$columnName] == 'on'
                            )
                        ) {
                            $dataArray[$columnName] = 1;
                            debug ($dataArray[$columnName], 'Pos 2 $dataArray['.$columnName.']');
                        } else {
                            $dataArray[$columnName] = 0;
                            debug ($dataArray[$columnName], 'Pos 3 $dataArray['.$columnName.']');
                        }
                    }
                    break;
                default:
                    // nothing
                    break;
            }

            if ($bMultipleValues) {
                $value = $dataArray[$columnName] ?? '';

                if (!empty($value) && !is_array($value)) {
                    $dataArray[$columnName] = GeneralUtility::trimExplode(',', $value, true);
                }
            }
        }

        if (
            is_object($staticInfoObj) &&
            !empty($dataArray['static_info_country'])
        ) {
            // empty zone if it does not fit to the provided country
            $zoneArray =
                $staticInfoObj->initCountrySubdivisions(
                    $dataArray['static_info_country']
                );
            if (!isset($zoneArray[$dataArray['zone']])) {
                $dataArray['zone'] = '';
            }
        }

        if (
            !$usePrivacyPolicy &&
            isset($dataArray['privacy_policy_acknowledged'])
        ) {
            unset($dataArray['privacy_policy_acknowledged']);
        }

        debug ($dataArray, 'modifyRow ENDE $dataArray');
        return true;
    } // modifyRow


    protected function mergeItems(
        $itemArray,
        $labelItemArray
    ) {
        $result = [];
        if (empty($itemArray)) {
            $result = $labelItemArray;
        } elseif (empty($labelItemArray)) {
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

    function generateSelectionPreviewContent(
        Localization $languageObj,
        string $theTable,
        string $columnName,
        array $valuesArray,
        array $columnConfig,
        bool $mergeLabels,
        bool $bStdWrap,
        array $stdWrap,
        bool $HSC,
        bool $bNotLast
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $xhtmlFix = HtmlUtility::determineXhtmlFix();
        $columnContent = '';
        $itemArray = [];
        $textSchema = $theTable . '.' . $columnName . '.I.';
        $labelItemArray = $languageObj->getItemsLL($textSchema, true);

        if ($mergeLabels || !count($labelItemArray)) {
            if (isset($columnConfig['itemsProcFunc'])) {
                $itemArray = GeneralUtility::callUserFunction(
                    $columnConfig['itemsProcFunc'],
                    $columnConfig,
                    $this
                );
            }
            $itemArray = $columnConfig['items'] ?? [];
            if ($mergeLabels) {
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
                $label = '';
                if (empty($itemKeyArray)) {
                    $label = $valuesArray[$i];
                } else {
                    $label =
                        $languageObj->getLabelFromString(
                            $itemKeyArray[$valuesArray[$i]]['label']
                        );
                }

                if ($HSC && is_string($label)) {
                    $label = htmlspecialchars($label);
                }
                $columnContent .=
                    ((!$bNotLast || $i < count($valuesArray) - 1) ?
                    $cObj->stdWrap($label, $stdWrap) :
                    $label
                );
            }
        }

        return $columnContent;
    }

    public function generateSelectedTitlesPreviewContent(
        Localization $languageObj,
        ?UserGroup $userGroupObj,
        Parameters $controlData,
        $theTable,
        array $conf,
        string $columnName,
        array $columnConfig,
        array $valuesArray,
        array $stdWrap,
        bool $bNotLast,
        bool $HSC,
    )
    {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $columnContent = '';
        $valuesArray = array_filter($valuesArray, 'strlen'); // removes null values
        $firstValue = current($valuesArray);

        if (!empty($firstValue) || count($valuesArray) > 1) {
            $foreignRows =
                $this->getRowsByUids(
                    $columnConfig['foreign_table'],
                    '*',
                    $valuesArray
                );

            if (
                is_array($foreignRows) &&
                count($foreignRows) > 0
            ) {
                $language = $controlData->getSysLanguageUid(
                    $conf,
                    'ALL',
                    $columnConfig['foreign_table']
                );
                $languageAspect = new LanguageAspect($language, $language);
                $titleField = $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['label'];

                for ($i = 0; $i < count($foreignRows); $i++) {
                    if (
                        $theTable == 'fe_users' &&
                        $columnName == 'usergroup'
                    ) {
                        $foreignRows[$i] =
                            $userGroupObj->getUsergroupOverlay(
                                $conf,
                                $controlData,
                                $foreignRows[$i]
                            );
                    } elseif (
                        $localizedRow =
                            $GLOBALS['TSFE']->sys_page->getLanguageOverlay(
                                $columnConfig['foreign_table'],
                                $foreignRows[$i],
                                $languageAspect)
                    ) {
                        $foreignRows[$i] = $localizedRow;
                    }
                    $text = $foreignRows[$i][$titleField];
                    if ($HSC) {
                        $text = htmlspecialchars($text);
                    }

                    $columnContent .=
                    (
                        ($bNotLast || $i < count($foreignRows) - 1) ?
                            $cObj->stdWrap($text, $stdWrap) :
                            $text
                    );
                }
            }
        }

        return $columnContent;
    }

    // Configure preview based on input type
    protected function generatePreviewContent(
        Localization $languageObj,
        Parameters $controlData,
        $theTable,
        array $conf,
        string $type,
        string $columnName,
        $columnValue,
        array $columnConfig,
        bool $bStdWrap,
        array $stdWrap,
        bool $bListWrap,
        array $listWrap,
        bool $HSC,
        bool $bNotLast
    ): string
    {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $xhtmlFix = HtmlUtility::determineXhtmlFix();
        $columnContent = '';
        $userGroupObj = null;

        if ($theTable == 'fe_users' && $columnName == 'usergroup') {
            $tablesObj = GeneralUtility::makeInstance(Tables::class);
            $addressObj = $tablesObj->get('address');
            $userGroupObj = $addressObj->getFieldObj('usergroup');
        }

        switch ($type) {
            case 'input':
            case 'text':
                if (
                    isset($columnValue) &&
                    (string) $columnValue != ''
                ) {
                    $columnContent =
                        ($HSC && is_string($columnValue) ?
                            nl2br(htmlspecialchars($columnValue)) :
                            (string) $columnValue
                        );
                }
                break;

            case 'check':
                $label = '';
                if (
                    isset($columnConfig['items'])
                ) {
                    if (!$bStdWrap) {
                        $stdWrap['wrap'] = '<li>|</li>';
                    }

                    if (!$bListWrap) {
                        $listWrap['wrap'] = '<ul class="agency-multiple-checked-values">|</ul>';
                    }
                    $bCheckedArray = [];
                    if (
                        isset($columnValue)
                    ) {
                        if (
                            is_array($columnValue)
                        ) {
                            foreach($columnValue as $key => $value) {
                                $bCheckedArray[$value] = true;
                            }
                        } else if ((string) $columnValue != '') {
                            foreach($columnConfig['items'] as $key => $value) {
                                $checked = ($columnValue & (1 << $key));
                                if ($checked) {
                                    $bCheckedArray[$key] = true;
                                }
                            }
                        }
                    }

                    $count = 0;
                    $checkedCount = 0;
                    foreach($columnConfig['items'] as $key => $value) {
                        $count++;
                        $checked = (!empty($bCheckedArray[$key]));

                        if ($checked) {
                            $checkedCount++;
                            $label = $languageObj->getLabelFromString($columnConfig['items'][$key]['label']);
                            if ($HSC) {
                                $label =
                                htmlspecialchars($label);
                            }
                            $label = ($checked ? $label : '');
                            $columnContent .= ((!$bNotLast || $checkedCount < count($bCheckedArray)) ? $cObj->stdWrap($label, $stdWrap) : $label);
                        }
                    }
                    $columnContent = $cObj->stdWrap($columnContent, $listWrap);
                } else {
                    if (
                        !empty($columnValue)
                    ) {
                        $label = $languageObj->getLabel('yes');
                    } else {
                        $label = $languageObj->getLabel('no');
                    }

                    if ($HSC) {
                        $label = htmlspecialchars($label);
                    }
                    $columnContent = $label;
                }
                break;

                case 'radio':
                    $itemArray = [];

                    if (
                        isset($columnValue) &&
                        (string) $columnValue != ''
                    ) {
                        $valuesArray = is_array($columnValue) ? $columnValue : explode(',', (string) $columnValue);
                        $textSchema = $theTable . '.' . $columnName . '.I.';
                        $labelItemArray = $languageObj->getItemsLL($textSchema, true);

                        if (!empty($conf['mergeLabels']) || !count($labelItemArray)) {
                            if (isset($columnConfig['itemsProcFunc'])) {
                                $itemArray =
                                    GeneralUtility::callUserFunction(
                                        $columnConfig['itemsProcFunc'],
                                        $columnConfig,
                                        $this
                                    );
                            }
                            $itemArray = $columnConfig['items'];
                            if (!empty($conf['mergeLabels'])) {
                                $itemArray =
                                    $this->mergeItems($itemArray, $labelItemArray);
                            }
                        } else {
                            $itemArray = $labelItemArray;
                        }

                        if (
                            is_array($itemArray) &&
                            count($itemArray)
                        ) {
                            $itemKeyArray = $this->getItemKeyArray($itemArray);

                            if (!$bStdWrap) {
                                $stdWrap['wrap'] = '| ';
                            }

                            for ($i = 0; $i < count($valuesArray); $i++) {
                                $label =
                                    $languageObj->getLabelFromString(
                                        $itemKeyArray[$valuesArray[$i]]['label']
                                    );
                                if ($HSC) {
                                    $label = htmlspecialchars($label);
                                }
                                $columnContent .= ((!$bNotLast || $i < count($valuesArray) - 1) ? $cObj->stdWrap($label, $stdWrap) : $label);
                            }
                        }
                    }
                    break;

                case 'select':

                    if (
                        isset($columnValue) &&
                        (
                            !empty($columnValue) ||
                            is_string($columnValue) &&
                            $columnValue == '0'
                        )
                    ) {
                        $valuesArray = is_array($columnValue) ? $columnValue : explode(',', (string) $columnValue);
                        if (isset($userGroupObj) && is_object($userGroupObj)) {
                            $reservedValues = $userGroupObj->getReservedValues($conf);
                            $valuesArray = array_diff($valuesArray, $reservedValues);
                        }

                        $columnContent .=
                            $this->generateSelectionPreviewContent(
                                $languageObj,
                                $theTable,
                                $columnName,
                                $valuesArray,
                                $columnConfig,
                                !empty($conf['mergeLabels']),
                                $bStdWrap,
                                $stdWrap,
                                $HSC,
                                $bNotLast
                            );

                        if (
                            count($valuesArray) &&
                            // check if a language overlay must be used
                            isset($columnConfig['foreign_table'])
                        ) {
                            $columnContent .=
                                $this->generateSelectedTitlesPreviewContent(
                                        $languageObj,
                                        $userGroupObj,
                                        $controlData,
                                        $theTable,
                                        $conf,
                                        $columnName,
                                        $columnConfig,
                                        $valuesArray,
                                        $stdWrap,
                                        $bNotLast,
                                        $HSC,
                                    );
                        }
                    }
                    break;

                case 'category':
                    $valuesArray = is_array($columnValue) ? $columnValue : explode(',', (string) $columnValue);
                    $columnContent .=
                        $this->generateSelectionPreviewContent(
                            $languageObj,
                            $theTable,
                            $columnName,
                            $valuesArray,
                            $columnConfig,
                            !empty($conf['mergeLabels']),
                            $bStdWrap,
                            $stdWrap,
                            $HSC,
                            $bNotLast
                        );
                    $columnContent .=
                        $this->generateSelectedTitlesPreviewContent(
                            $languageObj,
                            $userGroupObj,
                            $controlData,
                            $theTable,
                            $conf,
                            $columnName,
                            $columnConfig,
                            $valuesArray,
                            $stdWrap,
                            $bNotLast,
                            $HSC,
                        );
                    break;

                default:
                    // unsupported input type
                    $label = $languageObj->getLabel('unsupported');
                    if ($HSC) {
                        $label = htmlspecialchars($label);
                    }
                    $columnContent .= $columnConfig['type'] . ':' . $label;
                    break;
            }

        return $columnContent;
    }

    public function getSelectCheckMainPart (
        &$previouslySelected,
        $columnName,
        $index,
        $prefixId,
        $uid,
        $title,
        $valuesArray,
        $renderMode,
        $allowMultipleSelection
    )
    {
        $css = GeneralUtility::makeInstance(Css::class);
        $useXHTML = HtmlUtility::useXHTML();
        $xhtmlFix = HtmlUtility::determineXhtmlFix();
        $titleText = htmlspecialchars($title);
        $selectedHtml = ($useXHTML ? ' selected="selected"' : ' selected');
        $selected = (in_array($uid, $valuesArray) ? $selectedHtml : '');
        $columnContent = '';

        if (
            !$allowMultipleSelection &&
            $previouslySelected
        ) {
            $selected = '';
        }
        $previouslySelected = ($previouslySelected || $selected);

        if (
            $renderMode == 'checkbox'
        ) {
            $columnContent .= '<div class="' . $css->getClassName($columnName, 'divInput-' . $index) . '">';
            $columnContent .= '<input  class="' .
            $css->getClassName($columnName, 'input-' . ($index)) .
            '" id="'.
            FrontendUtility::getClassName(
                $columnName,
                $prefixId
            ) .
            '-' . $uid . '" name="FE[' . $theTable . '][' . $columnName . '][' . $uid . ']" value="' . $uid .
            '" type="checkbox"' . ($selected ? $checkedHtml : '') . $xhtmlFix . '></div>' .
            '<div class="viewLabel ' . $css->getClassName($columnName, 'divLabel-' . $index) . '"><label for="' .
            FrontendUtility::getClassName(
                $columnName,
                $prefixId
            ) . '-' . $uid .
            '" class="' . $css->getClassName($columnName, 'label') . '">' . $titleText . '</label></div>';
        } else {
            $columnContent .= '<option value="' . $uid . '"' . $selected . '>' . $titleText . '</option>';
        }

        return $columnContent;
    }

    protected function getSelectCheckStartPart(
        $theTable,
        $columnName,
        $prefixId,
        $renderMode,
        $allowMultipleSelection,
        $markerSuffix
    ) {
        $columnContent = '';
        $attributeMultiple = '';
        $attributeClassName = '';
        $useXHTML = HtmlUtility::useXHTML();
        $xhtmlFix = HtmlUtility::determineXhtmlFix();
        $cObj = FrontendUtility::getContentObjectRenderer();
        $css = GeneralUtility::makeInstance(Css::class);

        if (
            $allowMultipleSelection
        ) {
            if ($useXHTML) {
                $attributeMultiple = ' multiple="multiple"';
            } else {
                $attributeMultiple = ' multiple';
            }
        }

        $attributeIdName = ' id="' .
            FrontendUtility::getClassName(
                $columnName,
                $prefixId
            ) .
            '" name="FE[' . $theTable . '][' . $columnName . ']';

        if ($attributeMultiple != '') {
            $attributeIdName .= '[]';
        }
        $attributeIdName .= '"';
        $attributeTitle = ' title="###TOOLTIP_' . $markerSuffix .
            $cObj->caseshift($columnName, 'upper') . '###"';
        $attributeClassName = $css->getClassName($columnName, 'input');

        if (
            isset($renderMode) &&
            $renderMode == 'checkbox'
        ) {
            $attributeClass = ' class="' . $attributeClassName . '"';
            $columnContent .= '
                <input' . $attributeIdName . ' value="" type="hidden"' . $attributeClass . $xhtmlFix . '>';

            $attributeClass = '';
            if (
                $attributeMultiple != ''
            ) {
                $attributeClassName = $css->getClassName('multiple-checkboxes', '');
                $attributeClass = ' class="' . $attributeClassName . '"';
            }

            $columnContent .= '
                <div ';
        if ($attributeClass != '') {
            $columnContent .= $attributeClass;
        }
        $columnContent .=
            $attributeTitle .
            $xhtmlFix . '>';
        } else {
            if (
                $attributeMultiple != ''
            ) {
                $attributeClassName .= ' ' . $css->getClassName('multiple-select', '');
            }
            $attributeClass = ' class="' . $attributeClassName . '"';
            $columnContent .= '<select' . $attributeIdName;
            if ($attributeClass != '') {
                $columnContent .= $attributeClass;
            }

            $columnContent .=
                $attributeMultiple .
                $attributeTitle .
                '>';
        }
        return $columnContent;
    }

    protected function getSelectCheckEndPart(
        $renderMode,
    ) {
        $columnContent = '';

        if (
            $renderMode == 'checkbox'
        ) {
            $columnContent .= '</div>';
        } else {
            $columnContent .= '</select>';
        }

        return $columnContent;
    }

    protected function generateContent(
        $languageObj,
        $controlData,
        $theTable,
        $cmd,
        $cmdKey,
        $conf,
        $type,
        $columnName,
        $columnValue,
        $columnConfig,
        $prefixId,
        $bStdWrap,
        $stdWrap,
        $bNotLast
    ) {
        $itemArray = '';
        $columnContent = '';
        $useXHTML = HtmlUtility::useXHTML();
        $xhtmlFix = HtmlUtility::determineXhtmlFix();
        $css = GeneralUtility::makeInstance(Css::class);
        $cObj = FrontendUtility::getContentObjectRenderer();
        $userGroupObj = null;

        if ($theTable == 'fe_users' && $columnName == 'usergroup') {
            $tablesObj = GeneralUtility::makeInstance(Tables::class);
            $addressObj = $tablesObj->get('address');
            $userGroupObj = $addressObj->getFieldObj('usergroup');
        }

        if (isset($columnConfig['foreign_table'])) {
            $language = $controlData->getSysLanguageUid(
                $conf,
                'ALL',
                $columnConfig['foreign_table']
            );
            $languageAspect = new LanguageAspect($language, $language);
        }

        // Configure inputs based on TCA type
        if (in_array($type, ['check', 'radio', 'select', 'category'])) {
            $valuesArray = [];

            if (isset($columnValue)) {
                $valuesArray = is_array($columnValue) ? $columnValue : explode(',', (string) $columnValue);
            }

            if (empty($valuesArray[0]) && isset($columnConfig['default'])) {
                $valuesArray[] = $columnConfig['default'];
            }
            $textSchema = $theTable . '.' . $columnName . '.I.';
            $labelItemArray = $languageObj->getItemsLL($textSchema, true);

            if (!empty($conf['mergeLabels']) || !count($labelItemArray)) {
                if (isset($columnConfig['itemsProcFunc'])) {
                    $itemArray =
                        GeneralUtility::callUserFunction(
                            $columnConfig['itemsProcFunc'],
                            $columnConfig,
                            $this
                        );
                }
                $itemArray = $columnConfig['items'] ?? [];
                if (!empty($conf['mergeLabels'])) {
                    $itemArray =
                    $this->mergeItems($itemArray, $labelItemArray);
                }
            } else {
                $itemArray = $labelItemArray;
            }
        }

        switch ($type) {
            case 'input':
                $columnContent = '<input ' .
                'class="' . $css->getClassName($columnName, 'input') . '" ' .
                'type="input" name="FE[' . $theTable . '][' . $columnName . ']"' .
                ' title="###TOOLTIP_' . (($cmd == 'invite') ? 'INVITATION_' : '') . $cObj->caseshift($columnName, 'upper') . '###"' .
                ' size="' . ($columnConfig['size'] ?: 30) . '"';
                if ($columnConfig['max']) {
                    $columnContent .= ' maxlength="' . $columnConfig['max'] . '"';
                }
                if (isset($columnConfig['default'])) {
                    $label = $languageObj->getLabelFromString($columnConfig['default']);
                    $label = htmlspecialchars($label);
                    $columnContent .= ' value="' . $label . '"';
                }
                $columnContent .= $xhtmlFix . '>';
                break;

            case 'text':
                $label = (isset($columnConfig['default']) ? $languageObj->getLabelFromString($columnConfig['default']) : '');
                $label = htmlspecialchars($label);
                $columnContent = '<textarea id="' .
                    FrontendUtility::getClassName(
                        $columnName,
                        $prefixId
                    ) .
                    '" class="' . $css->getClassName($columnName, 'input') .
                    '" name="FE[' . $theTable . '][' . $columnName . ']"' .
                    ' title="###TOOLTIP_' . (($cmd == 'invite') ? 'INVITATION_' : '') . $cObj->caseshift($columnName, 'upper') . '###"' .
                    ' cols="' . ($columnConfig['cols'] ?: 30) . '"' .
                    ' rows="' . ($columnConfig['rows'] ?: 5) . '"' .
                    '>' . $label . '</textarea>';
                break;

            case 'check':
                $label = $languageObj->getLabel('tooltip_' . $columnName);
                $label = htmlspecialchars($label);

                if (
                    isset($itemArray) &&
                    is_array($itemArray) &&
                    !empty($itemArray)
                ) {
                    $uidText =
                    FrontendUtility::getClassName(
                        $columnName,
                        $prefixId
                    );
                    if (
                        isset($mrow) &&
                        is_array($mrow) &&
                        !empty($mrow['uid'])
                    ) {
                        $uidText .= '-' . $mrow['uid'];
                    }
                    $columnContent = '<ul id="' . $uidText . '" class="' . $css->getClassName('agency-multiple-checkboxes', 'ul') . '">';

                    if (
                        isset($columnValue) &&
                        (
                            $controlData->getSubmit() ||
                            $controlData->getDoNotSave() ||
                            $cmd == 'edit'
                        )
                    ) {
                        $startVal = $columnValue;
                    } else {
                        $startVal = $columnConfig['default'] ?? '0';
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
                            '" class="' . $css->getClassName($columnName, 'input-' . $i) .
                            '" name="FE[' . $theTable . '][' . $columnName . '][]" value="' . $key . '"' .
                            $checkedHtml . $xhtmlFix . '><label for="' . $uidText . '-' . $key . $xhtmlFix . '">' .
                            $label .
                            '</label></li>';
                        $columnContent .= $newContent;
                    }
                    $columnContent .= '</ul>';
                } else {
                    $checkedHtml = ($useXHTML ? ' checked="checked"' : ' checked');
                    $columnContent =
                    '<input type="checkbox"' .
                    ' id="' .
                    FrontendUtility::getClassName(
                        $columnName,
                        $prefixId
                    ) .
                    '" class="' . $css->getClassName($columnName, 'input') .
                    '" name="FE[' . $theTable . '][' . $columnName . ']" title="' .
                    $label . '"' . (isset($columnValue) && $columnValue != '' ? ' value="on"' . $checkedHtml : '') .
                    $xhtmlFix . '>';
                }
                break;

            case 'radio':
                if (
                    isset($columnValue) &&
                    (
                        $controlData->getSubmit() ||
                        $controlData->getDoNotSave() ||
                        $cmd == 'edit'
                    )
                ) {
                    $startVal = $columnValue;
                } else {
                    $startVal = $columnConfig['default'] ?? '';
                }

                if (empty($startVal) && isset($columnConfig['items'])) {
                    reset($columnConfig['items']);
                    [$startConf] = $columnConfig['items'];
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
                            $columnName,
                            $prefixId
                        ) .
                        '-' . $i .
                        '" class="' . $css->getClassName($columnName, 'input') .
                        '" name="FE[' . $theTable . '][' . $columnName . ']"' .
                        ' value="' . $value . '" ' . ($value == $startVal ? $checkedHtml : '') . $xhtmlFix . '>' .
                        '<label for="' .
                        FrontendUtility::getClassName(
                            $columnName,
                            $prefixId
                        ) .
                        '-' . $i . '">' . $label . '</label>';
                        $i++;
                        $columnContent .=
                        ((!$bNotLast || $i < count($itemArray) - 1) ?
                        $cObj->stdWrap($itemOut, $stdWrap) :
                        $itemOut);
                    }
                }
                break;

            case 'select':
                $checkedHtml =  ($useXHTML ? ' checked="checked"' : ' checked');
                $selectedHtml = ($useXHTML ? ' selected="selected"' : ' selected');

                $allowMultipleSelection =
                    isset($columnConfig['maxitems']) &&
                    $columnConfig['maxitems'] > 1 &&
                    (
                        $columnName != 'usergroup' ||
                        !empty($conf['allowMultipleUserGroupSelection']) ||
                        $theTable != 'fe_users'
                    );
                $columnContent .=
                    $this->getSelectCheckStartPart(
                        $theTable,
                        $columnName,
                        $prefixId,
                        $columnConfig['renderMode'] ?? '',
                        $allowMultipleSelection,
                        (($cmd == 'invite') ? 'INVITATION_' : '')
                    );
                if (
                    is_array($itemArray)
                ) {
                    $itemArray = $this->getItemKeyArray($itemArray);
                    $i = 0;

                    foreach ($itemArray as $k => $item) {
                        $label = $languageObj->getLabelFromString($item['label'], true);
                        $label = htmlspecialchars($label);
                        if (
                            isset($columnConfig['renderMode']) &&
                            $columnConfig['renderMode'] == 'checkbox'
                        ) {
                            $columnContent .= '<div class="' . $css->getClassName($columnName, 'divInput-' . ($i + 1)) . '">' .
                            '<input class="' .
                            $css->getClassName('checkbox-checkboxes', 'input') . ' ' . $css->getClassName($columnName, 'input-' . ($i + 1)) .
                            '" id="' .
                            FrontendUtility::getClassName(
                                $columnName,
                                $prefixId
                            ) .
                            '-' . $i . '" name="FE[' . $theTable . '][' . $columnName . '][' . $k . ']" value="' . $k .
                            '" type="checkbox"  ' . (in_array($k, $valuesArray) ? $checkedHtml : '') . $xhtmlFix . '></div>' .
                            '<div class="viewLabel ' . $css->getClassName($columnName, 'divLabel') . '">' .
                            '<label for="' .
                            FrontendUtility::getClassName(
                                $columnName,
                                $prefixId
                            ) .
                            '-' . $i . '"' .
                            ' class="' .  $css->getClassName($columnName, 'label') . '"' .
                            '>' . $label . '</label></div>';
                        } else {
                            $columnContent .= '<option value="' . $k . '" ' . (in_array($k, $valuesArray) ? $selectedHtml : '') . '>' . $label . '</option>';
                        }
                        $i++;
                    }

                    if (
                        isset($columnConfig['renderMode']) &&
                        $columnConfig['renderMode'] == 'checkbox'
                    ) {
                        $columnContent .= '</div>';
                    }
                }

                if (
                    !empty($columnConfig['foreign_table'])
                ) {
                    $titleField = $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['label'];
                    $whereArray = [];
                    $reservedValues = [];
                    $queryBuilder = null;

                    if (
                        isset($userGroupObj) &&
                        is_object($userGroupObj)
                    ) {
                        $reservedValues = $userGroupObj->getReservedValues($conf);
                        $foreignTable = $this->getForeignTable($theTable, $columnName);
                        $allowedUserGroupArray = [];
                        $allowedSubgroupArray = [];
                        $deniedUserGroupArray = [];

                        $userGroupObj->getAllowedValues(
                            $allowedUserGroupArray,
                            $allowedSubgroupArray,
                            $deniedUserGroupArray,
                            $conf,
                            $cmdKey,
                        );

                        $pidArray = $userGroupObj->getConfigPidArray(
                            $controlData->getPid(),
                            $conf['userGroupsPidList']
                        );

                        $queryBuilder =
                            $this->frontendGroupRepository
                                ->getUserGroupWhereClause(
                                    $whereArray,
                                    $pidArray,
                                    $conf,
                                    $cmdKey,
                                    $allowedUserGroupArray,
                                    $allowedSubgroupArray,
                                    $deniedUserGroupArray,
                                    true
                                );
                    }

                    if (
                        $conf['useLocalization'] &&
                        $GLOBALS['TCA'][$columnConfig['foreign_table']] &&
                        $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['languageField'] &&
                        $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['transOrigPointerField']
                    ) {
                        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($columnConfig['foreign_table']);
                            $whereArray[CompositeExpression::TYPE_AND] = $queryBuilder->expr()
                            ->eq(
                                $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['transOrigPointerField'],
                                0
                            );
                    }

                    if (
                        // $columnName == 'categories' &&
                        $columnConfig['foreign_table'] == 'sys_category' &&
                        isset($GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['languageField']) &&
                        !empty($conf['categories_PIDLIST'])
                    ) {
                        $categoryObj = GeneralUtility::makeInstance(Category::class);
                        $pidArray = $categoryObj->getConfigPidArray(
                            $controlData->getPid(),
                            $conf['categories_PIDLIST']
                        );
                        $whereArray[CompositeExpression::TYPE_AND] = $queryBuilder->expr()
                            ->in(
                                'pid',
                                $queryBuilder->createNamedParameter(
                                    $pidArray,
                                    Connection::PARAM_INT_ARRAY
                                )
                        );

                        if (!empty($conf['useLocalization'])) {
                            $whereArray[CompositeExpression::TYPE_AND] = $queryBuilder->expr()
                                ->eq(
                                        $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['languageField'],
                                        $queryBuilder->createNamedParameter(
                                            $language, Connection::PARAM_INT
                                        )
                                );
                        }
                    }

                    if (!isset($queryBuilder)) {
                        $table = $columnConfig['foreign_table'];
                        $queryBuilder = $this->connectionPool
                            ->getQueryBuilderForTable($table);
                    }
                    $queryBuilder
                        ->select('*')
                        ->from($columnConfig['foreign_table']);

                    if (!empty($whereArray['where'])) {
                        $queryBuilder->where(
                            ...$whereArray['where']
                        );
                    }
                    if (!empty($whereArray[CompositeExpression::TYPE_OR])) {
                        $queryBuilder->orWhere(
                            ...$whereArray[CompositeExpression::TYPE_OR]
                        );
                    }
                    if (!empty($whereArray[CompositeExpression::TYPE_AND])) {
                        $queryBuilder->andWhere(
                            ...$whereArray[CompositeExpression::TYPE_AND]
                        );
                    }

                    $orderBy = $GLOBALS['TCA'][$columnConfig['foreign_table']]['ctrl']['sortby'] ?? '';

                    if ($orderBy != '') {
                        $queryBuilder->orderBy($orderBy);
                    }
                    $result = $queryBuilder->executeQuery();

                    if (
                        !in_array(
                            $columnName,
                            $controlData->getRequiredArray()
                        )
                    ) {
                        if (
                            $columnContent ||
                            isset($columnConfig['renderMode']) &&
                            $columnConfig['renderMode'] == 'checkbox'
                        ) {
                            // nothing
                        } else {
                            $columnContent .= '<option value=""' . ($valuesArray[0] ? '' : $selectedHtml) . '></option>';
                        }
                    }

                    $outputArray = [];

                    while ($row2 = $result->fetchAssociative()) {
                        if ($localizedRow =
                            $GLOBALS['TSFE']->sys_page->getLanguageOverlay(
                                $columnConfig['foreign_table'],
                                $row2,
                                $languageAspect
                            )
                        ) {
                            $row2 = $localizedRow;
                        }

                        if ($columnName == 'usergroup') {
                            $row2 = $userGroupObj->getUsergroupOverlay($conf, $controlData, $row2);
                        }

                        if (!in_array($row2['uid'], $reservedValues)) {
                            $outputArray[$row2['uid']] = $row2[$titleField];
                        }
                    }

                    $i = 0;
                    $previouslySelected = false;

                    foreach ($outputArray as $uid => $title) {
                        $i++;

                        // Handle usergroup case
                        if (
                            $columnName == 'usergroup'
                        ) {
                            $columnContent .=
                                $this->getSelectCheckMainPart(
                                    $previouslySelected,
                                    $columnName,
                                    $i,
                                    $prefixId,
                                    $uid,
                                    $title,
                                    $valuesArray,
                                    $columnConfig['renderMode'] ?? '',
                                    $conf['allowMultipleUserGroupSelection']
                                );
                        } else {
                            $titleText = htmlspecialchars($title);

                            if (
                                isset($columnConfig['renderMode']) &&
                                $columnConfig['renderMode'] == 'checkbox'
                            ) {
                                $columnContent .= '<div class="' . $css->getClassName($columnName, 'divInput-' . $i) . '">';+
                                $columnContent .= '<input class="' .
                                $css->getClassName(
                                    'checkbox'
                                ) .
                                '" id="'.
                                FrontendUtility::getClassName(
                                    $columnName,
                                    $prefixId
                                ) .
                                '-' . $uid . '" name="FE[' . $theTable . '][' .  $columnName . '][' . $uid . ']" value="' . $uid . '" type="checkbox"' . (in_array($uid, $valuesArray) ? $checkedHtml : '') . $xhtmlFix . '></div>' .
                                '<div class="viewLabel ' . $css->getClassName($columnName, 'divLabel-' . $i) . '"><label for="' .
                                FrontendUtility::getClassName(
                                    $columnName,
                                    $prefixId
                                ) . '-' . $uid . '">' . $titleText . '</label></div>';
                            } else {
                                $columnContent .= '<option value="' . $uid . '"' . (in_array($uid, $valuesArray) ? $selectedHtml : '') . '>' . $titleText . '</option>';
                            }
                        }
                    }
                }

                $columnContent .= $this->getSelectCheckEndPart($columnConfig['renderMode'] ?? '');
                break;

            case 'category':
                $columnContent .=
                    $this->getSelectCheckStartPart(
                        $theTable,
                        $columnName,
                        $prefixId,
                        $columnConfig['renderMode'] ?? '',
                        true,
                        (($cmd == 'invite') ? 'INVITATION_' : '')
                    );

                $categoryObj = GeneralUtility::makeInstance(Category::class);
                $pidArray = $categoryObj->getConfigPidArray(
                    $controlData->getPid(),
                    $conf['categories_PIDLIST']
                );
                $categories = $categoryObj->findRecords($pidArray);
                $previouslySelected = false;
                $index = 0;

                foreach ($categories as $category) {
                    $index++;

                    $columnContent .= $this->getSelectCheckMainPart(
                        $previouslySelected,
                        $columnName,
                        $index,
                        $prefixId,
                        $category->getUid(),
                        $category->getTitle(),
                        $valuesArray,
                        $columnConfig['renderMode'] ?? '',
                        true
                    );
                }
                $columnContent .= $this->getSelectCheckEndPart($columnConfig['renderMode'] ?? '');
                break;

            default:
                $columnContent .= $columnConfig['type'] . ':' . $languageObj->getLabel('unsupported');
                break;
        }

        return $columnContent;
    }

    /**
    * Adds form element markers from the Table Configuration Array to a marker array
    *
    * @param array $markerArray: the input and output marker array
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
    public function addMarkers(
        &$markerArray,
        $conf,
        Localization $languageObj,
        Parameters $controlData,
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
    ) {
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

        $fields = (!empty($cmdKey) && isset($conf[$cmdKey . '.']['fields']) ? $conf[$cmdKey . '.']['fields'] : '');

        if ($mode == Mode::PREVIEW) {
            if ($activity == '') {
                $activity = 'preview';
            }
        } elseif (!$viewOnly && $activity != 'email') {
            $activity = 'input';
        }

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $columnName => $columnSettings) {
            if (
                GeneralUtility::inList($fields, $columnName) ||
                $useMissingFields
            ) {
                $columnConfig = $columnSettings['config'];
                $columnContent = '';

                if (!$bChangesOnly || isset($mrow[$columnName])) {
                    $type = $columnConfig['type'];

                    // check for a setup of wraps:
                    $stdWrap = [];
                    $bNotLast = false;
                    $bStdWrap = false;
                    // any item wraps set?
                    if (
                        isset($conf[$type . '.']) &&
                        isset($conf[$type . '.'][$activity . '.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$columnName . '.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$columnName . '.']['item.'])
                    ) {
                        $stdWrap = $conf[$type . '.'][$activity . '.'][$columnName . '.']['item.'];
                        $bStdWrap = true;
                        if ($conf[$type . '.'][$activity . '.'][$columnName . '.']['item.']['notLast']) {
                            $bNotLast = true;
                        }
                    }
                    $listWrap = [];
                    $bListWrap = false;

                    // any list wraps set?
                    if (
                        isset($conf[$type . '.']) &&
                        isset($conf[$type . '.'][$activity.'.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$columnName . '.']) &&
                        isset($conf[$type . '.'][$activity . '.'][$columnName . '.']['list.'])
                    ) {
                        $listWrap = $conf[$type . '.'][$activity . '.'][$columnName . '.']['list.'];
                        $bListWrap = true;
                    } else {
                        $listWrap['wrap'] = '<ul class="agency-multiple-checked-values">|</ul>';
                    }

                    if (
                        $mode == Mode::PREVIEW ||
                        $viewOnly
                    ) {
                        $columnContent =
                            $this->generatePreviewContent(
                                $languageObj,
                                $controlData,
                                $theTable,
                                $conf,
                                $type,
                                $columnName,
                                $mrow[$columnName] ?? null,
                                $columnConfig,
                                $bStdWrap,
                                $stdWrap,
                                $bListWrap,
                                $listWrap,
                                $HSC,
                                $bNotLast
                            );
                    } else {
                        $columnContent =
                            $this->generateContent(
                                $languageObj,
                                $controlData,
                                $theTable,
                                $cmd,
                                $cmdKey,
                                $conf,
                                $type,
                                $columnName,
                                $mrow[$columnName] ?? null,
                                $columnConfig,
                                $prefixId,
                                $bStdWrap,
                                $stdWrap,
                                $bNotLast
                            );
                    }
                }

                if ($mode == Mode::PREVIEW || $viewOnly) {
                    $markerArray['###TCA_INPUT_VALUE_' . $columnName . '###'] = $columnContent;
                }
                $markerArray['###TCA_INPUT_' . $columnName . '###'] = $columnContent;
            } else {
                // field not in form fields list
            }
        }
    }

    public function getCheckboxFields($theTable, $fieldList) {
        $checkFields = [];

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $columnName => $columnSettings) {
            $columnConfig = $columnSettings['config'];
            if (
                !$columnConfig ||
                !is_array($columnConfig) ||
                !GeneralUtility::inList($fieldList, $columnName)
            ) {
                continue;
            }

            switch ($columnConfig['type']) {
                case 'check':
                    $checkFields[] = $columnName;
                    break;
            }
        }

        return $checkFields;
    }


    /**
    * Transfers the item array to one where the key corresponds to the value
    * @param    array   array of selectable items like found in TCA
    * @ return  array   array of selectable items with correct key
    */
    public function getItemKeyArray($itemArray)
    {
        $result = [];

        if (is_array($itemArray)) {
            foreach ($itemArray as $row) {
                $key = $row['value'];
                $result[$key] = $row;
            }
        }
        return $result;
    }   // getItemKeyArray
}
