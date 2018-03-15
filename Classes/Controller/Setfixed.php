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

use JambageCom\Agency\Controller\Email;


class Setfixed {

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
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
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
        \JambageCom\Agency\View\Template $template,        
        \JambageCom\Agency\View\CreateView $displayObj,
        \JambageCom\Agency\View\EditView $editView,
        \JambageCom\Agency\View\DeleteView $deleteView,
        $templateCode,
        $dataArray,
        array $origArray,
        $securedArray,
        $pObj,
        $token,
        &$hasError
    ) {
        $email = GeneralUtility::makeInstance(Email::class);
        $content = false;
        $row = $origArray;
        $usesPassword = false;
        $enableAutoLoginOnConfirmation =
            $controlData->enableAutoLoginOnConfirmation($conf, $cmdKey);
        $errorContent = '';
        $hasError = false;
        $sendExecutionEmail = false;
        $cryptedPassword = '';
        $extensionKey = $controlData->getExtensionKey();

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

        if ($controlData->getSetfixedEnabled()) {
            $autoLoginIsRequested = false;
            $origUsergroup = $row['usergroup'];
            $setfixedUsergroup = '';
            $setfixedSuffix = $setFixedKey = $controlData->getFeUserData('sFK');
            $fD = $controlData->getFd();
            $setfixedConfig = array();
            if (
                isset($conf['setfixed.']) &&
                isset($conf['setfixed.'][$setfixedSuffix . '.']) &&
                isset($conf['setfixed.'][$setfixedSuffix . '.']['_CONFIG.'])
            ) {
                $setfixedConfig = $conf['setfixed.'][$setfixedSuffix . '.']['_CONFIG.'];
            }

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
                            $controlData->getFeUserData(),
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
                in_array($setFixedKey, $controlData->getSetfixedOptions())
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
                    $sendExecutionEmail = true;
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmdKey,
                        $token,
                        $setFixedKey,
                        $fD
                    );
                    $content = $editView->render(
                        $markerArray,
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $template,
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
                        $setfixedConfig['askAgain'] &&
                        !$controlData->getSubmit()
                    ) { // ask again if the user really wants to delete
                        $content = $deleteView->render(
                            $markerArray,
                            $conf,
                            $prefixId,
                            $extensionKey,
                            $cObj,
                            $langObj,
                            $controlData,
                            $confObj,
                            $tcaObj,
                            $markerObj,
                            $dataObj,
                            $theTable,
                            $dataArray,
                            $origArray,
                            $securedArray,
                            $token,
                            $setFixedKey,
                            $fD
                        );
                    } else {
                        $sendExecutionEmail = true;
                        // execute the deletion
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
                    }
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
                    if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'] as $classRef) {
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
                        $token,
                        $setFixedKey,
                        $fD
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
                        $token,
                        $setFixedKey,
                        $fD
                    );
                }


                if ($setFixedKey != 'EDIT') {

                    if (
                        $setfixedConfig['askAgain'] &&
                        !$controlData->getSubmit()
                    ) { // ask again if the user really wants to confirm
                        $content =
                            $this->confirmationScreen(
                                $markerArray,
                                $conf,
                                $prefixId,
                                $cObj,
                                $langObj,
                                $controlData,
                                $confObj,
                                $tcaObj,
                                $markerObj,
                                $dataObj,
                                $template,
                                $templateCode,
                                $theTable,
                                $dataArray,
                                $origArray,
                                $securedArray,
                                $cmdKey,
                                $setFixedKey,
                                $fD,
                                $token
                            );
                    }

                    if (
                        !$content &&
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
                            $sendExecutionEmail = true; // +++ neu FHO; Hier noch eine Abfrage, bevor die Confirmation beginnt
                            $content =
                                $editView->render(
                                    $markerArray,
                                    $conf,
                                    $cObj,
                                    $langObj,
                                    $controlData,
                                    $confObj,
                                    $tcaObj,
                                    $markerObj,
                                    $dataObj,
                                    $template,
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
                                $template->getPlainTemplate(
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
                            $template->getPlainTemplate(
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
                            $sendExecutionEmail = true;
                    }

                    if (!$content) {
                        $subpartMarker = '###TEMPLATE_' . SETFIXED_PREFIX . 'OK###';
                        $content =
                            $template->getPlainTemplate(
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
                        $sendExecutionEmail = true;
                    }

                    if (
                        !$hasError &&
                        $sendExecutionEmail && // neu
                        (
                            $conf['email.']['SETFIXED_REFUSE'] ||
                            $conf['enableEmailConfirmation'] ||
                            $conf['infomail']
                        )
                    ) {
                        $errorCode = '';
                            // Compiling email
                        $bEmailSent = $email->compile(
                            SETFIXED_PREFIX . $setfixedSuffix,
                            $conf,
                            $cObj,
                            $langObj,
                            $controlData,
                            $confObj,
                            $tcaObj,
                            $markerObj,
                            $dataObj,
                            $template,
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
                            $bEmailSent = $email->compile(
                                SETFIXED_PREFIX . 'REVIEW',
                                $conf,
                                $cObj,
                                $langObj,
                                $controlData,
                                $confObj,
                                $tcaObj,
                                $markerObj,
                                $dataObj,
                                $template,
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
                                $content = $template->getPlainTemplate(
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
                $content = $template->getPlainTemplate(
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
    * Shows a form where the user is asked if he really wants to confirm
    *
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $errorFieldArray: array of field with errors (former $this->data->inError[$theField])
    * @return string  the template with substituted markers
    */
    public function confirmationScreen (
        $markerArray,
        $conf,
        $prefixId,
        $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        \JambageCom\Agency\View\Template $template,
        $templateCode,
        $theTable,
        $dataArray,
        array $origArray,
        $securedArray,
        $cmdKey,
        $setFixedKey,
        $fD
    ) {
    // Display the form, if access granted.

        $markerArray['###HIDDENFIELDS###'] .=
            '<input type="hidden" name="' .
            $prefixId . '[rU]" value="' .
            $dataObj->getRecUid() .
            '" />';

        $tokenParameter = $controlData->getTokenParameter();
        $markerArray['###BACK_URL###'] =
            (
                $controlData->getBackURL() ?
                    $controlData->getBackURL() :
                    $cObj->getTypoLink_URL(
                        $conf['loginPID'] . ',' . $GLOBALS['TSFE']->type
                    )
            ) . $tokenParameter;
        $subpartMarker = '###TEMPLATE_' . $setFixedKey . '_PREVIEW###';
        $content = $template->getPlainTemplate(
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
            $dataArray,
            $theTable,
            $prefixId,
            $origArray,
            $securedArray
        );

        return $content;
    }
}

