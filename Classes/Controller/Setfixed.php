<?php

declare(strict_types=1);

namespace JambageCom\Agency\Controller;

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
* @author   Kasper Skaarhoj <kasperXXXX@typo3.com>
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;
use JambageCom\Div2007\Utility\SystemUtility;

use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Api\System;
use JambageCom\Agency\Api\Url;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Controller\Email;
use JambageCom\Agency\Database\Data;
use JambageCom\Agency\Database\Tca;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Security\SecuredData;
use JambageCom\Agency\View\Marker;
use JambageCom\Agency\View\Template;
use JambageCom\Agency\View\CreateView;
use JambageCom\Agency\View\EditView;
use JambageCom\Agency\View\DeleteView;



class Setfixed implements SingletonInterface
{
    /**
    * Process the front end user reply to the confirmation request
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param string $theTable: the table in use
    * @param array $autoLoginKey: the auto-login key
    * @param string $prefixId: the extension prefix id
    * @param array  Array with key/values being marker-strings/substitution values.
    * @return string  the template with substituted markers
    */
    public function process(
        &$hasError,
        &$confirmationEmailSent,
        array $conf,
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        Parameters $controlData,
        Url $url,
        ConfigurationStore $confObj,
        Tca $tcaObj,
        Marker $markerObj,
        Data $dataObj,
        $theTable,
        $autoLoginKey,
        $prefixId,
        $uid,
        $cmdKey,
        array $markerArray,
        Template $template,
        CreateView $displayObj,
        EditView $editView,
        DeleteView $deleteView,
        $templateCode,
        array $dataArray,
        array $origArray,
        array $securedArray,
        $pObj,
        $token
    ) {
        $email = GeneralUtility::makeInstance(Email::class);
        $content = false;
        $request = $controlData->getRequest();
        $context = $controlData->getContext();
        $feUser = [];
        $feUser['uid'] = $context->getPropertyFromAspect('frontend.user', 'id');
        $feUser['usergroup'] = $context->getPropertyFromAspect('frontend.user', 'groupIds');
        $row = $currentArray = $origArray;
        $usesPassword = false;
        $enableAutoLoginOnConfirmation =
            Parameters::enableAutoLoginOnConfirmation($conf, $cmdKey);
        $systemObj =
            GeneralUtility::makeInstance(
                System::class,
                $controlData
            );
        $errorContent = '';
        $errorCode = [];
        $hasError = false;
        $sendExecutionEmail = false;
        $cryptedPassword = '';
        $loginSuccess = false;
        $extensionKey = $controlData->getExtensionKey();

        if (!$controlData->getSetfixedEnabled()) {
            return false;
        }

        if (
            $theTable == 'fe_users' &&
            !empty($row) &&
            (
                !$row['by_invitation'] ||
                $cmdKey == 'invite'
            ) &&
            !$row['lost_password'] &&
            !$enableAutoLoginOnConfirmation
        ) {
            $usesPassword = true;
        }

        $autoLoginIsRequested = false;
        $origUsergroup = $row['usergroup'] ?? 0;
        $setfixedUsergroup = '';
        $setfixedSuffix = $setFixedKey = $controlData->getFeUserData('sFK');
        $fD = $controlData->getFd();
        $setfixedConfig = [];
        if (
            isset($conf['setfixed.'][$setfixedSuffix . '.']['_CONFIG.'])
        ) {
            $setfixedConfig = $conf['setfixed.'][$setfixedSuffix . '.']['_CONFIG.'];
        }

        $fieldArray = [];

        if (is_array($fD)) {
            foreach ($fD as $field => $value) {
                $row[$field] = rawurldecode($value);
                if ($field == 'usergroup') {
                    $setfixedUsergroup = $row[$field] ?? 0;
                }
                $fieldArray[] = $field;
            }
        }

        $autoLoginKey = '';
        if ($theTable == 'fe_users') {
            // Determine if auto-login is requested
            $autoLoginIsRequested =
                $this->getAutoLoginIsRequested(
                    $controlData->getFeUserData(),
                    $autoLoginKey
                );
        }

        $authObj = GeneralUtility::makeInstance(Authentication::class);
        // Calculate the setfixed hash from incoming data
        $fieldList = $row['_FIELDLIST'] ?? '';
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
                !empty($origArray) &&
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
                    $errorCode,
                    $markerArray,
                    $conf,
                    $cObj,
                    $languageObj,
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
            } elseif (
                $setFixedKey == 'DELETE' ||
                $setFixedKey == 'REFUSE'
            ) {
                if (
                    !empty($setfixedConfig['askAgain']) &&
                    !$controlData->getSubmit()
                ) { // ask again if the user really wants to delete
                    $content = $deleteView->render(
                        $errorCode,
                        $markerArray,
                        $conf,
                        $prefixId,
                        $extensionKey,
                        $cObj,
                        $languageObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $dataArray,
                        $origArray,
                        $feUser,
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
            } else { // APPROVE, CREATE
                $newFieldList = '';

                if (
                    empty($setfixedConfig['askAgain']) ||
                    $controlData->getSubmit()
                ) { // ask again if the user really wants to confirm
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
                                $remainingGroups,
                                $groupsToAdd
                            );
                            $row['usergroup'] = implode(',', array_unique($finalGroups));
                        }
                    }

                    // Hook: first we initialize the hooks
                    $hookObjectsArray = [];
                    if (
                        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass']) &&
                        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'])
                    ) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['confirmRegistrationClass'] as $classRef) {
                            $hookObj = GeneralUtility::makeInstance($classRef);
                            if (
                                method_exists($hookObj, 'needsInit') &&
                                method_exists($hookObj, 'init') &&
                                $hookObj->needsInit()
                            ) {
                                $hookObj->init($dataObj);
                            }

                            $hookObjectsArray[] = $hookObj;
                        }
                    }
                    $newFieldList = implode(',', array_intersect(
                        GeneralUtility::trimExplode(',', $dataObj->getFieldList(), true),
                        GeneralUtility::trimExplode(',', implode(',', $fieldArray), true)
                    ));

                    // Hook: confirmRegistrationClass_preProcess
                    foreach($hookObjectsArray as $hookObj) {
                        if (method_exists($hookObj, 'confirmRegistrationClass_preProcess')) {
                            $hookObj->confirmRegistrationClass_preProcess(
                                $controlData,
                                $theTable,
                                $row,
                                $newFieldList,
                                $this,
                                $errorCode
                            );

                            if (!empty($errorCode)) {
                                break;
                            }
                        }
                    }

                    if ($setFixedKey == 'UNSUBSCRIBE') {
                        $newFieldList = implode(',', array_intersect(
                            GeneralUtility::trimExplode(',', $newFieldList),
                            GeneralUtility::trimExplode(',', $conf['unsubscribeAllowedFields'], true)
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

                    if ($autoLoginIsRequested) {
                        // $cryptedPassword = '';
                        $encoded = $currentArray['tx_agency_password'];
                        $cryptedPassword = base64_decode($encoded);

                        $errorCode = [];
                        $errorMessage = '';
                        \JambageCom\Agency\Security\SecuredData::getStorageSecurity()
                            ->decryptPasswordForAutoLogin(
                                $cryptedPassword,
                                $errorCode,
                                $errorMessage,
                                $autoLoginKey
                            );
                        $markerArray['###ENCRYPTION###'] = $cryptedPassword;
                    }

                    $modArray = [];
                    $currentArray =
                        $tcaObj->modifyTcaMMfields(
                            $theTable,
                            $currentArray,
                            $modArray
                        );
                    $row = array_merge($row, $modArray);
                    SystemUtility::userProcess(
                        $pObj,
                        $conf['setfixed.'],
                        'userFunc_afterSave',
                        [
                            'rec' => $currentArray,
                            'origRec' => $origArray
                        ]
                    );

                    // Hook: confirmRegistrationClass_postProcess
                    foreach($hookObjectsArray as $hookObj) {
                        if (method_exists($hookObj, 'confirmRegistrationClass_postProcess')) {
                            $hookObj->confirmRegistrationClass_postProcess(
                                $controlData,
                                $theTable,
                                $row,
                                $currentArray,
                                $origArray,
                                $this
                            );
                        }
                    }
                }
            } // neu Ende

            // Outputting template
            if (
                $theTable == 'fe_users' &&
                in_array($setFixedKey, ['APPROVE', 'ENTER', 'LOGIN'])
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
                    !empty($setfixedConfig['askAgain']) &&
                    !$controlData->getSubmit()
                ) { // ask again if the user really wants to confirm
                    $content =
                        $this->confirmationScreen(
                            $errorCode,
                            $markerArray,
                            $conf,
                            $prefixId,
                            $cObj,
                            $languageObj,
                            $controlData,
                            $url,
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
                            $fD
                        );
                    // $errorContent
                    if (!empty($errorCode)) {
                        $hasError = true;
                    }
                }

                if (
                    !$hasError &&
                    !$content &&
                    $theTable == 'fe_users' &&
                    (
                        $setFixedKey == 'ENTER' ||
                        (
                            $setFixedKey == 'APPROVE' &&
                            $enableAutoLoginOnConfirmation &&
                            !$conf['enableAdminReview']
                        )
                    ) &&
                    !$usesPassword
                ) {
                    $redirect = true;
                    // Auto-login
                    $loginSuccess =
                        $systemObj->login(
                            $cObj,
                            $languageObj,
                            $url,
                            $conf,
                            $currentArray['username'],
                            $cryptedPassword,
                            true,
                            $redirect
                        );

                    if ($loginSuccess) {
                        if ($setFixedKey != 'ENTER') {
                            $sendExecutionEmail = true;
                        }
                    } else {
                        // Login failed
                        $content =
                            $template->getPlainTemplate(
                                $errorCode,
                                $conf,
                                $cObj,
                                $languageObj,
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
                                [],
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

                if (
                    !$hasError &&
                    !$content
                ) {
                    $subpartMarker = '###TEMPLATE_' . SETFIXED_PREFIX . 'OK_' . $setfixedSuffix . '###';
                    $content =
                        $template->getPlainTemplate(
                            $errorCode,
                            $conf,
                            $cObj,
                            $languageObj,
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

                if (
                    !$hasError &&
                    !$content
                ) {
                    $subpartMarker = '###TEMPLATE_' . SETFIXED_PREFIX . 'OK###';
                    $content =
                        $template->getPlainTemplate(
                            $errorCode,
                            $conf,
                            $cObj,
                            $languageObj,
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

                $emailResult = true;
                if (
                    !$hasError &&
                    $sendExecutionEmail &&
                    (
                        $conf['email.']['SETFIXED_REFUSE'] ||
                        $conf['enableEmailConfirmation'] ||
                        $conf['infomail']
                    )
                ) {
                    $errorCode = [];
                    $subpart = SETFIXED_PREFIX . $setfixedSuffix;
                    // Compiling email
                    $emailResult = $email->compile(
                        $subpart,
                        $cObj,
                        $languageObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        [$row],
                        [$origArray],
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
                    !empty($errorCode)
                ) {
                    $errorText =
                        $languageObj->getLabel($errorCode[0], $dummy, '', false, true);
                    $errorContent = sprintf($errorText, $errorCode[1]);
                    $content = $errorContent;
                } elseif (
                    $emailResult &&
                    $theTable == 'fe_users'
                ) {
                    // If applicable, send admin a request to review the registration request

                    if (
                        $conf['enableAdminReview'] &&
                        $setFixedKey == 'APPROVE' &&
                        $sendExecutionEmail
                    ) {
                        $subpart = SETFIXED_PREFIX . 'REVIEW';
                        $emailResult = $email->compile(
                            $subpart,
                            $cObj,
                            $languageObj,
                            $controlData,
                            $confObj,
                            $tcaObj,
                            $markerObj,
                            $dataObj,
                            $template,
                            $theTable,
                            $autoLoginKey,
                            $prefixId,
                            [$row],
                            [$origArray],
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
                            !empty($errorCode)
                        ) {
                            $errorText =
                                $languageObj->getLabel($errorCode[0], $dummy, '', false, true);
                            if (isset($errorCode[1])) {
                                $errorContent = sprintf($errorText, $errorCode[1]);
                            } else {
                                $errorContent = $errorText;
                            }
                        } else if ($emailResult) {
                            $confirmationEmailSent = true;
                        }
                    }

                    if ($errorContent) {
                        $content = $errorContent;
                    } elseif (
                        !$hasError &&
                        !$content &&
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
                            $systemObj->login(
                                $cObj,
                                $languageObj,
                                $url,
                                $conf,
                                $currentArray['username'],
                                $cryptedPassword,
                                true,
                                true
                            );

                        // delete password helper fields
                        $systemObj->removePasswordAdditions(
                            $dataObj,
                            $theTable,
                            $uid,
                            $row
                        );

                        if ($loginSuccess) {
                            // Login was successful
                            exit;
                        } else {
                            // Login failed
                            $content = $template->getPlainTemplate(
                                $errorCode,
                                $conf,
                                $cObj,
                                $languageObj,
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
                                [],
                                ''
                            );
                            $hasError = true;
                        }
                    } else {
                        // confirmation after INVITATION
                    }
                }
            }
        } elseif (
            strcmp($authObj->getAuthCode(), $theAuthCode) ||  // Do not create an error in case of APPROVE after a redirect from a password set and autologin form
            !count($origArray) ||
            $origArray['disable'] == 1
        ) {
            $content = $template->getPlainTemplate(
                $errorCode,
                $conf,
                $cObj,
                $languageObj,
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
                [],
                ''
            );
            // TODO: Your registration has been confirmed .
        }
        return $content;
    }	// processSetFixed

    /**
    * Determines if auto login should be attempted
    *
    * @param array $feuData: incoming fe_users parameters
    * @param string &$autoLoginKey: returns auto-login key
    * @return boolean true, if auto-login should be attempted
    */
    public function getAutoLoginIsRequested(
        array $feuserData,
        &$autoLoginKey
    ) {
        $autoLoginIsRequested = false;
        if (
            isset($feuserData['key']) &&
            $feuserData['key'] !== ''
        ) {
            $autoLoginKey = $feuserData['key'];
            $autoLoginIsRequested = true;
        }

        return $autoLoginIsRequested;
    }

    /**
    * Shows a form where the user is asked if he really wants to confirm
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $errorFieldArray: array of field with errors (former $this->data->inError[$theField])
    * @return string  the template with substituted markers
    */
    public function confirmationScreen(
        array &$errorCode,
        $markerArray,
        $conf,
        $prefixId,
        $cObj,
        Localization $languageObj,
        Parameters $controlData,
        Url $url,
        ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        Template $template,
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
        $xhtmlFix = HtmlUtility::getXhtmlFix();
        $markerArray['###HIDDENFIELDS###'] .=
            '<input type="hidden" name="' .
            $prefixId . '[rU]" value="' .
            $dataObj->getRecUid() .
            '" ' . $xhtmlFix . '>';

        $markerArray['###BACK_URL###'] =
            (
                $controlData->getBackURL() ?:
                FrontendUtility::getTypoLink_URL(
                    $cObj,
                    $conf['loginPID'] . ',' . $controlData->getType()
                )
            );
        $subpartMarker = '###TEMPLATE_' . $setFixedKey . '_PREVIEW###';
        $content = $template->getPlainTemplate(
            $errorCode,
            $conf,
            $cObj,
            $languageObj,
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
