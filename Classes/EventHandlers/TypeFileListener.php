<?php
declare( strict_types=1 );


namespace SUDHAUS7\Sudhaus7Wizard\EventHandlers;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Events\BeforeContentCloneEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\TCA\ColumnType\CleanEvent;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TypeFileListener {

    public function __invoke(CleanEvent $event): void
    {
        if ($event->getColumntype() === 'file') {
            $fieldName = $event->getColumn();
            $record = $event->getRecord();
            $table = $event->getTable();
            $origuid = $event->getCreateProcess()->getTranslateUidReverse( $table, $record['uid']);
            $origSysFileReferences = $event->getCreateProcess()->getSource()->getRows( 'sys_file_reference', ['uid_foreign'=>$origuid,'tablenames'=>$table,'fieldname'=>$fieldName] );
            foreach($origSysFileReferences as $origSysFileReference) {


                $event->getCreateProcess()->getSource()->getRows( 'sys_file', ['uid'=>$origSysFileReference['uid_local']] );

                $subEvent = new BeforeContentCloneEvent('sys_file_reference', $origSysFileReference['uid'],$origSysFileReference['pid'],$origSysFileReference,$event->getCreateProcess());
                $subEvent = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($subEvent);

                // this has now the new sys_file
                $newSysFileReference = $subEvent->getRecord();
                unset($newSysFileReference['uid']);
                $newSysFileReference['pid'] = $record['pid'];
                $newSysFileReference['uid_foreign'] = $record['uid'];

                [$rowsaffected, $newUid] = CreateProcess::insertRecord( 'sys_file_reference', $newSysFileReference);

                $event->getCreateProcess()->addContentMap( 'sys_file_reference', (int)$origSysFileReference['uid'], (int)$newUid , );
                $event->getCreateProcess()->addCleanupInline('sys_file_reference', (int)$newUid);

            }

        }
    }

}
