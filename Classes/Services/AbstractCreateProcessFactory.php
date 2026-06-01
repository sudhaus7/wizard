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

use function class_exists;

use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Events\LoadInitialSiteConfigEvent;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Sources\LocalDatabase;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract factory implementation used in the default CreateProcessFactory,
 * but can be used as a base for custom factory implementation.
 */
abstract class AbstractCreateProcessFactory implements CreateProcessFactoryInterface
{
    public function get(Creator $creator, ?LoggerInterface $logger = null): CreateProcess
    {
        /** @var CreateProcess $task */
        $task = GeneralUtility::makeInstance(CreateProcess::class);
        if ($logger instanceof LoggerInterface) {
            $task->setLogger($logger);
        }
        $task->setTask($creator);
        $task->setTemplateKey($creator->getBase());
        /** @var class-string $processInterface */
        $processInterface              = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'][ $task->getTemplateKey() ];
        /** @var WizardProcessInterface $wizardProcess */
        $wizardProcess = GeneralUtility::makeInstance($processInterface);
        $task->setTemplate($wizardProcess);
        $sourceClassName = $creator->getSourceclass();
        if (class_exists($sourceClassName)) {
            $sourceClass = GeneralUtility::makeInstance(ltrim($sourceClassName, '\\'));
            $task->setSource($sourceClass instanceof SourceInterface ? $sourceClass : GeneralUtility::makeInstance(LocalDatabase::class));
            $task->getSource()->setCreateProcess($task);
            $task->getSource()->setCreator($creator);
            if ($logger instanceof LoggerInterface) {
                $task->getSource()->setLogger($logger);
            }
        }
        $pid = $creator->getSourcepid();
        $siteconfig = $task->getSource()->getSiteConfig($pid);

        // wanted to do this early to have more control over where the source is loaded
        $event = new LoadInitialSiteConfigEvent($pid, $siteconfig, $task);
        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($event);
        $task->setSiteConfig($event->getSiteconfig());
        return $task;
    }
}
