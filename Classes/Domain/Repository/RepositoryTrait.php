<?php
declare(strict_types=1);

namespace JambageCom\Agency\Domain\Repository;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait RepositoryTrait
{
    public function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @param string|null $table
     * @return Connection
     */
    public function getConnection(string|null $table = null): Connection
    {
        return $this->getConnectionPool()->getConnectionForTable($table ?? $this->table);
    }

    /**
     * @param string|null $table
     * @return QueryBuilder
     */
    public function getQueryBuilder(string|null $table = null): QueryBuilder
    {
        return $this->getConnectionPool()->getQueryBuilderForTable($table ?? $this->table);
    }

    /**
     * @param bool $withDeleted
     * @return QueryBuilder
     */
    public function getQueryBuilderWithoutRestrictions(bool $withDeleted = false): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->getRestrictions()
            ->removeAll();
        if (!$withDeleted) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        return $queryBuilder;
    }

    /**
     * @param bool $withoutRestrictions
     * @return array
     * @throws Exception
     */
    public function findAllRecords(bool $withoutRestrictions = false): array
    {
        $queryBuilder = $withoutRestrictions ? $this->getQueryBuilderWithoutRestrictions() : $this->getQueryBuilder();
        return $queryBuilder->select('*')->from($this->table)->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int $uid
     * @param array $fields
     * @param bool $withoutRestrictions
     * @return array
     * @throws Exception
     */
    public function findRecordByUid(int $uid, array $fields = ['*'], bool $withoutRestrictions = false): array
    {
        $queryBuilder = $withoutRestrictions ? $this->getQueryBuilderWithoutRestrictions() : $this->getQueryBuilder();

        return $queryBuilder
            ->select(...$fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array $uidList
     * @param array $fields
     * @param bool $withoutRestrictions
     * @return array
     * @throws Exception
     */
    public function findRecordByUidList(array $uidList, array $fields = ['*'], bool $withoutRestrictions = false): array
    {
        $queryBuilder = $withoutRestrictions ? $this->getQueryBuilderWithoutRestrictions() : $this->getQueryBuilder();

        return $queryBuilder
            ->select(...$fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uidList, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param int $pid
     * @param array $fields
     * @param bool $withoutRestrictions
     * @return array
     * @throws Exception
     */
    public function findRecordByPid(int $pid, array $fields = ['*'], bool $withoutRestrictions = false): array
    {
        $queryBuilder = $withoutRestrictions ? $this->getQueryBuilderWithoutRestrictions() : $this->getQueryBuilder();

        return $queryBuilder
            ->select(...$fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param mixed $value
     * @param string $fieldName
     * @param array $fields
     * @param bool $withoutRestrictions
     * @return array
     * @throws Exception
     */
    public function findRecordByField(mixed $value, string $fieldName = 'pid', array $fields = ['*'], bool $withoutRestrictions = false): array
    {
        $queryBuilder = $withoutRestrictions ? $this->getQueryBuilderWithoutRestrictions() : $this->getQueryBuilder();

        return $queryBuilder
            ->select(...$fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($value))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function findRecordByPidList(array $pidList, array $fields = ['*'], bool $withoutRestrictions = false): array
    {
        $queryBuilder = $withoutRestrictions ? $this->getQueryBuilderWithoutRestrictions() : $this->getQueryBuilder();
        return $queryBuilder
            ->select(...$fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pidList, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array $data
     * @param array $types
     * @return int
     */
    public function insertRecord(array $data = [], array $types = []): int
    {
        return $this->getConnection($this->table)->insert($this->table, $data, $types);
    }

    /**
     * @param array $data
     * @param array $identifier
     * @param array $types
     * @return int
     */
    public function updateRecord(array $data, array $identifier, array $types = []): int
    {
        return $this->getConnection($this->table)->update($this->table, $data, $identifier, $types);
    }

    /**
     * @param int $uid
     * @return int
     */
    public function deleteRecordByUid(int $uid): int
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder
            ->delete($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeStatement();
    }

    /**
     * @param int $pid
     * @return int
     */
    public function deleteRecordByPid(int $pid): int
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder
            ->delete($this->table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            )
            ->executeStatement();
    }
}
