<?php

declare(strict_types=1);

namespace JambageCom\Agency\Api;

use TYPO3\CMS\Core\SingletonInterface;

use JambageCom\Div2007\Base\TranslationBase;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
class Localization extends TranslationBase implements SingletonInterface
{
    public $allowedSuffixes = ['', 'formal', 'informal']; // list of allowed suffixes
    protected $salutation = '';

    public function init(
        $extensionKey = '',
        $confLocalLang = [], // you must pass only the $conf['_LOCAL_LANG.'] part of the setup of the caller
        $scriptRelPath = '',
        $lookupFilename = 'locallang.xlf',
        $useDiv2007Language = true
    ): void {
        $scriptRelPath = DIV2007_LANGUAGE_SUBPATH;
        parent::init(
            $extensionKey,
            $confLocalLang,
            $scriptRelPath,
            $lookupFilename,
            $useDiv2007Language
        );
    }

    public function setSalutation($salutation): void
    {
        if (
            in_array($salutation, $this->allowedSuffixes, 1)
        ) {
            $this->salutation = $salutation;
        }
    }

    public function getSalutation()
    {
        return $this->salutation;
    }

    public function getLabelFromString(
        $string,
        $force = true
    ) {
        $result = '';
        $arr = explode(':', $string);

        if($arr[0] == 'LLL' && $arr[1] == 'EXT') {
            $temp = $this->getLabel($arr[3]);
            if ($temp || !$force) {
                $result = $temp;
            } else {
                $result = $GLOBALS['TSFE']->sL($string);
            }
        } else {
            $result = $string;
        }

        return $result;
    } // getLabelFromString

    /**
    * Get the item array for a select if configured via TypoScript
    * @param string  name of the field
    * @return array array of selectable items
    */
    public function getItemsLL(
        $textSchema,
        $bAll = true,
        $valuesArray = []
    ) {
        $result = [];
        if ($bAll) {
            for ($i = 0; $i < 999; ++$i) {
                $text = $this->getLabel($textSchema . $i);
                if ($text != '') {
                    $result[] = [$text, $i];
                }
            }
        } else {
            foreach ($valuesArray as $k => $i) {
                $text = $this->getLabel($textSchema . $i);
                if ($text != '') {
                    $result[] = [$text, $i];
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
    * @param   string      input: if set then this language is used if possible. output: the used language
    * @param string Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
    * @param boolean If true, the output label is passed through htmlspecialchars()
    * @param boolean If true then an error text will be generated with the information that no text for the key could be found..
    * @return  string      The value from LOCAL_LANG. false in error case
    */
    public function getLabel(
        $key,
        &$usedLang = '',
        $alternativeLabel = '',
        $hsc = false,
        $showError = false
    ) {
        // If the suffix is allowed and we have a localized string for the desired salutation, we'll take that.
        $localizedLabel = '';
        $usedLang = '';
        $salutation = $this->getSalutation();

        // Check for an allowed salutation suffix and, if configured, try to localize
        if (
            $salutation != ''
        ) {
            $expandedKey = $key . '_' . $salutation;
            $localizedLabel =
                parent::getLabel(
                    $expandedKey,
                    $usedLang,
                    $alternativeLabel,
                    $hsc
                );
        }

        // No allowed salutation suffix and fall back
        if (
            $localizedLabel == '' ||
            $localizedLabel == $alternativeLabel ||
            $usedLang != $this->getLocalLangKey()
        ) {
            $localizedLabel =
                parent::getLabel(
                    $key,
                    $usedLang,
                    $alternativeLabel,
                    $hsc
                );

            if ($localizedLabel == '') {
                if ($localizedLabel == '' && $showError) {
                    $localizedLabel = 'ERROR in extension "' .  $this->getExtensionKey() . '" no text for key "' . $key . '" can be found';
                }
            }
        }
        return $localizedLabel;
    }
}
