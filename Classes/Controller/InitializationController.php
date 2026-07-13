<?php

declare(strict_types=1);

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
* @author   Kasper Skårhøj <kasperYYYY@typo3.com>
* @author   Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author   Franz Holzinger <franz@ttproducts.de>
* @maintainer   Franz Holzinger <franz@ttproducts.de>
*
*
*/

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use Psr\Http\Message\ServerRequestInterface;

use JambageCom\Div2007\Database\CoreQuery;
use JambageCom\Div2007\Utility\HtmlUtility;
use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\Agency\Configuration\ConfigurationStore;

use JambageCom\Agency\Api\Localization;
use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Api\Url;
use JambageCom\Agency\Constants\Extension;
use JambageCom\Agency\Database\Tca;
use JambageCom\Agency\Database\Tables;
use JambageCom\Agency\Database\Data;
use JambageCom\Agency\Domain\Model\FrontendUser;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Utility\LocalizationUtility;
use JambageCom\Agency\View\CreateView;
use JambageCom\Agency\View\EditView;
use JambageCom\Agency\View\DeleteView;
use JambageCom\Agency\View\Marker;
use JambageCom\Agency\View\Template;



class InitializationController implements SingletonInterface
{
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
    public function init(
        &$controlData,
        array &$origArray,
        &$dataObj,
        &$actionController,
        &$tcaObj,
        &$languageObj,
        &$markerObj,
        &$errorMessage,
        ServerRequestInterface $request,
        RegisterPluginController $pluginController,
        ContentObjectRenderer $cObj,
        ConfigurationStore $confObj,
        $conf,
        $theTable,
        $adminFieldList,
        $buttonLabelsList,
        $otherLabelsList
    ) {
        $result = true;
        HtmlUtility::generateXhtmlFix();
        $useStaticInfo =
            ExtensionManagementUtility::isLoaded('static_info_tables');
        $tcaObj = GeneralUtility::makeInstance(Tca::class);
        $tcaObj->init($useStaticInfo, $conf);
        $confObj->init($conf);
        $tablesObj = GeneralUtility::makeInstance(Tables::class);
        $tablesObj->init($theTable);
        $authObj = GeneralUtility::makeInstance(Authentication::class);
        $authObj->init($confObj); // config is changed
        $controlData = GeneralUtility::makeInstance(Parameters::class);
        $controlData->init(
            $origArray,
            $confObj,
            $request,
            $pluginController->prefixId,
            $pluginController->extKey,
            $pluginController->piVars,
            $theTable
        );

        $urlObj = GeneralUtility::makeInstance(Url::class);
        $dataObj =
            GeneralUtility::makeInstance(
                Data::class
            );
        $markerObj = GeneralUtility::makeInstance(Marker::class);
        $actionController = GeneralUtility::makeInstance(ActionController::class);

        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageObj->init(
            Extension::KEY,
            $conf['_LOCAL_LANG.'] ?? '',
            $request
        );
        $languageObj->loadLocalLang(
            'EXT:' . Extension::KEY . DIV2007_LANGUAGE_SUBPATH . 'locallang.xlf',
            false
        );
        $languageObj->setSalutation($conf['salutation']);
        $urlObj->init(
            $cObj,
            $controlData->getPiVars(),
            $controlData->getPrefixId()
        );

        if ($result !== false) {
            if ($pluginController->extKey != Extension::KEY) {
                $filename = LocalizationUtility::getFilename();
                $filename = 'EXT:' . $pluginController->extKey . $filename;

                // Static Methods for Extensions for fetching the texts of agency
                $languageObj->loadLocalLang(
                    $filename,
                    false
                );
            } // otherwise the labels from agency need not be included, because this has been done in TYPO3 pibase

            $templateFile = $conf['templateFile'];
            $templateCode = FrontendUtility::fileResource($templateFile, '', false);
            if (
                (!$templateFile || empty($templateCode))
            ) {
                $errorText = $languageObj->getLabel(
                    'internal_no_template'
                );
                $errorMessage = sprintf($errorText, $templateFile, 'plugin.tx_' . $pluginController->extKey . '.templateFile');
            }

            if ($controlData->isTokenValid()) {
                $actionController->init(
                    $confObj,
                    $languageObj,
                    $cObj,
                    $controlData,
                    $urlObj
                );
                $coreQuery = GeneralUtility::makeInstance(
                    CoreQuery::class,
                    $request->getAttribute('frontend.controller')
                );

                $dataObj->init(
                    $coreQuery,
                    $tcaObj,
                    $actionController,
                    $theTable,
                    $templateCode,
                    $controlData
                );

                $resultInit = $actionController->init2( // only here the $conf is changed
                    $adminFieldList,
                    $origArray,
                    $errorMessage,
                    $dataObj,
                    $confObj,
                    $useStaticInfo,
                    $theTable,
                    $controlData,
                    $tcaObj
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
                    $useStaticInfo,
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


    public function main(
        RegisterPluginController $pluginController,
        ServerRequestInterface $request,
        ContentObjectRenderer $cObj,
        $content,
        $conf,
        $theTable,
    ) {
        $adminFieldList = 'username,password,name,disable,usergroup,by_invitation,tx_agency_password,lost_password';
        $buttonLabelsList = '';
        $otherLabelsList = '';
        $dataObj = null; // object of type tx_agency_data
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $errorMessage = '';
        $origArray = [];
        $controlData = null;

        $success = $this->init(
            $controlData,
            $origArray,
            $dataObj,
            $actionController,
            $tcaObj,
            $languageObj,
            $markerObj,
            $errorMessage,
            $request,
            $pluginController,
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
                ExtensionManagementUtility::isLoaded('static_info_tables'),
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
        } elseif ($success === false) {
            $xhtmlFix = HtmlUtility::determineXhtmlFix();
            $content = '<em>Internal error in ' . $pluginController->extKey . '!</em><br ' . $xhtmlFix . '> Maybe you forgot to include the basic template file under "include statics from extensions".';
        }

        $content =
            FrontendUtility::wrapInBaseClass(
                $content,
                $pluginController->prefixId,
                $pluginController->extKey
            );

        return $content;
    }
}
