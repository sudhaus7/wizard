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

class ModifyCleanContentSkipListEvent
{
    /**
     * @var array|string[]
     */
    protected array $skipList;
    protected CreateProcess $create_process;

    /**
     * @param string[] $skipList
     * @param CreateProcess $create_process
     */
    public function __construct(array $skipList, CreateProcess $create_process)
    {
        $this->create_process = $create_process;
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

    /**
     * @return CreateProcess
     */
    public function getCreateProcess(): CreateProcess
    {
        return $this->create_process;
    }
}