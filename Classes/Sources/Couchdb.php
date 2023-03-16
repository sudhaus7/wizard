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
use SUDHAUS7\Sudhaus7Base\Tools\Globals;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use TYPO3\CMS\Core\Core\Environment;

class Couchdb implements SourceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    private array $views = [];
    private array $tree = [];
    private string $credentials = 'admin:sNvbVr2hWh4u4nQZf3nA4W';

    public function __construct(private readonly Creator $creator)
    {
        $this->couchdb = 'http://tools.sudhaus7.de:32768/' . $creator->getSourcepid() . '/';
        $this->addBaseViews();
        //$this->getUsedTables();
        //exit;
    }

    private array $maps = [

        'default'=>'
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

    public function getRow($table, $where=[], $pidfilter=[])
    {
        if ($table=='pages' && isset($where['uid']) && $where['uid'] == -1) {
            $template = empty($pidfilter) ? $this->maps['default'] : $this->maps['pidin'];
            $map = sprintf($template, $table, ' && doc.uid == doc.startid ', implode(',', $pidfilter));
            $data = $this->filter($map);
            $data['rows'][0]['value']['row']['pid']=$this->creator->getPid();
        } else {
            $data = [];
            if (count($where)==1 && isset($where['uid'])) {
                $tmp = $this->getbyid($table . '_' . $where['uid']);
                if (!isset($tmp['error'])) {
                    $data['rows'][0]['value']=$tmp;
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
        if ($data['rows'][0]['value']['table']=='pages' && $data['rows'][0]['value']['uid'] == $data['rows'][0]['value']['startid']) {
            $data['rows'][0]['value']['row']['pid']=$this->creator->getPid();
        }

        $row = $data['rows'][0]['value']['row'];
        if (empty($row)) {
            print_r([__FILE__, __LINE__, $table, $where, $pidfilter, $map, $data]);
            exit;
        }
        self::cleanRow($table, $row);
        return $row;
        //return Globals::db()->exec_SELECTgetSingleRow('*', $table, $wherestring);
    }

    public function getRows($table, $where=[], $pidfilter=[])
    {
        if ($table=='sys_file') {
            return [];
        }

        if (empty($pidfilter)) {
            $this->addView($table, $where);
            $url = $this->getViewurl($table, $where);
            //$this->out( __METHOD__ . ' ' . $url );
            $temp = $this->get($url);
            $data = ['rows'=>[]];
            foreach ($temp['rows'] as $e) {
                $tmp =  $this->getbyid($e['value']);
                $data['rows'][] = ['value'=>$tmp];
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
            if ($d['value']['table']=='pages' && $d['value']['uid'] == $d['value']['pid']) {
                $d['value']['row']['pid']=$this->creator->getPid();
            }
            $row = $d['value']['row'];
            self::cleanRow($table, $row);
            $rows[]=$row;
        }

        return $rows;
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
            $where = ['pid'=>$start];
            $this->addView('pages', $where);
            $data = $this->getRows('pages', ['pid'=>$start]);

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

    public function ping(): void
    {
        //echo "PRE PING";
        try {
            Globals::db()->isConnected();
        } catch (\Exception $e) {
            print_r($e);
            exit;
        }

        //echo "POST PING";
    }

    public function getIrre($table, $uid, $pid, array $oldrow, array $columnconfig, $pidlist = [])
    {
        $where = [
            $columnconfig['config']['foreign_field']=>$uid,
        ];

        //$where = 'doc.table == \''.$columnconfig['config']['foreign_table'].'\' && doc.row.'.$columnconfig['config']['foreign_field'].' == '.$uid;

        if (isset($columnconfig['config']['foreign_table_field'])) {
            $where[$columnconfig['config']['foreign_table_field']]=$table;
            //$where .= ' && doc.row.'.$columnconfig['config']['foreign_table_field'].' == "'.$table.'"';
        }
        if (isset($columnconfig['config']['foreign_match_fields']) && !empty($columnconfig['config']['foreign_match_fields'])) {
            foreach ($columnconfig['config']['foreign_match_fields'] as $ff => $vv) {
                $where[$ff]=$vv;
                //$where .= ' && doc.row.' . $ff . ' == "' . $vv . '" ';
            }
        }

        /*
        if (isset($columnconfig['config']['foreign_table_where'])) {
            $tmp = $columnconfig['config']['foreign_table_where'];
            $tmp = str_replace('###CURRENT_PID###', $pid, $tmp);
            $tmp = str_replace('###THIS_UID###', $uid, $tmp);
            foreach ($GLOBALS['TCA'][$columnconfig['config']['foreign_table']]['columns'] as $key => $x) {

                $tmp = str_replace('###REC_FIELD_' . $key . '###', $oldrow[$key], $tmp);
            }
            $sql .= ' ' . $tmp;
        }
        */
        //$rows = $this->getRows( $columnconfig['config']['foreign_table'],$where);
        /*$map = '
            function(doc) {
                if('.$where.') {
                   emit(doc._id,doc);
                }
            }
            ';
        */
        //$data = $this->filter($map);
        //print_r([__METHOD__,$columnconfig['config']['foreign_table'],$where,$this->getViewurl( $columnconfig['config']['foreign_table'], $where )]);
        $data =  $this->getRows($columnconfig['config']['foreign_table'], $where);
        return $data;
        /*
        $ret = [];
        if (isset($data['rows'])) foreach ($data['rows'] as $idx=>$d) {
            $row = $d['value']['row'];
            //self::cleanRow( $table, $row);


            $ret[]=$row;
        }




        return $ret;
        */
    }

    public function cloneContent_pre_sys_file_reference($olduid, $oldpid, $row, &$pObj)
    {
        $row['t3_origuid']=$olduid;
        if (\is_null($row['t3_origuid'])) {
            $row['t3_origuid']=0;
        }

        $row['table_local'] = 'sys_file';
        return $row;
    }

    /**
     * @param string $newidentifier
     * @return array
     */
    public function handleFile(array $sys_file, $newidentifier)
    {
        file_put_contents(Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier, base64_decode((string)$sys_file['medium']));
        echo 'chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier;
        echo "\n";
        echo 'chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier;
        echo "\n";

        exec('chown www-data:www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        exec('chmod ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        //$sys_file_metadata = $this->getRow('sys_file_metadata',['file'=>$sys_file['uid']]);
        unset($sys_file['uid']);
        unset($sys_file['medium']);
        $sys_file['identifier'] = $newidentifier;
        $sys_file['identifier_hash'] = sha1((string)$sys_file['identifer']);
        $sys_file['folder_hash'] = sha1(dirname((string)$sys_file['identifer']));
        $sys_file['storage'] = 1;
        $a = explode('.', (string)$sys_file['name']);
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

        $sys_file['type'] = $type;
        $sys_file['extension'] = $ext;
        $sys_file['mime_type'] = $mime;

        $sys_file['size'] = filesize(Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        $sys_file['creation_date'] =  filemtime(Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        $sys_file['modification_date'] =  filemtime(Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);
        $sys_file['sha1'] = $sys_file['sha1sum'];
        unset($sys_file['sha1sum']);

        $this->ping();
        Globals::db()->exec_INSERTquery('sys_file', $sys_file);
        $uid = Globals::db()->sql_insert_id();

        if ($type == 2) {
            [$width, $height, $type, $attr] = \getimagesize(Environment::getPublicPath() . '/' . '/fileadmin' . $newidentifier);

            Globals::db()->exec_INSERTquery('sys_file_metadata', [
                'pid'=>0,
                'file'=>$uid,
                'width'=>$width,
                'height'=>$height,
            ]);
        }

        /* if (!empty($sys_file_metadata)) {
             unset($sys_file_metadata['uid']);
             $sys_file_metadata['file'] = $uid;
             Globals::db()->exec_INSERTquery('sys_file_metadata', $sys_file_metadata);
         }*/
        $sys_file['uid'] = $uid;
        return $sys_file;
    }

    public function getMM($mmtable, $uid, $tablename)
    {
        $where = ['uid_local'=>$uid];
        return $this->getRows($mmtable, $where);

        $where = 'doc.table == \'' . $mmtable . '\' && doc.row.uid_local == ' . $uid;

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
        /*
        $sql = 'select * from ' . $mmtable . ' where uid_local=' . $uid;
        $testres = Globals::db()->sql_query('show columns from ' . $mmtable . '  like \'tablenames\'');
        $test = Globals::db()->sql_fetch_row($testres);
        if (!empty($test)) {
            $sql .= ' and (tablenames="' . $tablename . '" or tablenames="")';
        }
        $ret = [];
        $res = Globals::db()->sql_query($sql);
        while ($row = Globals::db()->sql_fetch_assoc($res)) {
            $ret[]=$row;
        }

        */
    }

    public function pageSort($new): void
    {
        Globals::db()->sql_query('SET @count=16');
        Globals::db()->sql_query('update pages set sorting=@count:=@count+16 where pid=' . $this->creator->getPid() . ' order by doktype desc,title asc');
    }

    private readonly string $couchdb;
    public function sourcePid()
    {
        return -1;
    }
    public function cloneContent_pre_tt_content($olduid, $oldpid, $row, &$pObj)
    {
        if (!isset($row['image']) || \is_null($row['image'])) {
            $row['image']=0;
        }
        if (!isset($row['assets']) || \is_null($row['assets'])) {
            $row['assets']=0;
        }
        if (!isset($row['media']) || \is_null($row['media'])) {
            $row['media']=0;
        }
        return $row;
    }
    public function cloneTreePreInsert($old, $page, $pObj)
    {
        if (!isset($page['media']) || \is_null($page['media'])) {
            $page['media']=0;
        }

        return $page;
    }
    public function getTables(): array
    {
        $data = $this->getbyid('_design/application/_view/gettables/?group=true');
        foreach ($data['rows'] as $row) {
            $this->usedTables[] = $row['key'];
        }
        return \array_intersect(array_keys($GLOBALS['TCA']), $this->usedTables);
    }
    public static function cleanRow($table, &$row): void
    {
        $fields = [];
        $fields = (array)$fields;
        $fields[]='uid';
        $fields[]='pid';
        $fields[]='tstamp';
        $fields[]='crdate';
        $fields[]='cruser_id';
        $fields[]='deleted';
        $fields[]='hidden';
        $fields[]='sorting';
        $fields[]='sortorder';
        $fields[]='perms_userid';
        $fields[]='perms_groupid';
        $fields[]='perms_user';
        $fields[]='perms_group';
        $fields[]='perms_everybody';
        $fields[]='t3_origuid';
        $fields[]='uid_local';
        $fields[]='uid_foreign';
        $fields[]='sha1sum';
        $fields[]='medium';
        $fields[]='tx_rlmptmplselector_main_tmpl';
        $fields[]='tx_rlmptmplselector_ca_tmpl';
        $fields[]='bfelem_flex';
        foreach (array_keys($row) as $k) {
            if (!array_key_exists($k, $GLOBALS['TCA'][$table]['columns'])) {
                unset($row[$k]);
            }
        }
    }
    private function expandwhere($where, array $mywhere)
    {
        if (!empty($where)) {
            foreach ($where as $key=>$value) {
                if (is_array($value)) {
                    $mywhere[] = ' ' . implode(' && ', $this>$this->expandwhere($value, [])) . '  ';
                } else {
                    $mywhere[] = sprintf('doc.row.%s == \'%s\'', $key, $value);
                }
            }
        }
        return $mywhere;
    }

    private function get(string $id)
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
    private function filterorig($map)
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
        $payload = json_encode(['map'=>$map], JSON_THROW_ON_ERROR);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        curl_close($ch);

        return \json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    private function filter($map)
    {
        $views = $this->get('_design/tmpviews');
        if ($views['error']) {
            $this->put('_design/tmpviews', [
                'language'=>'javascript',
                'views'=>[
                    'dummy'=>['map'=>'function(doc) { emit(doc._id,doc); }'],
                ],
            ]);
            $views = $this->get('_design/tmpviews');
        }

        $id = uniqid('tmp', true);
        $path = '_design/tmpviews/_view/' . $id;
        $views['views'][$id] = [
            'map'=>$map,
        ];
        $this->out(__METHOD__ . ':' . print_r([$path, $views], true));
        $this->put('_design/tmpviews', $views);
        return $this->get($path . '/');
    }

    private function getbyid($id)
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

    private function addView($table, $where): void
    {
        $views = [];
        $keys = array_keys($where);
        \natsort($keys);
        $viewname = $table . '_by_' . implode('_', $keys);
        if (!array_key_exists($viewname, $views['views'])) {
            $views = $this->getViews();
            if (! isset($views['views'][ $viewname ])) {
                foreach ($keys as $k => $v) {
                    $keys[ $k ] = match ($v) {
                        'pid', 'uid' => 'doc.' . $v,
                        default => 'doc.row.' . $v,
                    };
                }
                $views['views'][ $viewname ] = [
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

    private function addBaseViews(): void
    {
        $views = $this->getViews();
        $update = false;
        if (! isset($views['views'][ 'gettables' ])) {
            $views['views'][  'gettables' ] = [
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
    private function getViewname($table, $where): string
    {
        $keys = array_keys($where);
        \natsort($keys);
        return $table . '_by_' . implode('_', $keys);
    }

    private function getViewquery($where): string
    {
        $keys = array_keys($where);
        \natsort($keys);
        $q = [];
        foreach ($keys as $k) {
            $q[]=$where[$k];
        }
        return \urlencode(json_encode([implode('|', $q)], JSON_THROW_ON_ERROR));
    }
    private function getViews()
    {
        $views = $this->get('_design/application');
        if ($views['error']) {
            $this->put('_design/application', [
                'language'=>'javascript',
                'views'=>[
                    'dummy'=>['map'=>'function(doc) { emit(doc._id,doc); }'],
                ],
            ]);
            $views = $this->get('_design/application');
        }
        return $views;
    }

    private function getViewurl($table, $where): string
    {
        return '_design/application/_view/' . $this->getViewname($table, $where) . '?keys=' . $this->getViewquery($where);
    }

    private array $usedTables = [];
    private function out(string $out): void
    {
        echo $out,"\n";
    }
}
