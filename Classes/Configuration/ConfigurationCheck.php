<?php

declare(strict_types=1);

namespace JambageCom\Agency\Configuration;

/***************************************************************
*  Copyright notice
*
*  (c) 2022 Stanislas Rolland (typo3(arobas)sjbr.ca)
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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Constants\Extension;
use JambageCom\Agency\Utility\LocalizationUtility;
use JambageCom\Agency\View\Marker;

class ConfigurationCheck implements LoggerAwareInterface
{
    use LoggerAwareTrait;


    /* Checks requirements for this plugin
    *
    * @return string Error message, if error found, empty string otherwise
    */
    public function checkRequirements(
        $conf,
        $extensionKey
    ) {
        $content = '';
        $requiredExtensions = [];
        $requiredExtensions[] = 'div2007';
        $requiredExtensions[] = 'mail';
        $requiredExtensions[] = 'rdct';

        // Check if all required extensions are available
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['depends'])) {
            $requiredExtensions =
                array_diff(
                    array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['depends']),
                    ['php', 'typo3']
                );
        }

        foreach ($requiredExtensions as $extension) {
            if (!ExtensionManagementUtility::isLoaded($extension)) {
                $message = sprintf(LocalizationUtility::translate('internal_required_extension_missing'), $extension);

                $this->logger->critical($message);
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
                    $this->logger->critical($message);
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
    public function checkSecuritySettings($extensionKey)
    {
        $content = '';
        if ($extensionKey == Extension::KEY) {
            // Check if salted passwords are enabled in front end
            if (
                class_exists(SaltedPasswordsUtility::class)
            ) {
                if (
                    !SaltedPasswordsUtility::isUsageEnabled('FE')
                ) {
                    $message = LocalizationUtility::translate('internal_salted_passwords_disabled');
                    $this->logger->critical($message);
                    $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
                } else {
                    // Check if we can get a salting instance
                    $objSalt = PasswordHashFactory::getSaltingInstance(null);

                    if (!is_object($objSalt)) {
                        // Could not get a salting instance from saltedpasswords
                        $message = LocalizationUtility::translate('internal_salted_passwords_no_instance');
                        $this->logger->critical($message);
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
    public function checkDeprecatedMarkers(
        ServerRequestInterface $request,
        array $conf,
        $extensionKey
    ) {
        $content = '';
        $templateCode = FrontendUtility::fileResource($conf['templateFile'], '', false);
        $messages =
            Marker::checkDeprecatedMarkers(
                $request,
                $templateCode,
                $extensionKey,
                $conf['templateFile']
            );

        foreach ($messages as $message) {
            $this->logger->warning($message);
            $content .= sprintf(LocalizationUtility::translate('internal_check_requirements_frontend'), $message);
        }

        return $content;
    }
}
