<?php

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Agency\Utility\SessionUtility;


class System {

    /**
    * Perform user login and redirect to configured url, if any
    *
    * @param boolen $redirect: whether to redirect after login or not. If true, then you must immediately call exit after this call
    * @return boolean true, if login was successful, false otherwise
    */
    public function login (
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $languageObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Api\Url $url,
        $conf,
        $username,
        $cryptedPassword,
        $requiresAuthorization = true,
        $redirect = true
    )
    {
        $result = true;
        $ok = true;
        $message = '';

            // Log the user in
        $loginData = array(
            'uname' => $username,
            'uident' => $cryptedPassword,
            'uident_text' => $cryptedPassword,
            'status' => 'login',
        );

        // Check against configured pid (defaulting to current page)
        $GLOBALS['TSFE']->fe_user->checkPid = true;
        $pageIds = ($cObj->data['pages'] ? $cObj->data['pages'] . ',' : '') . $controlData->getPid();
        $GLOBALS['TSFE']->fe_user->checkPid_value =
            \JambageCom\Div2007\Utility\SystemUtility::getRecursivePids(
                $pageIds,
                $cObj->data['recursive']
            );

            // Get authentication info array
        $authInfo = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();

            // Get user info
        $user =
            $GLOBALS['TSFE']->fe_user->fetchUserRecord(
                $authInfo['db_user'],
                $loginData['uname']
            );

        if (is_array($user)) {
            if ($requiresAuthorization) {
                $ok = false;
                $serviceKeyArray = array();

                if (class_exists(\TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordService::class)) {
                    $serviceKeyArray[] = \TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordService::class;
                } else if (class_exists(\TYPO3\CMS\Saltedpasswords\SaltedPasswordService::class)) {
                    $serviceKeyArray[] = \TYPO3\CMS\Saltedpasswords\SaltedPasswordService::class;
                }

                if (
                    $conf['authServiceClass'] != '' &&
                    $conf['authServiceClass'] != '{$plugin.tx_agency.authServiceClass}' &&
                    class_exists($conf['authServiceClass'])
                ) {
                    $serviceKeyArray = array_merge($serviceKeyArray, GeneralUtility::trimExplode(',', $conf['authServiceClass']));
                }

                $serviceChain = '';
                $authServiceObj = false;

                while (
                    is_object(
                        $authServiceObj =
                            GeneralUtility::makeInstanceService(
                                'auth',
                                'authUserFE',
                                $serviceChain
                            )
                    )
                ) {
                    $serviceChain .= ',' . $authServiceObj->getServiceKey();
                    $ok = $authServiceObj->compareUident($user, $loginData);

                    if ($ok) {
                        break;
                    }
                }
            } else {
                $ok = true;
            }

            if ($ok) {
                    // Login successfull: create user session
                $GLOBALS['TSFE']->fe_user->createUserSession($user);
                    // Enforce session so we get a FE cookie. Otherwise autologin might not work (TYPO3 6.2.5+) :
                $GLOBALS['TSFE']->fe_user->setAndSaveSessionData('dummy', true);
                $GLOBALS['TSFE']->initUserGroups();
                $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
                $GLOBALS['TSFE']->loginUser = 1;
            } else if (
                is_object($authServiceObj) &&
                in_array(get_class($authServiceObj), $serviceKeyArray)
            ) {
                    // auto login failed...
                $message = $languageObj->getLabel('internal_auto_login_failed');
                $result = false;
            } else {
                    // Required authentication service not available
                $message = $languageObj->getLabel('internal_required_authentication_service_not_available');
                $result = false;
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
            $message = sprintf($languageObj->getLabel('internal_no_enabled_user'), $loginData['uname']);
            $result = false;
        }

        if ($result == false) {
            $extensionKey = $controlData->getExtensionKey();
            SessionUtility::clearData(
                $extensionKey,
                false
            );

            if ($message != '') {
                GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
            }
            $ok = false;
        }

        if (
            $ok &&
            $redirect
        ) {
                // Redirect to configured page, if any
            $redirectUrl = $controlData->readRedirectUrl();
            if (!$redirectUrl && $result == true) {
                $redirectUrl = trim($conf['autoLoginRedirect_url']);
            }

            if (!$redirectUrl) {
                if ($conf['loginPID']) {
                    $redirectUrl = $url->get('', $conf['loginPID']);
                } else {
                    $redirectUrl = $controlData->getSiteUrl();
                }
            }

            header('Location: ' . GeneralUtility::locationHeaderUrl($redirectUrl));
        }

        return $result;
    }

    public function removePasswordAdditions (
        \JambageCom\Agency\Domain\Data $dataObj,
        $theTable,
        $uid,
        $row
    )
    {
        $deleteFields = array(
            'lost_password',
            'tx_agency_password'
        );
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
}

