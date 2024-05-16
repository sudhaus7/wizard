<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Configuration;

use TYPO3\CMS\Core\SingletonInterface;

class ExtensionConfiguration implements SingletonInterface
{
    private bool $defaultSiteSorter;
    private string $groupPrefix;
    private bool $unifyShortName;
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration
    ) {
        $extensionConfiguration = $this->extensionConfiguration->get('sudhaus7_wizard');
        $this->defaultSiteSorter = $extensionConfiguration['defaultSiteSorter'] ?? false;
        $this->groupPrefix = $extensionConfiguration['groupprefix'] ?? '';
        $this->unifyShortName = $extensionConfiguration['unifyshortname'] ?? true;
    }

    public function isUnifyShortName(): bool
    {
        return $this->unifyShortName;
    }

    public function getGroupPrefix(): string
    {
        return $this->groupPrefix;
    }

    public function isDefaultSiteSorter(): bool
    {
        return $this->defaultSiteSorter;
    }
}
