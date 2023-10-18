# TYPO3 Sudhaus7 Wizard

[![Latest Stable Version](https://img.shields.io/packagist/v/sudhaus7/sudhaus7-wizard.svg)](https://packagist.org/packages/sudhaus7/sudhaus7-wizard)
[![Build Status](https://github.com/endroid/qr-code/workflows/CI/badge.svg)](https://github.com/sudhaus7/sudhaus7-wizard/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/sudhaus7/sudhaus7-wizard.svg)](https://packagist.org/packages/sudhaus7/sudhaus7-wizard)
[![Monthly Downloads](https://img.shields.io/packagist/dm/sudhaus7/sudhaus7-wizard.svg)](https://packagist.org/packages/sudhaus7/sudhaus7-wizard)

A TYPO3 Plugin for duplicating Sites

Changelog

0.2.0

* Breaking change: Update in WizardTemplateConfigInterface - please add at least the following lines to your implementations of this interface:

```php
public function modifyRecordTCA(array $TCA): array
{
    return $TCA;
}
```
