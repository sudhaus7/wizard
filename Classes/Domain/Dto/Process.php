<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Dto;

use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;

final class Process
{
    public function __construct(
        private Creator $creator,
        private ?LoggerInterface $logger = null,
        private string $mappingFolder = ''
    ) {}

    public function getCreator(): Creator
    {
        return $this->creator;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getMappingFolder(): string
    {
        return $this->mappingFolder;
    }
}
