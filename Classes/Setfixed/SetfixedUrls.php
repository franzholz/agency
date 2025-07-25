<?php

declare(strict_types=1);

namespace JambageCom\Agency\Setfixed;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
*  This script is distributed in the hopFnue that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
* Part of the agency (Agency Registration) extension.
*
* setfixed functions. former class tx_agency_setfixed
*
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Domain\Tables;

class SetFixedUrls
{
    /**
    * Computes the setfixed url's
    *
    * @param array  $markerArray: the input marker array
    * @param array  $setfixed: the TS setup setfixed configuration
    * @param array  $record: the record row
    * @param array $controlData: the object of the control data
    * @param array $autoLoginKey: the auto-login key
    * @return void
    */
    public static function compute(
        $nextCmd,
        $prefixId,
        $cObj,
        Parameters $controlData,
        &$markerArray,
        $setfixed,
        array $record,
        $theTable,
        $extensionKey,
        $useShortUrls,
        $editSetfixed,
        $autoLoginKey,
        $confirmType
    ): void {
        if ($controlData->getSetfixedEnabled() && is_array($setfixed)) {
            $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
            $authObj = GeneralUtility::makeInstance(Authentication::class);

            foreach($setfixed as $theKey => $data) {
                if (strstr($theKey, '.')) {
                    $theKey = substr($theKey, 0, -1);
                }
                $setfixedpiVars = [];
                $noFeusersEdit = false;

                if ($theTable != 'fe_users' && $theKey == 'EDIT') {
                    $noFeusersEdit = true;
                }

                $setfixedpiVars[$prefixId . '%5BrU%5D'] = $record['uid'];
                $fieldList = $data['_FIELDLIST'];
                $fieldListArray = GeneralUtility::trimExplode(',', $fieldList);

                foreach ($fieldListArray as $fieldname) {
                    if (isset($data[$fieldname])) {
                        $fieldValue = $data[$fieldname];

                        if ($fieldname == 'usergroup' && $data['usergroup.']) {
                            $tablesObj = GeneralUtility::makeInstance(Tables::class);
                            $addressObj = $tablesObj->get('address');
                            $userGroupObj = $addressObj->getFieldObj('usergroup');

                            if (is_object($userGroupObj)) {
                                $fieldValue =
                                    $userGroupObj->getExtendedValue(
                                        $extensionKey,
                                        $fieldValue,
                                        $data['usergroup.'],
                                        $record
                                    );

                                $data[$fieldname] = $fieldValue;
                            }
                        }
                        $record[$fieldname] = $fieldValue;
                    }
                }

                $theCmd = '';
                $pidCmd = '';

                if ($noFeusersEdit) {
                    $theCmd = $pidCmd = 'edit';
                    if($editSetfixed) {
                        $bSetfixedHash = true;
                    } else {
                        $bSetfixedHash = false;
                        // calculate the 'aC' parameter used as authentication code
                        $setfixedpiVars[$prefixId . '%5BaC%5D'] =
                            $authObj->generateAuthCode(
                                $record,
                                $fieldList
                            );
                    }
                } else {
                    $theCmd = 'setfixed';
                    $pidCmd = 'confirm';
                    if ($nextCmd == 'invite') {
                        $pidCmd = 'confirmInvitation';
                    }
                    if ($nextCmd == 'password') {
                        $pidCmd = 'password';
                    }
                    $setfixedpiVars[$prefixId . '%5BsFK%5D'] = $theKey;
                    $bSetfixedHash = true;

                    if (
                        $useShortUrls &&
                        $autoLoginKey != ''
                    ) {
                        $setfixedpiVars[$prefixId . '%5Bkey%5D'] = $autoLoginKey;
                    }
                }

                if ($bSetfixedHash) {
                    $setfixedpiVars[$prefixId . '%5BaC%5D'] =
                        $authObj->setfixedHash($record, $fieldList);
                }
                $setfixedpiVars[$prefixId . '%5Bcmd%5D'] = $theCmd;

                if (is_array($data)) {
                    foreach($data as $fieldname => $fieldValue) {
                        if (strpos($fieldname, '.') !== false) {
                            continue;
                        }
                        $setfixedpiVars['fD%5B' . $fieldname . '%5D'] = rawurlencode($fieldValue);
                    }
                }

                $linkPID = $controlData->getPid($pidCmd);

                if (
                    $parameterApi->getParameter('L') &&
                    !GeneralUtility::inList($GLOBALS['TSFE']->config['config']['linkVars'], 'L')
                ) {
                    $setfixedpiVars['L'] = $parameterApi->getParameter('L');
                }

                if ($useShortUrls) {
                    $theHash = self::storeFixedPiVars($setfixedpiVars);
                    $setfixedpiVars = [$prefixId . '%5BregHash%5D' => $theHash];
                }
                $urlConf = [];
                $urlConf['disableGroupAccessCheck'] = true;
                $confirmType = (MathUtility::canBeInterpretedAsInteger($confirmType) ? intval($confirmType) : $controlData->getType();
                $url =
                    FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $linkPID . ',' . $confirmType,
                        $setfixedpiVars,
                        '',
                        $urlConf
                    );

                $bIsAbsoluteURL = ((strncmp($url, 'http://', 7) == 0) || (strncmp($url, 'https://', 8) == 0));
                $markerKey = '###SETFIXED_' . $cObj->caseshift($theKey, 'upper') . '_URL###';
                $url = ($bIsAbsoluteURL ? '' : $controlData->getSiteUrl()) . ltrim($url, '/');
                $markerArray[$markerKey] = str_replace(['[', ']'], ['%5B', '%5D'], $url);
            }	// foreach
        }
    }	// compute

    /**
    *  Store the setfixed vars and return a replacement hash
    */
    public static function storeFixedPiVars(array $params)
    {
        $hashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
        $calc = $hashCalculator->calculateCacheHash($params);
        $regHash_calc = substr($calc, 0, 20);

        // and store it with a serialized version of the array in the DB
        $res =
            $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'md5hash',
                'cache_md5params',
                'md5hash=' .
                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                        $regHash_calc,
                        'cache_md5params'
                    )
            );

        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
            $insertFields = ['md5hash' => $regHash_calc, 'tstamp' => time(), 'type' => 99, 'params' => serialize($params)];

            $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                'cache_md5params',
                $insertFields
            );
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $regHash_calc;
    }
}
