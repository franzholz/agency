<?php

namespace JambageCom\Agency\Security;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
* Part of the agency (Agency Registration) extension. Former class tx_agency_transmission_security
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\Backend\BackendFactory;
use TYPO3\CMS\Rsaauth\Storage\StorageFactory;

use JambageCom\Div2007\Utility\HtmlUtility;


class TransmissionSecurity implements \TYPO3\CMS\Core\SingletonInterface {
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
    protected function setTransmissionSecurityLevel ($level = '') {
        if ($level == '') {
            $level = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];
        }
        $this->transmissionSecurityLevel = $level;
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
    * @return boolean true if a decryption has been done
    */
    public function decryptIncomingFields ($extensionKey, array &$row, &$errorMessage) {
        $decrypted = false;

        if (count($row)) {
            switch ($this->getTransmissionSecurityLevel()) {
                case 'rsa':
                    $needsDecryption = false;
                    foreach ($row as $field => $value) {
                        if (isset($value) && $value != '') {
                            if (substr($value, 0, 4) == 'rsa:') {
                                $needsDecryption = true;
                            }
                        }
                    }
                    
                    if (!$needsDecryption) {
                        return $decrypted;
                    }

                        // Get services from rsaauth
                        // Can't simply use the authentication service because we have two fields to decrypt
                    /** @var $backend \TYPO3\CMS\Rsaauth\Backend\AbstractBackend */
                    $backend = BackendFactory::getBackend();
                    /** @var $storage \TYPO3\CMS\Rsaauth\Storage\AbstractStorage */
                    $storage = StorageFactory::getStorage();
            
                    if (is_object($backend) && is_object($storage)) {
                        $key = $storage->get();
                        if ($key != null) {
                            foreach ($row as $field => $value) {
                                if (isset($value) && $value != '') {
                                    if (substr($value, 0, 4) == 'rsa:') {
                                            // Decode password
                                        $result = $backend->decrypt($key, substr($value, 4));
                                        if ($result) {
                                            $row[$field] = $result;
                                            $decrypted = true;
                                        } else {
                                                // RSA auth service failed to process incoming password
                                                // May happen if the key is wrong
                                                // May happen if multiple instance of rsaauth on same page
                                            $errorMessage = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_rsaauth_process_incoming_password_failed');
                                            GeneralUtility::sysLog($errorMessage, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                                        }
                                    }
                                }
                            }
                                // Remove the key
                            $storage->put(null);
                        } else {
                                // RSA auth service failed to retrieve private key
                                // May happen if the key was already removed
                            $errorMessage = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_rsaauth_retrieve_private_key_failed');
                            GeneralUtility::sysLog($errorMessage, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                        }
                    } else {
                            // Required RSA auth backend not available
                            // Should not happen: checked in tx_agency_pi_base::checkRequirements
                        $errorMessage = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_rsaauth_backend_not_available');
                        GeneralUtility::sysLog($errorMessage, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
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

    public function getJavaScript (
        &$javaScript,
        $extensionKey,
        $checkPasswordAgain,
        $formId,
        $loginForm = false
    ) {
        if (
            $this->getTransmissionSecurityLevel() == 'rsa' &&
            $checkPasswordAgain
        ) {
            $javaScript .=
'<script type="text/javascript">
document.getElementById(\'' . $formId . '\').addEventListener(\'submit\', function(event) {
        var password = document.getElementById(\'' . $extensionKey . '-password\'); 
        var password_again = document.getElementById(\'' . $extensionKey . '-password_again\');

        if (!password.value.trim().length) {
            event.stopImmediatePropagation();
            return false; 
        }
        if (password.value != password_again.value) {
            document.getElementById(\'password_again_failure\').value = 1;
            password.value = \'X\';
            event.stopImmediatePropagation();
        } else {
            document.getElementById(\'' . $extensionKey . '[submit-security]\').value = \'1\'; 
        }
        password_again.value = \'\';
    });
</script>';
        }
    }

    /**
    * Adds values to the ###HIDDENFIELDS### and ###ENCRYPTION### markers
    *
    * @param array $markerArray: marker array
    * @return void
    */
    public function getMarkers (
        $extensionKey,
        array &$markerArray,
        $checkPasswordAgain,
        $loginForm = false
    ) {
        $markerArray['###ENCRYPTION###'] = '';
        $xhtmlFix = HtmlUtility::getXhtmlFix();
        $extraHiddenFieldsArray = array();

        if (
            $loginForm &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])
        ) {
            $_params = array();
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
                list($onSubmit, $hiddenFields) = GeneralUtility::callUserFunction($funcRef, $_params, $this);
                $extraHiddenFieldsArray[] = $hiddenFields;
            }
        }

        switch ($this->getTransmissionSecurityLevel()) {
            case 'rsa':
                if ($checkPasswordAgain) {

                    $extraHiddenFieldsArray[] = '<input type="hidden" name="password_again_failure" value="0"' . $xhtmlFix . '>' . LF . '<input type="hidden" name="' . $extensionKey . '[submit-security]" value="0"' . $xhtmlFix . '>';
                }

                $markerArray['###ENCRYPTION###'] = ' data-rsa-encryption=""';
                break;
            case 'normal':
            default:
                break;
        }

        $extraHiddenFields = '';
        if (count($extraHiddenFieldsArray)) {
            $extraHiddenFields = LF . implode(LF, $extraHiddenFieldsArray);
        }

        if ($extraHiddenFields != '') {
            $markerArray['###HIDDENFIELDS###'] .= $extraHiddenFields . LF;
        }
        
        return true;
    }
}

