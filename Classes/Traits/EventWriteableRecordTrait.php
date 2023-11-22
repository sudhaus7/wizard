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

namespace SUDHAUS7\Sudhaus7Wizard\Traits;

trait EventWriteableRecordTrait
{
    /**
     * @var array<array-key, mixed>
     */
    protected array $record;

    protected string $table;

    /**
     * gets the current record for the given table from the database
     *
     * @return array<array-key, mixed>
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * write the record back to the event
     *
     * @param array<array-key, mixed> $record
     */
    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
