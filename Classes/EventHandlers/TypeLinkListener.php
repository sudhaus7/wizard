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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * handles the new TCA Type 'link'
 */
final class TypeLinkListener
{
    public function __invoke(FinalEvent $event): void
    {
        if ($event->getColumntype() === 'link') {
            $fieldName = $event->getColumn();
            $record = $event->getRecord();
            $record[$fieldName] = $event->getCreateProcess()->translateTypolinkString( $record[$fieldName] );
            $event->setRecord($record);
        }
    }
}
