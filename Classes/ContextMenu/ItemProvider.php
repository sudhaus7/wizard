<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\ContextMenu;

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;

final class ItemProvider extends AbstractProvider
{
    protected $itemsConfiguration = [
        's7wizard' => [
            'type' => 'item',
            'label' => 'Create new from page...', // you can use "LLL:" syntax here
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'createTask', //name of the function in the JS file
        ],
    ];

    public function getPriority(): int
    {
        return 90;
    }

    public function canHandle(): bool
    {
        return $this->table === 'pages';
    }

    public function addItems(array $items): array
    {
        $this->initDisabledItems();
        $localItems = $this->prepareItems($this->itemsConfiguration);
        $items = $items + $localItems;
        return $items;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        return [
            'data-callback-module' => 'TYPO3/CMS/Sudhaus7Wizard/CreateWizardTaskWizard',
        ];
    }

    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }

        $canRender = false;
        if ($itemName == 's7wizard') {
            $canRender = $this->isSiteRoot();
        }
        return $canRender;
    }

    private function isSiteRoot(): bool
    {
        $pageRecord = BackendUtility::getRecord('pages', $this->identifier);
        return ($pageRecord['is_siteroot'] ?? 0) === 1;
    }
}
