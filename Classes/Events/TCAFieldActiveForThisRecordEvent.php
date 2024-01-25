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
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

class TCAFieldActiveForThisRecordEvent implements LoggerAwareInterface, WizardEventInterface
{
    use LoggerAwareTrait;
    use EventTrait;

    protected string $table;
    protected string $column;
    protected array $record;
    protected bool $isAllowed = false;

    public function __construct(string $table, string $column, array $record, CreateProcess $createProcess)
    {
        $this->record = $record;
        $this->table = $table;
        $this->column = $column;
        $this->createProcess = $createProcess;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function isAllowed(): bool
    {
        return $this->isAllowed;
    }

    public function setIsAllowed(bool $isAllowed): void
    {
        $this->isAllowed = $isAllowed;
    }
}
