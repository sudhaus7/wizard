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
use SUDHAUS7\Sudhaus7Wizard\Traits\EventTrait;

final class BeforeSiteConfigWriteEvent implements WizardEventInterface
{
    use EventTrait;

    protected CreateProcess $createProcess;

    /**
     * @var array<array-key, mixed>
     */
    protected array $siteconfig;

    /**
     * @param array<array-key, mixed> $siteConfig
     */
    public function __construct(array $siteConfig, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->siteconfig = $siteConfig;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getSiteconfig(): array
    {
        return $this->siteconfig;
    }

    /**
     * @param array<array-key, mixed> $siteconfig
     */
    public function setSiteconfig(array $siteconfig): void
    {
        $this->siteconfig = $siteconfig;
    }
}
