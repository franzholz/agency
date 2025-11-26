<?php

declare(strict_types=1);

namespace JambageCom\Agency\Database\Field;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\Agency\Domain\Repository\FrontendGroupRepository;

class UserGroup extends Base implements SingletonInterface
{
    protected $savedReservedValues = [];

    public function __construct(
        protected readonly FrontendGroupRepository $frontendGroupRepository
    ) {
    }

    /*
    * Modifies the form fields configuration depending on the $cmdKey
    *
    * @param array $conf: the configuration array
    * @param string $cmdKey: the command key
    * @return void
    */
    public function modifyConf(&$conf, $cmdKey): void
    {
        // Add usergroup to the list of fields and required fields if the user is allowed to select user groups
        // Except when only updating password
        if (
            !empty($cmdKey) &&
            $cmdKey != 'password' &&
            $cmdKey != 'delete'
        ) {
            if (isset($conf[$cmdKey . '.']['allowUserGroupSelection'])) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',usergroup', true)));
                $conf[$cmdKey . '.']['required'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',usergroup', true)));
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
    public function getAllowedValues(
        &$allowedUserGroupArray,
        &$allowedSubgroupArray,
        &$deniedUserGroupArray,
        $conf,
        $cmdKey
    ): void {
        $allowedUserGroupArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['allowedUserGroups'], true);
        $allowedSubgroupArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['allowedSubgroups'], true);
        $deniedUserGroupArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['deniedUserGroups'], true);
    }

    /*
    * Gets the array of user groups reserved for control of the registration process
    *
    * @return array the reserved user groups
    */
    public function getReservedValues($conf)
    {
        $result = array_merge(
            GeneralUtility::trimExplode(',', $conf['create.']['overrideValues.']['usergroup'], true),
            GeneralUtility::trimExplode(',', $conf['invite.']['overrideValues.']['usergroup'], true),
            GeneralUtility::trimExplode(',', $conf['setfixed.']['APPROVE.']['usergroup'], true),
            GeneralUtility::trimExplode(',', $conf['setfixed.']['ACCEPT.']['usergroup'], true),
            $this->savedReservedValues
        );

        $result = array_unique($result);

        return $result;
    }

    public function removeInvalidValues(
        $conf,
        $cmdKey,
        &$row
    ): void {
        if (
            isset($row['usergroup']) &&
            $conf[$cmdKey . '.']['allowUserGroupSelection']
        ) {
            // nothing
        } else {
            $row['usergroup'] = ''; // the setting of the usergropus has not been allowed
        }
    }

    public function getPidArray(int $pid, string $userGroupsPidList = ''): array
    {
        $pidArray = [];
        $tmpArray = GeneralUtility::trimExplode(',', $userGroupsPidList, true);
        if (count($tmpArray)) {
            foreach($tmpArray as $value) {
                $valueIsInt = MathUtility::canBeInterpretedAsInteger($value);
                if ($valueIsInt) {
                    $pidArray[] = intval($value);
                }
            }
        }
        if (empty($pidArray)) {
            $pidArray[] = $pid;
        }
        return $pidArray;
    }


    public function parseOutgoingData(
        $theTable,
        $fieldname,
        $foreignTable,
        $cmdKey,
        $pid,
        $conf,
        $dataArray,
        $origArray,
        &$parsedArray
    ): void {
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
                    $allowedUserGroupArray = [];
                    $allowedSubgroupArray = [];
                    $deniedUserGroupArray = [];

                    $this->getAllowedValues(
                        $allowedUserGroupArray,
                        $allowedSubgroupArray,
                        $deniedUserGroupArray,
                        $conf,
                        $cmdKey,
                    );

                    $pidArray = $this->getPidArray(
                        $conf['userGroupsPidList']
                    );

                    $whereArray = [];
                    $queryBuilder =
                        $this->frontendGroupRepository->getUserGroupWhereClause(
                            $whereArray,
                            $foreignTable,
                            $pidArray,
                            $conf,
                            $cmdKey,
                            $allowedUserGroupArray,
                            $allowedSubgroupArray,
                            $deniedUserGroupArray,
                            false
                        );

                    $keepValues = $this->frontendGroupRepository->getSearchedUids(
                        'uid',
                        $foreignTable,
                        $whereArray,
                        '',
                        '',
                        '',
                        'uid'
                    );

                    // $rowArray =
                    //     $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    //         'uid',
                    //         $foreignTable,
                    //         $whereClause,
                    //         '',
                    //         '',
                    //         '',
                    //         'uid'
                    //     );
                    //
                    /*
                    if (isset($rowArray) && is_array($rowArray) && count($rowArray)) {
                        $keepValues = array_keys($rowArray);
                    }*/
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

    public function getExtendedValue($extKey, $value, $config, $row)
    {
        if (isset($config) && is_array($config) && isset($row['cnum'])) {
            foreach ($config as $key => $lineConfig) {
                if (
                    isset($lineConfig) &&
                    is_array($lineConfig) &&
                    isset($lineConfig['uid']) &&
                    isset($lineConfig['file'])
                ) {
                    $dataFilename = $lineConfig['file'];
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
                                            } elseif (
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
