<?php

namespace JambageCom\Agency\Hooks;

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
use JambageCom\Div2007\Base\StatusProviderBase;
use JambageCom\Agency\Constants\Extension;

/**
 * Hook into the backend module "Reports" checking the configuration required for agency
 */
class StatusProvider extends StatusProviderBase
{
    /**
    * @var string Extension key
    */
    protected $extensionKey = Extension::KEY;

    /**
    * @var string Extension name
    */
    protected $extensionName = 'Agency Registration (' . Extension::KEY . ')';
}
