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

use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class EditView {

    /**
    * Displays the record update form
    *
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $origArray: the array coming from the database
    * @param array  $errorFieldArray: array of field with errors (former $dataObj->inError[$theField])
    * @return string  the template with substituted markers
    */
    protected function renderForm (
        array &$markerArray,
        array $conf,
        $prefixId,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        \JambageCom\Agency\View\Template $template,
        $theTable,
        $dataArray,
        array $origArray,
        $securedArray,
        $cmd,
        $cmdKey,
        $mode,
        $errorFieldArray,
        $token
    ) {
        if (isset($dataArray) && is_array($dataArray)) {
            $currentArray = array_merge($origArray, $dataArray);
        } else {
            $currentArray = $origArray;
        }

        if ($cmdKey == 'password') {
            $subpart = '###TEMPLATE_SETFIXED_OK_APPROVE_INVITE###';
        } else {
            $subpart = '###TEMPLATE_EDIT' . $markerObj->getPreviewLabel() . '###';
        }
        $templateCode = $cObj->getSubpart($dataObj->getTemplateCode(), $subpart);

        if (
            !$conf['linkToPID'] ||
            !$conf['linkToPIDAddButton'] ||
            !($mode == MODE_PREVIEW ||
            !$conf[$cmd . '.']['preview'])
        ) {
            $templateCode =
                $cObj->substituteSubpart(
                    $templateCode,
                    '###SUB_LINKTOPID_ADD_BUTTON###',
                    ''
                );
        }
        $failure = GeneralUtility::_GP('noWarnings') ? '' : $controlData->getFailure();

        if (!$failure) {
            $templateCode =
                $cObj->substituteSubpart(
                    $templateCode,
                    '###SUB_REQUIRED_FIELDS_WARNING###',
                    ''
                );
        }
        $markerObj->addPasswordTransmissionMarkers(
            $markerArray,
            $controlData->getUsePasswordAgain()
        );
        $templateCode =
            $template->removeRequired(
                $conf,
                $cObj,
                $controlData,
                $dataObj,
                $theTable,
                $cmdKey,
                $templateCode,
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
            $langObj,
            $prefixId,
            $currentArray
        );

        $tcaObj->addTcaMarkers(
            $markerArray,
            $conf,
            $langObj,
            $controlData,
            $currentArray,
            $origArray,
            $cmd,
            $cmdKey,
            $theTable,
            $prefixId,
            true
        );

        $tcaObj->addTcaMarkers(
            $markerArray,
            $conf,
            $langObj,
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
            $langObj,
            $controlData->getExtensionKey(),
            $theTable,
            $currentArray,
            $origArray,
            $securedArray,
            array(),
            $controlData->getRequiredArray(),
            $dataObj->getFieldList(),
            $GLOBALS['TCA'][$theTable]['columns'],
            '',
            false
        );

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $theField => $fieldConfig) {

            if (
                $fieldConfig['config']['internal_type'] == 'file' &&
                $fieldConfig['config']['allowed'] != '' &&
                $fieldConfig['config']['uploadfolder'] != ''
            ) {
                $markerObj->addFileUploadMarkers(
                    $langObj,
                    $theTable,
                    $theField,
                    $fieldConfig,
                    $markerArray,
                    $cmd,
                    $cmdKey,
                    $prefixId,
                    $currentArray,
                    $controlData->getMode() == MODE_PREVIEW
                );
            }
        }

        $templateCode =
            $markerObj->removeStaticInfoSubparts(
                $templateCode,
                $markerArray
            );
        $markerArray['###HIDDENFIELDS###'] .=
            chr(10) . '<input type="hidden" name="FE[' . $theTable . '][uid]" value="' . $currentArray['uid'] . '" />';

        if ($theTable != 'fe_users') {
            $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);
            $markerArray['###HIDDENFIELDS###'] .=
                chr(10) . '<input type="hidden" name="' . $prefixId . '[aC]" value="' .
                    $authObj->generateAuthCode(
                        $origArray,
                        $conf['setfixed.']['EDIT.']['_FIELDLIST']
                    ) .
                '" />';
            $markerArray['###HIDDENFIELDS###']
                .= chr(10) . '<input type="hidden" name="' . $prefixId . '[cmd]" value="edit" />';
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
            $conf[$cmdKey . '.']['fields'],
            $currentArray
        );

            // Avoid cleartext password in HTML source
        $markerArray['###FIELD_password###'] = '';
        $markerArray['###FIELD_password_again###'] = '';
        $deleteUnusedMarkers = true;

        $content =
            $cObj->substituteMarkerArray(
                $templateCode,
                $markerArray,
                '',
                false,
                $deleteUnusedMarkers
            );

        if ($mode != MODE_PREVIEW) {
            $form =
                \tx_div2007_alpha5::getClassName_fh002(
                    $theTable . '_form',
                    $prefixId
                );
            $modData =
                $dataObj->modifyDataArrForFormUpdate(
                    $conf,
                    $currentArray,
                    $cmdKey
                );
            $fields = $dataObj->getFieldList() . ',' . $dataObj->getAdditionalUpdateFields();
            $fields = implode(',', array_intersect(explode(',', $fields), GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1)));
            $fields = $controlData->getOpenFields($fields);
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
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param array  $errorFieldArray: array of field with errors (former $this->data->inError[$theField])
    * @return string  the template with substituted markers
    */
    public function render (
        array &$markerArray,
        $conf,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        \JambageCom\Agency\View\Template $template,
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

        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }

            // If editing is enabled
        if ($conf['edit']) {
            $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);

            if(
                $theTable != 'fe_users' &&
                $conf['setfixed.']['EDIT.']['_FIELDLIST']
            ) {
                $fD = GeneralUtility::_GP('fD');
                $fieldArr = array();
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
                    $conf['setfixed.']['EDIT.']['_FIELDLIST']
                );

            if (
                is_array($origArray) &&
                (
                    ($theTable == 'fe_users' && $GLOBALS['TSFE']->loginUser) ||
                    $aCAuth ||
                    (
                        $theAuthCode != '' &&
                        !strcmp($authObj->getAuthCode(), $theAuthCode)
                    )
                )
            ) {
                $markerObj->setArray($markerArray);

                // Must be logged in OR be authenticated by the aC code in order to edit
                // If the recUid selects a record.... (no check here)
                if (
                    !strcmp($authObj->getAuthCode(), $theAuthCode) ||
                    $aCAuth ||
                    $dataObj->getCoreQuery()->DBmayFEUserEdit(
                        $theTable,
                        $origArray,
                        $GLOBALS['TSFE']->fe_user->user,
                        $conf['allowedGroups'],
                        $conf['fe_userEditSelf']
                    )
                ) {
                    // Display the form, if access granted.
                    $content = $this->renderForm(
                        $markerArray,
                        $conf,
                        $prefixId,
                        $cObj,
                        $langObj,
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
                        $conf,
                        $cObj,
                        $langObj,
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
                    $conf,
                    $cObj,
                    $langObj,
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
            $content .= $langObj->getLL('internal_edit_option');
        }

        return $content;
    }	// render
}
