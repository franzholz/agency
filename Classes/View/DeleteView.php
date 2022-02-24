<?php

namespace JambageCom\Agency\View;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Stanislas Rolland (typo3(arobas)sjbr.ca)
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


class DeleteView {

    /**
    * This is basically the preview display of delete
    *
    * @param array $cObj: the cObject
    * @param array $languageObj: the language object
    * @param array $controlData: the object of the control data
    * @return string  the template with substituted markers
    */
    public function render (
        &$errorCode,
        array $markerArray,
        $conf,
        $prefixId,
        $extensionKey,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Api\Localization $languageObj,
        \JambageCom\Agency\Request\Parameters $controlData,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        \JambageCom\Agency\Domain\Tca $tcaObj,
        \JambageCom\Agency\View\Marker $markerObj,
        \JambageCom\Agency\Domain\Data $dataObj,
        \JambageCom\Agency\View\Template $template,
        $theTable,
        array $dataArray,
        array $origArray,
        array $securedArray,
        $token,
        $setFixedKey,
        array $fD
    )
    {
        $aCAuth = false;
        $xhtmlFix = \JambageCom\Div2007\Utility\HtmlUtility::determineXhtmlFix();

        if ($conf['delete']) {
            if (!isset($markerArray['###HIDDENFIELDS###'])) {
                $markerArray['###HIDDENFIELDS###'] = '';
            }
            $templateCode = $dataObj->getTemplateCode();
            $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);

            // If deleting is enabled
            $origArray =
                $GLOBALS['TSFE']->sys_page->getRawRecord(
                    $theTable,
                    $dataObj->getRecUid()
                );

            if (is_array($origArray)) {
                $aCAuth =
                    $authObj->aCAuth(
                        $origArray,
                        $conf['setfixed.']['DELETE.']['_FIELDLIST']
                    );
            }

            if (
                ($theTable == 'fe_users' && \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()) ||
                $aCAuth
            ) {
                // Must be logged in OR be authenticated by the aC code in order to delete

                // If the recUid selects a record.... (no check here)
                if (is_array($origArray)) {
                    $bMayEdit =
                        $dataObj->getCoreQuery()->DBmayFEUserEdit(
                            $theTable,
                            $origArray,
                            $GLOBALS['TSFE']->fe_user->user,
                            $conf['allowedGroups'] ?? '',
                            $conf['fe_userEditSelf'] ?? ''
                        );

                    if ($aCAuth || $bMayEdit) {
//                         $markerArray = $markerObj->getArray();
                        // Display the form, if access granted.

                        $markerArray['###HIDDENFIELDS###'] .=
                            '<input type="hidden" name="' .
                            $prefixId . '[rU]" value="' .
                            $dataObj->getRecUid() .
                            '"' . $xhtmlFix . '>';
                        $tokenParameter = $controlData->getTokenParameter();
                        $markerArray['###BACK_URL###'] =
                            (
                                $controlData->getBackURL() ?
                                    $controlData->getBackURL() :
                                    $cObj->getTypoLink_URL(
                                        $conf['loginPID'] . ',' . $GLOBALS['TSFE']->type
                                    )
                            ) . $tokenParameter;
                        $markerObj->addGeneralHiddenFieldsMarkers(
                            $markerArray,
                            'delete',
                            $token,
                            $setFixedKey,
                            $fD
                        );
                        $markerObj->setArray($markerArray);
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
                            $templateCode,
                            '###TEMPLATE_DELETE_PREVIEW###',
                            $markerArray,
                            $dataArray,
                            $theTable,
                            $prefixId,
                            $origArray,
                            $securedArray
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
                            $templateCode,
                            '###TEMPLATE_NO_PERMISSIONS###',
                            $markerArray,
                            $dataArray,
                            $theTable,
                            $prefixId,
                            $origArray,
                            $securedArray
                        );
                    }
                }
            } else {
                // Finally this is if there is no login user. This must tell that you must login. Perhaps link to a page with create-user or login information.
                if ( $theTable == 'fe_users' ) {
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
                        $templateCode,
                        '###TEMPLATE_AUTH###',
                        $markerArray,
                        $origArray,
                        $theTable,
                        $prefixId,
                        array(),
                        $securedArray
                    );
                } else {
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
                        $templateCode,
                        '###TEMPLATE_NO_PERMISSIONS###',
                        $markerArray,
                        $origArray,
                        $theTable,
                        $prefixId,
                        array(),
                        $securedArray
                    );
                }
            }
        } else {
            $content .= 'Delete-option is not set in TypoScript';
        }
        return $content;
    }	// render
}

