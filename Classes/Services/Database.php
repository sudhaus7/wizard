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

class Database implements SingletonInterface
{
    public function __construct(
        protected readonly ReferenceIndexUpdater $referenceIndexUpdater,
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return int[]
     */
    public function insert(string $table, array $data): array
    {
        $conn = $this->connectionPool->getConnectionForTable($table);
        $rows = $conn->insert($table, $data);
        $newid = (int)$conn->lastInsertId();

        $this->referenceIndexUpdater->registerForUpdate($table, $newid, 0);

        return [$rows, $newid];
    }

    public function finish(): void
    {
        $this->referenceIndexUpdater->update();
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<string, mixed> $where
     * @throws \Doctrine\DBAL\Exception
     */
    public function update(string $table, array $data, array $where): int
    {
        if (!isset($where['uid'])) {
            $res = $this->connectionPool
                ->getConnectionForTable($table)
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

        $affected = $this->connectionPool->getConnectionForTable($table)->update($table, $data, $where);

        if (isset($data['deleted']) && (int)$data['deleted'] === 1) {
            $this->referenceIndexUpdater->registerForDrop($table, $where['uid'], 0);
        } else {
            $this->referenceIndexUpdater->registerForUpdate($table, $where['uid'], 0);
        }
        return $affected;
    }
}
