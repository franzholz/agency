<?php

declare(strict_types=1);

namespace JambageCom\Agency\Upgrades;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;



#[UpgradeWizard('agencyPluginListTypeToCTypeUpdate')]
final class PluginListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        $this->validateRequirementsFixed();
    }

    protected function getListTypeToCTypeMapping(): array
    {
        $mapping = [
            'agency' => 'agency',
        ];

        return $mapping;
    }

    public function getTitle(): string
    {
        return 'Migrates agency plugins';
    }

    public function getDescription(): string
    {
        return 'Migrates agency from list_type to CType.';
    }

    protected function columnsExistInContentTable(): bool
    {
        $schemaManager = $this->connectionPool
        ->getConnectionForTable(self::TABLE_CONTENT)
        ->createSchemaManager();

        $tableColumnNames = array_flip(
            array_map(
                static fn(Column $column) => $column->getName(),
                      $schemaManager->listTableColumns(self::TABLE_CONTENT)
            )
        );

        foreach (['CType', 'list_type'] as $column) {
            if (!isset($tableColumnNames[$column])) {
                return false;
            }
        }

        return true;
    }

    protected function columnsExistInBackendUserGroupsTable(): bool
    {
        $schemaManager = $this->connectionPool
        ->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS)
        ->createSchemaManager();

        return isset($schemaManager->listTableColumns(self::TABLE_BACKEND_USER_GROUPS)['explicit_allowdeny']);
    }

    protected function hasContentElementsToUpdate(): bool
    {
        $listTypesToUpdate = array_keys($this->getListTypeToCTypeMapping());

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
        ->count('uid')
        ->from(self::TABLE_CONTENT)
        ->where(
            $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in(
                    'list_type',
                    $queryBuilder->createNamedParameter($listTypesToUpdate, Connection::PARAM_STR_ARRAY)
                ),
        );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }

    protected function hasBackendUserGroupsToUpdate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();

        $searchConstraints = [];
        foreach ($this->getListTypeToCTypeMapping() as $listType) {
            $searchConstraints[] = $queryBuilder->expr()->like(
                'explicit_allowdeny',
                $queryBuilder->createNamedParameter(
                    '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:' . $listType) . '%'
                )
            );
        }

        $queryBuilder
        ->count('uid')
        ->from(self::TABLE_BACKEND_USER_GROUPS)
        ->where(
            $queryBuilder->expr()->or(...$searchConstraints),
        );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Returns true, if no legacy explicit_allowdeny be_groups configuration is found. Note, that we can not rely
     * BackendGroupsExplicitAllowDenyMigration status here, since the update must also be executed for new
     * TYPO3 v13+ installations, where BackendGroupsExplicitAllowDenyMigration is not required.
     */
    protected function hasNoLegacyBackendGroupsExplicitAllowDenyConfiguration(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
        ->count('uid')
        ->from(self::TABLE_BACKEND_USER_GROUPS)
        ->where(
            $queryBuilder->expr()->like(
                'explicit_allowdeny',
                $queryBuilder->createNamedParameter(
                    '%ALLOW%'
                )
            ),
        );
        return (int)$queryBuilder->executeQuery()->fetchOne() === 0;
    }

    protected function getContentElementsToUpdate(string|int $listType): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
        ->select('uid')
        ->from(self::TABLE_CONTENT)
        ->where(
            $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter((string) $listType)),
        );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function getBackendUserGroupsToUpdate(string $listType): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
        ->select('uid', 'explicit_allowdeny')
        ->from(self::TABLE_BACKEND_USER_GROUPS)
        ->where(
            $queryBuilder->expr()->like(
                'explicit_allowdeny',
                $queryBuilder->createNamedParameter(
                    '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:' . $listType) . '%'
                )
            ),
        );
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function updateContentElements(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_CONTENT);

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getContentElementsToUpdate($listType) as $record) {
                $connection->update(
                    self::TABLE_CONTENT,
                    [
                        'CType' => $contentType,
                        'list_type' => '',
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    protected function updateBackendUserGroups(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS);

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getBackendUserGroupsToUpdate($listType) as $record) {
                $fields = GeneralUtility::trimExplode(',', $record['explicit_allowdeny'], true);
                foreach ($fields as $key => $field) {
                    if ($field === 'tt_content:list_type:' . $listType) {
                        unset($fields[$key]);
                        $fields[] = 'tt_content:CType:' . $contentType;
                    }
                }

                $connection->update(
                    self::TABLE_BACKEND_USER_GROUPS,
                    [
                        'explicit_allowdeny' => implode(',', array_unique($fields)),
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    private function validateRequirementsFixed(): void
    {
        if ($this->getTitle() === '') {
            throw new \RuntimeException('The update class "' . static::class . '" must provide a title by extending "getTitle()"', 1754309398136);
        }
        if ($this->getDescription() === '') {
            throw new \RuntimeException('The update class "' . static::class . '" must provide a description by extending "getDescription()"', 1754309415902);
        }
        if ($this->getListTypeToCTypeMapping() === []) {
            throw new \RuntimeException('The update class "' . static::class . '" does not provide a "list_type" to "CType" migration mapping', 1754309429634);
        }

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentElement) {
            if (!is_string($listType) && !is_int($listType) || $listType === '') {
                throw new \RuntimeException('Invalid mapping item "' . $listType . '" in class "' . static::class, 1754309443068);
            }
            if (!is_string($contentElement) || $contentElement === '') {
                throw new \RuntimeException('Invalid mapping item "' . $contentElement . '" in class "' . static::class, 1754309452725);
            }
        }
    }
}
