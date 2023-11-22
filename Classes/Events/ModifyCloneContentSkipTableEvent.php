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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

final class ModifyCloneContentSkipTableEvent implements LoggerAwareInterface, WizardEventInterface
{
    use LoggerAwareTrait;
    use EventTrait;

    /**
     * @var array|string[]
     */
    protected array $skipList;

    /**
     * @param string[] $skipList
     * @param CreateProcess $create_process
     */
    public function __construct(array $skipList, CreateProcess $create_process)
    {
        $this->createProcess = $create_process;
        $this->skipList       = $skipList;
        $this->logger         = $create_process->getLogger();
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

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
