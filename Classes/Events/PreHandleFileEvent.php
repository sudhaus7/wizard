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

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

class PreHandleFileEvent implements WizardEventInterface
{
    use EventTrait;

    /**
     * @var array<array-key, mixed>
     */
    protected array $record;

    protected string $newidentifier;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(string $newidentifier, array $record, CreateProcess $create_process)
    {
        $this->createProcess = $create_process;
        $this->newidentifier = $newidentifier;
        $this->record = $record;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function getNewidentifier(): string
    {
        return $this->newidentifier;
    }

    public function setNewidentifier(string $newidentifier): void
    {
        $this->newidentifier = $newidentifier;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }
}
