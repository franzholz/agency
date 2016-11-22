<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014-2014 Franz Holzinger (franz@ttproducts.de)
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
 * hook functions for the TYPO3 cms
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage agency
 *
 *
 */


class tx_agency_feusergroup_scheduler extends tx_scheduler_Task {

	/**
	 * Function executed from the Scheduler.
	 * Removes all outdated FE user groups of all FE users
	 *
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute () {

		$result = FALSE;

		if (t3lib_extMgm::isLoaded('voucher')) {
			$result = tx_voucher_api::removeOutdatedGroups();
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/hooks/class.tx_agency_feusergroup_scheduler.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/hooks/class.tx_agency_feusergroup_scheduler.php']);
}

