<?php

namespace JambageCom\Agency\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
* @author   Kasper Skaarhoj <kasperXXXX@typo3.com>
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;

class SetFixed {
    public $previewLabel;
    public $setfixedEnabled;
    public $cObj;
    public $buttonLabelsList;
    public $otherLabelsList;


    /**
    * Process the front end user reply to the confirmation request
    *
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param string $theTable: the table in use
    * @param array $autoLoginKey: the auto-login key
    * @param string $prefixId: the extension prefix id
    * @param array  Array with key/values being marker-strings/substitution values.
    * @return string  the template with substituted markers
    */
    public function process (
        $conf,
        $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        $theTable,
        $autoLoginKey,
        $prefixId,
        $uid,
        $cmdKey,
        $markerArray,
        $displayObj,
        $emailObj,
        $templateCode,
        $dataArray,
        array $origArray,
        $securedArray,
        $pObj,
        $feuData,
        $token,
        &$hasError
    ) {
        $content = false;
        $row = $origArray;
        $usesPassword = false;
        $enableAutoLoginOnConfirmation = $controlData->enableAutoLoginOnConfirmation($conf, $cmdKey);

        if (
            $theTable == 'fe_users' &&
            (
                !$row['by_invitation'] ||
                (
                    $cmdKey == 'invite' &&
                    !$enableAutoLoginOnConfirmation
                )
            ) &&
            !$row['lost_password']
        ) {
            $usesPassword = true;
        }

        $errorContent = '';
        $hasError = false;
        $cryptedPassword = '';

        if ($controlData->getSetfixedEnabled()) {
            $autoLoginIsRequested = false;
            $origUsergroup = $row['usergroup'];
            $setfixedUsergroup = '';
            $setfixedSuffix = $setFixedKey = $feuData['sFK'];
            $fD = GeneralUtility::_GP('fD', 1);
            $fieldArr = array();

            if (is_array($fD)) {
                foreach ($fD as $field => $value) {
                    $row[$field] = rawurldecode($value);
                    if ($field == 'usergroup') {
                        $setfixedUsergroup = $row[$field];
                    }
                    $fieldArr[] = $field;
                }
            }

            $autoLoginKey = '';
            if ($theTable == 'fe_users') {
                    // Determine if auto-login is requested
                $autoLoginIsRequested =
                    $controlData->getStorageSecurity()
                        ->getAutoLoginIsRequested(
                            $feuData,
                            $autoLoginKey
                        );
            }

            $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);
                // Calculate the setfixed hash from incoming data
            $fieldList = $row['_FIELDLIST'];
            $codeLength = strlen($authObj->getAuthCode());
            $theAuthCode = '';

                // Let's try with a code length of 8 in case this link is coming from direct mail
            if (
                $codeLength == 8 &&
                in_array($setFixedKey, array('DELETE', 'EDIT', 'UNSUBSCRIBE'))
            ) {
                $theAuthCode = $authObj->setfixedHash($row, $fieldList, $codeLength);
            } else {
                $theAuthCode = $authObj->setfixedHash($row, $fieldList);
            }

            if (
                !strcmp($authObj->getAuthCode(), $theAuthCode) &&
                !(
                    $setFixedKey == 'APPROVE' &&
                    count($origArray) &&
                    $origArray['disable'] == '0'
                )
            ) {
                if ($setFixedKey == 'EDIT') {
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmd,
                        $token
                    );
                    $content = $displayObj->editScreen(
                        $markerArray,
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $theTable,
                        $prefixId,
                        $dataArray,
                        $origArray,
                        $securedArray,
                        'setfixed',
                        $cmdKey,
                        $controlData->getMode(),
                        $dataObj->getInError(),
                        $token
                    );
                } else if (
                    $setFixedKey == 'DELETE' ||
                    $setFixedKey == 'REFUSE'
                ) {
                    if (
                        !$GLOBALS['TCA'][$theTable]['ctrl']['delete'] ||
                        $conf['forceFileDelete']
                    ) {
                        // If the record is fully deleted... then remove the image attached.
                        $dataObj->deleteFilesFromRecord(
                            $theTable,
                            $row
                        );
                    }
                    $res = $dataObj->getCoreQuery()->DBgetDelete(
                        $theTable,
                        $uid,
                        true
                    );
                    $dataObj->deleteMMRelations(
                        $theTable,
                        $uid,
                        $row
                    );
                } else {
                    if ($theTable == 'fe_users') {
                        if ($conf['create.']['allowUserGroupSelection']) {
                            $originalGroups = is_array($origUsergroup)
                                ? $origUsergroup
                                : GeneralUtility::trimExplode(',', $origUsergroup, true);
                            $overwriteGroups = GeneralUtility::trimExplode(
                                ',',
                                $conf['create.']['overrideValues.']['usergroup'],
                                true
                            );

                            $remainingGroups = array_diff($originalGroups, $overwriteGroups);
                            $groupsToAdd = GeneralUtility::trimExplode(',', $setfixedUsergroup, true);
                            $finalGroups = array_merge(
                                $remainingGroups, $groupsToAdd
                            );
                            $row['usergroup'] = implode(',', array_unique($finalGroups));
                        }
                    }

                        // Hook: first we initialize the hooks
                    $hookObjectsArr = array();
                    if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$controlData->getExtKey()]['confirmRegistrationClass'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$controlData->getExtKey()]['confirmRegistrationClass'] as $classRef) {
                            $hookObj = GeneralUtility::makeInstance($classRef);
                            if (
                                method_exists($hookObj, 'needsInit') &&
                                method_exists($hookObj, 'init') &&
                                $hookObj->needsInit()
                            ) {
                                $hookObj->init($dataObj);
                            }

                            $hookObjectsArr[] = $hookObj;
                        }
                    }
                    $newFieldList = implode(',', array_intersect(
                        GeneralUtility::trimExplode(',', $dataObj->getFieldList(), 1),
                        GeneralUtility::trimExplode(',', implode($fieldArr, ','), 1)
                    ));
                    $errorCode = '';

                        // Hook: confirmRegistrationClass_preProcess
                    foreach($hookObjectsArr as $hookObj) {
                        if (method_exists($hookObj, 'confirmRegistrationClass_preProcess')) {
                            $hookObj->confirmRegistrationClass_preProcess(
                                $controlData,
                                $theTable,
                                $row,
                                $newFieldList,
                                $this,
                                $errorCode
                            );
                            if ($errorCode) {
                                break;
                            }
                        }
                    }

                    if ($setFixedKey == 'UNSUBSCRIBE') {
                        $newFieldList = implode(',', array_intersect(
                            GeneralUtility::trimExplode(',', $newFieldList),
                            GeneralUtility::trimExplode(',', $conf['unsubscribeAllowedFields'], 1)
                        ));
                    }

                    if (
                        $setFixedKey != 'ENTER' &&
                        $newFieldList != ''
                    ) {
                        $res = $dataObj->getCoreQuery()->DBgetUpdate(
                            $theTable,
                            $uid,
                            $row,
                            $newFieldList,
                            true
                        );
                    }
                    $currenArray = $origArray;

                    if ($autoLoginIsRequested) {
                        $cryptedPassword = $currenArray['tx_agency_password'];
                        $controlData->getStorageSecurity()
                            ->decryptPasswordForAutoLogin(
                                $cryptedPassword,
                                $autoLoginKey
                            );
                    }
                    $modArray = array();
                    $currenArray =
                        $tcaObj->modifyTcaMMfields(
                            $theTable,
                            $currenArray,
                            $modArray
                        );
                    $row = array_merge($row, $modArray);
                    \tx_div2007_alpha5::userProcess_fh002(
                        $pObj,
                        $conf['setfixed.'],
                        'userFunc_afterSave',
                        array('rec' => $currenArray, 'origRec' => $origArray)
                    );

                        // Hook: confirmRegistrationClass_postProcess
                    foreach($hookObjectsArr as $hookObj) {
                        if (method_exists($hookObj, 'confirmRegistrationClass_postProcess')) {
                            $hookObj->confirmRegistrationClass_postProcess(
                                $controlData,
                                $theTable,
                                $row,
                                $currenArray,
                                $origArray,
                                $this
                            );
                        }
                    }
                }

                    // Outputting template
                if (
                    $theTable == 'fe_users' &&
                        // LOGIN is here only for an error case  ???
                    in_array($setFixedKey, array('APPROVE', 'ENTER', 'LOGIN'))
                ) {
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $usesPassword ?
                            'login' :
                            'password',
                        $token
                    );
                    if ($usesPassword) {
                        $markerObj->addPasswordTransmissionMarkers(
                            $markerArray,
                            $controlData->getUsePasswordAgain()
                        );
                        $markerObj->setArray($markerArray);
                    }
                } else {
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        'setfixed',
                        $token
                    );
                }

                if ($setFixedKey != 'EDIT') {
                    if (
                        $theTable == 'fe_users' &&
                        (
                            $setFixedKey == 'ENTER' ||
                            (
                                $setFixedKey == 'APPROVE' &&
                                $enableAutoLoginOnConfirmation
                            )
                        ) &&
                        !$usesPassword
                    ) {
                            // Auto-login
                        $loginSuccess =
                            $pObj->login(
                                $conf,
                                $langObj,
                                $controlData,
                                $currenArray['username'],
                                $cryptedPassword,
                                false
                            );

                        if ($loginSuccess) {
                            $content =
                                $displayObj->editScreen(
                                    $markerArray,
                                    $conf,
                                    $cObj,
                                    $langObj,
                                    $controlData,
                                    $confObj,
                                    $tcaObj,
                                    $markerObj,
                                    $dataObj,
                                    $theTable,
                                    $prefixId,
                                    $dataArray,
                                    $origArray,
                                    $securedArray,
                                    'password',
                                    'password',
                                    $controlData->getMode(),
                                    $dataObj->getInError(),
                                    $token
                                );
                        } else {
                                // Login failed
                            $content =
                                $displayObj->getPlainTemplate(
                                    $conf,
                                    $cObj,
                                    $langObj,
                                    $controlData,
                                    $confObj,
                                    $tcaObj,
                                    $markerObj,
                                    $dataObj,
                                    $templateCode,
                                    '###TEMPLATE_SETFIXED_LOGIN_FAILED###',
                                    $markerArray,
                                    $origArray,
                                    $theTable,
                                    $prefixId,
                                    array(),
                                    ''
                                );
                            $hasError = true;
                        }
                    }

                    if (
                        $conf['enableAdminReview'] &&
                        $setFixedKey == 'APPROVE'
                    ) {
                        $setfixedSuffix .= '_REVIEW';
                    }

                    if (!$content) {
                        $subpartMarker = '###TEMPLATE_' . SETFIXED_PREFIX . 'OK_' . $setfixedSuffix . '###';
                        $content =
                            $displayObj->getPlainTemplate(
                                $conf,
                                $cObj,
                                $langObj,
                                $controlData,
                                $confObj,
                                $tcaObj,
                                $markerObj,
                                $dataObj,
                                $templateCode,
                                $subpartMarker,
                                $markerArray,
                                $origArray,
                                $theTable,
                                $prefixId,
                                $row,
                                $securedArray,
                                false
                            );
                    }

                    if (!$content) {
                        $subpartMarker = '###TEMPLATE_' . SETFIXED_PREFIX . 'OK###';
                        $content =
                            $displayObj->getPlainTemplate(
                                $conf,
                                $cObj,
                                $langObj,
                                $controlData,
                                $confObj,
                                $tcaObj,
                                $markerObj,
                                $dataObj,
                                $templateCode,
                                $subpartMarker,
                                $markerArray,
                                $origArray,
                                $theTable,
                                $prefixId,
                                $row,
                                $securedArray
                            );
                    }

                    if (
                        !$hasError &&
                        (
                            $conf['email.']['SETFIXED_REFUSE'] ||
                            $conf['enableEmailConfirmation'] ||
                            $conf['infomail']
                        )
                    ) {
                        $errorCode = '';
                            // Compiling email
                        $bEmailSent = $emailObj->compile(
                            SETFIXED_PREFIX . $setfixedSuffix,
                            $conf,
                            $cObj,
                            $langObj,
                            $controlData,
                            $confObj,
                            $tcaObj,
                            $markerObj,
                            $dataObj,
                            $displayObj,
                            $this,
                            $theTable,
                            $autoLoginKey,
                            $prefixId,
                            array($row),
                            array($origArray),
                            $securedArray,
                            $origArray[$conf['email.']['field']],
                            $markerArray,
                            'setfixed',
                            $cmdKey,
                            $templateCode,
                            $dataObj->getInError(),
                            $conf['setfixed.'],
                            $errorCode
                        );
                    }

                    if (
                        !$bEmailSent &&
                        is_array($errorCode)
                    ) {
                        $errorText = $langObj->getLL($errorCode['0'], '', false, true);
                        $errorContent = sprintf($errorText, $errorCode['1']);
                        $content = $errorContent;
                    } else if ($theTable == 'fe_users') {
                            // If applicable, send admin a request to review the registration request
                        if (
                            $conf['enableAdminReview'] &&
                            $setFixedKey == 'APPROVE' &&
                            $usesPassword
                        ) {
                            $errorCode = '';
                            $bEmailSent = $emailObj->compile(
                                SETFIXED_PREFIX . 'REVIEW',
                                $conf,
                                $cObj,
                                $langObj,
                                $controlData,
                                $confObj,
                                $tcaObj,
                                $markerObj,
                                $dataObj,
                                $displayObj,
                                $this,
                                $theTable,
                                $autoLoginKey,
                                $prefixId,
                                array($row),
                                array($origArray),
                                $securedArray,
                                $origArray[$conf['email.']['field']],
                                $markerArray,
                                'setfixed',
                                $cmdKey,
                                $templateCode,
                                $dataObj->getInError(),
                                $conf['setfixed.'],
                                $errorCode
                            );

                            if (
                                !$bEmailSent &&
                                is_array($errorCode)
                            ){
                                $errorText = $langObj->getLL($errorCode['0'], '', false, true);
                                $errorContent = sprintf($errorText, $errorCode['1']);
                            }
                        }

                        if ($errorContent) {
                            $content = $errorContent;
                        } else if (
                                // Auto-login on confirmation
                            $enableAutoLoginOnConfirmation &&
                            $usesPassword &&
                            (
                                (
                                    $setFixedKey == 'APPROVE' &&
                                    !$conf['enableAdminReview']
                                ) ||
                                $setFixedKey == 'ENTER'
                            ) &&
                            $autoLoginIsRequested
                        ) {
                            $loginSuccess =
                                $pObj->login(
                                    $conf,
                                    $langObj,
                                    $controlData,
                                    $currenArray['username'],
                                    $cryptedPassword,
                                    $currenArray
                                );

                            if ($loginSuccess) {
                                    // Login was successful
                                exit;
                            } else {
                                    // Login failed
                                $content = $displayObj->getPlainTemplate(
                                    $conf,
                                    $cObj,
                                    $langObj,
                                    $controlData,
                                    $confObj,
                                    $tcaObj,
                                    $markerObj,
                                    $dataObj,
                                    $templateCode,
                                    '###TEMPLATE_SETFIXED_LOGIN_FAILED###',
                                    $markerArray,
                                    $origArray,
                                    $theTable,
                                    $prefixId,
                                    array(),
                                    ''
                                );
                                $hasError = true;
                            }
                        } else {
                            // confirmation after INVITATION
                        }
                    }
                }
            } else {
                $content = $displayObj->getPlainTemplate(
                    $conf,
                    $cObj,
                    $langObj,
                    $controlData,
                    $confObj,
                    $tcaObj,
                    $markerObj,
                    $dataObj,
                    $templateCode,
                    '###TEMPLATE_SETFIXED_FAILED###',
                    $markerArray,
                    $origArray,
                    $theTable,
                    $prefixId,
                    array(),
                    ''
                );
            }
        }

        return $content;
    }	// processSetFixed

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
    public function computeUrl (
        $nextCmd,
        $prefixId,
        $cObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        &$markerArray,
        $setfixed,
        array $record,
        $theTable,
        $useShortUrls,
        $editSetfixed,
        $autoLoginKey,
        $confirmType
    ) {
        if ($controlData->getSetfixedEnabled() && is_array($setfixed)) {
            $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);

            foreach($setfixed as $theKey => $data) {
                if (strstr($theKey, '.')) {
                    $theKey = substr($theKey, 0, -1);
                }
                $setfixedpiVars = array();
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
                            $tablesObj = GeneralUtility::makeInstance(\JambageCom\Agency\Domain\Tables::class);
                            $addressObj = $tablesObj->get('address');
                            $userGroupObj = $addressObj->getFieldObj('usergroup');

                            if (is_object($userGroupObj)) {
                                $fieldValue =
                                    $userGroupObj->getExtendedValue(
                                        $controlData->getExtKey(),
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

                if (is_array($data) ) {
                    foreach($data as $fieldname => $fieldValue) {
                        if (strpos($fieldname, '.') !== false) {
                            continue;
                        }
                        $setfixedpiVars['fD%5B' . $fieldname . '%5D'] = rawurlencode($fieldValue);
                    }
                }

                $linkPID = $controlData->getPid($pidCmd);

                if (
                    GeneralUtility::_GP('L') &&
                    !GeneralUtility::inList($GLOBALS['TSFE']->config['config']['linkVars'], 'L')
                ) {
                    $setfixedpiVars['L'] = GeneralUtility::_GP('L');
                }

                if ($useShortUrls) {
                    $thisHash = $this->storeFixedPiVars($setfixedpiVars);
                    $setfixedpiVars = array($prefixId . '%5BregHash%5D' => $thisHash);
                }
                $urlConf = array();
                $urlConf['disableGroupAccessCheck'] = true;
                $bconfirmTypeIsInt = \tx_div2007_core::testInt($confirmType);
                $confirmType = ($bconfirmTypeIsInt ? intval($confirmType) : $GLOBALS['TSFE']->type);
                $url =
                    \tx_div2007_alpha5::getTypoLink_URL_fh003(
                        $cObj,
                        $linkPID . ',' . $confirmType,
                        $setfixedpiVars,
                        '',
                        $urlConf
                    );

                $bIsAbsoluteURL = ((strncmp($url, 'http://', 7) == 0) || (strncmp($url, 'https://', 8) == 0));
                $markerKey = '###SETFIXED_' . $cObj->caseshift($theKey, 'upper') . '_URL###';
                $url = ($bIsAbsoluteURL ? '' : $controlData->getSiteUrl()) . ltrim($url, '/');
                $markerArray[$markerKey] = str_replace(array('[',']'), array('%5B', '%5D'), $url);
            }	// foreach
        }
    }	// computeUrl

    /**
    *  Store the setfixed vars and return a replacement hash
    */
    public function storeFixedPiVars (array $params) {

        $regHash_calc = \tx_div2007_core::generateHash($params, 20);

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
            $insertFields = array (
                'md5hash' => $regHash_calc,
                'tstamp' => time(),
                'type' => 99,
                'params' => serialize($params)
            );

            $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                'cache_md5params',
                $insertFields
            );
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $regHash_calc;
    }
}


