<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2015 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * display functions
 *
 * @author	Kasper Skaarhoj <kasper2007@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */

class tx_agency_control {
	public $langObj;
	public $marker;
	public $auth;
	public $email;
	public $tca;
	public $requiredArray; // List of required fields
	public $controlData;
		// Commands that may be processed when no user is logged in
	public $noLoginCommands = array('create', 'invite', 'setfixed', 'infomail', 'login');


	public function init (
		tx_agency_conf $confObj,
		$langObj,
		$cObj,
		$controlData,
		$marker,
		$email,
		$tca,
		$urlObj
	) {
		$this->langObj = $langObj;
		$conf = $confObj->getConf();
		$this->marker = $marker;
		$this->email = $email;
		$this->tca = $tca;
		$this->urlObj = $urlObj;
			// Retrieve the extension key
		$extKey = $controlData->getExtKey();
			// Get the command as set in piVars
		$cmd = $controlData->getCmd();

			// If not set, get the command from the flexform
		if ($cmd == '') {
				// Check the flexform
			$cObj->data['pi_flexform'] = t3lib_div::xml2array($cObj->data['pi_flexform']);
			$cmd = tx_div2007_alpha5::getSetupOrFFvalue_fh004(
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
		tx_agency_conf $confObj,
		$staticInfoObj,
		$theTable,
		tx_agency_controldata $controlData,
		tx_agency_data $dataObj,
		&$adminFieldList,
		array &$origArray
	) {
		$conf = $confObj->getConf();
		$tablesObj = t3lib_div::getUserObj('&tx_agency_lib_tables');
		$addressObj = $tablesObj->get('address');
		$extKey = $controlData->getExtKey();
		$cmd = $controlData->getCmd();
		$dataArray = $dataObj->getDataArray();
		$fieldlist = '';
		$modifyPassword = FALSE;

		$bHtmlSpecialChars = FALSE;
		$controlData->secureInput($dataArray, $bHtmlSpecialChars);

		if (
			$theTable == 'fe_users'
		) {
			$modifyPassword = $controlData->securePassword($dataArray);
		}
		$dataObj->setDataArray($dataArray);

		if (version_compare(TYPO3_version, '6.2.0', '<')) {

				// Setting the list of fields allowed for editing and creation.
			$tcaFieldArray =
				t3lib_div::trimExplode(
					',',
					$GLOBALS['TCA'][$theTable]['feInterface']['fe_admin_fieldList'],
					1
				);
			$tcaFieldArray = array_unique($tcaFieldArray);
			$fieldlist = implode(',', $tcaFieldArray);
		} else {
			$fieldlist = implode(',', tx_div2007_core::getFields($theTable));
		}

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
				FALSE
			);
		}

		$feUserdata = $controlData->getFeUserData();
		$theUid = 0;

		if (is_array($dataArray) && $dataArray['uid']) {
			$theUid = $dataArray['uid'];
		} else if (is_array($feUserdata) && $feUserdata['rU']) {
			$theUid = $feUserdata['rU'];
		} else if (!in_array($cmd, $this->noLoginCommands)) {
			$theUid = $GLOBALS['TSFE']->fe_user->user['uid'];
		}

		if ($theUid) {
			$dataObj->setRecUid($theUid);
			$newOrigArray = $GLOBALS['TSFE']->sys_page->getRawRecord($theTable, $theUid);

			if (isset($newOrigArray) && is_array($newOrigArray)) {
				$this->tca->modifyRow(
					$staticInfoObj,
					$theTable,
					$newOrigArray,
					$dataObj->getFieldList(),
					TRUE
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

		if ($cmdKey == '') {
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
					t3lib_div::trimExplode(',', $adminFieldList, 1)
				)
			);
		$dataObj->setAdminFieldList($adminFieldList);

		if (!t3lib_extMgm::isLoaded('direct_mail')) {
			$conf[$cmdKey.'.']['fields'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('module_sys_dmail_category,module_sys_dmail_newsletter')));
			$conf[$cmdKey . '.']['required'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['required'], 1), array('module_sys_dmail_category, module_sys_dmail_newsletter')));
		}

		$fieldConfArray = array('fields', 'required');
		foreach ($fieldConfArray as $k => $v) {
			// make it ready for t3lib_div::inList which does not yet allow blanks
			$conf[$cmdKey . '.'][$v] = implode(',',  array_unique(t3lib_div::trimExplode(',', $conf[$cmdKey . '.'][$v])));
		}

		$theTable = $controlData->getTable();
		if ($theTable == 'fe_users') {
				// When not in edit mode, add username to lists of fields and required fields unless explicitly disabled
			if (empty($conf[$cmdKey.'.']['doNotEnforceUsername'])) {
				if ($cmdKey != 'edit' && $cmdKey != 'password') {
					$conf[$cmdKey . '.']['fields'] = implode(',', array_unique(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',username', 1)));
					$conf[$cmdKey . '.']['required'] = implode(',', array_unique(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',username', 1)));
				}
			}

			// When in edit mode, remove password from required fields
			if ($cmdKey == 'edit') {
				$conf[$cmdKey . '.']['required'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['required'], 1), array('password')));
			}

			if ($conf[$cmdKey . '.']['generateUsername'] || $cmdKey == 'password') {
				$conf[$cmdKey . '.']['fields'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('username')));
			}

			if (
				(
					$cmdKey == 'invite' ||
					$cmdKey == 'create'
				) && $conf[$cmdKey . '.']['generatePassword']
			) {
				$conf[$cmdKey . '.']['fields'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('password')));
			}

			if ($conf[$cmdKey . '.']['useEmailAsUsername']) {
				$conf[$cmdKey . '.']['fields'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('username')));
				if ($cmdKey == 'create' || $cmdKey == 'invite') {
					$conf[$cmdKey . '.']['fields'] = implode(',', t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'] . ',email', 1));
					$conf[$cmdKey . '.']['required'] = implode(',', t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['required'] . ',email', 1));
				}
				if (
					($cmdKey == 'edit' || $cmdKey == 'password') &&
					$controlData->getSetfixedEnabled()
				) {
					$conf[$cmdKey . '.']['fields'] = implode(',', array_diff(t3lib_div::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1), array('email')));
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
			t3lib_extMgm::isLoaded('tt_address') &&
			isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address'])
		) {
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address']);
			if (is_array($extConf) && $extConf['disableCombinedNameField'] == '1') {
				$conf[$cmdKey . '.']['fields'] = t3lib_div::rmFromList('name', $conf[$cmdKey . '.']['fields']);
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
		}
		$confObj->setConf($conf);

			// Setting requiredArr to the fields in "required" fields list intersected with the total field list in order to remove invalid fields.
		$requiredArray = array_intersect(
			t3lib_div::trimExplode(
				',',
				$conf[$cmdKey . '.']['required'],
				1
			),
			t3lib_div::trimExplode(
				',',
				$conf[$cmdKey . '.']['fields'],
				1
			)
		);
		$dataObj->setDataArray($dataArray);
		$controlData->setRequiredArray($requiredArray);
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
		tx_agency_conf $confObj,
		$setfixedObj,
		$langObj,
		$displayObj,
		$controlData,
		$dataObj,
		$staticInfoObj,
		$theTable,
		$cmd,
		$cmdKey,
		array $origArray,
		$templateCode,
		&$error_message
	) {
		$dataArray = $dataObj->getDataArray();
		$conf = $confObj->getConf();
		$extKey = $controlData->getExtKey();
		$prefixId = $controlData->getPrefixId();
		$controlData->setMode(MODE_NORMAL);

		$savePassword = '';
		$autoLoginKey = '';
		$hasError = FALSE;
		$parseResult = TRUE;

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
			tx_div2007_core::mergeRecursiveWithOverrule($finalDataArray, $securedArray);
		} else {
			$finalDataArray = $dataArray;
		}

		$hasSubmitData = (
			$controlData->getFeUserData('submit') != '' ||
			$controlData->getFeUserData('submit-security') != ''
		);

		if ($hasSubmitData) {
			$bSubmit = TRUE;
			$controlData->setSubmit(TRUE);
		}

		$doNotSaveData = $controlData->getFeUserData('doNotSave');

		if ($doNotSaveData != '') {
			$bDoNotSave = TRUE;
			$controlData->setDoNotSave(TRUE);
			$controlData->clearSessionData();
		}

		$markerArray = $this->marker->getArray();

			// Evaluate incoming data
		if (
			is_array($finalDataArray) &&
			count($finalDataArray) &&
			!in_array($cmd, $noSaveCommands)
		) {
			$dataObj->setName($finalDataArray, $cmdKey, $theTable);
			$parseResult = $dataObj->parseValues($theTable, $finalDataArray, $origArray, $cmdKey);
			$dataObj->overrideValues($finalDataArray, $cmdKey);

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
					array()
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
					$this->marker->setArray($markerArray);
					$finalDataArray =
						tx_div2007_alpha5::userProcess_fh002(
							$this,
							$conf,
							'evalFunc',
							$finalDataArray
						);
					$markerArray = $this->marker->getArray();
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
					$checkFieldArray
				);

					// If the two password fields are not equal, clear session data
				if (
					is_array($evalErrors['password']) &&
					in_array('twice', $evalErrors['password'])
				) {
					$controlData->clearSessionData();
				}

				$this->marker->setArray($markerArray);
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
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['registrationProcess']
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
					array()
				);
			}
			$controlData->setRequiredArray(array());
			$this->marker->setArray($markerArray);
			$controlData->setFeUserData(0, 'preview');
		} else {
			$this->marker->setNoError($cmdKey, $markerArray);
			$this->marker->setArray($markerArray);
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
			$this->marker->setPreviewLabel('_PREVIEW');
			$controlData->setMode(MODE_PREVIEW);
		}

			// If data is submitted, we take care of it here.
		if (
			$cmd == 'delete' &&
			!$controlData->getFeUserData('preview') &&
			!$bDoNotSave
		) {
			// Delete record if delete command is set + the preview flag is NOT set.
			$dataObj->deleteRecord($controlData, $theTable, $origArray, $dataArray);
		}
		$errorContent = '';
		$bDeleteRegHash = FALSE;


			// Display forms
		if ($dataObj->getSaved()) {

			$bCustomerConfirmsMode = FALSE;
			$bDefaultMode = FALSE;
			if (
				($cmd == '' || $cmd == 'create')
			) {
				$bDefaultMode = TRUE;
			}

			if (
				$bDefaultMode &&
				($cmdKey != 'edit') &&
				$conf['enableAdminReview'] &&
				($conf['enableEmailConfirmation'] || $conf['infomail'])
			) {
				$bCustomerConfirmsMode = TRUE;
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
				$displayObj->getKeyAfterSave(
					$cmd,
					$cmdKey,
					$bCustomerConfirmsMode,
					$bSetfixed,
					$bCreateReview
				);

			$errorContent =
				$displayObj->afterSave(
					$conf,
					$cObj,
					$langObj,
					$controlData,
					$confObj,
					$this->tca,
					$this->marker,
					$dataObj,
					$setfixedObj,
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
				$markerArray = $this->marker->getArray(); // uses its own markerArray
				$errorCode = '';
				$bEmailSent = FALSE;

				if (
					$conf['enableAdminReview'] &&
					$bDefaultMode &&
					!$bCustomerConfirmsMode
				) {
						// Send admin the confirmation email
						// The user will not confirm in this mode
					$bEmailSent = $this->email->compile(
						SETFIXED_PREFIX . 'REVIEW',
						$conf,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
						$displayObj,
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

					// Send email message(s)
					$bEmailSent = $this->email->compile(
						$key,
						$conf,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
						$displayObj,
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
					$errorText = $langObj->getLL($errorCode['0'], '', FALSE, TRUE);
					$errorContent = sprintf($errorText, $errorCode['1']);
				}
			}

			if ($errorContent == '') {	// success case
				$origGetFeUserData = t3lib_div::_GET($prefixId);
				$bDeleteRegHash = TRUE;

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
					header('Location: '.t3lib_div::locationHeaderUrl($destUrl));
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
					$loginSuccess = FALSE;
					$password = $controlData->readPassword();
					$loginSuccess =
						$this->login(
							$conf,
							$langObj,
							$controlData,
							$dataArray['username'],
							$password,
							TRUE
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

			$this->marker->addLabelMarkers(
				$markerArray,
				$conf,
				$cObj,
				$controlData->getExtKey(),
				$theTable,
				$finalDataArray,
				$dataObj->getOrigArray(),
				$securedArray,
				array(),
				$controlData->getRequiredArray(),
				$dataObj->getFieldList(),
				$GLOBALS['TCA'][$theTable]['columns'],
				'',
				FALSE
			);

			$this->marker->setArray($markerArray);
			$content = $cObj->substituteMarkerArray($templateCode, $markerArray);
		} else {
				// Finally, there has been no attempt to save.
				// That is either preview or just displaying an empty or not correctly filled form
			$this->marker->setArray($markerArray);
			$token = $controlData->readToken();

			if ($cmd == '' && $controlData->getFeUserData('preview')) {
				$cmd = $cmdKey;
			}
			switch ($cmd) {
				case 'setfixed':
					if ($conf['infomail']) {
						$controlData->setSetfixedEnabled(1);
					}
					$feuData = $controlData->getFeUserData();
					$origArray = $dataObj->parseIncomingData($origArray, FALSE);
					$content = $setfixedObj->processSetFixed(
						$conf,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
						$theTable,
						$autoLoginKey,
						$prefixId,
						$uid,
						$cmdKey,
						$markerArray,
						$displayObj,
						$this->email,
						$templateCode,
						$finalDataArray,
						$origArray,
						$securedArray,
						$this,
						$feuData,
						$token,
						$hasError
					);
					break;
				case 'infomail':
					$this->marker->addGeneralHiddenFieldsMarkers(
						$markerArray,
						$cmd,
						$token
					);
					if ($conf['infomail']) {
						$controlData->setSetfixedEnabled(1);
					}
					$origArray = $dataObj->parseIncomingData($origArray, FALSE);
					$errorCode = '';
					$content = $this->email->sendInfo(
						$conf,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
						$displayObj,
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
					$this->marker->addGeneralHiddenFieldsMarkers(
						$markerArray,
						$cmd,
						$token
					);
					$content = $displayObj->deleteScreen(
						$markerArray,
						$conf,
						$prefixId,
						$extKey,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
						$theTable,
						$finalDataArray,
						$origArray,
						$securedArray,
						$token
					);
					break;
				case 'edit':
				case 'password':
					$this->marker->addGeneralHiddenFieldsMarkers(
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
						$this->tca,
						$this->marker,
						$dataObj,
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
					$this->marker->addGeneralHiddenFieldsMarkers(
						$markerArray,
						$cmd,
						$token
					);
					$content = $displayObj->createScreen(
						$markerArray,
						$conf,
						$prefixId,
						$extKey,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
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
					$this->marker->addGeneralHiddenFieldsMarkers(
						$markerArray,
						$cmd,
						$token
					);
					$content = $displayObj->createScreen(
						$markerArray,
						$conf,
						$prefixId,
						$extKey,
						$cObj,
						$langObj,
						$controlData,
						$confObj,
						$this->tca,
						$this->marker,
						$dataObj,
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
					$cmdKey != 'edit' ||
					$cmdKey != 'password'
				) &&
				!$errorContent &&
				!$hasError &&
				!$controlData->getFeUserData('preview')
			) {
				$bDeleteRegHash = TRUE;
			}
		}

		if (
			$bDeleteRegHash &&
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
	 * @param boolen $redirect: whether to redirect after login or not. If TRUE, then you must immediately call exit after this call
	 * @return boolean TRUE, if login was successful, FALSE otherwise
	 */
	public function login (
		$conf,
		$langObj,
		$controlData,
		$username,
		$cryptedPassword,
		$redirect = TRUE
	) {
		$result = TRUE;
		$message = '';

			// Log the user in
		$loginData = array(
			'uname' => $username,
			'uident' => $cryptedPassword,
			'uident_text' => $cryptedPassword,
			'status' => 'login',
		);

		// Check against configured pid (defaulting to current page)
		$GLOBALS['TSFE']->fe_user->checkPid = TRUE;
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
			} else {
				$serviceKeyArray[] = 'tx_saltedpasswords_sv1';
			}

			if (
				$conf['authServiceClass'] != '' &&
				$conf['authServiceClass'] != '{$plugin.tx_agency.authServiceClass}' &&
				class_exists($conf['authServiceClass'])
			) {
				$serviceKeyArray = array_merge($serviceKeyArray, t3lib_div::trimExplode(',', $conf['authServiceClass']));
			}

			$serviceChain = '';
			$ok = FALSE;
			$authServiceObj = FALSE;

			while (is_object($authServiceObj = t3lib_div::makeInstanceService('auth', 'authUserFE', $serviceChain))) {
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
				$result = FALSE;
			} else {
					// Required authentication service not available
				$message = $langObj->getLL('internal_required_authentication_service_not_available');
				$result = FALSE;
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
			$result = FALSE;
		}

		if ($result == FALSE) {
			$controlData->clearSessionData(FALSE);

			if ($message != '') {
				t3lib_div::sysLog($message, $controlData->getExtKey(), t3lib_div::SYSLOG_SEVERITY_ERROR);
			}
		}

		if (
			$redirect
		) {
				// Redirect to configured page, if any
			$redirectUrl = $controlData->readRedirectUrl();
			if (!$redirectUrl && $result == TRUE) {
				$redirectUrl = trim($conf['autoLoginRedirect_url']);
			}

			if (!$redirectUrl) {
				if ($conf['loginPID']) {
					$redirectUrl = $this->urlObj->get('', $conf['loginPID']);
				} else {
					$redirectUrl = $controlData->getSiteUrl();
				}
			}
			header('Location: ' . t3lib_div::locationHeaderUrl($redirectUrl));
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/control/class.tx_agency_control.php']) {
  include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/control/class.tx_agency_control.php']);
}

