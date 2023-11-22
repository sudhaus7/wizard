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

namespace SUDHAUS7\Sudhaus7Wizard\Events;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventWriteableRecordInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventWriteableRecordTrait;

final class AfterCreateFilemountEvent implements LoggerAwareInterface, WizardEventInterface, WizardEventWriteableRecordInterface
{
    use LoggerAwareTrait;
    use EventTrait;
    use EventWriteableRecordTrait;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(array $record, CreateProcess $create_process)
    {
        $this->record = $record;
        $this->createProcess = $create_process;
        $this->logger = $create_process->getLogger();
        $this->table = 'sys_filemounts';
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
