<?php

namespace JambageCom\Agency\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 1999-2003 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2004-2016 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */

use JambageCom\Agency\Configuration\ConfigurationCheck;


class RegisterPluginController extends \tslib_pibase {

		// Plugin initialization variables
	public $prefixId = 'agency';
	public $scriptRelPath = 'pi/class.tx_agency_pi_base.php'; // Path to this script relative to the extension dir.
	public $extKey = AGENCY_EXT;		// Extension key.


	public function main ($content, $conf) {
		$this->pi_setPiVarDefaults();
		$this->conf = $conf;

			// Check installation requirements
		$content =
			ConfigurationCheck::checkRequirements(
				$conf,
				$this->extKey
			);

			// Check installation security settings
		$content =
			ConfigurationCheck::checkSecuritySettings(
				$this->extKey
			);

			// Check presence of deprecated markers
		$content .=
			ConfigurationCheck::checkDeprecatedMarkers(
				$this->cObj,
				$conf,
				$this->extKey
			);

		$theTable = '';
		// The table must be configured
		if (
			isset($conf['table.']) &&
			is_array($conf['table.']) &&
			$conf['table.']['name']
		) {
			$theTable  = $conf['table.']['name'];
		}

		// Check presence of configured table in TCA
		if (
			$theTable == '' ||
			!is_array($GLOBALS['TCA'][$theTable]) ||
			!is_array($GLOBALS['TCA'][$theTable]['columns'])
		) {
			$errorText = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_table_without_TCA');
			$content = sprintf($errorText, $theTable);
		}

			// If no error content, proceed
		if ($content == '') {
			$mainObj = \t3lib_div::getUserObj('JambageCom\\Agency\\Controller\\InitializationController');
			$mainObj->cObj = $this->cObj;
			$content = $mainObj->main($content, $conf, $this, 'fe_users');
		}
		return $content;
	}

}
