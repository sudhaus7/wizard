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

namespace SUDHAUS7\Sudhaus7Template\Wizard\Listeners;

use SUDHAUS7\Sudhaus7Template\Wizard\WizardProcess;
use SUDHAUS7\Sudhaus7Wizard\Events\FinalContentEvent;
use SUDHAUS7\Sudhaus7Wizard\Services\FlexformService;
use SUDHAUS7\Sudhaus7Wizard\Services\TyposcriptService;

class SysTemplateListener
{
    public function __invoke(FinalContentEvent $event)
    {
        if ($event->getCreateProcess()->getTemplate() instanceof
            WizardProcess && $event->getExtensionKey()==='template' && $event->getTable() === 'sys_template') {
            $record = $event->getRecord();
            if ($record['root'] === 1) {
                $constants = TyposcriptService::parse($record['constants']);

                $config = FlexformService::flatten($event->getCreateProcess()->getTask()->getFlexinfo());
                $constants['plugin.']['bootstrap_package.']['settings.']['scss.']['primary'] = $config['primary'];
                $constants['plugin.']['bootstrap_package.']['settings.']['scss.']['secondary'] = $config['secondary'];
                $constants['plugin.']['bootstrap_package.']['settings.']['scss.']['breadcrumb-bg'] = $config['breadcrumb'];
                $constants['page.']['preloader.']['backgroundColor'] = $config['preloader'];

                $constants['plugin.']['tx_indexedsearch.']['settings.']['rootPidList'] = $event->getCreateProcess()->translateIDlist(
                    'pages',
                    $constants['plugin.']['tx_indexedsearch.']['settings.']['rootPidList']
                );
                $constants['plugin.']['tx_indexedsearch.']['settings.']['targetPid'] = $event->getCreateProcess()->getTranslateUid(
                    'pages',
                    $constants['plugin.']['tx_indexedsearch.']['settings.']['targetPid']
                );
                $constants['styles.']['content.']['loginform.']['pid'] = $event->getCreateProcess()->translateIDlist(
                    'pages',
                    $constants['styles.']['content.']['loginform.']['pid']
                );
                $record['constants'] = TyposcriptService::fold($constants);
                $event->setRecord($record);
            }
        }
    }
}
