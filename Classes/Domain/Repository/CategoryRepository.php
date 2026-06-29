<?php
declare(strict_types=1);

namespace JambageCom\Agency\Domain\Repository;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use JsonException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class CategoryRepository extends Repository implements AssignmentRecordInterface
{
    use RepositoryTrait;
    protected string $table = 'sys_category';

    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @throws InvalidQueryException
     */
    public function findByPidList(array $pidList): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->matching(
            $query->in('pid', $pidList)
        );

        return $query->execute();
    }

    /**
     * @throws InvalidQueryException
     */
    public function findByUids(array $categoryUids, array $orderings = []): array|QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->in('uid', $categoryUids));

        if (!empty($orderings)) {
            $query->setOrderings($orderings);
        }

        return $query->execute();
    }

    /**
     * @throws Exception
     */
    public function getAssignedToRecord($uid, $table): array
    {
        $mmTable = 'sys_category_record_mm';
        $fieldName = 'categories';
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable);
        $queryBuilder = $connection->createQueryBuilder();
        return $queryBuilder
            ->select($mmTable . '.uid_local')
            ->from($mmTable)
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($fieldName))
            )
            ->executeQuery()
            ->fetchFirstColumn();
    }
}
