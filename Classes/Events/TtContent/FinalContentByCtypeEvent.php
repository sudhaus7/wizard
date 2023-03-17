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

namespace SUDHAUS7\Sudhaus7Wizard\Events\TtContent;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;

class FinalContentByCtypeEvent
{
    protected string $ctype;
    protected ?string $listType;
    protected array $record;
    protected CreateProcess $create_process;

    public function __construct(string $ctype, ?string $listType, array $record, CreateProcess $create_process)
    {
        $this->ctype = $ctype;
        $this->listType = $listType;
        $this->record = $record;
        $this->create_process = $create_process;
    }

    /**
     * @return string
     */
    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @return string|null
     */
    public function getListType(): ?string
    {
        return $this->listType;
    }

    /**
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * @return CreateProcess
     */
    public function getCreateProcess(): CreateProcess
    {
        return $this->create_process;
    }

    /**
     * @param array $record
     */
    public function setRecord(array $record): void
    {
        $this->record = $record;
    }
}
