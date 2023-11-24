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

final class NewFileIdentifierEvent implements WizardEventInterface
{
    use EventTrait;

    protected string $oldidentifier;
    protected string $newidentifier;
    protected int $storage;

    public function __construct(string $oldidentifier, string $newidentifier, int $storage, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->oldidentifier  = $oldidentifier;
        $this->newidentifier  = $newidentifier;
        $this->storage = $storage;
    }

    public function getOldidentifier(): string
    {
        return $this->oldidentifier;
    }

    public function getNewidentifier(): string
    {
        return $this->newidentifier;
    }

    public function setNewidentifier(string $newidentifier): void
    {
        $this->newidentifier = $newidentifier;
    }

    public function getStorage(): int
    {
        return $this->storage;
    }
}
