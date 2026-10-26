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

namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA;

/**
 * Writes the TCA options whose format differs between the supported TYPO3 core versions.
 *
 * Obtain the implementation for the running core through {@see \SUDHAUS7\Sudhaus7Wizard\Tools::getTcaConfiguration()}.
 */
interface TcaConfigurationInterface
{
    /**
     * Restricts the backend search of a table to the given fields.
     *
     * @param array<string, mixed> $tableTca
     * @param list<string> $searchFields
     * @return array<string, mixed>
     */
    public function configureSearchFields(array $tableTca, array $searchFields): array;

    /**
     * Sets the data structure a flex field uses for record types without a data structure of their own.
     *
     * @param array<string, mixed> $tableTca
     * @return array<string, mixed>
     */
    public function setDefaultFlexDataStructure(array $tableTca, string $field, string $dataStructure): array;

    /**
     * @param array<string, mixed> $tableTca
     */
    public function hasFlexDataStructure(array $tableTca, string $field, string $recordType): bool;

    /**
     * Assigns a data structure to a flex field for one record type, unless one is assigned already.
     *
     * @param array<string, mixed> $tableTca
     * @return array<string, mixed>
     */
    public function addFlexDataStructure(array $tableTca, string $field, string $recordType, string $dataStructure): array;
}
