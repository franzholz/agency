<?php

namespace JambageCom\Agency\Domain\Field;

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
 * base class for all database table fields classes
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage agency
 *
 */


class Base
{
    public $hasBeenInitialised = false;

    public function init(): void
    {
        $this->hasBeenInitialised = true;
    }

    public function needsInit()
    {
        return !$this->hasBeenInitialised;
    }

    public function modifyConf(&$conf, $cmdKey): void
    {
    }

    public function get($row, $fieldname)
    {
        return $row[$fieldname];
    }

    public function parseOutgoingData(
        $theTable,
        $fieldname,
        $foreignTable,
        $cmdKey,
        $pid,
        $conf,
        $dataArray,
        $origArray,
        &$parsedArr
    ): void {
        $parsedArr[$fieldname] = $dataArray[$fieldname];	// just copied the original value
    }
}
