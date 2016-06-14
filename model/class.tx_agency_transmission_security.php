<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Storage security functions
 *
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */
class tx_agency_transmission_security {
		// Extension key
	protected $extKey = AGENCY_EXT;
		// The storage security level: normal or rsa
	protected $transmissionSecurityLevel = 'normal';

	/**
	* Constructor
	*
	* @return	void
	*/
	public function __construct () {
		$this->setTransmissionSecurityLevel();
	}

	/**
	* Sets the transmission security level
	*
	* @return	void
	*/
	protected function setTransmissionSecurityLevel () {
		$this->transmissionSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];
	}

	/**
	* Gets the transmission security level
	*
	* @return	string	the storage security level
	*/
	public function getTransmissionSecurityLevel () {
		return $this->transmissionSecurityLevel;
	}

	/**
	* Decrypts fields that were encrypted for transmission
	*
	* @param array $row: incoming data array that may contain encrypted fields
	* @return boolean TRUE if a decryption has been done
	*/
	public function decryptIncomingFields (array &$row, $message = '') {
		$decrypted = FALSE;

		if (count($row)) {
			switch ($this->getTransmissionSecurityLevel()) {
				case 'rsa':
						// Get services from rsaauth
						// Can't simply use the authentication service because we have two fields to decrypt
					$backend = tx_rsaauth_backendfactory::getBackend();
					$storage = tx_rsaauth_storagefactory::getStorage();
					/* @var $storage tx_rsaauth_abstract_storage */
					if (is_object($backend) && is_object($storage)) {
						$key = $storage->get();
						if ($key != NULL) {
							foreach ($row as $field => $value) {
								if (isset($value) && $value != '') {
									if (substr($value, 0, 4) == 'rsa:') {
											// Decode password
										$result = $backend->decrypt($key, substr($value, 4));

										if ($result) {
											$row[$field] = $result;
											$decrypted = TRUE;
										} else {
												// RSA auth service failed to process incoming password
												// May happen if the key is wrong
												// May happen if multiple instance of rsaauth on same page
											$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $this->extKey . '/pi/locallang.xml:internal_rsaauth_process_incoming_password_failed');
											t3lib_div::sysLog($message, $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
										}
									}
								}
							}
								// Remove the key
							$storage->put(NULL);
						} else {
								// RSA auth service failed to retrieve private key
								// May happen if the key was already removed
							$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $this->extKey . '/pi/locallang.xml:internal_rsaauth_retrieve_private_key_failed');
							t3lib_div::sysLog($message, $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
						}
					} else {
							// Required RSA auth backend not available
							// Should not happen: checked in tx_agency_pi_base::checkRequirements
						$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $this->extKey . '/pi/locallang.xml:internal_rsaauth_backend_not_available');
						t3lib_div::sysLog($message, $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
					}
					break;
				case 'normal':
				default:
						// Nothing to decrypt
					break;
			}
		}
		return $decrypted;
	}

	/**
	* Gets value for ###FORM_ONSUBMIT### and ###HIDDENFIELDS### markers
	*
	* @param array $markerArray: marker array
	* @return void
	*/
	public function getMarkers (array &$markerArray, $checkPasswordAgain) {
		$markerArray['###FORM_ONSUBMIT###'] = '';

		switch ($this->getTransmissionSecurityLevel()) {
			case 'rsa':
				$onSubmit = '';
				$extraHiddenFieldsArray = array();

				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])) {
					$_params = array();
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
						list($onSubmit, $hiddenFields) = t3lib_div::callUserFunction($funcRef, $_params, $this);
						$extraHiddenFieldsArray[] = $hiddenFields;
					}
				} else {
						// Extension rsaauth not installed
						// Should not happen: checked in tx_agency_pi_base::checkRequirements
					$message = sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $this->extKey . '/pi/locallang.xml:internal_required_extension_missing'), 'rsaauth');
					t3lib_div::sysLog($message, $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
					return;
				}

				if (version_compare(TYPO3_version, '6.2.0', '<')) {
					$headerData = '<script type="text/javascript" src="' . $GLOBALS['TSFE']->absRefPrefix . t3lib_div::createVersionNumberedFilename(t3lib_extMgm::siteRelPath('agency')  . 'scripts/rsaauth.js') . '"></script>';
					$GLOBALS['TSFE']->additionalHeaderData['agency_rsaauth'] = $headerData;
					$onSubmit = 'x_agency_encrypt(this); return true;';
				} else {
					if ($checkPasswordAgain) {
						$onSubmit = 'if (!this.'. $this->extKey . '-password.value) return 0; if (this.pass.value != this[\'FE[fe_users][password_again]\'].value) {this.password_again_failure.value = 1; this.'. $this->extKey . '-password.value = \'X\'; this[\'FE[fe_users][password_again]\'].value = \'\'; return true;} else { this[\'' . $this->extKey . '[submit-security]\'].value =\'1\'; this[\'FE[fe_users][password_again]\'].value = \'\'; ' . $onSubmit . '}';
						$extraHiddenFieldsArray[] = '<input type="hidden" name="password_again_failure" value="0">' . LF . '<input type="hidden" name="'. $this->extKey . '[submit-security]" value="0">';
					}
				}

				$markerArray['###FORM_ONSUBMIT###'] = ' onsubmit="' . $onSubmit . '"';

				$extraHiddenFields = '';
				if (count($extraHiddenFieldsArray)) {
					$extraHiddenFields = LF . implode(LF, $extraHiddenFieldsArray);
				}
				$markerArray['###HIDDENFIELDS###'] .= $extraHiddenFields;
				break;
			case 'normal':
			default:
				$markerArray['###HIDDENFIELDS###'] .= LF;
				break;
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/model/class.tx_agency_transmission_security.php']) {
  include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/model/class.tx_agency_transmission_security.php']);
}
