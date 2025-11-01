<?php

declare(strict_types=1);

namespace JambageCom\Agency\Hooks;

/*
*  Copyright notice
*
*  (c) 2017 Stanislas Rolland (typo3(arobas)sjbr.ca)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Controller\Setfixed;
use JambageCom\Agency\Database\Data;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\View\Marker;



/**
* Example of hooks for extension Front End User Registration (sr_feuser_register)
*/
class RegistrationProcessHooks
{
    protected $hasBeenInitialised = false;

    public function init(
        Data $dataObject
    ): void {
        $this->hasBeenInitialised = true;
    }

    public function needsInit()
    {
        return !$this->hasBeenInitialised;
    }

    /**
    * @param string $cmdKey: the cmd being processed
    * @param array $conf: the plugin configuration
    */
    public function registrationProcess_beforeConfirmCreate(
        $theTable,
        array $dataArray,
        Parameters $parameters,
        $cmdKey,
        ConfigurationStore $confObj
    ): void {
        $conf = $confObj->getConf();

        // in the case of this hook, the record array is passed by reference
        // in this example hook, we generate a username based on the first and last names of the user
        if ($parameters->getFeUserData('preview') && $conf[$cmdKey . '.']['generateUsername']) {
            $firstName = trim($dataArray['first_name']);
            $lastName = trim($dataArray['last_name']);
            $name = trim($dataArray['name']);
            if ((!$firstName || !$lastName) && $name) {
                $nameArray = GeneralUtility::trimExplode(' ', $name);
                $firstName = ($firstName ?: $nameArray[0]);
                $lastName = ($lastName ?: $nameArray[1]);
            }
            $dataArray['username'] = substr(strtolower($firstName), 0, 5) . substr(strtolower($lastName), 0, 5);
            $DBrows = PageRepository::getRecordsByField($theTable, 'username', $dataArray['username'], 'LIMIT 1');
            $counter = 0;
            while($DBrows) {
                $counter = $counter + 1;
                $DBrows = PageRepository::getRecordsByField($theTable, 'username', $dataArray['username'] . $counter, 'LIMIT 1');
            }
            if ($counter) {
                $dataArray['username'] = $dataArray['username'] . $counter;
            }
        }
    }

    public function registrationProcess_afterSaveEdit(
        $theTable,
        array $dataArray,
        array $origArray,
        $token,
        array &$newRow,
        $cmd,
        $cmdKey,
        $pid,
        $fieldList,
        Data $pObj
    ): void {
    }

    public function registrationProcess_beforeSaveDelete(
        Parameters $parameters,
        $origArray,
        Data $pObj
    ): void {
    }

    public function registrationProcess_afterSaveCreate(
        Parameters $parameters,
        $theTable,
        array $dataArray,
        array $origArray,
        $token,
        array &$newRow,
        $cmd,
        $cmdKey,
        $pid,
        $extraList,
        Data $pObj
    ): void {
    }

    public function confirmRegistrationClass_preProcess(
        Parameters $parameters,
        $theTable,
        array $row,
        $newFieldList,
        SetFixed $pObj,
        array &$errorCode
    ): void {
        // in the case of this hook, the record array is passed by reference
        // you may not see this echo if the page is redirected to auto-login
    }

    public function confirmRegistrationClass_postProcess(
        Parameters $parameters,
        $theTable,
        array $row,
        array $currArr,
        array $origArray,
        SetFixed $pObj
    ): void {
        // you may not see this echo if the page is redirected to auto-login
    }

    /**
    * Add some markers to the current marker array
    *
    * @param array $markerArray: reference to the marker array
    * @param Parameters $controlData: the parameter controlling object
    * @param array $confObj: the plugin configuration object
    * @param Marker invoking marker object
    */
    public function addGlobalMarkers(
        array &$markerArray,
        Parameters $controlData,
        ConfigurationStore $confObj,
        Marker $markerObject
    ): void {
        // add your global markers to the $markerArray here
    }
}
