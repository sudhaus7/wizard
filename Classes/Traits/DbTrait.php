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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

trait DbTrait
{
    /**
     * @var array<string, array<int|string, array<non-empty-string, int|string>>>
     */
    private static array $data = [];

    public static function getQueryBuilderWithoutRestriction(string $tableName): QueryBuilder
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);

        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $query;
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<array-key, mixed> $where
     */
    public static function updateRecord(string $tableName, array $data, array $where): int
    {
        $data = self::cleanFieldsBeforeInsert($tableName, $data);

        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)->update($tableName, $data, $where);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<int, int|string> the number of affected rows, the new uid
     */
    public static function insertRecord(string $tableName, array $data): array
    {
        $newId = StringUtility::getUniqueId('NEW');
        $data = self::cleanFieldsBeforeInsert($tableName, $data);
        self::$data[$tableName] ??= [];
        self::$data[$tableName][$newId] = $data;
        return [1, $newId];
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
        if (
            !array_key_exists($tableName, $GLOBALS['TCA'] ?? [])
            || !is_array($GLOBALS['TCA'][$tableName]['columns'])
        ) {
            throw new \RuntimeException(
                sprintf('Table "%s" not defined in TCA', $tableName),
                1715700409306
            );
        }

        foreach ($row as $columnName => $value) {
            if (
                (
                    !array_key_exists($columnName, $GLOBALS['TCA'][$tableName]['columns'])
                    && !in_array($columnName, $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
                    && !self::checkForControlField($GLOBALS['TCA'][$tableName]['ctrl'], $columnName)
                )
                || self::isAutoSetFieldByDataHandler($GLOBALS['TCA'][$tableName]['ctrl'], $columnName)
            ) {
                unset($row[$columnName]);
            }
        }
        return $row;
    }

    /**
     * This method checks against auto-created and not configured fields in TCA
     * control section.
     * Actually, only sortby is checked, as other fields are set default by
     * DataHandler such as tstamp, crdate, cruser_id (deprecated)
     *
     * If a field is set inside control AND is configured in columns, this
     * method is not called, as the check for columsn is done before
     *
     *
     * @param array{
     *     sortby?: string
     * } $controlSection
     */
    private static function checkForControlField(array $controlSection, string $columnName): bool
    {
        if ($columnName === ($controlSection['sortby'] ?? '')) {
            return true;
        }
        return false;
    }

    /**
     * This method checks if a field is auto-set by DataHandler. In this case
     * it returns true, so the field can be removed as not needed and auto-set
     * by DataHandler itself
     *
     * @param array{
     *     cruser_id?: string,
     *     tstamp?: string,
     *     crdate?: string
     * } $controlSection
     * @param string $columnName
     * @return bool
     */
    private static function isAutoSetFieldByDataHandler(array $controlSection, string $columnName): bool
    {
        if (($controlSection['cruser_id'] ?? '') === $columnName) {
            return true;
        }

        if (($controlSection['tstamp'] ?? '') === $columnName) {
            return true;
        }

        if (($controlSection['crdate'] ?? '') === $columnName) {
            return true;
        }

        return false;
    }
}
