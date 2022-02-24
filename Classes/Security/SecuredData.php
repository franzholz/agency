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
* Part of the agency (Agency Registration) extension. former class tx_agency_auth
*
* authentication functions
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Agency\Utility\SessionUtility;


/**
* Secured data handling
*/
class SecuredData
{
    /**
    * Names of secured fields
    *
    * @var array
    */
    static protected $securedFields = array('password', 'password_again', 'tx_agency_password');

    /**
     * Gets the transmission security object
     *
     * @return tx_agency_transmission_security the transmission security object
     */
    static public function getTransmissionSecurity () {
        $result = GeneralUtility::makeInstance(
            \JambageCom\Div2007\Security\TransmissionSecurity::class
        );
        return $result;
    }

    /**
     * Gets the storage security object
     *
     * @return tx_agency_transmission_security the storage security object
     */
    static public function getStorageSecurity () {
        $result = GeneralUtility::makeInstance(
            \JambageCom\Div2007\Security\StorageSecurity::class
        );
        return $result;
    }

    /**
    * Gets the array of names of secured fields
    *
    * @return array names of secured fields
    */
    static public function getSecuredFields ()
    {
        return self::$securedFields;
    }

    /**
    * Reduces the list of fields to the fields that are allowed to be shown
    *
    * @param array $fields: initial list of field names
    * @return array new list of field names
    */
    static public function getOpenFields ($fields)
    {
        $securedFieldArray = self::getSecuredFields();
        $fieldArray = array_unique(GeneralUtility::trimExplode(',', $fields));
        foreach ($securedFieldArray as $securedField) {
            $k = array_search($securedField, $fieldArray);
            if ($k !== false) {
                unset($fieldArray[$k]);
            }
        }
        $result = implode(',', $fieldArray);
        return $result;
    }

    /**
    * Changes potential malicious script code of the input to harmless HTML
    *
    * @param array $dataArray: array of key/value pairs
    * @param bool $htmlSpecial: whether to apply htmlspecialchars to the values
    * @return void
    */
    static public function secureInput (
        &$dataArray,
        $htmlSpecial = true
    )
    {
        foreach ($dataArray as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            $dataArray[$key][$key2][$key3] = self::getSecuredValue($key3, $value3, $htmlSpecial);
                        }
                    } else {
                        $dataArray[$key][$key2] = self::getSecuredValue($key2, $value2, $htmlSpecial);
                    }
                }
            } else {
                $dataArray[$key] = self::getSecuredValue($key, $value, $htmlSpecial);
            }
        }
    }

    /**
    * Changes potential malicious script code of the input to harmless HTML
    *
    * @param string $field: name of field to secure
    * @param string $value: input value
    * @param bool $htmlSpecial: whether to apply htmlspecialchars to the value
    * @return string secured value
    */
    static public function getSecuredValue (
        $field,
        $value,
        $htmlSpecial = true
    )
    {
        $securedValue = $value;
        if (
            !empty($securedValue) &&
            !in_array($field, self::getSecuredFields()
        )) {
            $securedValue = htmlspecialchars_decode($securedValue);
            if ($htmlSpecial) {
                $securedValue = htmlspecialchars($securedValue);
            }
        }
        return $securedValue;
    }

    /**
    * Writes the password to FE user session data
    *
    * @param    array   $row: data array that may contain password values
    *
    * @return void
    */
    static public function securePassword (
        $extensionKey,
        array &$row,
        &$errorMessage
    ) {
        $result = true;
        $data = [];
            // Decrypt incoming password (and eventually other encrypted fields)
        $passwordRow = ['password' => self::readPassword($extensionKey)];
        $errorCode = '';
        $errorMessage = '';
        $passwordDecrypted =
            self::getTransmissionSecurity()->decryptIncomingFields(
                $passwordRow,
                $errorCode,
                $errorMessage
            );

            // Collect secured fields
        if ($passwordDecrypted !== false) {
            self::writePassword(
                $extensionKey,
                $passwordRow['password'],
                $passwordRow['password']
            );
        } else if ($errorMessage == '') {
            self::writePassword(
                $extensionKey,
                $passwordRow['password'],
                $row['password_again'] ?? ''
            );
        } else {
            $result = false;
        }
        return $result;
    }

    /**
    * Writes the password to session data
    *
    * @param    string  $password: the password
    * @return   void
    */
    static public function writePassword (
        $extensionKey,
        $password,
        $passwordAgain = '',
        $token = '',
        $redirectUrl = ''
    ) {
        $sessionData = SessionUtility::readData($extensionKey);
        if ($password == '') {
            $sessionData['password'] = '__UNSET';
            $sessionData['password_again'] = '__UNSET';
        } else {
            $sessionData['password'] = $password;
            if ($passwordAgain != '') {
                $sessionData['password_again'] = $passwordAgain;
            }
        }
        SessionUtility::writeData(
            $extensionKey,
            $sessionData,
            true,
            true,
            $token,
            $redirectUrl
        );
    }


    /*************************************
    * PASSWORD HANDLING
    *************************************/
    /**
    * Retrieves the password from session data and encrypt it for storage
    *
    * @return   string  the encrypted password
    *           boolean false in case of an error
    */
    static public function readPasswordForStorage ($extensionKey) {
        $result = false;
        $password = self::readPassword($extensionKey);
        if ($password != '') {
            $result =
                self::getStorageSecurity()->encryptPasswordForStorage($password);
        }
        return $result;
    }

    /**
    * Retrieves the password from session data
    *
    * @return   string  the password
    */
    static public function readPassword ($extensionKey) {
        $result = '';
        $securedArray = self::readSecuredArray($extensionKey);
        if ($securedArray['password']) {
            $result = $securedArray['password'];
        }
        return $result;
    }


    /**
    * Generates a value for the password and stores it the FE user session data
    *
    * @param    array   $dataArray: incoming array
    * @return   void
    */
    static public function generatePassword (
        $extensionKey,
        $cmdKey,
        array $conf,
        array $cmdConf,
        array &$dataArray,
        &$autoLoginKey
    ) {
        // We generate an interim password in the case of an invitation
        if (
            $cmdConf['generatePassword']
        ) {
            $genLength = intval($cmdConf['generatePassword']);

            if ($genLength) {
                $generatedPassword =
                    substr(
                        md5(uniqid(microtime(), 1)),
                        0,
                        $genLength
                    );
                $dataArray['password'] = $generatedPassword;
                $dataArray['password_again'] = $generatedPassword;
                self::writePassword(
                    $extensionKey,
                    $generatedPassword,
                    $generatedPassword
                );
            }
        }

        if (
            \JambageCom\Agency\Request\Parameters::enableAutoLoginOnConfirmation(
                $conf,
                $cmdKey
            )
        ) {
            $password = self::readPassword($extensionKey);
            $cryptedPassword = '';
            $autoLoginKey = '';
            $isEncrypted =
                self::getStorageSecurity()
                    ->encryptPasswordForAutoLogin(
                        $password,
                        $cryptedPassword,
                        $autoLoginKey
                    );
            if ($isEncrypted) {
                $dataArray['tx_agency_password'] = base64_encode($cryptedPassword);
            }
        }
    }

    /*************************************
    * SECURED ARRAY HANDLING
    *************************************/
    /**
    * Retrieves values of secured fields from FE user session data
    * Used for the password
    *
    * @return   array   secured FE user session data
    */
    static public function readSecuredArray (
        $extensionKey
    ) {
        $securedArray = [];
        $sessionData = SessionUtility::readData($extensionKey);
        $securedFields = self::getSecuredFields();
        foreach ($securedFields as $securedField) {
            if (isset($sessionData[$securedField])) {
                $securedArray[$securedField] = $sessionData[$securedField];
            }
        }
        return $securedArray;
    }
} 
