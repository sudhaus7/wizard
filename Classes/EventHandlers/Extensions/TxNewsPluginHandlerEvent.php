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

use SUDHAUS7\Sudhaus7Wizard\Events\TtContent\FinalContentByCtypeEvent;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TxNewsPluginHandlerEvent
{
    public function __invoke(FinalContentByCtypeEvent $event)
    {
        if ($event->getCtype() === 'news_pi1') {
            $process = $event->getCreateProcess();
            $record = $event->getRecord();
            if (!empty($record['pi_flexform'])) {
                $flex = GeneralUtility::xml2array($record['pi_flexform']);

                if (isset($flex['data']['additional']['lDEF']['settings.detailPid']['vDEF'])) {
                    $flex['data']['additional']['lDEF']['settings.detailPid']['vDEF'] = $process->getTranslateUid('pages', $flex['data']['additional']['lDEF']['settings.detailPid']['vDEF']);
                }
                if (isset($flex['data']['additional']['lDEF']['settings.listPid']['vDEF'])) {
                    $flex['data']['additional']['lDEF']['settings.listPid']['vDEF'] = $process->getTranslateUid('pages', $flex['data']['additional']['lDEF']['settings.listPid']['vDEF']);
                }
                if (isset($flex['data']['additional']['lDEF']['settings.backPid']['vDEF'])) {
                    $flex['data']['additional']['lDEF']['settings.backPid']['vDEF'] = $process->getTranslateUid('pages', $flex['data']['additional']['lDEF']['settings.backPid']['vDEF']);
                }
                if (isset($flex['data']['additional']['lDEF']['settings.tags']['vDEF'])) {
                    $flex['data']['additional']['lDEF']['settings.tags']['vDEF'] = $process->translateIDlist('tx_news_domain_model_tag', $flex['data']['additional']['lDEF']['settings.tags']['vDEF']);
                }

                if (isset($flex['data']['sDEF']['lDEF']['settings.startingpoint']['vDEF'])) {
                    $flex['data']['sDEF']['lDEF']['settings.startingpoint']['vDEF'] = $process->translateIDlist('pages', $flex['data']['sDEF']['lDEF']['settings.startingpoint']['vDEF']);
                }

                if (isset($flex['data']['sDEF']['lDEF']['settings.categories']['vDEF'])) {
                    $flex['data']['sDEF']['lDEF']['settings.categories']['vDEF'] = $process->translateIDlist('sys_category', $flex['data']['sDEF']['lDEF']['settings.categories']['vDEF']);
                }

                $record['pi_flexform'] = Tools::array2xml($flex);
            }
            $event->setRecord($record);
        }
    }
}
