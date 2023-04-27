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

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardEventInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

class BeforeUserCreationUCDefaultsEvent implements WizardEventInterface
{
    use EventTrait;

    protected CreateProcess $create_process;
    private array $uc;

    public function __construct(array $uc, CreateProcess $create_process)
    {
        $this->create_process = $create_process;
        $this->uc = $uc;
    }

    /**
     * @return array
     */
    public function getUc(): array
    {
        return $this->uc;
    }

    /**
     * @param array $uc
     */
    public function setUc(array $uc): void
    {
        $this->uc = $uc;
    }
}
