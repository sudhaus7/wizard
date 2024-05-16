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

namespace SUDHAUS7\Sudhaus7Wizard\Events\TCA\Column;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Events\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Events\WizardEventWriteableRecordInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventWriteableRecordTrait;

final class CleanEvent implements WizardEventInterface, WizardEventWriteableRecordInterface
{
    use EventTrait;
    use EventWriteableRecordTrait;

    protected string $column;

    /**
     * @var array<array-key, mixed>
     */
    protected array $columnConfig;

    /**
     * @var array{
     *     table: string,
     *     olduid: string|int,
     *     oldpid: string|int,
     *     newpid: string|int,
     *     pObj: object
     * } configuration array
     */
    protected array $parameters;

    /**
     * @param array<array-key, mixed> $columnConfig
     * @param array<array-key, mixed> $record
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(
        string $table,
        string $column,
        array $columnConfig,
        array $record,
        array $parameters,
        CreateProcess $createProcess
    ) {
        $this->table = $table;
        $this->column = $column;
        $this->record = $record;
        $this->parameters = $parameters;
        $this->createProcess = $createProcess;
        $this->columnConfig = $columnConfig;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getColumnConfig(): array
    {
        return $this->columnConfig;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
