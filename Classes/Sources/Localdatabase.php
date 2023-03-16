<?php

/*
 * This file is part of the TYPO3 project.
 * (c) 2022 B-Factor GmbH
 *          Sudhaus7
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 * @copyright 2022 B-Factor GmbH https://b-factor.de/
 * @author Frank Berger <fberger@b-factor.de>
 * @author Daniel Simon <dsimon@b-factor.de>
 */

namespace SUDHAUS7\Sudhaus7Wizard\Sources;

use Doctrine\DBAL\Statement;
use SUDHAUS7\Sudhaus7Base\Tools\DB;
use SUDHAUS7\Sudhaus7Base\Tools\Globals;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Localdatabase implements SourceInterface
{
    private Creator $creator;
    private array $tree = [];

    public function __construct(Creator $creator)
    {
        $this->creator = $creator;
    }

    public function getTree($start)
    {
        /** @var QueryBuilder $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        /** @var Statement $stmt */
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

    public function ping()
    {
        $db = Globals::db();
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
            $tmp = str_replace('###CURRENT_PID###', $pid, $tmp);
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
     * @param array $sys_file
     * @param string $newidentifier
     *
     * @return array
     */
    public function handleFile(array $sys_file, $newidentifier)
    {
        echo 'cp ' . Environment::getPublicPath() . '/' . '/fileadmin' . $sys_file['identifier'] . ' ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier;
        echo "\n";
        echo 'chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier;
        echo "\n";
        echo 'chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier;
        echo "\n";
        exec('cp ' . Environment::getPublicPath() . '/' . '/fileadmin' . $sys_file['identifier'] . ' ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);

        exec('chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        exec('chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        $sys_file_metadata = DB::getRecord('sys_file_metadata', $sys_file['uid'], 'file');
        unset($sys_file['uid']);
        $sys_file['identifier'] = $newidentifier;
        $sys_file['identifier_hash'] = sha1($sys_file['identifer']);
        $sys_file['folder_hash'] = sha1(dirname($sys_file['identifer']));

        [$affected,$uid] = DB::insertRecord('sys_file', $sys_file);

        if (!empty($sys_file_metadata)) {
            unset($sys_file_metadata['uid']);
            $sys_file_metadata['file'] = $uid;
            DB::insertRecord('sys_file_metadata', $sys_file_metadata);
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

    public function pageSort($new)
    {
        $page = DB::getRecord('pages', $new);
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

        $query->executeStatement('SET @count=16');
        $query->executeStatement('update pages set sorting=@count:=@count+16 where pid=' . $page['pid'] . ' order by doktype desc,title asc');
    }

    public function sourcePid()
    {
        return $this->creator->getSourcepid();
    }

    public function getTables()
    {
        return array_keys($GLOBALS['TCA']);
    }

    public function getRow($table, $where = [], $pidfilter = [])
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $row   = $query->select(
            [ '*' ],
            $table,
            $where
        )->fetchAssociative();
        return $row ?? [];
    }

    public function getRows($table, $where = [], $pidfilter = [])
    {
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $rows = $query->select(
            [ '*' ],
            $table,
            $where
        )->fetchAllAssociative();
        return $rows ?? [];
    }
}
