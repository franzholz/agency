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
* Front End creating/editing/deleting records authenticated by fe_user login.
* A variant restricted to front end user self-registration and profile maintenance, with a number of enhancements (see the manual).
*
* @author   Kasper Skårhøj <kasperYYYY@typo3.com>
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
* @maintainer   Franz Holzinger <franz@ttproducts.de>
*
*
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;


class InitializationController implements \TYPO3\CMS\Core\SingletonInterface {


    /**
    * Creates and initializes all component classes
    *
    * @param object pi_base object
    * @param array $conf: the configuration of the cObj
    * @param string $theTable: the table in use
    * @param string $adminFieldList: list of table fields that are considered reserved for administration purposes
    * @param string $buttonLabelsList: a list of button label names
    * @param string $otherLabelsList: a list of other label names
    * @return boolean true, if initialization was successful, false otherwise
    */
    public function init (
        &$controlData,
        array &$origArray,
        &$staticInfoObj,
        &$dataObj,
        &$actionController,
        &$langObj,
        &$markerObj,
        &$errorMessage,
        \TYPO3\CMS\Frontend\Plugin\AbstractPlugin $pibaseObj,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        \JambageCom\Agency\Configuration\ConfigurationStore $confObj,
        $conf,
        $theTable,
        $adminFieldList,
        $buttonLabelsList,
        $otherLabelsList
    ) {
        $result = true;

        \JambageCom\Div2007\Utility\HtmlUtility::generateXhtmlFix();

        $tcaObj = GeneralUtility::makeInstance(\JambageCom\Agency\Domain\Tca::class);
        $confObj->init($conf);
        $tcaObj->init($pibaseObj->extKey, $theTable);
        $tablesObj = GeneralUtility::makeInstance(\JambageCom\Agency\Domain\Tables::class);
        $tablesObj->init($theTable);
        $authObj = GeneralUtility::makeInstance(\JambageCom\Agency\Security\Authentication::class);
        $authObj->init($confObj); // config is changed
        $controlData = GeneralUtility::makeInstance(\JambageCom\Agency\Request\Parameters::class);
        $controlData->init(
            $confObj,
            $pibaseObj->prefixId,
            $pibaseObj->extKey,
            $pibaseObj->piVars,
            $theTable
        );

        if ($pibaseObj->extKey != AGENCY_EXT) {
                    // Static Methods for Extensions for fetching the texts of agency
                \tx_div2007_alpha5::loadLL_fh002(
                    $pibaseObj,
                    'EXT:' . AGENCY_EXT . '/pi/locallang.xml',
                    false
                );
        } // otherwise the labels from agency need not be included, because this has been done in TYPO3 pibase

        if (
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(
                STATIC_INFO_TABLES_EXT
            )
        ) {
                // Initialise static info library
            if (class_exists('SJBR\\StaticInfoTables\\PiBaseApi')) {
                $staticInfoObj = GeneralUtility::makeInstance(\SJBR\StaticInfoTables\PiBaseApi::class);
            }

            if (
                is_object($staticInfoObj) &&
                (
                    !method_exists($staticInfoObj, 'needsInit') ||
                    $staticInfoObj->needsInit()
                )
            ) {
                $staticInfoObj->init();
            }
        }

        $langObj = GeneralUtility::makeInstance(\JambageCom\Agency\Api\Localization::class);
        $urlObj = GeneralUtility::makeInstance(\JambageCom\Agency\Api\Url::class);
        $coreQuery = GeneralUtility::makeInstance(
                \JambageCom\Div2007\Database\CoreQuery::class,
                $this->getTypoScriptFrontendController()
            );
        $dataObj =
            GeneralUtility::makeInstance(
                \JambageCom\Agency\Domain\Data::class,
                $coreQuery
            );
        $markerObj = GeneralUtility::makeInstance(\JambageCom\Agency\View\Marker::class);
        $this->setfixedObj = GeneralUtility::makeInstance(\JambageCom\Agency\Controller\SetFixed::class);
        $actionController = GeneralUtility::makeInstance(\JambageCom\Agency\Controller\ActionController::class);

        $urlObj->init(
            $cObj,
            $controlData->getPiVars(),
            $controlData->getPrefixId()
        );

        $langObj->init1(
            $pibaseObj,
            $cObj,
            $conf,
            'pi/class.tx_agency_pi_base.php',
            $pibaseObj->extKey
        );
        $result = $langObj->loadLL();
        if ($result !== false) {
            $templateFile = $conf['templateFile'];
            $templateCode = $cObj->fileResource($templateFile);
            if (
                (!$templateFile || empty($templateCode))
            ) {
                $errorText = \tx_div2007_alpha5::getLL_fh002(
                        $langObj,
                        'internal_no_template'
                    );
                $errorMessage = sprintf($errorText, $templateFile, 'plugin.tx_' . $pibaseObj->extKey . '.templateFile');
            }

            if ($controlData->isTokenValid()) {
                $actionController->init(
                    $confObj,
                    $langObj,
                    $cObj,
                    $controlData,
                    $tcaObj,
                    $urlObj
                );

                $dataObj->init(
                    $langObj,
                    $tcaObj,
                    $actionController,
                    $theTable,
                    $templateCode,
                    $controlData,
                    $staticInfoObj
                );

                $actionController->init2( // only here the $conf is changed
                    $confObj,
                    $staticInfoObj,
                    $theTable,
                    $controlData,
                    $dataObj,
                    $adminFieldList,
                    $origArray
                );
                $dataObj->setOrigArray($origArray);
                $uid = $dataObj->getRecUid();

                $markerObj->init(
                    $confObj,
                    $dataObj,
                    $tcaObj,
                    $controlData,
                    $controlData->getBackURL(),
                    $controlData->getExtensionKey(),
                    $controlData->getPrefixId(),
                    $controlData->getTable(),
                    $urlObj,
                    $staticInfoObj,
                    $uid,
                    $controlData->readToken()
                );

                if ($buttonLabelsList != '') {
                    $markerObj->setButtonLabelsList($buttonLabelsList);
                }

                if ($otherLabelsList != '') {
                    $markerObj->addOtherLabelsList($otherLabelsList);
                }
            } else {
                $result = false;
                $errorMessage = $langObj->getLL('internal_invalid_token');
            }
        } else {
            $errorMessage = $langObj->getLL('internal_init_language');
        }

        return $result;
    } // init


    public function main (
        \TYPO3\CMS\Frontend\Plugin\AbstractPlugin $pibaseObj,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        $content,
        $conf,
        $theTable,
        $adminFieldList = 'username,password,name,disable,usergroup,by_invitation,tx_agency_password,lost_password',
        $buttonLabelsList = '',
        $otherLabelsList = ''
    ) {
        $staticInfoObj = null;
        $dataObj = null; // object of type tx_agency_data
        $confObj = GeneralUtility::makeInstance(\JambageCom\Agency\Configuration\ConfigurationStore::class);
        $errorMessage = '';
        $origArray = array();

        $success = $this->init(
            $controlData,
            $origArray,
            $staticInfoObj,
            $dataObj,
            $actionController,
            $langObj,
            $markerObj,
            $errorMessage,
            $pibaseObj,
            $cObj,
            $confObj,
            $conf,
            $theTable,
            $adminFieldList,
            $buttonLabelsList,
            $otherLabelsList
        );
        $cmd = $controlData->getCmd();
        $cmdKey = $controlData->getCmdKey();
        $templateCode = $dataObj->getTemplateCode();

        if ($success) {
            $displayObj = GeneralUtility::makeInstance(\JambageCom\Agency\View\CreateView::class);
            $editView = GeneralUtility::makeInstance(\JambageCom\Agency\View\EditView::class);
            $deleteView = GeneralUtility::makeInstance(\JambageCom\Agency\View\DeleteView::class);
            $template = GeneralUtility::makeInstance(\JambageCom\Agency\View\Template::class);
            $content = $actionController->doProcessing(
                $pibaseObj->cObj,
                $confObj,
                $this->setfixedObj,
                $langObj,
                $template,
                $displayObj,
                $editView,
                $deleteView,
                $controlData,
                $dataObj,
                $markerObj,
                $staticInfoObj,
                $theTable,
                $cmd,
                $cmdKey,
                $origArray,
                $templateCode,
                $errorMessage
            );
        }

        if ($errorMessage) {
            $content = $errorMessage;
        } else if ($success === false) {
            $content = '<em>Internal error in ' . $pibaseObj->extKey . '!</em><br /> Maybe you forgot to include the basic template file under "include statics from extensions".';
        }

        $content =
            \tx_div2007_alpha5::wrapInBaseClass_fh002(
                $content,
                $pibaseObj->prefixId,
                $pibaseObj->extKey
            );
        return $content;
    }


    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    static protected function getTypoScriptFrontendController ()
    {
        return $GLOBALS['TSFE'];
    }
}

