<?php

declare(strict_types=1);

namespace JambageCom\Agency\Utility;

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
* language functions
*
* @author	Franz Holzinger <franz@ttproducts.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Agency\Constants\Extension;

class LocalizationUtility
{
    protected static $filename = '';

    public static function init(): void
    {
        $filename = DIV2007_LANGUAGE_SUBPATH . 'locallang.xlf';
        self::setFilename($filename);
    }

    public static function setFilename($filename): void
    {
        self::$filename = $filename;
    }

    public static function getFilename()
    {
        return self::$filename;
    }

    public static function translate($key)
    {
        $filename = self::getFilename();

        $result =
            FrontendUtility::translate(
                Extension::KEY,
                $filename,
                $key
            );
        return $result;
    }
}
