<?php

namespace JambageCom\Agency\Domain;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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
 * setup configuration functions. former class tx_agency_lib_tables
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use JambageCom\Agency\Domain\Table\FeUsers;
use JambageCom\Agency\Domain\Table\Setfixed;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

use JambageCom\Agency\Constants\Extension;

class Tables implements SingletonInterface
{
    public $tableClassArray = [];
    public $tablename;

    public function init($tablename): void
    {
        $this->tablename = $tablename;
        if ($tablename == 'fe_users') {
            $this->tableClassArray['address'] = FeUsers::class;
        } else {
            $this->tableClassArray['address'] = Setfixed::class;
        }
    }   // init

    public function getTableClassArray()
    {
        return $this->tableClassArray;
    }

    public function setTableClassArray($tableClassArray): void
    {
        $this->tableClassArray = $tableClassArray;
    }

    public function getTableClass(
        $functablename,
        $bView = false
    ) {
        $result = '';
        if ($functablename) {
            $result = $this->tableClassArray[$functablename] . ($bView ? '_view' : '');
        }
        return $result;
    }

    public function get(
        $functablename,
        $bView = false
    ) {
        $classNameArray = [];
        $tableObjArray = [];

        $classNameArray['model'] = $this->getTableClass($functablename, false);
        if ($bView) {
            $classNameArray['view'] = $this->getTableClass($functablename, true);
        }

        if (!$classNameArray['model'] || $bView && !$classNameArray['model']) {
            debug('Error in ' . Extension::KEY . '. No class found after calling function tx_agency_lib_tables::get with parameters "' . $functablename . '", ' . $bView . '.', 'internal error'); // keep this
            return 'ERROR';
        }

        foreach ($classNameArray as $k => $className) {
            if ($className != 'skip') {
                if (strpos($className, ':') === false) {
                    // nothing
                } else {
                    [$extKey, $className] = GeneralUtility::trimExplode(':', $className, true);

                    if (!ExtensionManagementUtility::isLoaded($extKey)) {
                        debug('Error in ' . Extension::KEY . '. No extension "' . $extKey . '" has been loaded to use class class.' . $className . '.', 'internal error'); // keep this
                        continue;
                    }
                }
                $tableObj[$k] = GeneralUtility::makeInstance($className); // fetch and store it as persistent object
            }
        }

        if (isset($tableObj['model']) && is_object($tableObj['model'])) {
            if ($tableObj['model']->needsInit()) {
                $tableObj['model']->init(
                    $functablename,
                    $this->tablename
                );
            }
        } else {
            debug('Object for \'' . $functablename . '\' has not been found.', 'internal error in ' . Extension::KEY); // keep this
        }

        if (
            isset($tableObj['view']) &&
            is_object($tableObj['view']) &&
            isset($tableObj['model']) &&
            is_object($tableObj['model'])
        ) {
            // nothing yet
        }

        return ($bView ? $tableObj['view'] : $tableObj['model']);
    }
}
