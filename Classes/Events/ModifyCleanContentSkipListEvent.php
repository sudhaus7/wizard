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
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

final class ModifyCleanContentSkipListEvent implements WizardEventInterface
{
    use EventTrait;

    /**
     * @var string[]
     */
    protected array $skipList;

    /**
     * @param string[] $skipList
     */
    public function __construct(array $skipList, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->skipList       = $skipList;
    }

    /**
     * @return string[]
     */
    public function getSkipList(): array
    {
        return $this->skipList;
    }

    /**
     * @param string[] $skipList
     */
    public function setSkipList(array $skipList): void
    {
        $this->skipList = $skipList;
    }
}
