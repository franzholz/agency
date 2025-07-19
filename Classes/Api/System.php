<?php

declare(strict_types=1);

namespace JambageCom\Agency\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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
 * customer number functions for the FE user field cnum
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;


use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Core\Authentication\Event\LoginAttemptFailedEvent;


use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

use JambageCom\Div2007\Utility\SystemUtility;

use JambageCom\Agency\Api\Url;
use JambageCom\Agency\Authentication\AuthenticationService;
use JambageCom\Agency\Domain\Data;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Utility\SessionUtility;



class System implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?Parameters $controlData = null;

    protected UserSessionManager $userSessionManager;

    protected ?UserSession $userSession = null;

    public function __construct(Parameters $controlData)
    {
        $this->controlData = $controlData;
    }


    /**
    * Perform user login and redirect to configured url, if any
    *
    * @param boolen $redirect: whether to redirect after login or not. If true, then you must immediately call exit after this call
    * @return boolean true, if login was successful, false otherwise
    */
    public function login(
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        Url $url,
        $conf,
        $username,
        $cryptedPassword,
        $requiresAuthorization = true,
        $redirect = true
    )
    {
        $result = true;
        $ok = true;
        $authenticated = false;
        $message = '';
        $authServiceObj = null;
        $request = $this->controlData->getRequest();
        $frontendUser = $this->controlData->getFrontendUser();
        $user = [];
        $activeLogin = false;

        // Log the user in
        $loginData = [
            'uname' => $username,
            'uident' => $cryptedPassword,
            'uident_text' => $cryptedPassword,
            'status' => 'login',
        ];

        // Check against configured pid (defaulting to current page)
        $frontendUser->checkPid = true;
        $pageIds = ($cObj->data['pages'] ? $cObj->data['pages'] . ',' : '') . $this->controlData->getPid();
        $frontendUser->checkPid_value =
            SystemUtility::getRecursivePids(
                $pageIds,
                $cObj->data['recursive']
            );

        // Get authentication info array
        $authInfo = $frontendUser->getAuthInfoArray($request);

        if ($requiresAuthorization) {
            $ok = false;
            $serviceKeyArray = [];

            if (
                $conf['authServiceClass'] != '' &&
                $conf['authServiceClass'] != '{$plugin.tx_agency.authServiceClass}'
            ) {
                $keyArray = GeneralUtility::trimExplode(',', $conf['authServiceClass']);
                foreach ($keyArray as $key) {
                    if (class_exists($key)) {
                        $serviceKeyArray[] = $key;
                    }
                }
            }
            $serviceKeyArray[] = AuthenticationService::class;

            $serviceChain = '';
            $ok = false;

            while (
                !$ok &&
                is_object(
                    $authServiceObj =
                        GeneralUtility::makeInstanceService(
                            'auth',
                            'authUserFE',
                            GeneralUtility::trimExplode(',', $serviceChain, true)
                        )
                )
            ) {
                $subType = 'authUserFE';
                $isProcessed =
                    $authServiceObj->processLoginData($loginData, 'normal');
                if (!$isProcessed) {
                    continue;
                }
                $authServiceObj->initAuth($subType, $loginData, $authInfo, $frontendUser);

                // Get user info
                $user =
                    $authServiceObj->fetchUserRecord(
                        $loginData['uname']
                        // $authInfo['db_user'],
                    );

                if (
                    !empty($user) &&
                    ($ret = $authServiceObj->authUser($user)) > 0
                ) {
                    // If the service returns >=200 then no more checking is needed - useful for IP checking without password
                    if ((int)$ret >= 200) {
                        $ok = true;
                        $authenticated = true;
                    } else if ((int)$ret >= 100) {
                        // nothing
                    } else {
                        $ok = true;
                        $authenticated = true;
                    }
                } else {
                    $authenticated = false;
                    break;
                }

                if (!empty($user)) {
                    $serviceChain .= ',' . $authServiceObj->getServiceKey();
                }
            }
        } else {
            $ok = true;
        }

        if ($ok && $authenticated) {
            $this->userSessionManager = UserSessionManager::create($frontendUser->loginType);
            $this->userSession =
                $this->userSessionManager->createFromRequestOrAnonymous(
                    $request,
                    $frontendUser->getCookieName()
                );
            $isExistingSession = !$this->userSession->isNew();
            $anonymousSession = $isExistingSession && $this->userSession->isAnonymous();

            // Insert session record if needed
            if (!$isExistingSession
                || $anonymousSession
                || (int)($user[$frontendUser->userid_column] ?? 0) !== $this->userSession->getUserId()
            ) {
                $sessionData = $this->userSession->getData();
                // Create a new session with a fixated user
                $this->userSession = $frontendUser->createUserSession($user);

                // Preserve session data on login
                if ($anonymousSession || $isExistingSession) {
                    $this->userSession->overrideData($sessionData);
                }

                // The login session is started.
                $this->logger->debug('User session finally read', [
                    $frontendUser->userid_column => $user[$frontendUser->userid_column],
                    $frontendUser->username_column => $user[$frontendUser->username_column],
                ]);
                $activeLogin = true;
            } else {
                // if we come here the current session is for sure not anonymous as this is a pre-condition for $authenticated = true

            }

            if ($activeLogin && !$this->userSession->isNew()) {
                $this->regenerateSessionId();
            }

            if ($activeLogin) {
                // User logged in - write that to the log!
                if ($frontendUser->writeStdLog) {
                    $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::LOGIN, SystemLogErrorClassification::MESSAGE, 1, 'User %s logged in from ###IP###', [$userRecordCandidate[$this->username_column]], '', '', '');
                }
                $this->logger->info('User {username} logged in from {ip}', [
                    'username' => $user[$frontendUser->username_column],
                    'ip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                ]);
            } else {
                $this->logger->debug('User {username} authenticated from {ip}', [
                    'username' => $user[$frontendUser->username_column],
                    'ip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                ]);
            }
            // Check if multi-factor authentication is required
            $this->evaluateMfaRequirements();
        } else {
            $result = false;
        // Mark the current login attempt as failed
            if (empty($user) && $activeLogin) {
                $this->logger->debug('Login failed', [
                    'loginData' => $this->removeSensitiveLoginDataForLoggingInfo($loginData),
                ]);
                $message =
                    sprintf(
                        $languageObj->getLabel('internal_no_enabled_user'),
                        $loginData['uname']
                    );
            } elseif (!empty($user)) {
                $this->logger->debug('Login failed', [
                    $frontendUser->userid_column => $user[$frontendUser->userid_column],
                    $frontendUser->username_column => $user[$frontendUser->username_column],
                ]);
                $message = $languageObj->getLabel('internal_auto_login_failed');
            }

            // If there were a login failure, check to see if a warning email should be sent
            if ($activeLogin) {
                GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
                    new LoginAttemptFailedEvent($this, $request, $this->removeSensitiveLoginDataForLoggingInfo($loginData))
                );
            }
            if (
                !is_object($authServiceObj) ||
                !in_array(get_class($authServiceObj), $serviceKeyArray)
            ) {
                // Required authentication service not available
                $message = $languageObj->getLabel('internal_required_authentication_service_not_available');
                $result = false;
            }
        }

        if ($result == true) {
            $frontendUser->storeSessionData();
        } else {
            if (strlen($message)) {
                $this->logger->critical($message);
            }
            $ok = false;
        }

        if (
            $ok &&
            $redirect
        ) {
            // Redirect to configured page, if any
            $redirectUrl = $this->controlData->readRedirectUrl();
            if (!$redirectUrl && $result == true) {
                $redirectUrl = trim($conf['autoLoginRedirect_url']);
            }

            if (!$redirectUrl) {
                if ($conf['loginPID']) {
                    $redirectUrl = $url->get($conf['loginPID'], '', [], ['regHash']);
                } else {
                    $redirectUrl = $this->controlData->getSiteUrl();
                }
            }
            header('Location: ' . GeneralUtility::locationHeaderUrl($redirectUrl));
        }

        return $result;
    }

    public function removePasswordAdditions(
        Data $dataObj,
        $theTable,
        $uid,
        $row
    ): void {
        $deleteFields = [
            'lost_password',
            'tx_agency_password'
        ];
        foreach ($deleteFields as $field) {
            $row[$field] = '';
        }
        $newFieldList = implode(',', $deleteFields);

        $res = $dataObj->getCoreQuery()->DBgetUpdate(
            $theTable,
            $uid,
            $row,
            $newFieldList,
            true
        );
    }

    /**
     * This method checks if the user is authenticated but has not succeeded in
     * passing his MFA challenge. This method can therefore only be used if a user
     * has been authenticated against his first authentication method (username+password
     * or any other authentication token).
     *
     * @throws MfaRequiredException
     * @internal
     */
    protected function evaluateMfaRequirements(): void
    {
        // MFA has been validated already, nothing to do
        if ($this->getSessionData('mfa')) {
            return;
        }
        // If the user session does not contain the 'mfa' key - indicating that MFA is already
        // passed - get the first provider for authentication, which is either the default provider
        // or the first active provider (based on the providers configured ordering).
        $frontendUser = $this->controlData->getFrontendUser();
        $provider = GeneralUtility::makeInstance(MfaProviderRegistry::class)->getFirstAuthenticationAwareProvider($frontendUser);
        // Throw an exception (hopefully caught in a middleware) when an active provider for the user exists
        if ($provider !== null) {
            throw new MfaRequiredException($provider, 1708773832);
        }
    }

    /**
     * Returns the session data stored for $key.
     * The data will last only for this login session since it is stored in the user session.
     *
     * @param string $key The key associated with the session data
     * @return mixed
     */
    public function getSessionData($key)
    {
        return $this->userSession ? $this->userSession->get($key) : '';
    }

    /**
     * Set session data by key.
     * The data will last only for this login session since it is stored in the user session.
     *
     * @param string $key A non empty string to store the data under
     * @param mixed $data Data store store in session
     */
    public function setSessionData($key, $data)
    {
        $this->userSession->set($key, $data);
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     */
    protected function regenerateSessionId()
    {
        $this->userSession = $this->userSessionManager->regenerateSession($this->userSession->getIdentifier());
    }

    /**
     * Removes any sensitive data from the incoming data (either from loginData, processedLogin data
     * or the user record from the DB).
     *
     * No type hinting is added because it might be possible that the incoming data is of any other type.
     *
     * @param mixed|array $data
     * @param bool $isUserRecord
     * @return mixed
     */
    protected function removeSensitiveLoginDataForLoggingInfo($data, bool $isUserRecord = false)
    {
        if ($isUserRecord && is_array($data)) {
            $fieldNames = ['uid', 'pid', 'tstamp', 'crdate', 'deleted', 'disabled', 'starttime', 'endtime', 'username', 'admin', 'usergroup', 'db_mountpoints', 'file_mountpoints', 'file_permissions', 'workspace_perms', 'lastlogin', 'workspace_id', 'category_perms'];
            $data = array_intersect_key($data, array_combine($fieldNames, $fieldNames));
        }
        if (isset($data['uident'])) {
            $data['uident'] = '********';
        }
        if (isset($data['uident_text'])) {
            $data['uident_text'] = '********';
        }
        if (isset($data['password'])) {
            $data['password'] = '********';
        }
        return $data;
    }

}
