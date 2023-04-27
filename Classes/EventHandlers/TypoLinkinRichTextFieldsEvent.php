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

use SUDHAUS7\Sudhaus7Wizard\Events\TCA\ColumnType\FinalEvent;

class TypoLinkinRichTextFieldsEvent
{
    public function __invoke(FinalEvent $event)
    {
        if ($event->getColumntype() === 'text') {
            $config = $event->getColumnConfig();
            if ((isset($config['enableRichtext']) && $config['enableRichtext']) || isset($config['softref']) && \str_contains(
                'typolink_tag',
                $config['softref']
            )) {
                $proc = $event->getCreateProcess();
                $fieldname = $event->getColumn();
                $record = $event->getRecord();

                \preg_match_all('/<a.+href="(t3:\/\/\S+)"/mU', $record[$fieldname], $matches);
                foreach ($matches[1] as $match) {
                    $replace = $proc->translateT3LinkString($match);
                    $record[$fieldname] = str_replace($match, $replace, $record[$fieldname]);
                }
                $event->setRecord($record);
            }
        }
    }
}
