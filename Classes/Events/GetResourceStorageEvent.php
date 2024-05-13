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
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;

final class GetResourceStorageEvent implements WizardEventInterface
{
    use EventTrait;

    protected ResourceStorageInterface $storage;

    public function __construct(ResourceStorageInterface $storage, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->storage       = $storage;
    }

    public function getStorage(): ResourceStorageInterface
    {
        return $this->storage;
    }

    public function setStorage(ResourceStorageInterface $storage): void
    {
        $this->storage = $storage;
    }
}
