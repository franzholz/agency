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
* marker functions. former class tx_agency_marker
*
* @author	Kasper Skaarhoj <kasper2007@typo3.com>
* @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author	Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

use JambageCom\Div2007\Api\Css;
use JambageCom\Div2007\Captcha\CaptchaInterface;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;

use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Api\Url;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Constants\Extension;
use JambageCom\Agency\Constants\Mode;
use JambageCom\Agency\Domain\Data;
use JambageCom\Agency\Domain\Tca;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Security\SecuredData;


define('SAVED_SUFFIX', '_SAVED');
define('SETFIXED_PREFIX', 'SETFIXED_');


class Marker
{
    public $conf = [];
    public $data;
    public $control;
    public $controlData;
    public $tca;
    public $previewLabel;
    public $staticInfoObj;
    public $markerArray = [];
    public $buttonLabelsList;
    public $otherLabelsList;
    public $dataArray; // temporary array of data
    private $urlMarkerArray;
    private $thePidTitle;
    private $tmpTcaMarkers;

    public function init(
        ConfigurationStore $confObj,
        Data $data,
        Tca $tcaObj,
        Parameters $controlData,
        $backUrl,
        $extKey,
        $prefixId,
        $theTable,
        Url $urlObj,
        $staticInfoObj,
        $uid,
        $token
    ): void {
        $this->conf = $confObj->getConf();
        $this->data = $data;
        $this->tca = $tcaObj;
        $this->controlData = $controlData;
        $this->staticInfoObj = $staticInfoObj;
        $this->thePidTitle = $controlData->getPidTitle();

        $markerArray = [];

        $charset = 'utf-8';
        $markerArray['###CHARSET###'] = $charset;
        $markerArray['###PREFIXID###'] = $prefixId;
        $markerArray['###FORM_NAME###'] = $controlData->determineFormId();

        // Setting URL, HIDDENFIELDS and signature markers
        $urlMarkerArray =
            $this->generateURLMarkers(
                $urlObj,
                $backUrl,
                $uid,
                $token,
                $theTable,
                $extKey,
                $prefixId
            );

        $this->setUrlMarkerArray($urlMarkerArray);
        $markerArray = array_merge($markerArray, $urlMarkerArray);
        $this->setArray($markerArray);

        // Button labels
        $buttonLabelsList = 'register,confirm_register,back_to_form,update,confirm_update,enter,confirm_delete,cancel_delete,confirm_refuse,cancel_refuse,confirm_approve,cancel_approve,update_and_more,password_enter_new';

        $this->setButtonLabelsList($buttonLabelsList);

        $otherLabelsList = 'yes,no,new_password,password_again,tooltip_password_again,tooltip_invitation_password_again,click_here_to_register,tooltip_click_here_to_register,click_here_to_edit,tooltip_click_here_to_edit,click_here_to_delete,tooltip_click_here_to_delete,click_here_to_refuse,tooltip_click_here_to_refuse,click_here_to_see_terms,tooltip_click_here_to_see_terms,click_here_to_see_privacy_policy,tooltip_click_here_to_see_privacy_policy,privacy_policy_acknowledged_2,privacy_policy_hint,privacy_policy_hint_1,privacy_policy_hint_2,' .
        'click_here_to_see_information,tooltip_click_here_to_see_information' .
        ',copy_paste_link,enter_account_info,enter_invitation_account_info,required_info_notice,excuse_us' .
        ',consider_terms_usage,disclaimer,signature' .
            ',tooltip_login_username,tooltip_login_password,' .
            ',registration_problem,registration_internal,registration_login,registration_sorry,registration_clicked_twice,registration_help,kind_regards,kind_regards_cre,kind_regards_del,kind_regards_ini,kind_regards_inv,kind_regards_upd' .
            ',v_dear,v_verify_before_create,v_verify_invitation_before_create,v_verify_before_update,v_really_wish_to_delete,v_really_wish_to_refuse,v_really_wish_to_approve,v_edit_your_account' .
            ',v_infomail_lost_password,v_infomail_dear,v_infomail_lost_password_enter_new,v_infomail_lost_password_email_not_found,v_infomail_lost_password_subject' .
            ',v_infomail_lost_password_message1,v_infomail_lost_password_message2,v_infomail_lost_password_message3' .
            ',v_now_enter_your_username,v_now_choose_password,v_notification' .
            ',v_registration_created,v_registration_created_subject,v_registration_created_message1,v_registration_created_message2,v_registration_created_message3' .
            ',v_to_the_administrator'.
            ',v_registration_review_subject,v_registration_review_message1,v_registration_review_message2,v_registration_review_message3' .
            ',v_please_confirm,v_your_account_was_created,v_your_account_was_created_nomail,v_follow_instructions1,v_follow_instructions2,v_follow_instructions_review1,v_follow_instructions_review2' .
            ',v_invitation_confirm,v_invitation_account_was_created,v_invitation_instructions1' .
            ',v_registration_initiated,v_registration_initiated_subject,v_registration_initiated_message1,v_registration_initiated_message2,v_registration_initiated_message3,v_registration_initiated_review1,v_registration_initiated_review2' .
            ',v_registration_invited,v_registration_invited_subject,v_registration_invited_message1,v_registration_invited_message1a,v_registration_invited_message2' .
            ',v_registration_infomail_message1a' .
            ',v_registration_confirmed,v_registration_confirmed_subject,v_registration_confirmed_message1,v_registration_confirmed_message2,v_registration_confirmed_review1,v_registration_confirmed_review2' .
            ',v_registration_cancelled,v_registration_cancelled_subject,v_registration_cancelled_message1,v_registration_cancelled_message2' .
            ',v_deletion_cancelled_subject,v_deletion_cancelled_message1' .
            ',v_registration_accepted,v_registration_accepted_subject,v_registration_accepted_message1,v_registration_accepted_message2' .
            ',v_registration_refused,v_registration_refused_subject,v_registration_refused_message1,v_registration_refused_message2' .
            ',v_registration_accepted_subject2,v_registration_accepted_message3,v_registration_accepted_message4' .
            ',v_registration_refused_subject2,v_registration_refused_message3,v_registration_refused_message4' .
            ',v_registration_entered_subject,v_registration_entered_message1,v_registration_entered_message2' .
            ',v_registration_updated,v_registration_updated_subject,v_registration_updated_message1' .
            ',v_registration_deleted,v_registration_deleted_subject,v_registration_deleted_message1,v_registration_deleted_message2' .
            ',v_registration_unsubscribed,v_registration_unsubscribed_subject,v_registration_unsubscribed_message1,v_registration_unsubscribed_message2';
        $this->setOtherLabelsList($otherLabelsList);
    }

    public function getButtonLabelsList()
    {
        return $this->buttonLabelsList;
    }

    public function setButtonLabelsList($buttonLabelsList): void
    {
        $this->buttonLabelsList = $buttonLabelsList;
    }

    public function getOtherLabelsList()
    {
        return $this->otherLabelsList;
    }

    public function setOtherLabelsList($otherLabelsList): void
    {
        $this->otherLabelsList = $otherLabelsList;
    }

    public function addOtherLabelsList($otherLabelsList): void
    {
        if ($otherLabelsList != '') {

            $formerOtherLabelsList = $this->getOtherLabelsList();

            if ($formerOtherLabelsList != '') {
                $newOtherLabelsList = $formerOtherLabelsList . ',' . $otherLabelsList;
                $newOtherLabelsList = StringUtility::uniqueList($newOtherLabelsList);
                $this->setOtherLabelsList($newOtherLabelsList);
            } else {
                $this->setOtherLabelsList($otherLabelsList);
            }
        }
    }

    public function getArray()
    {
        return $this->markerArray;
    }

    public function setArray($param, $value = ''): void
    {
        if (is_array($param)) {
            $this->markerArray = $param;
        } else {
            $this->markerArray[$param] = $value;
        }
    }

    public function getPreviewLabel()
    {
        return $this->previewLabel;
    }

    public function setPreviewLabel($label): void
    {
        $this->previewLabel = $label;
    }

    // enables the usage of {data:<field>}, {tca:<field>} and {meta:<stuff>} in the label markers
    public function replaceVariables(
        $matches
    ) {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $cObj = FrontendUtility::getContentObjectRenderer();
        $conf = $confObj->getConf();
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $controlData = GeneralUtility::makeInstance(Parameters::class);

        $result = '';

        switch ($matches[1]) {
            case 'data':
                $dataArray = $this->getReplaceData();
                $row = $dataArray['row'];
                $result = $row[$matches[2]];
                break;
            case 'tca':
                if (!is_array($this->tmpTcaMarkers)) {
                    $this->tmpTcaMarkers = [];
                    $dataArray = $this->getReplaceData();
                    $row = $dataArray['row'];
                    $cmd = $controlData->getCmd();
                    $cmdKey = $controlData->getCmdKey();
                    $theTable = $controlData->getTable();
                    $this->tca->addMarkers(
                        $this->tmpTcaMarkers,
                        $conf,
                        $languageObj,
                        $controlData,
                        $row,
                        $this->data->getOrigArray(),
                        $cmd,
                        $cmdKey,
                        $theTable,
                        $controlData->getPrefixId(),
                        true,
                        '',
                        false,
                        true
                    );
                }
                $result = $this->tmpTcaMarkers['###TCA_INPUT_' . $matches[2] . '###'];
                break;
            case 'meta':
                if ($matches[2] == 'title') {
                    $result = $this->thePidTitle;
                }
                break;
        }
        if (is_array($result)) {
            $result = implode(',', $result);
        }
        return $result;
    }

    public function setReplaceData($data): void
    {
        $this->dataArray['row'] = $data['row'];
    }

    public function getReplaceData()
    {
        return $this->dataArray;
    }

    /**
    * Sets the error markers to 'no error'
    *
    * @param string command key
    * @param array  Array with key/values being marker-strings/substitution values.
    * @return void  all initialization done directly on array $this->dataArray
    */
    public function setNoError(
        $cmdKey,
        &$markContentArray
    ): void {
        if (
            !empty($cmdKey) &&
            isset($this->conf[$cmdKey . '.']['evalValues.'])
        ) {
            foreach($this->conf[$cmdKey . '.']['evalValues.'] as $theField => $theValue) {
                $markContentArray['###EVAL_ERROR_FIELD_' . $theField . '###'] = '<!--no error-->';
            }
        }
    } // setNoError

    /**
    * Gets the field name needed for the name attribute of the input HTML tag.
    *
    * @param string name of the table
    * @param string name of the field
    * @return string  FE[tablename][fieldname]  ... POST var to transmit the entries with the form
    */
    public function getFieldName(
        $theTable,
        $theField
    ) {
        $result = 'FE[' . $theTable . '][' . $theField . ']';

        if (
            $theField == 'password'
        ) {
            $result = 'pass'; // make the RSA script working: FrontendLoginFormRsaEncryption.js
        }
        return $result;
    }

    /**
    * Adds language-dependent label markers
    *
    * @param array  $markerArray: the input marker array
    * @param array  $row: the record array
    * @param array  $origRow: the original record array as stored in the database
    * @param array  $requiredArray: the required fields array
    * @param array  info fields
    * @param array  $TCA[tablename]['columns']
    * @return void
    */
    public function addLabelMarkers(
        &$markerArray,
        $conf,
        ContentObjectRenderer $cObj,
        Localization $languageObj,
        $extKey,
        $theTable,
        array $row,
        array $origRow,
        $securedArray,
        $keepFields,
        $requiredArray,
        $infoFields,
        $tcaColumns,
        $activity = '',
        $bChangesOnly = false
    ): void {
        $bUseMissingFields = false;
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        if ($activity == 'email') {
            $bUseMissingFields = true;
        }
        $markerArray['###XHTML###'] = HtmlUtility::getXhtmlFix();

        $urlObj = GeneralUtility::makeInstance(Url::class);
        $formUrlMarkerArray = $this->generateFormURLMarkers($urlObj);
        $urlMarkerArray = $this->getUrlMarkerArray();
        $formUrlMarkerArray = array_merge($urlMarkerArray, $formUrlMarkerArray);

        if (is_array($securedArray)) {
            foreach ($securedArray as $field => $value) {
                $row[$field] = $securedArray[$field];
            }
        }

        // Data field labels
        $infoFieldArray = GeneralUtility::trimExplode(',', $infoFields, true);
        $specialFieldArray = GeneralUtility::trimExplode(',', $this->data->getSpecialFieldList(), true);

        if (!empty($specialFieldArray['0'])) {
            $infoFieldArray = array_merge($infoFieldArray, $specialFieldArray);
            $requiredArray = array_merge($requiredArray, $specialFieldArray);
        }

        if ($bUseMissingFields) {
            $tcaFieldArray = array_keys($tcaColumns);
            $infoFieldArray = array_merge($infoFieldArray, $tcaFieldArray);
        }

        $infoFieldArray = array_unique($infoFieldArray);
        foreach($infoFieldArray as $theField) {
            $markerkey = $cObj->caseshift($theField, 'upper');
            $bValueChanged = false;

            if ($bChangesOnly && isset($row[$theField]) && isset($origRow[$theField])) {
                if (is_array($row[$theField]) && is_array($origRow[$theField])) {
                    $diffArray = array_diff($row[$theField], $origRow[$theField]);
                    if (count($diffArray)) {
                        $bValueChanged = true;
                    }
                } else {
                    if ($row[$theField] != $origRow[$theField]) {
                        $bValueChanged = true;
                    }
                }
            }

            if (!$bChangesOnly || $bValueChanged || in_array($theField, $keepFields)) {
                $label = $languageObj->getLabel($theTable . '.' . $theField);
                if (empty($label)) {
                    $label = $languageObj->getLabel($theField);
                }
                $label = (empty($label) ? $languageObj->getLabelFromString($tcaColumns[$theField]['label']) : $label);
                $label = htmlspecialchars($label);
            } else {
                $label = '';
            }
            $markerArray['###LABEL_' . $markerkey . '###'] = $label;
            $markerArray['###TOOLTIP_' . $markerkey . '###'] = $languageObj->getLabel('tooltip_' . $theField);
            $label = $languageObj->getLabel('tooltip_invitation_' . $theField);
            $label = htmlspecialchars($label);
            $markerArray['###TOOLTIP_INVITATION_' . $markerkey . '###'] = $label;

            $colConfig = $tcaColumns[$theField]['config'];

            if (
                $colConfig['type'] == 'select' &&
                isset($colConfig['items']) &&
                is_array($colConfig['items'])
            ) {
                $colContent = '';
                $markerArray['###FIELD_' . $markerkey . '_CHECKED###'] = '';
                $markerArray['###LABEL_' . $markerkey . '_CHECKED###'] = '';
                $markerArray['###POSTVARS_' . $markerkey . '###'] = '';

                if (isset($row[$theField])) {
                    $fieldArray = [];
                    if (is_array($row[$theField])) {
                        $fieldArray = $row[$theField];
                    } else {
                        $fieldArray = GeneralUtility::trimExplode(',', (string) $row[$theField]);
                    }

                    foreach ($fieldArray as $key => $value) {
                        $label = $languageObj->getLabelFromString($colConfig['items'][$value]['label']);
                        $markerArray['###FIELD_' . $markerkey . '_CHECKED###'] .= '- ' . $label . '<br' . HtmlUtility::getXhtmlFix() . '>';
                        $label = $languageObj->getLabelFromString($colConfig['items'][$value]['label']);
                        $markerArray['###LABEL_' . $markerkey . '_CHECKED###'] .= '- ' . $label . '<br' . HtmlUtility::getXhtmlFix() . '>';
                        $markerArray['###POSTVARS_' . $markerkey.'###'] .= chr(10) . '	<input type="hidden" name="FE[fe_users][' . $theField . '][' . $key . ']" value ="' . $value . '"' . HtmlUtility::getXhtmlFix() . '>';
                    }
                }
            } elseif ($colConfig['type'] == 'check') {
                $yes = (isset($row[$theField]) && $row[$theField] != '');
                $markerArray['###FIELD_' . $markerkey . '_CHECKED###'] = ($yes ? 'checked' : '');
                $markerArray['###LABEL_' . $markerkey . '_CHECKED###'] = ($yes ? $languageObj->getLabel('yes') : $languageObj->getLabel('no'));
            }

            if (in_array(trim($theField), $requiredArray)) {
                $markerArray['###REQUIRED_' . $markerkey . '###'] = $cObj->cObjGetSingle($conf['displayRequired'], $conf['displayRequired.'], $extKey); // default: '<span>*</span>';
                $key = 'missing_' . $theField;
                $label = $languageObj->getLabel($key);
                if ($label == '') {
                    $label = $languageObj->getLabel('internal_no_text_found');
                    $label = sprintf($label, $key);
                }
                $markerArray['###MISSING_' . $markerkey . '###'] = $label;
                $markerArray['###MISSING_INVITATION_' . $markerkey . '###'] = $languageObj->getLabel('missing_invitation_' . $theField);
            } else {
                $markerArray['###REQUIRED_' . $markerkey . '###'] = '';
                $markerArray['###MISSING_' . $markerkey . '###'] = '';
                $markerArray['###MISSING_INVITATION_' . $markerkey . '###'] = '';
            }
            $markerArray['###NAME_' . $markerkey . '###'] = $this->getFieldName($theTable, $theField);
        }
        $markerArray['###NAME_PASSWORD_AGAIN###'] = $this->getFieldName($theTable, 'password_again');
        $buttonLabels = GeneralUtility::trimExplode(',', $this->getButtonLabelsList(), true);

        foreach($buttonLabels as $labelName) {
            if ($labelName) {
                $buttonKey = strtoupper($labelName);
                $markerArray['###LABEL_BUTTON_' . $buttonKey . '###'] = $languageObj->getLabel('button_' . $labelName);
                $attributes = '';

                if (
                    isset($conf['button.'])
                    && isset($conf['button.'][$buttonKey . '.'])
                    && isset($conf['button.'][$buttonKey . '.']['attribute.'])
                ) {
                    $attributesArray = [];
                    foreach ($conf['button.'][$buttonKey . '.']['attribute.'] as $key => $value) {
                        $attributesArray[] = $key . '="' . $value . '"';
                    }
                    $attributes = implode(' ', $attributesArray);
                    $attributes = $templateService->substituteMarkerArray($attributes, $formUrlMarkerArray);
                }
                $markerArray['###ATTRIBUTE_BUTTON_' . $buttonKey . '###'] = $attributes;
            }
        }
        // Assemble the name to be substituted in the labels
        $name = '';
        if ($conf['salutation'] == 'informal' && $row['first_name'] != '') {
            $name = $row['first_name'];
        } else {
            // Honour Address List (tt_address) configuration settings
            if ($theTable == 'tt_address' && ExtensionManagementUtility::isLoaded('tt_address') && isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_address'])) {
                $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('tt_address');
                if (is_array($extConf)) {
                    $nameFormat = '';

                    if ($extConf['disableCombinedNameField'] != '1' && $row['name'] != '') {
                        $name = $row['name'];
                    } elseif (isset($extConf['backwardsCompatFormat'])) {
                        $nameFormat = $extConf['backwardsCompatFormat'];
                    }

                    if ($nameFormat != '') {
                        $name = sprintf(
                            $nameFormat,
                            $row['first_name'],
                            $row['middle_name'],
                            $row['last_name']
                        );
                    }
                }
            }

            if ($name == '' && isset($row['name'])) {
                $name = trim($row['name']);
            }

            if ($name == '') {
                $name = ((isset($row['first_name']) && trim($row['first_name'])) ? trim($row['first_name']) : '') .
                    ((isset($row['middle_name']) && trim($row['middle_name'])) ? ' ' . trim($row['middle_name']) : '') .
                    ((isset($row['last_name']) && trim($row['last_name'])) ? ' ' . trim($row['last_name']) : '');
                $name = trim($name);
            }
            if ($name == '' && isset($row['uid'])) {
                $name = 'id(' . $row['uid'] . ')';
            }
        }
        $name = htmlspecialchars($name);

        $this->tmpTcaMarkers = null; // reset function replaceVariables
        $dataArray = [];
        $dataArray['row'] = $row;
        $this->setReplaceData($dataArray);

        $outputArray = ['username' => '', 'email' => '', 'password' => ''];
        foreach ($outputArray as $field => $value) {
            if (
                isset($row[$field]) &&
                $row[$field] != ''
            ) {
                $outputArray[$field] = htmlspecialchars($row[$field]); // assign it to avoid the "Illegal string offset" warning
            }
        }

        if (
            $outputArray['username'] == ''
        ) {
            $outputArray['username'] = $outputArray['email'];
        }

        $genderLabelArray = [];
        $vDear = 'v_dear';
        if (isset($row['gender'])) {
            if (
                $row['gender'] == '0' ||
                $row['gender'] == 'm'
            ) {
                $vDear = 'v_dear_male';
            } elseif (
                $row['gender'] == '1' ||
                $row['gender'] == 'f'
            ) {
                $vDear = 'v_dear_female';
            }
        }
        $genderLabelArray['v_dear'] = $vDear;


        $this->addOtherLabelMarkers(
            $markerArray,
            $cObj,
            $languageObj,
            $conf,
            $name,
            $outputArray,
            $genderLabelArray
        );
    }

    public function addOtherLabelMarkers(
        &$markerArray,
        $cObj,
        $languageObj,
        $conf,
        $name = '',
        $outputArray = '',
        $genderLabelArray = ''
    ): void {
        if (!is_array($outputArray)) {
            $outputArray = ['username' => '', 'email' => '', 'password' => ''];
        }
        $otherLabelsList = $this->getOtherLabelsList();

        if (isset($conf['extraLabels']) && $conf['extraLabels'] != '') {
            $otherLabelsList .= ',' . $conf['extraLabels'];
        }
        $otherLabels = GeneralUtility::trimExplode(',', $otherLabelsList, true);

        foreach($otherLabels as $value) {
            if (
                isset($genderLabelArray) &&
                is_array($genderLabelArray) &&
                isset($genderLabelArray[$value])
            ) {
                $labelName = $genderLabelArray[$value];
            } else {
                $labelName = $value;
            }
            $langText = $languageObj->getLabel($labelName);
            $label = sprintf(
                $langText,
                $this->thePidTitle,
                $outputArray['username'],
                $name,
                $outputArray['email'],
                $outputArray['password']
            );
            $label = preg_replace_callback('/{([a-z_]+):([a-zA-Z0-9_]+)}/', [$this, 'replaceVariables'], $label);
            $markerkey = $cObj->caseshift($value, 'upper');
            $markerArray['###LABEL_' . $markerkey . '###'] = $label;
        }
    }  // addOtherLabelMarkers

    public function setRow($row): void
    {
        $this->row = $row;
    }

    public function getRow($row)
    {
        return $this->row;
    }

    /**
    * Generates the URL markers
    *
    * @param string auth code
    * @return void
    */
    public function generateURLMarkers(
        Url $urlObj,
        $backUrl,
        $uid,
        $token,
        $theTable,
        $extKey,
        $prefixId
    ) {
        $markerArray = [];
        $vars = [];
        $unsetVarsList = 'mode,pointer,sort,sword,backURL,submit,rU,aC,sFK,doNotSave,preview';
        $unsetVars = GeneralUtility::trimExplode(',', $unsetVarsList);
        $unsetVars['cmd'] = 'cmd';
        $unsetVarsAll = $unsetVars;
        $unsetVarsAll[] = 'token';
        $formUrl = $urlObj->get($this->controlData->getPageId() . ',' . $this->controlData->getType(), '', $vars, $unsetVarsAll);

        unset($unsetVars['cmd']);
        $markerArray['###FORM_URL###'] = $formUrl;

        $ac = $this->controlData->getFeUserData('aC');
        if ($ac) {
            $vars['aC'] = $ac;
        }
        $vars['cmd'] = $this->controlData->getCmd();
        $vars['token'] = $token;
        $vars['backURL'] = rawurlencode($formUrl);
        $vars['cmd'] = 'delete';
        $vars['rU'] = $uid;
        $vars['preview'] = '1';

        $markerArray['###DELETE_URL###'] = $urlObj->get($this->controlData->getPid('edit') . ',' . $this->controlData->getType(), '', $vars);

        $vars['cmd'] = 'create';

        $unsetVars[] = 'regHash';
        $url = $urlObj->get($this->controlData->getPid('register') . ',' . $this->controlData->getType(), '', $vars, $unsetVars);
        $markerArray['###REGISTER_URL###'] = $url;

        $unsetVarsList = 'mode,pointer,sort,sword,backURL,submit,doNotSave,preview';
        $unsetVars = GeneralUtility::trimExplode(',', $unsetVarsList);

        $vars['cmd'] = 'login';
        $markerArray['###LOGIN_FORM###'] = $urlObj->get($this->controlData->getPid('login') . ',' . $this->controlData->getType(), '', $vars, $unsetVars);

        $vars['cmd'] = 'infomail';
        $markerArray['###INFOMAIL_URL###'] = $urlObj->get($this->controlData->getPid('infomail') . ',' . $this->controlData->getType(), '', $vars, $unsetVars);

        $vars['cmd'] = 'edit';

        $markerArray['###EDIT_URL###'] =
            $urlObj->get(
                $this->controlData->getPid('edit') . ',' . $this->controlData->getType(),
                '',
                $vars,
                $unsetVars
            );
        $markerArray['###THE_PID###'] = $this->controlData->getPid();
        $markerArray['###THE_PID_TITLE###'] = $this->thePidTitle;
        $markerArray['###BACK_URL###'] = $backUrl;
        $markerArray['###SITE_NAME###'] = $this->conf['email.']['fromName'];
        $markerArray['###SITE_URL###'] = $this->controlData->getSiteUrl();
        $markerArray['###SITE_WWW###'] = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        $markerArray['###SITE_EMAIL###'] = $this->conf['email.']['from'];

        // Set the url to the terms and conditions
        if ($this->conf['terms.']['url']) {
            $termsUrlParam = $this->conf['terms.']['url'];
        } else {
            $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
            $termsUrlParam = $sanitizer->sanitize($this->conf['terms.']['file']);
        }
        $markerArray['###TERMS_URL###'] = $urlObj->get($termsUrlParam, '', [], [], false);

        // Set the url to the privacy policy
        if ($this->conf['privacy.']['url']) {
            $privacyUrlParam = $this->conf['privacy.']['url'];
        } else {
            $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
            $privacyUrlParam = $sanitizer->sanitize($this->conf['privacy.']['file']);
        }
        $markerArray['###PRIVACY_POLICY_URL###'] =
            $urlObj->get(
                $privacyUrlParam,
                '',
                [],
                [],
                false
            );

        return $markerArray;
    }	// generateURLMarkers

    /**
    * Generates the form URL markers
    *
    * @param string auth code
    * @return void
    */
    public function generateFormURLMarkers($urlObj)
    {
        $commandArray =  ['register', 'edit', 'delete', 'confirm', 'login'];
        $markerArray = [];
        $vars = [];
        $unsetVarsList = 'mode,pointer,sort,sword,backURL,submit,rU,aC,sFK,doNotSave,preview';
        $unsetVars = GeneralUtility::trimExplode(',', $unsetVarsList);
        $unsetVars['cmd'] = 'cmd';
        $unsetVarsAll = $unsetVars;
        $unsetVarsAll[] = 'token';
        $commandPidArray = [];

        foreach ($commandArray as $command) {
            $upperCommand = strtoupper($command);
            $pid = $this->conf[$command . 'PID'] ?? 0;
            if (!$pid) {
                $pid = $this->controlData->getPageId();
            }
            $formUrl = $urlObj->get($pid . ',' . $this->controlData->getType(), '', $vars, $unsetVarsAll);
            $markerArray['###FORM_' . $upperCommand . '_URL###'] = $formUrl;
        }
        return $markerArray;
    }

    public function setUrlMarkerArray($markerArray): void
    {
        $this->urlMarkerArray = $markerArray;
    }

    public function getUrlMarkerArray()
    {
        return $this->urlMarkerArray;
    }

    /**
    * Adds URL markers to a $markerArray
    *
    * @param array  $markerArray: the input marker array
    * @param string auth code
    * @return void
    */
    public function addGeneralHiddenFieldsMarkers(
        &$markerArray,
        $cmd,
        $token,
        $setFixedKey,
        array $fD
    ): void {
        $localMarkerArray = [];
        $authObj = GeneralUtility::makeInstance(Authentication::class);
        $authCode = $authObj->getAuthCode();

        $backUrl = $this->controlData->getBackURL();
        $extKey = $this->controlData->getExtensionKey();
        $prefixId = $this->controlData->getPrefixId();

        $localMarkerArray['###HIDDENFIELDS###'] = ($markerArray['###HIDDENFIELDS###'] ?? '') . ($cmd ? '<input type="hidden" name="' . $prefixId . '[cmd]" value="' . $cmd . '"' . HtmlUtility::getXhtmlFix() . '>' : '');
        $localMarkerArray['###HIDDENFIELDS###'] .= chr(10) . ($authCode ? '<input type="hidden" name="' . $prefixId . '[aC]" value="' . $authCode . '"' . HtmlUtility::getXhtmlFix() . '>' : '');
        $localMarkerArray['###HIDDENFIELDS###'] .= chr(10) . ($backUrl ? '<input type="hidden" name="' . $prefixId . '[backURL]" value="' . htmlspecialchars($backUrl) . '"' . HtmlUtility::getXhtmlFix() . '>' : '');

        if ($setFixedKey != '') {
            $localMarkerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="' . $prefixId . '[sFK]" value="' . htmlspecialchars($setFixedKey) . '"' . HtmlUtility::getXhtmlFix() . '>';
        }

        if (isset($fD) && is_array($fD)) {
            foreach ($fD as $field => $value) {
                $localMarkerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="' . $prefixId . '[fD][' . htmlspecialchars($field) . ']" value="' . htmlspecialchars($value) . '"' . HtmlUtility::getXhtmlFix() . '>';
            }
        }

        $this->addFormToken(
            $localMarkerArray,
            $token,
            $extKey,
            $prefixId
        );

        $markerArray = array_merge($markerArray, $localMarkerArray);
    }

    /**
    * Adds Static Info markers to a marker array
    *
    * @param array  $markerArray: the input marker array
    * @param array  $row: the table record
    * @return void
    */
    public function addStaticInfoMarkers(
        &$markerArray,
        Localization $languageObj,
        $prefixId,
        $row = '',
        $viewOnly = false
    ): void {
        if (is_object($this->staticInfoObj)) {
            $css = GeneralUtility::makeInstance(Css::class);
            $cmd = $this->controlData->getCmd();
            $theTable = $this->controlData->getTable();

            if ($this->controlData->getMode() == Mode::PREVIEW || $viewOnly) {
                $markerArray['###FIELD_static_info_country###'] =
                    $this->staticInfoObj->getStaticInfoName('COUNTRIES', is_array($row) ? $row['static_info_country'] : '');
                $markerArray['###FIELD_zone###'] = $this->staticInfoObj->getStaticInfoName('SUBDIVISIONS', is_array($row) ? $row['zone'] : '', is_array($row) ? $row['static_info_country'] : '');
                if (!$markerArray['###FIELD_zone###']) {
                    $markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="FE['.$theTable.'][zone]" value=""' . HtmlUtility::getXhtmlFix() . '>';
                }
                $markerArray['###FIELD_language###'] = $this->staticInfoObj->getStaticInfoName('LANGUAGES', is_array($row) ? $row['language'] : '');
            } else {
                $idCountry =
                    FrontendUtility::getClassName(
                        'static_info_country',
                        $prefixId
                    );
                $titleCountry = $languageObj->getLabel('tooltip_' . (($cmd == 'invite') ? 'invitation_' : '')  . 'static_info_country');
                $idZone =
                    FrontendUtility::getClassName(
                        'zone',
                        $prefixId
                    );
                $titleZone = $languageObj->getLabel('tooltip_' . (($cmd == 'invite') ? 'invitation_' : '')  . 'zone');
                $idLanguage =
                    FrontendUtility::getClassName(
                        'language',
                        $prefixId
                    );
                $titleLanguage = $languageObj->getLabel('tooltip_' . (($cmd == 'invite') ? 'invitation_' : '')  . 'language');
                $fieldNameCountry = 'static_info_country';
                $selected = (is_array($row) && isset($row[$fieldNameCountry]) ? $row[$fieldNameCountry] : []);
                $where = '';
                if (isset($this->conf['where.']) && is_array($this->conf['where.'])) {
                    $where = $this->conf['where.']['static_countries'];
                }
                $markerArray['###SELECTOR_STATIC_INFO_COUNTRY###'] = $this->staticInfoObj->buildStaticInfoSelector(
                    'COUNTRIES',
                    'FE[' . $theTable . ']' . '[' . $fieldNameCountry . ']',
                    $css->getClassName($fieldNameCountry, 'select'),
                    $selected,
                    '',
                    $this->conf['onChangeCountryAttribute'],
                    $idCountry,
                    $titleCountry,
                    $where,
                    '',
                    $this->conf['useLocalCountry']
                );

                $fieldNameZone = 'zone';
                $where = '';
                if (isset($this->conf['where.']) && is_array($this->conf['where.'])) {
                    $where = $this->conf['where.']['static_country_zones'];
                }
                $markerArray['###SELECTOR_ZONE###'] =
                    $this->staticInfoObj->buildStaticInfoSelector(
                        'SUBDIVISIONS',
                        'FE[' . $theTable . ']' . '[' . $fieldNameZone . ']',
                        $css->getClassName($fieldNameZone, 'select'),
                        $row[$fieldNameZone] ?? '',
                        $row[$fieldNameCountry] ?? '',
                        '',
                        $idZone,
                        $titleZone,
                        $where
                    );
                if (!$markerArray['###SELECTOR_ZONE###']) {
                    $markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="FE[' . $theTable . '][' . $fieldNameZone . ']" value=""' . HtmlUtility::getXhtmlFix() . '>';
                }

                $fieldNameLanguage = 'language';
                $where = '';
                if (isset($this->conf['where.']) && is_array($this->conf['where.'])) {
                    $where = $this->conf['where.']['static_languages'];
                }

                $markerArray['###SELECTOR_LANGUAGE###'] =
                    $this->staticInfoObj->buildStaticInfoSelector(
                        'LANGUAGES',
                        'FE[' . $theTable . ']' . '[' . $fieldNameLanguage . ']',
                        $css->getClassName($fieldNameLanguage, 'select'),
                        $row[$fieldNameLanguage] ?? '',
                        '',
                        '',
                        $idLanguage,
                        $titleLanguage,
                        $where
                    );
            }
        }
    }	// addStaticInfoMarkers

    /**
    * Builds a file uploader
    *
    * @param string  $theField: the field name
    * @param array  $config: the field TCA config
    * @param array  $filenames: array of uploaded file names
    * @param string  $prefix: the field name prefix
    * @return string  generated HTML uploading tags
    */
    public function buildFileUploader(
        Localization $languageObj,
        $theField,
        $config,
        $cmd,
        $cmdKey,
        $prefixId,
        $theTable,
        $filenameArray,
        $viewOnly = false,
        $activity = '',
        $bHtml = true
    ) {
        $HTMLContent = '';
        $tablePrefix = 'FE[' . $theTable . ']';
        $size = $config['maxitems'];
        $cmdParts = [];
        if (!empty($cmdKey) && isset($this->conf[$cmdKey . '.']['evalValues.'][$theField])) {
            $cmdParts = preg_split('/\[|\]/', $this->conf[$cmdKey . '.']['evalValues.'][$theField]);
        }
        if(!empty($cmdParts[1])) {
            $size = min($size, intval($cmdParts[1]));
        }
        $size = $size ?: 1;
        $number = $size - sizeof($filenameArray);
        $dir = $config['uploadfolder'];

        if ($viewOnly) {
            for ($i = 0; $i < sizeof($filenameArray); $i++) {
                $HTMLContent .= $filenameArray[$i];
                if ($activity == 'email') {
                    if ($bHtml) {
                        $HTMLContent .= '<br' . HtmlUtility::getXhtmlFix() . '>';
                    } else {
                        $HTMLContent .= chr(13) . chr(10);
                    }
                } elseif ($bHtml) {
                    $HTMLContent .= '<a href="' . $dir . '/' . $filenameArray[$i] . '"' .
                    FrontendUtility::classParam(
                        'file-view',
                        '',
                        $prefixId
                    ) .
                    ' target="_blank" title="' . $languageObj->getLabel('file_view') . '">' .
                        $languageObj->getLabel('file_view') .
                    '</a><br' . HtmlUtility::getXhtmlFix() . '>';
                }
            }
        } else {
            $HTMLContent = '<script>' . chr(13) . '
var submitFile = function(id){
    if(confirm(\'' . $languageObj->getLabel('confirm_file_delete') . '\')) {
        document.getElementById(id).value=\'1\';
        return true;
    } else
        return false;
};' . chr(13) .
            '</script>' . chr(13);
            for($i = 0; $i < sizeof($filenameArray); $i++) {
                $partContent =
                    $filenameArray[$i] . '<input type="hidden" id="' . $prefixId . '-file-' . $i . '" name="' . $tablePrefix . '[' . $theField . '][' . $i . '][submit_delete]" value=""' .
                    ' title="' .
                    $languageObj->getLabel('icon_delete') .
                    '" alt="' . $languageObj->getLabel('icon_delete') . '"' .
                    FrontendUtility::classParam(
                        'delete-view',
                        '',
                        $prefixId
                    ) .
                    HtmlUtility::getXhtmlFix() . '>';

                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $fileUrl = $sanitizer->sanitize($this->conf['icon_delete']);

                $partContent .=
                    '<input type="image" name="submit_delete" src="' .
                    $fileUrl . '"' .
                    ' onclick="return submitFile(\'' . $prefixId . '-file-' . $i . '\');"' . HtmlUtility::getXhtmlFix() . '>';

                $partContent .=
                    '<a href="' . $dir . '/' . $filenameArray[$i] . '" ' .
                    FrontendUtility::classParam(
                        'file-view',
                        '',
                        $prefixId
                    ) .
                    ' target="_blank" title="' . $languageObj->getLabel('file_view') . '">' .
                    $languageObj->getLabel('file_view') . '</a>' .
                    '<br' . HtmlUtility::getXhtmlFix() . '>';
                $HTMLContent .= $partContent . '<input type="hidden" name="' . $tablePrefix . '[' . $theField . '][' . $i . '][name]' . '" value="' . $filenameArray[$i] .
                '"' . HtmlUtility::getXhtmlFix() . '>';
            }

            for ($i = sizeof($filenameArray); $i < $number + sizeof($filenameArray); $i++) {
                $HTMLContent .= '<input id="' .
                FrontendUtility::getClassName(
                    $theField,
                    $prefixId
                ) .
                '-' . ($i - sizeof($filenameArray)) .
                '" name="' . $tablePrefix . '[' . $theField . '][' . $i . ']" title="' .
                $languageObj->getLabel('tooltip_' . (($cmd == 'invite') ? 'invitation_' : '')  . 'image') . '" size="40" type="file" ' .
                FrontendUtility::classParam(
                    'uploader-view',
                    '',
                    $prefixId
                ) .
                HtmlUtility::getXhtmlFix() . '><br' . HtmlUtility::getXhtmlFix() . '>';
            }
        }
        return $HTMLContent;
    }	// buildFileUploader

    /**
    * Adds uploading markers to a marker array
    *
    * @param string  $theField: the field name
    * @param array  $markerArray: the input marker array
    * @param array  $dataArray: the record array
    * @return void
    */
    public function addFileUploadMarkers(
        Localization $languageObj,
        $theTable,
        $theField,
        $fieldConfig,
        &$markerArray,
        $cmd,
        $cmdKey,
        $prefixId,
        $dataArray = [],
        $viewOnly = false,
        $activity = '',
        $bHtml = true
    ): void {
        $filenameArray = [];

        if ($dataArray[$theField]) {
            $filenameArray = $dataArray[$theField];
        }

        if ($viewOnly) {
            $markerArray['###UPLOAD_PREVIEW_' . $theField . '###'] =
                $this->buildFileUploader(
                    $languageObj,
                    $theField,
                    $fieldConfig['config'],
                    $cmd,
                    $cmdKey,
                    $prefixId,
                    $theTable,
                    $filenameArray,
                    true,
                    $activity,
                    $bHtml
                );
        } else {
            $markerArray['###UPLOAD_' . $theField . '###'] =
                $this->buildFileUploader(
                    $languageObj,
                    $theField,
                    $fieldConfig['config'],
                    $cmd,
                    $cmdKey,
                    $prefixId,
                    $theTable,
                    $filenameArray,
                    false,
                    $activity,
                    $bHtml
                );
            $max_size = $fieldConfig['config']['max_size'] * 1024;
            $markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $max_size . '"' . HtmlUtility::getXhtmlFix() . '>';
        }
    }	// addFileUploadMarkers

    /**
    * Inserts a token for the form and stores it
    *
    * @param array  $markerArray: the token is added to the '###HIDDENFIELDS###' marker
    */
    public function addFormToken(
        &$markerArray,
        $token,
        $extKey,
        $prefixId
    ): void {
        $markerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="' . $prefixId . '[token]" value="' . $token . '"' . HtmlUtility::getXhtmlFix() . '>';
    }

    public function addHiddenFieldsMarkers(
        &$markerArray,
        $theTable,
        $extKey,
        $prefixId,
        $cmdKey,
        $mode,
        $token,
        $useEmailAsUsername,
        $enableEmailConfirmation,
        $cmdKeyFields,
        $dataArray = []
    ): void {
        if ($this->conf[$cmdKey . '.']['preview'] && $mode != Mode::PREVIEW) {
            $markerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="' . $prefixId .  '[preview]" value="1"' . HtmlUtility::getXhtmlFix() . '>';
            if (
                $theTable == 'fe_users' &&
                $cmdKey == 'edit' &&
                $useEmailAsUsername
            ) {
                $markerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="FE[' . $theTable . '][username]" value="' . htmlspecialchars($dataArray['username']) . '"' . HtmlUtility::getXhtmlFix() . '>';
                if ($enableEmailConfirmation) {
                    $markerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="FE[' . $theTable . '][email]" value="' . htmlspecialchars($dataArray['email']) . '"' . HtmlUtility::getXhtmlFix() . '>';
                }
            }
        }
        $fieldArray = GeneralUtility::trimExplode(',', $cmdKeyFields, true);

        if ($mode == Mode::PREVIEW) {
            $fieldArray = array_diff($fieldArray, ['hidden', 'disable']);

            $fields = implode(',', $fieldArray);
            $fields = SecuredData::getOpenFields($fields);
            $fieldArray = explode(',', $fields);

            foreach ($fieldArray as $theField) {
                $value = $dataArray[$theField] ?? '';
                if (is_array($value)) {
                    $value = implode(',', $value);
                } else if (is_string($value)) {
                    $value = htmlspecialchars($value);
                }
                $markerArray['###HIDDENFIELDS###'] .= chr(10) . '<input type="hidden" name="FE[' . $theTable . '][' . $theField . ']" value="' . $value . '"' . HtmlUtility::getXhtmlFix() . '>';
            }
        } elseif (
            ($theTable == 'fe_users') &&
            ($cmdKey == 'edit' || $cmdKey == 'password') &&
            !in_array('email', $fieldArray) &&
            !in_array('username', $fieldArray)
        ) {
            // Password change form probably contains neither email nor username
            $theField = 'username';
            $value = htmlspecialchars($dataArray[$theField]);
            $markerArray['###HIDDENFIELDS###'] .= LF . '<input type="hidden" name="FE[' . $theTable . '][' . $theField . ']" value="' . $value . '"' . HtmlUtility::getXhtmlFix() . '>';
        }

        $this->addFormToken(
            $markerArray,
            $token,
            $extKey,
            $prefixId
        );
    }	// addHiddenFieldsMarkers


    /**
    * Inserts an input checkbox for the privacy policy agreement
    *
    * @param array  $markerArray: the token is added to the '###HIDDENFIELDS###' marker
    */
    public function addPrivacyPolicy(
        &$markerArray,
        $prefixId,
        $theTable,
        array $dataArray,
        $usePrivacyPolicy
    ): void {
        $markerArray['###BUTTON_DISABLED###'] = '';

        if ($usePrivacyPolicy) {
            $theField = 'privacy_policy_acknowledged';
            $privacyCheckboxName = $this->getFieldName(
                $theTable,
                $theField
            );
            if (
                !isset($dataArray[$theField]) ||
                empty($dataArray[$theField])
            ) {
                $markerArray['###BUTTON_DISABLED###'] =
                    (HtmlUtility::useXHTML() ? 'disabled="disabled"' : 'disabled');
            }
            $submitButtonName = $prefixId . '[submit]';

            $markerArray['###EXTRA_INPUT_privacy_policy_acknowledged###'] =
            '<input type="checkbox" name="' . $privacyCheckboxName . '"  onchange="document.getElementsByName(\'' . $submitButtonName . '\')[0].disabled=!document.getElementsByName(\'' . $privacyCheckboxName . '\')[0].checked;"' . HtmlUtility::getXhtmlFix() . '>';
        } else {
            $markerArray['###EXTRA_INPUT_privacy_policy_acknowledged###'] = '';
        }
    }

    /**
    * Removes irrelevant Static Info subparts (zone selection when the country has no zone)
    *
    * @param string  $templateCode: the input template
    * @param array  $markerArray: the marker array
    * @return string  the output template
    */
    public function removeStaticInfoSubparts(
        $templateCode,
        $markerArray,
        $viewOnly = false
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        if ($this->controlData->getMode() == Mode::PREVIEW || $viewOnly) {
            if (empty($markerArray['###FIELD_zone###'])) {
                return $templateService->substituteSubpart($templateCode, '###SUB_INCLUDED_FIELD_zone###', '');
            }
        } else {
            if (empty($markerArray['###SELECTOR_ZONE###'])) {
                return $templateService->substituteSubpart($templateCode, '###SUB_INCLUDED_FIELD_zone###', '');
            }
        }
        return $templateCode;
    }	// removeStaticInfoSubparts

    /**
    * Adds elements to the input $markContentArray based on the values from the fields from $fieldList found in $row
    *
    * @param	array		Array with key/values being marker-strings/substitution values.
    * @param	array		An array with keys found in the $fieldList (typically a record) which values should be moved to the $markContentArray
    * @param	string		A list of fields from the $row array to add to the $markContentArray array. If empty all fields from $row will be added (unless they are integers)
    * @param	boolean		If set, all values added to $markContentArray will be nl2br()'ed
    * @param	string		Prefix string to the fieldname before it is added as a key in the $markContentArray. Notice that the keys added to the $markContentArray always start and end with "###"
    * @param	boolean		If set, all values are passed through htmlspecialchars() - RECOMMENDED to avoid most obvious XSS and maintain XHTML compliance.
    * @return	array		The modified $markContentArray
    */
    public function fillInMarkerArray(
        $markerArray,
        $row,
        $securedArray,
        Parameters $controlData,
        $dataObj,
        ConfigurationStore $confObj,
        $fieldList = '',
        $nl2br = true,
        $prefix = 'FIELD_',
        $HSC = true
    ) {
        $conf = $confObj->getConf();
        if (is_array($securedArray)) {
            foreach ($securedArray as $field => $value) {
                $row[$field] = $securedArray[$field];
            }
        }

        if ($fieldList != '') {
            $fArr = GeneralUtility::trimExplode(',', $fieldList, true);
            foreach($fArr as $field) {
                $markerArray['###' . $prefix . $field . '###'] = $nl2br && is_string($row[$field]) ? nl2br($row[$field]) : $row[$field];
            }
        } else {

            if (is_array($row)) {
                foreach($row as $field => $value) {
                    $bFieldIsInt = MathUtility::canBeInterpretedAsInteger($field);
                    if (!$bFieldIsInt) {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        if ($HSC && !empty($value) && is_string($value)) {
                            $value = htmlspecialchars($value);
                        }
                        $markerArray['###' . $prefix . $field . '###'] =
                            $nl2br && !empty($value) && is_string($value) ? nl2br($value) : $value;
                    }
                }
            }
        }
        // Add global markers
        $extKey = $controlData->getExtensionKey();

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['registrationProcess']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['registrationProcess'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['registrationProcess'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);

                if (method_exists($hookObj, 'addGlobalMarkers')) {
                    if (
                        method_exists($hookObj, 'needsInit') &&
                        method_exists($hookObj, 'init') &&
                        $hookObj->needsInit()
                    ) {
                        $hookObj->init($dataObj);
                    }

                    $hookObj->addGlobalMarkers(
                        $markerArray,
                        $controlData,
                        $confObj,
                        $this
                    );
                }
            }
        }

        return $markerArray;
    }

    public static function fillInCaptchaMarker(
        &$markerArray,
        $captcha
    ): void {
        if (
            $captcha instanceof CaptchaInterface
        ) {
            $captcha->addGlobalMarkers(
                $markerArray,
                true
            );
        }
    }

    /*
    * Checks for the presence of some deprecated markers in the source code of the HTML template
    *
    * @param string $templateCode: the template source code
    * @param string $extKey: current extension key
    * @param string $fileName: name of template file
    * @return array error messages
    *
    * See: tx_agency_pi_base::checkDeprecatedMarkers
    */
    public static function checkDeprecatedMarkers(
        ServerRequestInterface $request,
        $templateCode,
        $extKey,
        $fileName
    ) {
        $messages = [];
        // These changes apply only to agency
        if ($extKey == Extension::KEY) {
            // Version 0: no clear-text passwords in templates
            // Remove any ###FIELD_password###, ###FIELD_password_again### markers
            // Remove markers ###TEMPLATE_INFOMAIL###, ###TEMPLATE_INFOMAIL_SENT### and ###EMAIL_TEMPLATE_INFOMAIL###
            $removeMarkers = [
                '###FIELD_password###',
                '###FIELD_password_again###'
            ];
            $removeMarkerMessage =
                GeneralUtility::makeInstance(LanguageServiceFactory::class)
                ->createFromSiteLanguage($request->getAttribute('language'))->sL('LLL:EXT:' . $extKey . DIV2007_LANGUAGE_SUBPATH . 'locallang.xlf:internal_remove_deprecated_marker');

            foreach ($removeMarkers as $marker) {
                if (strpos($templateCode, $marker) !== false) {
                    $messages[] = sprintf($removeMarkerMessage, $marker, $fileName);
                }
            }

            $replaceMarkers = [
                [
                    'marker' => '###CHECK_NOT_USED1###',
                    'replacement' => '###CHECK_NOT_USED1A###'
                ],
            ];
            $replaceMarkerMessage =
                GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromSiteLanguage($request->getAttribute('language'))->sL(
                    'LLL:EXT:' . $extKey . DIV2007_LANGUAGE_SUBPATH . 'locallang.xlf:internal_replace_deprecated_marker'
                );

            foreach ($replaceMarkers as $replaceMarker) {
                if (strpos($templateCode, $replaceMarker['marker']) !== false) {
                    $messages[] = sprintf($replaceMarkerMessage, $replaceMarker['marker'], $replaceMarker['replacement'], $fileName);
                }
            }
        }

        return $messages;
    }
}
