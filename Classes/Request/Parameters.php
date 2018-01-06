<?php
namespace JambageCom\Agency\Request;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
*  (c) 2012 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
 * control data store functions. former class tx_agency_controldata
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\Div2007\Captcha\CaptchaInterface;


/**
 * Request parameters
 */
class Parameters
{
    public $thePid = 0;
    public $thePidTitle;
    public $theTable;
    public $site_url;
    public $prefixId;
    public $piVars;
    public $extensionKey;
    public $cmd = '';
    public $cmdKey = '';
    public $pid = array();
    public $defaultPid = '';
    public $setfixedEnabled = 0;
    public $submit = false;
    public $bDoNotSave = false;
    public $failure = false; // is set if data did not have the required fields set.
    public $sys_language_content;
    public $feUserData = array();
        // Names of secured fields
    static protected $securedFieldArray = array('password', 'password_again', 'tx_agency_password');
    public $bValidRegHash;
    public $regHash;
    private $confObj;
        // Whether the token was found valid
    protected $isTokenValid = false;
        // Transmission security object
    protected $transmissionSecurity;
        // Storage security object
    protected $storageSecurity;
        // support for repeated password (password_again internal field)
    protected $usePasswordAgain = false;
    protected $usePassword = false;
    protected $captcha = null;
    protected $setFixedOptions = array('DELETE', 'EDIT', 'UNSUBSCRIBE');
    protected $setFixedParameters = array('rU', 'aC', 'cmd', 'sFK');
    protected $fD = array();


    public function init (
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $prefixId,
        $extensionKey,
        $piVars,
        $theTable
    ) {
        $fdArray = array();
        $conf = $confObj->getConf();
        if ($theTable == 'fe_users') {
            $this->initPasswordField($conf);
        }
        $this->confObj = $confObj;
        $this->setDefaultPid($conf);

        $this->site_url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

        if ($GLOBALS['TSFE']->absRefPrefix) {
            if(
                strpos($GLOBALS['TSFE']->absRefPrefix, 'http://') === 0 ||
                strpos($GLOBALS['TSFE']->absRefPrefix, 'https://') === 0
            ) {
                $this->site_url = $GLOBALS['TSFE']->absRefPrefix;
            } else {
                $this->site_url = $this->site_url . ltrim($GLOBALS['TSFE']->absRefPrefix, '/');
            }
        }
        $this->setPrefixId($prefixId);
        $this->setExtensionKey($extensionKey);
        $this->piVars = $piVars;
        $this->setTable($theTable);
        $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);

        $this->sys_language_content = intval($GLOBALS['TSFE']->config['config']['sys_language_uid']);

            // set the title language overlay
        $this->setPidTitle($conf, $this->sys_language_content);

        $pidTypeArray = array('login', 'register', 'edit', 'infomail', 'confirm', 'confirmInvitation', 'password');
        // set the pid's

        foreach ($pidTypeArray as $k => $type) {
            $this->setPid($type, $conf[$type . 'PID']);
        }

        if (
            $conf['enableEmailConfirmation'] ||
            ($this->theTable == 'fe_users' && $conf['enableAdminReview']) ||
            $conf['setfixed']
        ) {
            $this->setSetfixedEnabled(1);
        }

            // Get hash variable if provided and if short url feature is enabled
        $feUserData = GeneralUtility::_GP($prefixId);
        $bSecureStartCmd =
            (
                count($feUserData) == 1 &&
                in_array($feUserData['cmd'], array('create', 'edit', 'password'))
            );
        $bValidRegHash = false;

        if ($conf['useShortUrls']) {
            $this->cleanShortUrlCache();
            if (isset($feUserData) && is_array($feUserData)) {
                $regHash = $feUserData['regHash'];
            }

            if (!$regHash) {
                $getData = GeneralUtility::_GET($prefixId);

                if (isset($getData) && is_array($getData)) {
                    $regHash = $getData['regHash'];
                }
            }

                // Check and process for short URL if the regHash GET parameter exists
            if ($regHash) {
                $getVars = $this->getShortUrl($regHash);

                if (
                    isset($getVars) &&
                    is_array($getVars) &&
                    count($getVars)
                ) {
                    $bValidRegHash = true;
                    $origDataFieldArray = array('sFK', 'cmd', 'submit', 'fetch', 'regHash', 'preview', 'token');
                    $origFeuserData = array();
                    // copy the original values which must not be overridden by the regHash stored values
                    foreach ($origDataFieldArray as $origDataField) {
                        if (isset($feUserData[$origDataField])) {
                            $origFeuserData[$origDataField] = $feUserData[$origDataField];
                        }
                    }
                    $restoredFeUserData = $getVars[$prefixId];

                    foreach ($getVars as $k => $v ) {
                        // restore former GET values for the url
                        GeneralUtility::_GETset($v, $k);
                    }

                    if (
                        $restoredFeUserData['rU'] > 0 &&
                        $restoredFeUserData['rU'] == $feUserData['rU']
                    ) {
                        $feUserData = array_merge($feUserData, $restoredFeUserData);
                    } else {
                        $feUserData = $restoredFeUserData;
                    }

                    if (isset($feUserData) && is_array($feUserData)) {
                        $setFixedCmd = '';

                        if (isset($origFeuserData['cmd'])) {
                            $feUserData['cmdKey'] = $origFeuserData['cmd'];
                            $setFixedCmd = $feUserData['cmd'];
                        }
                        $feUserData = array_merge($feUserData, $origFeuserData);
                        if ($setFixedCmd != '') {
                            $feUserData['cmd'] = $setFixedCmd;
                        }
                    } else {
                        $feUserData = $origFeuserData;
                    }

                    $this->setRegHash($regHash);
                }
            }
        }

        if (isset($feUserData) && is_array($feUserData)) {
            $this->setFeUserData($feUserData);
        }

            // Establishing compatibility with the extension Direct Mail
        $piVarArray = $this->getSetfixedParameters();

        foreach ($piVarArray as $pivar) {
            $value = htmlspecialchars(GeneralUtility::_GP($pivar));
            if ($value != '') {
                $this->setFeUserData($value, $pivar);
            }
        }

        $aC = $this->getFeUserData('aC');
        $authObj->setAuthCode($aC);

            // Query variable &prefixId[cmd] overrides query variable &cmd, if not empty
        if (
            isset($feUserData) &&
            is_array($feUserData) &&
            isset($feUserData['cmd']) &&
            $feUserData['cmd'] != ''
        ) {
            $value = htmlspecialchars($feUserData['cmd']);
            $this->setFeUserData($value, 'cmd');
        }
        $cmd = $this->getFeUserData('cmd');
        if ($cmd) {
            $this->setCmd($cmd);
        }

            // Cleanup input values
        $feUserData = $this->getFeUserData();
        $this->secureInput($feUserData);

        if ($this->getUsePassword()) {
            // Establishing compatibility with the extension Felogin
            $value = $this->getFormPassword();
            if ($value != '') {
                $this->writePassword($value, '');
            }
        }

            // Get the data for the uid provided in query parameters
        $bRuIsInt = MathUtility::canBeInterpretedAsInteger($feUserData['rU']);
        if ($bRuIsInt) {
            $theUid = intval($feUserData['rU']);
            $origArray = $GLOBALS['TSFE']->sys_page->getRawRecord($theTable, $theUid);
        }

        if (
            isset($getVars) &&
            is_array($getVars) &&
            isset($getVars['fD']) &&
            is_array($getVars['fD'])
        ) {
            $fdArray = $getVars['fD'];
        } else if (
            !isset($getVars)
        ) {
            if (isset($feUserData['fD'])) {
                $fdArray = $feUserData['fD'];
            } else {
                $fdArray = GeneralUtility::_GP('fD');
            }
        }
        $this->setFd($fdArray);

            // Get the token
        $token = '';

        if (
            $cmd == 'setfixed'
        ) {
            if (
                isset($origArray) &&
                is_array($origArray) &&
                $origArray['token'] != ''
            ) {
                    // Use the token from the FE user data
                $token = $origArray['token'];
            }
        } else {
                // Get the latest token from the session data
            $token = $this->readToken();
        }

        if (
            is_array($feUserData) &&
            (
                !count($feUserData) ||
                $bSecureStartCmd ||
                ($token != '' && $feUserData['token'] == $token) ||
                ($token == '' && $feUserData['token'] != '' && $cmd == 'create') // Allow always the creation of a new user. No session data exists in this case.
            )
        ) {
            $this->setTokenValid(true);
        } else if (
            $bRuIsInt &&
                // When processing a setfixed link from other extensions,
                // there might no token and no short url regHash, but there might be an authCode
            (
                $bValidRegHash ||
                !$conf['useShortUrls'] ||
                ($authObj->getAuthCode($aC) && !$bSecureStartCmd)
            )
        ) {
            if (
                isset($fdArray) &&
                is_array($fdArray) &&
                isset($origArray) &&
                is_array($origArray)
            ) {
                    // Calculate the setfixed hash from incoming data
                $fieldList = rawurldecode($fdArray['_FIELDLIST']);
                $setFixedArray = array_merge($origArray, $fdArray);
                $codeLength = strlen($authObj->getAuthCode());
                $sFK = $this->getFeUserData('sFK');

                    // Let's try with a code length of 8 in case this link is coming from direct mail
                if (
                    $codeLength == 8 &&
                    in_array($sFK, $this->getSetfixedOptions())
                ) {
                    $authCode = $authObj->setfixedHash($setFixedArray, $fieldList, $codeLength);
                } else {
                    $authCode = $authObj->setfixedHash($setFixedArray, $fieldList);
                }

                if (!strcmp($authObj->getAuthCode(), $authCode)) {
                        // We use the valid authCode in place of token
                    $this->setFeUserData($authCode, 'token');
                    $this->setTokenValid(true);
                }
            }
        }

        if ($this->isTokenValid()) {
            $this->setValidRegHash($bValidRegHash);
            $this->setFeUserData($feUserData);
            $this->writeRedirectUrl();
        } else {
                // Erase all FE user data when the token is not valid
            $this->setFeUserData(array());
                // Erase any stored password
            $this->writePassword('', '');
        }

            // Generate a new token for the next created forms
        $token = $authObj->generateToken();
        $this->writeToken($token);
    }

    public function initCaptcha (
        $cmdKey
    ) {
        $confObj = GeneralUtility::makeInstance(\JambageCom\Agency\Configuration\ConfigurationStore::class);
        $conf = $confObj->getConf();
        $extensionKey = $this->getExtensionKey();

        $usesCaptcha =
            GeneralUtility::inList($conf[$cmdKey . '.']['fields'], 'captcha_response') &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha']) &&
            is_array($conf[$cmdKey . '.']) &&
            is_array($conf[$cmdKey . '.']['evalValues.']) &&
            is_object(
                $captcha = \JambageCom\Div2007\Captcha\CaptchaManager::getCaptcha(
                    $extensionKey,
                    $conf[$cmdKey . '.']['evalValues.']['captcha_response']
                )
            );

        if ($usesCaptcha) {
            $this->setCaptcha($captcha);
        }
    }

    /**
     * Set the title of the page of the records
     *
     * @return void
     */
    protected function setPidTitle ($conf, $sys_language_uid) {
        $pidRecord = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $pidRecord->init(0);
        $pidRecord->sys_language_uid = (int) $sys_language_uid;
        $row = $pidRecord->getPage((int) $this->getPid());
        $this->thePidTitle = trim($conf['pidTitleOverride']) ?: $row['title'];
    }


    public function getConf () {
        $result = $this->confObj->getConf();
        return $result;
    }


    public function initPasswordField ($conf) {
        $this->usePassword = false;
        $this->usePasswordAgain = false;
        if (isset($conf['create.']['evalValues.']['password'])) {
            $this->usePassword = true;
            if (GeneralUtility::inList($conf['create.']['evalValues.']['password'], 'twice')) {
                $this->usePasswordAgain = true;
            }
        }
    }


    public function getFormPassword () {
        $result = GeneralUtility::_POST('pass');
        return $result;
    }


    public function getUsePassword () {
        return $this->usePassword;
    }


    public function getUsePasswordAgain () {
        return $this->usePasswordAgain;
    }


    public function setDefaultPid ($conf) {

        $bPidIsInt = MathUtility::canBeInterpretedAsInteger($conf['pid']);
        $this->defaultPid = ($bPidIsInt ? intval($conf['pid']) : $GLOBALS['TSFE']->id);
    }


    public function getDefaultPid () {
        return $this->defaultPid;
    }


    public function setRegHash ($regHash) {
        $this->regHash = $regHash;
    }


    public function getRegHash () {
        return $this->regHash;
    }


    public function setValidRegHash ($bValidRegHash) {
        $this->bValidRegHash = $bValidRegHash;
    }


    public function getValidRegHash () {
        return $this->bValidRegHash;
    }


    static public function enableAutoLoginOnCreate (
        array $conf
    ) {
        $result = (
            $conf['enableAutoLoginOnCreate']
        );

        return $result;
    }


    static public function enableAutoLoginOnConfirmation (
        array $conf,
        $cmdKey = ''
    ) {
        $result = false;

        if ($conf['enableAutoLoginOnConfirmation']) {
            $result = true;
        }

        if ($cmdKey == '' || $cmdKey == 'invite') {
            $result |= $conf['enableAutoLoginOnInviteConfirmation'];
        }

        if ($cmdKey == 'create') {
            $result &= !self::enableAutoLoginOnCreate($conf);
        }

        return $result;
    }


    /**
     * Gets the transmission security object
     *
     * @return tx_agency_transmission_security the transmission security object
     */
    public function getTransmissionSecurity () {
        if (!is_object($this->transmissionSecurity)) {
            /* tx_agency_transmission_security */
            $this->transmissionSecurity = GeneralUtility::makeInstance(\JambageCom\Agency\Security\TransmissionSecurity::class);
        }
        return $this->transmissionSecurity;
    }


    /**
     * Gets the storage security object
     *
     * @return tx_agency_transmission_security the storage security object
     */
    public function getStorageSecurity () {
        if (!is_object($this->storageSecurity)) {
            /* tx_agency_storage_security */
            $this->storageSecurity = GeneralUtility::makeInstance(\JambageCom\Agency\Security\StorageSecurity::class);
        }
        return $this->storageSecurity;
    }


    /* reduces to the field list which are allowed to be shown */
    public function getOpenFields ($fields) {
        $securedFieldArray = self::getSecuredFieldArray();
        $newFieldArray = array();
        $fieldArray = GeneralUtility::trimExplode(',', $fields);
        array_unique($fieldArray);

        foreach ($securedFieldArray as $securedField) {
            $k = array_search($securedField, $fieldArray);
            if ($k !== false)   {
                unset($fieldArray[$k]);
            }
        }
        $fields = implode(',', $fieldArray);
        return $fields;
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
    public function readSecuredArray () {
        $securedArray = array();
        $sessionData = $this->readSessionData();
        $securedFieldArray = self::getSecuredFieldArray();
        foreach ($securedFieldArray as $securedField) {
            if (isset($sessionData[$securedField])) {
                $securedArray[$securedField] = $sessionData[$securedField];
            }
        }
        return $securedArray;
    }


    /**
    * Gets the array of names of secured fields
    *
    * @return   array   names of secured fields
    */
    static public function getSecuredFieldArray () {
        return self::$securedFieldArray;
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
    public function readPasswordForStorage () {
        $result = false;
        $password = $this->readPassword();
        if ($password) {
            $result = $this->getStorageSecurity()->encryptPasswordForStorage($password);
        }
        return $result;
    }


    /**
    * Retrieves the password from session data
    *
    * @return   string  the password
    */
    public function readPassword () {
        $result = '';
        $securedArray = $this->readSecuredArray();
        if ($securedArray['password']) {
            $result = $securedArray['password'];
        }
        return $result;
    }


    /**
    * Writes the password to FE user session data
    *
    * @param    array   $row: data array that may contain password values
    *
    * @return void
    */
    public function securePassword (array &$row) {
        $result = true;

        $data = array();
            // Decrypt incoming password (and eventually other encrypted fields)
        $passwordRow = array('password' => $this->readPassword());
        $message = '';
        $passwordDecrypted = $this->getTransmissionSecurity()->decryptIncomingFields($passwordRow, $message);
            // Collect secured fields

        if ($passwordDecrypted) {
            $this->writePassword($passwordRow['password'], $passwordRow['password']);
        } else if ($message == '') {
            $this->writePassword($passwordRow['password'], $row['password_again']);
        } else {
            $result = false;
        }
        return $result;
    }


    /**
    * Generates a value for the password and stores it the FE user session data
    *
    * @param    array   $dataArray: incoming array
    * @return   void
    */
    public function generatePassword (
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
                $this->writePassword($generatedPassword, $generatedPassword);
            }
        }

        if (
            self::enableAutoLoginOnConfirmation($conf, $cmdKey)
        ) {
            $password = $this->readPassword();
            $cryptedPassword = '';
            $autoLoginKey = '';
            $isEncrypted =
                $this->getStorageSecurity()
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


    /**
    * Writes the password to session data
    *
    * @param    string  $password: the password
    * @return   void
    */
    protected function writePassword (
        $password,
        $passwordAgain = ''
    ) {
        $sessionData = $this->readSessionData();
        if ($password == '') {
            $sessionData['password'] = '__UNSET';
            $sessionData['password_again'] = '__UNSET';
        } else {
            $sessionData['password'] = $password;
            if ($passwordAgain != '') {
                $sessionData['password_again'] = $passwordAgain;
            }
        }
        $this->writeSessionData($sessionData);
    }

    /*************************************
    * TOKEN HANDLING
    *************************************/
    /**
    * Whether the token was found valid
    *
    * @return   boolean whether the token was found valid
    */
    public function isTokenValid () {
        return $this->isTokenValid;
    }


    /**
    * Sets whether the token was found valid
    *
    * @return   boolean $valid: whether the token was found valid
    * @return   void
    */
    protected function setTokenValid ($valid) {
        $this->isTokenValid = $valid;
    }


    /**
    * Retrieves the token from FE user session data
    *
    * @return   string  token
    */
    public function readToken () {
        $token = '';
        $sessionData = $this->readSessionData();

        if (isset($sessionData['token'])) {
            $token = $sessionData['token'];
        }

        return $token;
    }


    /**
    * Writes the token to FE user session data
    *
    * @param    string  token
    * @return void
    */
    protected function writeToken ($token) {
        $sessionData = $this->readSessionData();
        if ($token == '') {
            $sessionData['token'] = '__UNSET';
        } else {
            $sessionData['token'] = $token;
        }
        $this->writeSessionData($sessionData, false);
    }


    /**
    * Retrieves the redirectUrl from FE user session data
    *
    * @return   string  redirectUrl
    */
    public function readRedirectUrl () {
        $redirectUrl = '';
        $sessionData = $this->readSessionData();
        if (isset($sessionData['redirect_url'])) {
            $redirectUrl = $sessionData['redirect_url'];
        }
        return $redirectUrl;
    }


    /**
    * Writes the redirectUrl to FE user session data
    *
    * @return void
    */
    protected function writeRedirectUrl () {
        $redirectUrl = GeneralUtility::_GET('redirect_url');
        if ($redirectUrl != '') {
            $data = array();
            $data['redirect_url'] = $redirectUrl;
            $this->writeSessionData($data);
        }
    }


    /*************************************
    * FE USER SESSION DATA HANDLING
    *************************************/
    /**
    * Retrieves session data
    *
    * @param    boolean $readAll: whether to retrieve all session data or only data for this extension key
    * @return   array   session data
    */
    public function readSessionData ($readAll = false) {
        $sessionData = array();
        $extensionKey = $this->getExtensionKey();
        $allSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'feuser');

        if (
            isset($allSessionData) &&
            is_array($allSessionData)
        ) {
            if ($readAll) {
                $sessionData = $allSessionData;
            } else if (isset($allSessionData[$extensionKey])) {
                $sessionData = $allSessionData[$extensionKey];
            }
        }
        return $sessionData;
    }


    /**
    * Writes data to FE user session data
    *
    * @param    array   $data: the data to be written to FE user session data
    * @param    boolean $keepToken: whether to keep any token
    * @param    boolean $keepRedirectUrl: whether to keep any redirectUrl
    * @return   array   session data
    */
    public function writeSessionData (
        array $data,
        $keepToken = true,
        $keepRedirectUrl = true
    ) {
        $clearSession = empty($data);
        if ($keepToken && !isset($data['token'])) {
            $token = $this->readToken();
            if ($token != '') {
                $data['token'] = $token;
            }
        }

        if ($keepRedirectUrl && !isset($data['redirect_url'])) {
            $redirect_url = $this->readRedirectUrl();
            if ($redirect_url != '') {
                $data['redirect_url'] = $redirect_url;
            }
        }
        $extensionKey = $this->getExtensionKey();
            // Read all session data
        $allSessionData = $this->readSessionData(true);

        if (
            isset($allSessionData[$extensionKey]) &&
            is_array($allSessionData[$extensionKey])
        ) {
            $keys = array_keys($allSessionData[$extensionKey]);
            if ($clearSession) {
                foreach ($keys as $key) {
                    unset($allSessionData[$extensionKey][$key]);
                }
            }
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($allSessionData[$extensionKey], $data);
        } else {
            $allSessionData[$extensionKey] = $data;
        }

        $GLOBALS['TSFE']->fe_user->setKey('ses', 'feuser', $allSessionData);
            // The feuser session data shall not get lost when coming back from external scripts
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }


    /**
    * Deletes all session data except the token and possibly the redirectUrl
    *
    * @param    boolean $keepRedirectUrl: whether to keep any redirectUrl
    * @return   void
    */
    public function clearSessionData ($keepRedirectUrl = true) {
        $data = array();
        $this->writeSessionData($data, true, $keepRedirectUrl);
    }


    /**
    * Changes potential malicious script code of the input to harmless HTML
    *
    * @return void
    */
    static public function getSecuredValue (
        $field,
        $value,
        $bHtmlSpecial
    ) {
        $securedFieldArray = self::getSecuredFieldArray();

        if (
            $field != '' &&
            in_array($field, $securedFieldArray)
        ) {
            // nothing for password and password_again
        } else {
            $value = htmlspecialchars_decode($value);
            if ($bHtmlSpecial) {
                $value = htmlspecialchars($value);
            }
        }
        $result = $value;
        return $result;
    }


    /**
    * Changes potential malicious script code of the input to harmless HTML
    *
    * @return void
    */
    static public function secureInput (
        &$dataArray,
        $bHtmlSpecial = true
    ) {
        if (isset($dataArray) && is_array($dataArray)) {
            foreach ($dataArray as $k => $value) {
                if (is_array($value)) {
                    foreach ($value as $k2 => $value2) {
                        if (is_array($value2)) {
                            foreach ($value2 as $k3 => $value3) {
                                $dataArray[$k][$k2][$k3] = self::getSecuredValue($k3, $value3, $bHtmlSpecial);
                            }
                        } else {
                            $dataArray[$k][$k2] = self::getSecuredValue($k2, $value2, $bHtmlSpecial);
                        }
                    }
                } else {
                    $dataArray[$k] = self::getSecuredValue($k, $value, $bHtmlSpecial);
                }
            }
        }
    }


    // example: plugin.tx_agency_pi.conf.sys_dmail_category.ALL.sys_language_uid = 0
    public function getSysLanguageUid ($conf, $theCode, $theTable) {

        if (
            is_array($conf) &&
            isset($conf['conf.']) &&
            is_array($conf['conf.']) &&
            isset($conf['conf.'][$theTable . '.']) &&
            is_array($conf['conf.'][$theTable . '.']) &&
            isset($conf['conf.'][$theTable . '.'][$theCode . '.']) &&
            is_array($conf['conf.'][$theTable . '.'][$theCode . '.']) &&
            MathUtility::canBeInterpretedAsInteger($conf['conf.'][$theTable . '.'][$theCode . '.']['sys_language_uid'])
        )   {
            $result = $conf['conf.'][$theTable . '.'][$theCode . '.']['sys_language_uid'];
        } else {
            $result = $this->sys_language_content;
        }
        return $result;
    }


    public function getPidTitle () {
        return $this->thePidTitle;
    }


    public function getSiteUrl () {
        return $this->site_url;
    }


    public function getPrefixId () {
        return $this->prefixId;
    }


    public function setPrefixId ($prefixId) {
        $this->prefixId = $prefixId;
    }


    public function getExtensionKey () {
        return $this->extensionKey;
    }


    public function setExtensionKey ($extensionKey) {
        $this->extensionKey = $extensionKey;
    }


    public function getPiVars () {
        return $this->piVars;
    }


    public function setPiVars ($piVars) {
        $this->piVars = $piVars;
    }


    public function getCmd () {
        return $this->cmd;
    }


    public function setCmd ($cmd) {
        $this->cmd = $cmd;
    }


    public function getCmdKey () {
        return $this->cmdKey;
    }


    public function setCmdKey ($cmdKey) {
        $this->cmdKey = $cmdKey;
    }


    /**
     * Gets the feUserData array or an index of the array
     *
     * @param string $key: the key for which the value should be returned
     * @return mixed the value of the specified key or the full array
     */
    public function getFeUserData ($key = '') {
        $result = false;

        if (
            $key != ''
        ) {
            if (isset($this->feUserData[$key])) {
                $result = $this->feUserData[$key];
            }
        } else {
            $result = $this->feUserData;
        }
        return $result;
    }


    /**
     * Sets the feUserData array or an index of the array
     *
     * @param mixed $value: the value to be assigned
     * @param string $key: the key for which the value should be set
     * @return void
     */
    public function setFeUserData ($value, $key = '') {
        if ($key != '') {
            $this->feUserData[$key] = $value;
        } else {
            $this->feUserData = $value;
        }
    }

    public function setCaptcha (CaptchaInterface $captcha) {
        $this->captcha = $captcha;
    }

    public function getCaptcha () {
        return $this->captcha;
    }

    public function setFailure ($failure) {
        $this->failure = $failure;
    }

    public function getFailure () {
        return $this->failure;
    }

    public function setSubmit ($submit) {
        $this->submit = $submit;
    }


    public function getSubmit () {
        return $this->submit;
    }


    public function setDoNotSave ($bParam) {
        $this->bDoNotSave = $bParam;
    }


    public function getDoNotSave () {
        return $this->bDoNotSave;
    }


    public function getPid ($type = '') {

        if ($type) {
            if (isset($this->pid[$type])) {
                $result = $this->pid[$type];
            }
        }

        if (!$result) {
            $result = $this->getDefaultPid();
        }
        return $result;
    }


    public function setPid ($type, $pid) {
        if (!intval($pid)) {
            switch ($type) {
                case 'infomail':
                case 'confirm':
                    $pid = $this->getPid('register');
                    break;
                case 'confirmInvitation':
                    $pid = $this->getPid('confirm');
                    break;
                case 'password':
                    $pid = $this->getPid('password');
                    break;
                default:
                    $pid = $GLOBALS['TSFE']->id;
                    break;
            }
        }
        $this->pid[$type] = $pid;
    }


    public function getMode () {
        return $this->mode;
    }


    public function setMode ($mode) {
        $this->mode = $mode;
    }


    public function getTable () {
        return $this->theTable;
    }


    public function setTable ($theTable) {
        $this->theTable = $theTable;
    }


    public function getRequiredArray () {
        return $this->requiredArray;
    }


    public function setRequiredArray ($requiredArray) {
        $this->requiredArray = $requiredArray;
    }


    public function getSetfixedEnabled () {
        return $this->setfixedEnabled;
    }


    public function setSetfixedEnabled ($setfixedEnabled) {
        $this->setfixedEnabled = $setfixedEnabled;
    }


    public function getSetfixedOptions () {
        return $this->setFixedOptions;
    }

    public function setSetfixedOptions ($setFixedOptions) {
        $this->setFixedOptions = $setFixedOptions;
    }

    public function getSetfixedParameters () {
        return $this->setFixedParameters;
    }

    public function setSetfixedParameters ($setFixedParameters) {
        $this->setFixedParameters = $setFixedParameters;
    }

    public function getFd () {
        return $this->fD;
    }

    public function setFd ($fD) {
        $this->fD = $fD;
    }

    public function getBackURL () {
        $result = rawurldecode($this->getFeUserData('backURL'));
        return $result;
    }

    public function getTokenParameter () {
        $tokenParam = $this->getPrefixId() . '%5Btoken%5D=' . $this->readToken();
        $result = '&amp;'. $tokenParam;
        return $result;
    }

    /**
    * Checks if preview display is on.
    *
    * @return boolean  true if preview display is on
    */
    public function isPreview () {
        $result = '';
        $confObj = GeneralUtility::makeInstance(\JambageCom\Agency\Configuration\ConfigurationStore::class);
        $conf = $confObj->getConf();
        $cmdKey = $this->getCmdKey();

        $result = ($conf[$cmdKey . '.']['preview'] && $this->getFeUserData('preview'));
        return $result;
    }   // isPreview


    /*************************************
    * SHORT URL HANDLING
    *************************************/
    /**
    *  Get the stored variables using the hash value to access the database
    */
    public function getShortUrl ($regHash) {
            // get the serialised array from the DB based on the passed hash value
        $varArray = array();
        $res =
            $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'params',
                'cache_md5params',
                'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
                    $regHash,
                    'cache_md5params'
                )
            );

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $varArray = unserialize($row['params']);
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

            // convert the array to one that will be properly incorporated into the GET global array.
        $retArray = array();
        foreach($varArray as $key => $val) {
            $val = str_replace('%2C', ',', $val);
            $search = array('[%5D]', '[%5B]');
            $replace = array('\']', '\'][\'');
            $newkey = "['" . preg_replace($search, $replace, $key);
            if (!preg_match('/' . preg_quote(']') . '$/', $newkey)){
                $newkey .= "']";
            }
            eval("\$retArray" . $newkey . "='$val';");
        }
        return $retArray;
    }   // getShortUrl


    /**
    *  Get the stored variables using the hash value to access the database
    */
    public function deleteShortUrl ($regHash) {
        if ($regHash != '') {
            // get the serialised array from the DB based on the passed hash value
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                'cache_md5params',
                'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($regHash, 'cache_md5params')
            );
        }
    }


    /**
    *  Clears obsolete hashes used for short url's
    */
    public function cleanShortUrlCache () {

        $confObj = GeneralUtility::makeInstance(\JambageCom\Agency\Configuration\ConfigurationStore::class);
        $conf = $confObj->getConf();

        $shortUrlLife = intval($conf['shortUrlLife']) ? strval(intval($conf['shortUrlLife'])) : '30';
        $max_life = time() - (86400 * intval($shortUrlLife));
        $res =
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                'cache_md5params',
                'tstamp<' . $max_life . ' AND type=99'
            );
    }   // cleanShortUrlCache
}

