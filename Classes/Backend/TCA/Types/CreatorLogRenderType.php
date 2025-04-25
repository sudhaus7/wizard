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

namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Types;

use SUDHAUS7\Sudhaus7Wizard\Logger\WizardDatabaseLogger;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreatorLogRenderType extends AbstractFormElement
{
    /**
     * @inheritDoc
     */
    public function render()
    {
        $result = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $log = '<tr><td>No log found</td></tr>';

        $uid = $this->data['vanillaUid'];
        $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(WizardDatabaseLogger::TABLE)->select(
            ['*'],
            WizardDatabaseLogger::TABLE,
            ['creator' => $uid],
            [],
            ['uid' => 'ASC']
        );
        if ($res->rowCount() > 0) {
            $log = '';
            while ($row = $res->fetchAssociative()) {
                $log .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', date('Y-m-d', $row['tstamp']), date('H:i:s', $row['tstamp']), $row['level'], $row['message'], $row['context']);
            }
        }
        $result['html'] = "<div class=\"panel panel-default\"><div class=\"table-fit\"><table class=\"table\">$log</table></div></div>";
        return $result;
    }
}
