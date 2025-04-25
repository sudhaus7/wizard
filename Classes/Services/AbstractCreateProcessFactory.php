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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Events\LoadInitialSiteConfigEvent;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Sources\LocalDatabase;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function class_exists;

/**
 * Abstract factory implementation used in the default CreateProcessFactory,
 * but can be used as a base for custom factory implementation.
 */
abstract class AbstractCreateProcessFactory implements CreateProcessFactoryInterface
{
    public function get(Creator $creator, ?LoggerInterface $logger = null): CreateProcess
    {
        /** @var CreateProcess $tsk */
        $tsk = GeneralUtility::makeInstance(CreateProcess::class);
        if ($logger instanceof LoggerInterface) {
            $tsk->setLogger($logger);
        }
        $tsk->setTask($creator);
        $tsk->setTemplateKey($creator->getBase());
        /** @var class-string $processInterface */
        $processInterface              = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'][ $tsk->getTemplateKey() ];
        /** @var WizardProcessInterface $wizardProcess */
        $wizardProcess = GeneralUtility::makeInstance($processInterface);
        $tsk->setTemplate($wizardProcess);
        $sourceClassName = $creator->getSourceclass();
        if ( class_exists($sourceClassName)) {
            $sourceClass = GeneralUtility::makeInstance(ltrim($sourceClassName, '\\'));
            $tsk->setSource($sourceClass instanceof SourceInterface ? $sourceClass : GeneralUtility::makeInstance(LocalDatabase::class));
            $tsk->getSource()->setCreateProcess($tsk);
            $tsk->getSource()->setCreator($creator);
            $tsk->getSource()->setLogger($logger);
        }
        $pid = $creator->getSourcepid();
        $siteconfig = $tsk->getSource()->getSiteConfig($pid);

        // wanted to do this early to have more control over where the source is loaded
        $event = new LoadInitialSiteConfigEvent($pid, $siteconfig, $tsk);
        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($event);
        $tsk->setSiteConfig($event->getSiteconfig());
        return $tsk;
    }
}
