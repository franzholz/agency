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
use JambageCom\Div2007\Utility\SystemUtility;

use JambageCom\Agency\Constants\Mode;
use JambageCom\Agency\Controller\Email;
use JambageCom\Agency\Security\SecuredData;
use JambageCom\Agency\Utility\SessionUtility;


class ActionController {
    public $languageObj;
    public $auth;
    public $email;
    public $requiredArray; // List of required fields
    public $controlData;
        // Commands that may be processed when no user is logged in
    public $noLoginCommands = array('create', 'invite', 'setfixed', 'infomail', 'login');


    public function init (
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        \JambageCom\Agency\Api\Localization $languageObj,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        $urlObj
    )
    {
        $this->langObj = $languageObj;
        $conf = $confObj->getConf();
        $this->urlObj = $urlObj;
            // Retrieve the extension key
        $extensionKey = $controlData->getExtensionKey();
            // Get the command as set in piVars
        $cmd = $controlData->getCmd();

            // If not set, get the command from the flexform
        if ($cmd == '') {
                // Check the flexform
            $cObj->data['pi_flexform'] = GeneralUtility::xml2array($cObj->data['pi_flexform']);
            $cmd = \JambageCom\Div2007\Utility\ConfigUtility::getSetupOrFFvalue(
                $languageObj,
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
        \JambageCom\Agency\Domain\Tca $tcaObj,
        &$adminFieldList,
        array &$origArray,
        &$errorMessage
    )
    {
        $conf = $confObj->getConf();
        $tablesObj = GeneralUtility::makeInstance(\JambageCom\Agency\Domain\Tables::class);
        $addressObj = $tablesObj->get('address');
        $extensionKey = $controlData->getExtensionKey();
        $cmd = $controlData->getCmd();
        $dataArray = $dataObj->getDataArray();
        $fieldlist = '';
        $modifyPassword = false;

        $bHtmlSpecialChars = false;
        SecuredData::secureInput($dataArray, $bHtmlSpecialChars);

        if (
            $theTable == 'fe_users' &&
            !empty($dataArray)
        ) {
            $modifyPassword =
                SecuredData::securePassword(
                    $extensionKey,
                    $dataArray,
                    $errorMessage
                );

            if ($modifyPassword === false) {
                return false;
            }
        }
        $dataObj->setDataArray($dataArray);

        $fieldlist =
            implode(',', \JambageCom\Div2007\Utility\TableUtility::getFields($theTable));

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
            $tcaObj->modifyRow(
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
                $tcaObj->modifyRow(
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

            // Setting requiredArray to the fields in "required" fields list intersected with the total field list in order to remove invalid fields.
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

        $additionalFields = array_unique($additionalFields);
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
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $setfixedObj,
        \JambageCom\Agency\Api\Localization $languageObj,
        \JambageCom\Agency\View\Template $template,
        \JambageCom\Agency\View\CreateView $displayObj,
        \JambageCom\Agency\View\EditView $editView,
        \JambageCom\Agency\View\DeleteView $deleteView,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Domain\Data $dataObj,
        \JambageCom\Agency\Domain\Tca $tcaObj,
        \JambageCom\Agency\View\Marker $markerObj,
        $staticInfoObj,
        $theTable,
        $cmd,
        $cmdKey,
        array $origArray,
        $templateCode,
        &$errorMessage
    )
    {
        $dataArray = $dataObj->getDataArray();
        $conf = $confObj->getConf();
        $fD = array();
        $extensionKey = $controlData->getExtensionKey();
        $prefixId = $controlData->getPrefixId();
        $controlData->setMode(Mode::NORMAL);
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
                $securedArray = SecuredData::readSecuredArray($extensionKey);
            }
            $finalDataArray = $dataArray;
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
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
            SessionUtility::clearData(
                $extensionKey,
                true,
                $controlData->readToken(),
                $controlData->readRedirectUrl()
            );
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
                    SessionUtility::clearData(
                        $extensionKey,
                        true,
                        $controlData->readToken(),
                        $controlData->readRedirectUrl()
                    );
                }

                if (
                    $conf['evalFunc'] &&
                    is_array($conf['evalFunc.'])
                ) {
                    $markerObj->setArray($markerArray);
                    $finalDataArray =
                        SystemUtility::userProcess(
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
                    SessionUtility::clearData(
                        $extensionKey,
                        true,
                        $controlData->readToken(),
                        $controlData->readRedirectUrl()
                    );
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
                        SecuredData::generatePassword(
                            $extensionKey,
                            $cmdKey,
                            $conf,
                            $conf[$cmdKey . '.'],
                            $finalDataArray,
                            $autoLoginKey
                        );
                    }

                    // If inviting or if auto-login will be required on confirmation, we store an encrypted version of the password
                    $savePassword = SecuredData::readPasswordForStorage($extensionKey);
                }
                $extraFields = '';
                if (
                    $cmdKey == 'create' &&
                    isset($finalDataArray['privacy_policy_acknowledged']) &&
                    $finalDataArray['privacy_policy_acknowledged']
                ) {
                    $finalDataArray['privacy_policy_date'] = SystemUtility::createTime();
                    $extraFields = 'privacy_policy_date';
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
                    $extraFields,
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
                        SessionUtility::clearData(
                            $extensionKey,
                            true,
                            $controlData->readToken(),
                            $controlData->readRedirectUrl()
                        );
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
                // No preview flag if an evaluation failure has occurred
            $controlData->setFeUserData(0, 'preview');
        }

        if ($controlData->getFeUserData('preview')) {
            $markerObj->setPreviewLabel('_PREVIEW');
            $controlData->setMode(Mode::PREVIEW);
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
                        $languageObj,
                        $markerObj,
                        $templateCode,
                        '###TEMPLATE_DELETE_CANCEL###',
                        $markerArray
                    );
            } else {
                // Delete record if delete command is set and if the preview flag is NOT set.
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
                    $email = GeneralUtility::makeInstance(\JambageCom\Agency\Controller\Email::class);
                        // Send admin the confirmation email
                        // The user will not confirm in this mode
                    $bEmailSent = $email->compile(
                        SETFIXED_PREFIX . 'REVIEW',
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
                        array($dataArray),
                        array($origArray),
                        $securedArray,
                        $conf['email.']['admin'],
                        $markerArray,
                        'setfixed',
                        $cmdKey,
                        $templateCode,
                        $dataObj->getInError(),
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
                    $email = GeneralUtility::makeInstance(\JambageCom\Agency\Controller\Email::class);

                    // Send email message(s)
                    $bEmailSent = $email->compile(
                        $key,
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
                        array($dataArray),
                        array($origArray),
                        $securedArray,
                        $recipient,
                        $markerArray,
                        $cmd,
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
                    $errorText = $languageObj->getLabel($errorCode['0'], $dummy, '', false, true);
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
                    $password = SecuredData::readPassword($extensionKey);
                    $loginSuccess =
                        \JambageCom\Agency\Api\System::login(
                            $cObj,
                            $languageObj,
                            $controlData,
                            $this->urlObj,
                            $conf,
                            $dataArray['username'],
                            $password,
                            true,
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
                $languageObj,
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
                        $languageObj,
                        $controlData,
                        $this->urlObj,
                        $confObj,
                        $tcaObj,
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
                    $fetch = $controlData->getFeUserData('fetch');
                    $pidLock = '';

                    if (isset($fetch) && !empty($fetch)) {
                        $pages = ($cObj->data['pages'] ? $cObj->data['pages'] . ',' : '') . $controlData->getPid();                        
                        $allPages = \JambageCom\Div2007\Utility\TableUtility::getAllSubPages($pages);
                        $pidLock = 'AND pid IN (' . implode(',', $allPages) . ')';
                    }

                    $content = $email->processInfo(
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
                        $origArray,
                        $securedArray,
                        $markerArray,
                        $cmd,
                        $cmdKey,
                        $fetch,
                        $pidLock,
                        $templateCode,
                        $controlData->getFailure(),
                        $errorCode
                    );

                    if (
                        $content == '' &&
                        is_array($errorCode)
                    ) {
                        $content = $languageObj->getLabel($errorCode['0']);
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
                        $languageObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
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
                        $languageObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
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
                is_array($errorCode)
            ) {
                $errorText = $languageObj->getLabel($errorCode['0']);
                if (isset($errorCode['1'])) {
                    $errorContent = sprintf($errorText, $errorCode['1']);
                } else {
                    $errorContent = $errorText;
                }
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
}

