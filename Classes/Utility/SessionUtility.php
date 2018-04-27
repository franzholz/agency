<?php

namespace JambageCom\Agency\Utility;

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
* language functions
*
* @author	Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/


class SessionUtility {
    /*************************************
    * FE USER SESSION DATA HANDLING
    *************************************/
    /**
    * Retrieves session data
    *
    * @param    boolean $readAll: whether to retrieve all session data or only data for this extension key
    * @return   array   session data
    */
    public function readData ($extensionKey, $readAll = false)
    {
        $sessionData = array();
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
    public function writeData (
        $extensionKey,
        array $data,
        $keepToken = true,
        $keepRedirectUrl = true,
        $token = '', // $this->readToken();
        $redirectUrl = '' // $this->readRedirectUrl()
    )
    {
        $clearSession = empty($data);
        if (
            $keepToken &&
            !isset($data['token']) &&
            $token != ''
        ) {
            $data['token'] = $token;
        }

        if (
            $keepRedirectUrl &&
            !isset($data['redirect_url']) &&
            $redirectUrl != ''
        ) {
            $data['redirect_url'] = $redirectUrl;
        }

            // Read all session data
        $allSessionData = self::readData(true);

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
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
                $allSessionData[$extensionKey],
                $data
            );
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
    public function clearData (
        $extensionKey,
        $keepRedirectUrl = true,
        $token = '',
        $redirectUrl = '' 
    )
    {
        $data = array();
        self::writeData(
            $extensionKey,
            $data,
            true,
            $keepRedirectUrl,
            $token,
            $redirectUrl
        );
    }
}

