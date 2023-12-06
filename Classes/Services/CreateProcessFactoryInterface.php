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

/**
 * Factory interface for `CreateProcess` instantiation.
 */
interface CreateProcessFactoryInterface
{
    /**
     * Factory method to build a `CreateProcess` instance based on the `Creator` dataset.
     *
     * @param Creator $creator
     * @param LoggerInterface|null $logger
     * @return CreateProcess
     */
    public function get(Creator $creator, ?LoggerInterface $logger = null): CreateProcess;
}
