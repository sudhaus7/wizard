<?php

/*
 * This file is part of the TYPO3 project.
 * (c) 2022 B-Factor GmbH
 *          Sudhaus7
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 * @copyright 2022 B-Factor GmbH https://b-factor.de/
 * @author Frank Berger <fberger@b-factor.de>
 * @author Daniel Simon <dsimon@b-factor.de>
 */

namespace SUDHAUS7\Sudhaus7Wizard\Sources;

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;

interface SourceInterface
{

    /**
     * @param $table
     * @param $uid
     * @param array $where
     *
     * @return mixed
     */
    public function getRow($table, $where=[], $pidfilter=[]);

    /**
     * @param $table
     * @param $pid
     * @param array $where
     *
     * @return mixed
     */
    public function getRows($table, $where=[], $pidfilter=[]);

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
     * @param Creator $creator
     *
     * @return mixed
     */
    public function __construct(Creator $creator);

    /**
     * @param $new
     *
     * @return mixed
     */
    public function pageSort($new);

    /**
     * @return mixed
     */
    public function sourcePid();

    /**
     * @return array
     */
    public function getTables();
}
