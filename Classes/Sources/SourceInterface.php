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

namespace SUDHAUS7\Sudhaus7Wizard\Sources;

use Psr\Log\LoggerAwareInterface;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;

interface SourceInterface extends LoggerAwareInterface
{
    public function setCreator(Creator $creator): void;
    public function getCreator(): ?Creator;
    /**
     * Returns the Site Config as an array
     *
     * @param mixed $id
     *
     * @return array<array-key, mixed>
     */
    public function getSiteConfig(mixed $id): array;

    /**
     * @param array<array-key, mixed> $where
     */
    public function getRow(string $table, array $where = []): mixed;

    /**
     * @param array<array-key, mixed> $where
     * @return array<array-key, mixed>
     */
    public function getRows(string $table, array $where = []): array;

    /**
     * Filters the possible PIDs for a given table. This Method expects all p
     * possible pid and should return the pids which actually have rows
     *
     * @param array<array-key, mixed> $pidList
     *
     * @return array<array-key, mixed>
     */
    public function filterByPid(string $table, array $pidList): array;

    /**
     * @return array<array-key, mixed>
     */
    public function getTree(int $start): array;

    /**
     * Ping the Source
     */
    public function ping(): void;

    /**
     * @param array<array-key, mixed> $oldRow
     * @param array<array-key, mixed> $columnConfig
     * @param array<array-key, mixed> $pidList
     *
     * @return array<array-key, mixed>
     */
    public function getIrre(
        string $table,
        int    $uid,
        int    $pid,
        array  $oldRow,
        array  $columnConfig,
        array $pidList = []
    ): array;

    /**
     * @param array<array-key, mixed> $sysFile
     *
     * @return array<array-key, mixed>
     */
    public function handleFile(array $sysFile, string $newIdentifier): array;

    /**
     * @return array<array-key, mixed>
     */
    public function getMM(string $mmTable, int|string $uid, string $tableName): array;

    public function sourcePid(): int;

    /**
     * @return array<array-key, mixed>
     */
    public function getTables(): array;
}
