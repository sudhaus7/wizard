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
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\FinalTTContentFormFrameworkListener;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\PreSysFileReferenceEventHandler;
use SUDHAUS7\Sudhaus7Wizard\EventHandlers\TypoLinkinRichTextFieldsEvent;
use SUDHAUS7\Sudhaus7Wizard\Sources\Localdatabase;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $services = $containerConfigurator->services();
    $services->defaults()
             ->public()
             ->autowire()
             ->autoconfigure();

    $services->load('SUDHAUS7\\Sudhaus7Wizard\\', __DIR__ . '/../Classes/')
             ->exclude([
                 __DIR__ . '/../Classes/Domain/Model/',
                 __DIR__ . '/../Classes/Events/',
             ]);

    $services->alias(SourceInterface::class, Localdatabase::class);
    $services->set(RunCommand::class)
        ->tag('console.command', [
            'command'=>'sudhaus7:wizard',
            'description'=>'run wizard tasks',
            'schedulable'=>true,
        ]);

    $services->set(PreSysFileReferenceEventHandler::class)
             ->tag('event.listener', ['identifier'=>'s7wizardBaseHandleSysFileReferences']);
    $services->set(FinalTTContentFormFrameworkListener::class)
             ->tag('event.listener', ['identifier'=>'s7wizardBaseFinalTTContentFormFrameworkListener']);
    $services->set(TypoLinkinRichTextFieldsEvent::class)
             ->tag('event.listener', ['identifier'=>'s7wizardTypoLinkinRichTextFieldsEventListener']);
};
