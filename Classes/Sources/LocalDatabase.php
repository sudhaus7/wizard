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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Events\FinalContentEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\GetResourceStorageEvent;
use SUDHAUS7\Sudhaus7Wizard\Services\FolderService;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function in_array;

class LocalDatabase implements SourceInterface
{
    use LoggerAwareTrait;
    use DbTrait;

    /**
     * @var array<array-key, mixed>
     */
    private array $tree = [];

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

    private ?Creator $creator = null;
    protected ?CreateProcess $createProcess = null;
    /**
     * @return Creator
     */
    public function getCreator(): Creator
    {
        return $this->creator;
    }

    /**
     * @param Creator $creator
     */
    public function setCreator(Creator $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * @inheritDoc
     * @throws DBALException
     * @throws Exception
     */
    public function getTree(int $start): array
    {
        /** @var QueryBuilder $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $stmt = $query->select('uid')
            ->from('pages')
            ->where(
                $query->expr()->eq('pid', $start)
            )
            ->execute();

        while ($p = $stmt->fetchNumeric()) {
            if (! in_array($p[0], $this->tree)) {
                $this->tree[] = $p[0];
                $this->getTree($p[0]);
            }
        }
        return $this->tree;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getSiteConfig(mixed $id): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByRootPageId((int)$id);
            return $site->getConfiguration();
        } catch (SiteNotFoundException $e) {
            $this->logger->debug($e->getMessage(), [$id]);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage(), [$id]);
        }
        return $this->siteconfig;
    }

    public function ping(): void
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        if (!$db->isConnected()) {
            $db->connect();
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DBALException
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
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($columnConfig['config']['foreign_table']);

        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $where = [
            $query->expr()->in('pid', $pidList),
        ];
        if (isset($columnConfig['config']['foreign_field'])) {
            $where[] = $query->expr()->eq($columnConfig['config']['foreign_field'], $uid);
        } elseif (!empty($column)) {
            $where[] = $query->expr()->in('uid', GeneralUtility::intExplode(',', $oldRow[$column]));
        }

        if (isset($columnConfig['config']['foreign_table_field'])) {
            $where[] = $query->expr()->eq($columnConfig['config']['foreign_table_field'], $query->createNamedParameter($table));
        }
        if (!empty($columnConfig['config']['foreign_match_fields'])) {
            foreach ($columnConfig['config']['foreign_match_fields'] as $ff => $vv) {
                $where[] = $query->expr()->eq($ff, $query->createNamedParameter($vv));
            }
        }

        if (isset($columnConfig['config']['foreign_table_where'])) {
            $tmp = $columnConfig['config']['foreign_table_where'];
            $tmp = str_replace('###CURRENT_PID###', $pid, (string)$tmp);
            $tmp = str_replace('###THIS_UID###', $uid, $tmp);
            foreach (array_keys($GLOBALS['TCA'][$columnConfig['config']['foreign_table']]['columns']) as $key) {
                $tmp = str_replace('###REC_FIELD_' . $key . '###', $oldRow[$key], $tmp);
            }
        }

        $stmt = $query
            ->select('*')
            ->from($columnConfig['config']['foreign_table'])
            ->where(...$where)
            ->executeQuery();

        return $stmt->fetchAllAssociative() ?: [];
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InsufficientFolderReadPermissionsException
     * @throws InsufficientFolderWritePermissionsException
     */
    public function handleFile(array $sysFile, $newIdentifier): array
    {
        $this->logger->debug('handleFile ' . $newIdentifier . ' START');

	    /** @var ResourceStorage $storage */
	    $storage           = GeneralUtility::makeInstance(StorageRepository::class)->getDefaultStorage();

	    $defaultStorageEvent = new GetResourceStorageEvent($storage, $this);
	    GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($defaultStorageEvent);
	    $storage = $defaultStorageEvent->getStorage();

        $folder = GeneralUtility::makeInstance(FolderService::class)
            ->getOrCreateFromIdentifier(dirname($newIdentifier), $storage);

        $newFileName = $folder->getStorage()->sanitizeFileName(basename($newIdentifier));
        $newIdentifier = $folder->getIdentifier() . $newFileName;
        if ($folder->hasFile($newFileName)) {
            $this->logger->debug('file exists - END' . Environment::getPublicPath() . '/fileadmin' . $newIdentifier);
            $res = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_file')
                ->select(
                    [ '*' ],
                    'sys_file',
                    ['identifier' => $newIdentifier]
                );
            return $res->fetchAssociative() ?: [];
        }

        $this->logger->notice('cp ' . Environment::getPublicPath() . '/fileadmin' . $sysFile['identifier'] . ' ' . Environment::getPublicPath() . '/fileadmin' . $newIdentifier);

        $oldfile = $folder->getStorage()->getFileByIdentifier($sysFile['identifier']);
        $file = $oldfile->copyTo($folder);

        $newIdentifier = $file->getIdentifier();

        $uid = $file->getUid();

        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_metadata');
        $res = $query->select(
            [ '*' ],
            'sys_file_metadata',
            ['file' => $sysFile['uid']]
        );
        $sys_file_metadata = $res->fetchAssociative();

        if (!empty($sys_file_metadata)) {
            $subEventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
            $subEvent = new FinalContentEvent('sys_file_metadata', $sys_file_metadata, $this->getCreateProcess());

            $subEventDispatcher->dispatch($subEvent);
            $sys_file_metadata = $subEvent->getRecord();

            $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_metadata')
                                 ->select(
                                     [ '*' ],
                                     'sys_file_metadata',
                                     ['file'=>$uid]
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
                foreach ($sys_file_metadata as $k=>$v) {
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

        return   BackendUtility::getRecord('sys_file', $uid);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMM($mmTable, $uid, $tableName): array
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($mmTable);
        $res = $query->select(
            [ '*' ],
            $mmTable,
            ['uid_local' => $uid]
        );

        $ret = $res->fetchAllAssociative();
        return $ret ?? [];
    }

    public function sourcePid(): int
    {
        return $this->creator->getSourcepid();
    }

    /**
     * @inheritDoc
     */
    public function getTables(): array
    {
        return array_keys($GLOBALS['TCA']);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     * @throws Exception
     */
    public function getRow($table, $where = []): array
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $query
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);
        $query = $query->select('*')
            ->from($table)
            ->setMaxResults(1);
        foreach ($where as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }
        $result = $query->executeQuery();
        return $result->fetchAssociative() ?: [];
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DBALException
     */
    public function getRows($table, $where = []): array
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $query
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);
        $query = $query->select('*')
            ->from($table);
        foreach ($where as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }
        $result = $query->executeQuery();
        return $result->fetchAllAssociative() ?: [];
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DBALException
     */
    public function filterByPid(string $table, array $pidList): array
    {
        $preList = array_filter($pidList, function ($v) { return (int)$v > 0; });

        $filteredPidList = [];
        if (count($preList) > 0) {
            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $stmt  = $query
                ->selectLiteral('distinct pid')
                ->from($table)
                ->where(
                    $query->expr()->in('pid', $pidList)
                )
                ->executeQuery();
            while ($row = $stmt->fetchAssociative()) {
                $filteredPidList[] = $row['pid'];
            }
        }

        return $filteredPidList;
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
