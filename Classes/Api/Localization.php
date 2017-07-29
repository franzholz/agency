<?php

namespace JambageCom\Agency\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
* language functions, former class tx_agency_lang
*
* @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
* @author	Franz Holzinger <franz@ttproducts.de>
* @author	Oliver Klee <typo-coding@oliverklee.de>
*
* @package TYPO3
* @subpackage agency
*
*
*/



class Localization extends \JambageCom\Div2007\Base\LocalisationBase implements \TYPO3\CMS\Core\SingletonInterface {
    public $allowedSuffixes = array('formal', 'informal'); // list of allowed suffixes

    public function init1 ($pObj, $cObj, $conf, $scriptRelPath, $extKey) {

        parent::init(
            $cObj,
            $extKey,
            $conf,
            $scriptRelPath
        );

        // keep previsous language settings if available
        if (isset($pObj->LOCAL_LANG) && is_array($pObj->LOCAL_LANG)) {
            $this->setLocallang($pObj->LOCAL_LANG);
        }
        if (isset($pObj->LOCAL_LANG_charset) && is_array($pObj->LOCAL_LANG_charset)) {
            $this->setLocallangCharset($pObj->LOCAL_LANG_charset);
        }
        if (isset($pObj->LOCAL_LANG_loaded)) {
            $this->setLocallangLoaded($pObj->LOCAL_LANG_loaded);
        }
    }

    public function getLLFromString ($string, $force = true) {
        $result = '';
        $arr = explode(':', $string);

        if($arr[0] == 'LLL' && $arr[1] == 'EXT') {
            $temp = $this->getLL($arr[3]);
            if ($temp || !$force) {
                $result = $temp;
            } else {
                $result = $GLOBALS['TSFE']->sL($string);
            }
        } else {
            $result = $string;
        }

        return $result;
    } // getLLFromString

    /**
    * Get the item array for a select if configured via TypoScript
    * @param string  name of the field
    * @return array array of selectable items
    */
    public function getItemsLL ($textSchema, $bAll = true, $valuesArray = array()) {
        $result = array();
        if ($bAll) {
            for ($i = 0; $i < 999; ++$i) {
                $text = $this->getLL($textSchema . $i);
                if ($text != '') {
                    $result[] = array($text, $i);
                }
            }
        } else {
            foreach ($valuesArray as $k => $i) {
                $text = $this->getLL($textSchema . $i);
                if ($text != '') {
                    $result[] = array($text, $i);
                }
            }
        }
        return $result;
    }	// getItemsLL

    /**
    * Returns the localized label of the LOCAL_LANG key, $key
    * In $this->conf['salutation'], a suffix to the key may be set (which may be either 'formal' or 'informal').
    * If a corresponding key exists, the formal/informal localized string is used instead.
    * If the key doesn't exist, we just use the normal string.
    *
    * Example: key = 'greeting', suffix = 'informal'. If the key 'greeting_informal' exists, that string is used.
    * If it doesn't exist, we'll try to use the string with the key 'greeting'.
    *
    * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
    *
    * @param string The key from the LOCAL_LANG array for which to return the value.
    * @param string Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
    * @param boolean If true, the output label is passed through htmlspecialchars()
    * @param boolean If true then an error text will be generated with the information that no text for the key could be found..
    * @return string The value from LOCAL_LANG.
    */
    public function getLL ($key, $alt = '', $hsc = false, $showError = false) {

            // If the suffix is allowed and we have a localized string for the desired salutation, we'll take that.
        $localizedLabel = '';
        $usedLang = '';
        $conf = $this->getConf();
            // Check for an allowed salutation suffix and, if configured, try to localize
        if (
            isset($conf['salutation']) &&
            in_array($conf['salutation'], $this->allowedSuffixes, 1)
        ) {
            $expandedKey = $key . '_' . $conf['salutation'];
            $localizedLabel =
                \tx_div2007_alpha5::getLL_fh002(
                    $this,
                    $expandedKey,
                    $usedLang,
                    $alt,
                    $hsc
                );
        }
            // No allowed salutation suffix and fall back
        if (
            $localizedLabel == '' ||
            $localizedLabel == $alt ||
            $usedLang != $this->getLLkey()
        ) {
            $localizedLabel =
                \tx_div2007_alpha5::getLL_fh002(
                    $this,
                    $key,
                    $usedLang,
                    $alt,
                    $hsc
                );

            if ($localizedLabel == '') {
                if ($localizedLabel == '' && $showError) {
                    $localizedLabel = 'ERROR in extension "' .  $this->getExtKey() . '" no text for key "' . $key . '" can be found';
                }
            }
        }
        return $localizedLabel;
    }

    public function loadLL () {
        $result = true;
        $conf = $this->getConf();

            // flatten the structure of labels overrides
        if (is_array($conf['_LOCAL_LANG.'])) {
            $done = false;
            $i = 0;
            while(!$done && $i < 10000) {
                $done = true;
                foreach($conf['_LOCAL_LANG.'] as $k => $lA) {
                    if (is_array($lA)) {
                        foreach($lA as $llK => $llV) {
                            if (is_array($llV)) {
                                foreach ($llV as $llK2 => $llV2) {
                                    if (is_array($llK2)) {
                                        foreach ($llV2 as $llK3 => $llV3) {
                                            if (is_array($llV3)) {
                                                foreach ($llV3 as $llK4 => $llV4) {
                                                    $conf['_LOCAL_LANG.'][$k][$llK . $llK2 . $llK3 . $llK4] = $llV4;
                                                }
                                            } else {
                                                $conf['_LOCAL_LANG.'][$k][$llK . $llK2 . $llK3] = $llV3;
                                            }
                                        }
                                    } else {
                                        $conf['_LOCAL_LANG.'][$k][$llK . $llK2] = $llV2;
                                    }
                                }
                                unset($conf['_LOCAL_LANG.'][$k][$llK]);
                                $done = false;
                                ++$i;
                            }
                        }
                    }
                }
            }
            $this->setConf($conf);
        }
        \tx_div2007_alpha5::loadLL_fh002($this);

        // do a check if the language file works
        $tmpText = $this->getLL('unsupported');

        if ($tmpText == '') {
            $result = false;
        }

        return $result;
    } // loadLL
}


