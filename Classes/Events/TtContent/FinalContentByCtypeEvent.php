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
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventWriteableRecordInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventWriteableRecordTrait;

final class FinalContentByCtypeEvent implements WizardEventInterface, WizardEventWriteableRecordInterface
{
    use EventTrait;
    use EventWriteableRecordTrait;

    protected string $ctype;
    protected ?string $listType;

    /**
     * @param array<array-key, mixed> $record
     */
    public function __construct(
        string $cType,
        ?string $listType,
        array $record,
        CreateProcess $createProcess
    ) {
        $this->ctype = $cType;
        $this->listType = $listType;
        $this->record = $record;
        $this->createProcess = $createProcess;
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
}
