<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Dto;

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\WizardProcess\WizardProcessInterface;

interface CreateProcessInterface
{
    public function getWizardProcess(): WizardProcessInterface;

    public function getCreator(): Creator;

    /**
     * @return array{
     *      uid: int,
     *      title: string,
     *      base: string,
     *      path: string
     *  }|array<string, int|string>
     */
    public function getFileMount(): array;

    /**
     * @param array{
     *      uid: int,
     *      title: string,
     *      base: string,
     *      path: string
     *  }|array<string, int|string> $fileMount
     */
    public function setFileMount(array $fileMount): void;
}
