<?php

declare(strict_types=1);

namespace JambageCom\Agency\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2003 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2017 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
* @author   Kasper Skårhøj <kasperYYYY@typo3.com>
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
* @maintainer   Franz Holzinger <franz@ttproducts.de>
*
*
*/

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Compatibility\AbstractPlugin;

use JambageCom\Agency\Constants\Extension;
use JambageCom\Agency\Configuration\ConfigurationCheck;
use JambageCom\Agency\Utility\LocalizationUtility;


class RegisterPluginController extends AbstractPlugin
{
    // Plugin initialization variables
    public $prefixId = Extension::KEY;
    public $scriptRelPath = 'Classes/Controller/RegisterPluginController.php'; // Path to this script relative to the extension dir.
    public $extKey = Extension::KEY;		// Extension key.
    protected ?Context $context = null;

    public function main(
        $content,
        $conf,
        ServerRequestInterface $request
    ) {
        $this->conf = $conf;
        LocalizationUtility::init();
        $configurationCheck = GeneralUtility::makeInstance(ConfigurationCheck::class);

        // Check installation requirements
        $content =
            $configurationCheck->checkRequirements(
                $conf,
                $this->extKey
            );

        // Check installation security settings
        $content .=
            $configurationCheck->checkSecuritySettings(
                $this->extKey
            );

        // Check presence of deprecated markers
        $content .=
            $configurationCheck->checkDeprecatedMarkers(
                $request,
                $conf,
                $this->extKey
            );

        $theTable = '';
        // The table must be configured
        if (
            isset($conf['table.']) &&
            is_array($conf['table.']) &&
            $conf['table.']['name']
        ) {
            $theTable  = $conf['table.']['name'];
        }

        // Check presence of configured table in TCA
        if (
            $theTable == '' ||
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            $errorText = LocalizationUtility::translate('internal_table_without_TCA');
            $content = sprintf($errorText, $theTable);
        }

        // If no error content, proceed
        if ($content == '') {
            $mainObj = GeneralUtility::makeInstance(InitializationController::class);
            $content =
                $mainObj->main(
                    $this,
                    $request,
                    $this->cObj,
                    $content,
                    $conf,
                    'fe_users'
                );
        }

        return $content;
    }
}
