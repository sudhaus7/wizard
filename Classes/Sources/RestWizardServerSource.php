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

use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Services\FolderService;
use SUDHAUS7\Sudhaus7Wizard\Services\RestWizardRequest;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class RestWizardServerSource implements SourceInterface
{
    use DbTrait;
    use LoggerAwareTrait;

    protected array $remoteTables = [];
    protected ?Creator $creator = null;
    private array $tree = [];
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
        'imports'=>[

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
        // something differen? domain?

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        try {
            $site = $siteFinder->getSiteByPageId((int)$id);
            return $site->getConfiguration();
        } catch (SiteNotFoundException $e) {
            // no harm done
            $x = 1;
        } catch (\Exception $e) {
            $x =1;
        }
        return $this->siteconfig;
    }

    protected $rowCache = [];

    /**
     * @inheritDoc
     */
    public function getRow($table, $where = [])
    {
        if (!empty($this->remoteTables) && !\in_array($table, $this->remoteTables)) {
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
            $content = $this->getAPI()->request($endpoint);
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
     */
    public function getRows($table, $where = [])
    {
        if (!empty($this->remoteTables) && !\in_array($table, $this->remoteTables)) {
            return [];
        }

        $fields = array_keys($where);
        $values = array_values($where);

        if ((int)$values[0] < 0) {
            return [];
        }

        $endpoint = sprintf('content/%s/%s/%d', $table, $fields[0], $values[0]);
        $this->logger->debug('getRows ' . $endpoint);
        $content = $this->getAPI()->request($endpoint);
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
     */
    public function getTree($start)
    {
        $endpoint = sprintf('tree/%d', $start);
        $this->logger->debug('getTree ' . $endpoint);
        $content = $this->getAPI()->request($endpoint);
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function ping()
    {
        // TODO: Implement ping() method.
    }

    /**
     * @inheritDoc
     */
    public function getIrre($table, $uid, $pid, array $oldrow, array $columnconfig, $pidlist = [])
    {
        if (!empty($this->remoteTables) && !\in_array($table, $this->remoteTables)) {
            return [];
        }
        if ($uid < 0 || $pid < 0) {
            return [];
        }
        $where = [
            $columnconfig['config']['foreign_field']=>$uid,
        ];
        if (isset($columnconfig['config']['foreign_table_field'])) {
            $where[$columnconfig['config']['foreign_table_field']] = $table;
        }

        if (isset($columnconfig['config']['foreign_match_fields']) && !empty($columnconfig['config']['foreign_match_fields'])) {
            foreach ($columnconfig['config']['foreign_match_fields'] as $ff => $vv) {
                $where[$ff]=$vv;
            }
        }

        $endpoint = sprintf('content/%s', $columnconfig['config']['foreign_table']);

        $this->logger->debug('getIRRE ' . $endpoint . ' ' . \json_encode($where));
        $content = $this->getAPI()->post($endpoint, $where);
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function handleFile(array $sys_file, $newidentifier)
    {
        $this->logger->debug('handleFile ' . $newidentifier . ' START');

        $folder = GeneralUtility::makeInstance(FolderService::class)->getOrCreateFromIdentifier(dirname($newidentifier));

        $newfilename = $folder->getStorage()->sanitizeFileName(basename($newidentifier));
        $newidentifier = $folder->getIdentifier() . $newfilename;
        if ($folder->hasFile($newfilename)) {
            $this->logger->debug('file exists - END' . Environment::getPublicPath() . '/fileadmin' . $newidentifier);

            $file = $folder->getFile($newfilename);

            $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file')
                                 ->select(
                                     [ '*' ],
                                     'sys_file',
                                     ['uid'=>$file->getUid()]
                                 );
            $sys_file = $res->fetchAssociative();
            if (!$sys_file) {
                return ['uid'=>0];
            }
            return $sys_file;
        }

        $this->logger->debug('fetching ' . $this->getAPI()->getAPIHOST() . 'fileadmin/' . trim($sys_file['identifier'], '/'));

        $buf = @\file_get_contents($this->getAPI()->getAPIHOST() . 'fileadmin' . $sys_file['identifier']);
        if (!$buf) {
            $this->logger->error('fetch failed' . $this->getAPI()->getAPIHOST() . 'fileadmin/' . trim($sys_file['identifier'], '/'));
            return ['uid'=>0];
        }

        $tempfile = \tempnam(\sys_get_temp_dir(), 'wizarddl');
        \file_put_contents($tempfile, $buf);

        $file = $folder->addFile($tempfile, basename($newidentifier));
        @unlink($tempfile);

        $this->logger->debug('wrote file ' . Environment::getPublicPath() . '/fileadmin' . $newidentifier);

        $olduid = $sys_file['uid'];

        unset($sys_file['uid']);

        $uid = $file->getUid();

        try {
            $endpoint = sprintf('content/%s/file/%d', 'sys_file_metadata', $olduid);
            $this->logger->debug('FILE metadata fetching ' . $endpoint);
            $content  = $this->getAPI()->request($endpoint);
            if (\is_array($content) && !empty($content) && !empty($content[0])) {
                $sys_file_metadata = $content[0];
                unset($sys_file_metadata['uid']);
                $sys_file_metadata['file'] = $uid;
                self::insertRecord('sys_file_metadata', $sys_file_metadata);
            }
        } catch (\Exception $e) {
            $this->logger->error('FILE fetching ' . $endpoint . ' : ' . $e->getMessage());
        }
        $sys_file = BackendUtility::getRecord('sys_file', $uid);
        $this->logger->debug('handleFile ' . $newidentifier . ' END');
        return $sys_file;
    }

    /**
     * @inheritDoc
     */
    public function getMM($mmtable, $uid, $tablename)
    {
        if (!empty($this->remoteTables) && !\in_array($mmtable, $this->remoteTables)) {
            return [];
        }
        $endpoint = sprintf('content/%s/uid_local/%d', $mmtable, $uid);
        $this->logger->debug('getMM ' . $endpoint);
        $content  = $this->getAPI()->request($endpoint);
        if (\is_array($content)) {
            return $content;
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function sourcePid()
    {
        return $this->creator->getSourcepid();
    }

    /**
     * @inheritDoc
     */
    public function getTables()
    {
        $this->logger->debug('getTables');
        if (empty($this->remoteTables)) {
            $this->remoteTables = $this->getAPI()->request('tables');
        }
        return \array_intersect(array_keys($GLOBALS['TCA']), $this->remoteTables);
    }

    public function getSites()
    {
        $endpoint = 'content/pages/is_siteroot/1';
        $this->logger->debug('getSites ' . $endpoint);
        $content = $this->getAPI()->request($endpoint);
        return $content;
    }

    public function getAPI(): RestWizardRequest
    {
        throw new \Exception('implement the getAPI method first', 1696870054);
    }

    public function filterByPid(string $table, array $pidList): array
    {
        $preList = array_filter($pidList, function ($v) { return (int)$v > 0; });

        $filteredList = [];
        if (count($preList)>0) {
            $endpoint = sprintf('filter/%s/pid', $table);
            $this->logger->debug('filterByPid ' . $endpoint);
            $filteredList = $this->getAPI()->post($endpoint, [ 'values' => implode(',', $preList) ]);
        }
        return $filteredList;
    }
}
