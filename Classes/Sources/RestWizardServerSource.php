<?php

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

namespace SUDHAUS7\Sudhaus7Wizard\Sources;

use function array_intersect;

use Doctrine\DBAL\Driver\Exception;

use function file_get_contents;
use function file_put_contents;
use function in_array;

use InvalidArgumentException;

use function is_array;
use function json_encode;

use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Events\FinalContentEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\GetResourceStorageEvent;
use SUDHAUS7\Sudhaus7Wizard\Services\FolderService;
use SUDHAUS7\Sudhaus7Wizard\Services\RestWizardRequest;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;

use function sys_get_temp_dir;
use function tempnam;

use Throwable;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class RestWizardServerSource implements SourceInterface
{
    use DbTrait;
    use LoggerAwareTrait;

    /**
     * @var array<array-key, mixed>
     */
    protected array $remoteTables = [];

    protected ?Creator $creator = null;
    protected ?CreateProcess $createProcess = null;

    /**
     * @var array<array-key, mixed>
     */
    private array $tree = [];

    /**
     * @var array<array-key, mixed>
     */
    protected array $rowCache = [];

    /**
     * @var array<array-key, mixed>
     */
    public array $siteconfig = [
        'base'          => 'domainname',
        'baseVariants'  => [],
        'errorHandling' => [],
        'languages'     =>
            [
                0 =>
                    [
                        'title'           => 'Default',
                        'enabled'         => true,
                        'base'            => '/',
                        'typo3Language'   => 'en',
                        'locale'          => 'enUS.UTF-8',
                        'iso-639-1'       => 'en',
                        'navigationTitle' => 'English',
                        'hreflang'        => 'en-US',
                        'direction'       => 'ltr',
                        'flag'            => 'en',
                        'languageId'      => '0',
                    ],
            ],
        'rootPageId'    => 0,
        'routes'        =>
            [
                0 =>
                    [
                        'route'   => 'robots.txt',
                        'type'    => 'staticText',
                        'content' => 'User-agent: *
Disallow: /typo3/
Disallow: /typo3_src/
Allow: /typo3/sysext/frontend/Resources/Public/*
',
                    ],
            ],
        'imports' => [

        ],
    ];
    public function setCreator(Creator $creator): void
    {
        // modify username
        // fetch original user
        $this->creator = $creator;
    }

    public function getCreator(): ?Creator
    {
        return $this->creator;
    }

    /**
     * @inheritDoc
     */
    public function getSiteConfig(mixed $id): array
    {
        $result = $this->getAPI()->request('/siteconfig/' . $id);
        if (is_array($result) && isset($result['rootPageId'])) {
            return $result;
        }
        return $this->siteconfig;
    }

    /**
     * @inheritDoc
     */
    public function getRow(string $table, array $where = []): mixed
    {
        if (!empty($this->remoteTables) && ! in_array($table, $this->remoteTables)) {
            return [];
        }
        if ($where['uid'] < 0) {
            return [];
        }

        if ($table === 'pages') {
            $endpoint = sprintf('page/%d', $where['uid']);
        } else {
            $endpoint = sprintf('content/%s/uid/%d', $table, $where['uid']);
        }
        $this->logger->debug('getRow ' . $endpoint);

        if (!isset($this->rowCache[$endpoint])) {
            try {
                $content = $this->getAPI()->request($endpoint);
            } catch (Throwable $e) {
                $this->logger->warning('getRow ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
                sleep(5);
                $content = $this->getAPI()->request($endpoint);
            }
            if ($table === 'pages') {
                $this->rowCache[$endpoint] = $content;
            } else {
                $this->rowCache[ $endpoint ] = $content[0] ?? [];
            }
        }
        return $this->rowCache[$endpoint];
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getRows(string $table, array $where = []): array
    {
        if (!empty($this->remoteTables) && ! in_array($table, $this->remoteTables)) {
            return [];
        }

        $fields = array_keys($where);
        $values = array_values($where);

        if ((int)$values[0] < 0) {
            return [];
        }

        $endpoint = sprintf('content/%s/%s/%d', $table, $fields[0], $values[0]);
        $this->logger->debug('getRows ' . $endpoint);

        try {
            $content = $this->getAPI()->request($endpoint);
        } catch (Throwable $e) {
            $this->logger->warning('getRows ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
            sleep(5);
            $content = $this->getAPI()->request($endpoint);
        }
        foreach ($content as $row) {
            $cacheendpoint = sprintf('content/%s/uid/%d', $table, $row['uid']);
            if (!isset($this->rowCache[$cacheendpoint])) {
                $this->rowCache[$cacheendpoint] = $row;
            }
        }
        return $content;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getTree(int $start): array
    {
        $endpoint = sprintf('tree/%d', $start);
        $this->logger->debug('getTree ' . $endpoint);
        try {
            $content = $this->getAPI()->request($endpoint);
        } catch (Throwable $e) {
            $this->logger->warning('getTree ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
            sleep(5);
            $content = $this->getAPI()->request($endpoint);
        }
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function ping(): void
    {
        // TODO: Implement ping() method.
    }

    /**
     * @inheritDoc
     */
    public function getIrre(
        string $table,
        int $uid,
        int $pid,
        array $oldRow,
        array $columnConfig,
        array $pidList = [],
        string $column = ''
    ): array {
        if (!empty($this->remoteTables) && ! in_array($table, $this->remoteTables)) {
            return [];
        }
        if ($uid < 0 || $pid < 0) {
            return [];
        }
        $where = [
            $columnConfig['config']['foreign_field'] => $uid,
        ];
        if (isset($columnConfig['config']['foreign_table_field'])) {
            $where[$columnConfig['config']['foreign_table_field']] = $table;
        }

        if (!empty($columnConfig['config']['foreign_match_fields'])) {
            foreach ($columnConfig['config']['foreign_match_fields'] as $ff => $vv) {
                $where[$ff] = $vv;
            }
        }

        $endpoint = sprintf('content/%s', $columnConfig['config']['foreign_table']);

        $this->logger->debug('getIRRE ' . $endpoint . ' ' . json_encode($where));
        try {
            $content = $this->getAPI()->post($endpoint, $where);
        } catch (Throwable $e) {
            $this->logger->warning('getIrre ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
            sleep(5);
            $content = $this->getAPI()->post($endpoint, $where);
        }
        return $content;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InsufficientFolderReadPermissionsException
     * @throws InsufficientFolderWritePermissionsException
     * @throws \Exception
     */
    public function handleFile(array $sysFile, string $newIdentifier): array
    {
        $this->logger->debug('handleFile ' . $newIdentifier . ' START');

        /** @var ResourceStorage $storage */
        $storage           = GeneralUtility::makeInstance(StorageRepository::class)->getDefaultStorage();

        $defaultStorageEvent = new GetResourceStorageEvent($storage, $this->getCreateProcess());
        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($defaultStorageEvent);
        $storage = $defaultStorageEvent->getStorage();

        $folder = GeneralUtility::makeInstance(FolderService::class)->getOrCreateFromIdentifier(dirname($newIdentifier), $storage);

        $newFileName = $folder->getStorage()->sanitizeFileName(basename($newIdentifier));
        $newIdentifier = $folder->getIdentifier() . $newFileName;
        if ($folder->hasFile($newFileName)) {
            $this->logger->debug('file exists - END' . Environment::getPublicPath() . '/fileadmin' . $newIdentifier);

            $file = $folder->getFile($newFileName);

            $res = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_file')
                ->select(
                    [ '*' ],
                    'sys_file',
                    ['uid' => $file->getUid()]
                );
            return $res->fetchAssociative() ?: ['uid' => 0];
        }

        $this->logger->debug('fetching ' . $this->getAPI()->getAPIFILEHOST() . 'fileadmin/' . trim($sysFile['identifier'], '/'));

        $remoteFilename = $sysFile['identifier'];
        $remoteFilename = str_replace(' ', '%20', $remoteFilename);

        $buf = @file_get_contents($this->getAPI()->getAPIFILEHOST() . 'fileadmin' . $remoteFilename);
        if (!$buf) {
            $this->logger->error('fetch failed' . $this->getAPI()->getAPIFILEHOST() . 'fileadmin/' . trim($sysFile['identifier'], '/'));
            return ['uid' => 0];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'wizarddl');
        file_put_contents($tempFile, $buf);

        $file = $folder->addFile($tempFile, basename($newIdentifier));
        @unlink($tempFile);

        $this->logger->debug('wrote file ' . Environment::getPublicPath() . '/fileadmin' . $newIdentifier);

        $olduid = $sysFile['uid'];

        unset($sysFile['uid']);

        $uid = $file->getUid();

        try {
            $endpoint = sprintf('content/%s/file/%d', 'sys_file_metadata', $olduid);
            $this->logger->debug('FILE metadata fetching ' . $endpoint);

            try {
                $content  = $this->getAPI()->request($endpoint);
            } catch (Throwable $e) {
                $this->logger->warning('handleFile ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
                sleep(5);
                $content  = $this->getAPI()->request($endpoint);
            }
            if (!empty($content) && !empty($content[0])) {
                $sys_file_metadata = $content[0];

                $subEventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
                $subEvent = new FinalContentEvent('sys_file_metadata', $sys_file_metadata, $this->getCreateProcess());

                $subEventDispatcher->dispatch($subEvent);
                $sys_file_metadata = $subEvent->getRecord();

                $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_metadata')
                                     ->select(
                                         [ '*' ],
                                         'sys_file_metadata',
                                         ['file' => $uid]
                                     );
                $newSysFileMetadata = $res->fetchAssociative();
                if (!empty($newSysFileMetadata)) {
                    $skipFields = [
                        'uid',
                        'pid',
                        'file',
                        'width', // width is already set by the copy procedure
                        'height', // height is already set by the copy procedure
                        'cruser_id',
                    ];
                    $update = [];
                    foreach ($sys_file_metadata as $k => $v) {
                        if (! in_array($k, $skipFields)) {
                            if (! empty($v) || (int)$v > 0) {
                                $update[ $k ] = $v;
                            }
                        }
                    }
                    if (!empty($update)) {
                        self::updateRecord('sys_file_metadata', $update, [ 'uid' => $newSysFileMetadata['uid'] ]);
                    }
                } else {
                    unset($sys_file_metadata['uid']);
                    $sys_file_metadata['file'] = $uid;
                    self::insertRecord('sys_file_metadata', $sys_file_metadata);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('FILE fetching ' . $endpoint . ' : ' . $e->getMessage());
        }
        $sysFile = BackendUtility::getRecord('sys_file', $uid);
        $this->logger->debug('handleFile ' . $newIdentifier . ' END');
        return $sysFile ?? ['uid' => 0];
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getMM(string $mmTable, int|string $uid, string $tableName): array
    {
        if (!empty($this->remoteTables) && ! in_array($mmTable, $this->remoteTables)) {
            return [];
        }
        $endpoint = sprintf('content/%s/uid_local/%d', $mmTable, $uid);
        $this->logger->debug('getMM ' . $endpoint);
        try {
            $content  = $this->getAPI()->request($endpoint);
        } catch (Throwable $e) {
            $this->logger->warning('getMM ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
            sleep(5);
            $content  = $this->getAPI()->request($endpoint);
        }
        if (is_array($content)) {
            return $content;
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function sourcePid(): int
    {
        return $this->creator->getSourcepid();
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getTables(): array
    {
        $this->logger->debug('getTables');
        if (empty($this->remoteTables)) {
            try {
                $this->remoteTables = $this->getAPI()->request('tables');
            } catch (Throwable $e) {
                $this->logger->warning('tables failed retrying in 5 seconds once ' . $e->getMessage());
                sleep(5);
                $this->remoteTables = $this->getAPI()->request('tables');
            }
        }
        return array_intersect(array_keys($GLOBALS['TCA']), $this->remoteTables);
    }

    /**
     * @return array<array-key, mixed>
     * @throws \Exception
     */
    public function getSites(): array
    {
        $endpoint = 'content/pages/is_siteroot/1';
        $this->logger->debug('getSites ' . $endpoint);
        try {
            $content = $this->getAPI()->request($endpoint);
        } catch (Throwable $e) {
            $this->logger->warning('getSites ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
            sleep(5);
            $content = $this->getAPI()->request($endpoint);
        }
        return $content;
    }

    /**
     * @throws \Exception
     */
    public function getAPI(): RestWizardRequest
    {
        throw new \Exception('implement the getAPI method first', 1696870054);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function filterByPid(string $table, array $pidList): array
    {
        $preList = array_filter($pidList, function ($v) { return (int)$v > 0; });

        $filteredList = [];
        if (count($preList) > 0) {
            $endpoint = sprintf('filter/%s/pid', $table);
            $this->logger->debug('filterByPid ' . $endpoint);
            try {
                $filteredList = $this->getAPI()->post($endpoint, [ 'values' => implode(',', $preList) ]);
            } catch (Throwable $e) {
                $this->logger->warning('filterByPid ' . $endpoint . ' failed retrying in 5 seconds once ' . $e->getMessage());
                sleep(5);
                $filteredList = $this->getAPI()->post($endpoint, [ 'values' => implode(',', $preList) ]);
            }
        }
        return $filteredList;
    }

    public function getCreateProcess(): CreateProcess
    {
        if ($this->createProcess === null) {
            throw new InvalidArgumentException('Create Process must be defined', 1715795482);
        }
        return $this->createProcess;
    }

    public function setCreateProcess(CreateProcess $createProcess): void
    {
        $this->createProcess = $createProcess;
    }
}
