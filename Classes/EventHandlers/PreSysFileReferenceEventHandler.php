<?php

declare(strict_types=1);

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

use SUDHAUS7\Sudhaus7Wizard\Events\BeforeContentCloneEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class PreSysFileReferenceEventHandler
{
    public function __invoke(BeforeContentCloneEvent $event)
    {
        if ($event->getTable() === 'sys_file_reference') {
            $row = $event->getRecord();
            $sys_file = $event->getCreateProcess()->getSource()->getRow('sys_file', ['uid'=>$row['uid_local']]);
            $newidentifier = '/' . trim($event->getCreateProcess()->getFilemount()['path'] . $sys_file['name'], '/');

            $test = BackendUtility::getRecord('sys_file', $newidentifier, 'identifier');
            if (!empty($test)) {
                $event->getCreateProcess()->log('Using File ' . $newidentifier);
                $row['uid_local'] = $test['uid'];
                $event->setRecord($row);
            }
            $event->getCreateProcess()->log('Create File ' . $newidentifier);
            try {
                $new_sys_file = $event->getCreateProcess()->getSource()->handleFile($sys_file, $newidentifier);
                $event->getCreateProcess()->addContentMap('sys_file', $sys_file['uid'], $new_sys_file['uid']);
                $row['uid_local'] = $new_sys_file['uid'];
            } catch (\Exception $e) {
                print_r([$e->getMessage(), $e->getTraceAsString()]);
                exit;
            }
            $event->setRecord($row);
        }
    }
}
