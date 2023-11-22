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

namespace SUDHAUS7\Sudhaus7Template\Wizard;

use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Types\RemoteSites;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardTemplateConfigInterface;

class WizardConfigRemote implements WizardTemplateConfigInterface
{
    public function getExtension(): string
    {
        return 'template_remote';
    }

    public function getDescription(): string
    {
        return 'EKBO Remote';
    }

    public function getSourcePid(): int|string
    {
        return 1;
    }

    public function getFlexinfoFile(): string
    {
        return 'FILE:EXT:template/Configuration/Flexforms/Wizard.xml';
    }

    public function getAddFields(): string
    {
        return '';
    }

    public function modifyRecordTCA(array $TCA): array
    {
        $TCA['types']['template_remote']['showitem']         = 'status,base,sourceclass,sourcepid,valuemapping,projektname,longname,shortname,domainname,contact,email,--div--;Benutzer,reduser,redpass,redemail,--div--;Template Konfigurationen,flexinfo';
        $TCA['types']['template_remote']['columnsOverrides'] = [
            'valuemapping' => [
                'config' => [
                    'items' => [['EKBO Mapping', 'EXT:template/Resources/Private/Mapping/Ekbo.yaml']],
                ],
            ],
            'sourcepid'   => [
                'config' => [
                    'renderType'    => 'selectSingle',
                    'type'          => 'select',
                    'min'           => 1,
                    'max'           => 1,
                    'size'          => 1,
                    'multi'         => false,
                    'eval' => 'trim',
                    'itemsProcFunc' => RemoteSites::class . '->itemsProcFunc',
                ],
            ],
            'sourceclass' => [
                'config' => [
                    'items' => [
                        0 => [
                            'EKBO Server',
                            '\\' . \SUDHAUS7\Sudhaus7Template\Wizard\WizardSource::class,
                        ],
                    ],
                ],
            ],
        ];

        return $TCA;
    }
}
