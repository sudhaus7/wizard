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

interface WizardEventWriteableRecordInterface
{
    /**
     * @return array<array-key, mixed>
     */
    public function getRecord(): array;

    /**
     * @param array<array-key, mixed> $record
     */
    public function setRecord(array $record): void;

    public function getTable(): string;
}
