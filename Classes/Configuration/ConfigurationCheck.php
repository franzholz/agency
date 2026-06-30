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
