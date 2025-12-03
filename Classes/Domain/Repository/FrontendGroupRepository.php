<?php

/*
 * This file is part of the "tt_products" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JambageCom\Agency\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FrontendUser repository with all the callable functionality
 */
class FrontendGroupRepository
{
    private const TABLE = 'fe_groups';
    protected readonly Connection $connection;

    public function __construct(
        protected readonly Context $context,
        protected readonly ConnectionPool $connectionPool,
    ) {
        $this->connection = $connectionPool->getConnectionForTable(self::TABLE);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getUserGroupWhereClause(
        array &$whereArray,
        array $pidArray,
        array $conf,
        string $cmdKey,
        array $allowedUserGroupArray,
        array $allowedSubgroupArray,
        array $deniedUserGroupArray,
        bool $bAllow = true
    ): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();

        $whereArray['where'] = [];
        $whereArray[CompositeExpression::TYPE_AND] = [];
        $whereArray[CompositeExpression::TYPE_OR] = [];
        $subgroupWhereArray = [];

        $whereArray[CompositeExpression::TYPE_AND][] = $queryBuilder->expr()->in(
            'pid',
            $queryBuilder->createNamedParameter($pidArray, ArrayParameterType::INTEGER)
        );

        if (
            isset($allowedUserGroupArray[0]) &&
            $allowedUserGroupArray[0] != 'ALL'
        ) {
            $function = ($bAllow ? 'in' : 'notIn');
            $subgroupWhereArray[] = $queryBuilder->expr()->{$function}(
                'uid',
                $queryBuilder->createNamedParameter(
                    $allowedUserGroupArray,
                    ArrayParameterType::INTEGER
                )
            );
        }

        if (count($allowedSubgroupArray)) {
            $function = ($bAllow ? 'in' : 'notIn');
            $subgroupWhereArray[] = $queryBuilder->expr()->{$function}(
                'subgroup',
                $queryBuilder->createNamedParameter(
                    $allowedSubgroupArray,
                    ArrayParameterType::INTEGER
                )
            );
        }

        if (count($subgroupWhereArray)) {
            // $subgroupWhereClause .= implode(' ' . ($bAllow ? 'OR' : 'AND') . ' ', $subgroupWhereClauseArray);
            // $whereClausePart2Array[] = '( ' . $subgroupWhereClause . ' )';
            $resultKey = ($bAllow ? CompositeExpression::TYPE_OR : CompositeExpression::TYPE_AND);
            $whereArray[$resultKey] = $subgroupWhereArray;
        }

        if (count($deniedUserGroupArray)) {
            // $uidArray = $GLOBALS['TYPO3_DB']->fullQuoteArray($deniedUserGroupArray, $theTable);
            // $whereClausePart2Array[] = 'uid ' . ($bAllow ? 'NOT IN' : 'IN') . ' (' . implode(',', $uidArray) . ')';

            $function = ($bAllow ? 'notIn' : 'in');
            $denyWhere = $queryBuilder->expr()->{$function}(
                'uid',
                $queryBuilder->createNamedParameter(
                    $deniedUserGroupArray,
                    ArrayParameterType::INTEGER
                )
            );

            if ($bAllow) {
                $resultKey = CompositeExpression::TYPE_AND;
                $whereArray[$resultKey][] = $denyWhere;
            } else {
                $resultKey = CompositeExpression::TYPE_OR;
                $whereArray[$resultKey][] = $denyWhere;
            }
        }

        return $queryBuilder;
    }

    /**
     *  Get the searched record uids
     */
    public function getSearchedUids($whereArray): array
    {
        $uidArray = [];
        $queryBuilder = $this->getQueryBuilder();

        $result = $queryBuilder
        ->select('uid',  $theField)
        ->from(self::TABLE)
        ->orWhere(
            ...$whereArray['CompositeExpression::TYPE_OR']
        )
        ->andWhere(
            ...$whereArray['CompositeExpression::TYPE_AND']
        )
        ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            // Do something with that single row
            $uidArray[] = $row['uid'];
        }

        return $uidArray;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::TABLE);
    }
}
