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

namespace SUDHAUS7\Sudhaus7Wizard\EventHandlers\Extensions;

use SUDHAUS7\Sudhaus7Wizard\Events\BeforeContentCloneEvent;

final class TxNewsFixRecordHandler
{
    public function __invoke(BeforeContentCloneEvent $event): void
    {
        if ($event->getTable() === 'tx_news_domain_model_news') {
            $record = $event->getRecord();
            if ($record['related_links'] === null) {
                $record['related_links'] = 0;
            }

            $event->setRecord($record);
        }
    }
}
