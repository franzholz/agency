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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Div2007\Utility\HtmlUtility;
use JambageCom\Agency\Domain\Tca;
use JambageCom\Agency\Domain\Tables;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Request\Parameters;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use SJBR\StaticInfoTables\PiBaseApi;
use JambageCom\Agency\Api\Url;
use JambageCom\Div2007\Database\CoreQuery;
use JambageCom\Agency\Domain\Data;
use JambageCom\Agency\View\Marker;
use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Utility\LocalizationUtility;
use JambageCom\Agency\View\CreateView;
use JambageCom\Agency\View\EditView;
use JambageCom\Agency\View\DeleteView;
use JambageCom\Agency\View\Template;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Constants\Extension;



class InitializationController implements SingletonInterface {


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
        &$tcaObj,
        &$languageObj,
        &$markerObj,
        &$errorMessage,
        AbstractPlugin $pibaseObj,
        ContentObjectRenderer $cObj,
        ConfigurationStore $confObj,
        $conf,
        $theTable,
        $adminFieldList,
        $buttonLabelsList,
        $otherLabelsList
    )
    {

        $result = true;
        HtmlUtility::generateXhtmlFix();

        $tcaObj = GeneralUtility::makeInstance(Tca::class);
        $confObj->init($conf);
        $tcaObj->init($pibaseObj->extKey, $theTable);
        $tablesObj = GeneralUtility::makeInstance(Tables::class);
        $tablesObj->init($theTable);
        $authObj = GeneralUtility::makeInstance(Authentication::class);
        $authObj->init($confObj); // config is changed
        $controlData = GeneralUtility::makeInstance(Parameters::class);
        $controlData->init(
            $confObj,
            $pibaseObj->prefixId,
            $pibaseObj->extKey,
            $pibaseObj->piVars,
            $theTable
        );

        if (
            ExtensionManagementUtility::isLoaded(
                STATIC_INFO_TABLES_EXT
            )
        ) {
                // Initialise static info library
            if (class_exists('SJBR\\StaticInfoTables\\PiBaseApi')) {
                $staticInfoObj = GeneralUtility::makeInstance(PiBaseApi::class);
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

        $urlObj = GeneralUtility::makeInstance(Url::class);
        $coreQuery = GeneralUtility::makeInstance(
                CoreQuery::class,
                static::getTypoScriptFrontendController()
            );
        $dataObj =
            GeneralUtility::makeInstance(
                Data::class,
                $coreQuery
            );
        $markerObj = GeneralUtility::makeInstance(Marker::class);
        $actionController = GeneralUtility::makeInstance(ActionController::class);

        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageObj->init(
            Extension::KEY,
            $conf['_LOCAL_LANG.'] ?? ''
        );
        $languageObj->loadLocalLang(
            'EXT:' . Extension::KEY . DIV2007_LANGUAGE_SUBPATH . 'locallang.xlf',
            false
        );        
        $tmpText = $languageObj->getLabel('unsupported');
        if ($tmpText == '') {
            $result = false;
        }

        $languageObj->setSalutation($conf['salutation']);
        $urlObj->init(
            $cObj,
            $controlData->getPiVars(),
            $controlData->getPrefixId()
        );

        if ($result !== false) {
            if ($pibaseObj->extKey != Extension::KEY) {
                $filename = LocalizationUtility::getFilename();
                $filename = 'EXT:' . $pibaseObj->extKey . $filename;

                    // Static Methods for Extensions for fetching the texts of agency
                $languageObj->loadLocalLang(
                    $filename,
                    false
                );
            } // otherwise the labels from agency need not be included, because this has been done in TYPO3 pibase

            $templateFile = $conf['templateFile'];
            $templateCode = FrontendUtility::fileResource($templateFile);
            if (
                (!$templateFile || empty($templateCode))
            ) {
                $errorText = $languageObj->getLabel(
                        'internal_no_template'
                    );
                $errorMessage = sprintf($errorText, $templateFile, 'plugin.tx_' . $pibaseObj->extKey . '.templateFile');
            }

            if ($controlData->isTokenValid()) {
                $actionController->init(
                    $confObj,
                    $languageObj,
                    $cObj,
                    $controlData,
                    $urlObj
                );

                $dataObj->init(
                    $languageObj,
                    $tcaObj,
                    $actionController,
                    $theTable,
                    $templateCode,
                    $controlData,
                    $staticInfoObj
                );

                $resultInit = $actionController->init2( // only here the $conf is changed
                    $confObj,
                    $staticInfoObj,
                    $theTable,
                    $controlData,
                    $dataObj,
                    $tcaObj,
                    $adminFieldList,
                    $origArray,
                    $errorMessage
                );

                if ($resultInit === false) {
                    return false;
                }
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
                $errorMessage = $languageObj->getLabel('internal_invalid_token');
            }
        } else {
            $errorMessage = $languageObj->getLabel('internal_init_language');
        }

        return $result;
    } // init


    public function main (
        AbstractPlugin $pibaseObj,
        ContentObjectRenderer $cObj,
        $content,
        $conf,
        $theTable,
        $adminFieldList = 'username,password,name,disable,usergroup,by_invitation,tx_agency_password,lost_password',
        $buttonLabelsList = '',
        $otherLabelsList = ''
    )
    {
        $staticInfoObj = null;
        $dataObj = null; // object of type tx_agency_data
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $errorMessage = '';
        $origArray = [];

        $success = $this->init(
            $controlData,
            $origArray,
            $staticInfoObj,
            $dataObj,
            $actionController,
            $tcaObj,
            $languageObj,
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
            $setfixedObj = GeneralUtility::makeInstance(Setfixed::class);
            $displayObj = GeneralUtility::makeInstance(CreateView::class);
            $editView = GeneralUtility::makeInstance(EditView::class);
            $deleteView = GeneralUtility::makeInstance(DeleteView::class);
            $template = GeneralUtility::makeInstance(Template::class);
            $content = $actionController->doProcessing(
                $cObj,
                $confObj,
                $setfixedObj,
                $languageObj,
                $template,
                $displayObj,
                $editView,
                $deleteView,
                $controlData,
                $dataObj,
                $tcaObj,
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
            $xhtmlFix = HtmlUtility::determineXhtmlFix();
            $content = '<em>Internal error in ' . $pibaseObj->extKey . '!</em><br ' . $xhtmlFix . '> Maybe you forgot to include the basic template file under "include statics from extensions".';
        }

        $content =
            FrontendUtility::wrapInBaseClass(
                $content,
                $pibaseObj->prefixId,
                $pibaseObj->extKey
            );
        return $content;
    }


    /**
     * @return TypoScriptFrontendController
     */
    static protected function getTypoScriptFrontendController ()
    {
        return $GLOBALS['TSFE'];
    }
}

