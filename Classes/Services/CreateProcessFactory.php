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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Sources\Localdatabase;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreateProcessFactory
{
    public static function get(Creator $creator, ?LoggerInterface $logger = null): CreateProcess
    {
        $tsk              = new CreateProcess();
        if ($logger instanceof LoggerInterface) {
            $tsk->setLogger($logger);
        }
        $tsk->setTask($creator);
        $tsk->setTemplatekey($creator->getBase());
        $cls              = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'][ $tsk->getTemplatekey() ];
        $tsk->setTemplate(GeneralUtility::makeInstance($cls));
        $sourceclassname = $creator->getSourceclass();
        if (\class_exists($sourceclassname)) {
            $sourceclass = GeneralUtility::makeInstance(ltrim($sourceclassname, '\\'), $creator);
            $tsk->source = $sourceclass instanceof SourceInterface ? $sourceclass : GeneralUtility::makeInstance(Localdatabase::class, $creator);
            $tsk->source->setLogger($logger);
        }
        $pid = $creator->getSourcepid();
        $tsk->setSiteconfig($tsk->source->getSiteConfig($pid));
        return $tsk;
    }
}
