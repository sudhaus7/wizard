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
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventWriteableRecordInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventWriteableRecordTrait;

class AfterContentCloneEvent implements LoggerAwareInterface, WizardEventInterface, WizardEventWriteableRecordInterface
{
    use LoggerAwareTrait;
    use EventWriteableRecordTrait;
    use EventTrait;
    protected string $table;
    protected int $newuid;
    protected int $olduid;
    protected int $oldpid;

    protected array $record;
    public function __construct(string $table, int $olduid, int $oldpid, int $newuid, array $record, CreateProcess $create_process)
    {
        $this->table = $table;
        $this->newuid = $newuid;
        $this->olduid = $olduid;
        $this->oldpid = $oldpid;
        $this->create_process = $create_process;
        $this->record         = $record;
        $this->logger = $create_process->getLogger();
    }

    /**
     * @return int
     */
    public function getOlduid(): int
    {
        return $this->olduid;
    }

    /**
     * @return int
     */
    public function getOldpid(): int
    {
        return $this->oldpid;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getNewuid(): int
    {
        return $this->newuid;
    }
}
