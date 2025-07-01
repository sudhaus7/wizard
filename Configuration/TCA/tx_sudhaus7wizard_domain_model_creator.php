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

use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation\DomainnameEvaluation;
use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation\NotifyEmailEvaluation;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Sources\LocalDatabase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

return [

    'ctrl' => [
        'title'             => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator',
        'label' => 'projektname',
        'rootLevel' => -1,
        'tstamp'            => 'tstamp',
        'crdate'            => 'crdate',
        'delete'            => 'deleted',
        'enablecolumns'     => [
            'disabled' => 'hidden',
        ],
        'type' => 'base',
        'searchFields' => 'projektname,longname,domainname,',
        'dynamicConfigFile' => ExtensionManagementUtility::extPath('sudhaus7_wizard') . 'Configuration/TCA/tx_sudhaus7wizard_domain_model_creator.php',
        'iconfile' => 'EXT:sudhaus7_wizard/Resources/Public/Icons/icon.svg',
        'subtype_value_field' => 'sourceclass',
    ],
    'types' => [
        '1' => ['showitem' => 'base'],
    ],
    'palettes' => [
        //'1' => Array('showitem' => 'hidden,sys_language_uid,t3ver_label,l10n_parent'),
    ],
    'columns' => [

        't3ver_label' => [
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'none',
                'size' => 27,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],

        'sourcepid' => [
            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.source',
            'config' => [
                'type' => 'link',
                'softref' => 'typo3link',
                'default' => '',
                'size' => 50,
                'required' => true,
                'appearance' => ['browserTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel'],

            ],
        ],

        'base' => [
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.base',

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => '1',
                'items' => [
                    ['label' => 'Bitte wählen', 'value' => 1],
                ],
            ],
            'onChange' => 'reload',
        ],

        'sourceclass' => [
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.sourcetype',
            'exclude' => 1,
            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => '\\' . LocalDatabase::class,
                'items' => [
                    ['label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.sourcetype.localdb', 'value' => '\\' . LocalDatabase::class],

                    //['Remote Server with WizardServer component', '\\' . \SUDHAUS7\Sudhaus7Wizard\Sources\RestWizardServer::class],
                    //['Umzugs-service', '\\' . \SUDHAUS7\Sudhaus7Wizard\Sources\Couchdb::class],
                ],
            ],
        ],

        'projektname' => [
            'exclude' => 0,
            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.projektname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'longname' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.longname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'shortname' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.shortname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'valuemapping' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.valuemapping',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Bitte wählen', 'value' => ''],
                ],
            ],
        ],
        'domainname' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.domainname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'domainname,trim,' . DomainnameEvaluation::class,
                'required' => true,
            ],
        ],

        'notify_email' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.notify_email',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,' . NotifyEmailEvaluation::class,
            ],
        ],

        'contact' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.contact',
            'config' => [
                'type' => 'email',
            ],
        ],

        'reduser' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.reduser',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'redemail' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.redemail',
            'config' => [
                'type' => 'email',
                'size' => 30,
                'required' => true,
            ],
        ],
        'redpass' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.redpass',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'status' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.status',
            'config' => [
                'default' => '0',
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => Creator::getStatusTca(),
            ],
        ],

        'flexinfo' => [
            'displayCond' => 'FIELD:status:<:' . Creator::STATUS_READY,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.flexinfo',
            'config' => [
                'ds' => [
                    'default' => 'FILE:EXT:sudhaus7_wizard/Configuration/Flexforms/Wizard.xml',
                ],
                'ds_pointerField' => 'base',
                'type' => 'flex',
            ],
        ],

        'email' => [
            'displayCond' => 'FIELD:status:=:' . Creator::STATUS_DONE,
            'label' => 'Email-Vorlage',
            'config' => [
                'type' => 'email',
            ],
        ],
        'pid' => [
            'exclude' => 1,
            'label' => 'PID',
            'config' => [
                'type' => 'none',
            ],
        ],

        'sourceuser' => [
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.sourceuser',
            'config' => [
                'type' => 'input',
            ],
        ],

        'sourcefilemount' => [
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.sourcefilemount',
            'config' => [
                'type' => 'input',
            ],
        ],

        'stacktrace' => [
            //'displayCond' => 'FIELD:status:=:'.Creator::STATUS_FAILED,
            'label' => 'Stacktrace',
            'config' => [
                'type' => 'text',
            ],
        ],

        'log' => [
            'label' => 'Log',
            'config' => [
                'type' => 'user',
                'renderType' => 'creatorLog',
            ],
        ],
    ],
];
