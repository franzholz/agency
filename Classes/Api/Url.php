<?php

namespace JambageCom\Agency\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Stanislas Rolland (stanislas.rolland@sjbr.ca)
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
 * url functions. former class tx_agency_url
 *
 * @author	Kasper Skaarhoj <kasper2007@typo3.com>
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */


class Url implements \TYPO3\CMS\Core\SingletonInterface {
    public $cObj;
    private $piVars;
    private $prefixId;


    public function init ($cObj, $piVars, $prefixId) {
        $this->cObj = $cObj;
        $this->piVars = $piVars;
        $this->prefixId = $prefixId;
    }


	/**
	* Generates a pibase-compliant typolink
	*
	* @param string  $tag: string to include within <a>-tags; if empty, only the url is returned
	* @param string  $id: page id (could of the form id,type )
	* @param array  $vars: extension variables to add to the url ($key, $value)
	* @param array  $unsetVars: extension variables (piVars to unset)
	* @param boolean  $usePiVars: if set, input vars and incoming piVars arrays are merge
	* @return string  generated link or url
	*/
	public function get ($tag = '', $id, $vars = array(), $unsetVars = array(), $usePiVars = true) {

		$vars = (array) $vars;
		$unsetVars = (array) $unsetVars;
		if ($usePiVars) {
			$vars = array_merge($this->piVars, $vars); //vars override pivars

			foreach($unsetVars as $key) {
				if (isset($vars[$key])) {
					// unsetvars override anything
					unset($vars[$key]);
				}
			}
		}

		foreach($vars as $key => $val) {
			$piVars[$this->prefixId . '%5B' . $key . '%5D'] = $val;
		}

		if ($tag) {
			$rc = $this->cObj->getTypoLink($tag, $id, $piVars);
		} else {
			$rc = $this->cObj->getTypoLink_URL($id, $piVars);
		}
		$rc = str_replace(array('[', ']'), array('%5B', '%5D'), $rc);
		return $rc;
	}	// get_url
}

