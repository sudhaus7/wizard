<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Dto;

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\WizardProcess\WizardProcessInterface;

interface CreateProcessInterface
{
    public function getWizardProcess(): WizardProcessInterface;

    public function getCreator(): Creator;
}
