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

namespace SUDHAUS7\Sudhaus7Wizard\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WizardDatabaseLogger extends AbstractLogger
{
    public const INFO = 'info';
    public const ERROR = 'error';
    public const TABLE = 'tx_sudhaus7wizard_domain_model_log';
    protected float $timer = 0.0;
    protected Connection $connection;
    protected Creator $creator;
    protected ?LoggerInterface $console = null;
    private $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
    ];
    private $formatLevelMap = [
        LogLevel::EMERGENCY => self::ERROR,
        LogLevel::ALERT => self::ERROR,
        LogLevel::CRITICAL => self::ERROR,
        LogLevel::ERROR => self::ERROR,
        LogLevel::WARNING => self::INFO,
        LogLevel::NOTICE => self::INFO,
        LogLevel::INFO => self::INFO,
        LogLevel::DEBUG => self::INFO,
    ];

    public function __construct(Creator $creator, ?LoggerInterface $console, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        $this->console = $console;
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;

        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $this->creator = $creator;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($this->console instanceof LoggerInterface) {
            $this->console->log($level, $message, $context);
        }

        $this->connection->insert(self::TABLE, [
            'tstamp' => time(),
            'crdate' => time(),
            'creator' => $this->creator->getUid(),
            'pid' => $this->creator->getPid(),
            'level' => (string)$level,
            'message' => (string)$message,
            'context' => json_encode($context),
        ]);
    }
}
