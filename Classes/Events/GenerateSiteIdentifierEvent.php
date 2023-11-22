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

final class GenerateSiteIdentifierEvent implements WizardEventInterface
{
    use EventTrait;

    /**
     * @var array<array-key, mixed>
     */
    protected array $siteconfig;

    protected string $identifier;

    protected string $basepath;

    /**
     * @param array<array-key, mixed> $siteconfig
     */
    public function __construct(array $siteconfig, string $basepath, CreateProcess $createProcess)
    {
        $this->createProcess = $createProcess;
        $this->siteconfig = $siteconfig;
        $this->basepath = $basepath;
        $this->identifier = '';
    }

    /**
     * @return array
     */
    public function getSiteconfig(): array
    {
        return $this->siteconfig;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getBasepath(): string
    {
        return $this->basepath;
    }
}
