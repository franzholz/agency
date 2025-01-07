<?php

declare(strict_types=1);

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

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use JambageCom\Div2007\Captcha\CaptchaInterface;
use JambageCom\Div2007\Captcha\CaptchaManager;
use JambageCom\Div2007\Utility\ControlUtility;
use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Constants\Field;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Security\SecuredData;
use JambageCom\Agency\Utility\SessionUtility;




/**
 * Request parameters
 */
class Parameters implements SingletonInterface
{
    private $confObj;
    protected $thePid = 0;
    protected $thePidTitle;
    protected $mode;
    protected $theTable;
    protected $requiredArray;
    protected $site_url;
    protected $prefixId;
    protected $piVars;
    protected $request;
    protected $frontendUser;
    protected $extensionKey;
    protected $cmd = '';
    protected $cmdKey = '';
    protected $pid = [];
    protected $defaultPid = '';
    protected $setfixedEnabled = 0;
    protected $submit = false;
    protected $bDoNotSave = false;
    protected $failure = false; // is set if data did not have the required fields set.

    protected $sys_language_content;
    protected $feUserData = [];
    protected $bValidRegHash;
    protected $regHash;
    // Whether the token was found valid
    protected $tokenValid = false;
    // support for repeated password (password_again internal field)
    protected $usePasswordAgain = false;
    protected $usePassword = false;
    protected $captcha = null;
    protected $setFixedOptions = ['DELETE', 'EDIT', 'UNSUBSCRIBE'];
    protected $setFixedParameters = ['rU', 'aC', 'cmd', 'sFK'];
    protected $fD = [];

    /**
     * @var TypoScriptFrontendController|null
     */
    protected $typoScriptFrontendController;

    protected ?Context $context = null;

    public function injectContext(Context $context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function isLoggedIn()
    {
        $context = $this->getContext();
        $result = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');

        return $result;
    }

    public function init(
        ConfigurationStore $confObj,
        ServerRequestInterface $request,
        $prefixId,
        $extensionKey,
        $piVars,
        $theTable
    ): void {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $fdArray = [];
        $conf = $confObj->getConf();
        $shortUrls = $conf['useShortUrls'] ?? false;
        $this->setRequest($request);
        $this->setFrontendUser($request->getAttribute('frontend.user'));

        if ($theTable == 'fe_users') {
            $this->initPasswordField($conf);
        }
        $this->confObj = $confObj;
        $this->setDefaultPid($conf['pid']);

        $this->site_url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $tsfe = $this->getTypoScriptFrontendController();

        if ($tsfe->absRefPrefix) {
            if(
                strpos($tsfe->absRefPrefix, 'http://') === 0 ||
                strpos($tsfe->absRefPrefix, 'https://') === 0
            ) {
                $this->site_url = $tsfe->absRefPrefix;
            } else {
                $this->site_url = $this->site_url . ltrim($tsfe->absRefPrefix, '/');
            }
        }
        $this->setPrefixId($prefixId);
        $this->setExtensionKey($extensionKey);
        $this->piVars = $piVars;
        $this->setTable($theTable);
        $authObj = GeneralUtility::makeInstance(Authentication::class);

        $this->sys_language_content = intval($tsfe->config['config']['sys_language_uid'] ?? 0);

        // set the title language overlay
        $this->setPidTitle($conf, $this->sys_language_content);

        $pidTypeArray = ['login', 'register', 'edit', 'infomail', 'confirm', 'confirmInvitation', 'password'];
        // set the pid's

        foreach ($pidTypeArray as $k => $type) {
            $this->setPid($type, $conf[$type . 'PID'] ?? '');
        }

        if (
            $conf['enableEmailConfirmation'] ||
            ($this->theTable == 'fe_users' && !empty($conf['enableAdminReview'])) ||
            $conf['setfixed']
        ) {
            $this->setSetfixedEnabled(1);
        }

        // Get hash variable if provided and if short url feature is enabled
        $feUserData = $parameterApi->getParameter($prefixId);
        $bSecureStartCmd =
            (
                is_array($feUserData) &&
                count($feUserData) == 1 &&
                isset($feUserData['cmd']) &&
                in_array($feUserData['cmd'], ['create', 'edit', 'password'])
            );
        $bValidRegHash = false;

        if ($shortUrls) {
            $regHash = false;

            // delete outdated short urls
            $this->cleanShortUrlCache();
            if (
                isset($feUserData) &&
                is_array($feUserData) &&
                isset($feUserData['regHash'])
            ) {
                $regHash = $feUserData['regHash'];
            }

            if (!$regHash) {
                $getData = $parameterApi->getGetParameter($prefixId);

                if (
                    isset($getData) &&
                    is_array($getData) &&
                    isset($getData['regHash'])
                ) {
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
                    $origDataFieldArray = ['sFK', 'cmd', 'submit', 'fetch', 'regHash', 'preview', 'token'];
                    $origFeuserData = [];
                    // copy the original values which must not be overridden by the regHash stored values
                    foreach ($origDataFieldArray as $origDataField) {
                        if (isset($feUserData[$origDataField])) {
                            $origFeuserData[$origDataField] = $feUserData[$origDataField];
                        }
                    }
                    $restoredFeUserData = $getVars[$prefixId];

                    foreach ($getVars as $k => $v) {
                        // restore former GET values for the url
                        ControlUtility::_GETset($v, $k);
                    }

                    if (
                        isset($feUserData['rU']) &&
                        $restoredFeUserData['rU'] > 0 &&
                        $restoredFeUserData['rU'] == $feUserData['rU']
                    ) {
                        $feUserData = array_merge($feUserData, $restoredFeUserData);
                    } else {
                        $feUserData = $restoredFeUserData;
                    }

                    if (is_array($feUserData)) {
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
            $value = $parameterApi->getParameter($pivar);
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
                    $this->getFrontendUser(),
                    $extensionKey,
                    $value,
                    '',
                    $this->readToken(),
                    $this->readRedirectUrl()
                );
            }
        }

        // Get the data for the uid provided in query parameters
        $bRuIsInt = MathUtility::canBeInterpretedAsInteger($feUserData['rU'] ?? '');
        if ($bRuIsInt) {
            $theUid = intval($feUserData['rU']);
            $origArray = $tsfe->sys_page->getRawRecord($theTable, $theUid);
        }

        if (
            isset($getVars) &&
            is_array($getVars) &&
            isset($getVars['fD']) &&
            is_array($getVars['fD'])
        ) {
            $fdArray = $getVars['fD'];
        } elseif (
            !isset($getVars)
        ) {
            if (isset($feUserData['fD'])) {
                $fdArray = $feUserData['fD'];
            } else {
                $fdArray = $parameterApi->getParameter('fD');
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
                (
                    !empty($feUserData['token']) &&
                    (
                        (
                            !empty($token) && $feUserData['token'] == $token
                        ) ||
                        (
                            empty($token) && $cmd == 'create'
                        )
                    )
                )
                // Allow always the creation of a new user. No session data exists in this case.
            )
        ) {
            $this->setTokenValid(true);
        } elseif (
            $bRuIsInt &&
                // When processing a setfixed link from third party extensions,
                // there might be no token and no short url regHash, but there might be an authCode
            (
                $bValidRegHash ||
                !$shortUrls ||
                ($authObj->getAuthCode($aC) && !$bSecureStartCmd)
            )
        ) {
            if (
                !empty($fdArray) &&
                !empty($origArray)
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
            $this->setFeUserData([]);
            // Erase any stored password
            SecuredData::writePassword(
                $this->getFrontendUser(),
                $extensionKey,
                ''
            );
        }

        // Generate a new token for the next created forms
        $token = $authObj->generateToken();
        $this->writeToken($token);
    }

    public function initCaptcha(
        $cmdKey
    ): void {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();
        $extensionKey = $this->getExtensionKey();

        $usesCaptcha =
            !empty($cmdKey) &&
            isset($conf[$cmdKey . '.']['fields']) &&
            GeneralUtility::inList(
                $conf[$cmdKey . '.']['fields'],
                Field::CAPTCHA
            ) &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha']) &&
            isset($conf[$cmdKey . '.']['evalValues.']) &&
            is_object(
                $captcha = CaptchaManager::getCaptcha(
                    $extensionKey,
                    $conf[$cmdKey . '.']['evalValues.'][Field::CAPTCHA]
                )
            );

        if ($usesCaptcha) {
            $this->setCaptcha($captcha);
        }
    }

    /**
     * Set the title of the page o  f the records
     *
     * @return void
     */
    protected function setPidTitle($conf, $sys_language_uid)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect($sys_language_uid));
        $pidRecord = GeneralUtility::makeInstance(PageRepository::class, $context);
        $row = $pidRecord->getPage((int) $this->getPid());
        $this->thePidTitle = trim($conf['pidTitleOverride']) ?: $row['title'];
    }

    public function getConf()
    {
        $result = $this->confObj->getConf();
        return $result;
    }

    public function initPasswordField($conf): void
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

    public function getFormPassword()
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $result = $parameterApi->getPostParameter('pass');
        return $result;
    }

    public function getUsePassword()
    {
        return $this->usePassword;
    }

    public function getUsePasswordAgain()
    {
        return $this->usePasswordAgain;
    }

    public function setDefaultPid($pid): void
    {

        $bPidIsInt = MathUtility::canBeInterpretedAsInteger($pid);
        $this->defaultPid = ($bPidIsInt ? intval($pid) : $this->getTypoScriptFrontendController()->id);
    }

    public function getDefaultPid()
    {
        return $this->defaultPid;
    }

    public function setRegHash($regHash): void
    {
        $this->regHash = $regHash;
    }

    public function getRegHash()
    {
        return $this->regHash;
    }

    public function setValidRegHash($bValidRegHash): void
    {
        $this->bValidRegHash = $bValidRegHash;
    }

    public function getValidRegHash()
    {
        return $this->bValidRegHash;
    }

    public static function enableAutoLoginOnCreate(
        array $conf
    ) {
        $result = (
            $conf['enableAutoLoginOnCreate']
        );

        return $result;
    }

    public static function enableAutoLoginOnConfirmation(
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
    public function isTokenValid()
    {
        return $this->tokenValid;
    }

    /**
    * Sets whether the token was found valid
    *
    * @return   boolean $valid: whether the token was found valid
    * @return   void
    */
    protected function setTokenValid($valid)
    {
        $this->tokenValid = $valid;
    }

    /**
    * Retrieves the token from FE user session data
    *
    * @return   string  token
    */
    public function readToken()
    {
        $token = '';
        $extensionKey = $this->getExtensionKey();
        $sessionData = SessionUtility::readData($this->getFrontendUser(), $extensionKey);

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
    protected function writeToken($token)
    {
        $extensionKey = $this->getExtensionKey();
        $sessionData = SessionUtility::readData($this->getFrontendUser(), $extensionKey);
        if ($token == '') {
            $sessionData['token'] = '__UNSET';
        } else {
            $sessionData['token'] = $token;
        }
        SessionUtility::writeData(
            $this->getFrontendUser(),
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
    public function readRedirectUrl()
    {
        $redirectUrl = '';
        $extensionKey = $this->getExtensionKey();
        $sessionData = SessionUtility::readData($this->getFrontendUser(), $extensionKey);
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
    protected function writeRedirectUrl()
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $redirectUrl = $parameterApi->getGetParameter('redirect_url');
        if ($redirectUrl != '') {
            $data = [];
            $data['redirect_url'] = $redirectUrl;
            $extensionKey = $this->getExtensionKey();
            SessionUtility::writeData(
                $this->getFrontendUser(),
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
    public function getSysLanguageUid($conf, $theCode, $theTable)
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
        ) {
            $result = $conf['conf.'][$theTable . '.'][$theCode . '.']['sys_language_uid'];
        } else {
            $result = $this->sys_language_content;
        }
        return $result;
    }

    private function setRequest ($request)
    {
        $this->request = $request;
    }

    public function getRequest ()
    {
        return $this->request;
    }

    private function setFrontendUser ($frontendUser)
    {
        $this->frontendUser = $frontendUser;
    }

    public function getFrontendUser ()
    {
        return $this->frontendUser;
    }

    public function getPidTitle()
    {
        return $this->thePidTitle;
    }

    public function getSiteUrl()
    {
        return $this->site_url;
    }

    public function getPrefixId()
    {
        return $this->prefixId;
    }

    public function setPrefixId($prefixId): void
    {
        $this->prefixId = $prefixId;
    }

    public function getExtensionKey()
    {
        return $this->extensionKey;
    }

    public function setExtensionKey($extensionKey): void
    {
        $this->extensionKey = $extensionKey;
    }

    public function getPiVars()
    {
        return $this->piVars;
    }

    public function setPiVars($piVars): void
    {
        $this->piVars = $piVars;
    }

    public function getCmd()
    {
        return $this->cmd;
    }

    public function setCmd($cmd): void
    {
        $this->cmd = $cmd;
    }

    public function getCmdKey()
    {
        return $this->cmdKey;
    }

    public function setCmdKey($cmdKey): void
    {
        $this->cmdKey = $cmdKey;
    }

    /**
     * Gets the feUserData array or an index of the array
     *
     * @param string $key: the key for which the value should be returned
     * @return mixed the value of the specified key or the full array
     */
    public function getFeUserData($key = '')
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
    public function setFeUserData($value, $key = ''): void
    {
        if ($key != '') {
            $this->feUserData[$key] = $value;
        } else {
            $this->feUserData = $value;
        }
    }

    public function setCaptcha(CaptchaInterface $captcha): void
    {
        $this->captcha = $captcha;
    }

    public function getCaptcha()
    {
        return $this->captcha;
    }

    public function setFailure($failure): void
    {
        $this->failure = $failure;
    }

    public function getFailure()
    {
        return $this->failure;
    }

    public function setSubmit($submit): void
    {
        $this->submit = $submit;
    }

    public function getSubmit()
    {
        return $this->submit;
    }

    public function setDoNotSave($bParam): void
    {
        $this->bDoNotSave = $bParam;
    }

    public function getDoNotSave()
    {
        return $this->bDoNotSave;
    }

    public function getPid($type = '')
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

    public function setPid($type, $pid): void
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
                    $pid = $this->getTypoScriptFrontendController()->id;
                    break;
            }
        }
        $this->pid[$type] = $pid;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    public function getTable()
    {
        return $this->theTable;
    }

    public function setTable($theTable): void
    {
        $this->theTable = $theTable;
    }

    public function getRequiredArray()
    {
        return $this->requiredArray;
    }

    public function setRequiredArray($requiredArray): void
    {
        $this->requiredArray = $requiredArray;
    }

    public function getSetfixedEnabled()
    {
        return $this->setfixedEnabled;
    }

    public function setSetfixedEnabled($setfixedEnabled): void
    {
        $this->setfixedEnabled = $setfixedEnabled;
    }

    public function getSetfixedOptions()
    {
        return $this->setFixedOptions;
    }

    public function setSetfixedOptions($setFixedOptions): void
    {
        $this->setFixedOptions = $setFixedOptions;
    }

    public function getSetfixedParameters()
    {
        return $this->setFixedParameters;
    }

    public function setSetfixedParameters($setFixedParameters): void
    {
        $this->setFixedParameters = $setFixedParameters;
    }

    public function getFd()
    {
        return $this->fD;
    }

    public function setFd($fD): void
    {
        $this->fD = $fD;
    }

    public function determineFormId($suffix = '_form')
    {
        $result = FrontendUtility::getClassName(
            $this->getTable() . $suffix,
            $this->getPrefixId()
        );
        return $result;
    }

    public function getBackURL()
    {
        $result = rawurldecode($this->getFeUserData('backURL'));
        return $result;
    }

    public function getTokenParameter()
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
    public function isPreview()
    {
        $result = '';
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();
        $cmdKey = $this->getCmdKey();

        $result = (!empty($cmdKey) && !empty($conf[$cmdKey . '.']['preview']) && $this->getFeUserData('preview'));
        return $result;
    }   // isPreview

    /*************************************
    * SHORT URL HANDLING
    *************************************/
    /**
    *  Get the stored variables using the hash value to access the database
    */
    public function getShortUrl($regHash)
    {
        // get the serialised array from the DB based on the passed hash value
        $varArray = [];
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
        $retArray = [];
        foreach($varArray as $key => $val) {
            $val = str_replace('%2C', ',', $val);
            $search = ['[%5D]', '[%5B]'];
            $replace = ['\']', '\'][\''];
            $newkey = "['" . preg_replace($search, $replace, $key);
            if (!preg_match('/' . preg_quote(']') . '$/', $newkey)) {
                $newkey .= "']";
            }
            eval("\$retArray" . $newkey . "='$val';");
        }
        return $retArray;
    }   // getShortUrl

    /**
    *  Get the stored variables using the hash value to access the database
    */
    public function deleteShortUrl($regHash): void
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
    public function cleanShortUrlCache(): void
    {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
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

    /**
     * @return TypoScriptFrontendController|null
     */
    public function getTypoScriptFrontendController()
    {
        return $this->typoScriptFrontendController ?: $GLOBALS['TSFE'] ?? null;
    }
}
