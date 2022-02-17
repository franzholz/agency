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

use JambageCom\Agency\Security\SecuredData;
use JambageCom\Agency\Utility\SessionUtility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;


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
    public $bValidRegHash;
    public $regHash;
    private $confObj;
        // Whether the token was found valid
    protected $isTokenValid = false;
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
    )
    {
        $fdArray = array();
        $conf = $confObj->getConf();
        if ($theTable == 'fe_users') {
            $this->initPasswordField($conf);
        }
        $this->confObj = $confObj;
        $this->setDefaultPid($conf['pid']);

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
                is_array($feUserData) &&
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
                        \JambageCom\Div2007\Utility\ControlUtility::_GETset($v, $k);
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
        SecuredData::secureInput($feUserData);

        if ($this->getUsePassword()) {
            // Establishing compatibility with the extension Felogin
            $value = $this->getFormPassword();
            if ($value !== null) {
                SecuredData::writePassword(
                    $extensionKey,
                    $value,
                    '',
                    $this->readToken(),
                    $this->readRedirectUrl()
                );
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
            SecuredData::writePassword(
                $extensionKey,
                ''
            );
        }

            // Generate a new token for the next created forms
        $token = $authObj->generateToken();
        $this->writeToken($token);
    }

    public function initCaptcha (
        $cmdKey
    )
    {
        $confObj = GeneralUtility::makeInstance(\JambageCom\Agency\Configuration\ConfigurationStore::class);
        $conf = $confObj->getConf();
        $extensionKey = $this->getExtensionKey();

        $usesCaptcha =
            GeneralUtility::inList($conf[$cmdKey . '.']['fields'], \JambageCom\Agency\Constants\Field::CAPTCHA) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha']) &&
            is_array($conf[$cmdKey . '.']) &&
            is_array($conf[$cmdKey . '.']['evalValues.']) &&
            is_object(
                $captcha = \JambageCom\Div2007\Captcha\CaptchaManager::getCaptcha(
                    $extensionKey,
                    $conf[$cmdKey . '.']['evalValues.'][\JambageCom\Agency\Constants\Field::CAPTCHA]
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
    protected function setPidTitle ($conf, $sys_language_uid)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect($sys_language_uid));
        $pidRecord = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class, $context);
        $row = $pidRecord->getPage((int) $this->getPid());
        $this->thePidTitle = trim($conf['pidTitleOverride']) ?: $row['title'];
    }

    public function getConf ()
    {
        $result = $this->confObj->getConf();
        return $result;
    }

    public function initPasswordField ($conf)
    {
        $this->usePassword = false;
        $this->usePasswordAgain = false;
        if (isset($conf['create.']['evalValues.']['password'])) {
            $this->usePassword = true;
            if (GeneralUtility::inList($conf['create.']['evalValues.']['password'], 'twice')) {
                $this->usePasswordAgain = true;
            }
        }
    }

    public function getFormPassword ()
    {
        $result = GeneralUtility::_POST('pass');
        return $result;
    }

    public function getUsePassword ()
    {
        return $this->usePassword;
    }

    public function getUsePasswordAgain ()
    {
        return $this->usePasswordAgain;
    }

    public function setDefaultPid ($pid)
    {

        $bPidIsInt = MathUtility::canBeInterpretedAsInteger($pid);
        $this->defaultPid = ($bPidIsInt ? intval($pid) : $GLOBALS['TSFE']->id);
    }

    public function getDefaultPid ()
    {
        return $this->defaultPid;
    }

    public function setRegHash ($regHash)
    {
        $this->regHash = $regHash;
    }

    public function getRegHash ()
    {
        return $this->regHash;
    }

    public function setValidRegHash ($bValidRegHash)
    {
        $this->bValidRegHash = $bValidRegHash;
    }

    public function getValidRegHash ()
    {
        return $this->bValidRegHash;
    }

    static public function enableAutoLoginOnCreate (
        array $conf
    )
    {
        $result = (
            $conf['enableAutoLoginOnCreate']
        );

        return $result;
    }

    static public function enableAutoLoginOnConfirmation (
        array $conf,
        $cmdKey = ''
    )
    {
        $result = false;

        if ($conf['enableAutoLoginOnConfirmation']) {
            $result = true;
        }

        if ($cmdKey == '' || $cmdKey == 'invite') {
            $result |= $conf['enableAutoLoginOnInviteConfirmation'];
        }

        if ($cmdKey == 'create') {
            $result &= !static::enableAutoLoginOnCreate($conf);
        }

        return $result;
    }

    /*************************************
    * TOKEN HANDLING
    *************************************/
    /**
    * Whether the token was found valid
    *
    * @return   boolean whether the token was found valid
    */
    public function isTokenValid ()
    {
        return $this->isTokenValid;
    }

    /**
    * Sets whether the token was found valid
    *
    * @return   boolean $valid: whether the token was found valid
    * @return   void
    */
    protected function setTokenValid ($valid)
    {
        $this->isTokenValid = $valid;
    }

    /**
    * Retrieves the token from FE user session data
    *
    * @return   string  token
    */
    public function readToken ()
    {
        $token = '';
        $extensionKey = $this->getExtensionKey();
        $sessionData = SessionUtility::readData($extensionKey);

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
    protected function writeToken ($token)
    {
        $extensionKey = $this->getExtensionKey();
        $sessionData = SessionUtility::readData($extensionKey);
        if ($token == '') {
            $sessionData['token'] = '__UNSET';
        } else {
            $sessionData['token'] = $token;
        }
        SessionUtility::writeData(
            $extensionKey,
            $sessionData,
            false,
            true,
            '',
            $this->readRedirectUrl()
        );
    }

    /**
    * Retrieves the redirectUrl from FE user session data
    *
    * @return   string  redirectUrl
    */
    public function readRedirectUrl ()
    {
        $redirectUrl = '';
        $extensionKey = $this->getExtensionKey();
        $sessionData = SessionUtility::readData($extensionKey);
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
    protected function writeRedirectUrl ()
    {
        $redirectUrl = GeneralUtility::_GET('redirect_url');
        if ($redirectUrl != '') {
            $data = array();
            $data['redirect_url'] = $redirectUrl;
            $extensionKey = $this->getExtensionKey();
            SessionUtility::writeData(
                $extensionKey,
                $data,
                true,
                true,
                $this->readToken(),
                $this->readRedirectUrl()
            );
        }
    }

    // example: plugin.tx_agency_pi.conf.sys_dmail_category.ALL.sys_language_uid = 0
    public function getSysLanguageUid ($conf, $theCode, $theTable)
    {

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

    public function getSiteUrl ()
    {
        return $this->site_url;
    }

    public function getPrefixId ()
    {
        return $this->prefixId;
    }

    public function setPrefixId ($prefixId)
    {
        $this->prefixId = $prefixId;
    }

    public function getExtensionKey ()
    {
        return $this->extensionKey;
    }

    public function setExtensionKey ($extensionKey)
    {
        $this->extensionKey = $extensionKey;
    }

    public function getPiVars ()
    {
        return $this->piVars;
    }

    public function setPiVars ($piVars)
    {
        $this->piVars = $piVars;
    }

    public function getCmd ()
    {
        return $this->cmd;
    }

    public function setCmd ($cmd)
    {
        $this->cmd = $cmd;
    }

    public function getCmdKey ()
    {
        return $this->cmdKey;
    }

    public function setCmdKey ($cmdKey)
    {
        $this->cmdKey = $cmdKey;
    }

    /**
     * Gets the feUserData array or an index of the array
     *
     * @param string $key: the key for which the value should be returned
     * @return mixed the value of the specified key or the full array
     */
    public function getFeUserData ($key = '')
    {
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
    public function setFeUserData ($value, $key = '')
    {
        if ($key != '') {
            $this->feUserData[$key] = $value;
        } else {
            $this->feUserData = $value;
        }
    }

    public function setCaptcha (CaptchaInterface $captcha)
    {
        $this->captcha = $captcha;
    }

    public function getCaptcha ()
    {
        return $this->captcha;
    }

    public function setFailure ($failure)
    {
        $this->failure = $failure;
    }

    public function getFailure ()
    {
        return $this->failure;
    }

    public function setSubmit ($submit)
    {
        $this->submit = $submit;
    }

    public function getSubmit ()
    {
        return $this->submit;
    }

    public function setDoNotSave ($bParam)
    {
        $this->bDoNotSave = $bParam;
    }

    public function getDoNotSave ()
    {
        return $this->bDoNotSave;
    }

    public function getPid ($type = '')
    {
        $result = false;
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

    public function setPid ($type, $pid)
    {
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

    public function getMode ()
    {
        return $this->mode;
    }

    public function setMode ($mode)
    {
        $this->mode = $mode;
    }

    public function getTable ()
    {
        return $this->theTable;
    }

    public function setTable ($theTable)
    {
        $this->theTable = $theTable;
    }

    public function getRequiredArray ()
    {
        return $this->requiredArray;
    }

    public function setRequiredArray ($requiredArray)
    {
        $this->requiredArray = $requiredArray;
    }

    public function getSetfixedEnabled ()
    {
        return $this->setfixedEnabled;
    }

    public function setSetfixedEnabled ($setfixedEnabled)
    {
        $this->setfixedEnabled = $setfixedEnabled;
    }

    public function getSetfixedOptions ()
    {
        return $this->setFixedOptions;
    }

    public function setSetfixedOptions ($setFixedOptions)
    {
        $this->setFixedOptions = $setFixedOptions;
    }

    public function getSetfixedParameters ()
    {
        return $this->setFixedParameters;
    }

    public function setSetfixedParameters ($setFixedParameters)
    {
        $this->setFixedParameters = $setFixedParameters;
    }

    public function getFd ()
    {
        return $this->fD;
    }

    public function setFd ($fD)
    {
        $this->fD = $fD;
    }
    
    public function determineFormId ($suffix = '_form')
    {
        $result = \JambageCom\Div2007\Utility\FrontendUtility::getClassName(
            $this->getTable() . $suffix,
            $this->getPrefixId()
        );
        return $result;
    }

    public function getBackURL ()
    {
        $result = rawurldecode($this->getFeUserData('backURL'));
        return $result;
    }

    public function getTokenParameter ()
    {
        $tokenParam = $this->getPrefixId() . '%5Btoken%5D=' . $this->readToken();
        $result = '&amp;'. $tokenParam;
        return $result;
    }

    /**
    * Checks if preview display is on.
    *
    * @return boolean  true if preview display is on
    */
    public function isPreview ()
    {
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
    public function getShortUrl ($regHash)
    {
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
    public function deleteShortUrl ($regHash)
    {
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
    public function cleanShortUrlCache ()
    {

        $confObj = GeneralUtility::makeInstance(\JambageCom\Agency\Configuration\ConfigurationStore::class);
        $conf = $confObj->getConf();

        $shortUrlLife = intval($conf['shortUrlLife']) ? strval(intval($conf['shortUrlLife'])) : '30';
        $max_life = time() - (86400 * intval($shortUrlLife));
        if (is_object($GLOBALS['TYPO3_DB'])) {
            $res =
                $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                    'cache_md5params',
                    'tstamp<' . $max_life . ' AND type=99'
                );
        }
    }   // cleanShortUrlCache
}

