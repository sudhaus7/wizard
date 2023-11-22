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

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreatorRepository
 */
class CreatorRepository
{
    protected static string $table = 'tx_sudhaus7wizard_domain_model_creator';

    /**
     * @return Creator[]
     * @throws DBALException
     * @throws Exception
     */
    public function findAll(): array
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::$table);
        $statement = $db
            ->select('*')
            ->from(self::$table);
        $found = [];

        $result = $statement->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $found[] = Creator::createFromDatabaseRow($row);
        }

        return $found;
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    public function findNext(): ?Creator
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::$table);
        $statement = $db
            ->select('*')
            ->from(self::$table)
            ->where(
                $db->expr()->eq(
                    'status',
                    $db->createNamedParameter(10, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);
        $found = null;

        $result = $statement->executeQuery();

        if ($row = $result->fetchAssociative()) {
            $found = Creator::createFromDatabaseRow($row);
        }

        return $found;
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    public function findByIdentifier(int|string $identifier, bool $force = false): ?Creator
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::$table);
        if ($force) {
            $db->getRestrictions()->removeAll();
        }
        $statement = $db
            ->select('*')
            ->from(self::$table)
            ->where(
                $db->expr()->eq(
                    'uid',
                    $db->createNamedParameter($identifier, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);
        $found = null;

        $result = $statement->executeQuery();

        if ($row = $result->fetchAssociative()) {
            $found = Creator::createFromDatabaseRow($row);
        }

        return $found;
    }

    /**
     * @throws DBALException
     */
    public function isRunning(): bool
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::$table);
        $statement = $db
            ->select('*')
            ->from(self::$table)
            ->where(
                $db->expr()->eq(
                    'status',
                    $db->createNamedParameter(15, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $result = $statement->executeQuery();

        return $result->rowCount() > 0;
    }

    public function updateStatus(Creator $creator): void
    {
        $data = [
            self::$table => [
                $creator->getUid() => [
                    'status' => $creator->getStatus(),
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }

    public function updatePid(Creator $creator): void
    {
        $cmd = [
            self::$table => [
                $creator->getUid() => [
                    'move' => $creator->getPid(),
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }
}
