<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Dto;

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\WizardProcess\WizardProcessInterface;

abstract class AbstractCreateProcess implements CreateProcessInterface
{
    public function __construct(
        private Creator $creator,
        private WizardProcessInterface $wizardProcess
    ) {
    }

    public function getWizardProcess(): WizardProcessInterface
    {
        return $this->wizardProcess;
    }

    public function getCreator(): Creator
    {
        return $this->creator;
    }
}
