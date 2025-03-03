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

use SUDHAUS7\Sudhaus7Wizard\Cli\RunCommand;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\DefaultSiteSorterListener;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\Extensions\TxNewsFixRecordHandler;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\Extensions\TxNewsPluginHandlerEvent;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\FinalTTContentFormFrameworkListener;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\PreSysFileReferenceEventHandler;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\SysFileReferenceHandleLinkFieldListener;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\TypeLinkListener;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\TypoLinkinRichTextFieldsEvent;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactory;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactoryInterface;
use SUDHAUS7\Sudhaus7Wizard\Sources\LocalDatabase;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $services = $containerConfigurator->services();
    $services->defaults()
             // @todo public as default is a bad practice - change this and declare only required services public.
             ->public()
             ->autowire()
             ->autoconfigure();

    $services->load('SUDHAUS7\\Sudhaus7Wizard\\', __DIR__ . '/../Classes/')
             ->exclude([
                 __DIR__ . '/../Classes/Domain/Model/',
                 __DIR__ . '/../Classes/Events/',
                 __DIR__ . '/../Classes/Backend/',
                 __DIR__ . '/../Classes/Logger/',
             ]);

    $services->alias(CreateProcessFactoryInterface::class, CreateProcessFactory::class);
    $services->alias(SourceInterface::class, LocalDatabase::class);
    $services->set(RunCommand::class)
        ->tag('console.command', [
            'command' => 'sudhaus7:wizard',
            'description' => 'run wizard tasks',
            'schedulable' => true,
        ]);

    $services->set(PreSysFileReferenceEventHandler::class)
             ->tag('event.listener', ['identifier' => 's7wizardBaseHandleSysFileReferences']);

    $services->set(DefaultSiteSorterListener::class)
        ->tag('event.listener', ['identifier' => 's7wizardDefaultSiteSorterListener']);

    $services->set(FinalTTContentFormFrameworkListener::class)
             ->tag('event.listener', ['identifier' => 's7wizardBaseFinalTTContentFormFrameworkListener']);

    $services->set(TypoLinkinRichTextFieldsEvent::class)
             ->tag('event.listener', ['identifier' => 's7wizardTypoLinkinRichTextFieldsEventListener']);

    $services->set(TxNewsPluginHandlerEvent::class)
             ->tag('event.listener', ['identifier' => 's7wizardTxNewsPluginHandlerEvent']);
    $services->set(TxNewsFixRecordHandler::class)
             ->tag('event.listener', ['identifier' => 's7wizardTxNewsFixRecordHandler']);
    $services->set(SysFileReferenceHandleLinkFieldListener::class)
             ->tag('event.listener', ['identifier' => 's7wizardSysFileReferenceHandleLinkFieldListener']);
    $services->set( TypeLinkListener::class)
             ->tag('event.listener', ['identifier' => 's7wizardTypeLinkListener']);
};
