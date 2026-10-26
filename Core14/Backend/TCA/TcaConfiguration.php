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

namespace SUDHAUS7\Sudhaus7Wizard\Core14\Backend\TCA;

use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\TcaConfigurationInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * TYPO3 v14: every field of a searchable type is searched unless it is marked "searchable => false",
 * and a record type gets its own flex data structure through "columnsOverrides".
 */
#[AsAlias(
    id: TcaConfigurationInterface::class,
    public: true,
)]
final class TcaConfiguration implements TcaConfigurationInterface
{
    private const SEARCHABLE_TYPES = [
        'color',
        'email',
        'flex',
        'input',
        'json',
        'link',
        'slug',
        'text',
        'uuid',
    ];

    public function configureSearchFields(array $tableTca, array $searchFields): array
    {
        foreach ($tableTca['columns'] ?? [] as $fieldName => $fieldConfiguration) {
            $type = (string)($fieldConfiguration['config']['type'] ?? '');
            if (in_array($fieldName, $searchFields, true)
                || isset($fieldConfiguration['config']['searchable'])
                || !in_array($type, self::SEARCHABLE_TYPES, true)
            ) {
                continue;
            }
            $tableTca['columns'][$fieldName]['config']['searchable'] = false;
        }
        return $tableTca;
    }

    public function setDefaultFlexDataStructure(array $tableTca, string $field, string $dataStructure): array
    {
        $tableTca['columns'][$field]['config']['ds'] = $dataStructure;
        return $tableTca;
    }

    public function hasFlexDataStructure(array $tableTca, string $field, string $recordType): bool
    {
        return isset($tableTca['types'][$recordType]['columnsOverrides'][$field]['config']['ds']);
    }

    public function addFlexDataStructure(array $tableTca, string $field, string $recordType, string $dataStructure): array
    {
        $tableTca['types'][$recordType]['columnsOverrides'][$field]['config']['ds'] ??= $dataStructure;
        return $tableTca;
    }
}
