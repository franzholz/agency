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

use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class Template {

    /**
    * Removes required and error sub-parts when there are no errors
    *
    * Works like this:
    * - Insert subparts like this ###SUB_REQUIRED_FIELD_".$theField."### that tells that the field is required, if it's not correctly filled in.
    * - These subparts are all removed, except if the field is listed in $failure string!
    * - Subparts like ###SUB_ERROR_FIELD_".$theField."### are also removed if there is no error on the field
    * - Remove also the parts of non-included fields, using a similar scheme!
    *
    * @param array $controlData: the object of the control data
    * @param string  $templateCode: the content of the HTML template
    * @param array  $errorFieldArray: array of field with errors (former $dataObj->inError[$theField])
    * @param string  $failure: the list of fields with errors
    * @return string  the template with susbstituted parts
    */
    public function removeRequired (
        $conf,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        $dataObj,
        $theTable,
        $cmdKey,
        $templateCode,
        $errorFieldArray,
        $failure = ''
    ) {
        $requiredArray = $controlData->getRequiredArray();
        $includedFields = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], 1);
        $additionalIncludedFields = $dataObj->getAdditionalIncludedFields();
        $includedFields = array_merge($includedFields, $additionalIncludedFields);
        $includedFields = array_unique($includedFields);
        $infoFields = explode(',', $dataObj->getFieldList());

        if (!is_array($infoFields)) {
            return false;
        }
        $specialFields = array();
        $specialFieldList = $dataObj->getSpecialFieldList();

        if ($specialFieldList != '') {
            $specialFields = explode(',', $specialFieldList);
            $infoFields = array_merge($infoFields, $specialFields);
        }

        $directMailFields = array(
            'module_sys_dmail_category',
            'module_sys_dmail_newsletter',
            'module_sys_dmail_html',
        );
        $infoFields = array_merge($infoFields, $directMailFields); // add always the Direct Mail fields because its markers are present in the HTML template

        foreach ($directMailFields as $theField) {
            if (
                !is_array($GLOBALS['TCA'][$theTable]['columns'][$theField])
            ) {
                $includedFields = array_diff(
                    $includedFields,
                    array(
                        $theField
                    )
                );
            }
        }

        if (
            !$controlData->getCaptcha()
        ) {
            $templateCode =
                $cObj->substituteSubpart(
                    $templateCode,
                    '###SUB_INCLUDED_FIELD_captcha_response###',
                    ''
                );
        }

        // Honour Address List (tt_address) configuration setting
        if (
            $controlData->getTable() == 'tt_address' &&
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_address') &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address'])
        ) {
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_address']);
            if (
                is_array($extConf) &&
                $extConf['disableCombinedNameField'] == '1'
            ) {
                $templateCode =
                    $cObj->substituteSubpart(
                        $templateCode,
                        '###SUB_INCLUDED_FIELD_name###',
                        ''
                    );
            }
        }

        $infoFields = array_unique($infoFields);

        foreach ($infoFields as $k => $theField) {
            if ($theField == '') {
                continue;
            }

                // Remove field required subpart, if field is not required
            if (
                in_array(trim($theField), $requiredArray) ||
                in_array(trim($theField), $specialFields)
            ) {
                if (!GeneralUtility::inList($failure, $theField)) {
                    $templateCode =
                        $cObj->substituteSubpart(
                            $templateCode,
                            '###SUB_REQUIRED_FIELD_' . $theField . '###',
                            ''
                        );
                    $templateCode =
                        $cObj->substituteSubpart(
                            $templateCode,
                            '###SUB_ERROR_FIELD_' . $theField . '###',
                            ''
                        );
                } else if (!$errorFieldArray[$theField]) {
                    $templateCode =
                        $cObj->substituteSubpart(
                            $templateCode,
                            '###SUB_ERROR_FIELD_' . $theField . '###',
                            ''
                        );
                }
            } else {
                    // Remove field included subpart, if field is not included and is not in failure list
                if (
                    !in_array(trim($theField), $includedFields) &&
                    !GeneralUtility::inList($failure, $theField)
                ) {
                    $templateCode =
                        $cObj->substituteSubpart(
                            $templateCode,
                            '###SUB_INCLUDED_FIELD_' . $theField . '###',
                            ''
                        );
                } else {
                    $templateCode =
                        $cObj->substituteSubpart(
                            $templateCode,
                            '###SUB_REQUIRED_FIELD_' . $theField . '###',
                            ''
                        );
                    if (!GeneralUtility::inList($failure, $theField)) {
                        $templateCode =
                            $cObj->substituteSubpart(
                                $templateCode,
                                '###SUB_ERROR_FIELD_' . $theField . '###',
                                ''
                            );
                    }

                    if (
                        is_array($conf['parseValues.']) &&
                        strpos($conf['parseValues.'][$theField], 'checkArray')
                    ) {
                        $listOfCommands = GeneralUtility::trimExplode(',', $conf['parseValues.'][$theField], 1);
                        foreach($listOfCommands as $cmd) {
                            $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                            $theCmd = trim($cmdParts[0]);
                            switch($theCmd) {
                                case 'checkArray':
                                    $positions = GeneralUtility::trimExplode(';', $cmdParts[1]);
                                    for($i = 0; $i < 10; $i++) {
                                        if(!in_array($i, $positions)) {
                                            $templateCode =
                                                $cObj->substituteSubpart(
                                                    $templateCode,
                                                    '###SUB_INCLUDED_FIELD_' . $theField . '_' . $i . '###',
                                                    ''
                                                );
                                        }
                                    }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $templateCode;
    }

    /**
    * Removes HTML comments contained in input content string
    *
    * @param string $content: the input content
    * @return string the input content with HTML comment removed
    */
    public function removeHTMLComments ($content) {
        $result = preg_replace('/<!(?:--[\s\S]*?--\s*)?>[\t\v\n\r\f]*/', '', $content);
        return $result;
    }

    /**
    * Replaces HTML br tags with line feeds in input content string
    *
    * @param string $content: the input content
    * @return string the input content with HTML br tags replaced
    */
    public function replaceHTMLBr ($content) {
        $result = preg_replace('/<br\s?\/?>/', LF, $content);
        return $result;
    }

    /**
    * Removes all HTML tags from input content string
    *
    * @param string $content: the input content
    * @return string the input content with HTML tags removed
    */
    public function removeHtmlTags ($content) {
            // Preserve <http://...> constructs
        $result = str_replace('<http', '###http', $content);
        $result = strip_tags($result);
        $result = str_replace('###http', '<http', $result);
        return $result;
    }

    /**
    * Removes superfluous line feeds from input content string
    *
    * @param string $content: the input content
    * @return string the input content with superfluous fine feeds removed
    */
    public function removeSuperfluousLineFeeds ($content) {
        $result = preg_replace('/[' . preg_quote(LF) . ']{3,}/', LF . LF, $content);
        return $result;
    }

    /**
    * Initializes a template, filling values for data and labels
    *
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param string  $subpartMarker: the template subpart marker
    * @param array  $row: the data array or empty array
    * @return string  the template with substituted parts and markers
    */
    public function getPlainTemplate (
        $conf,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $tcaObj,
        $markerObj,
        $dataObj,
        $templateCode,
        $subpartMarker,
        $markerArray,
        array $origArray,
        $theTable,
        $prefixId,
        array $row,
        $securedArray,
        $bCheckEmpty = true,
        $failure = ''
    ) {
        if (
            !is_array($GLOBALS['TCA'][$theTable]) ||
            !is_array($GLOBALS['TCA'][$theTable]['columns'])
        ) {
            return false;
        }

        $cmdKey = $controlData->getCmdKey();
        $templateCode = $cObj->getSubpart($templateCode, $subpartMarker);

        if ($templateCode != '') {
                // Remove non-included fields
            $templateCode =
                $this->removeRequired(
                    $conf,
                    $cObj,
                    $controlData,
                    $dataObj,
                    $theTable,
                    $cmdKey,
                    $templateCode,
                    explode(',', $failure),
                    $failure
                );

            $markerArray =
                $markerObj->fillInMarkerArray(
                    $markerArray,
                    $row,
                    $securedArray,
                    $controlData,
                    $dataObj,
                    $confObj,
                    ''
                );

            $markerObj->addStaticInfoMarkers(
                $markerArray,
                $langObj,
                $prefixId,
                $row
            );

            $cmd = $controlData->getCmd();
            $theTable = $controlData->getTable();
            $tcaObj->addTcaMarkers(
                $markerArray,
                $conf,
                $langObj,
                $controlData,
                $row,
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
                $langObj,
                $controlData->getExtensionKey(),
                $theTable,
                $row,
                $origArray,
                $securedArray,
                array(),
                $controlData->getRequiredArray(),
                $dataObj->getFieldList(),
                $GLOBALS['TCA'][$theTable]['columns'],
                '',
                false
            );

            $templateCode =
                $markerObj->removeStaticInfoSubparts(
                    $templateCode,
                    $markerArray
                );

                // Avoid cleartext password in HTML source
            $markerArray['###FIELD_password###'] = '';
            $markerArray['###FIELD_password_again###'] = '';
            $deleteUnusedMarkers = true;

            $result =
                $cObj->substituteMarkerArray(
                    $templateCode,
                    $markerArray,
                    '',
                    false,
                    $deleteUnusedMarkers
                );
        } else if ($bCheckEmpty) {
            $errorText = $langObj->getLL('internal_no_subtemplate');
            $result = sprintf($errorText, $subpartMarker);
        }

        return $result;
    }	// getPlainTemplate

    /**
    * Initializes a template, filling values for data and labels
    *
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param string  $subpartMarker: the template subpart marker
    * @param array  $row: the data array or empty array
    * @return string  the template with substituted parts and markers
    */
    public function getSimpleTemplate (
        $conf,
        $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        $markerObj,
        $templateCode,
        $subpartMarker,
        array $markerArray,
        $bCheckEmpty = true
    ) {
        $templateCode = $cObj->getSubpart($templateCode, $subpartMarker);

        if ($templateCode != '') {
            $markerObj->addOtherLabelMarkers(
                $markerArray,
                $cObj,
                $langObj,
                $conf
            );

            $deleteUnusedMarkers = true;

            $result =
                $cObj->substituteMarkerArray(
                    $templateCode,
                    $markerArray,
                    '',
                    false,
                    $deleteUnusedMarkers
                );
        } else if ($bCheckEmpty) {
            $errorText = $langObj->getLL('internal_no_subtemplate');
            $result = sprintf($errorText, $subpartMarker);
        }

        return $result;
    }   // getSimpleTemplate

    /**
    * Determine which template subpart should be used atfer the last save operation
    *
    * @param string $cmd: the cmd that was executed
    * @param string $cmdKey: the command key that was use
    * @param boolean $bCustomerConfirmsMode =
    * 			($cmd == '' || $cmd == 'create') &&
    *			($cmdKey != 'edit') &&
    *			$conf['enableAdminReview'] &&
    *			($conf['enableEmailConfirmation'] || $conf['infomail'])
    * @param boolean $bSetfixed =
    *				This is the case where the user or admin has to confirm
    *			$conf['enableEmailConfirmation'] ||
    *			($this->theTable == 'fe_users' && $conf['enableAdminReview']) ||
    *			$conf['setfixed']
    * @param boolean $bCreateReview =
    *				This is the case where the user does not have to confirm, but has to wait for admin review
    *				This applies only on create ($bDefaultMode) and to fe_users
    *				$bCreateReview implies $bSetfixed
    *			!$conf['enableEmailConfirmation'] &&
    *			$conf['enableAdminReview']
    * @return boolean or string
    */
    public function getKeyAfterSave (
        $cmd,
        $cmdKey,
        $bCustomerConfirmsMode,
        $bSetfixed,
        $bCreateReview
    ) {
        $result = false;
        switch ($cmd) {
            case 'delete':
                $result = 'DELETE' . SAVED_SUFFIX;
                break;
            case 'edit':
            case 'password':
                $result = 'EDIT' . SAVED_SUFFIX;
                break;
            case 'invite':
                $result = SETFIXED_PREFIX . 'INVITE';
                break;
            case 'create':
            default:
                if ($cmdKey == 'edit') {
                    $result = 'EDIT' . SAVED_SUFFIX;
                } else if ($bSetfixed) {
                    $result = SETFIXED_PREFIX . 'CREATE';

                    if ($bCreateReview) {
                        $result = 'CREATE' . SAVED_SUFFIX . '_REVIEW';
                    } else if ($bCustomerConfirmsMode) {
                        $result .= '_REVIEW';
                    }
                } else {
                    $result = 'CREATE' . SAVED_SUFFIX;
                }
                break;
        }
        return $result;
    }
}
