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

namespace SUDHAUS7\Sudhaus7Wizard\Core13\Backend\TCA;

use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\TcaConfigurationInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * TYPO3 v13: search fields are listed in ctrl.searchFields, flex data structures are keyed by
 * the record type in the field's "ds" array and resolved through "ds_pointerField".
 */
#[AsAlias(
    id: TcaConfigurationInterface::class,
    public: true,
)]
final class TcaConfiguration implements TcaConfigurationInterface
{
    public function configureSearchFields(array $tableTca, array $searchFields): array
    {
        $tableTca['ctrl']['searchFields'] = implode(',', $searchFields);
        return $tableTca;
    }

    public function setDefaultFlexDataStructure(array $tableTca, string $field, string $dataStructure): array
    {
        $tableTca['columns'][$field]['config']['ds']['default'] = $dataStructure;
        $tableTca['columns'][$field]['config']['ds_pointerField'] = $tableTca['ctrl']['type'];
        return $tableTca;
    }

    public function hasFlexDataStructure(array $tableTca, string $field, string $recordType): bool
    {
        return isset($tableTca['columns'][$field]['config']['ds'][$recordType]);
    }

    public function addFlexDataStructure(array $tableTca, string $field, string $recordType, string $dataStructure): array
    {
        $tableTca['columns'][$field]['config']['ds'][$recordType] ??= $dataStructure;
        return $tableTca;
    }
}
