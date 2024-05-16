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
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventWriteableRecordTrait;

final class BeforeContentCloneEvent implements LoggerAwareInterface, WizardEventInterface, WizardEventWriteableRecordInterface
{
    use LoggerAwareTrait;
    use EventTrait;
    use EventWriteableRecordTrait;

    protected int $olduid;
    protected int $oldpid;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(
        string $table,
        int $olduid,
        int $oldpid,
        array $record,
        CreateProcess $createProcess
    ) {
        $this->table = $table;
        $this->olduid = $olduid;
        $this->oldpid = $oldpid;
        $this->createProcess = $createProcess;
        $this->record         = $record;
        $this->logger = $createProcess->getLogger();
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
}
