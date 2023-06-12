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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Localdatabase implements SourceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use DbTrait;
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

    private ?Creator $creator = null;

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

    public function __construct()
    {
    }

    public function getSiteConfig(mixed $id): array
    {
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

    public function getTree($start): array
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
            if (!\in_array($p[0], $this->tree)) {
                $this->tree[] = $p[0];
                $this->getTree($p[0]);
            }
        }
        //}
        return $this->tree;
    }

    public function ping(): void
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        if (!$db->isConnected()) {
            $db->connect();
        }
    }

    public function getIrre($table, $uid, $pid, array $oldrow, array $columnconfig, $pidlist = [])
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($columnconfig['config']['foreign_table']);

        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $where = [
            $query->expr()->eq($columnconfig['config']['foreign_field'], $uid),
            $query->expr()->in('pid', $pidlist),
        ];

        if (isset($columnconfig['config']['foreign_table_field'])) {
            $where[] = $query->expr()->eq($columnconfig['config']['foreign_table_field'], $query->createNamedParameter($table));
        }
        if (isset($columnconfig['config']['foreign_match_fields']) && !empty($columnconfig['config']['foreign_match_fields'])) {
            foreach ($columnconfig['config']['foreign_match_fields'] as $ff => $vv) {
                $where[] = $query->expr()->eq($ff, $query->createNamedParameter($vv));
            }
        }

        if (isset($columnconfig['config']['foreign_table_where'])) {
            $tmp = $columnconfig['config']['foreign_table_where'];
            $tmp = str_replace('###CURRENT_PID###', $pid, (string)$tmp);
            $tmp = str_replace('###THIS_UID###', $uid, $tmp);
            foreach (array_keys($GLOBALS['TCA'][$columnconfig['config']['foreign_table']]['columns']) as $key) {
                $tmp = str_replace('###REC_FIELD_' . $key . '###', $oldrow[$key], $tmp);
            }
            //$sql .= ' '.$tmp;
        }

        $stmt = $query->select('*')
                      ->from($columnconfig['config']['foreign_table'])
                      ->where(...$where)
                      ->execute();

        $ret = $stmt->fetchAllAssociative();
        return $ret ?? [];
    }

    /**
     * @param string $newidentifier
     * @return array
     */
    public function handleFile(array $sys_file, $newidentifier)
    {
        $this->logger->notice('cp ' . Environment::getPublicPath() . '/fileadmin' . $sys_file['identifier'] . ' ' . Environment::getPublicPath() . '/fileadmin' . $newidentifier);
        //$this->logger->notice('chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        //$this->logger->notice('chmod ug+rw ' . Environment::getPublicPath() . 'fileadmin' . $newidentifier);

        exec('cp ' . Environment::getPublicPath() . '/fileadmin' . $sys_file['identifier'] . ' ' . Environment::getPublicPath() . '/fileadmin' . $newidentifier);

        //exec('chown www-data:www-data ' . Environment::getPublicPath() . 'fileadmin' . $newidentifier);
        //exec('chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);

        /** @var \TYPO3\CMS\Core\Database\Connection $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_metadata');
        /** @var \Doctrine\DBAL\Result $res */
        $res = $query->select(
            [ '*' ],
            'sys_file_metadata',
            ['file'=>$sys_file['uid']]
        );
        $sys_file_metadata = $res->fetchAssociative();

        unset($sys_file['uid']);
        $sys_file['identifier'] = $newidentifier;
        $sys_file['identifier_hash'] = sha1((string)$sys_file['identifier']);
        $sys_file['folder_hash'] = sha1(dirname((string)$sys_file['identifier']));

        [$affected,$uid] = self::insertRecord('sys_file', $sys_file);

        if (!empty($sys_file_metadata)) {
            unset($sys_file_metadata['uid']);
            $sys_file_metadata['file'] = $uid;
            self::insertRecord('sys_file_metadata', $sys_file_metadata);
        }
        $sys_file['uid'] = $uid;
        return $sys_file;
    }

    public function getMM($mmtable, $uid, $tablename)
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($mmtable);
        $res = $query->select(
            [ '*' ],
            $mmtable,
            ['uid_local'=>$uid]
        );

        //$sql = 'select * from '.$mmtable.' where uid_local='.$uid;
        //$testres = Globals::db()->sql_query('show columns from '.$mmtable.'  like \'tablenames\'');
        //$test = Globals::db()->sql_fetch_row($testres);
        //if (!empty($test)) {
        //$sql .= ' and (tablenames="'.$tablename.'" or tablenames="")';
        //}
        $ret = $res->fetchAllAssociative();
        return $ret ?? [];
    }

    public function sourcePid(): ?string
    {
        return $this->creator->getSourcepid();
    }

    public function getTables(): array
    {
        return array_keys($GLOBALS['TCA']);
    }

    public function getRow($table, $where = [])
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $query->getRestrictions()->removeByType(HiddenRestriction::class);
        $query->getRestrictions()->removeByType(StartTimeRestriction::class);
        $query->getRestrictions()->removeByType(EndTimeRestriction::class);
        $query = $query->select('*')
            ->from($table);
        foreach ($where as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }
        $result = $query->execute();
        if ($result) {
            return $result->fetchAssociative();
        }
        return [];
    }

    public function getRows($table, $where = [])
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $query->getRestrictions()->removeByType(HiddenRestriction::class);
        $query->getRestrictions()->removeByType(StartTimeRestriction::class);
        $query->getRestrictions()->removeByType(EndTimeRestriction::class);
        $query = $query->select('*')
            ->from($table);
        foreach ($where as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }
        $result = $query->execute();
        if ($result) {
            return $result->fetchAllAssociative();
        }
        return [];
    }
}
