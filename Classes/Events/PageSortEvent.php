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

final class PageSortEvent implements WizardEventInterface
{
    use EventTrait;

    /**
     * @var array<array-key, mixed>
     */
    protected array $record;

    protected int $oldpid;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(int $oldpid, array $record, CreateProcess $create_process)
    {
        $this->create_process = $create_process;
        $this->oldpid = $oldpid;
        $this->record = $record;
    }

    /**
     * gets the current record for the given table from the database
     *
     * @return array<array-key, mixed>
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    public function getOldpid(): int
    {
        return $this->oldpid;
    }
}
