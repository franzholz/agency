<?php

declare(strict_types=1);

namespace JambageCom\Agency\Security;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
* Part of the agency (Agency Registration) extension. former class tx_agency_auth
*
* authentication functions
*
* @package TYPO3
* @subpackage agency
*
*
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

use JambageCom\Div2007\Security\StorageSecurity;

use JambageCom\Agency\Request\Parameters;
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
    protected static $securedFields = ['password', 'password_again', 'tx_agency_password'];

    /**
    * Gets the array of names of secured fields
    *
    * @return array names of secured fields
    */
    public static function getSecuredFields()
    {
        return self::$securedFields;
    }

    /**
    * Reduces the list of fields to the fields that are allowed to be shown
    *
    * @param array $fields: initial list of field names
    * @return array new list of field names
    */
    public static function getOpenFields($fields)
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
    public static function secureInput(
        &$dataArray,
        $htmlSpecial = true
    ): void {
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
    public static function getSecuredValue(
        $field,
        $value,
        $htmlSpecial = true
    ) {
        $securedValue = $value;
        if (
            !empty($securedValue) &&
            !in_array(
                $field,
                self::getSecuredFields()
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
    public static function securePassword(
        FrontendUserAuthentication $frontendUser,
        $extensionKey,
        array &$row,
        &$errorMessage
    ) {
        $result = true;
        $data = [];
        // Decrypt incoming password (and eventually other encrypted fields)
        $passwordRow =
            ['password' => self::readPassword($frontendUser, $extensionKey)];
        $errorCode = '';
        $errorMessage = '';

        if ($errorMessage == '') {
            self::writePassword(
                $frontendUser,
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
    public static function writePassword(
        FrontendUserAuthentication $frontendUser,
        $extensionKey,
        $password,
        $passwordAgain = '',
        $token = '',
        $redirectUrl = ''
    ): void {
        $sessionData = SessionUtility::readData($frontendUser, $extensionKey);
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
            $frontendUser,
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
    public static function readPasswordForStorage(
        FrontendUserAuthentication $frontendUser,
        $extensionKey
    )
    {
        $password = self::readPassword($frontendUser, $extensionKey);

        if ($password != '') {
            $password =
            self::getStorageSecurity()->encryptPasswordForStorage($password);
        }

        return $password;
    }

    /**
    * Retrieves the password from session data
    *
    * @return   string  the password
    */
    public static function readPassword(
        FrontendUserAuthentication $frontendUser,
        $extensionKey
    )
    {
        $result = '';
        $securedArray = self::readSecuredArray($frontendUser, $extensionKey);
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
    public static function generatePassword(
        FrontendUserAuthentication $frontendUser,
        $extensionKey,
        $cmdKey,
        array $conf,
        array $cmdConf,
        array &$dataArray,
        &$autoLoginKey
    ): void {
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
                    $frontendUser,
                    $extensionKey,
                    $generatedPassword,
                    $generatedPassword
                );
            }
        }

        if (
            Parameters::enableAutoLoginOnConfirmation(
                $conf,
                $cmdKey
            )
        ) {
            $password = self::readPassword($frontendUser, $extensionKey);
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
                $dataArray['tx_agency_password'] = base64_encode($password);
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
    public static function readSecuredArray(
        FrontendUserAuthentication $frontendUser,
        $extensionKey
    ) {
        $securedArray = [];
        $sessionData = SessionUtility::readData($frontendUser, $extensionKey);
        $securedFields = self::getSecuredFields();
        foreach ($securedFields as $securedField) {
            if (isset($sessionData[$securedField])) {
                $securedArray[$securedField] = $sessionData[$securedField];
            }
        }
        return $securedArray;
    }

    /**
     * Gets the storage security object
     *
     * @return tx_agency_transmission_security the storage security object
     */
    public static function getStorageSecurity()
    {
        $result = GeneralUtility::makeInstance(
            StorageSecurity::class
        );
        return $result;
    }
}
