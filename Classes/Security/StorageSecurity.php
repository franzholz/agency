<?php

namespace JambageCom\Agency\Security;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
* Part of the agency (Agency Registration) extension. former class tx_agency_storage_security
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\Backend\BackendFactory;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;


class StorageSecurity implements \TYPO3\CMS\Core\SingletonInterface {
        // Extension key
    protected $extKey = AGENCY_EXT;

    /**
    * Gets the storage security level
    *
    * @return	string	the storage security level
    */
    static protected function getStorageSecurityLevel () {
        $result = 'normal';
        if (
            ExtensionManagementUtility::isLoaded('saltedpasswords') &&
            SaltedPasswordsUtility::isUsageEnabled('FE')
        ) {
            $result = 'salted';
        }
        return $result;
    }

    /**
    * Encrypts the password for secure storage
    *
    * @param	string	$password: password to encrypt
    * @return	string	encrypted password
    *           boolean false in case of an error
    */
    public function encryptPasswordForStorage ($password) {

        $encryptedPassword = $password;
        if ($password != '') {
            switch ($this->getStorageSecurityLevel()) {
                case 'salted':
                    $objSalt = SaltFactory::getSaltingInstance(null);
                    if (is_object($objSalt)) {
                        $encryptedPassword = $objSalt->getHashedPassword($password);
                    } else {
                        $encryptedPassword = false;
                        // Could not get a salting instance from saltedpasswords
                        // Should not happen: checked in method checkRequirements
                    }
                    break;
                case 'normal':
                default:
                        // No encryption!
                    break;
            }
        }

        return $encryptedPassword;
    }

    /**
    * Determines if auto login should be attempted
    *
    * @param array $feuData: incoming fe_users parameters
    * @param string &$autoLoginKey: returns auto-login key
    * @return boolean true, if auto-login should be attempted
    */
    public function getAutoLoginIsRequested (
        array $feuData,
        &$autoLoginKey
    ) {
        $autoLoginIsRequested = false;
        if (isset($feuData['key']) && $feuData['key'] !== '') {
            $autoLoginKey = $feuData['key'];
            $autoLoginIsRequested = true;
        }

        return $autoLoginIsRequested;
    }

    /**
    * Encrypts the password for auto-login on confirmation
    *
    * @param	string	$password: the password to be encrypted
    * @param	string	$cryptedPassword: returns the encrypted password
    * @param	string	$autoLoginKey: returns the auto-login key
    * @return	boolean  true if the crypted password and auto-login key are filled in
    */
    public function encryptPasswordForAutoLogin (
        $password,
        &$cryptedPassword,
        &$autoLoginKey
    ) {
        $result = false;
        $privateKey = '';
        $cryptedPassword = '';

        if ($password != '') {
                // Create the keypair
            $keyPair = openssl_pkey_new();

                // Get private key
            openssl_pkey_export($keyPair, $privateKey);
                // Get public key
            $keyDetails = openssl_pkey_get_details($keyPair);
            $publicKey = $keyDetails['key'];

            if (@openssl_public_encrypt($password, $cryptedPassword, $publicKey)) {
                $autoLoginKey = $privateKey;
                $result = true;
            }
        }

        return $result;
    }

    /**
    * Decrypts the password for auto-login on confirmation or invitation acceptation
    *
    * @param	string	$password: the password to be decrypted
    * @param	string	$autoLoginKey: the auto-login private key
    * @return	boolean  true if decryption is successfull or no rsaauth is used
    */
    public function decryptPasswordForAutoLogin (
        &$password,
        $autoLoginKey
    ) {
        $result = true;
        if ($autoLoginKey != '') {
            $privateKey = $autoLoginKey;
            if ($privateKey != '') {
                if (
                    $password != '' &&
                    ExtensionManagementUtility::isLoaded('rsaauth')
                ) {
                    $backend = BackendFactory::getBackend();
                    if (is_object($backend) && $backend->isAvailable()) {
                        $decryptedPassword = $backend->decrypt($privateKey, $password);
                        if ($decryptedPassword) {
                            $password = $decryptedPassword;
                        } else {
                                // Failed to decrypt auto login password
                            $message =
                                $GLOBALS['TSFE']->sL(
                                    'LLL:EXT:' . $this->extKey . '/pi/locallang.xml:internal_decrypt_auto_login_failed'
                                );
                            GeneralUtility::sysLog(
                                $message,
                                $this->extKey,
                                GeneralUtility::SYSLOG_SEVERITY_ERROR
                            );
                        }
                    } else {
                        // Required RSA auth backend not available
                        // Should not happen: checked in method checkRequirements
                        $result = false;
                    }
                }
            }
        }

        return $result;
    }
}

