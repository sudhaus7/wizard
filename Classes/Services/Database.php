<?php

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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\ReferenceIndexUpdater;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Database implements SingletonInterface
{
    protected ReferenceIndexUpdater $referenceIndexUpdater;
    public function __construct(ReferenceIndexUpdater $referenceIndexUpdater)
    {
        $this->referenceIndexUpdater = $referenceIndexUpdater;
    }

    public function insert(string $table, array $data): array
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $rows = $conn->insert($table, $data);
        $newid = $conn->lastInsertId($table);

        $this->referenceIndexUpdater->registerForUpdate($table, $newid, 0);

        return [$rows, $newid];
    }

    public function finish(): void
    {
        $this->referenceIndexUpdater->update();
    }

    public function update(string $table, array $data, array $where): int
    {

        if (!isset($where['uid'])) {
            $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)
                                 ->select(
                                     [ '*' ],
                                     $table,
                                     $where
                                 );
            $affected = 0;
            while ($row = $res->fetchAssociative()) {
                $this->update($table, $data, ['uid' => $row['uid']]);
                $affected++;
            }
            return $affected;
        }

        $affected = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->update($table, $data, $where);

        if (isset($data['deleted']) && (int)$data['deleted'] === 1) {
            $this->referenceIndexUpdater->registerForDrop($table, $where['uid'], 0);
        } else {
            $this->referenceIndexUpdater->registerForUpdate($table, $where['uid'], 0);
        }
        return $affected;
    }
}
