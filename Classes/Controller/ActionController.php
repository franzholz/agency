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


use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\ConfigUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\SystemUtility;
use JambageCom\Div2007\Utility\TableUtility;

use JambageCom\Agency\Api\CustomerNumber;
use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Api\System;
use JambageCom\Agency\Api\Url;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Constants\Mode;
use JambageCom\Agency\Controller\Email;
use JambageCom\Agency\Database\Data;
use JambageCom\Agency\Database\Tca;
use JambageCom\Agency\Database\Tables;
use JambageCom\Agency\Domain\Repository\FrontendUserRepository;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\SecuredData;
use JambageCom\Agency\Setfixed\SetfixedUrls;
use JambageCom\Agency\Utility\SessionUtility;
use JambageCom\Agency\View\Template;
use JambageCom\Agency\View\CreateView;
use JambageCom\Agency\View\EditView;
use JambageCom\Agency\View\DeleteView;
use JambageCom\Agency\View\Marker;
use JambageCom\Agency\View\AfterSaveView;



class ActionController implements SingletonInterface
{
    public $urlObj;
    public $auth;
    public $email;
    public $requiredArray; // List of required fields
    public $controlData;
    // Commands that may be processed when no user is logged in
    public $noLoginCommands = ['create', 'invite', 'setfixed', 'infomail', 'login'];
    protected ?FrontendUserRepository $frontendUserRepository = null;

    public function __construct(
        FrontendUserRepository $frontendUserRepository
    ) {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    public function init(
        ConfigurationStore $confObj,
        Localization $languageObj,
        ContentObjectRenderer $cObj,
        Parameters $controlData,
        Url $urlObj
    ): void {
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
            $cmd = ConfigUtility::getSetupOrFFvalue(
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
    public function init2(
        ConfigurationStore $confObj,
        $staticInfoObj,
        $theTable,
        Parameters $controlData,
        Data &$dataObj,
        Tca $tcaObj,
        &$adminFieldList,
        array &$origArray,
        &$errorMessage
    ) {
        $conf = $confObj->getConf();
        $tablesObj = GeneralUtility::makeInstance(Tables::class);
        $addressObj = $tablesObj->get('address');
        $extensionKey = $controlData->getExtensionKey();
        $cmd = $controlData->getCmd();
        $dataArray = $dataObj->getDataArray();
        $fieldlist = '';
        $modifyPassword = false;
        $request = $controlData->getRequest();
        $frontendUser = $request->getAttribute('frontend.user');
        $bHtmlSpecialChars = false;
        SecuredData::secureInput($dataArray, $bHtmlSpecialChars);
        $dataObj->setDataArray($dataArray);

        $fieldlist =
            implode(',', TableUtility::getFields($theTable));

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
                $dataArray,
                $staticInfoObj,
                $theTable,
                $fieldlist,
                true,
                false
            );
        }

        $feUserdata = $controlData->getFeUserData();
        $theUid = 0;
        $setFixedUid = false;

        if (is_array($dataArray) && !empty($dataArray['uid'])) {
            $theUid = $dataArray['uid'];
        } elseif (is_array($feUserdata) && !empty($feUserdata['rU'])) {
            $theUid = $feUserdata['rU'];

            if ($cmd == 'setfixed') {
                $setFixedUid = true;
            }
        } elseif (
            !in_array($cmd, $this->noLoginCommands) &&
            isset($frontendUser->user['uid'])
        ) {
            $theUid = $frontendUser->user['uid'];
        }

        if ($theUid) {
            $theUid = intval($theUid);
            $dataObj->setRecUid($theUid);
            $newOrigArray =
                $GLOBALS['TSFE']->sys_page->getRawRecord(
                    $theTable,
                    $theUid
                );

            if (isset($newOrigArray) && is_array($newOrigArray)) {
                $tcaObj->modifyRow(
                    $newOrigArray,
                    $staticInfoObj,
                    $theTable,
                    $dataObj->getFieldList()
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
        } elseif (
            isset($origArray['uid']) &&
            (
                $theTable != 'fe_users' ||
                isset($frontendUser->user['uid']) &&
                $theUid == $frontendUser->user['uid'] || // for security reason: do not allow the change of other user records
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
            } elseif (
                $cmd == 'delete'
            ) {
                $cmdKey = 'delete';
            }
        }

        if (
            $cmdKey == '' &&
            empty($setFixedUid) // Setfixed needs the original array in order to calculate the authorization key
        ) {
            $origArray = []; // do not use the read in original array
            $cmdKey = 'create';
        }

        $controlData->setCmdKey($cmdKey);

        if (!$theUid) {
            if (!count($dataArray)) {
                $dataArray = $dataObj->readDefaultValues($cmdKey);
            }
        }

        if (!empty($conf['addAdminFieldList'])) {
            $adminFieldList .= ',' . trim($conf['addAdminFieldList']);
        }
        $adminFieldList =
            implode(
                ',',
                array_intersect(
                    explode(',', $fieldlist),
                    GeneralUtility::trimExplode(',', $adminFieldList, true)
                )
            );
        $dataObj->setAdminFieldList($adminFieldList);

        if (!empty($cmdKey)) {
            if (
                ExtensionManagementUtility::isLoaded('direct_mail')
            ) {
                $conf[$cmdKey.'.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true), ['categories']));
                $conf[$cmdKey . '.']['required'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'], true), ['categories']));
            }

            $fieldConfArray = ['fields', 'required'];
            foreach ($fieldConfArray as $k => $v) {
                // make it ready for GeneralUtility::inList which does not yet allow blanks
                if (isset($conf[$cmdKey . '.'][$v])) {
                    $conf[$cmdKey . '.'][$v] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.'][$v])));
                }
            }
        }

        $theTable = $controlData->getTable();
        if (
            $theTable == 'fe_users'
        ) {
            if (
                !empty($cmdKey) &&
                $cmdKey != 'edit' &&
                $cmdKey != 'password' &&
                isset($conf[$cmdKey . '.']['fields'])
            ) {
                if (!isset($conf[$cmdKey . '.']['required'])) {
                    $conf[$cmdKey . '.']['required'] = '';
                }
                // When not in edit mode, add username to lists of fields and required fields unless explicitly disabled
                if (!empty($conf[$cmdKey.'.']['doNotEnforceUsername'])) {
                    $element = 'username';
                    $conf[$cmdKey . '.']['fields'] = implode(',', array_filter(explode(',', $conf[$cmdKey . '.']['fields']), function ($item) use ($element) {
                        return $element == $item;
                    }));
                    $conf[$cmdKey . '.']['required'] = implode(',', array_filter(explode(',', $conf[$cmdKey . '.']['required']), function ($item) use ($element) {
                        return $element == $item;
                    }));
                } else {
                    $conf[$cmdKey . '.']['fields'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',username', true)));
                    $conf[$cmdKey . '.']['required'] = implode(',', array_unique(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',username', true)));
                }
            }

            // When in edit mode, remove password from required fields
            if ($cmdKey == 'edit') {
                $conf[$cmdKey . '.']['required'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'], true), ['password']));
            }

            if (
                !empty($conf[$cmdKey . '.']['generateUsername']) ||
                $cmdKey == 'password'
            ) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true), ['username']));
            }

            if (
                !empty($conf[$cmdKey . '.']['generateCustomerNumber'])
            ) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true), ['cnum']));
            }

            if (
                (
                    $cmdKey == 'invite' ||
                    $cmdKey == 'create'
                ) && !empty($conf[$cmdKey . '.']['generatePassword'])
            ) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true), ['password']));
            }

            if (!empty($conf[$cmdKey . '.']['useEmailAsUsername'])) {
                $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true), ['username']));

                if (
                    $cmdKey == 'create' ||
                    $cmdKey == 'invite'
                ) {
                    $conf[$cmdKey . '.']['fields'] = implode(',', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',email', true));
                    $conf[$cmdKey . '.']['required'] = implode(',', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',email', true));
                }

                if (
                    (
                        $cmdKey == 'edit' ||
                        $cmdKey == 'password'
                    ) &&
                    $controlData->getSetfixedEnabled()
                ) {
                    $conf[$cmdKey . '.']['fields'] = implode(',', array_diff(GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true), ['email']));
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
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_address'])
        ) {
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('tt_address');
            if (is_array($extConf) && $extConf['disableCombinedNameField'] == '1') {
                $element = 'name';
                $conf[$cmdKey . '.']['fields'] = implode(',', array_filter(explode(',', $conf[$cmdKey . '.']['fields']), function ($item) use ($element) {
                    return $element == $item;
                }));
            }
        }

        // Adjust some evaluation settings
        if (
            !empty($cmdKey) &&
            isset($conf[$cmdKey . '.']['evalValues.']
            )) {
            // TODO: Fix scope issue: unsetting $conf entry here has no effect
            // Do not evaluate any password when inviting
            if ($cmdKey == 'invite') {
                unset($conf[$cmdKey . '.']['evalValues.']['password']);
            }
            // Do not evaluate the username if it is generated or if email is used
            if (
                !empty($conf[$cmdKey . '.']['useEmailAsUsername']) ||
                (
                    !empty($conf[$cmdKey . '.']['generateUsername']) &&
                    $cmdKey != 'edit' &&
                    $cmdKey != 'password'
                )
            ) {
                unset($conf[$cmdKey . '.']['evalValues.']['username']);
            }

            if (
                !empty($conf[$cmdKey . '.']['generateCustomerNumber'])
            ) {
                unset($conf[$cmdKey . '.']['evalValues.']['cnum']);
            }
        }
        $confObj->setConf($conf);
        $requiredArray = [];

        if (
            !empty($cmdKey) &&
            isset($conf[$cmdKey . '.']['required'])
        ) {
            // Setting requiredArray to the fields in "required" fields list intersected with the total field list in order to remove invalid fields.
            $requiredArray = array_intersect(
                GeneralUtility::trimExplode(
                    ',',
                    $conf[$cmdKey . '.']['required'],
                    true
                ),
                GeneralUtility::trimExplode(
                    ',',
                    $conf[$cmdKey . '.']['fields'],
                    true
                )
            );
        }
        $dataObj->setDataArray($dataArray);
        $controlData->setRequiredArray($requiredArray);

        $fieldList = $dataObj->getFieldList();
        $fieldArray = GeneralUtility::trimExplode(',', $fieldList, true);
        $additionalFields = $dataObj->getAdditionalIncludedFields();

        if ($theTable == 'fe_users' && !empty($cmdKey)) {

            if (
                !empty($conf[$cmdKey . '.']['useEmailAsUsername']) ||
                !empty($conf[$cmdKey . '.']['generateUsername'])
            ) {
                $additionalFields = array_merge($additionalFields, ['username']);
            }

            if (!empty($conf[$cmdKey . '.']['useEmailAsUsername'])) {
                $additionalFields = array_merge($additionalFields, ['email']);
            }

            if (
                !empty($conf[$cmdKey . '.']['generateCustomerNumber'])
            ) {
                $additionalFields = array_merge($additionalFields, ['cnum']);
            }

            if (
                $cmdKey == 'edit' &&
                !in_array('email', $additionalFields) &&
                !in_array('username', $additionalFields)
            ) {
                $additionalFields = array_merge($additionalFields, ['username']);
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
    public function doProcessing(
        ContentObjectRenderer $cObj,
        ConfigurationStore $confObj,
        $setfixedObj,
        Localization $languageObj,
        Template $template,
        CreateView $displayObj,
        EditView $editView,
        DeleteView $deleteView,
        Parameters $controlData,
        Data $dataObj,
        Tca $tcaObj,
        Marker $markerObj,
        $staticInfoObj,
        $theTable,
        $cmd,
        $cmdKey,
        array $origArray,
        $templateCode,
        &$errorMessage
    ) {
        $errorCode = [];
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $dataArray = $dataObj->getDataArray();
        $conf = $confObj->getConf();
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $request = $controlData->getRequest();
        $frontendUser = $request->getAttribute('frontend.user');
        $fD = [];
        $bSubmit = false;
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
        $bDoNotSave = false;

        // Commands with which the data will not be saved by $dataObj->save
        $noSaveCommands = ['infomail', 'login', 'delete'];
        $uid = $dataObj->getRecUid();
        $securedArray = [];
        // Check for valid token
        if (
            !$controlData->isTokenValid() ||
            (
                $theTable == 'fe_users' &&
                (
                    !$controlData->isLoggedIn() ||
                    ($uid > 0 && $frontendUser->user['uid'] != $uid)
                ) &&
                !in_array($cmd, $this->noLoginCommands)
            )
        ) {
            $controlData->setCmd($cmd);
            $origArray = [];
            $dataObj->setOrigArray($origArray);
            $dataObj->resetDataArray();
            $finalDataArray = $dataArray;
        } elseif ($dataObj->bNewAvailable()) {
            if ($theTable == 'fe_users') {
                $securedArrayRead =
                    SecuredData::readSecuredArray(
                        $securedArray,
                        $frontendUser,
                        $extensionKey
                    );
                if ($securedArrayRead && !empty($securedArray['password'])) {
                    $savePassword = $securedArray['password'];
                }
            }
            $finalDataArray = $dataArray;
            ArrayUtility::mergeRecursiveWithOverrule(
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
                $frontendUser,
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
                !isset($origArray['cnum']) &&
                $conf[$cmdKey . '.']['generateCustomerNumber']
            ) {
                $customerNumberApi = GeneralUtility::makeInstance(CustomerNumber::class);
                $customerNumber =
                    $customerNumberApi->generate(
                        $theTable,
                        $conf[$cmdKey . '.']['generateCustomerNumber.']
                    );
                if ($customerNumber != '') {
                    $finalDataArray['cnum'] = $customerNumber;
                }
            } else {
                $finalDataArray['cnum'] = ($origArray['cnum'] ?? '');
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
                $cmdKey,
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
                $checkFieldArray = $finalDataArray;
                if (
                    isset($finalDataArray['password']) &&
                    !empty($savePassword) &&
                    $savePassword == $finalDataArray['password']
                ) {
                    unset($checkFieldArray['password']);
                }
                $checkFieldArray = array_keys($checkFieldArray);

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
                    $checkFieldArray,
                    $controlData->getCaptcha()
                );

                // If the two password fields are not equal, clear session data
                if (
                    isset($evalErrors['password']) &&
                    is_array($evalErrors['password']) &&
                    in_array('twice', $evalErrors['password'])
                ) {
                    SessionUtility::clearData(
                        $frontendUser,
                        $extensionKey,
                        true,
                        $controlData->readToken(),
                        $controlData->readRedirectUrl()
                    );
                }

                if (
                    isset($conf['evalFunc']) &&
                    isset($conf['evalFunc.']) &&
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
                $checkFieldArray = [];
                if (!count($origArray)) {
                    $checkFieldArray = ['password']; // only check for the password field on creation
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
                    isset($evalErrors['password']) &&
                    is_array($evalErrors['password']) &&
                    in_array('twice', $evalErrors['password'])
                ) {
                    SessionUtility::clearData(
                        $frontendUser,
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
                            $frontendUser,
                            $extensionKey,
                            $cmdKey,
                            $conf,
                            $conf[$cmdKey . '.'],
                            $finalDataArray,
                            $autoLoginKey
                        );
                    }

                    // If inviting or if auto-login will be required on confirmation, we store an encrypted version of the password
                    $savePassword =
                        SecuredData::readPasswordForStorage(
                            $frontendUser,
                            $extensionKey
                        );
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

                $newDataArray = [];
                $theUid = $dataObj->save(
                    $newDataArray,
                    $staticInfoObj,
                    $controlData,
                    $theTable,
                    $finalDataArray,
                    $origArray,
                    $frontendUser->user,
                    $controlData->readToken(),
                    $cmd,
                    $cmdKey,
                    $controlData->getPid(),
                    $savePassword,
                    $extraFields,
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess']
                );
                debug ($newDataArray, '$newDataArray');

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
                            $frontendUser,
                            $extensionKey,
                            true,
                            $controlData->readToken(),
                            $controlData->readRedirectUrl()
                        );
                    }
                }
            }
        } elseif ($cmd == 'infomail') {
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
                    [],
                    [],
                    $controlData->getCaptcha()
                );
            }
            $controlData->setRequiredArray([]);
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
        $content = '';
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
                    $dataArray,
                    $frontendUser->user
                );
            }
        }
        $errorContent = '';
        $deleteRegHash = false;
        $confirmationEmailSent = false;

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

            $afterSave = GeneralUtility::makeInstance(AfterSaveView::class);
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
                    $extensionKey,
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
                $bEmailSent = false;

                if (
                    $conf['enableAdminReview'] &&
                    $bDefaultMode &&
                    !$bCustomerConfirmsMode
                ) {
                    $email = GeneralUtility::makeInstance(Email::class);
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
                        [$dataArray],
                        [$origArray],
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
                } elseif (
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
                            !empty($finalDataArray[$emailField])
                        ) ?
                        $finalDataArray[$emailField] :
                        $origArray[$emailField];
                    $email = GeneralUtility::makeInstance(Email::class);
                    debug ($dataArray, '$dataArray vor compile');
                    debug ($origArray, '$origArray');

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
                        [$dataArray],
                        [$origArray],
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
                    isset($errorCode[0])
                ) {
                    $errorText = $languageObj->getLabel($errorCode[0], $dummy, '', false, true);
                    if (isset($errorCode[1])) {
                        $errorContent = sprintf($errorText, $errorCode[1]);
                    } else {
                        $errorContent = $errorText;
                    }
                }
            }

            if ($errorContent == '') {	// success case
                $origGetFeUserData = $parameterApi->getGetParameter($prefixId);
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
                            $controlData->getBackURL() ?:
                            FrontendUtility::getTypoLink_URL(
                                $cObj,
                                $conf['linkToPID'] . ',' . $controlData->getType()
                            )
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
                    $password =
                        SecuredData::readPassword(
                            $frontendUser,
                            $extensionKey
                        );
                    $systemObj =
                        GeneralUtility::makeInstance(
                            System::class,
                            $controlData
                        );
                    $loginSuccess =
                        $systemObj->login(
                            $cObj,
                            $languageObj,
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
        } elseif ($dataObj->getError()) {

            // If there was an error, we return the template-subpart with the error message
            $templateCode = $templateService->getSubpart($templateCode, $dataObj->getError());

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
                [],
                $controlData->getRequiredArray(),
                $dataObj->getFieldList(),
                $GLOBALS['TCA'][$theTable]['columns'],
                '',
                false
            );
            $markerObj->setArray($markerArray);
            $content = $templateService->substituteMarkerArray($templateCode, $markerArray);
        } elseif ($content == '') {
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
                case 'login':
                    // Login is done in the Setfixed class. The login can instead happen in a separate login extension.
                case 'setfixed':
                    if ($conf['infomail']) {
                        $controlData->setSetfixedEnabled(1);
                    }

                    $origArray = $dataObj->parseIncomingData($origArray, false);
                    $content = $setfixedObj->process(
                        $hasError,
                        $confirmationEmailSent,
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
                        $token
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
                    $email = GeneralUtility::makeInstance(Email::class);
                    $fetch = $controlData->getFeUserData('fetch');
                    $pidLock = '';

                    if (isset($fetch) && !empty($fetch)) {
                        $pages = ($cObj->data['pages'] ? $cObj->data['pages'] . ',' : '') . $controlData->getPid();
                        $allPages = TableUtility::getAllSubPages($pages);
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
                        isset($errorCode[0])
                    ) {
                        $content = $languageObj->getLabel($errorCode[0]);
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
                        $frontendUser->user,
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
                isset($errorCode[0])
            ) {
                $errorText = $languageObj->getLabel($errorCode[0]);
                if (isset($errorCode[1])) {
                    $errorContent = sprintf($errorText, $errorCode[1]);
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
                !$confirmationEmailSent &&
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
            $setfixedUrls = GeneralUtility::makeInstance(SetfixedUrls::class);
            $setfixedUrls->deleteShortUrl($regHash);
        }

        return $content;
    }
}
