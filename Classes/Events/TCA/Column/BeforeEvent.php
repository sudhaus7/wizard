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
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventWriteableRecordInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventWriteableRecordTrait;

class BeforeEvent implements WizardEventInterface, WizardEventWriteableRecordInterface
{
    use EventTrait;
    use EventWriteableRecordTrait;
    /**
     * @var string the tablename
     */
    protected string $table;
    /**
     * @var string the TCA column
     */
    protected string $column;
    /**
     * @var array the TCA Config
     */
    protected array $columnConfig;
    /**
     * @var array the record to work on
     */
    protected array $record;
    /**
     * @var array configuration array:
     * [
        'table'  => $table,
        'olduid' => $olduid,
        'oldpid' => $oldpid,
        'newpid' => $newpid,
        'pObj'   => $this,
        ]
     */
    protected array $parameters;
    public function __construct(string $table, string $column, array $columnConfig, array $record, array $parameters, CreateProcess $create_process)
    {
        $this->table = $table;
        $this->column = $column;
        $this->record = $record;
        $this->parameters = $parameters;
        $this->create_process = $create_process;
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
     * @return array
     */
    public function getColumnConfig(): array
    {
        return $this->columnConfig;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
