<?php

namespace JambageCom\Agency\Controller;

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
 * email functions. former class tx_agency_email
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\MailUtility;


class Email implements \TYPO3\CMS\Core\SingletonInterface {
	public $infomailPrefix = 'INFOMAIL_';
	public $emailMarkPrefix = 'EMAIL_TEMPLATE_';
	public $emailMarkAdminSuffix = '_ADMIN';
	public $emailMarkHTMLSuffix = '_HTML';
    protected $extensionKey = '';

    public function getExtensionKey ()
    {
        return $this->extensionKey;
    }

    public function setExtensionKey ($extensionKey)
    {
        $this->extensionKey = $extensionKey;
    }

    public function isHTMLMailEnabled ($conf)
    {
        $result = true;
        if (
            isset($conf['email.']) &&
            isset($conf['email.']['HTMLMail'])
        ) {
            $result = $conf['email.']['HTMLMail'];
        }
        return $result;
    }

    /**
    * This method sends info mail to subscriber or it displays a screen to update or delete the membership or to send a login link to reenter a forgotten password.
    *
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param array $controlData: the object of the control data
    * @param string $theTable: the table in use
    * @param array $autoLoginKey: the auto-login key
    * @param string $prefixId: the extension prefix id
    * @param array  Array with key/values being marker-strings/substitution values.
    * @return	string		HTML content message
    * @see init(),compile(), send()
    */
    public function processInfo (
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        \JambageCom\Agency\Domain\Tca $tcaObj,
        \JambageCom\Agency\View\Marker $markerObj,
        \JambageCom\Agency\Domain\Data $dataObj,
        \JambageCom\Agency\View\Template $template,
        $theTable,
        $autoLoginKey,
        $prefixId,
        $origArr,
        $securedArray,
        $markerArray,
        $cmd,
        $cmdKey,
        $templateCode,
        $failure,
        &$errorCode
    )
    {
        $content = false;
        $conf = $confObj->getConf();

        if ($conf['infomail'] && $conf['email.']['field']) {
            $fetch = $controlData->getFeUserData('fetch');

            if (isset($fetch) && !empty($fetch) && !$failure) {
                $pidLock = 'AND pid IN (' . ($cObj->data['pages'] ? $cObj->data['pages'] . ',' : '') . $controlData->getPid() . ')';
                $enable = $GLOBALS['TSFE']->sys_page->enableFields($theTable);
                    // Getting records
                    // $conf['email.']['field'] must be a valid field in the table!
                $DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField(
                    $theTable,
                    $conf['email.']['field'],
                    $fetch,
                    $pidLock . $enable,
                    '',
                    '',
                    '100'
                );
                $errorContent = '';
                $emailHasBeenSent = false;

                    // Processing records
                if (is_array($DBrows)) {

                    $key = 'INFOMAIL';

                    if ($theTable == 'fe_users') {
                        $key = 'SETFIXED_PASSWORD';
                        $outGoingData = array();
                        // add a r
                        $outGoingData['lost_password'] = '1';

                        $extraList = 'lost_password';
                        $result =
                            $dataObj->getCoreQuery()->DBgetUpdate(
                                $theTable,
                                $DBrows[0]['uid'],
                                $outGoingData,
                                $extraList,
                                true
                            );
                    }

                    $recipient = $DBrows[0][$conf['email.']['field']];
                    $dataObj->setDataArray($DBrows[0]);
                    $emailHasBeenSent = $this->compile(
                        $key,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        $DBrows,
                        $DBrows,
                        $securedArray,
                        trim($recipient),
                        $markerArray,
                        $cmd,
                        $cmdKey,
                        $templateCode,
                        $dataObj->getInError(),
                        $conf['setfixed.'],
                        $errorCode
                    );
                } elseif (GeneralUtility::validEmail($fetch)) {
                    $key = 'INFOMAIL_NORECORD';
                    $fetchArray = array( '0' => array('email' => $fetch));
                    $emailHasBeenSent = $this->compile(
                        $key,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $template,
                        $theTable,
                        $autoLoginKey,
                        $prefixId,
                        $fetchArray,
                        $fetchArray,
                        $securedArray,
                        $fetch,
                        $markerArray,
                        $cmd,
                        $cmdKey,
                        $templateCode,
                        $dataObj->getInError(),
                        array(),
                        $errorCode
                    );
                }

                if (
                    !$emailHasBeenSent &&
                    is_array($errorCode)
                ){
                    $errorText = $langObj->getLL($errorCode['0'], $dummy, '', false, true);
                    $errorContent = sprintf($errorText, $errorCode['1']);
                }

                if ($errorContent != '') {
                    $content = $errorContent;
                } else {
                    $subpartkey = '###TEMPLATE_' . $this->infomailPrefix . 'SENT###';
                    if ($key == 'INFOMAIL_NORECORD') {
                        $subpartkey = '###TEMPLATE_' . $this->infomailPrefix . 'NORECORD_SENT###';                        
                    }
                    $content =
                        $template->getPlainTemplate(
                            $errorCode,
                            $conf,
                            $cObj,
                            $langObj,
                            $controlData,
                            $confObj,
                            $tcaObj,
                            $markerObj,
                            $dataObj,
                            $templateCode,
                            $subpartkey,
                            $markerArray,
                            $origArr,
                            $theTable,
                            $prefixId,
                            (is_array($DBrows) ? $DBrows[0] : (is_array($fetchArray) ? $fetchArray[0] : array())),
                            $securedArray,
                            false
                        );
                }
            } else {
                $markerArray['###FIELD_email###'] = '';
                $subpartkey = '###TEMPLATE_INFOMAIL###';
                if (isset($fetch) && !empty($fetch)) {
                    $markerArray['###FIELD_email###'] = htmlspecialchars($fetch);
                }

                $content =  // lost password entry form
                    $template->getPlainTemplate(
                        $errorCode,
                        $conf,
                        $cObj,
                        $langObj,
                        $controlData,
                        $confObj,
                        $tcaObj,
                        $markerObj,
                        $dataObj,
                        $templateCode,
                        $subpartkey,
                        $markerArray,
                        $origArr,
                        $theTable,
                        $prefixId,
                        array(),
                        $securedArray,
                        true,
                        $failure
                    );
            }
        } else {
            $errorCode = array();
            $errorCode['0'] = 'internal_infomail_configuration';
        }

        return $content;
    }

    /**
    * Prepares an email message
    *
    * @param string  $key: template key
    * @param array $cObj: the cObject
    * @param array $langObj: the language object
    * @param array $controlData: the object of the control data
    * @param string $theTable: the table in use
    * @param array $autoLoginKey: the auto-login key
    * @param string $prefixId: the extension prefix id
    * @param array  $DBrows: invoked with just one row of fe_users
    * @param string  $recipient: an email or the id of a front user
    * @param array  key/values being marker-strings/substitution values.
    * @param array  $errorFieldArray: array of field with errors (former $dataObj->inError[$theField])
    * @param array  $setFixedConfig: a setfixed TS config array
    * @param array  $errorCode: array of error indices
    * @return boolean : false in case of error. The $errorCode will be filled in.
    */
    public function compile (
        $key,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $langObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        \JambageCom\Agency\Domain\Tca $tcaObj,
        \JambageCom\Agency\View\Marker $markerObj,
        \JambageCom\Agency\Domain\Data $dataObj,
        \JambageCom\Agency\View\Template $template,
        $theTable,
        $autoLoginKey,
        $prefixId,
        array $DBrows,
        array $origRows,
        array $securedArray,
        $recipient,
        array $markerArray,
        $cmd,
        $cmdKey,
        $templateCode,
        array $errorFieldArray,
        array $setFixedConfig,
        &$errorCode
    )
    {
        $errorCode = '';
        $conf = $confObj->getConf();
        $useAdditionalFields = true;
        $extKey = $controlData->getExtensionKey();
        $result = true;
        $missingSubpartArray = array();
        $userSubpartsFound = 0;
        $adminSubpartsFound = 0;
        $checkEmailSent = false;

        if (!isset($DBrows[0]) || !is_array($DBrows[0])) {
            $DBrows = $origRows;
        }
        $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);

        if (
            $this->isHTMLMailEnabled($conf)
        ) {
            if (
                (
                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('direct_mail') ||
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['enableDirectMail']
                ) &&
                isset($DBrows[0]['module_sys_dmail_html'])
            ) {
                $useHtml = $DBrows[0]['module_sys_dmail_html'];
            } else {
                $useHtml = true;
            }
        } else {
            $useHtml = false;
        }

            // Setting CSS style markers if required
        if ($useHtml) {
            $this->addCSSStyleMarkers($markerArray, $conf, $cObj);
        }

        $viewOnly = true;
        $content = array(
            'user' => array(),
            'userhtml' => array(),
            'admin' => array(),
            'adminhtml' => array(),
            'mail' => array()
        );
        $content['mail'] = '';
        $content['user']['all'] = '';
        $content['userhtml']['all'] = '';
        $content['admin']['all'] = '';
        $content['adminhtml']['all'] = '';
        $setfixedArray =
            array(
                'SETFIXED_CREATE',
                'SETFIXED_CREATE_REVIEW',
                'SETFIXED_PASSWORD',
                'SETFIXED_INVITE',
                'SETFIXED_REVIEW'
            );
        $infomailArray = array('INFOMAIL', 'INFOMAIL_NORECORD');

        if (
            ($conf['enableEmailConfirmation'] && in_array($key, $setfixedArray)) ||
            (isset($conf['email.'][$key]) && intval($conf['email.'][$key]) == 1) ||
            (
                $conf['infomail'] &&
                in_array($key, $infomailArray) &&
                    // Silently refuse to not send infomail to non-subscriber, if so requested
                !($key == 'INFOMAIL_NORECORD' && intval($conf['email.'][$key]) == '0')
            )
        ) {
            $checkEmailSent = true;
            $subpartMarker = '###' . $this->emailMarkPrefix . $key . '###';
            $content['user']['all'] =
                trim(
                    $cObj->getSubpart(
                        $templateCode,
                        $subpartMarker
                    )
                );

            if ($content['user']['all'] == '') {
                $missingSubpartArray[] = $subpartMarker;
            } else {
                $content['user']['all'] =
                    $template->removeRequired(
                        $confObj,
                        $cObj,
                        $controlData,
                        $dataObj,
                        $theTable,
                        $cmdKey,
                        $content['user']['all'],
                        $useAdditionalFields,
                        $errorFieldArray
                    );
                $userSubpartsFound++;
            }

            if ($useHtml) {
                $subpartMarker = '###' . $this->emailMarkPrefix . $key . $this->emailMarkHTMLSuffix . '###';
                $content['userhtml']['all'] = trim($cObj->getSubpart($templateCode,  $subpartMarker));

                if ($content['userhtml']['all'] == '') {
                    $missingSubpartArray[] = $subpartMarker;
                } else {
                    $content['userhtml']['all'] =
                        $template->removeRequired(
                            $confObj,
                            $cObj,
                            $controlData,
                            $dataObj,
                            $theTable,
                            $cmdKey,
                            $content['userhtml']['all'],
                            $useAdditionalFields,
                            $errorFieldArray
                        );
                    $userSubpartsFound++;
                }
            }
        }

        if (
            !isset($conf['notify.'][$key]) ||
            $conf['notify.'][$key]
        ) {
            $checkEmailSent = true;
            $subpartMarker = '###' . $this->emailMarkPrefix . $key . $this->emailMarkAdminSuffix . '###';
            $content['admin']['all'] =
                trim(
                    $cObj->getSubpart(
                        $templateCode,
                        $subpartMarker
                    )
                );

            if ($content['admin']['all'] == '') {
                    $missingSubpartArray[] = $subpartMarker;
            } else {
                $content['admin']['all'] =
                    $template->removeRequired(
                        $confObj,
                        $cObj,
                        $controlData,
                        $dataObj,
                        $theTable,
                        $cmdKey,
                        $content['admin']['all'],
                        $useAdditionalFields,
                        $errorFieldArray
                    );
                $adminSubpartsFound++;
            }

            if ($useHtml) {
                $subpartMarker =  '###' . $this->emailMarkPrefix . $key . $this->emailMarkAdminSuffix . $this->emailMarkHTMLSuffix . '###';
                $content['adminhtml']['all'] =
                    trim(
                        $cObj->getSubpart(
                            $templateCode,
                            $subpartMarker
                        )
                    );

                if ($content['adminhtml']['all'] == '') {
                    $missingSubpartArray[] = $subpartMarker;
                } else {
                    $content['adminhtml']['all'] =
                        $template->removeRequired(
                            $confObj,
                            $cObj,
                            $controlData,
                            $dataObj,
                            $theTable,
                            $cmdKey,
                            $content['adminhtml']['all'],
                            $useAdditionalFields,
                            $errorFieldArray
                        );
                    $adminSubpartsFound++;
                }
            }
        }

        $contentIndexArray = array();
        $contentIndexArray['text'] = array();
        $contentIndexArray['html'] = array();

        if ($content['user']['all']) {
            $content['user']['rec'] = $cObj->getSubpart($content['user']['all'],  '###SUB_RECORD###');
            $contentIndexArray['text'][] = 'user';
        }
        if ($content['userhtml']['all']) {
            $content['userhtml']['rec'] = $cObj->getSubpart($content['userhtml']['all'],  '###SUB_RECORD###');
            $contentIndexArray['html'][] = 'userhtml';
        }
        if ($content['admin']['all']) {
            $content['admin']['rec'] = $cObj->getSubpart($content['admin']['all'],  '###SUB_RECORD###');
            $contentIndexArray['text'][] = 'admin';
        }
        if ($content['adminhtml']['all']) {
            $content['adminhtml']['rec'] = $cObj->getSubpart($content['adminhtml']['all'],  '###SUB_RECORD###');
            $contentIndexArray['html'][] = 'adminhtml';
        }
        $bChangesOnly = ($conf['email.']['EDIT_SAVED'] == '2' && $cmd == 'edit');

        if ($bChangesOnly) {
            $keepFields = array('uid', 'pid', 'tstamp', 'name', 'first_name', 'middle_name', 'last_name', 'username');
        } else {
            $keepFields = array();
        }
        $markerArray =
            $markerObj->fillInMarkerArray(
                $markerArray,
                $DBrows[0],
                $securedArray,
                $controlData,
                $dataObj,
                $confObj,
                '',
                false
            );
        $markerObj->addLabelMarkers(
            $markerArray,
            $conf,
            $cObj,
            $langObj,
            $extKey,
            $theTable,
            $DBrows[0],
            $origRows[0],
            $securedArray,
            $keepFields,
            $controlData->getRequiredArray(),
            $dataObj->getFieldList(),
            $GLOBALS['TCA'][$theTable]['columns'],
            'email',
            $bChangesOnly
        );
        $content['user']['all'] =
            $cObj->substituteMarkerArray(
                $content['user']['all'],
                $markerArray
            );
        $content['userhtml']['all'] =
            $cObj->substituteMarkerArray(
                $content['userhtml']['all'],
                $markerArray
            );
        $content['admin']['all'] =
            $cObj->substituteMarkerArray(
                $content['admin']['all'],
                $markerArray
            );
        $content['adminhtml']['all'] =
            $cObj->substituteMarkerArray(
                $content['adminhtml']['all'],
                $markerArray
            );

        foreach ($DBrows as $k => $row) {
            $origRow = $origRows[$k];

            if (isset($origRow) && is_array($origRow)) {
                if (isset($row) && is_array($row)) {
                    $currentRow = array_merge($origRow, $row);
                } else {
                    $currentRow = $origRow;
                }
            } else {
                $currentRow = $row;
            }

            if ($bChangesOnly) {
                $mrow = array();
                foreach ($row as $field => $v) {
                    if (in_array($field, $keepFields)) {
                        $mrow[$field] = $row[$field];
                    } else {
                        if ($row[$field] != $origRow[$field]) {
                            $mrow[$field] = $row[$field];
                        } else {
                            $mrow[$field] = ''; // needed to empty the ###FIELD_...### markers
                        }
                    }
                }
            } else {
                $mrow = $currentRow;
            }

            $markerArray['###SYS_AUTHCODE###'] = $authObj->generateAuthCode($row);
            \JambageCom\Agency\Setfixed\SetfixedUrls::compute(
                $cmd,
                $prefixId,
                $cObj,
                $controlData,
                $markerArray,
                $setFixedConfig,
                $currentRow,
                $theTable,
                $conf['useShortUrls'],
                $conf['edit.']['setfixed'],
                $autoLoginKey,
                $conf['confirmType']
            );

            $markerObj->addStaticInfoMarkers(
                $markerArray,
                $langObj,
                $prefixId,
                $row,
                $viewOnly
            );

            foreach ($GLOBALS['TCA'][$theTable]['columns'] as $theField => $fieldConfig) {

                if (
                    (
                        $fieldConfig['config']['internal_type'] == 'file' &&
                        $fieldConfig['config']['allowed'] != '' &&
                        $fieldConfig['config']['uploadfolder'] != ''
                    ) ||
                    (
                        $fieldConfig['config']['foreign_table'] == 'sys_file_reference'
                    )
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
                        $row,
                        $viewOnly,
                        'email',
                        ($emailType == 'html')
                    );
                }
            }
            $markerObj->addLabelMarkers(
                $markerArray,
                $conf,
                $cObj,
                $langObj,
                $extKey,
                $theTable,
                $row,
                $origRow,
                $securedArray,
                $keepFields,
                $controlData->getRequiredArray(),
                $dataObj->getFieldList(),
                $GLOBALS['TCA'][$theTable]['columns'],
                'email',
                $bChangesOnly
            );

            foreach ($contentIndexArray as $emailType => $indexArray) {
                $fieldMarkerArray = array();
                $fieldMarkerArray =
                    $markerObj->fillInMarkerArray(
                        $fieldMarkerArray,
                        $mrow,
                        $securedArray,
                        $controlData,
                        $dataObj,
                        $confObj,
                        '',
                        false,
                        'FIELD_',
                        ($emailType == 'html')
                    );

                $tcaObj->addMarkers(
                    $fieldMarkerArray,
                    $conf,
                    $langObj,
                    $controlData,
                    $row,
                    $origRow,
                    $cmd,
                    $cmdKey,
                    $theTable,
                    $prefixId,
                    $viewOnly,
                    'email',
                    $bChangesOnly,
                    ($emailType == 'html')
                );
                $markerArray = array_merge($markerArray, $fieldMarkerArray);

                foreach ($indexArray as $index) {
                    $content[$index]['rec'] =
                        $markerObj->removeStaticInfoSubparts(
                            $content[$index]['rec'],
                            $markerArray,
                            $viewOnly
                        );

                    $content[$index]['accum'] .=
                        $cObj->substituteMarkerArray(
                            $content[$index]['rec'],
                            $markerArray
                        );
                    if ($emailType == 'text') {
                        $content[$index]['accum'] = htmlSpecialChars_decode($content[$index]['accum'], ENT_QUOTES);
                    }
                }
            }
        }

            // Substitute the markers and eliminate HTML markup from plain text versions
        if ($content['user']['all']) {
            $content['user']['final'] =
                $cObj->substituteSubpart(
                    $content['user']['all'],
                    '###SUB_RECORD###',
                    $content['user']['accum']
                );
            $content['user']['final'] =
                $template->removeHTMLComments(
                    $content['user']['final']
                );
            $content['user']['final'] =
                $template->replaceHTMLBr(
                    $content['user']['final']
                );
            $content['user']['final'] =
                $template->removeHtmlTags(
                    $content['user']['final']
                );
            $content['user']['final'] =
                $template->removeSuperfluousLineFeeds(
                    $content['user']['final']
                );
                // Remove erroneous \n from locallang file
            $content['user']['final'] = str_replace('\n', '', $content['user']['final']);
        }

        if ($content['userhtml']['all']) {
            $content['userhtml']['final'] =
                $cObj->substituteSubpart(
                    $content['userhtml']['all'],
                    '###SUB_RECORD###',
                    FrontendUtility::wrapInBaseClass(
                        $content['userhtml']['accum'],
                        $controlData->getPrefixId(),
                        $extKey
                    )
                );
                // Remove HTML comments
            $content['userhtml']['final'] = $template->removeHTMLComments($content['userhtml']['final']);
                // Remove erroneous \n from locallang file
            $content['userhtml']['final'] = str_replace('\n', '', $content['userhtml']['final']);
        }

        if ($content['admin']['all']) {
            $content['admin']['final'] =
                $cObj->substituteSubpart(
                    $content['admin']['all'], '###SUB_RECORD###', $content['admin']['accum']
                );
            $content['admin']['final'] =
                $template->removeHTMLComments($content['admin']['final']);
            $content['admin']['final'] =
                $template->replaceHTMLBr($content['admin']['final']);
            $content['admin']['final'] =
                $template->removeHtmlTags($content['admin']['final']);
            $content['admin']['final'] =
                $template->removeSuperfluousLineFeeds($content['admin']['final']);
                // Remove erroneous \n from locallang file
            $content['admin']['final'] =
                str_replace('\n', '', $content['admin']['final']);
        }

        if ($content['adminhtml']['all']) {
            $content['adminhtml']['final'] =
                $cObj->substituteSubpart(
                    $content['adminhtml']['all'],
                    '###SUB_RECORD###',
                    FrontendUtility::wrapInBaseClass(
                        $content['adminhtml']['accum'],
                        $controlData->getPrefixId(),
                        $extKey
                    )
                );
                // Remove HTML comments
            $content['adminhtml']['final'] = $template->removeHTMLComments($content['adminhtml']['final']);
                // Remove erroneous \n from locallang file
            $content['adminhtml']['final'] = str_replace('\n', '', $content['adminhtml']['final']);
        }

        if (
            \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($recipient)
        ) {
            $fe_userRec = $GLOBALS['TSFE']->sys_page->getRawRecord('fe_users', $recipient);
            $recipient = $fe_userRec['email'];
        }

            // Check if we need to add an attachment
        if (
            $conf['addAttachment'] &&
            $conf['addAttachment.']['cmd'] == $cmd &&
            $conf['addAttachment.']['sFK'] == $controlData->getFeUserData('sFK')
        ) {
            $file = ($conf['addAttachment.']['file'] ? $GLOBALS['TSFE']->tmpl->getFileName($conf['addAttachment.']['file']) : '');
        }

            // SETFIXED_REVIEW will be sent to user only if the admin part is present
        if (
            ($userSubpartsFound + $adminSubpartsFound >= 1) &&
            (
                $adminSubpartsFound >= 1 ||
                $key != 'SETFIXED_REVIEW'
            )
        ) {
            $result = $this->send(
                $conf,
                $recipient,
                $conf['email.']['admin'],
                $content['user']['final'],
                $content['userhtml']['final'],
                $content['admin']['final'],
                $content['adminhtml']['final'],
                $file
            );
        } else {
            $result = false;
            if (!empty($missingSubpartArray)) { // $conf['notify.'][$key]
                $errorCode = array();
                $errorCode['0'] = 'internal_no_subtemplate';
                $errorCode['1'] = $missingSubpartArray['0'];
            }
        }

        if (
            $checkEmailSent &&
            $result === false &&
            empty($errorCode)
        ) {
            $errorCode = array();
            $errorCode['0'] = 'internal_email_not_sent';
            $errorCode['1'] = $recipient;
        }

        return $result;
    } // compile

    /**
    * Dispatches the email messsage
    *
    * @param string  $recipient: email address
    * @param string  $admi+n: email address
    * @param string  $content: plain content for the recipient
    * @param string  $HTMLcontent: HTML content for the recipient
    * @param string  $adminContent: plain content for admin
    * @param string  $adminContentHTML: HTML content for admin
    * @param string  $fileAttachment: file name
    * @return void
    */
    public function send (
        $conf,
        $recipient,
        $admin,
        $content = '',
        $contentHTML = '',
        $adminContent = '',
        $adminContentHTML = '',
        $fileAttachment = ''
    )
    {
        $result = false;

        // Send mail to admin
        if (
            $admin &&
                (
                    $adminContent != '' ||
                    $adminContentHTML != ''
                )
        ) {
            if (isset($conf['email.']['replyTo'])) {
                if ($conf['email.']['replyTo'] == 'user') {
                    $replyTo = $recipient;
                } else {
                    $replyTo = $conf['email.']['replyTo'];
                }
            }

            // Send mail to the admin
            $result = $this->sendHTML(
                $adminContentHTML,
                $adminContent,
                $admin,
                $conf['email.']['from'],
                $conf['email.']['fromName'],
                $replyTo,
                ''
            );
        }

        // Send mail to user
        if (
            $recipient &&
                (
                    $content != '' ||
                    $contentHTML != ''
                )
        ) {
            // Send mail to the front end user
            $result = $this->sendHTML(
                $contentHTML,
                $content,
                $recipient,
                $conf['email.']['from'],
                $conf['email.']['fromName'],
                $conf['email.']['replyToAdmin'] ? $conf['email.']['replyToAdmin'] : '',
                $fileAttachment
            );
        }

        return $result;
    }

    /**
    * Adds CSS styles marker to a marker array for substitution in an HTML email message
    *
    * @param array  $markerArray: the input marker array
    * @return void
    */
    public function addCSSStyleMarkers (
        array &$markerArray,
        $conf,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
    )
    {
        $markerArray['###CSS_STYLES###'] = '	/*<![CDATA[*/
';
        $fileResource = $cObj->fileResource($conf['email.']['HTMLMailCSS']);
        $markerArray['###CSS_STYLES###'] .= $fileResource;
        $markerArray['###CSS_STYLES###'] .= '
/*]]>*/';

        return $markerArray;
    }	// addCSSStyleMarkers

    /**
    * Invokes the HTML mailing class
    *
    * @param string  $content['HTML']: HTML version of the message
    * @param string  $PLAINContent: plain version of the message
    * @param string  $recipient: email address
    * @param string  $fromEmail: email address
    * @param string  $fromName: name
    * @param string  $replyTo: email address
    * @param string  $fileAttachment: file name
    * @return void
    */
    public function sendHTML (
        $HTMLContent,
        $PLAINContent,
        $recipient,
        $fromEmail,
        $fromName,
        $replyTo = '',
        $fileAttachment = ''
    )
    {
        $result = false;

        if (
            trim($recipient) &&
            (
                trim($HTMLContent) ||
                trim($PLAINContent)
            )
        ) {
            $defaultSubject = 'Agency Registration';
            $result = MailUtility::send(
                $recipient,
                $subject,
                $PLAINContent,
                $HTMLContent,
                $fromEmail,
                $fromName,
                $fileAttachment,
                '',
                '',
                '',
                $replyTo,
                $this->getExtensionKey(),
                'sendMail',
                $defaultSubject
            );
        }

        return $result;
    }
}

