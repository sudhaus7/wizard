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
     * @return array
     */
    public function getSiteConfig(mixed $id): array;

    /**
     * @param $table
     * @param $uid
     * @param array $where
     *
     * @return mixed
     */
    public function getRow($table, $where=[]);

    /**
     * @param $table
     * @param $pid
     * @param array $where
     *
     * @return mixed
     */
    public function getRows($table, $where=[]);

    /**
     * @param $start
     *
     * @return mixed
     */
    public function getTree($start);

    /**
     * Ping the Source
     */
    public function ping();

    /**
     * @param $table
     * @param $uid
     * @param $pid
     * @param array $oldrow
     * @param array $columnconfig
     * @param array $pidlist
     *
     * @return array
     */
    public function getIrre($table, $uid, $pid, array $oldrow, array $columnconfig, $pidlist=[]);

    /**
     * @param array $sys_file
     * @param string $newidentifier
     *
     * @return array
     */
    public function handleFile(array $sys_file, $newidentifier);

    /**
     * @param $mmtable
     * @param $uid
     * @param $tablename
     *
     * @return array
     */
    public function getMM($mmtable, $uid, $tablename);

    /**
     * @return mixed
     */
    public function sourcePid();

    /**
     * @return array
     */
    public function getTables();
}
