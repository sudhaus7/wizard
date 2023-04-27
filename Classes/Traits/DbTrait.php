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
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait DbTrait
{
    /**
     * @param string $tablename
     * @param array $data
     * @param array $where
     *
     * @return int the number of affected rows
     */
    public static function updateRecord(string $tablename, array $data, array $where): int
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tablename)->update($tablename, $data, $where);
    }

    /**
     * @param string $tablename
     * @param array $data
     *
     * @return int[] the number of affected rows, the new uid
     */
    public static function insertRecord(string $tablename, array $data): array
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tablename);
        $rows =$conn->insert($tablename, $data);
        $newid = $conn->lastInsertId($tablename);
        return [$rows, $newid];
    }

    public static function tableHasField(string $tablename, string $field): bool
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tablename);
        $columns = $conn->getSchemaManager()->listTableColumns($tablename);
        foreach ($columns as $column) {
            if ($column->getName() === $field) {
                return true;
            }
        }
        return false;
    }
}
