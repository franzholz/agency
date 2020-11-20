<?php

namespace JambageCom\Agency\Configuration;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Stanislas Rolland (typo3(arobas)sjbr.ca)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
*
* Part of the agency (Agency Registration) extension.
*
* Check the configuration and extension requirements
*
* @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author	Franz Holzinger <franz@ttproducts.de>
* @maintainer	Franz Holzinger <franz@ttproducts.de>
*
*
*/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\Backend\BackendFactory;

use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Utility\LocalizationUtility;

class ConfigurationCheck {

    /* Checks requirements for this plugin
    *
    * @return string Error message, if error found, empty string otherwise
    */
    static public function checkRequirements (
        $conf,
        $extensionKey
    )
    {
        $content = '';
        $requiredExtensions = array();
        $loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];
        if (
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $requiredExtensions[] = 'typo3db_legacy';
        }

            // Check if all required extensions are available
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['depends'])) {
            $requiredExtensions =
                array_diff(
                    array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['depends']),
                    array('php', 'typo3')
                );
        }

        if (
            $loginSecurityLevel == 'rsa' ||
            (
                \JambageCom\Agency\Request\Parameters::enableAutoLoginOnConfirmation($conf, '')
            )
        ) {
            $requiredExtensions[] = 'rsaauth';
        }

        foreach ($requiredExtensions as $extension) {
            if (!ExtensionManagementUtility::isLoaded($extension)) {
                $message = sprintf(LocalizationUtility::translate('internal_required_extension_missing'), $extension);

                GeneralUtility::sysLog(
                    $message,
                    $extensionKey,
                    GeneralUtility::SYSLOG_SEVERITY_ERROR
                );

                $content .=
                    sprintf(
                        LocalizationUtility::translate('internal_check_requirements_frontend'),
                        $message
                    );
            }
        }

            // Check if any conflicting extension is available
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['conflicts'])) {
            $conflictingExtensions =
                array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['conflicts']);
        }

        if (
            isset($conflictingExtensions) &&
            is_array($conflictingExtensions)
        ) {
            foreach ($conflictingExtensions as $extension) {
                if (ExtensionManagementUtility::isLoaded($extension)) {
                    $message = sprintf(LocalizationUtility::translate('internal_conflicting_extension_installed'), $extension);
                    GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                    $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
                }
            }
        }

        return $content;
    }

    /**
    * Checks security settings
    *
    * @param string $extensionKey the extension key
    * @return string Error message, if error found, empty string otherwise
    */
    static public function checkSecuritySettings ($extensionKey)
    {
        $content = '';
        if ($extensionKey == 'agency') {
            $loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];

            // Check if front end login security level is correctly set
            $supportedTransmissionSecurityLevels = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['loginSecurityLevels'];

            if (
                $loginSecurityLevel != '' &&
                !in_array(
                    $loginSecurityLevel,
                    $supportedTransmissionSecurityLevels
                )
            ) {
                $message = LocalizationUtility::translate('internal_login_security_level');
                GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
            } else {
                    // Check if salted passwords are enabled in front end
                if (                
                    class_exists(\TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility::class) ||
                    class_exists(\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::class)
                ) {
                    if (
                        class_exists(\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::class) &&
                        !\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('FE')
                    ) {
                        $message = LocalizationUtility::translate('internal_salted_passwords_disabled');
                        GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                        $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
                    } else {
                        $objSalt = null;
                        if (version_compare(TYPO3_version, '9.5.0', '>=')) {
                                // Check if we can get a salting instance
                            $objSalt = \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::getSaltingInstance(null);
                        } else {
                            $objSalt = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null);
                        }

                        if (!is_object($objSalt)) {
                                // Could not get a salting instance from saltedpasswords
                            $message = LocalizationUtility::translate('internal_salted_passwords_no_instance');
                            GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                            $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
                        }
                    }
                }

                    // Check if we can get a backend from rsaauth
                if (ExtensionManagementUtility::isLoaded('rsaauth')) {
                    $backend = BackendFactory::getBackend();
                    $storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
                    if (
                        !is_object($backend) ||
                        !$backend->isAvailable() ||
                        !is_object($storage)
                    ) {
                            // Required RSA auth backend not available
                        $message = LocalizationUtility::translate('internal_rsaauth_backend_not_available');
                        GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                        $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
                    }
                }
            }
        }

        return $content;
    }

    /* Checks whether the HTML templates contains any deprecated marker
    *
    * @return string Error message, if error found, empty string otherwise
    */
    static public function checkDeprecatedMarkers (
        $cObj,
        array $conf,
        $extensionKey
    )
    {
        $content = '';
        $templateCode = FrontendUtility::fileResource($conf['templateFile']);
        $messages =
            \JambageCom\Agency\View\Marker::checkDeprecatedMarkers(
                $templateCode,
                $extensionKey,
                $conf['templateFile']
            );

        foreach ($messages as $message) {
            GeneralUtility::sysLog($message, $extensionKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
            $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
        }

        return $content;
    }
}

