<?php

namespace JambageCom\Agency\Domain\Field;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2018 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * class the usergroup field
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage agency
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;


class UserGroup extends Base {

    protected $savedReservedValues = [];

    /*
    * Modifies the form fields configuration depending on the $cmdKey
    *
    * @param array $conf: the configuration array
    * @param string $cmdKey: the command key
    * @return void
    */
    public function modifyConf (&$conf, $cmdKey)
    {
            // Add usergroup to the list of fields and required fields if the user is allowed to select user groups
            // Except when only updating password
        if (
            !empty($cmdKey) &&
            $cmdKey != 'password' &&
            $cmdKey != 'delete'
        ) {
            if (isset($conf[$cmdKey . '.']['allowUserGroupSelection'])) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',usergroup', 1)));
                $conf[$cmdKey . '.']['required'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',usergroup', 1)));
            } else {
                    // Remove usergroup from the list of fields and required fields if the user is not allowed to select user groups
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), ['usergroup']));
                $conf[$cmdKey . '.']['required'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'], 1), ['usergroup']));
            }
        }
            // If inviting and administrative review is enabled, save original reserved user groups
        if ($cmdKey == 'invite' && $conf['enableAdminReview']) {
            $this->savedReservedValues = $this->getReservedValues($conf);
        }
    }

    /*
    * Gets allowed values for user groups
    *
    * @param array $conf: the configuration array
    * @param string $cmdKey: the command key
    * @return void
    */
    public function getAllowedValues (
        $conf,
        $cmdKey,
        &$allowedUserGroupArray,
        &$allowedSubgroupArray,
        &$deniedUserGroupArray
    )
    {
        $allowedUserGroupArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['allowedUserGroups'], 1);
        $allowedSubgroupArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['allowedSubgroups'], 1);
        $deniedUserGroupArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['deniedUserGroups'], 1);
    }

    /*
    * Gets the array of user groups reserved for control of the registration process
    *
    * @return array the reserved user groups
    */
    public function getReservedValues ($conf)
    {
        $result = array_merge(
            GeneralUtility::trimExplode(',', $conf['create.']['overrideValues.']['usergroup'], 1),
            GeneralUtility::trimExplode(',', $conf['invite.']['overrideValues.']['usergroup'], 1),
            GeneralUtility::trimExplode(',', $conf['setfixed.']['APPROVE.']['usergroup'], 1),
            GeneralUtility::trimExplode(',', $conf['setfixed.']['ACCEPT.']['usergroup'], 1),
            $this->savedReservedValues
        );

        $result = array_unique($result);

        return $result;
    }

    public function removeInvalidValues (
        $conf,
        $cmdKey,
        &$row
    )
    {
        if (
            isset($row['usergroup']) &&
            $conf[$cmdKey . '.']['allowUserGroupSelection']
        ) {
// nothing
        } else {
            $row['usergroup'] = ''; // the setting of the usergropus has not been allowed
        }
    }

    public function getAllowedWhereClause (
        $theTable,
        $pid,
        $conf,
        $cmdKey,
        $bAllow = true
    )
    {
        $whereClause = '';
        $subgroupWhereClauseArray = [];
        $subgroupWhereClause = '';
        $pidArray = [];
        $tmpArray = GeneralUtility::trimExplode(',', $conf['userGroupsPidList'], 1);
        if (count($tmpArray)) {
            foreach($tmpArray as $value) {
                $valueIsInt = MathUtility::canBeInterpretedAsInteger($value);
                if ($valueIsInt) {
                    $pidArray[] = intval($value);
                }
            }
        }

        if (count($pidArray) > 0) {
            $whereClause = ' pid IN (' . implode(',', $pidArray) . ') ';
        } else {
            $whereClause = ' pid=' . intval($pid) . ' ';
        }

        $whereClausePart2 = '';
        $whereClausePart2Array = [];

        $this->getAllowedValues(
            $conf,
            $cmdKey,
            $allowedUserGroupArray,
            $allowedSubgroupArray,
            $deniedUserGroupArray
        );

        if ($allowedUserGroupArray['0'] != 'ALL') {
            $uidArray = $GLOBALS['TYPO3_DB']->fullQuoteArray($allowedUserGroupArray, $theTable);
            $subgroupWhereClauseArray[] = 'uid ' . ($bAllow ? 'IN' : 'NOT IN') . ' (' . implode(',', $uidArray) . ')';
        }

        if (count($allowedSubgroupArray)) {
            $subgroupArray = $GLOBALS['TYPO3_DB']->fullQuoteArray($allowedSubgroupArray, $theTable);
            $subgroupWhereClauseArray[] = 'subgroup ' . ($bAllow ? 'IN' : 'NOT IN') . ' (' . implode(',', $subgroupArray) . ')';
        }

        if (count($subgroupWhereClauseArray)) {
            $subgroupWhereClause .= implode(' ' . ($bAllow ? 'OR' : 'AND') . ' ', $subgroupWhereClauseArray);
            $whereClausePart2Array[] = '( ' . $subgroupWhereClause . ' )';
        }

        if (count($deniedUserGroupArray)) {
            $uidArray = $GLOBALS['TYPO3_DB']->fullQuoteArray($deniedUserGroupArray, $theTable);
            $whereClausePart2Array[] = 'uid ' . ($bAllow ? 'NOT IN' : 'IN') . ' (' . implode(',', $uidArray) . ')';
        }

        if (count($whereClausePart2Array)) {
            $whereClausePart2 = implode(' ' . ($bAllow ? 'AND' : 'OR') . ' ', $whereClausePart2Array);
            $whereClause .= ' AND (' . $whereClausePart2 . ')';
        }

        return $whereClause;
    }

    public function parseOutgoingData (
        $theTable,
        $fieldname,
        $foreignTable,
        $cmdKey,
        $pid,
        $conf,
        $dataArray,
        $origArray,
        &$parsedArray
    )
    {
        if (
            isset($dataArray) &&
            is_array($dataArray) &&
            isset($dataArray[$fieldname]) &&
            is_array($dataArray[$fieldname])
        ) {
            $valuesArray = [];

            if (
                isset($origArray) &&
                is_array($origArray) &&
                isset($origArray[$fieldname]) &&
                is_array($origArray[$fieldname])
            ) {
                $valuesArray = $origArray[$fieldname];

                if ($conf[$cmdKey . '.']['keepUnselectableUserGroups']) {
                    $whereClause =
                        $this->getAllowedWhereClause(
                            $foreignTable,
                            $pid,
                            $conf,
                            $cmdKey,
                            false
                        );
                    $rowArray =
                        $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                            'uid',
                            $foreignTable,
                            $whereClause,
                            '',
                            '',
                            '',
                            'uid'
                        );

                    if ($rowArray && is_array($rowArray) && count($rowArray)) {
                        $keepValues = array_keys($rowArray);
                    }
                } else {
                    $keepValues = $this->getReservedValues($conf);
                }
                if (
                    isset($keepValues) &&
                    is_array($keepValues)
                ) {
                    $valuesArray = array_intersect($valuesArray, $keepValues);
                }
            }

            $dataArray[$fieldname] = array_unique(array_merge($dataArray[$fieldname], $valuesArray));
            $parsedArray[$fieldname] = $dataArray[$fieldname];
        }
    }

    public function getExtendedValue ($extKey, $value, $config, $row)
    {
        if (isset($config) && is_array($config) && isset($row['cnum'])) {
            foreach ($config as $key => $lineConfig) {
                if (
                    isset($lineConfig) &&
                    is_array($lineConfig) &&
                    isset($lineConfig['uid']) &&
                    isset($lineConfig['file'])
                ) {
                    $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                    $dataFilename = $sanitizer->sanitize($lineConfig['file']);
                    $absFilename = GeneralUtility::getFileAbsFileName($dataFilename);
                    $handle = fopen($absFilename, 'rt');
                    if ($handle === false) {
                        throw new \Exception($extKey . ': File not found ("' . $absFilename . '")');
                    } else {
                        // Dateityp bestimmen
                        $basename = basename($dataFilename);
                        $posFileExtension = strrpos($basename, '.');
                        $fileExtension = substr($basename, $posFileExtension + 1);

                        if ($fileExtension == 'xml') {
                            $objDom = new \domDocument();
                            $objDom->encoding = 'utf-8';
                            $resultLoad = $objDom->load($absFilename, LIBXML_COMPACT);

                            if ($resultLoad) {

                                $bRowFits = false;
                                $objRows = $objDom->getElementsByTagName('Row');

                                foreach ($objRows as $myRow) {
                                    $tag = $myRow->nodeName;
                                    if ($tag == 'Row') {
                                        $objRowDetails = $myRow->childNodes;
                                        $xmlRow = [];
                                        $count = 0;

                                        foreach ($objRowDetails as $rowDetail) {
                                            $count++;
                                            $detailValue = '';
                                            $detailTag = $rowDetail->nodeName;

                                            if ($detailTag != '#text') {
                                                $detailValue = trim($rowDetail->nodeValue);
                                                $xmlRow[$detailTag] = $detailValue;
                                            }
                                            if ($count > 30) {
                                                break;
                                            }
                                        }

                                        // strip off leading zeros
                                        $cnumInput = preg_replace('@^(0*)@', '', $row['cnum']);
                                        $cnumXml = preg_replace('@^(0*)@', '', $xmlRow['cnum']);

                                        if (
                                            $cnumInput != '' &&
                                            $cnumInput == $cnumXml
                                        ) {
                                            $textArray = 
                                                [$row['last_name'], $xmlRow['last_name']];
                                            $nameArray = [];
                                            foreach ($textArray as $text) {
                                                $text = strtolower($text);
                                                $text = preg_replace('@\x{00e4}@u', 'ae', $text); // umlaut ä => ae
                                                $text = preg_replace('@\x{00f6}@u', 'oe', $text); // umlaut ö => oe
                                                $text = preg_replace('@\x{00fc}@u', 'ue', $text); // umlaut ü => ue
                                                $nameArray[] = $text;
                                            }

                                            if ($row['email'] == $xmlRow['email']) {
                                                $bRowFits = true;
                                            } else if (
                                                $nameArray['0'] == $nameArray['1'] &&
                                                $row['zip'] == $xmlRow['zip']
                                            ) {
                                                $bRowFits = true;
                                            }
                                        }

                                        if ($bRowFits) {
                                            break;
                                        }
                                    }
                                }
                                if ($bRowFits) {
                                    $value = intval($lineConfig['uid']);
                                    break;
                                }
                            } else {
                                throw new \Exception($extKey . ': The file "' . $absFilename . '" is not XML valid.');
                            }
                        } else {
                            throw new \Exception($extKey . ': The file "' . $absFilename . '" has an invalid extension.');
                        }
                    }
                }
            }
        }

        return $value;
    }
}

