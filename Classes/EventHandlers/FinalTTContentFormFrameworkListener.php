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

use SUDHAUS7\Sudhaus7Wizard\Events\TtContent\FinalContentByCtypeEvent;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FinalTTContentFormFrameworkListener
{
    public function __invoke(FinalContentByCtypeEvent $event): void
    {
        if ($event->getListType() === 'formframework') {
            $row = $event->getRecord();
            if (!empty($row['pi_flexform'])) {
                $flex = GeneralUtility::xml2array($row['pi_flexform']);

                /** @var Random $rnd */
                $rnd = GeneralUtility::makeInstance(Random::class);
                foreach ($flex['data'] as $key => $config) {
                    if ($key !== 'sDEF') {
                        foreach ($config as $subconfig) {
                            if (isset($subconfig['settings.finishers.Redirect.pageUid'])) {
                                $flex['data'][$key]['lDEF']['settings.finishers.Redirect.pageUid']['vDEF'] = $event->getCreateProcess()->getPageMap()[(int)$flex['data'][$key]['lDEF']['settings.finishers.Redirect.pageUid']['vDEF']];
                            }
                            if (isset($subconfig['settings.finishers.EmailToReceiver.recipients'])) {
                                $flex['data'][$key]['lDEF']['settings.finishers.EmailToReceiver.recipients']['el'] = [
                                    $rnd->generateRandomBytes(22) => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => $event->getCreateProcess()->getTask()->getContact(),
                                                ],
                                                'name' => [
                                                    'vDEF' => $event->getCreateProcess()->getTask()->getLongname(),
                                                ],
                                            ],
                                            '_TOGGLE' => 0,
                                        ],
                                    ],
                                ];
                                $flex['data'][$key]['lDEF']['settings.finishers.EmailToReceiver.senderName']['vDEF'] = 'Baukasten ' . $event->getCreateProcess()->getTask()->getLongname();
                                $flex['data'][$key]['lDEF']['settings.finishers.EmailToReceiver.title']['vDEF'] = 'Aus Ihrem Baukasten ' . $event->getCreateProcess()->getTask()->getLongname();
                            }
                        }
                    }
                }

                $flex['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] = '1:/mediapool/Formulare/Allgemeines-Formular.form.yaml';
                $flex['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'] = 1;
                //$flex['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEV']=1;

                $row['pi_flexform'] = Tools::array2xml($flex);
                $event->setRecord($row);
            }
        }
    }
}
