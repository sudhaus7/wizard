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

namespace SUSDHAUS7\Sudhaus7Template\Wizard\Listeners;

use SUDHAUS7\Sudhaus7Wizard\Events\FinalContentEvent;
use SUDHAUS7\Sudhaus7Wizard\Services\TyposcriptService;

class SysTemplateListener
{
    public function __invoke(FinalContentEvent $event)
    {
        if ($event->getCreateProcess()->getTemplatekey()==='template' && $event->getTablename() === 'sys_template') {
            $record = $event->getRecord();
            $constants = TyposcriptService::parse($record['constants']);

            $info = $event->getCreateProcess()->getTask()->getFlexinfo();

            $record['constants'] = TyposcriptService::fold($constants);
        }
    }
}
