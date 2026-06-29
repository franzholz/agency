<?php
declare(strict_types=1);

namespace JambageCom\Agency\Domain\Repository;

interface AssignmentRecordInterface
{
    public function findAllRecords(): array;

    public function findRecordByField(mixed $value, string $fieldName = 'pid', array $fields = ['*'], bool $withoutRestrictions = false): array;

    public function getAssignedToRecord($uid, $table): array;
}
