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

namespace SUDHAUS7\Sudhaus7Wizard\EventHandlers;

use SUDHAUS7\Sudhaus7Wizard\Events\TCA\Inlines\CleanEvent;

class SysFileReferenceHandleLinkFieldListener
{
    public function __invoke(CleanEvent $event)
    {
        if ($event->getTable() === 'sys_file_reference') {
            $record = $event->getRecord();
            if (!empty($record['link'])) {
                $record['link'] = $event->getCreateProcess()->translateTypolinkString($record['link']);
                $event->setRecord($record);
            }
        }
    }
}
