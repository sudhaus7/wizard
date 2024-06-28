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

class CalculateMountpointNameEvent implements WizardEventInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use EventTrait;

    protected string $mountpointName;
    protected array $config;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(string $mountpointName, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->logger = $createProcess->getLogger();
        $this->mountpointName     = $mountpointName;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getMountpointName(): string
    {
        return $this->mountpointName;
    }

    public function setMountpointName(string $mountpointName): void
    {
        $this->mountpointName = $mountpointName;
    }
}
