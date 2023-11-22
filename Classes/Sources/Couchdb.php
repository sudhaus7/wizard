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

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 * @deprecated
 */
final class Couchdb implements SourceInterface
{
    use LoggerAwareTrait;
    use DbTrait;

    /**
     * @var array<array-key, mixed>
     */
    public array $siteconfig = [
        'base' => 'domainname',
        'baseVariants' => [],
        'errorHandling' => [],
        'languages' =>
            [
                0 =>
                    [
                        'title' => 'Default',
                        'enabled' => true,
                        'base' => '/',
                        'typo3Language' => 'en',
                        'locale' => 'enUS.UTF-8',
                        'iso-639-1' => 'en',
                        'navigationTitle' => 'English',
                        'hreflang' => 'en-US',
                        'direction' => 'ltr',
                        'flag' => 'en',
                        'languageId' => '0',
                    ],
            ],
        'rootPageId' => 0,
        'routes' =>
            [
                0 =>
                    [
                        'route' => 'robots.txt',
                        'type' => 'staticText',
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
    /**
     * @var array<array-key, mixed>
     */
    private array $views = [];
    /**
     * @var array<array-key, mixed>
     */
    private array $tree = [];
    private string $credentials = 'admin:sNvbVr2hWh4u4nQZf3nA4W';
    private ?Creator $creator = null;
    private array $maps = [

        'default' => '
function(doc) {
    if(doc.table == \'%1$s\'  %2$s ) {
        emit(doc._id,doc);
    }
}
        ',
        'pidin' => '
function(doc) {
	var pids = [%3$s];
    if(doc.table == \'%1$s\'  %2$s && pids.indexOf(parseInt(doc.row.pid)) > -1 ) {
        emit(doc._id,doc);
    }
}
        ',
    ];
    private string $couchdb;
    private array $usedTables = [];

    /**
     * @return Creator|null
     */
    public function getCreator(): ?Creator
    {
        return $this->creator;
    }

    /**
     * @param Creator|null $creator
     */
    public function setCreator(?Creator $creator): void
    {
        $this->creator = $creator;
        $this->couchdb = 'http://tools.sudhaus7.de:32768/' . $creator->getSourcepid() . '/';
        $this->addBaseViews();
    }

    private function addBaseViews(): void
    {
        $views = $this->getViews();
        $update = false;
        if (!isset($views['views']['gettables'])) {
            $views['views']['gettables'] = [
                'map' => 'function(doc) { emit(doc.table,null); }',
                'reduce' => 'function(keys,values) { return true; }',
            ];
            $update = true;
        }
        if ($update) {
            $this->put('_design/application', $views);
        }
        $this->views = array_keys($views['views']);
    }

    private function getViews()
    {
        $views = $this->get('_design/application');
        if ($views['error']) {
            $this->put('_design/application', [
                'language' => 'javascript',
                'views' => [
                    'dummy' => ['map' => 'function(doc) { emit(doc._id,doc); }'],
                ],
            ]);
            $views = $this->get('_design/application');
        }
        return $views;
    }

    private function get(string $id): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->couchdb . $id);
        if (!empty($this->credentials)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->credentials);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_POST,           1 );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/json',
            'Accept: */*',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return \json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    private function put(string $id, $data)
    {
        $payload = json_encode($data, JSON_THROW_ON_ERROR);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->couchdb . $id);
        if (!empty($this->credentials)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->credentials);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); /* or PUT */
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/json',
            'Accept: */*',
        ]);

        //curl_setopt($ch, CURLOPT_USERPWD, 'myDBusername:myDBpass');

        $response = curl_exec($ch);
        return \json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getSiteConfig(mixed $id): array
    {
        return $this->siteconfig;
    }

    /**
     * @param array<array-key, mixed> $where
     * @param array<array-key, mixed> $pidfilter
     * @throws \JsonException
     */
    public function getRow(string $table, array $where = [], array $pidfilter = []): mixed
    {
        if ($table == 'pages' && isset($where['uid']) && $where['uid'] == -1) {
            $template = empty($pidfilter) ? $this->maps['default'] : $this->maps['pidin'];
            $map = sprintf($template, $table, ' && doc.uid == doc.startid ', implode(',', $pidfilter));
            $data = $this->filter($map);
            $data['rows'][0]['value']['row']['pid'] = $this->creator->getPid();
        } else {
            $data = [];
            if (count($where) == 1 && isset($where['uid'])) {
                $tmp = $this->getbyid($table . '_' . $where['uid']);
                if (!isset($tmp['error'])) {
                    $data['rows'][0]['value'] = $tmp;
                }
            }

            if (empty($data)) {
                if (empty($pidfilter)) {
                    $this->addView($table, $where);
                    $url = $this->getViewurl($table, $where);
                    $this->out(__METHOD__ . ' ' . $url);
                    $data = $this->get($url);
                    print_r($data);
                    exit;
                }

                $template = empty($pidfilter) ? $this->maps['default'] : $this->maps['pidin'];
                $mywhere = $this->expandwhere($where, []);
                $wherestring = '';
                if (!empty($mywhere)) {
                    $wherestring = ' && ' . implode(' && ', $mywhere);
                }
                $map = sprintf($template, $table, $wherestring, implode(',', $pidfilter));
                $data = $this->filter($map);
            }
        }
        if ($data['rows'][0]['value']['table'] == 'pages' && $data['rows'][0]['value']['uid'] == $data['rows'][0]['value']['startid']) {
            $data['rows'][0]['value']['row']['pid'] = $this->creator->getPid();
        }

        $row = $data['rows'][0]['value']['row'];
        if (empty($row)) {
            print_r([__FILE__, __LINE__, $table, $where, $pidfilter, $map, $data]);
            exit;
        }
        self::cleanRow($table, $row);
        return $row;
    }

    private function filter(mixed $map): array
    {
        $views = $this->get('_design/tmpviews');
        if ($views['error']) {
            $this->put('_design/tmpviews', [
                'language' => 'javascript',
                'views' => [
                    'dummy' => ['map' => 'function(doc) { emit(doc._id,doc); }'],
                ],
            ]);
            $views = $this->get('_design/tmpviews');
        }

        $id = uniqid('tmp', true);
        $path = '_design/tmpviews/_view/' . $id;
        $views['views'][$id] = [
            'map' => $map,
        ];
        $this->out(__METHOD__ . ':' . print_r([$path, $views], true));
        $this->put('_design/tmpviews', $views);
        return $this->get($path . '/');
    }

    private function out(string $out): void
    {
        echo $out, "\n";
    }

    /**
     * @return array<array-key, mixed>
     * @throws \JsonException
     */
    private function getbyid(int|string $id): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->couchdb . $id);
        if (!empty($this->credentials)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->credentials);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/json',
            'Accept: */*',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return \json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<array-key, mixed> $where
     * @throws \JsonException
     */
    private function addView(string $table, array $where): void
    {
        $views = [];
        $keys = array_keys($where);
        \natsort($keys);
        $viewname = $table . '_by_' . implode('_', $keys);
        if (!array_key_exists($viewname, $views['views'])) {
            $views = $this->getViews();
            if (!isset($views['views'][$viewname])) {
                foreach ($keys as $k => $v) {
                    $keys[$k] = match ($v) {
                        'pid', 'uid' => 'doc.' . $v,
                        default => 'doc.row.' . $v,
                    };
                }
                $views['views'][$viewname] = [
                    'map' => 'function(doc) { var k=' . implode(
                        '+\'|\'+',
                        $keys
                    ) . '; log("' . $viewname . ' "+k); if(doc.table=="' . $table . '") { emit(k,doc._id); }}',
                ];
                $this->put('_design/application', $views);
                $this->out('Added View ' . $viewname);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $where
     * @throws \JsonException
     */
    private function getViewurl(string $table, array $where): string
    {
        return '_design/application/_view/' . $this->getViewname($table, $where) . '?keys=' . $this->getViewquery($where);
    }

    /**
     * @param array<array-key, mixed> $where
     */
    private function getViewname(string $table, array $where): string
    {
        $keys = array_keys($where);
        \natsort($keys);
        return $table . '_by_' . implode('_', $keys);
    }

    /**
     * @param array<array-key, mixed> $where
     * @throws \JsonException
     */
    private function getViewquery(array $where): string
    {
        $keys = array_keys($where);
        \natsort($keys);
        $q = [];
        foreach ($keys as $k) {
            $q[] = $where[$k];
        }
        return \urlencode(json_encode([implode('|', $q)], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<array-key, mixed> $where
     * @param array<array-key, mixed> $mywhere
     * @return array <array-key, mixed>
     */
    private function expandwhere(array $where, array $mywhere): array
    {
        if (!empty($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $mywhere[] = ' ' . implode(' && ', $this->expandwhere($value, [])) . '  ';
                } else {
                    $mywhere[] = sprintf('doc.row.%s == \'%s\'', $key, $value);
                }
            }
        }
        return $mywhere;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function cleanRow(string $table, array &$row): void
    {
        $fields = [];
        $fields = (array)$fields;
        $fields[] = 'uid';
        $fields[] = 'pid';
        $fields[] = 'tstamp';
        $fields[] = 'crdate';
        $fields[] = 'cruser_id';
        $fields[] = 'deleted';
        $fields[] = 'hidden';
        $fields[] = 'sorting';
        $fields[] = 'sortorder';
        $fields[] = 'perms_userid';
        $fields[] = 'perms_groupid';
        $fields[] = 'perms_user';
        $fields[] = 'perms_group';
        $fields[] = 'perms_everybody';
        $fields[] = 't3_origuid';
        $fields[] = 'uid_local';
        $fields[] = 'uid_foreign';
        $fields[] = 'sha1sum';
        $fields[] = 'medium';
        $fields[] = 'tx_rlmptmplselector_main_tmpl';
        $fields[] = 'tx_rlmptmplselector_ca_tmpl';
        $fields[] = 'bfelem_flex';
        foreach (array_keys($row) as $k) {
            if (!array_key_exists($k, $GLOBALS['TCA'][$table]['columns'])) {
                unset($row[$k]);
            }
        }
    }

    public function getTree($start): array
    {
        if ($start == -1) {
            $map = '
    	    function(doc) {
			    if(doc.table == \'pages\' && doc.uid==doc.startid ) {
			       emit(doc._id,doc);
			    }
			}
			';
            $data = $this->filter($map);
            $data = [$data['rows'][0]['value']];
        } else {
            $where = ['pid' => $start];
            $this->addView('pages', $where);
            $data = $this->getRows('pages', ['pid' => $start]);

            //print_r($data);exit;
        }

        //        print_r($data);exit;

        foreach ($data as $row) {
            if (!\in_array((int)$row['uid'], $this->tree)) {
                $this->tree[] = (int)$row['uid'];
                $this->getTree((int)$row['uid']);
            }
        }

        return $this->tree;
    }

    public function getRows($table, $where = [], $pidfilter = []): array
    {
        if ($table == 'sys_file') {
            return [];
        }

        if (empty($pidfilter)) {
            $this->addView($table, $where);
            $url = $this->getViewurl($table, $where);
            //$this->out( __METHOD__ . ' ' . $url );
            $temp = $this->get($url);
            $data = ['rows' => []];
            foreach ($temp['rows'] as $e) {
                $tmp = $this->getbyid($e['value']);
                $data['rows'][] = ['value' => $tmp];
            }
        } else {
            $wherestring = '';
            $mywhere = $this->expandwhere($where, []);
            if (!empty($mywhere)) {
                $wherestring = ' && ' . implode(' && ', $mywhere);
            }
            //$template = empty($pidfilter) ? $this->maps['default'] : $this->maps['pidin'];
            $map = sprintf($this->maps['pidin'], $table, $wherestring, implode(',', $pidfilter));
            $data = $this->filter($map);
        }

        $rows = [];
        foreach ($data['rows'] as $d) {
            if ($d['value']['table'] == 'pages' && $d['value']['uid'] == $d['value']['pid']) {
                $d['value']['row']['pid'] = $this->creator->getPid();
            }
            $row = $d['value']['row'];
            self::cleanRow($table, $row);
            $rows[] = $row;
        }

        return $rows;
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
        $where = [
            $columnConfig['config']['foreign_field'] => $uid,
        ];

        if (isset($columnConfig['config']['foreign_table_field'])) {
            $where[$columnConfig['config']['foreign_table_field']] = $table;
            //$where .= ' && doc.row.'.$columnconfig['config']['foreign_table_field'].' == "'.$table.'"';
        }
        if (!empty($columnConfig['config']['foreign_match_fields'])) {
            foreach ($columnConfig['config']['foreign_match_fields'] as $ff => $vv) {
                $where[$ff] = $vv;
            }
        }
        return $this->getRows($columnConfig['config']['foreign_table'], $where);
    }

    public function cloneContent_pre_sys_file_reference($olduid, $oldpid, $row, &$pObj): array
    {
        $row['t3_origuid'] = $olduid;
        if (\is_null($row['t3_origuid'])) {
            $row['t3_origuid'] = 0;
        }

        $row['table_local'] = 'sys_file';
        return $row;
    }

    /**
     * @param array<array-key, mixed> $sysFile
     * @return array<array-key, mixed>
     */
    public function handleFile(array $sysFile, string $newIdentifier): array
    {
        file_put_contents(Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier, base64_decode((string)$sysFile['medium']));
        echo 'chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier;
        echo "\n";
        echo 'chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier;
        echo "\n";

        exec('chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier);
        exec('chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier);
        //$sys_file_metadata = $this->getRow('sys_file_metadata',['file'=>$sys_file['uid']]);
        unset($sysFile['uid']);
        unset($sysFile['medium']);
        $sysFile['identifier'] = $newIdentifier;
        $sysFile['identifier_hash'] = sha1((string)$sysFile['identifer']);
        $sysFile['folder_hash'] = sha1(dirname((string)$sysFile['identifer']));
        $sysFile['storage'] = 1;
        $a = explode('.', (string)$sysFile['name']);
        $ext = array_pop($a);
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                $type = 2;
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $type = 2;
                $mime = 'image/gif';
                break;
            case 'png':
                $type = 2;
                $mime = 'image/png';
                break;
            case 'mp3':
                $type = 3;
                $mime = 'audio/mpeg';
                break;
            default:
                $type = 1;
                $mime = 'application/octet-stream';
                break;
        }

        $sysFile['type'] = $type;
        $sysFile['extension'] = $ext;
        $sysFile['mime_type'] = $mime;

        $sysFile['size'] = filesize(Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier);
        $sysFile['creation_date'] = filemtime(Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier);
        $sysFile['modification_date'] = filemtime(Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier);
        $sysFile['sha1'] = $sysFile['sha1sum'];
        unset($sysFile['sha1sum']);

        $this->ping();
        [$rows, $uid] = self::insertRecord('sys_file', $sysFile);

        if ($type == 2) {
            [$width, $height, $type, $attr] = \getimagesize(Environment::getPublicPath() . '/' . '/fileadmin' . $newIdentifier);

            self::insertRecord('sys_file_metadata', [
                'pid' => 0,
                'file' => $uid,
                'width' => $width,
                'height' => $height,
            ]);
        }

        $sysFile['uid'] = $uid;
        return $sysFile;
    }

    public function ping(): void
    {
        //echo "PRE PING";
        //try {
        //    Globals::db()->isConnected();
        //} catch (\Exception $e) {
        //   print_r($e);
        //  exit;
        //}

        //echo "POST PING";
    }

    /**
     * @inheritDoc
     */
    public function getMM(string $mmTable, int|string $uid, string $tableName): array
    {
        $where = ['uid_local' => $uid];
        return $this->getRows($mmTable, $where);

        $where = 'doc.table == \'' . $mmTable . '\' && doc.row.uid_local == ' . $uid;

        $map = '
    	    function(doc) {
			    if(' . $where . ') {
			       emit(doc._id,doc);
			    }
			}
			';

        $data = $this->filter($map);

        $ret = [];
        foreach ($data['rows'] as $row) {
            $ret[] = $row['values']['row'];
        }
        return $ret;
    }

    /**
     * @throws Exception
     */
    public function pageSort($new): void
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $db->executeQuery('SET @count=16');
        $db->executeQuery('update pages set sorting=@count:=@count+16 where pid=' . $this->creator->getPid() . ' order by doktype desc,title asc');
    }

    public function sourcePid(): int
    {
        return -1;
    }

    public function cloneContent_pre_tt_content($olduid, $oldpid, $row, &$pObj): array
    {
        if (!isset($row['image'])) {
            $row['image'] = 0;
        }
        if (!isset($row['assets'])) {
            $row['assets'] = 0;
        }
        if (!isset($row['media'])) {
            $row['media'] = 0;
        }
        return $row;
    }

    /**
     * @return array<array-key, mixed>
     * @throws \JsonException
     */
    public function getTables(): array
    {
        $data = $this->getbyid('_design/application/_view/gettables/?group=true');
        foreach ($data['rows'] as $row) {
            $this->usedTables[] = $row['key'];
        }
        return \array_intersect(array_keys($GLOBALS['TCA']), $this->usedTables);
    }

    /**
     * @param array<array-key, mixed> $pidList
     * @return array<array-key, mixed>
     */
    public function filterByPid(string $table, array $pidList): array
    {
        return $pidList;
    }

    /**
     * @return array<array-key, mixed>
     * @throws \JsonException
     */
    private function filterorig(mixed $map)
    {
        $this->out(__METHOD__ . ':' . print_r($map, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->couchdb . '_temp_view');
        if (!empty($this->credentials)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->credentials);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/json',
            'Accept: */*',
        ]);
        $payload = json_encode(['map' => $map], JSON_THROW_ON_ERROR);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        curl_close($ch);

        return \json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }
}
