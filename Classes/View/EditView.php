<?php

declare(strict_types=1);

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
* edit display
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;

use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Constants\Mode;
use JambageCom\Agency\Domain\Tca;
use JambageCom\Agency\Domain\Data;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Security\SecuredData;



class EditView
{
    /**
    * Displays the record update form
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $origArray: the array coming from the database
    * @param array  $errorFieldArray: array of field with errors (former $dataObj->inError[$theField])
    * @return string  the template with substituted markers
    */
    protected function renderForm(
        array $markerArray,
        array $conf,
        $prefixId,
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        Parameters $controlData,
        ConfigurationStore $confObj,
        Tca $tcaObj,
        Marker $markerObj,
        Data $dataObj,
        Template $template,
        $theTable,
        array $dataArray,
        array $origArray,
        array $securedArray,
        $cmd,
        $cmdKey,
        $mode,
        $errorFieldArray,
        $token
    ) {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $xhtmlFix = HtmlUtility::determineXhtmlFix();
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        if (isset($dataArray) && is_array($dataArray)) {
            $currentArray = array_merge($origArray, $dataArray);
        } else {
            $currentArray = $origArray;
        }
        if (!isset($markerArray['###HIDDENFIELDS###'])) {
            $markerArray['###HIDDENFIELDS###'] = '';
        }

        if ($cmdKey == 'password') {
            $subpart = '###TEMPLATE_SETFIXED_OK_APPROVE_INVITE###';
        } else {
            $subpart = '###TEMPLATE_EDIT' . $markerObj->getPreviewLabel() . '###';
        }
        $templateCode = $templateService->getSubpart($dataObj->getTemplateCode(), $subpart);

        if (
            !$conf['linkToPID'] ||
            !$conf['linkToPIDAddButton'] ||
            !($mode == Mode::PREVIEW ||
            !$conf[$cmd . '.']['preview'])
        ) {
            $templateCode =
                $templateService->substituteSubpart(
                    $templateCode,
                    '###SUB_LINKTOPID_ADD_BUTTON###',
                    ''
                );
        }
        $failure = $parameterApi->getParameter('noWarnings') ? '' : $controlData->getFailure();

        if (!$failure) {
            $templateCode =
                $templateService->substituteSubpart(
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
                ($mode == Mode::PREVIEW),
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

        $markerObj->addStaticInfoMarkers(
            $markerArray,
            $languageObj,
            $prefixId,
            $currentArray
        );

        $tcaObj->addMarkers(
            $markerArray,
            $conf,
            $languageObj,
            $controlData,
            $currentArray,
            $origArray,
            $cmd,
            $cmdKey,
            $theTable,
            $prefixId,
            true
        );

        $tcaObj->addMarkers(
            $markerArray,
            $conf,
            $languageObj,
            $controlData,
            $currentArray,
            $origArray,
            $cmd,
            $cmdKey,
            $theTable,
            $prefixId,
            false
        );

        $markerObj->addLabelMarkers(
            $markerArray,
            $conf,
            $cObj,
            $languageObj,
            $controlData->getExtensionKey(),
            $theTable,
            $currentArray,
            $origArray,
            $securedArray,
            [],
            $controlData->getRequiredArray(),
            $dataObj->getFieldList(),
            $GLOBALS['TCA'][$theTable]['columns'],
            '',
            false
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
                    $currentArray,
                    $controlData->getMode() == Mode::PREVIEW
                );
            }
        }

        $templateCode =
            $markerObj->removeStaticInfoSubparts(
                $templateCode,
                $markerArray
            );
        $markerArray['###HIDDENFIELDS###'] .=
            chr(10) . '<input type="hidden" name="FE[' . $theTable . '][uid]" value="' . $currentArray['uid'] . '"' . $xhtmlFix . '>';

        if ($theTable != 'fe_users') {
            $authObj = GeneralUtility::makeInstance(Authentication::class);
            $markerArray['###HIDDENFIELDS###'] .=
                chr(10) . '<input type="hidden" name="' . $prefixId . '[aC]" value="' .
                    $authObj->generateAuthCode(
                        $origArray,
                        $conf['setfixed.']['EDIT.']['_FIELDLIST']
                    ) .
                '"' . $xhtmlFix . '>';
            $markerArray['###HIDDENFIELDS###']
                .= chr(10) . '<input type="hidden" name="' . $prefixId . '[cmd]" value="edit"' . $xhtmlFix . '>';
        }

        $markerObj->addHiddenFieldsMarkers(
            $markerArray,
            $theTable,
            $controlData->getExtensionKey(),
            $prefixId,
            $cmdKey,
            $mode,
            $token,
            $conf[$cmdKey . '.']['useEmailAsUsername'],
            $conf['enableEmailConfirmation'],
            $conf[$cmdKey . '.']['fields'],
            $currentArray
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

        if ($mode != Mode::PREVIEW) {
            $modData =
                $dataObj->modifyDataArrForFormUpdate(
                    $conf,
                    $currentArray,
                    $cmdKey
                );
            $fields = $dataObj->getFieldList() . ',' . $dataObj->getAdditionalUpdateFields();
            $fields = implode(',', array_intersect(explode(',', $fields), GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true)));
            $fields = SecuredData::getOpenFields($fields);
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

        return $content;
    }	// renderForm

    /**
    * Checks if the edit form may be displayed; if not, a link to login
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $errorFieldArray: array of field with errors (former $this->data->inError[$theField])
    * @return string  the template with substituted markers
    */
    public function render(
        array &$errorCode,
        array &$markerArray,
        $conf,
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        Parameters $controlData,
        ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        Template $template,
        $theTable,
        $prefixId,
        $dataArray,
        array $origArray,
        $securedArray,
        $cmd,
        $cmdKey,
        $mode,
        $errorFieldArray,
        $token
    ) {
        $theAuthCode = '';
        $frontendUser = $controlData->getFrontendUser();

        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }

        // If editing is enabled
        if ($conf['edit']) {
            $authObj = GeneralUtility::makeInstance(Authentication::class);
            $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

            if(
                $theTable != 'fe_users' &&
                $conf['setfixed.']['EDIT.']['_FIELDLIST']
            ) {
                $fD = $parameterApi->getParameter('fD');
                $fieldArr = [];
                if (is_array($fD)) {
                    foreach($fD as $field => $value) {
                        $origArray[$field] = rawurldecode($value);
                        $fieldArr[] = $field;
                    }
                }
                $theAuthCode =
                    $authObj->setfixedHash(
                        $origArray,
                        $origArray['_FIELDLIST']
                    );
            }

            $origArray = $dataObj->parseIncomingData($origArray);
            $aCAuth =
                $authObj->aCAuth(
                    $origArray,
                    $conf['setfixed.']['EDIT.']['_FIELDLIST'] ?? ''
                );

            if (
                is_array($origArray) &&
                (
                    ($theTable == 'fe_users' && $controlData->isLoggedIn()) ||
                    $aCAuth ||
                    (
                        $theAuthCode != '' &&
                        !strcmp($authObj->getAuthCode(), $theAuthCode)
                    )
                )
            ) {
                $markerObj->setArray($markerArray);
                $authCode = $authObj->getAuthCode();

                // Must be logged in OR be authenticated by the aC code in order to edit
                // If the recUid selects a record.... (no check here)
                if (
                    is_string($authCode) &&
                        !strcmp($authCode, $theAuthCode) ||
                    $aCAuth ||
                    $dataObj->getCoreQuery()->DBmayFEUserEdit(
                        $theTable,
                        $origArray,
                        $frontendUser->user,
                        $conf['allowedGroups'] ?? '',
                        $conf['fe_userEditSelf'] ?? false
                    )
                ) {
                    // Display the form, if access granted.
                    $content = $this->renderForm(
                        $markerArray,
                        $conf,
                        $prefixId,
                        $cObj,
                        $languageObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $dataArray,
                        $origArray,
                        $securedArray,
                        $cmd,
                        $cmdKey,
                        $mode,
                        $errorFieldArray,
                        $token
                    );
                } else {
                    // Else display error, that you could not edit that particular record...
                    $content = $template->getPlainTemplate(
                        $errorCode,
                        $conf,
                        $cObj,
                        $languageObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $dataObj->getTemplateCode(),
                        '###TEMPLATE_NO_PERMISSIONS###',
                        $markerArray,
                        $dataArray,
                        $theTable,
                        $prefixId,
                        $origArray,
                        $securedArray
                    );
                }
            } else {
                // This is if there is no login user. This must tell that you must login. Perhaps link to a page with create-user or login information.
                $content = $template->getPlainTemplate(
                    $errorCode,
                    $conf,
                    $cObj,
                    $languageObj,
                    $controlData,
                    $confObj,
                    $tcaObj,
                    $markerObj,
                    $dataObj,
                    $dataObj->getTemplateCode(),
                    '###TEMPLATE_AUTH###',
                    $markerArray,
                    $dataArray,
                    $theTable,
                    $prefixId,
                    $dataObj->getOrigArray(),
                    $securedArray
                );
            }
        } else {
            $content .= $languageObj->getLabel('internal_edit_option');
        }

        return $content;
    }	// render
}
