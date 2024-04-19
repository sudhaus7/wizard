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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

class TranslateUidEvent implements LoggerAwareInterface, WizardEventInterface
{
    use EventTrait;
    use LoggerAwareTrait;

    protected string $tablename;
    protected int $searchedUid;
    protected int $foundUid;

    public function __construct(string $tablename, int $searchedUid, int $foundUid)
    {
        $this->tablename = $tablename;
        $this->searchedUid  = $searchedUid;
        $this->foundUid  = $foundUid;
    }

    public function getTablename(): string
    {
        return $this->tablename;
    }

    public function getSearchedUid(): int
    {
        return $this->searchedUid;
    }

    public function getFoundUid(): int
    {
        return $this->foundUid;
    }

    public function setFoundUid(int $foundUid): void
    {
        $this->foundUid = $foundUid;
    }
}
