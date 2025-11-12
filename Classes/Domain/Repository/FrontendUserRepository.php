<?php

/*
 * This file is part of the "tt_products" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JambageCom\TtProducts\Domain\Repository;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;


/**
 * FrontendUser repository with all the callable functionality
 */
class FrontendUserRepository extends Repository
{
    /**
     * Constructs a new Repository
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Override default findByUid function to enable also the option to turn off
     * the enableField setting
     *
     * @param int $uid id of record
     * @param bool $respectEnableFields if set to false, hidden records are shown
     */
    public function findByUid($uid, $respectEnableFields = true): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        debug ($this->objectType, 'findByUid $this->objectType +++');

        // only for Debug START:
        $debugApi = \JambageCom\FhDebug\Utility\DebugFunctions::getApi();
        $queryArray = $debugApi->object2array($query);
        debug ($queryArray['querySettings'], '$queryArray[\'querySettings\'] findByUid Pos 1');
        // only for Debug END:

        if (!$respectEnableFields) {
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $languageAspect = $query->getQuerySettings()->getLanguageAspect();
            $languageAspect = new LanguageAspect($languageAspect->getId(), $languageAspect->getContentId(), LanguageAspect::OVERLAYS_OFF, $languageAspect->getFallbackChain());
            $query->getQuerySettings()->setLanguageAspect($languageAspect);
        }

        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('uid', $uid),
                               $query->equals('deleted', 0)
            )
        )->execute()->getFirst();
        debug ($result, 'findByUid ENDE $result');
        return $result;
    }
}
