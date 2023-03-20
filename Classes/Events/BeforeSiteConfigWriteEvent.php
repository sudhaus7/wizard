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

class BeforeSiteConfigWriteEvent implements WizardEventInterface
{
    use EventTrait;
    protected CreateProcess $create_process;
    protected array $siteconfig;
    public function __construct(array $siteconfig, CreateProcess $create_process)
    {
        $this->create_process = $create_process;
        $this->siteconfig = $siteconfig;
    }

    /**
     * @return array
     */
    public function getSiteconfig(): array
    {
        return $this->siteconfig;
    }

    /**
     * @param array $siteconfig
     */
    public function setSiteconfig(array $siteconfig): void
    {
        $this->siteconfig = $siteconfig;
    }
}
