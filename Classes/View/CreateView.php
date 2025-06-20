<?php

declare(strict_types=1);

namespace JambageCom\Agency\View;

/***************************************************************
*  Copyright notice
*
*  (c) 2019 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Api\Javascript;
use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Constants\Mode;
use JambageCom\Agency\Domain\Tca;
use JambageCom\Agency\Domain\Data;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\SecuredData;


class CreateView
{
    /**
    * Generates the record creation form
    * or the first link display to create or edit someone's data
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $errorFieldArray: array of field with errors (former $this->data->inError[$theField])
    * @return string  the template with substituted markers
    */
    public function render(
        array $markerArray,
        $conf,
        $prefixId,
        $extensionKey,
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        Parameters $controlData,
        ConfigurationStore $confObj,
        Tca $tcaObj,
        Marker $markerObj,
        Data $dataObj,
        Template $template,
        $cmd,
        $cmdKey,
        $mode,
        $theTable,
        array $dataArray,
        array $origArray,
        array $securedArray,
        $infoFields,
        $errorFieldArray,
        $token
    ) {
        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }

        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $templateCode = $dataObj->getTemplateCode();
        $currentArray = array_merge($origArray, $dataArray);
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        if ($controlData->getUsePassword() && !isset($currentArray['password'])) {
            $currentArray['password'] = '';
        }
        if ($controlData->getUsePasswordAgain()) {
            $currentArray['password_again'] = $currentArray['password'];
        }

        if ($conf['create']) {

            // Call all beforeConfirmCreate hooks before the record has been shown and confirmed
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['registrationProcess'] as $classRef) {
                    $hookObj = GeneralUtility::makeInstance($classRef);

                    if (
                        method_exists($hookObj, 'needsInit') &&
                        method_exists($hookObj, 'init') &&
                        $hookObj->needsInit()
                    ) {
                        $hookObj->init($dataObj);
                    }

                    if (method_exists($hookObj, 'registrationProcess_beforeConfirmCreate')) {
                        $hookObj->registrationProcess_beforeConfirmCreate(
                            $theTable,
                            $dataArray,
                            $controlData,
                            $cmdKey,
                            $confObj
                        );
                    }
                }
            }
            $currentArray = array_merge($currentArray, $dataArray);
            $key = ($cmd == 'invite') ? 'INVITE' : 'CREATE';
            $bNeedUpdateJS = true;

            if ($cmd == 'create' || $cmd == 'invite') {
                $subpartKey = '###TEMPLATE_' . $key . $markerObj->getPreviewLabel() . '###';
            } else {
                $bNeedUpdateJS = false;
                if ($controlData->isLoggedIn()) {
                    $subpartKey = '###TEMPLATE_CREATE_LOGIN###';
                } else {
                    $subpartKey = '###TEMPLATE_AUTH###';
                }
            }

            $templateCode = $templateService->getSubpart($templateCode, $subpartKey);
            $failure = $parameterApi->getParameter('noWarnings') ? false : $controlData->getFailure();

            if ($failure == false) {
                $templateCode = $templateService->substituteSubpart(
                    $templateCode,
                    '###SUB_REQUIRED_FIELDS_WARNING###',
                    ''
                );
            }

            $templateCode =
                $template->removeRequired(
                    $confObj,
                    $cObj,
                    $controlData,
                    $dataObj,
                    $theTable,
                    $cmdKey,
                    $templateCode,
                    $mode == Mode::PREVIEW,
                    $errorFieldArray,
                    $failure
                );

            $markerArray =
                $markerObj->fillInMarkerArray(
                    $markerArray,
                    $currentArray,
                    $securedArray,
                    $controlData,
                    $dataObj,
                    $confObj,
                    '',
                    true
                );
            $markerObj->fillInCaptchaMarker(
                $markerArray,
                $controlData->getCaptcha()
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
                $prefixId
            );

            foreach ($GLOBALS['TCA'][$theTable]['columns'] as $theField => $fieldConfig) {
                if (
                    isset($fieldConfig['config']['internal_type']) &&
                    $fieldConfig['config']['internal_type'] == 'file' &&
                    !empty($fieldConfig['config']['allowed']) &&
                    !empty($fieldConfig['config']['uploadfolder'])
                ) {
                    $markerObj->addFileUploadMarkers(
                        $languageObj,
                        $theTable,
                        $theField,
                        $fieldConfig,
                        $markerArray,
                        $cmd,
                        $cmdKey,
                        $prefixId,
                        $dataArray,
                        $controlData->getMode() == Mode::PREVIEW
                    );
                }
            }

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
                $infoFields,
                $GLOBALS['TCA'][$theTable]['columns'],
                '',
                false
            );

            $templateCode =
                $markerObj->removeStaticInfoSubparts(
                    $templateCode,
                    $markerArray
                );
            $markerObj->addHiddenFieldsMarkers(
                $markerArray,
                $theTable,
                $extensionKey,
                $prefixId,
                $cmdKey,
                $mode,
                $token,
                $conf[$cmdKey . '.']['useEmailAsUsername'],
                $conf['enableEmailConfirmation'],
                $conf[$cmdKey . '.']['fields'],
                $dataArray
            );
            $includedFields = $confObj->getIncludedFields($cmdKey);
            $markerObj->addPrivacyPolicy(
                $markerArray,
                $prefixId,
                $theTable,
                $dataArray,
                $mode != Mode::PREVIEW &&
                    in_array('privacy_policy_acknowledged', $includedFields)
            );

            // Avoid cleartext password in HTML source
            $markerArray['###FIELD_password###'] = '';
            $markerArray['###FIELD_password_again###'] = '';
            $deleteUnusedMarkers = true;

            $content =
                $templateService->substituteMarkerArray(
                    $templateCode,
                    $markerArray,
                    '',
                    false,
                    $deleteUnusedMarkers
                );

            if (
                $mode != Mode::PREVIEW &&
                $bNeedUpdateJS
            ) {
                $fields = $dataObj->getFieldList() . ',' . $dataObj->getAdditionalUpdateFields();
                $fields = implode(
                    ',',
                    array_intersect(
                        explode(
                            ',',
                            $fields
                        ),
                        GeneralUtility::trimExplode(
                            ',',
                            $conf[$cmdKey . '.']['fields'],
                            true
                        )
                    )
                );
                $fields = SecuredData::getOpenFields($fields);
                $modData =
                    $dataObj->modifyDataArrForFormUpdate(
                        $conf,
                        $dataArray,
                        $cmdKey
                    );
                $form = $controlData->determineFormId();
                $updateJS =
                    FrontendUtility::getUpdateJS(
                        $modData,
                        $form,
                        'FE[' . $theTable . ']',
                        $fields
                    );
                $content .= $updateJS;
            }
        }

        return $content;
    } // render
}
