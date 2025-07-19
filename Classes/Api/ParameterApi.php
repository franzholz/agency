<?php

declare(strict_types=1);

namespace JambageCom\Agency\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2020 Franz Holzinger <franz@ttproducts.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Agency\Request\Parameters;


class ParameterApi implements SingletonInterface
{
    protected $controlData = null;

    public function setControlData(
        $controlData
    ): void
    {
        $this->controlData = $controlData;
    }

    public function getControlData(): Parameters
    {
        return $this->controlData;
    }

    public function getRequest()
    {
        return $this->getControlData()->getRequest();
    }

    public function getParameter($param)
    {
        $request = $this->getRequest();
        $value = $request->getParsedBody()[$param] ?? $request->getQueryParams()[$param] ?? null;
        return $value;
    }

    public function getPostParameter($param)
    {
        $request = $this->getRequest();
        $value = $request->getParsedBody()[$param] ?? null;
        return $value;
    }

    public function getGetParameter($param)
    {
        $request = $this->getRequest();
        $value = $request->getQueryParams()[$param] ?? null;
        return $value;
    }

    public function getParameterMerged($param)
    {
        $request = $this->getRequest();
        $getMergedWithPost = $request->getQueryParams()[$param] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($getMergedWithPost, $request->getParsedBody()[$param] ?? []);
        return $getMergedWithPost;
    }
}

