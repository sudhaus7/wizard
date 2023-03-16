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

namespace SUDHAUS7\Sudhaus7Wizard;

use SUDHAUS7\Sudhaus7Base\Tools\DB;
use SUDHAUS7\Sudhaus7Base\Tools\Globals;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Sources\Localdatabase;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Create
{
    public array $allwaysIgnoreTables = [];

    public array $siteconfig = [
        'base'          => 'domainname',
        'baseVariants'  => [],
        'errorHandling' =>
            [
                0 =>
                    [
                        'errorCode'          => 404,
                        'errorHandler'       => 'Page',
                        'errorContentSource' => 't3://page?uid=2347',
                    ],
            ],
        'languages'     =>
            [
                0 =>
                    [
                        'title'           => 'Deutsch',
                        'enabled'         => true,
                        'base'            => '/',
                        'typo3Language'   => 'de',
                        'locale'          => 'de_DE.utf8',
                        'iso-639-1'       => 'de',
                        'navigationTitle' => 'Deutsch',
                        'hreflang'        => 'de-DE',
                        'direction'       => 'ltr',
                        'flag'            => 'de',
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
            0 => ['resource'=>'EXT:solr_evm/Configuration/Routes/Solr.yaml'],
        ],
    ];

    public array $pageMap = [];
    public ?Creator $task = null;
    /**
     * @var SourceInterface
     */
    public $source;
    public array $group = [];
    public array $user = [];
    public array $filemount = [];
    public array $contentmap = [];
    public array $cleanUpTodo = [];
    public string $debugsection = 'Init';
    protected $pObj;
    protected WizardInterface $template;
    protected ?string $templatekey = null;
    protected int $tmplgroup = 0;
    protected int $tmpluser = 0;

    protected int $siterootid = 0;
    private array $checkusers = [];
    private ?OutputInterface $output = null;

    public $errorpage = 0;

    public function run($mapfolder = null): bool
    {
        //Globals::db()->store_lastBuiltQuery = true;
        $this->confArr = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_wizard');

        $this->out('Start', 'INFO', 'Start');

        $this->createFilemount();
        $this->createGroup();
        $this->createUser();
        $this->out(__FUNCTION__ . ':' . __LINE__);
        $sourcePid = $this->source->sourcePid();
        $this->out(__FUNCTION__ . ':' . __LINE__);

        $sourcePage = $this->source->getRow('pages', [ 'uid' => $sourcePid ]);
        $this->out(__FUNCTION__ . ':' . __LINE__);
        $this->out('Quelle: ' . $sourcePage['title']);
        if ($sourcePid > 0) {
            $this->pageMap[ $sourcePid ] = 0;
        }

        $this->out('Building Tree', 'INFO', 'Build TREE');
        $this->buildTree($sourcePid);

        $this->source->ping();
        $this->out('Clone Tree', 'INFO', 'Clone TREE');
        $this->cloneTree();
        $this->source->ping();
        $this->out('Clone Content', 'INFO', 'Clone Content');
        $this->cloneContent();
        $this->source->ping();
        //$this->cleanContent('clean');
        while ($this->cleanUpTodo !== []) {
            $this->out('Clone Inlines', 'INFO', 'Clone Inlines');
            $this->cloneInlines();
            $this->source->ping();
        }
        $this->out('Clean Pages', 'INFO', 'Clean Pages');
        $this->cleanPages();
        $this->source->ping();
        $this->out('Clean Content', 'INFO', 'Clean Content');
        $this->cleanContent();
        $this->source->ping();
        $this->out('About to finish', 'INFO', 'Finish');
        $this->pageSort();
        $this->source->ping();
        $this->finalGroup();
        $this->source->ping();
        $this->finalUser();
        $this->finalYaml();
        $this->source->ping();
        $this->template->finalize($this);
        $this->source->ping();
        $this->task->setPid($this->pageMap[ $sourcePid ]);
        if (! \is_null($mapfolder)) {
            if ($fp = fopen($mapfolder . '/page.csv', 'w')) {
                foreach ($this->pageMap as $k => $v) {
                    fwrite($fp, sprintf("%s;%s\n", $k, $v));
                }
                fclose($fp);
            }
            foreach ($this->contentmap as $table => $map) {
                if ($fp = fopen($mapfolder . '/' . $table . '.csv', 'w')) {
                    foreach ($map as $k => $v) {
                        fwrite($fp, sprintf("%s;%s\n", $k, $v));
                    }
                    fclose($fp);
                }
            }
        }

        return true;
    }

    public function out($c, $info = 'DEBUG', string $section = null): void
    {
        if (! \is_null($section)) {
            $this->debugsection = $section;
        }

        if ($this->output !== null) {
            $verb = match ($info) {
                'DEBUG2' => $this->output::VERBOSITY_VERY_VERBOSE,
                'DEBUG' => $this->output::VERBOSITY_VERBOSE,
                default => $this->output::VERBOSITY_NORMAL,
            };

            $this->output->writeln(
                date('Y-m-d H:i:s') . ' - ' . $info . ' - ' . $this->debugsection . ' - ' . $c,
                $verb
            );
        }
    }

    private array $confArr = [];

    public function addContentMap($table, $old, $new): void
    {
        if (! isset($this->contentmap[ $table ])) {
            $this->contentmap[ $table ] = [];
        }

        $this->contentmap[ $table ][ $old ] = $new;
    }

    public function addCleanupInline($table, $uid): void
    {
        if (! isset($this->cleanUpTodo[ $table ])) {
            $this->cleanUpTodo[ $table ] = [];
        }
        $this->cleanUpTodo[ $table ][] = $uid;
    }

    public function pageSort(): void
    {
        $old = $this->source->sourcePid();
        $new = $this->pageMap[ $old ];
        $this->source->pageSort($new);
    }

    public function translateIDlist($table, $list)
    {
        $ids = GeneralUtility::trimExplode(',', $list);
        if ($ids === []) {
            return $list;
        }
        $newlist = [];
        foreach ($ids as $id) {
            $newlist[] = $this->getTranslateUid($table, $id);
        }

        return implode(',', $newlist);
    }

    /**
     * @param $table
     * @param $uid
     *
     * @return int
     */
    public function getTranslateUid($table, $uid)
    {
        $tableprefix = false;
        if (str_starts_with((string)$uid, (string)$table)) {
            $tableprefix = true;
            $x           = explode('_', (string)$uid);
            $uid         = array_pop($x);
        }
        if ($table == 'pages') {
            if (isset($this->pageMap[ (int)$uid ])) {
                return (int)$this->pageMap[ (int)$uid ] > 0 ? (int)$this->pageMap[ (int)$uid ] : (int)$uid;
            }
        } elseif (isset($this->contentmap[ $table ]) && isset($this->contentmap[ $table ][ (int)$uid ])) {
            return (int)$this->contentmap[ $table ][ (int)$uid ] > 0 ? (int)$this->contentmap[ $table ][ (int)$uid ] : (int)$uid;
        }

        //return (int)$uid;
        return $tableprefix ? $table . '_' . $uid : $uid;
    }

    public function finalContent_tt_content($row)
    {
        $func = 'finalContent_tt_content_' . $row['CType'];
        $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
        if ($row['CType'] == 'list') {
            $func .= '_' . $row['list_type'];
        }

        $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
        if (method_exists($this->template, $func)) {
            $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
            $row = $this->template->$func($row, $this);
            $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
        }

        if (method_exists($this, $func)) {
            $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
            $row = $this->$func($row, $this);
            $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
        }

        $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);

        return $row;
    }

    public function finalContent_tt_content_form_formframework($row)
    {
        if (!empty($row['pi_flexform'])) {
            $flex = GeneralUtility::xml2array($row['pi_flexform']);

            /** @var Random $rnd */
            $rnd = GeneralUtility::makeInstance(Random::class);
            foreach ($flex['data'] as $key=>$config) {
                if ($key !== 'sDEF') {
                    foreach ($config as $subconfig) {
                        if (isset($subconfig['settings.finishers.Redirect.pageUid'])) {
                            $flex['data'][$key]['lDEF']['settings.finishers.Redirect.pageUid']['vDEF'] = $this->pageMap[(int)$flex['data'][$key]['lDEF']['settings.finishers.Redirect.pageUid']['vDEF']];
                        }
                        if (isset($subconfig['settings.finishers.EmailToReceiver.recipients'])) {
                            $flex['data'][$key]['lDEF']['settings.finishers.EmailToReceiver.recipients']['el'] = [
                                $rnd->generateRandomBytes(22)=>[
                                    '_arrayContainer'=>[
                                        'el'=>[
                                            'email'=>[
                                                'vDEF'=>$this->task->getContact(),
                                            ],
                                            'name'=>[
                                                'vDEF'=>$this->task->getLongname(),
                                            ],
                                        ],
                                        '_TOGGLE'=>0,
                                    ],
                                ],
                            ];
                            $flex['data'][$key]['lDEF']['settings.finishers.EmailToReceiver.senderName']['vDEF'] = 'Baukasten ' . $this->task->getLongname();
                            $flex['data'][$key]['lDEF']['settings.finishers.EmailToReceiver.title']['vDEF'] = 'Aus Ihrem Baukasten ' . $this->task->getLongname();
                        }
                    }
                }
            }

            $flex['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF']='1:/mediapool/Formulare/Allgemeines-Formular.form.yaml';
            $flex['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF']=1;
            //$flex['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEV']=1;

            $row['pi_flexform'] = self::array2xml($flex);
        }
        return $row;
    }

    public function translateLinkString($s): string
    {
        $s   = trim((string)$s);
        $a   = str_getcsv($s, ' ', 'dasdhasdsalkdjsalk13');
        $id  = $a[0];
        $aID = explode(':', $id);
        if (count($aID) > 1) {
            switch ($aID[0]) {
                case 'file':
                    $a[1] = 'file:' . $this->getTranslateUid('sys_file', $aID[1]);
                    break;
                case 'http':
                case 'https':
                    return implode(' ', $a);
                    break;
            }
        } elseif (in_array('mail', $a) && $a[1] == '-' && $a[2] == 'mail') {
            return implode(' ', $a);
        } elseif (str_starts_with($s, 'http') || str_starts_with($s, 'fileadmin') || str_starts_with($s, '/fileadmin')) {
            return implode(' ', $a);
        } else {
            $aID = explode('#', $id);
            if (count($aID) > 1) {
                $a[0] = $this->getTranslateUid('pages', $aID[0]) . '#' . $this->getTranslateUid(
                    'tt_content',
                    $aID[1]
                );
            } else {
                $a[0] = $this->getTranslateUid('pages', $id);
            }
        }

        return implode(' ', $a);
    }

    public function getTranslateUidReverse($table, $uid)
    {
        if ($table == 'pages') {
            if (in_array((int)$uid, $this->pageMap)) {
                return array_search((int)$uid, $this->pageMap);
            }
        } elseif (isset($this->contentmap[ $table ]) && in_array((int)$uid, $this->contentmap[ $table ])) {
            return array_search((int)$uid, $this->contentmap[ $table ]);
        }

        return (int)$uid;
    }

    /**
     * @param $row
     * @param Create $pObj
     */
    public function finalContent_pages($row, &$pObj)
    {
        if ($row['slug'] === '/meta/fehlermeldung') {
            $pObj->errorpage = $row['uid'];
        }
        if ($row['doktype'] == 4 && $row['urltype'] == 1 && ! empty($row['shortcut'])) {
            $row['shortcut'] = $pObj->getTranslateUid('pages', $row['shortcut']);
        }

        return $row;
    }

    public static function taskFactory(Creator $o, &$pObj, OutputInterface $output): \SUDHAUS7\Sudhaus7Wizard\Create
    {
        $tsk              = new Create();
        $tsk->task        = $o;
        $tsk->pObj        = $pObj;
        $tsk->output      = $output;
        $tsk->templatekey = $o->getBase();
        $cls              = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredExtentions'][ $tsk->templatekey ];
        $tsk->template    = new $cls();

        $sourceclassname = $o->getSourceclass();
        if (\class_exists($sourceclassname)) {
            $sourceclass = new $sourceclassname($o);
            $tsk->source = $sourceclass instanceof SourceInterface ? $sourceclass : new Localdatabase($o);
        }

        return $tsk;
    }

    private function createFilemount()
    {
        $shortname = $this->task->getShortname();
        $shortname = str_replace(
            [
                'ß',
                'ä',
                'ü',
                'ö',
                'Ä',
                'Ü',
                'Ö',
                ' ',
                '-',
            ],
            [
                'ss',
                'ae',
                'ue',
                'oe',
                'ae',
                'ue',
                'oe',
                '_',
                '_',
            ],
            $shortname
        );

        $dir = $this->template->getMediaBaseDir() . $shortname . '/';

        $name = 'Medien ' . $this->task->getProjektname();

        $this->out('Create Filemount 1 ' . $name);
        $this->source->ping();

        $test = DB::getRecord('sys_filemounts', $name, 'title');
        if (! empty($test)) {
            $this->filemount = $test;
            return;
        }
        $this->out('Create Filemount ' . 'mkdir -p ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        exec('mkdir -p ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        $this->out('Create Filemount ' . 'mkdir -p ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir . '/Formulare');
        exec('mkdir -p ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir . 'Formulare');

        $this->out('Adding Formulare Folder to config');
        $this->addToFormConfig($dir . '/Formulare');

        $this->out('Create Filemount ' . 'chown -R www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        exec('chown -R www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        $this->out('Create Filemount ' . 'chgrp -R www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        exec('chgrp -R www-data ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        $this->out('Create Filemount ' . 'chmod -R ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        exec('chmod -R ug+rw ' . Environment::getPublicPath() . '/' . '/fileadmin' . $dir);
        $tmpl = [
            'title' => $name,
            'path'  => $dir,
            'base'  => 1,
            'pid'   => 0,
        ];

        if (method_exists($this->template, 'createFilemount')) {
            $tmpl = $this->template->createFilemount($tmpl, $this);
        }
        $this->source->ping();

        [ $rows, $newuid ] = DB::insertRecord('sys_filemounts', $tmpl);
        if (! $rows) {
            throw new \Exception('Failed to insert', 1_616_680_146);
        }
        $tmpl['uid'] = $newuid;

        $this->filemount = $tmpl;
    }

    private function createGroup()
    {
        $tmpl            = $this->template->getTemplateGroup();
        $this->tmplgroup = $tmpl['uid'];
        $groupname       = $this->confArr['groupprefix'] . ' ' . $this->task->getProjektname();
        $this->out('Create Group ' . $groupname);
        $this->source->ping();

        $test = DB::getRecord('be_groups', $groupname, 'title');

        if (! empty($test)) {
            $this->group = $test;

            return;
        }

        unset($tmpl['uid']);
        $tmpl['title']            = $groupname;
        $tmp                      = GeneralUtility::trimExplode(',', $tmpl['file_mountpoints']);
        $tmp[]                    = $this->filemount['uid'];
        $tmpl['file_mountpoints'] = implode(',', $tmp);
        $tmpl['crdate']           = time();
        $tmpl['tstamp']           = time();
        if (method_exists($this->template, 'createGroup')) {
            $tmpl = $this->template->createGroup($tmpl, $this);
        }
        $this->source->ping();

        [ $rows, $newuid ] = DB::insertRecord('be_groups', $tmpl);

        if (!$rows) {
            throw new \Exception('cant create group', 1_616_680_548);
        }
        $tmpl['uid'] = $newuid;
        $this->group = $tmpl;
    }

    private function createUser()
    {
        $this->out('Create User ' . $this->task->getReduser());
        $tmpl           = $this->template->getTemplateUser();
        $this->tmpluser = $tmpl['uid'];
        $this->source->ping();

        $test = DB::getRecord('be_users', $this->task->getReduser(), 'username');

        if (! empty($test)) {
            $groups = GeneralUtility::trimExplode(',', $test['usergroup'], true);
            array_unshift($groups, $this->group['uid']);
            foreach ($groups as $k => $gid) {
                if ($gid == $this->tmplgroup) {
                    unset($groups[ $k ]);
                }
            }
            $test['usergroup']        = implode(',', $groups);
            $mountpoints              = GeneralUtility::trimExplode(',', $test['file_mountpoints'], true);
            $mountpoints[]            = $this->filemount['uid'];
            $test['file_mountpoints'] = implode(',', $mountpoints);
            $test['tstamp']           = time();
            $this->source->ping();

            DB::updateRecord('be_users', [
                'file_mountpoints' => $test['file_mountpoints'],
                'usergroup'        => $test['usergroup'],
                'tstamp'           => time(),
            ], [ 'uid' => $test['uid'] ]);
            $this->user = $test;

            return;
        }

        unset($tmpl['uid']);
        $tmpl['username']         = $this->task->getReduser();
        $tmpl['realName']         = $this->task->getProjektname();
        $tmpl['email']            = $this->task->getRedemail();
        $tmpl['file_mountpoints'] = $this->filemount['uid'];
        $tmpl['admin']            = 0;
        $tmpl['lastlogin']        = 0;
        $tmpl['crdate']           = time();
        $tmpl['tstamp']           = time();
        $tmpl['description']      = 'Angelegt durch Wizard';
        $tmpl['TSconfig']         = '';
        $uc                       = [];
        $tmpl['uc']               = serialize($uc);

        $salting          = ( new PasswordHashFactory() )->getDefaultHashInstance('BE');
        $tmpl['password'] = $salting->getHashedPassword($this->task->getRedpass());

        $tmpl['deleted'] = 0;
        $tmpl['disable'] = 0;
        $groups          = GeneralUtility::trimExplode(',', $tmpl['usergroup'], true);
        array_unshift($groups, $this->group['uid']);
        foreach ($groups as $k => $gid) {
            if ($gid == $this->tmplgroup) {
                unset($groups[ $k ]);
            }
        }
        $tmpl['usergroup'] = implode(',', $groups);
        if (method_exists($this->template, 'createUser')) {
            $tmpl = $this->template->createUser($tmpl, $this);
        }
        $this->source->ping();

        [ $rows, $newuid ] = DB::insertRecord('be_users', $tmpl);

        if (! $rows) {
            throw new \Exception('could not create user', 1_616_683_642);
        }
        $tmpl['uid'] = $newuid;
        $this->user  = $tmpl;
    }

    private function buildTree($start): void
    {
        $tree = $this->source->getTree($start);
        foreach ($tree as $uid) {
            if (! isset($this->pageMap[ $uid ])) {
                $this->pageMap[ $uid ] = 0;
            }
        }
    }

    private function updateMountpoint($newrootpage): void
    {
        $this->filemount['relatepage'] = $newrootpage;

        DB::updateRecord('sys_filemounts', [ 'relatepage' => $newrootpage ], [ 'uid' => $this->filemount['uid'] ]);
    }

    private function cloneTree()
    {
        $this->out('Clone Tree Start');
        $sourcePid = (int)$this->source->sourcePid();
        foreach (array_keys($this->pageMap) as $old) {
            $page = $this->source->getRow('pages', [ 'uid' => $old ]);

            $this->out('Cloning Page ' . $page['title']);
            unset($page['uid']);
            $page['t3_origuid'] = $old;

            if (! $this->isAdmin($page['perms_userid'])) {
                $page['perms_userid']  = $this->user['uid'];
                $page['perms_groupid'] = $this->group['uid'];
            }

            if ($old == $sourcePid) {
                $page['title'] = $this->task->getProjektname();
            }

            if ($page['is_siteroot']) {
                $page['title'] = $this->task->getLongname();
            }

            if (isset($this->pageMap[ $page['pid'] ]) && $this->pageMap[ $page['pid'] ] > 0) {
                $page['pid'] = (int)$this->pageMap[ $page['pid'] ];
            }
            if (method_exists($this->source, 'cloneTreePreInsert')) {
                $page = $this->source->cloneTreePreInsert($old, $page, $this);
            }
            if (method_exists($this->template, 'cloneTreePreInsert')) {
                $page = $this->template->cloneTreePreInsert($old, $page, $this);
            }

            $this->source->ping();

            if ((int)$page['pid'] > 0) {
                [ $rowsaffected, $newpageid ] = DB::insertRecord('pages', $page);

                if (! $rowsaffected) {
                    throw new \Exception('Create page failed', 1_616_685_103);
                }
                $this->pageMap[ $old ] = $newpageid;
                $this->addContentMap('pages', $old, $this->pageMap[ $old ]);
            }
            if ($page['is_siteroot']) {
                $this->createDomain($this->pageMap[ $old ]);
                $this->updateMountpoint($this->pageMap[ $old ]);
                $this->siterootid = $this->pageMap[ $old ];
            }
            if (method_exists($this->template, 'cloneTreePostInsert')) {
                $this->template->cloneTreePostInsert($old, $this->pageMap[ $old ], $page, $this);
            }
        }
        $this->out('Clone Tree End');
    }

    private function isAdmin($uid)
    {
        if (! isset($this->checkusers[ $uid ])) {
            $this->source->ping();
            $this->checkusers[ $uid ] = DB::getRecord('be_users', $uid);
        }
        if (is_array($this->checkusers[ $uid ])) {
            return $this->checkusers[ $uid ]['admin'];
        }

        return false;
    }

    private function createDomain($pid): void
    {
        $this->siteconfig['rootPageId'] = $pid;
        $this->siteconfig['base']       = 'https://' . $this->task->getDomainname() . '/';

        if (method_exists($this->template, 'createDomain')) {
            $this->template->createDomain($pid, $this);
        }
    }

    private function cloneContent()
    {
        $runTables = $this->source->getTables();
        $this->out('Start Clone Content');
        $aSkip = [
            'pages',
            'sys_domain',
            'sys_file_reference',
            'be_users',
            'be_groups',
            'tx_sudhaus7wizard_domain_model_creator',
            'sys_file',
            'sys_action',
        ];
        $aSkip = array_merge($aSkip, $this->allwaysIgnoreTables);
        foreach ($runTables as $table) {
            $config = $GLOBALS['TCA'][ $table ];
            if (! in_array($table, $aSkip)) {
                foreach ($this->pageMap as $oldpid => $newpid) {
                    $where        = self::myEnableFields($table);
                    $where['pid'] = $oldpid;
                    $rows         = $this->source->getRows($table, $where);
                    foreach ($rows as $row) {
                        $this->out('Content Clone ' . $table . ' ' . $row['uid']);

                        $olduid = $row['uid'];
                        unset($row['uid']);
                        $row['pid'] = $newpid;
                        if (isset($row['t3_origuid'])) {
                            $row['t3_origuid'] = $olduid;
                        }

                        $func = 'cloneContent_pre_' . $table;

                        if (method_exists($this->source, $func)) {
                            $row = $this->source->$func($olduid, $oldpid, $row, $this);
                        }

                        if (method_exists($this->template, $func)) {
                            $row = $this->template->$func($olduid, $oldpid, $row, $this);
                        }

                        if (method_exists($this, $func)) {
                            $row = $this->$func($olduid, $oldpid, $row, $this);
                        }

                        if ($table == 'sys_file_reference') {
                            //print_r(__METHOD__.':'.__LINE__.print_r($row, true));
                        }

                        $row = $this->runTCA('pre', $config['columns'], $row, [
                            'table'  => $table,
                            'olduid' => $olduid,
                            'oldpid' => $oldpid,
                            'newpid' => $newpid,
                            'pObj'   => $this,
                        ]);

                        if ($table == 'sys_file_reference') {
                            //print_r(__METHOD__.':'.__LINE__.print_r($row, true));
                        }

                        $this->source->ping();
                        if ($row) {
                            [ $rowsaffected, $newuid ] = DB::insertRecord($table, $row);

                            if (! $rowsaffected) {
                                throw new \Exception(sprintf(
                                    'cannot insert into %s payload %s',
                                    $table,
                                    json_encode($row, JSON_THROW_ON_ERROR)
                                ), 1_616_695_930);
                            }

                            $this->out('Insert ' . $table . ' olduid ' . $olduid . ' oldpid ' . $oldpid . ' newuid ' . $newuid . ' newpid ' . $newpid);
                            $this->addContentMap($table, $olduid, $newuid);
                            $this->addCleanupInline($table, $newuid);
                            $row = $this->runTCA('post', $config['columns'], $row, [
                                'table'  => $table,
                                'olduid' => $olduid,
                                'newuid' => $newuid,
                                'oldpid' => $oldpid,
                                'newpid' => $newpid,
                                'pObj'   => $this,
                            ]);

                            $func = 'cloneContent_post_' . $table;
                            if (method_exists($this, $func)) {
                                $this->out('calling func ' . $func . ' on this');
                                $row = $this->$func($olduid, $oldpid, $newuid, $row, $this);
                            }
                            if (method_exists($this->template, $func)) {
                                $this->out('calling func ' . $func . ' on template');
                                $this->template->$func($olduid, $oldpid, $newuid, $row, $this);
                            }

                            if (method_exists($this->source, $func)) {
                                $this->debug(__METHOD__ . ':' . __LINE__);
                                $this->source->$func($olduid, $oldpid, $newuid, $row, $this);
                                $this->debug(__METHOD__ . ':' . __LINE__);
                            }
                        } else {
                            $this->out('ERROR NO ROW ' . print_r([
                                    $table,
                                    [
                                        'table'  => $table,
                                        'olduid' => $olduid,
                                        'oldpid' => $oldpid,
                                        'newpid' => $newpid,
                                    ],
                                ], true));
                            exit;
                        }
                    }
                }
            }
        }
    }

    private function runTCA(string $state, $config, $row, array $parameters)
    {
        foreach ($config as $column => $columnconfig) {
            //$this->out('runTCA '.$state.' '.$parameters['table'].' '.$column);

            $func = 'cloneContent_' . $state . '_column_' . $column;
            if (method_exists($this, $func)) {
                if ($state == 'final') {
                    //$this->out(__FUNCTION__ . ':' . __LINE__ . ' ' . get_class($this) . '->' . $func);
                }
                try {
                    $row = $this->$func($column, $columnconfig, $row, $parameters);
                } catch (\Exception $e) {
                    print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                    exit;
                }
            }

            if (method_exists($this->template, $func)) {
                if ($state == 'final') {
                    //$this->out(__FUNCTION__ . ':' . __LINE__ . ' CALL ' . get_class($this->template) . '->' . $func);
                }
                try {
                    $row = $this->template->$func($column, $columnconfig, $row, $parameters, $this);
                } catch (\Exception $e) {
                    print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                    exit;
                }
                if ($state == 'final') {
                    $this->out(__FUNCTION__ . ':' . __LINE__ . ' DONE ' . $this->template::class . '->' . $func);
                }
            }

            $func = 'cloneContent_' . $state . '_columntype_' . strtolower((string)$columnconfig['config']['type']);
            if (method_exists($this, $func)) {
                if ($state == 'final') {
                    //$this->out(__FUNCTION__ . ':' . __LINE__ . ' ' . get_class($this) . '->' . $func);
                }
                try {
                    $row = $this->$func($column, $columnconfig, $row, $parameters);
                } catch (\Exception $e) {
                    print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                    exit;
                }
            }

            if (method_exists($this->template, $func)) {
                if ($state == 'final') {
                    //$this->out(__FUNCTION__ . ':' . __LINE__ . ' ' . get_class($this->template) . '->' . $func);
                }
                try {
                    $row = $this->template->$func($column, $columnconfig, $row, $parameters, $this);
                } catch (\Exception $e) {
                    print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                    exit;
                }
            }

            if (isset($columnconfig['config']['wizards'])) {
                foreach ($columnconfig['config']['wizards'] as $wizard => $wizardconfig) {
                    $func = 'cloneContent_' . $state . '_wizards_' . strtolower((string)$wizard);

                    if (method_exists($this, $func)) {
                        if ($state == 'final') {
                            //$this->out(__FUNCTION__ . ':' . __LINE__ . ' ' . get_class($this) . '->' . $func);
                        }
                        try {
                            $row = $this->$func($column, $columnconfig, $row, $parameters, $wizardconfig);
                        } catch (\Exception $e) {
                            print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                            exit;
                        }
                    }
                    if (method_exists($this->template, $func)) {
                        try {
                            $row = $this->template->$func(
                                $column,
                                $columnconfig,
                                $row,
                                $parameters,
                                $this,
                                $wizardconfig
                            );
                        } catch (\Exception $e) {
                            print_r([ $e->getMessage(), $e->getTraceAsString() ]);
                            exit;
                        }
                    }
                }
            }
        }

        return $row;
    }

    private function cloneInlines(): void
    {
        $this->out('Start Inlines Clone , TODO ' . count($this->cleanUpTodo));
        $aSkip = [
            'sys_domain',
            'sys_file_reference',
            'be_users',
            'be_groups',
            'tx_sudhaus7wizard_domain_model_creator',
        ];
        $aSkip = array_merge($aSkip, $this->allwaysIgnoreTables);
        $map   = $this->cleanUpTodo;
        print_r([ 'cleanupTodo' => $this->cleanUpTodo ]);
        $this->cleanUpTodo = [];

        foreach ($map as $table => $newuid) {
            $config = $GLOBALS['TCA'][ $table ];

            if (! in_array($table, $aSkip)) {
                $this->source->ping();

                $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

                $stmt = $query->select('*')
                              ->from($table)
                              ->where(
                                  $query->expr()->in('uid', $newuid)
                              )
                              ->execute();

                while ($origrow = $stmt->fetchAssociative()) {
                    $row = $origrow;
                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $this->out('Content Cleanup ' . $table . ' ' . $row['uid']);
                    $this->debug(__METHOD__ . ':' . __LINE__);

                    $func = 'cleanContent_' . $table;
                    if (method_exists($this->template, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $row = $this->template->$func($row, $this);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }

                    if (method_exists($this, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $row = $this->$func($row, $this);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $row = $this->runTCA('clean', $config['columns'], $row, [
                        'table' => $table,
                        'pObj'  => $this,
                    ]);

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $update = [];
                    foreach ($row as $k => $v) {
                        if ($origrow[ $k ] != $v) {
                            $update[ $k ] = $v;
                        }
                    }
                    unset($update['uid']);
                    unset($update['pid']);
                    $this->debug(__METHOD__ . ':' . __LINE__);
                    if ($update !== []) {
                        $this->source->ping();
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        DB::updateRecord($table, $update, [ 'uid' => $origrow['uid'] ]);
                    }
                }
            }
        }
    }

    private function cleanPages(): void
    {
        $this->out('Start Pages Cleanup ');
        //$aSkip = ['pages','sys_domain', 'sys_file_reference', 'be_users', 'be_groups'];
        //foreach ($GLOBALS['TCA'] as $table => $config) {
        $table  = 'pages';
        $config = $GLOBALS['TCA']['pages'];

        foreach ($this->pageMap as $oldpid => $newpid) {
            $this->source->ping();

            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
            $res   = $query->select(
                [ '*' ],
                $table,
                [ 'uid' => $newpid ]
            );

            while ($origrow = $res->fetchAssociative()) {
                $row = $origrow;
                $this->out('Page Cleanup ' . $row['title'] . ' ' . $row['uid']);

                $func = 'finalContent_' . $table;
                if (method_exists($this->template, $func)) {
                    $row = $this->template->$func($row, $this);
                }

                if (method_exists($this, $func)) {
                    $row = $this->$func($row, $this);
                }

                $row    = $this->runTCA('final', $config['columns'], $row, [
                    'table' => $table,
                    'pObj'  => $this,
                ]);
                $update = [];
                foreach ($row as $k => $v) {
                    if ($origrow[ $k ] != $v) {
                        $update[ $k ] = $v;
                    }
                }
                unset($update['uid']);
                unset($update['pid']);
                if ($update !== []) {
                    $this->source->ping();
                    $this->out(__FILE__ . ':' . __LINE__ . ' ' . $table . ' update ' . print_r($update, true));

                    DB::updateRecord($table, $update, [ 'uid' => $origrow['uid'] ]);
                }
            }
        }
    }

    private function cleanContent(): void
    {
        $this->out('Start Content Cleanup ');
        $aSkip   = [
            'pages',
            'sys_domain',
            'sys_action',
            'sys_file',
            'sys_file_metadata',
            'be_users',
            'be_groups',
            'sys_news',
            'sys_log',
            'sys_registry',
            'sys_history',
            'sys_redirect',
            'sys_filemounts',
            'tx_sudhaus7wizard_domain_model_creator',
        ];
        $aSkip   = array_merge($aSkip, $this->allwaysIgnoreTables);
        $newpids = \array_values($this->pageMap);
        foreach ($GLOBALS['TCA'] as $table => $config) {
            if (! in_array($table, $aSkip)) {
                //foreach ($this->pageMap as $oldpid => $newpid) {
                $this->source->ping();
                $this->out('Content Cleanup ' . $table);

                $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

                $stmt = $query->select('*')
                              ->from($table)
                              ->where(
                                  $query->expr()->in('pid', $newpids)
                              )
                              ->execute();

                while ($origrow = $stmt->fetchAssociative()) {
                    $row = $origrow;
                    $this->out('Content Cleanup ' . $table . ' ' . $row['uid']);

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $func = 'finalContent_' . $table;
                    if (method_exists($this->template, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
                        $row = $this->template->$func($row, $this);

                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }

                    if (method_exists($this, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__ . ':' . $func);
                        $row = $this->$func($row, $this);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $row = $this->runTCA('final', $config['columns'], $row, [
                        'table' => $table,
                        'pObj'  => $this,
                    ]);

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $update = [];
                    foreach ($row as $k => $v) {
                        if ($origrow[ $k ] != $v) {
                            $update[ $k ] = $v;
                        }
                    }
                    unset($update['uid']);
                    unset($update['pid']);
                    if ($update !== []) {
                        $this->source->ping();

                        $this->debug(__METHOD__ . ':' . __LINE__);
                        DB::updateRecord($table, $update, [ 'uid' => $origrow['uid'] ]);
                    }
                }
            }
        }
    }

    private function finalGroup(): void
    {
        $list = $this->translateIDlist('pages', $this->group['db_mountpoints']);
        if ($list != 0) {
            $this->group['db_mountpoints'] = $list;
            $this->source->ping();
            DB::updateRecord('be_groups', [ 'db_mountpoints' => $list ], [ 'uid' => $this->group['uid'] ]);
        }
    }

    private function finalUser(): void
    {
        $list = $this->translateIDlist('pages', $this->user['db_mountpoints']);
        if ($list == $this->user['db_mountpoints']) {
            $list = $this->siterootid;
        } else {
            $aList   = GeneralUtility::trimExplode(',', $list, true);
            $aList[] = $this->siterootid;
            $list    = implode(',', $aList);
        }
        $this->user['db_mountpoints'] = $list;
        $this->source->ping();
        DB::updateRecord('be_users', [ 'db_mountpoints' => $list ], [ 'uid' => $this->user['uid'] ]);
    }

    private function finalYaml(): void
    {
        if (method_exists($this->template, 'finalYaml')) {
            $this->template->finalYaml($this);
        }

        $page = DB::getRecord('pages', $this->siteconfig['rootPageId']);
        $parent = DB::getRecord('pages', $page['pid']);
        $parentofparent = DB::getRecord('pages', $parent['pid']);

        $identifier = '';
        if ($parentofparent !== []) {
            $identifier =  self::generateslug(trim((string)$parentofparent['slug'], '/')) . '-';
        }

        $parentslug = self::generateslug(trim((string)$parent['title'], '/'));
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')
                      ->update(
                          'pages',
                          ['uid'=>$parent['uid']],
                          ['slug'=>'/' . $parentslug]
                      );

        $identifier .= $parentslug;
        if ($identifier == '-microsites') {
            $identifier = 'microsites-' . self::generateslug($page['title']);
        }

        if ($this->errorpage > 0) {
            $this->siteconfig['errorHandling'][0]['errorContentSource'] = 't3://page?uid=' . $this->errorpage;
        }

        $path = Environment::getProjectPath();
        @mkdir($path . '/config');
        @mkdir($path . '/config/sites');
        @mkdir($path . '/config/sites/' . $identifier);
        file_put_contents(
            $path . '/config/sites/' . $identifier . '/config.yaml',
            Yaml::dump($this->siteconfig, 99, 2)
        );
    }

    private function cloneContent_final_columntype_flex($column, $columnconfig, $row, $parameters)
    {
        $table = $parameters['table'];
        $func = 'cloneContent_final_columntype_flex_' . $table . '_' . $column;
        if ($table == 'tt_content') {
            $func .= '_' . $row['CType'];

            if ($row['CType'] == 'list') {
                $func .= '_' . $row['list_type'];
            }
        }
        if (method_exists($this->source, $func)) {
            $row = $this->source->$func($row, $this);
        }
        if (method_exists($this->template, $func)) {
            $row = $this->template->$func($row, $this);
        }
        if (method_exists($this, $func)) {
            $row = $this->$func($row, $this);
        }
        return $row;
    }

    //private function cloneContent_final_column_header_link($column, $columnconfig, $row, $parameters)
    private function cloneContent_final_wizards_link($column, $columnconfig, $row, $parameters)
    {
        if (!empty($row[$column])) {
            //$this->out(__FUNCTION__.' '.$parameters['table'].' '.$column.' '.$row[$column]);
            $row[$column] = $this->translateLinkString($row[$column]);
        }
        return $row;
    }

    private function cloneContent_final_column_bodytext($column, $columnconfig, $row, $parameters)
    {
        if (!empty($row['bodytext'])) {
            preg_match_all('/<link\s+.*>/U', (string)$row['bodytext'], $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    $row['bodytext'] = str_replace($match, '<link ' . $this->translateLinkString(substr((string)$match, 6, -1)) . '>', (string)$row['bodytext']);
                }
            }
            $row['bodytext'] = str_replace('%EMAIL%', $this->task->getContact(), (string)$row['bodytext']);
        }
        return $row;
    }

    private function cloneContent_final_columntype_select($column, $columnconfig, $row, $parameters)
    {
        $skipTables = [];
        //$skipColumns = ['l18n_parent'];
        if (isset($columnconfig['config']['foreign_table']) && !in_array($columnconfig['config']['foreign_table'], $skipTables)) {
            $this->out('Clean select Column ' . $column);

            $table = $parameters['table'];
            $olduid = $row['t3_origuid'] ?: $this->getTranslateUidReverse($table, $row['uid']);

            /*
             if (empty($olduid)) {
                 throw new \Exception('Reference clean without t3_origuid in table '.$table);
             }
            */

            $oldpid = $parameters['oldpid'];
            $newuid = $row['uid'];
            $newpid = $parameters['newpid'];

            if (isset($columnconfig['config']['MM'])) {
                $this->fixMMRelation($columnconfig['config']['foreign_table'], $columnconfig['config']['MM'], $olduid, $newuid);
            } else {
                $val = $row[$column];
                $list = GeneralUtility::trimExplode(',', $val, true);
                $newlist = [];
                foreach ($list as $tmpolduid) {
                    $tmp = GeneralUtility::trimExplode('_', $tmpolduid, true);
                    if ((is_countable($tmp) ? count($tmp) : 0) > 1) {
                        $reftable = $tmp[0];
                        $olduid = $tmp[1];
                    } else {
                        $reftable = $columnconfig['config']['foreign_table'];
                        $olduid = $tmp[0];
                    }

                    $newlist[] = (is_countable($tmp) ? count($tmp) : 0) > 1 ? $reftable . '_' . $this->getTranslateUid($reftable, $olduid) : $this->getTranslateUid($reftable, $olduid);
                }
                if ($newlist !== []) {
                    $row[$column] = implode(',', $newlist);
                }
            }
        }
        return $row;
    }

    private function fixMMRelation($table, $mmtable, $olduid, $newuid): void
    {
        $mm = $this->source->getMM($mmtable, $olduid, $table);
        foreach ($mm as $row) {
            if (isset($row['uid'])) {
                unset($row['uid']);
            }
            $newforeign = $this->getTranslateUid($table, $row['uid_foreign']);
            $row['uid_local'] = $newuid;
            $row['uid_foreign'] = $newforeign;
            $this->source->ping();
            DB::insertRecord($mmtable, $row);
        }
    }

    private function cloneContent_final_columntype_group($column, $columnconfig, $row, $parameters)
    {
        if ($columnconfig['config']['internal_type'] == 'db') {
            $this->out('Clean Group Column ' . $column);
            $table = $parameters['table'];
            $olduid = $row['t3_origuid'] ?: $this->getTranslateUidReverse($table, $row['uid']);
            /*
             if (empty($olduid)) {
                 throw new \Exception('Reference clean without t3_origuid in table '.$table);
             }
            */
            $oldpid = $parameters['oldpid'];
            $newuid = $row['uid'];
            $newpid = $parameters['newpid'];

            if (isset($columnconfig['config']['MM'])) {
                if (isset($columnconfig['config']['foreign_table'])) {
                    $tables = [$columnconfig['config']['foreign_table']];
                } elseif ($columnconfig['config']['allowed'] == '*') {
                    $tables = array_keys($GLOBALS['TCA']);
                } else {
                    $tables = GeneralUtility::trimExplode(',', $columnconfig['config']['allowed'], true);
                }

                foreach ($tables as $tbl) {
                    $this->fixMMRelation($tbl, $columnconfig['config']['MM'], $olduid, $newuid);
                }
            } else {
                $val = $row[$column];
                $list = GeneralUtility::trimExplode(',', $val, true);
                $newlist = [];
                foreach ($list as $tmpolduid) {
                    $tmp = GeneralUtility::trimExplode('_', $tmpolduid, true);
                    if ((is_countable($tmp) ? count($tmp) : 0) > 1) {
                        $reftable = $tmp[0];
                        $olduid = $tmp[1];
                    } else {
                        $reftable = $columnconfig['config']['allowed'];
                        $olduid = $tmp[0];
                    }
                    $newlist[] = (is_countable($tmp) ? count($tmp) : 0) > 1 ? $reftable . '_' . $this->getTranslateUid($reftable, $olduid) : $this->getTranslateUid($reftable, $olduid);
                }
                if ($newlist !== []) {
                    $row[$column] = implode(',', $newlist);
                }
            }
        }
        return $row;
    }

    private function cloneContent_clean_columntype_inline($column, $columnconfig, $row, $parameters)
    {
        $this->debug('Memory : ' . memory_get_usage());

        $this->debug('Clean inline Column ' . $column);
        $table = $parameters['table'];
        $olduid = $row['t3_origuid'];

        if (!empty($olduid)) {
            //throw new \Exception('Reference clean without t3_origuid');

            $newuid = $row['uid'];
            $newpid = $row['pid'];

            $oldrow = $this->source->getRow($table, ['uid'=>$olduid]);
            $oldpid = $oldrow['pid'];

            $pidlist = array_keys($this->pageMap);
            $this->debug(__METHOD__ . ':' . __LINE__);
            $inlines = $this->source->getIrre($table, $olduid, $oldpid, $oldrow, $columnconfig, $pidlist);
            $this->debug(__METHOD__ . ':' . __LINE__);
            foreach ($inlines as $inline) {
                $this->debug(__METHOD__ . ':' . __LINE__);
                $inlineuid = $inline['uid'];
                if (isset($this->contentmap[$columnconfig['config']['foreign_table']]) && isset($this->contentmap[$columnconfig['config']['foreign_table']][$inlineuid])) {
                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $this->source->ping();

                    $this->debug(__METHOD__ . ':' . __LINE__);

                    $test = DB::getRecord($columnconfig['config']['foreign_table'], $this->contentmap[$columnconfig['config']['foreign_table']][$inlineuid]);

                    $this->debug(__METHOD__ . ':' . __LINE__ . ':' . print_r(['*', $columnconfig['config']['foreign_table'], 'uid=' . $this->contentmap[$columnconfig['config']['foreign_table']][$inlineuid], $test], true));
                }

                $this->debug(__METHOD__ . ':' . __LINE__);
                if ($test) {
                    $orig = $test;

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $func = 'cleanContent_' . $columnconfig['config']['foreign_table'];
                    if (method_exists($this->source, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $test = $this->source->$func($test, $this);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }
                    if (method_exists($this->template, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $test = $this->template->$func($test, $this);

                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }
                    if (method_exists($this, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $test = $this->$func($test, $this);

                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }

                    $this->debug(__METHOD__ . ':' . __LINE__ . print_r($test, true));
                    $test = $this->runTCA(
                        'clean',
                        $GLOBALS['TCA'][$columnconfig['config']['foreign_table']]['columns'],
                        $test,
                        [
                            'table' => $columnconfig['config']['foreign_table'],
                            'pObj' => $parameters['pObj'],
                        ]
                    );
                    $this->debug(__METHOD__ . ':' . __LINE__ . ':' . print_r($test, true));

                    $update = [];
                    foreach ($test as $k => $v) {
                        if ($orig[$k] != $v) {
                            $update[$k] = $v;
                        }
                    }
                    unset($update['uid']);
                    unset($update['pid']);

                    $this->debug(__METHOD__ . ':' . __LINE__ . print_r($update, true));
                    if ($update !== []) {
                        $this->source->ping();

                        $this->debug(__METHOD__ . ':' . __LINE__);
                        DB::updateRecord($columnconfig['config']['foreign_table'], $update, ['uid'=>$orig['uid']]);
                    }
                } else {
                    $inline['t3_origuid'] = $inlineuid;
                    unset($inline['uid']);
                    $inline['pid'] = $newpid;

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    $inline[$columnconfig['config']['foreign_field']] = $newuid;
                    $func = 'cloneContent_pre_' . $columnconfig['config']['foreign_table'];

                    if (method_exists($this->source, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $inline = $this->source->$func($inlineuid, $oldpid, $inline, $this);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }
                    if (method_exists($this->template, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $inline = $this->template->$func($inlineuid, $oldpid, $inline, $this);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }
                    if (method_exists($this, $func)) {
                        $this->debug(__METHOD__ . ':' . __LINE__);
                        $inline = $this->$func($inlineuid, $oldpid, $inline);
                        $this->debug(__METHOD__ . ':' . __LINE__);
                    }

                    $this->debug(__METHOD__ . ':' . __LINE__);
                    if ($columnconfig['config']['foreign_table']=='sys_file_reference') {
                        print_r(__METHOD__ . ':' . __LINE__ . print_r($inline, true));
                    }

                    $inline = $this->runTCA(
                        'pre',
                        $GLOBALS['TCA'][$columnconfig['config']['foreign_table']]['columns'],
                        $inline,
                        [
                            'table' => $columnconfig['config']['foreign_table'],
                            'olduid' => $inlineuid,
                            'oldpid' => $oldpid,
                            'newpid' => $row['pid'],
                            'pObj' => $parameters['pObj'],
                        ]
                    );

                    if ($columnconfig['config']['foreign_table']=='sys_file_reference') {
                        print_r(__METHOD__ . ':' . __LINE__ . print_r($inline, true));
                    }

                    if ($inline) {
                        $this->source->ping();

                        [$rowaffected,$newinlineuid] = DB::insertRecord($columnconfig['config']['foreign_table'], $inline);

                        if (!$rowaffected) {
                            throw new \Exception(sprintf('error insert to %s with %s', $columnconfig['config']['foreign_table'], json_encode($inline, JSON_THROW_ON_ERROR)), 1_616_700_010);
                        }

                        $this->addContentMap($columnconfig['config']['foreign_table'], $inlineuid, $newinlineuid);
                        $this->addCleanupInline($columnconfig['config']['foreign_table'], $newinlineuid);

                        $this->runTCA(
                            'post',
                            $GLOBALS['TCA'][$columnconfig['config']['foreign_table']]['columns'],
                            $inline,
                            [
                                'table' => $columnconfig['config']['foreign_table'],
                                'olduid' => $inlineuid,
                                'newuid' => $newinlineuid,
                                'oldpid' => $oldpid,
                                'newpid' => $newpid,
                                'pObj' => $parameters['pObj'],
                            ]
                        );
                        $func = 'cloneContent_post_' . $columnconfig['config']['foreign_table'];
                        if (method_exists($this, $func)) {
                            $row = $this->$func($inlineuid, $oldpid, $newinlineuid, $inline, $parameters['pObj']);
                        }
                        if (method_exists($this->template, $func)) {
                            $this->template->$func($inlineuid, $oldpid, $newinlineuid, $inline, $this);
                        }
                        if (method_exists($this->source, $func)) {
                            $this->debug(__METHOD__ . ':' . __LINE__);
                            $test = $this->source->$func($test, $this);

                            $this->debug(__METHOD__ . ':' . __LINE__);
                        }
                    }
                }
            }
        } else {
            $this->out('No t3_origuid in Table ' . $table . ' - skipped');
        }
        return $row;
    }

    private function cloneContent_pre_sys_file_reference($olduid, $oldpid, $row)
    {
        $sys_file = $this->source->getRow('sys_file', ['uid'=>$row['uid_local']]);
        $newidentifier = $this->filemount['path'] . $sys_file['name'];
        $this->source->ping();
        $test = DB::getRecord('sys_file', $newidentifier, 'identifier');
        if (!empty($test)) {
            $this->out('Using File ' . $newidentifier);
            $row['uid_local'] = $test['uid'];
            return $row;
        }
        $this->out('Create File ' . $newidentifier);
        try {
            $new_sys_file = $this->source->handleFile($sys_file, $newidentifier);
            $this->addContentMap('sys_file', $sys_file['uid'], $new_sys_file['uid']);
            $row['uid_local'] = $new_sys_file['uid'];
        } catch (\Exception $e) {
            print_r([$e->getMessage(), $e->getTraceAsString()]);
            exit;
        }
        return $row;
    }

    private function debug(string $s): void
    {
        $this->out($s, 'DEBUG2');
    }
    private static function myEnableFields($table): array
    {
        //BackendUtility::BEenableFields($table)
        return [];
    }

    private static function generateslug($str): ?string
    {
        $str = strtolower(trim((string)$str));

        $str = preg_replace('~[^\\pL\d]+~u', '_', $str);
        $str = str_replace(
            [
                'ß',
                'ä',
                'ü',
                'ö',
                '-',
            ],
            [
                'ss',
                'ae',
                'ue',
                'oe',
                '_',
            ],
            $str
        );
        // Trim incl. dashes
        $str = trim($str, '-');
        if (function_exists('iconv')) {
            $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
        }
        $str = preg_replace('/[^a-z0-9-]/', '_', $str);

        return preg_replace('/-+/', '_', $str);
    }

    private static function array2xml($a)
    {
        /** @var $flexObj FlexFormTools */
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        return $flexObj->flexArray2Xml($a, true);
    }

    private function addToFormConfig(string $path): void
    {
        //

        $config = Yaml::parseFile(Environment::getPublicPath() . '/fileadmin/bk_form_config.yaml');

        if (!\array_search('1:' . $path, $config['TYPO3']['CMS']['Form']['persistenceManager']['allowedFileMounts'], true)) {
            $keys = \array_keys($config['TYPO3']['CMS']['Form']['persistenceManager']['allowedFileMounts']);
            $lastkey = array_pop($keys);
            $config['TYPO3']['CMS']['Form']['persistenceManager']['allowedFileMounts'][$lastkey+10] = '1:' . $path;
        }
        \file_put_contents(Environment::getPublicPath() . '/fileadmin/bk_form_config.yaml', Yaml::dump($config, 99, 2));
    }
}
