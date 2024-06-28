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
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

class CalculateBackendUserGroupNameEvent implements WizardEventInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use EventTrait;

    protected string $groupName;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(string $groupName, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->logger = $createProcess->getLogger();
        $this->groupName     = $groupName;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): void
    {
        $this->groupName = $groupName;
    }
}
