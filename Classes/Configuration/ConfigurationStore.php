<?php

namespace JambageCom\Agency\Configuration;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
 * setup configuration functions. former class tx_agency_conf
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class ConfigurationStore implements SingletonInterface {

    protected $conf = [];
    protected $config = [];

    public function init ($conf): void
    {
        $this->conf = $conf;
        $this->config = [];
    }

    public function setConf (
        array $dataArray,
        $k = ''
    ): void
    {
        if ($k) {
            $this->conf[$k] = $dataArray;
        } else {
            $this->conf = $dataArray;
        }
    }

    public function getConf ($key = '')
    {
        $result = '';
        if ($key != '') {
            if (isset($this->conf[$key])) {
                $result = $this->conf[$key];
            }
        } else {
            $result = $this->conf;
        }
        return $result;
    }

    public function setConfig (
        array $dataArray,
        $k = ''
    ): void
    {
        if ($k) {
            $this->config[$k] = $dataArray;
        } else {
            $this->config = $dataArray;
        }
    }

    public function getConfig ()
    {
        return $this->config;
    }
    

    public function getIncludedFields (
        $cmdKey
    ) {
        $result = [];

        if (!empty($cmdKey)) {
            $configuration = $this->getConf($cmdKey . '.');
        }

        if (
            isset($configuration) &&
            is_array($configuration) &&
            isset($configuration['fields'])
        ) {
            $result =
                GeneralUtility::trimExplode(',', $configuration['fields'], 1);
        }
        return $result;
    }
}

