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

namespace SUDHAUS7\Sudhaus7Wizard\Events;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

final class AfterClonedTreeInsertEvent implements LoggerAwareInterface, WizardEventInterface
{
    use LoggerAwareTrait;
    use EventTrait;

    protected string|int $oldId;

    /**
     * @var array<array-key, mixed> the page Record
     */
    protected array $record;
    public function __construct(
        string|int $oldId,
        array $record,
        CreateProcess $create_process
    ) {
        $this->createProcess = $create_process;
        $this->oldId = $oldId;
        $this->record = $record;
        $this->logger = $create_process->getLogger();
    }

    /**
     * @return int|string
     */
    public function getOldId(): int|string
    {
        return $this->oldId;
    }

    /**
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
