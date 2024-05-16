<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Service;

use SUDHAUS7\Sudhaus7Wizard\Exception\DataHandlerExecutionFailedException;
use SUDHAUS7\Sudhaus7Wizard\Exception\TableNotDefinedInTCAException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

final class DataHandlingService implements SingletonInterface
{
    /**
     * This array keeps the Data for the TYPO3 DataHandler
     * Mapping inside should be compatible with DataHandler
     * @example $data['pages']['NEW1234'] = []
     * @var array<string, array<int|string, array<non-empty-string, int|string>>>
     */
    private array $data = [];

    /**
     * This array keeps the mapping from old uid to new Identifier for each table
     * @example $oldIdToNewIdentifierMap['pages'][23] = 'NEW1234';
     * @var array<non-empty-string, array<positive-int, non-empty-string>>
     */
    private array $oldIdToNewIdentifierMap = [];

    /**
     * @param non-empty-string $tableName
     * @param array<non-empty-string, mixed> $data
     * @return non-empty-string The NEW identifier
     */
    public function addRecord(string $tableName, array $data): string
    {
        $newIdentifier = StringUtility::getUniqueId('NEW');

        $originalUid = $data['uid'] ?? 0;

        $this->data[$tableName][$newIdentifier] = $this->cleanFieldsBeforeInsert($tableName, $data);
        $this->oldIdToNewIdentifierMap[$tableName][$originalUid] = $newIdentifier;
        return $newIdentifier;
    }

    /**
     * @param non-empty-string $tableName
     * @param array<non-empty-string, mixed> $data
     * @param positive-int $recordUid
     */
    public function updateRecord(string $tableName, array $data, int $recordUid): void
    {
        $this->data[$tableName][$recordUid] = $this->cleanFieldsBeforeInsert($tableName, $data);
    }

    /**
     * @throws DataHandlerExecutionFailedException
     */
    public function immediatelyUpdateRecord(string $tableName, array $data, int $recordUid): void
    {
        $data = [
            $tableName => [
                $recordUid => $this->cleanFieldsBeforeInsert($tableName, $data),
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if (count($dataHandler->errorLog) > 0) {
            throw new DataHandlerExecutionFailedException(
                'DataHandler Failed to execute immediately record update',
                1715886300091
            );
        }
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array<array-key, mixed>
     */
    private function cleanFieldsBeforeInsert(string $tableName, array $row): array
    {
        if (
            !array_key_exists($tableName, $GLOBALS['TCA'] ?? [])
            || !is_array($GLOBALS['TCA'][$tableName]['columns'])
        ) {
            throw new TableNotDefinedInTCAException(
                sprintf('Table "%s" not defined in TCA', $tableName),
                1715703098757
            );
        }

        // hard unset the uid, as this will not be used in this context
        unset($row['uid']);

        foreach ($row as $columnName => $value) {
            if (
                (
                    !array_key_exists($columnName, $GLOBALS['TCA'][$tableName]['columns'])
                    && !in_array($columnName, $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
                    && !$this->checkForControlField($GLOBALS['TCA'][$tableName]['ctrl'], $columnName)
                )
                || $this->isAutoSetFieldByDataHandler($GLOBALS['TCA'][$tableName]['ctrl'], $columnName)
            ) {
                unset($row[$columnName]);
            }
        }
        return $row;
    }

    /**
     * This method checks against auto-created and not configured fields in TCA
     * control section.
     * Actually, only sortby is checked, as other fields are set default by
     * DataHandler such as tstamp, crdate, cruser_id (deprecated)
     *
     * If a field is set inside control AND is configured in columns, this
     * method is not called, as the check for columsn is done before
     *
     *
     * @param array{
     *     sortby?: string
     * } $controlSection
     */
    private function checkForControlField(array $controlSection, string $columnName): bool
    {
        if ($columnName === ($controlSection['sortby'] ?? '')) {
            return true;
        }
        return false;
    }

    /**
     * This method checks if a field is auto-set by DataHandler. In this case
     * it returns true, so the field can be removed as not needed and auto-set
     * by DataHandler itself
     *
     * @param array{
     *     cruser_id?: string,
     *     tstamp?: string,
     *     crdate?: string
     * } $controlSection
     * @param string $columnName
     * @return bool
     */
    private function isAutoSetFieldByDataHandler(array $controlSection, string $columnName): bool
    {
        if (($controlSection['cruser_id'] ?? '') === $columnName) {
            return true;
        }

        if (($controlSection['tstamp'] ?? '') === $columnName) {
            return true;
        }

        if (($controlSection['crdate'] ?? '') === $columnName) {
            return true;
        }

        return false;
    }
}
