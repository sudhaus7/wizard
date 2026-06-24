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

final class LoadInitialSiteSettingsEvent implements WizardEventInterface
{
    use EventTrait;

    protected CreateProcess $createProcess;

    protected mixed $pageId;
    /**
     * @var array<array-key, mixed>
     */
    protected array $settings;

    /**
     * @param array<array-key, mixed> $settings
     */
    public function __construct(mixed $pageId, array $settings, CreateProcess $createProcess)
    {
        $this->pageId = $pageId;
        $this->createProcess = $createProcess;
        $this->settings = $settings;
    }

    /**
     * @return mixed
     */
    public function getPageId(): mixed
    {
        return $this->pageId;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<array-key, mixed> $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }
}
