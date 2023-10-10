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

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

class NewFileIdentifierEvent implements WizardEventInterface
{
    use EventTrait;

    protected $oldidentifier;
    protected $newidentifier;

    public function __construct(string $oldidentifier, string $newidentifier, CreateProcess $create_process)
    {
        $this->create_process = $create_process;
        $this->oldidentifier  = $oldidentifier;
        $this->newidentifier  = $newidentifier;
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
}
