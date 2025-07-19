<?php

declare(strict_types=1);

namespace JambageCom\Agency\Api;

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
 * customer number functions for the FE user field cnum
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class CustomerNumber implements SingletonInterface
{
    public static function generate(
        $theTable,
        $config
    ) {
        $prefix = $config['prefix'];
        $result = $prefix . '1';

        if ($prefix != '') {
            $newNumber = 1;
            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                'uid,cnum',
                $theTable,
                'cnum > \'\' AND deleted=0',
                '',
                'uid DESC',
                '1'
            );

            if (
                is_array($rows) &&
                isset($rows['0']) &&
                !empty($rows['0']['cnum'])
            ) {
                $cnum = $rows['0']['cnum'];
                $found = preg_match_all('/([\d]+)/', $cnum, $match);
                $index = $found - 1;
                if (
                    $found &&
                    isset($match) &&
                    is_array($match) &&
                    isset($match[$index]) &&
                    is_array($match[$index])
                ) {
                    $index2 = count($match[$index]) - 1;
                    $result = $prefix . ($match[$index][$index2] + 1);
                }
            }
        }

        return $result;
    }
}
