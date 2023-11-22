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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreatorRepository
 */
class CreatorRepository
{
    protected static string $table = 'tx_sudhaus7wizard_domain_model_creator';

    protected QueryBuilder $queryBuilder;

    public function __construct(
        ConnectionPool $connection
    ) {
        $this->queryBuilder = $connection->getQueryBuilderForTable(self::$table);
    }

    /**
     * @return Creator[]
     * @throws DBALException
     * @throws Exception
     */
    public function findAll(): array
    {
        $statement = $this->queryBuilder
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
        $statement = $this->queryBuilder
            ->select('*')
            ->from(self::$table)
            ->where(
                $this->queryBuilder->expr()->eq(
                    'status',
                    $this->queryBuilder->createNamedParameter(10, Connection::PARAM_INT)
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
        if ($force) {
            $this->queryBuilder->getRestrictions()->removeAll();
        }
        $statement = $this->queryBuilder
            ->select('*')
            ->from(self::$table)
            ->where(
                $this->queryBuilder->expr()->eq(
                    'uid',
                    $this->queryBuilder->createNamedParameter($identifier, Connection::PARAM_INT)
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
        $statement = $this->queryBuilder
            ->select('*')
            ->from(self::$table)
            ->where(
                $this->queryBuilder->expr()->eq(
                    'status',
                    $this->queryBuilder->createNamedParameter(15, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $result = $statement->executeQuery();

        return $result->columnCount() > 0;
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
