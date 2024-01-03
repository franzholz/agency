<?php

namespace JambageCom\Agency\View;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
* display functions
*
* @author	Kasper Skaarhoj <kasper2010@typo3.com>
* @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author	Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Domain\Tca;
use JambageCom\Agency\Domain\Data;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use JambageCom\Agency\Security\SecuredData;
use JambageCom\Agency\Setfixed\SetfixedUrls;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class AfterSaveView {

    /**
    * Displaying the page here that says, the record has been saved.
    * You're able to include the saved values by markers.
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param string $theTable: the table in use
    * @param array $autoLoginKey: the auto-login key
    * @param string  $subpartMarker: the template subpart marker
    * @param array  $row: the data array, if any
    * @param array  $errorFieldArray: array of field with errors (former $this->data->inError[$theField])
    * @return string  the template with substituted parts and markers
    */
    public function render (
        $conf,
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        Parameters $controlData,
        ConfigurationStore $confObj,
        Tca $tcaObj,
        Marker $markerObj,
        Data $dataObj,
        Template $template,
        $theTable,
        $extensionKey,
        $autoLoginKey,
        $prefixId,
        $dataArray,
        array $origArray,
        $securedArray,
        $cmd,
        $cmdKey,
        $key,
        $templateCode,
        &$markerArray,
        $errorFieldArray,
        &$content
    )
    {
        $useAdditionalFields = true;
        $errorContent = '';
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

            // Display confirmation message
        $subpartMarker = '###TEMPLATE_' . $key . '###';
        $templateCode = $templateService->getSubpart($templateCode, $subpartMarker);

        if ($templateCode) {
                // Remove non-included fields
            $templateCode =
                $template->removeRequired(
                    $confObj,
                    $cObj,
                    $controlData,
                    $dataObj,
                    $theTable,
                    $cmdKey,
                    $templateCode,
                    $useAdditionalFields,
                    $errorFieldArray
                );
            $markerArray =
                $markerObj->fillInMarkerArray(
                    $markerArray,
                    $dataArray,
                    $securedArray,
                    $controlData,
                    $dataObj,
                    $confObj,
                    '',
                    true,
                    'FIELD_',
                    true
                );
            $markerObj->addStaticInfoMarkers(
                $markerArray,
                $languageObj,
                $prefixId,
                $dataArray
            );

            $tcaObj->addMarkers(
                $markerArray,
                $conf,
                $languageObj,
                $controlData,
                $dataArray,
                $origArray,
                $cmd,
                $cmdKey,
                $theTable,
                $prefixId,
                true
            );

            $markerObj->addLabelMarkers(
                $markerArray,
                $conf,
                $cObj,
                $languageObj,
                $controlData->getExtensionKey(),
                $theTable,
                $dataArray,
                $origArray,
                $securedArray,
                [],
                $controlData->getRequiredArray(),
                $dataObj->getFieldList(),
                $GLOBALS['TCA'][$theTable]['columns'],
                '',
                false
            );

            if (
                $cmdKey == 'create' &&
                $controlData->getTable() == 'fe_users' &&
                !$conf['enableEmailConfirmation'] &&
                !$controlData->enableAutoLoginOnCreate($conf)
            ) {
                SecuredData::getTransmissionSecurity()
                    ->getMarkers(
                        $markerArray,
                        $controlData->getExtensionKey(),
                        $controlData->getUsePasswordAgain()
                    );
            }

            if (
                isset($conf[$cmdKey . '.']['marker.']) &&
                isset($conf[$cmdKey . '.']['marker.']['computeUrl']) &&
                $conf[$cmdKey . '.']['marker.']['computeUrl'] == '1'
            ) {
                SetfixedUrls::compute(
                    $cmd,
                    $prefixId,
                    $cObj,
                    $controlData,
                    $markerArray,
                    $conf['setfixed.'],
                    $dataArray,
                    $theTable,
                    $extensionKey,
                    $conf['useShortUrls'],
                    $conf['edit.']['setfixed'],
                    $autoLoginKey,
                    $conf['confirmType']
                );
            }

            $uppercase = false;
            $deleteUnusedMarkers = true;
            $content = $templateService->substituteMarkerArray(
                $templateCode,
                $markerArray,
                '',
                $uppercase,
                $deleteUnusedMarkers
            );
        } else {
            $errorText = $languageObj->getLabel('internal_no_subtemplate');
            $errorContent = sprintf($errorText, $subpartMarker);
        }
        return $errorContent;
    }
}

