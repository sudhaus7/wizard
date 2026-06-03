# TYPO3 Sudhaus7 Wizard

[![Latest Stable Version](https://img.shields.io/packagist/v/sudhaus7/sudhaus7-wizard.svg)](https://packagist.org/packages/sudhaus7/sudhaus7-wizard)
[![Build Status](https://github.com/endroid/qr-code/workflows/CI/badge.svg)](https://github.com/sudhaus7/sudhaus7-wizard/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/sudhaus7/sudhaus7-wizard.svg)](https://packagist.org/packages/sudhaus7/sudhaus7-wizard)
[![Monthly Downloads](https://img.shields.io/packagist/dm/sudhaus7/sudhaus7-wizard.svg)](https://packagist.org/packages/sudhaus7/sudhaus7-wizard)

This is a TYPO3 extension with the extension key `sudhaus7_wizard`. With this extension a sitepackage can be extended to be able to completly clone an existing site by generating a wizard record, configuring the new name, url and user.

Changelog

1.0.1
* Feature: Added site set support
* Bugfix: Corrected sites config path for classic installations
* Bugfix: Corrected wizard command
* Bugfix: Ensured correct folder creation

1.0.0
* Requires TYPO3 13.4 (dependency updated from `^13.0` to `^13.4`)
* Requires PHP 8.2 or higher
* Breaking change: `WizardProcessInterface` has been extended by a new method providing the template-defined file mountpoints to replace. Add this to your implementations:
```php
public function getFileMountPoints(): array;
```
The file mountpoint is now **replaced** (not appended) on wizard run during `be_groups` generation. To restore the previous append behaviour, listen to `CreateBackendUserGroupEvent`, which now exposes the template file mountpoints via `getTemplateFileMountPoints()`.

0.4.0
* breaking change a Source has been defined from SourceInterface. Sources need now a connection to the CreateProcess. Upgrade your source by adding this code-snippet:
```php
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;

protected ?CreateProcess $createProcess = null;

public function getCreateProcess(): CreateProcess
{
    if ($this->createProcess === null) {
        throw new \InvalidArgumentException('Create Process must be defined', 1715795482);
    }
    return $this->createProcess;
}

public function setCreateProcess( CreateProcess $createProcess ): void
{
    $this->createProcess = $createProcess;
}
```

0.2.0
* Breaking change: Update in WizardProcessInterface - in getTemplateBackendUserGroup and getTemplateBackendUser the CreateProcess Object is now added as a parameter. Please update your implementations for this Interface accordingly

```php

public function getTemplateBackendUser(CreateProcess $pObj): array;
public function getTemplateBackendUserGroup(CreateProcess $pObj): array;


```

0.2.0

* Breaking change: Update in WizardTemplateConfigInterface - please add at least the following lines to your implementations of this interface:

```php
public function modifyRecordTCA(array $TCA): array
{
    return $TCA;
}
```
