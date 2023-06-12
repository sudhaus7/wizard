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

use SUDHAUS7\Sudhaus7Wizard\Events\PageSortEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DefaultSiteSorterListener
{
    public function __invoke(PageSortEvent $event)
    {
        $globalconf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_wizard');

        if (isset($globalconf['defaultSiteSorter']) && $globalconf['defaultSiteSorter']) {
            $page = $event->getRecord();
            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

            $query->executeQuery('SET @count=16');
            $query->executeQuery('update pages set sorting=@count:=@count+16 where pid=' . $page['pid'] . ' order by doktype desc,title asc');
        }
    }
}
