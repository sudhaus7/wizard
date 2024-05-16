<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\WizardProcess;

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Exception\NoResponsibleWizardProcessFoundException;

final class WizardProcessFactory
{
    public function __construct(private WizardProcessRegistry $createProcessRegistry)
    {}

    /**
     * @throws NoResponsibleWizardProcessFoundException
     */
    public function getCreateProcessByCreator(Creator $creator): WizardProcessInterface
    {
        $processClassName = $creator->getWizardProcessClass();
        return $this->createProcessRegistry->getProcessByClassName($processClassName);
    }
}
