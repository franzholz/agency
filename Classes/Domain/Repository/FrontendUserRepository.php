<?php

declare(strict_types=1);

namespace JambageCom\Agency\Domain\Repository;


/**
 * This file is part of the "Franz Holzinger" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Franz Holzinger <franz@ttproducts.de>, jambage.com
 */

/**
 * The repository for FrontendUsers
 */
class FrontendUserRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @var array
     */
    protected $defaultOrderings = ['sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING];
}
