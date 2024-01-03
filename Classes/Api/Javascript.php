<?php

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

use TYPO3\CMS\Core\Utility\GeneralUtility;


class Javascript {

    static public function getOnSubmitHooks (
        &$javaScript,
        $pObj
    )
    {
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']) &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])
        ) {
            $_params = [];
            $out = '';
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
                [$onSubmit, $hiddenFields] =
                    GeneralUtility::callUserFunction(
                        $funcRef,
                        $_params,
                        $pObj
                    );
                $out .= $onSubmit;
            }
            $javaScript .=
'<script type="text/javascript">
' . $out . '
</script>';
        }
    }
}

