<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\WizardProcess;

use SUDHAUS7\Sudhaus7Wizard\Exception\NoResponsibleWizardProcessFoundException;

final class WizardProcessRegistry
{
    /**
     * @var array<string, WizardProcessInterface>
     */
    private array $wizardProcess = [];

    public function __construct(iterable $wizardProcesses)
    {
        foreach ($wizardProcesses as $wizardProcess) {
            if (!$wizardProcess instanceof WizardProcessInterface) {
                throw new \RuntimeException(
                    sprintf('Class "%s" must implement Interface "%s"', get_class($wizardProcess), WizardProcessInterface::class),
                    1715894592386
                );
            }
            $this->wizardProcess[get_class($wizardProcess)] = $wizardProcess;
        }
    }

    /**
     * @throws NoResponsibleWizardProcessFoundException
     */
    public function getProcessByClassName(string $processClassName): WizardProcessInterface
    {
        if (array_key_exists($processClassName, $this->wizardProcess)) {
            return $this->wizardProcess[$processClassName];
        }

        throw new NoResponsibleWizardProcessFoundException(
            sprintf('No valid WizardProcess found for class name "%"', $processClassName),
            1715894776860
        );
    }
}
