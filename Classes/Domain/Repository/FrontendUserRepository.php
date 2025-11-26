<?php

/*
 * This file is part of the "tt_products" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JambageCom\Agency\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FrontendUser repository with all the callable functionality
 */
class FrontendUserRepository
{
    private const TABLE = 'fe_users';
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

    /**
     * Insert the front end user record into the database
     *
     * @param int $pid pid of record
     * @param bool $respectEnableFields if set to false, hidden records are shown
     */
    public function save(int $pid, array $row/*, string $fields*/): int
    {
        $insertId = 0;
        $row['pid'] = $pid;
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->insert(self::TABLE)
            ->values(
                $row
            )
            ->executeStatement();
        $insertId = $queryBuilder->getConnection()->lastInsertId();

        return $insertId;
    }

    public function updateMMRelations(
        array $row
    ): void {
        // update the MM relation
        $fieldsList = array_keys($row);
        foreach ($GLOBALS['TCA'][self::TABLE]['columns'] as $colName => $colSettings) {

            if (
                in_array($colName, $fieldsList) &&
                $colSettings['config']['type'] == 'select' &&
                isset($colSettings['config']['MM'])
            ) {
                $valuesArray = $row[$colName];
                if (isset($valuesArray) && is_array($valuesArray)) {
                    $queryBuilder = $this->getQueryBuilder();
                    $queryBuilder
                        ->delete($colSettings['config']['MM'])
                        -where(
                            $queryBuilder->expr()->eq(
                                'uid_local',
                                $queryBuilder->createNamedParameter($row['uid'], Connection::PARAM_INT)
                            ),
                        )
                        ->executeStatement();

                    $insertFields = [];
                    $insertFields['uid_local'] = intval($row['uid']);
                    $insertFields['tablenames'] = '';
                    $insertFields['sorting'] = 0;
                    foreach($valuesArray as $theValue) {
                        $insertFields['uid_foreign'] = intval($theValue);
                        $insertFields['sorting']++;
                        $queryBuilder = $this->connection->createQueryBuilder();
                        $queryBuilder
                            ->insert($colSettings['config']['MM'])
                            ->values(
                                $insertFields
                            )
                            ->executeStatement();
                        $insertId = $queryBuilder->getConnection()->lastInsertId();
                    }
                }
            }
        }
    }   // updateMMRelations


    /**
     * Performs an UPDATE sql query
     * If a "tstamp" field is configured for the $table tablename in $GLOBALS['TCA'] then that field is automatically updated to the current time.
     * Notice: It is YOUR responsibility to make sure the data being updated is valid according the tablefield types etc.
     *
     * @param string $table The table name, should be in $GLOBALS['TCA']
     * @param int $uid The UID of the record from $table which we are going to update
     * @param array $dataArray the data array where key/value pairs are fieldnames/values for the record to update
     * @param string $fieldList Comma list of fieldnames which are allowed to be updated. Only values from the data record for fields in this list will be updated!!
     * @param bool $doExec If set, the query is executed. IT'S HIGHLY RECOMMENDED TO USE THIS FLAG to execute the query directly!!!
     *
     * @return string the query, ready to execute unless $doExec was TRUE in which case the return value is FALSE
     *
     */
    public function update(int $uid, array $dataArray, string $fieldList): int|bool
    {
        // uid can never be set
        unset($dataArray['uid']);

        if (!$uid) {
            return false;
        }

        $result = 0;
        $fieldList = implode(',', GeneralUtility::trimExplode(',', $fieldList, true));
        $updateFields = [];
        foreach ($dataArray as $f => $v) {
            if (GeneralUtility::inList($fieldList, $f)) {
                $updateFields[$f] = $v;
            }
        }

        if (isset($GLOBALS['TCA'][self::TABLE]['ctrl']['tstamp'])) {
            $updateFields[$GLOBALS['TCA'][self::TABLE]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
        }

        if (!empty($updateFields)) {
            $queryBuilder = $this->getQueryBuilder();
            $queryBuilder
                ->update(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                  );

            foreach ($updateFields as $key => $value) {
                 $queryBuilder->set($key, $value);
            }
            $result = $queryBuilder->executeStatement();

            // $result = static::getDatabaseConnection()->exec_UPDATEquery($table, 'uid=' . $uid, $updateFields);
        }

        return $result;
    }

    /**
     *  Delete the record
     */
    public function delete(int $uid): int
    {
        $result = 0;
        if ($uid) {
            $connection = $this->getConnection();
            $result = $connection->delete(
                self::TABLE,
                [
                    'uid' => $uid
                ]
            );
        }
        return $result;
    }

    /**
     *  Get the maximum customer number
     */
    public function maxCustomerNumber(): string
    {
        $customerNumber = '0';
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('uid', 'cnum')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt(
                    'cnum',
                    '\'\''
                )
              )
            ->orderBy('cnum', 'DESC')
            ->setMaxResults(1)
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $customerNumber = $row['cnum'] ?? 0;
        }

        return $customerNumber;
    }

    /**
     *  Get the first record with a specific value in a given field
     */
    public function getSpecificRecord(int $pid, string $theField, string $searchWord, bool $showDeleted = false): bool|array
    {
        $queryBuilder = $this->getQueryBuilder();
        $restrictions = $queryBuilder
            ->getRestrictions();

        $restrictions->removeAll();

        if (!$showDeleted) {
            $restrictions->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        // $queryBuilder->getRestrictions()->limitRestrictionsToTables(['feuser1']);

        $whereArray = [];
        if ($pid) {
            $whereArray[] = $queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
            );
        }
        $whereArray[] = $queryBuilder->expr()->eq(
            $theField,
            $queryBuilder->createNamedParameter($searchWord, Connection::PARAM_STR)
        );

        $result = $queryBuilder
            ->select('uid',  $theField)
            ->from(self::TABLE)
            ->andWhere(
                ...$whereArray
            )
            ->setMaxResults(1)
            ->executeQuery();
        $row = $result->fetchAssociative();

        return $row;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::TABLE);
    }
}
