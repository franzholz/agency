<?php

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
* controller . former class tx_agency_control
*
* @author   Kasper Skaarhoj <kasper2007@typo3.com>
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

use JambageCom\Agency\Controller\Email;


class ActionController {
    public $langObj;
    public $auth;
    public $email;
    public $tca;
    public $requiredArray; // List of required fields
    public $controlData;
        // Commands that may be processed when no user is logged in
    public $noLoginCommands = array('create', 'invite', 'setfixed', 'infomail', 'login');


    public function init (
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        $tca,
        $urlObj
    ) {
        $this->langObj = $langObj;
        $conf = $confObj->getConf();
        $this->tca = $tca;
        $this->urlObj = $urlObj;
            // Retrieve the extension key
        $extensionKey = $controlData->getExtensionKey();
            // Get the command as set in piVars
        $cmd = $controlData->getCmd();

            // If not set, get the command from the flexform
        if ($cmd == '') {
                // Check the flexform
            $cObj->data['pi_flexform'] = GeneralUtility::xml2array($cObj->data['pi_flexform']);
            $cmd = \tx_div2007_alpha5::getSetupOrFFvalue_fh004(
                $langObj,
                '',
                '',
                $conf['defaultCODE'],
                $cObj->data['pi_flexform'],
                'display_mode'
            );
            $cmd = $cObj->caseshift($cmd, 'lower');
        }

        $controlData->setCmd($cmd);
    }


    /* write the global $conf only here */
    public function init2 (
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $staticInfoObj,
        $theTable,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Domain\Data &$dataObj,
        &$adminFieldList,
        array &$origArray
    ) {
        $conf = $confObj->getConf();
        $tablesObj = GeneralUtility::makeInstance(\JambageCom\Agency\Domain\Tables::class);
        $addressObj = $tablesObj->get('address');
        $extensionKey = $controlData->getExtensionKey();
        $cmd = $controlData->getCmd();
        $dataArray = $dataObj->getDataArray();
        $fieldlist = '';
        $modifyPassword = false;

        $bHtmlSpecialChars = false;
        $controlData->secureInput($dataArray, $bHtmlSpecialChars);

        if (
            $theTable == 'fe_users' &&
            !empty($dataArray)
        ) {
            $modifyPassword = $controlData->securePassword($dataArray);
        }
        $dataObj->setDataArray($dataArray);

        $fieldlist = implode(',', \tx_div2007_core::getFields($theTable));

        // new
        if ($cmd == 'password') {
            $fieldlist = implode(',', array_keys($dataArray));
            if ($modifyPassword) {
                $fieldlist .= ',password';
            }
        }

        $dataObj->setFieldList($fieldlist);

        if (
            isset($dataArray) &&
            is_array($dataArray) &&
            !empty($dataArray)
        ) {
            $this->tca->modifyRow(
                $staticInfoObj,
                $theTable,
                $dataArray,
                $fieldlist,
                false
            );
        }
        $feUserdata = $controlData->getFeUserData();
        $theUid = 0;
        $setFixedUid = false;

        if (is_array($dataArray) && $dataArray['uid']) {
            $theUid = $dataArray['uid'];
        } else if (is_array($feUserdata) && $feUserdata['rU']) {
            $theUid = $feUserdata['rU'];

            if ($cmd == 'setfixed') {
                $setFixedUid = true;
            }
        } else if (!in_array($cmd, $this->noLoginCommands)) {
            $theUid = $GLOBALS['TSFE']->fe_user->user['uid'];
        }

        if ($theUid) {
            $dataObj->setRecUid($theUid);
            $newOrigArray =
                $GLOBALS['TSFE']->sys_page->getRawRecord(
                    $theTable,
                    $theUid
                );

            if (isset($newOrigArray) && is_array($newOrigArray)) {
                $this->tca->modifyRow(
                    $staticInfoObj,
                    $theTable,
                    $newOrigArray,
                    $dataObj->getFieldList(),
                    true
                );
                $origArray = $newOrigArray;
            }
        }
            // Set the command key
        $cmdKey = '';

        if (
            $cmd == 'invite' ||
            $cmd == 'password' ||
            $cmd == 'infomail'
        ) {
            $cmdKey = $cmd;
        } else if (
            isset($origArray['uid']) &&
            (
                $theTable != 'fe_users' ||
                $theUid == $GLOBALS['TSFE']->fe_user->user['uid'] || // for security reason: do not allow the change of other user records
                $origArray['disable'] // needed for setfixed after INVITE
            )
        ) {
            if (
                (
                    $cmd == '' ||
                    $cmd == 'setfixed' ||
                    $cmd == 'edit'
                )
            ) {
                $cmdKey = 'edit';
            } else if (
                $cmd == 'delete'
            ) {
                $cmdKey = 'delete';
            }
        }

        if (
            $cmdKey == '' &&
            !$setFixedUid // Setfixed needs the original array in order to calculate the authorization key
        ) {
            $origArray = array(); // do not use the read in original array
            $cmdKey = 'create';
        }

        $controlData->setCmdKey($cmdKey);

        if (!$theUid) {
            if (!count($dataArray)) {
                $dataArray = $dataObj->readDefaultValues($cmdKey);
            }
        }

        if (trim($conf['addAdminFieldList'])) {
            $adminFieldList .= ',' . trim($conf['addAdminFieldList']);
        }
        $adminFieldList =
            implode(
                ',',
                array_intersect(
                    explode(',', $fieldlist),
                    GeneralUtility::trimExplode(',', $adminFieldList, 1)
                )
            );
        $dataObj->setAdminFieldList($adminFieldList);

        if (ExtensionManagementUtility::isLoaded('direct_mail')) {
            $conf[$cmdKey.'.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('module_sys_dmail_category,module_sys_dmail_newsletter')));
            $conf[$cmdKey . '.']['required'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'], 1), array('module_sys_dmail_category, module_sys_dmail_newsletter')));
        }

        $fieldConfArray = array('fields', 'required');
        foreach ($fieldConfArray as $k => $v) {
            // make it ready for GeneralUtility::inList which does not yet allow blanks
            $conf[$cmdKey . '.'][$v] = implode(',',  array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.'][$v])));
        }

        $theTable = $controlData->getTable();
        if (
            $theTable == 'fe_users'
        ) {
            if (
                $cmdKey != 'edit' &&
                $cmdKey != 'password'
            ) {
                    // When not in edit mode, add username to lists of fields and required fields unless explicitly disabled
                if (!empty($conf[$cmdKey.'.']['doNotEnforceUsername'])) {
                    $conf[$cmdKey . '.']['fields'] = GeneralUtility::rmFromList('username', $conf[$cmdKey . '.']['fields']);
                    $conf[$cmdKey . '.']['required'] = GeneralUtility::rmFromList('username', $conf[$cmdKey . '.']['required']);
                } else {
                    $conf[$cmdKey . '.']['fields'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',username', 1)));
                    $conf[$cmdKey . '.']['required'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',username', 1)));
                }
            }

            // When in edit mode, remove password from required fields
            if ($cmdKey == 'edit') {
                $conf[$cmdKey . '.']['required'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'], 1), array('password')));
            }

            if (
                $conf[$cmdKey . '.']['generateUsername'] ||
                $cmdKey == 'password'
            ) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('username')));
            }

            if (
                $conf[$cmdKey . '.']['generateCustomerNumber']
            ) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('cnum')));
            }

            if (
                (
                    $cmdKey == 'invite' ||
                    $cmdKey == 'create'
                ) && $conf[$cmdKey . '.']['generatePassword']
            ) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('password')));
            }

            if ($conf[$cmdKey . '.']['useEmailAsUsername']) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('username')));
                if ($cmdKey == 'create' || $cmdKey == 'invite') {
                    $conf[$cmdKey . '.']['fields'] = implode(',', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',email', 1));
                    $conf[$cmdKey . '.']['required'] = implode(',', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',email', 1));
                }
                if (
                    ($cmdKey == 'edit' || $cmdKey == 'password') &&
                    $controlData->getSetfixedEnabled()
                ) {
                    $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('email')));
                }
            }
            $userGroupObj = $addressObj->getFieldObj('usergroup');

            if (is_object($userGroupObj)) {
                $userGroupObj->modifyConf($conf, $cmdKey);
            }

            if ($cmdKey == 'invite') {
                if ($conf['enableAdminReview']) {
                    if (
                        $controlData->getSetfixedEnabled() &&
                        is_array($conf['setfixed.']['ACCEPT.']) &&
                        is_array($conf['setfixed.']['APPROVE.'])
                    ) {
                        $conf['setfixed.']['APPROVE.'] = $conf['setfixed.']['ACCEPT.'];
                    }
                }
            }

            if ($cmdKey == 'create') {
                if ($conf['enableAdminReview'] && !$conf['enableEmailConfirmation']) {
                    $conf['create.']['defaultValues.']['disable'] = '1';
                    $conf['create.']['overrideValues.']['disable'] = '1';
                }
            }
        }
            // Honour Address List (tt_address) configuration setting
        if (
            $theTable == 'tt_address' &&
            ExtensionManagementUtility::isLoaded('tt_address') &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address'])
        ) {
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address']);
            if (is_array($extConf) && $extConf['disableCombinedNameField'] == '1') {
                $conf[$cmdKey . '.']['fields'] = GeneralUtility::rmFromList('name', $conf[$cmdKey . '.']['fields']);
            }
        }

            // Adjust some evaluation settings
        if (is_array($conf[$cmdKey . '.']['evalValues.'])) {
        // TODO: Fix scope issue: unsetting $conf entry here has no effect
                // Do not evaluate any password when inviting
            if ($cmdKey == 'invite') {
                unset($conf[$cmdKey . '.']['evalValues.']['password']);
            }
                // Do not evaluate the username if it is generated or if email is used
            if (
                $conf[$cmdKey . '.']['useEmailAsUsername'] ||
                (
                    $conf[$cmdKey . '.']['generateUsername'] &&
                    $cmdKey != 'edit' &&
                    $cmdKey != 'password'
                )
            ) {
                unset($conf[$cmdKey . '.']['evalValues.']['username']);
            }

            if (
                $conf[$cmdKey . '.']['generateCustomerNumber']
            ) {
                unset($conf[$cmdKey . '.']['evalValues.']['cnum']);
            }
        }
        $confObj->setConf($conf);

            // Setting requiredArr to the fields in "required" fields list intersected with the total field list in order to remove invalid fields.
        $requiredArray = array_intersect(
            GeneralUtility::trimExplode(
                ',',
                $conf[$cmdKey . '.']['required'],
                1
            ),
            GeneralUtility::trimExplode(
                ',',
                $conf[$cmdKey . '.']['fields'],
                1
            )
        );
        $dataObj->setDataArray($dataArray);
        $controlData->setRequiredArray($requiredArray);

        $fieldList = $dataObj->getFieldList();
        $fieldArray = GeneralUtility::trimExplode(',', $fieldList, 1);
        $additionalFields = $dataObj->getAdditionalIncludedFields();

        if ($theTable == 'fe_users') {

            if (
                $conf[$cmdKey . '.']['useEmailAsUsername'] ||
                $conf[$cmdKey . '.']['generateUsername']
            ) {
                $additionalFields = array_merge($additionalFields, array('username'));
            }

            if ($conf[$cmdKey . '.']['useEmailAsUsername']) {
                $additionalFields = array_merge($additionalFields, array('email'));
            }

            if (
                $conf[$cmdKey . '.']['generateCustomerNumber']
            ) {
                $additionalFields = array_merge($additionalFields, array('cnum'));
            }

            if (
                $cmdKey == 'edit' &&
                !in_array('email', $additionalFields) &&
                !in_array('username', $additionalFields)
            ) {
                $additionalFields = array_merge($additionalFields, array('username'));
            }
        }

        $dataObj->setAdditionalIncludedFields($additionalFields);
        $fieldArray = array_merge($fieldArray, $additionalFields);
        $fieldArray = array_unique($fieldArray);
        $fieldList = implode(',', $fieldArray);
        $dataObj->setFieldList($fieldList);
    } // init2

    /**
    * All processing of the codes is done here
    *
    * @param string  command to execute
    * @param string message if an error has occurred
    * @return string  text to display
    */
    public function doProcessing (
        $cObj,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $setfixedObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\View\Template $template,
        \JambageCom\Agency\View\CreateView $displayObj,
        \JambageCom\Agency\View\EditView $editView,
        \JambageCom\Agency\View\DeleteView $deleteView,
        \JambageCom\Agency\Request\Parameters $controlData,
        $dataObj,
        \JambageCom\Agency\View\Marker $markerObj,
        $staticInfoObj,
        $theTable,
        $cmd,
        $cmdKey,
        array $origArray,
        $templateCode,
        &$errorMessage
    ) {
        $dataArray = $dataObj->getDataArray();
        $conf = $confObj->getConf();
        $fD = array();
        $extensionKey = $controlData->getExtensionKey();
        $prefixId = $controlData->getPrefixId();
        $controlData->setMode(MODE_NORMAL);
        $controlData->initCaptcha(
            $cmdKey
        );

        $savePassword = '';
        $autoLoginKey = '';
        $hasError = false;
        $parseResult = true;

            // Commands with which the data will not be saved by $dataObj->save
        $noSaveCommands = array('infomail', 'login', 'delete');
        $uid = $dataObj->getRecUid();
        $securedArray = array();
            // Check for valid token
        if (
            !$controlData->isTokenValid() ||
            (
                $theTable == 'fe_users' &&
                (
                    !$GLOBALS['TSFE']->loginUser ||
                    ($uid > 0 && $GLOBALS['TSFE']->fe_user->user['uid'] != $uid)
                ) &&
                !in_array($cmd, $this->noLoginCommands)
            )
        ) {
            $controlData->setCmd($cmd);
            $origArray = array();
            $dataObj->setOrigArray($origArray);
            $dataObj->resetDataArray();
            $finalDataArray = $dataArray;
        } else if ($dataObj->bNewAvailable()) {
            if ($theTable == 'fe_users') {
                $securedArray = $controlData->readSecuredArray();
            }
            $finalDataArray = $dataArray;
            \tx_div2007_core::mergeRecursiveWithOverrule(
                $finalDataArray,
                $securedArray
            );
        } else {
            $finalDataArray = $dataArray;
        }

        $hasSubmitData = (
            $controlData->getFeUserData('submit') != '' ||
            $controlData->getFeUserData('submit-security') != ''
        );

        if ($hasSubmitData) {
            $bSubmit = true;
            $controlData->setSubmit(true);
        }

        $doNotSaveData = $controlData->getFeUserData('doNotSave');

        if ($doNotSaveData != '') {
            $bDoNotSave = true;
            $controlData->setDoNotSave(true);
            $controlData->clearSessionData();
        }

        $markerArray = $markerObj->getArray();

            // Evaluate incoming data
        if (
            is_array($finalDataArray) &&
            count($finalDataArray) &&
            !in_array($cmd, $noSaveCommands)
        ) {
            if (
                $conf[$cmdKey . '.']['generateCustomerNumber']
            ) {
                $customerNumberApi = GeneralUtility::makeInstance(\JambageCom\Agency\Api\CustomerNumber::class);
                $customerNumber =
                    $customerNumberApi->generate(
                        $theTable,
                        $conf[$cmdKey . '.']['generateCustomerNumber.']
                    );
                if ($customerNumber != '') {
                    $finalDataArray['cnum'] = $customerNumber;
                }
            }

            $dataObj->setName(
                $finalDataArray,
                $cmdKey,
                $theTable
            );
            $parseResult =
                $dataObj->parseValues(
                    $theTable,
                    $finalDataArray,
                    $origArray,
                    $cmdKey
                );
            $dataObj->overrideValues(
                $finalDataArray,
                $conf[$cmdKey . '.']
            );

            if (
                $parseResult &&
                (
                    $bSubmit ||
                    $bDoNotSave ||
                    $controlData->getFeUserData('linkToPID')
                )
            ) {
                    // A button was clicked on
                $evalErrors = $dataObj->evalValues(
                    $confObj,
                    $staticInfoObj,
                    $theTable,
                    $finalDataArray,
                    $origArray,
                    $markerArray,
                    $cmdKey,
                    $controlData->getRequiredArray(),
                    array(),
                    $controlData->getCaptcha()
                );

                    // If the two password fields are not equal, clear session data
                if (
                    is_array($evalErrors['password']) &&
                    in_array('twice', $evalErrors['password'])
                ) {
                    $controlData->clearSessionData();
                }

                if (
                    $conf['evalFunc'] &&
                    is_array($conf['evalFunc.'])
                ) {
                    $markerObj->setArray($markerArray);
                    $finalDataArray =
                        tx_div2007_alpha5::userProcess_fh002(
                            $this,
                            $conf,
                            'evalFunc',
                            $finalDataArray
                        );
                    $markerArray = $markerObj->getArray();
                }
            } else {
                $checkFieldArray = array();
                if (!count($origArray)) {
                    $checkFieldArray = array('password'); // only check for the password field on creation
                }

                    // This is either a country change submitted through the onchange event or a file deletion already processed by the parsing function
                    // You come here after a click on the text "Not a member yet? click here to register."
                    // We are going to redisplay
                $evalErrors = $dataObj->evalValues(
                    $confObj,
                    $staticInfoObj,
                    $theTable,
                    $finalDataArray,
                    $origArray,
                    $markerArray,
                    $cmdKey,
                    $controlData->getRequiredArray(),
                    $checkFieldArray,
                    $controlData->getCaptcha()
                );

                     // If the two password fields are not equal, clear session data
                if (
                    is_array($evalErrors['password']) &&
                    in_array('twice', $evalErrors['password'])
                ) {
                    $controlData->clearSessionData();
                }

                $markerObj->setArray($markerArray);

                if (!$bSubmit) {
                    $controlData->setFailure('submit'); // internal error simulation without any error message needed in order not to save in the next step. This happens e.g. at the first call to the create page
                }
            }
            $dataObj->setUsername($theTable, $finalDataArray, $cmdKey);
            $dataObj->setDataArray($finalDataArray);

            if (
                $parseResult &&
                $controlData->getFailure() == '' &&
                !$controlData->getFeUserData('preview') &&
                !$bDoNotSave
            ) {
                if ($theTable == 'fe_users') {
                    if (
                        $cmdKey == 'invite' ||
                        $cmdKey == 'create'
                    ) {
                        $controlData->generatePassword(
                            $cmdKey,
                            $conf,
                            $conf[$cmdKey . '.'],
                            $finalDataArray,
                            $autoLoginKey
                        );
                    }

                    // If inviting or if auto-login will be required on confirmation, we store an encrypted version of the password
                    $savePassword = $controlData->readPasswordForStorage();
                }

                $newDataArray = array();
                $theUid = $dataObj->save(
                    $staticInfoObj,
                    $theTable,
                    $finalDataArray,
                    $origArray,
                    $controlData->readToken(),
                    $newDataArray,
                    $cmd,
                    $cmdKey,
                    $controlData->getPid(),
                    $savePassword,
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess']
                );

                if ($newDataArray) {
                    $dataArray = $newDataArray;
                }

                if ($dataObj->getSaved()) {
                        // if auto login on create
                    if (
                        $theTable == 'fe_users' &&
                        $cmd == 'create' &&
                        !$controlData->getSetfixedEnabled() &&
                        $controlData->enableAutoLoginOnCreate($conf)
                    ) {
                        // do nothing
                        // conserve the session for the following auto login
                    } else {
                        $controlData->clearSessionData();
                    }
                }
            }
        } else if ($cmd == 'infomail') {
            if ($bSubmit) {
                $fetch = $controlData->getFeUserData('fetch');
                $finalDataArray['email'] = $fetch;
                $evalErrors = $dataObj->evalValues(
                    $confObj,
                    $staticInfoObj,
                    $theTable,
                    $finalDataArray,
                    $origArray,
                    $markerArray,
                    $cmdKey,
                    array(),
                    array(),
                    $controlData->getCaptcha()
                );
            }
            $controlData->setRequiredArray(array());
            $markerObj->setArray($markerArray);
            $controlData->setFeUserData(0, 'preview');
        } else {
            $markerObj->setNoError($cmdKey, $markerArray);
            $markerObj->setArray($markerArray);
            if ($cmd != 'delete') {
                $controlData->setFeUserData(0, 'preview'); // No preview if data is not received and deleted
            }
        }

        if (
            $controlData->getFailure() != '' ||
            !$parseResult
        ) {
            $controlData->setFeUserData(0, 'preview');
        }

            // No preview flag if a evaluation failure has occured
        if ($controlData->getFeUserData('preview')) {
            $markerObj->setPreviewLabel('_PREVIEW');
            $controlData->setMode(MODE_PREVIEW);
        }

            // If data is submitted, we take care of it here.
        if (
            $cmd == 'delete' &&
            !$controlData->getFeUserData('preview') &&
            !$bDoNotSave
        ) {
            if (
                $controlData->getFeUserData('task') == 'cancel' // the DELETE has been cancelled
            ) {
                $content =
                    $template->getSimpleTemplate(
                        $conf,
                        $cObj,
                        $langObj,
                        $markerObj,
                        $templateCode,
                        '###TEMPLATE_DELETE_CANCEL###',
                        $markerArray
                    );
            } else {
                // Delete record if delete command is set + the preview flag is NOT set.
                $dataObj->deleteRecord(
                    $controlData,
                    $theTable,
                    $origArray,
                    $dataArray
                );
            }
        }
        $errorContent = '';
        $deleteRegHash = false;

            // Display forms
        if ($dataObj->getSaved()) {

            $bCustomerConfirmsMode = false;
            $bDefaultMode = false;
            if (
                ($cmd == '' || $cmd == 'create')
            ) {
                $bDefaultMode = true;
            }

            if (
                $bDefaultMode &&
                ($cmdKey != 'edit') &&
                $conf['enableAdminReview'] &&
                ($conf['enableEmailConfirmation'] || $conf['infomail'])
            ) {
                $bCustomerConfirmsMode = true;
            }
                // This is the case where the user or admin has to confirm
                // $conf['enableEmailConfirmation'] ||
                // ($this->theTable == 'fe_users' && $conf['enableAdminReview']) ||
                // $conf['setfixed']
            $bSetfixed = $controlData->getSetfixedEnabled();
                // This is the case where the user does not have to confirm, but has to wait for admin review
                // This applies only on create ($bDefaultMode) and to fe_users
                // $bCreateReview implies $bSetfixed
            $bCreateReview =
                ($theTable == 'fe_users') &&
                !$conf['enableEmailConfirmation'] &&
                $conf['enableAdminReview'];
            $key =
                $template->getKeyAfterSave(
                    $cmd,
                    $cmdKey,
                    $bCustomerConfirmsMode,
                    $bSetfixed,
                    $bCreateReview
                );

            $afterSave = GeneralUtility::makeInstance(\JambageCom\Agency\View\AfterSaveView::class);
            $errorContent =
                $afterSave->render(
                    $conf,
                    $cObj,
                    $langObj,
                    $controlData,
                    $confObj,
                    $this->tca,
                    $markerObj,
                    $dataObj,
                    $setfixedObj,
                    $template,
                    $theTable,
                    $autoLoginKey,
                    $prefixId,
                    $dataArray,
                    $origArray,
                    $securedArray,
                    $cmd,
                    $cmdKey,
                    $key,
                    $templateCode,
                    $markerArray,
                    $dataObj->getInError(),
                    $content
                );

            if ($errorContent == '') {
                $markerArray = $markerObj->getArray(); // uses its own markerArray
                $errorCode = '';
                $bEmailSent = false;

                if (
                    $conf['enableAdminReview'] &&
                    $bDefaultMode &&
                    !$bCustomerConfirmsMode
                ) {
                    $email = GeneralUtility::makeInstance(\JambageCom\Agency\Api\Email::class);
                        // Send admin the confirmation email
                        // The user will not confirm in this mode
                    $bEmailSent = $email->compile(
                        SETFIXED_PREFIX . 'REVIEW',
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $setfixedObj.
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        array($dataArray),
                        array($origArray),
                        $securedArray,
                        $conf['email.']['admin'],
                        $markerArray,
                        'setfixed',
                        $cmdKey,
                        $templateCode,
                        $errorFieldArray,
                        $conf['setfixed.'],
                        $errorCode
                    );
                } else if (
                    $cmdKey == 'create' ||
                    $cmdKey == 'invite' ||
                    $conf['email.']['EDIT_SAVED'] ||
                    $conf['email.']['DELETE_SAVED']
                ) {
                    $emailField = $conf['email.']['field'];
                    $recipient =
                        (
                            isset($finalDataArray) &&
                            is_array($finalDataArray) &&
                            $finalDataArray[$emailField]
                        ) ?
                        $finalDataArray[$emailField] :
                        $origArray[$emailField];
                    $email = GeneralUtility::makeInstance(\JambageCom\Agency\Api\Email::class);

                    // Send email message(s)
                    $bEmailSent = $email->compile(
                        $key,
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $setfixedObj,
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        array($dataArray),
                        array($origArray),
                        $securedArray,
                        $recipient,
                        $markerArray,
                        $cmd,
                        $cmdKey,
                        $templateCode,
                        $errorFieldArray,
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
                }
            }

            if ($errorContent == '') {	// success case
                $origGetFeUserData = GeneralUtility::_GET($prefixId);
                $deleteRegHash = true;

                    // Link to on edit save
                    // backURL may link back to referring process
                if (
                    $theTable == 'fe_users' &&
                    ($cmd == 'edit' || $cmd == 'password') &&
                    (
                        $controlData->getBackURL() ||
                        (
                            $conf['linkToPID'] &&
                            (
                                $controlData->getFeUserData('linkToPID') ||
                                !$conf['linkToPIDAddButton']
                            )
                        )
                    )
                ) {
                    $destUrl =
                        (
                            $controlData->getBackURL() ?
                                $controlData->getBackURL() :
                                $cObj->getTypoLink_URL($conf['linkToPID'] . ',' . $GLOBALS['TSFE']->type)
                        );
                    header('Location: '.GeneralUtility::locationHeaderUrl($destUrl));
                    exit;
                }

                    // Auto login on create
                if (
                    $theTable == 'fe_users' &&
                    $cmd == 'create' &&
                    !$controlData->getSetfixedEnabled() &&
                    $controlData->enableAutoLoginOnCreate($conf)
                ) {
                    $autoLoginKey = '';
                    $loginSuccess = false;
                    $password = $controlData->readPassword();
                    $loginSuccess =
                        $this->login(
                            $conf,
                            $langObj,
                            $controlData,
                            $dataArray['username'],
                            $password,
                            true
                        );

                    if ($loginSuccess) {
                            // Login was successful
                        exit;
                    } else {
                            // Login failed... should not happen...
                            // If it does, a login form will be displayed as if auto-login was not configured
                        $content = '';
                    }
                }
            } else { // error case
                $content = $errorContent;
            }
        } else if ($dataObj->getError()) {

                // If there was an error, we return the template-subpart with the error message
            $templateCode = $cObj->getSubpart($templateCode, $dataObj->getError());

            $markerObj->addLabelMarkers(
                $markerArray,
                $conf,
                $cObj,
                $langObj,
                $controlData->getExtensionKey(),
                $theTable,
                $finalDataArray,
                $dataObj->getOrigArray(),
                $securedArray,
                array(),
                $controlData->getRequiredArray(),
                $dataObj->getFieldList(),
                $GLOBALS['TCA'][$theTable]['columns'],
                '',
                false
            );
            $markerObj->setArray($markerArray);
            $content = $cObj->substituteMarkerArray($templateCode, $markerArray);
        } else if ($content == '') {
                // Finally, there has been no attempt to save.
                // That is either preview or just displaying an empty or not correctly filled form
            $markerObj->setArray($markerArray);
            $token = $controlData->readToken();

            if (
                $cmd == '' &&
                $controlData->getFeUserData('preview')
            ) {
                $cmd = $cmdKey;
            }

            switch ($cmd) {
                case 'setfixed':
                    if ($conf['infomail']) {
                        $controlData->setSetfixedEnabled(1);
                    }
                    $origArray = $dataObj->parseIncomingData($origArray, false);
                    $content = $setfixedObj->process(
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        $uid,
                        $cmdKey,
                        $markerArray,
                        $template,
                        $displayObj,
                        $editView,
                        $deleteView,
                        $templateCode,
                        $finalDataArray,
                        $origArray,
                        $securedArray,
                        $this,
                        $token,
                        $hasError
                    );
                    break;
                case 'infomail':
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmd,
                        $token,
                        '',
                        $fD
                    );
                    if ($conf['infomail']) {
                        $controlData->setSetfixedEnabled(1);
                    }
                    $origArray = $dataObj->parseIncomingData($origArray, false);
                    $errorCode = '';
                    $email = GeneralUtility::makeInstance(Email::class);
                    $content = $email->sendInfo(
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $setfixedObj,
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        $origArray,
                        $securedArray,
                        $markerArray,
                        $cmd,
                        $cmdKey,
                        $templateCode,
                        $controlData->getFailure(),
                        $errorCode
                    );

                    if (
                        $content == '' &&
                        is_array($errorCode)
                    ) {
                        $content = $langObj->getLL($errorCode['0']);
                    }
                    break;
                case 'delete':
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmd,
                        $token,
                        '',
                        $fD
                    );
                    $content = $deleteView->render(
                        $markerArray,
                        $conf,
                        $prefixId,
                        $extensionKey,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $finalDataArray,
                        $origArray,
                        $securedArray,
                        $token,
                        '',
                        $fD
                    );
                    break;
                case 'edit':
                case 'password':
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmd,
                        $token,
                        '',
                        $fD
                    );
                    $content = $editView->render(
                        $markerArray,
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $prefixId,
                        $finalDataArray,
                        $origArray,
                        $securedArray,
                        $cmd,
                        $cmdKey,
                        $controlData->getMode(),
                        $dataObj->getInError(),
                        $token
                    );
                    break;
                case 'invite':
                case 'create':
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmd,
                        $token,
                        '',
                        $fD
                    );

                    $content = $displayObj->render(
                        $markerArray,
                        $conf,
                        $prefixId,
                        $extensionKey,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $cmd,
                        $cmdKey,
                        $controlData->getMode(),
                        $theTable,
                        $finalDataArray,
                        $origArray,
                        $securedArray,
                        $dataObj->getFieldList(),
                        $dataObj->getInError(),
                        $token
                    );
                    break;
                case 'login':
                    // nothing. The login parameters are processed by TYPO3 Core
                    break;
                default:
                    $markerObj->addGeneralHiddenFieldsMarkers(
                        $markerArray,
                        $cmd,
                        $token,
                        '',
                        $fD
                    );
                    $content = $displayObj->render(
                        $markerArray,
                        $conf,
                        $prefixId,
                        $extensionKey,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $this->tca,
                        $markerObj,
                        $dataObj,
                        $template,
                        $cmd,
                        $cmdKey,
                        $controlData->getMode(),
                        $theTable,
                        $finalDataArray,
                        $origArray,
                        $securedArray,
                        $dataObj->getFieldList(),
                        $dataObj->getInError(),
                        $token
                    );
                    break;
            }

            if (
                (
                    $cmd != 'setfixed' ||
                    $cmdKey != 'edit'
                ) &&
                !$errorContent &&
                !$hasError &&
                !$controlData->isPreview()
            ) {
                $deleteRegHash = true;
            }
        }

        if (
            $deleteRegHash &&
            $controlData->getValidRegHash()
        ) {
            $regHash = $controlData->getRegHash();
            $controlData->deleteShortUrl($regHash);
        }

        return $content;
    }


    /**
    * Perform user login and redirect to configured url, if any
    *
    * @param boolen $redirect: whether to redirect after login or not. If true, then you must immediately call exit after this call
    * @return boolean true, if login was successful, false otherwise
    */
    public function login (
        $conf,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        $username,
        $cryptedPassword,
        $redirect = true
    ) {
        $result = true;
        $message = '';

            // Log the user in
        $loginData = array(
            'uname' => $username,
            'uident' => $cryptedPassword,
            'uident_text' => $cryptedPassword,
            'status' => 'login',
        );

        // Check against configured pid (defaulting to current page)
        $GLOBALS['TSFE']->fe_user->checkPid = true;
        $GLOBALS['TSFE']->fe_user->checkPid_value = $controlData->getPid();

            // Get authentication info array
        $authInfo = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();

            // Get user info
        $user =
            $GLOBALS['TSFE']->fe_user->fetchUserRecord(
                $authInfo['db_user'],
                $loginData['uname']
            );

        if (is_array($user)) {
            $serviceKeyArray = array();

            if (class_exists('\\TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService')) {
                $serviceKeyArray[] = 'TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService';
            }

            if (
                $conf['authServiceClass'] != '' &&
                $conf['authServiceClass'] != '{$plugin.tx_agency.authServiceClass}' &&
                class_exists($conf['authServiceClass'])
            ) {
                $serviceKeyArray = array_merge($serviceKeyArray, GeneralUtility::trimExplode(',', $conf['authServiceClass']));
            }

            $serviceChain = '';
            $ok = false;
            $authServiceObj = false;

            while (
                is_object(
                    $authServiceObj =
                        GeneralUtility::makeInstanceService(
                            'auth',
                            'authUserFE',
                            $serviceChain
                        )
                )
            ) {
                $serviceChain .= ',' . $authServiceObj->getServiceKey();
                $ok = $authServiceObj->compareUident($user, $loginData);
                if ($ok) {
                    break;
                }
            }

            if ($ok) {
                    // Login successfull: create user session
                $GLOBALS['TSFE']->fe_user->createUserSession($user);
                $GLOBALS['TSFE']->initUserGroups();
                $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
                $GLOBALS['TSFE']->loginUser = 1;
            } else if (
                is_object($authServiceObj) &&
                in_array(get_class($authServiceObj), $serviceKeyArray)
            ) {
                    // auto login failed...
                $message = $langObj->getLL('internal_auto_login_failed');
                $result = false;
            } else {
                    // Required authentication service not available
                $message = $langObj->getLL('internal_required_authentication_service_not_available');
                $result = false;
            }

                // Delete regHash
            if (
                $controlData->getValidRegHash()
            ) {
                $regHash = $controlData->getRegHash();
                $controlData->deleteShortUrl($regHash);
            }
        } else {
                // No enabled user of the given name
            $message = sprintf($langObj->getLL('internal_no_enabled_user'), $loginData['uname']);
            $result = false;
        }

        if ($result == false) {
            $controlData->clearSessionData(false);

            if ($message != '') {
                GeneralUtility::sysLog($message, $controlData->getExtensionKey(), GeneralUtility::SYSLOG_SEVERITY_ERROR);
            }
        }

        if (
            $redirect
        ) {
                // Redirect to configured page, if any
            $redirectUrl = $controlData->readRedirectUrl();
            if (!$redirectUrl && $result == true) {
                $redirectUrl = trim($conf['autoLoginRedirect_url']);
            }

            if (!$redirectUrl) {
                if ($conf['loginPID']) {
                    $redirectUrl = $this->urlObj->get('', $conf['loginPID']);
                } else {
                    $redirectUrl = $controlData->getSiteUrl();
                }
            }
            header('Location: ' . GeneralUtility::locationHeaderUrl($redirectUrl));
        }

        return $result;
    }
}

