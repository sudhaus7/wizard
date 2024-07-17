<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 project.
 *
 * @author Frank Berger <fberger@sudhaus7.de>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace SUDHAUS7\Sudhaus7Wizard\Traits;

use function in_array;

use SUDHAUS7\Sudhaus7Wizard\Services\Database;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait DbTrait
{
    public static function getQueryBuilderWithoutRestriction(string $tableName): QueryBuilder
    {
        return Database::getQueryBuilderWithoutRestriction($tableName);
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<array-key, mixed> $where
     */
    public static function updateRecord(string $tableName, array $data, array $where): int
    {
        $data = self::cleanFieldsBeforeInsert($tableName, $data);

        $db = GeneralUtility::makeInstance(Database::class);
        return $db->update($tableName, $data, $where);

        //return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)->update($tableName, $data, $where);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<int, int|string> the number of affected rows, the new uid
     */
    public static function insertRecord(string $tableName, array $data): array
    {
        $data = self::cleanFieldsBeforeInsert($tableName, $data);
        $db = GeneralUtility::makeInstance(Database::class);

        [$rows, $newid] = $db->insert($tableName, $data);

        return [$rows, $newid];
    }

    public static function tableHasField(string $tableName, string $field): bool
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
        $columns = $conn->getSchemaManager()->listTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column->getName() === $field) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array<array-key, mixed>
     */
    public static function cleanFieldsBeforeInsert(string $tableName, array $row): array
    {
        if (!isset($GLOBALS['localtables'])) {
            $GLOBALS['localtables'] = [];
        }
        if (!isset($GLOBALS['localtables'][$tableName])) {
            $GLOBALS['localtables'][$tableName] = [];
            $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            $schema = $res->createSchemaManager();
            $columns = $schema->listTableColumns($tableName);
            $GLOBALS['localtables'][$tableName] = [];
            foreach ($columns as $column) {
                $GLOBALS['localtables'][$tableName][] = $column->getName();
            }
        }

        foreach ($row as $field => $value) {
            if (! in_array($field, $GLOBALS['localtables'][$tableName])) {
                unset($row[$field]);
            }
        }
        return $row;
    }
}
