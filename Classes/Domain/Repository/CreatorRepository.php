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

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Repository;

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreatorRepository
 */
#[Autoconfigure(public: true)]
final class CreatorRepository
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @return Creator[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function findAll(): array
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('tx_sudhaus7wizard_domain_model_creator');
        $statement = $queryBuilder
            ->select('*')
            ->from('tx_sudhaus7wizard_domain_model_creator');
        $found = [];

        $result = $statement->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $found[] = Creator::createFromDatabaseRow($row);
        }

        return $found;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function findNext(): ?Creator
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('tx_sudhaus7wizard_domain_model_creator');
        $statement = $queryBuilder
            ->select('*')
            ->from('tx_sudhaus7wizard_domain_model_creator')
            ->where(
                $queryBuilder->expr()->eq(
                    'status',
                    $queryBuilder->createNamedParameter(10, Connection::PARAM_INT)
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
     * @throws \Doctrine\DBAL\Exception
     */
    public function findByIdentifier(int|string $identifier, bool $force = false): ?Creator
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('tx_sudhaus7wizard_domain_model_creator');
        if ($force) {
            $queryBuilder->getRestrictions()->removeAll();
        }
        $statement = $queryBuilder
            ->select('*')
            ->from('tx_sudhaus7wizard_domain_model_creator')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($identifier, Connection::PARAM_INT)
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
     * @throws \Doctrine\DBAL\Exception
     */
    public function isRunning(): bool
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('tx_sudhaus7wizard_domain_model_creator');
        $statement = $queryBuilder
            ->count('*')
            ->from('tx_sudhaus7wizard_domain_model_creator')
            ->where(
                $queryBuilder->expr()->eq(
                    'status',
                    $queryBuilder->createNamedParameter(15, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $result = $statement->executeQuery();

        return $result->fetchOne() > 0;
    }

    public function updateStatus(Creator $creator): void
    {
        $data = [
            'tx_sudhaus7wizard_domain_model_creator' => [
                $creator->getUid() => [
                    'status' => $creator->getStatus(),
                    'stacktrace' => $creator->getStacktrace(),
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
            'tx_sudhaus7wizard_domain_model_creator' => [
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
