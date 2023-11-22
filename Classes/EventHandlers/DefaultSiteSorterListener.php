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

use Doctrine\DBAL\Exception;
use SUDHAUS7\Sudhaus7Wizard\Events\PageSortEvent;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DefaultSiteSorterListener
{
    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws Exception
     */
    public function __invoke(PageSortEvent $event): void
    {
        $globalConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_wizard');

        if (isset($globalConf['defaultSiteSorter']) && $globalConf['defaultSiteSorter']) {
            $page = $event->getRecord();
            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

            $query->executeQuery('SET @count=16');
            $query->executeQuery('update pages set sorting=@count:=@count+16 where pid=' . $page['pid'] . ' order by doktype desc,title asc');
        }
    }
}
