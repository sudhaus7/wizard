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

use SUDHAUS7\Sudhaus7Wizard\Events\TCA\ColumnType\FinalEvent;
use SUDHAUS7\Sudhaus7Wizard\Tools;

final class TypoLinkinRichTextFieldsEvent
{
    public function __invoke(FinalEvent $event): void
    {
        if ($event->getColumntype() === 'text') {
            $fieldName = $event->getColumn();
            $record = $event->getRecord();

            $config = Tools::resolveFieldConfigurationAndRespectColumnsOverrides($event->getTable(), $fieldName, $record);
            if (
                (
                    isset($config['enableRichtext']) &&
                    $config['enableRichtext']
                ) || (
                    isset($config['softref']) &&
                    \str_contains($config['softref'], 'typolink_tag')
                )
            ) {
                $proc = $event->getCreateProcess();

                \preg_match_all('/<a.+href="(t3:\/\/\S+)"/mU', (string)$record[$fieldName], $matches);
                foreach ($matches[1] as $match) {
                    $replace = $proc->translateT3LinkString($match);
                    $record[$fieldName] = str_replace($match, (string)$replace, (string)$record[$fieldName]);
                }

                // Legacy? Will man das?
                \preg_match_all('/<a.+href="(\d+)"/mU', (string)$record[$fieldName], $matches);
                foreach ($matches[1] as $match) {
                    $replace = $proc->getTranslateUid('pages', $match);
                    $record[$fieldName] = str_replace($match, (string)$replace, (string)$record[$fieldName]);
                }

                $event->setRecord($record);
            }
        }
    }
}
