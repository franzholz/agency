<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Stanislas Rolland (stanislas.rolland@sjbr.ca)
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
 * Example of hook handler for extension Agency Registration (agency)
 *
 * $Id$
 *
 * @author Stanislas Rolland <stanislas.rolland(arobas)sjbr.ca>
 *
 */


class tx_agency_hooksHandler {
	public $bHasBeenInitialised = FALSE;

	public function init (
		tx_agency_data $dataObject
	) {
		$this->bHasBeenInitialised = TRUE;
	}

	public function needsInit () {
		return !$this->bHasBeenInitialised;
	}

	public function registrationProcess_beforeConfirmCreate (
		array &$recordArray,
		tx_agency_controldata $controlDataObj
	) {
			// in the case of this hook, the record array is passed by reference
			// in this example hook, we generate a username based on the first and last names of the user
		$cmdKey = $controlDataObj->getCmdKey();
		$theTable = $controlDataObj->getTable();
		if (
			$controlDataObj->getFeUserData('preview') &&
			$controlDataObj->conf[$cmdKey . '.']['generateUsername']
		) {
			$firstName = trim($recordArray['first_name']);
			$lastName = trim($recordArray['last_name']);
			$name = trim($recordArray['name']);
			if ((!$firstName || !$lastName) && $name) {
				$nameArray = t3lib_div::trimExplode(' ', $name);
				$firstName = ($firstName ? $firstName : $nameArray[0]);
				$lastName = ($lastName ? $lastName : $nameArray[1]);
			}
			$recordArray['username'] = substr(strtolower($firstName), 0, 5) . substr(strtolower($lastName), 0, 5);
			$DBrows =
				$GLOBALS['TSFE']->sys_page->getRecordsByField(
					$theTable,
					'username',
					$recordArray['username'],
					'LIMIT 1'
				);
			$counter = 0;
			while($DBrows) {
				$counter = $counter + 1;
				$DBrows =
					$GLOBALS['TSFE']->sys_page->getRecordsByField(
						$theTable,
						'username',
						$recordArray['username'] . $counter, 'LIMIT 1'
					);
			}
			if ($counter) {
				$recordArray['username'] = $recordArray['username'] . $counter;
			}
		}
	}

	public function registrationProcess_afterSaveEdit (
		$theTable,
		array $dataArray,
		array $origArray,
		$token,
		array &$newRow,
		$cmd,
		$cmdKey,
		$pid,
		$fieldList,
		tx_agency_data $pObj
	) {
	}

	public function registrationProcess_beforeSaveDelete (
		tx_agency_controldata $controlDataObj,
		array &$recordArray,
		$invokingObj
	) {
	}

	public function registrationProcess_afterSaveCreate (
		tx_agency_controldata $controlDataObj,
		$theTable,
		array $dataArray,
		array $origArray,
		$token,
		array &$newRow,
		$cmd,
		$cmdKey,
		$pid,
		$fieldList,
		tx_agency_data $pObj
	) {
	}

	public function confirmRegistrationClass_preProcess (
		tx_agency_controldata $controlDataObj,
		$theTable,
		array &$recordArray,
		&$newFieldList,
		$invokingObj,
		$errorCode
	) {
	}

	public function confirmRegistrationClass_postProcess (
		tx_agency_controldata $controlDataObj,
		$theTable,
		array &$recordArray,
		array $currArr,
		array $origArray,
		$invokingObj
	) {
	}

	public function addGlobalMarkers (
		array &$markerArray,
		tx_agency_controldata $controlData,
		tx_agency_conf $confObj,
		$markerObject
	) {
	}

	public function evalValues (
		tx_agency_conf $confObj,
		$staticInfoObj,
		$theTable,
		array $dataArray,
		array $origArray,
		array $markContentArray,
		$cmdKey,
		array $requiredArray,
		array $checkFieldArray,
		$theField,
		$cmdParts,
		$bInternal,
		&$test,
		$dataObject  // object of type tx_agency_data
	) {
	}

	public function getFailureText (
		$failureText,
		array $dataArray,
		$theField,
		$theRule,
		$label,
		$orderNo = '',
		$param = '',
		$bInternal = FALSE
	) {
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/hooks/class.tx_agency_hooksHandler.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/hooks/class.tx_agency_hooksHandler.php']);
}

?>