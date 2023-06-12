<?php

namespace JambageCom\Agency\Domain\Table;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2018 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * base class for all database table classes
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage agency
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class Base {
    public $functablename;
    public $tablename;
    public $fieldClassArray = []; // must be overridden
    public $hasBeenInitialised = false;

    public function init ($functablename, $tablename)
    {
        $this->setFuncTablename($functablename);
        $this->setTablename($tablename);
        $this->hasBeenInitialised = true;
    }

    public function needsInit ()
    {
        return !$this->hasBeenInitialised;
    }

    public function getFieldClass ($fieldname)
    {
        $class = '';
        $tablename = $this->getTablename();

        if (
            $fieldname &&
            isset($GLOBALS['TCA'][$tablename]['columns'][$fieldname]) &&
            is_array($GLOBALS['TCA'][$tablename]['columns'][$fieldname]) &&
            isset($this->fieldClassArray[$fieldname])
        ) {
            $class = $this->fieldClassArray[$fieldname];
        }

        return $class;
    }

    public function getFieldObj ($fieldname)
    {
        $result = null;
        $class = $this->getFieldClass($fieldname);

        if ($class) {
            $result = $this->getObj($class);
        }
        return $result;
    }

    public function getObj ($className)
    {
        $fieldObj = GeneralUtility::makeInstance($className);	// fetch and store it as persistent object
        if ($fieldObj->needsInit()) {
            $fieldObj->init();
        }

        return $fieldObj;
    }

    public function getFuncTablename ()
    {
        return $this->functablename;
    }

    public function setFuncTablename ($tablename)
    {
        $this->functablename = $tablename;
    }

    public function getTablename ()
    {
        return $this->tablename;
    }

    public function setTablename ($tablename)
    {
        $this->tablename = $tablename;
    }
}

