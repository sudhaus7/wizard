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
use SUDHAUS7\Sudhaus7Wizard\Events\NewFileIdentifierEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PreSysFileReferenceEventHandler
{
    public function __invoke(BeforeContentCloneEvent $event): void
    {
        if ($event->getTable() === 'sys_file_reference') {
            $row = $event->getRecord();
            $sys_file = $event->getCreateProcess()->getSource()->getRow('sys_file', ['uid' => $row['uid_local']]);

            if (empty($sys_file)) {
                $row['uid_local'] = 0;
                $event->setRecord($row);
                return;
            }

            $path = GeneralUtility::trimExplode(':', $event->getCreateProcess()->getFilemount()['identifier'])[1];
            $path = trim($path, '/') . '/';
            $newIdentifier = '/' . trim($path . $sys_file['name'], '/');

            $subEventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
            $subEvent = new NewFileIdentifierEvent($sys_file['identifier'], $newIdentifier, $sys_file['storage'], $event->getCreateProcess());
            $subEventDispatcher->dispatch($subEvent);
            $newIdentifier = $subEvent->getNewidentifier();

            $test = BackendUtility::getRecord('sys_file', $newIdentifier, 'identifier');
            if (!empty($test)) {
                $event->getCreateProcess()->log('Using File ' . $newIdentifier);
                $row['uid_local'] = $test['uid'];
            } else {
                $event->getCreateProcess()->log('Create File ' . $newIdentifier);
                try {
                    $new_sys_file = $event->getCreateProcess()->getSource()->handleFile($sys_file, $newIdentifier);
                    $event->getCreateProcess()->addContentMap('sys_file', $sys_file['uid'], $new_sys_file['uid']);
                    $row['uid_local'] = $new_sys_file['uid'];
                } catch (\Exception $e) {
                    print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                    exit;
                }
            }
            $event->setRecord($row);
        }
    }
}
