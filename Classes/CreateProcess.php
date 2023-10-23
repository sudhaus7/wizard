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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterAllContentCloneEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterClonedTreeInsertEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterContentCloneEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterCreateFilemountEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterFinalContentCloneEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterTreeCloneEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\BeforeClonedTreeInsertEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\BeforeContentCloneEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\BeforeSiteConfigWriteEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\BeforeUserCreationUCDefaultsEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\CleanContentEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\CreateBackendUserEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\CreateBackendUserGroupEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\CreateFilemountEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\FinalContentEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\GenerateSiteIdentifierEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\ModifyCleanContentSkipListEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\ModifyCloneContentSkipTableEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\ModifyCloneInlinesSkipTablesEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\PageSortEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\TCA\Column;
use SUDHAUS7\Sudhaus7Wizard\Events\TCA\ColumnType;
use SUDHAUS7\Sudhaus7Wizard\Events\TCA\Inlines;
use SUDHAUS7\Sudhaus7Wizard\Events\TtContent\FinalContentByCtypeEvent;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Services\TyposcriptService;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreateProcess implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use DbTrait;

    private EventDispatcherInterface $eventDispatcher;
    public array $allwaysIgnoreTables = [];

    public array $siteconfig = [];

    public array $pageMap = [];
    public ?Creator $task = null;
    public ?SourceInterface $source = null;
    public array $group = [];
    public array $user = [];
    public array $filemount = [];
    public array $contentmap = [];
    public array $cleanUpTodo = [];
    public string $debugsection = 'Init';
    protected $pObj;
    protected WizardProcessInterface $template;
    protected ?string $templatekey = null;
    protected int $tmplgroup = 0;
    protected int $tmpluser = 0;

    protected int $siterootid = 0;
    private array $checkusers = [];

    public $errorpage = 0;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run($mapfolder = null): bool
    {
        if ($this->logger === null) {
            $this->setLogger(new NullLogger());
        }

        //Globals::db()->store_lastBuiltQuery = true;
        $this->confArr = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_wizard');

        $this->log('Start', 'INFO', 'Start');

        $this->log('Read original Site config');
        try {
            $origsite = GeneralUtility::makeInstance(SiteFinder::class)
                                      ->getSiteByRootPageId($this->source->sourcePid());
            $this->siteconfig = $origsite->getConfiguration();
        } catch (SiteNotFoundException $e) {
            $this->debug('Original Site not found');
        }
        $this->createFilemount();
        $this->createGroup();
        $this->createUser();

        $sourcePid = $this->source->sourcePid();

        $sourcePage = $this->source->getRow('pages', [ 'uid' => $sourcePid ]);

        $this->log('Quelle: ' . $sourcePage['title']);
        if ($sourcePid > 0) {
            $this->pageMap[ $sourcePid ] = 0;
        }

        $this->log('Building Tree', 'INFO', 'Build TREE');
        $this->buildTree($sourcePid);

        $this->source->ping();
        $this->log('Clone Tree', 'INFO', 'Clone TREE');
        $this->cloneTree();

        $this->eventDispatcher->dispatch(new AfterTreeCloneEvent($this));

        $this->source->ping();
        $this->log('Clone Content', 'INFO', 'Clone Content');
        $this->cloneContent();
        $this->source->ping();

        $this->eventDispatcher->dispatch(new AfterAllContentCloneEvent($this));

        //$this->cleanContent('clean');

        while ($this->cleanUpTodo !== []) {
            $this->log('Clone Inlines', 'INFO', 'Clone Inlines');
            $this->cloneInlines();
            $this->source->ping();
        }

        $this->log('Clean Pages', 'INFO', 'Clean Pages');
        $this->cleanPages();
        $this->source->ping();
        $this->log('Clean Content', 'INFO', 'Clean Content');
        $this->finalContent();

        $this->eventDispatcher->dispatch(new AfterFinalContentCloneEvent($this));

        $this->source->ping();
        $this->log('About to finish', 'INFO', 'Finish');
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

    public function log($c, $info = 'DEBUG', string $section = null, array $context = []): void
    {
        if (! \is_null($section)) {
            $this->debugsection = $section;
        }

        match ($info) {
            'DEBUG2' => $this->logger->debug($c . ' - ' . $this->debugsection, $context),
            'DEBUG' => $this->logger->debug($c . ' - ' . $this->debugsection, $context),
            default => $this->logger->info($c . ' - ' . $this->debugsection, $context),
        };
    }

    private array $confArr = [];

    /**
     * @param $table
     * @param $old
     * @param $new
     * @internal
     */
    public function addContentMap($table, $old, $new): void
    {
        if (! isset($this->contentmap[ $table ])) {
            $this->contentmap[ $table ] = [];
        }

        $this->contentmap[ $table ][ $old ] = $new;
    }

    /**
     * @param $table
     * @param $uid
     * @interal
     */
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

        $this->eventDispatcher->dispatch(new PageSortEvent($old, BackendUtility::getRecord('pages', $new)));
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
        if (\str_contains((string)$uid, '_')) {
            $tableprefix = true;
            $x           = explode('_', (string)$uid);
            $uid         = array_pop($x);
            $table   = implode('_', $x);
        }
        if ($table == 'pages') {
            if (isset($this->pageMap[ (int)$uid ])) {
                $uid = (int)$this->pageMap[ (int)$uid ] > 0 ? (int)$this->pageMap[ (int)$uid ] : (int)$uid;
            }
        } elseif (isset($this->contentmap[ $table ]) && isset($this->contentmap[ $table ][ (int)$uid ])) {
            $uid = (int)$this->contentmap[ $table ][ (int)$uid ] > 0 ? (int)$this->contentmap[ $table ][ (int)$uid ] : (int)$uid;
        }

        //return (int)$uid;
        return $tableprefix ? $table . '_' . $uid : $uid;
    }

    public function finalContent_tt_content($row)
    {
        $event = new FinalContentByCtypeEvent($row['CType'], $row['CType']==='list' ? $row['list_type'] : null, $row, $this);
        $this->eventDispatcher->dispatch($event);
        return $event->getRecord();
    }

    /**
     * @param string $s
     *
     *
     * <p>You can insert <a class="link-page" href="65">internal links</a> (links to pages within the website), <a class="link-external" href="http://typo3.org">external links</a> (links to external sites) or <a class="link-mail" href="test@test.net">e-mail links</a> (links that open the user's email client when clicked).</p>
    <p>Additional link stylings:</p>
    <ul> 	<li><a class="link-arrow" href="65">Arrow</a></li> 	<li><a class="link-page" href="65">Page</a></li> 	<li><a class="link-file" href="file:1">File</a></li> 	<li><a class="link-folder" href="t3://folder?storage=1&amp;identifier=%2Fintroduction%2Fimages%2F">Folder</a></li> 	<li><a class="link-mail" href="john.doe@example.com">E-Mail&nbsp;</a></li> </ul>
     *
     * @return string
     */
    public function translateT3LinkString($s): string
    {
        $urlParts = parse_url($s);
        if (isset($urlParts['scheme']) && $urlParts['scheme'] === 't3') {
            $queryParts = [];
            parse_str($urlParts['query'], $queryParts);
            if (isset($queryParts['uid'])) {
                $queryParts['uid'] = match ($urlParts['host']) {
                    'file' => $this->getTranslateUid('sys_file', (int)$queryParts['uid']),
                    'page' => $this->getTranslateUid('pages', (int)$queryParts['uid']),
                    // no break
                    default=>(int)$queryParts['uid']
                };
            }
            foreach ($queryParts as $k=>$v) {
                if (\str_starts_with($k, 'amp;')) {
                    $k2 = substr($k, 4);
                    unset($queryParts[$k]);
                    $queryParts[$k2]=$v;
                }
            }

            $urlParts['query'] = http_build_query($queryParts);
            $s = $urlParts['scheme'] . '://';
            if (isset($urlParts['host'])) {
                $s .= $urlParts['host'];
            }
            if (!empty($urlParts['query'])) {
                $s .= '?' . $urlParts['query'];
            }
            if (!empty($urlParts['fragment'])) {
                $s .= '#' . $this->getTranslateUid('tt_content', $urlParts['fragment']);
            }
            $x=1;
        } elseif (isset($urlParts['host']) && $urlParts['host'] === 'file') {
            $s = $urlParts['host'] . ':' . $this->getTranslateUid('sys_file', (int)$urlParts['port']);
        } elseif (isset($urlParts['host']) && $urlParts['host'] === 'page') {
            $s = $urlParts['host'] . ':' . $this->getTranslateUid('pages', (int)$urlParts['port']);
        }
        return $s;
    }

    public function translateTypolinkString($s): string
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
                case 't3':
                    $a[0] = $this->translateT3LinkString($a[0]);
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
     * @param CreateProcess $pObj
     */
    public function finalContent_pages($row, &$pObj)
    {
        if ($row['doktype'] == 4 && ! empty($row['shortcut'])) {
            $row['shortcut'] = $pObj->getTranslateUid('pages', $row['shortcut']);
        }

        return $row;
    }

    private function createFilemount()
    {
        $shortname = $this->task->getShortname();
        $shortname = Tools::generateslug($shortname);

        $dir = $this->template->getMediaBaseDir() . $shortname . '/';

        $name = 'Medien ' . $this->task->getProjektname();

        $event =  new CreateFilemountEvent([
            'title' => $name,
            'path'  => $dir,
            'base'  => 1,
            'pid'   => 0,
        ], $this);
        $this->eventDispatcher->dispatch($event);
        $tmpl = $event->getRecord();

        $dir = $tmpl['path'];
        $name = $tmpl['title'];

        $this->log('Create Filemount 1 ' . $name . ' - ' . $dir);
        $this->source->ping();

        $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_filemounts')
                             ->select(
                                 [ '*' ],
                                 'sys_filemounts',
                                 [
                                     'path'=>$dir,
                                 ]
                             );

        $test = $res->fetchAssociative();
        if (! empty($test)) {
            $this->filemount = $test;
            $event =  new AfterCreateFilemountEvent($this->filemount, $this);
            $this->eventDispatcher->dispatch($event);
            return;
        }

        $this->log('Create Filemount ' . 'mkdir -p ' . Environment::getPublicPath() . '/fileadmin/' . $tmpl['path']);
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/' . $tmpl['path']);

        $this->source->ping();

        [ $rows, $newuid ] = self::insertRecord('sys_filemounts', $tmpl);
        if (! $rows) {
            throw new \Exception('Failed to insert', 1_616_680_146);
        }
        $tmpl['uid'] = $newuid;

        $this->filemount = $tmpl;
        $event =  new AfterCreateFilemountEvent($this->filemount, $this);
        $this->eventDispatcher->dispatch($event);
    }

    private function createGroup()
    {
        $tmpl            = $this->template->getTemplateBackendUserGroup($this);
        $this->tmplgroup = $tmpl['uid'];

        $groupname       = $this->confArr['groupprefix'] . ' ' . $this->task->getProjektname();
        $this->log('Create Group ' . $groupname);
        $this->source->ping();

        /** @var \TYPO3\CMS\Core\Database\Connection $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_groups');
        /** @var \Doctrine\DBAL\Result $res */
        $res = $query->select(
            [ '*' ],
            'be_groups',
            ['title'=>$groupname]
        );

        $test = $res->fetchAssociative();

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

        $event = new CreateBackendUserGroupEvent($tmpl, $this);
        $this->eventDispatcher->dispatch($event);
        $tmpl = $event->getRecord();

        $this->source->ping();

        [ $rows, $newuid ] = self::insertRecord('be_groups', $tmpl);

        if (!$rows) {
            throw new \Exception('cant create group', 1_616_680_548);
        }
        $tmpl['uid'] = $newuid;
        $this->group = $tmpl;
    }

    private function createUser()
    {
        $this->log('Create User ' . $this->task->getReduser());
        $tmpl           = $this->template->getTemplateBackendUser($this);
        $this->tmpluser = $tmpl['uid'];
        $this->source->ping();

        /** @var \TYPO3\CMS\Core\Database\Connection $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users');
        /** @var \Doctrine\DBAL\Result $res */
        $res = $query->select(
            [ '*' ],
            'be_users',
            ['username'=>$this->task->getReduser()]
        );
        $test = $res->fetchAssociative();

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

            self::updateRecord('be_users', [
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
        if (!empty($this->task->getRedemail())) {
            $tmpl['email']            = $this->task->getRedemail();
        }
        $tmpl['file_mountpoints'] = $this->filemount['uid'];
        $tmpl['admin']            = 0;
        $tmpl['lastlogin']        = 0;
        $tmpl['crdate']           = time();
        $tmpl['tstamp']           = time();
        $tmpl['description']      = 'Angelegt durch Wizard';
        $tmpl['TSconfig']         = '';
        $uc                       = [];

        $uc['titleLen'] = 50;
        $uc['edit_RTE'] = 1;
        $uc['resizeTextareas_MaxHeight'] = 500;
        $uc['lang'] = 'default';

        $event = new BeforeUserCreationUCDefaultsEvent($uc, $this);
        $this->eventDispatcher->dispatch($event);
        $uc = $event->getUc();

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
        $event = new CreateBackendUserEvent($tmpl, $this);
        $this->eventDispatcher->dispatch($event);
        $tmpl = $event->getRecord();
        $this->source->ping();

        [ $rows, $newuid ] = self::insertRecord('be_users', $tmpl);

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

    /**
     * @param $newrootpage
     * @deprecated
     */
    private function updateMountpoint($newrootpage): void
    {
        //is this needed?
        //$this->filemount['relatepage'] = $newrootpage;
        //self::updateRecord('sys_filemounts', [ 'relatepage' => $newrootpage ], [ 'uid' => $this->filemount['uid'] ]);
    }

    private function cloneTree()
    {
        $this->log('Clone Tree Start');
        $sourcePid = (int)$this->source->sourcePid();
        foreach (array_keys($this->pageMap) as $old) {
            $page = $this->source->getRow('pages', [ 'uid' => $old ]);

            $this->log('Cloning Page ' . $page['title']);
            unset($page['uid']);

            $page['t3_origuid'] = $old;

            $page = $this->staticValueReplacement('pages', $page);

            if (! $this->isAdmin($page['perms_userid'])) {
                $page['perms_userid']  = $this->user['uid'];
                $page['perms_groupid'] = $this->group['uid'];
            }

            if ($old == $sourcePid) {
                $page['title'] = $this->task->getProjektname();
            }

            if ($page['is_siteroot']) {
                $page['title'] = $this->task->getLongname();

                $conf = TyposcriptService::parse((string)$page['TSconfig']);
                $conf['TCEMAIN.']['permissions.']['userid'] = $this->user['uid'];
                $conf['TCEMAIN.']['permissions.']['groupid'] = $this->group['uid'];
                $page['TSconfig'] = TyposcriptService::fold($conf);
            }

            if (isset($this->pageMap[ $page['pid'] ]) && $this->pageMap[ $page['pid'] ] > 0) {
                $page['pid'] = (int)$this->pageMap[ $page['pid'] ];
            }

            $event = new BeforeClonedTreeInsertEvent($old, $page, $this);
            $this->eventDispatcher->dispatch($event);
            $page = $event->getRecord();

            $this->source->ping();

            if ((int)$page['pid'] > 0 || ((int)$page['pid']===0 && $page['is_siteroot'])) {
                [ $rowsaffected, $newpageid ] = self::insertRecord('pages', $page);

                if (! $rowsaffected) {
                    throw new \Exception('Create page failed', 1_616_685_103);
                }
                $this->pageMap[ $old ] = $newpageid;
                $this->addContentMap('pages', $old, $this->pageMap[ $old ]);
            }
            if ($page['is_siteroot']) {
                $this->createDomain($this->pageMap[ $old ]);
                //$this->updateMountpoint($this->pageMap[ $old ]);
                $this->siterootid = $this->pageMap[ $old ];
            }
            $this->eventDispatcher->dispatch(new AfterClonedTreeInsertEvent($old, $page, $this));
        }
        $this->log('Clone Tree End');
    }

    private function isAdmin($uid)
    {
        if (! isset($this->checkusers[ $uid ])) {
            $this->source->ping();
            $this->checkusers[ $uid ] = BackendUtility::getRecord('be_users', $uid);
        }
        if (is_array($this->checkusers[ $uid ])) {
            return $this->checkusers[ $uid ]['admin'];
        }

        return false;
    }

    private function createDomain($pid): void
    {
        $this->siteconfig['rootPageId'] = $pid;
        // this is the case if the hostname has a port added, then http:// will be chosen
        $proto = strpos($this->task->getDomainname(), ':')!==false ? 'http://' : 'https://';
        $this->siteconfig['base']       = $proto . $this->task->getDomainname() . '/';
    }

    private function cloneContent()
    {
        $runTables = $this->source->getTables();
        $this->log('Start Clone Content');

        $aSkip = [
            'pages',
            'sys_domain',
            'sys_log',
            'sys_file_reference',
            'tx_impexp_presets',
            'tx_extensionmanager_domain_model_extension',
            'be_users',
            'be_groups',
            'tx_sudhaus7wizard_domain_model_creator',
            'sys_file',
            'sys_action',
        ];

        foreach ($GLOBALS['TCA'] as $tcatable => $tca) {
            if (isset($tca['ctrl']['rootLevel']) && (int)$tca['ctrl']['rootLevel'] === 1 && !in_array($tcatable, $aSkip)) {
                $aSkip[]=$tcatable;
            }
        }

        $event = new ModifyCloneContentSkipTableEvent($aSkip, $this);
        $this->eventDispatcher->dispatch($event);
        $aSkip = $event->getSkipList();

        $aSkip = array_merge($aSkip, $this->allwaysIgnoreTables);
        foreach ($runTables as $table) {
            $config = $GLOBALS['TCA'][ $table ];
            if (! in_array($table, $aSkip)) {
                $filteredPids = $this->getSource()->filterByPid($table, array_keys($this->pageMap));

                foreach ($filteredPids as $oldpid) {
                    $newpid = $this->pageMap[$oldpid];
                    $where        = self::myEnableFields($table);
                    $where['pid'] = $oldpid;
                    $rows         = $this->source->getRows($table, $where);
                    foreach ($rows as $row) {
                        $this->log('Content Clone ' . $table . ' ' . $row['uid']);

                        $olduid = $row['uid'];
                        unset($row['uid']);
                        $row['pid'] = $newpid;
                        if (self::tableHasField($table, 't3_origuid')) {
                            $row['t3_origuid'] = $olduid;
                        }

                        $row = $this->staticValueReplacement($table, $row);

                        $event = new BeforeContentCloneEvent($table, $olduid, $oldpid, $row, $this);
                        $this->eventDispatcher->dispatch($event);
                        $row = $event->getRecord();

                        $row = $this->runTCA('pre', $config['columns'], $row, [
                            'table'  => $table,
                            'olduid' => $olduid,
                            'oldpid' => $oldpid,
                            'newpid' => $newpid,
                            'pObj'   => $this,
                        ]);

                        $this->source->ping();
                        if ($row) {
                            [ $rowsaffected, $newuid ] = self::insertRecord($table, $row);

                            if (! $rowsaffected) {
                                throw new \Exception(sprintf(
                                    'cannot insert into %s payload %s',
                                    $table,
                                    json_encode($row)
                                ), 1_616_695_930);
                            }

                            $this->log('Insert ' . $table . ' olduid ' . $olduid . ' oldpid ' . $oldpid . ' newuid ' . $newuid . ' newpid ' . $newpid);

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

                            $this->eventDispatcher->dispatch(new AfterContentCloneEvent($table, $olduid, $oldpid, $newuid, $row, $this));
                        } else {
                            $this->log('ERROR NO ROW ' . print_r([
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

    public function isTCAFieldActiveForThisRecord(string $table, string $column, array $record): bool
    {
        if (isset($GLOBALS['TCA'][$table])) {
            $tca = $GLOBALS['TCA'][$table];
            $tcatype = $tca['ctrl']['type'] ?? 'type';
            $tcatypevalue = $record[ $tcatype ] ?? 0;
            if (isset($tca['types'][$tcatypevalue]) && \is_array($tca['types'][$tcatypevalue]['showitem'])) {
                $showitem = $tca['types'][ $tcatypevalue ]['showitem'];
            } elseif ($tcatypevalue === 0  && isset($tca['types'][1]) && \is_array($tca['types'][1]['showitem'])) {
                $tcatypevalue = 1;
                $showitem = $tca['types'][ $tcatypevalue ]['showitem'];
            } else {
                return true;
            }

            $fields = GeneralUtility::trimExplode(',', $showitem, true);
            foreach ($fields as $field) {
                if (\str_starts_with($field, '--div--')) {
                    continue;
                }
                if (\str_starts_with($field, '--palette--')) {
                    $tmp = GeneralUtility::trimExplode(';', $field, true);
                    $palette = array_pop($tmp);
                    if (isset($tca['palettes'][$palette]['showitem'])) {
                        $paletteShowitem = GeneralUtility::trimExplode(',', $tca['palettes'][$palette]['showitem']);
                        foreach ($paletteShowitem as $paletteItem) {
                            if (\str_starts_with($paletteItem, $column)) {
                                return true;
                            }
                        }
                    }
                } else {
                    if (\str_starts_with($field, $column)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function runTCA(string $state, $config, $row, array $parameters)
    {
        foreach ($config as $column => $columnconfig) {
            if (!$this->isTCAFieldActiveForThisRecord($parameters['table'], $column, $row)) {
                continue;
            }

            //$this->out('runTCA '.$state.' '.$parameters['table'].' '.$column);
            $columntype = strtolower($columnconfig['config']['type']);
            switch($state) {
                case 'pre':
                    $event = new Column\BeforeEvent($parameters['table'], $column, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $event = new ColumnType\BeforeEvent($parameters['table'], $column, $columntype, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();
                    break;
                case 'post':
                    $event = new Column\AfterEvent($parameters['table'], $column, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $event = new ColumnType\AfterEvent($parameters['table'], $column, $columntype, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();
                    break;
                case 'final':

                    $event = new Column\FinalEvent($parameters['table'], $column, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = match ($columntype) {
                        'group'=>$this->cloneContent_final_columntype_group($column, $columnconfig, $row, $parameters),
                        'select'=>$this->cloneContent_final_columntype_select($column, $columnconfig, $row, $parameters),
                        // no break
                        default=>$row
                    };

                    $event = new ColumnType\FinalEvent($parameters['table'], $column, $columntype, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    if (isset($columnconfig['config']['wizards'])) {
                        foreach ($columnconfig['config']['wizards'] as $wizard => $wizardconfig) {
                            $row = $this->cloneContent_final_wizards_link($wizard, $wizard, $row, $parameters);
                        }
                    }

                    break;
                case 'clean':
                    $event = new Column\CleanEvent($parameters['table'], $column, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = match ($columntype) {
                        'inline'=>$this->cloneContent_clean_columntype_inline($column, $columnconfig, $row, $parameters),
                        // no break
                        default=>$row
                    };

                    $event = new ColumnType\CleanEvent($parameters['table'], $column, $columntype, $columnconfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();
                    break;
            }
        }
        return $row;
    }

    private function cloneInlines(): void
    {
        $this->log('Start Inlines Clone , TODO ' . count($this->cleanUpTodo));
        $event = new ModifyCloneInlinesSkipTablesEvent([
            'sys_domain',
            //'sys_file_reference',
            'be_users',
            'be_groups',
            'tx_sudhaus7wizard_domain_model_creator',
        ], $this);
        $this->eventDispatcher->dispatch($event);

        $aSkip = array_merge($event->getSkipList(), $this->allwaysIgnoreTables);
        $map   = $this->cleanUpTodo;
        //print_r([ 'cleanupTodo' => $this->cleanUpTodo ]);
        $this->cleanUpTodo = [];

        foreach ($map as $table => $newuid) {
            $config = $GLOBALS['TCA'][ $table ];

            if (! in_array($table, $aSkip)) {
                $this->source->ping();

                $query = self::getQueryBuilderWithoutRestriction($table);
                $stmt = $query->select('*')
                              ->from($table)
                              ->where(
                                  $query->expr()->in('uid', $newuid)
                              )
                              ->execute();

                while ($origrow = $stmt->fetchAssociative()) {
                    // fetch a clean version, might have changed in between
                    $row = BackendUtility::getRecord($table, $origrow['uid']);
                    if (!$row) {
                        continue;
                    }
                    $this->log('Content Cleanup ' . $table . ' ' . $row['uid']);
                    $event = new Inlines\CleanEvent($table, $row, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = $this->runTCA('clean', $config['columns'], $row, [
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

                        self::updateRecord($table, $update, [ 'uid' => $origrow['uid'] ]);
                    }
                }
            }
        }
    }

    private function cleanPages(): void
    {
        $this->log('Start Pages Cleanup ');
        //$aSkip = ['pages','sys_domain', 'sys_file_reference', 'be_users', 'be_groups'];
        //foreach ($GLOBALS['TCA'] as $table => $config) {
        $table  = 'pages';
        $config = $GLOBALS['TCA']['pages'];

        foreach ($this->pageMap as $oldpid => $newpid) {
            $this->source->ping();

            $query = self::getQueryBuilderWithoutRestriction($table);
            $res = $query->select('*')
                ->from($table)
                ->where(
                    $query->expr()->eq('uid', $newpid)
                )->execute();

            while ($origrow = $res->fetchAssociative()) {
                // fetch a clean version, might have changed in between
                $row = BackendUtility::getRecord($table, $origrow['uid']);
                if (!$row) {
                    continue;
                }
                $this->log('Page Cleanup ' . $row['title'] . ' ' . $row['uid']);

                $row = $this->finalContent_pages($row, $this);

                $event = new FinalContentEvent($table, $row, $this);
                $this->eventDispatcher->dispatch($event);
                $row = $event->getRecord();

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
                    $this->log(__FILE__ . ':' . __LINE__ . ' ' . $table . ' update ' . print_r($update, true));

                    self::updateRecord($table, $update, [ 'uid' => $origrow['uid'] ]);
                }
            }
        }
    }

    private function finalContent(): void
    {
        $this->log('Start Content Cleanup ');

        $event = new ModifyCleanContentSkipListEvent([
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
        ], $this);
        $this->eventDispatcher->dispatch($event);

        $aSkip   = array_merge($event->getSkipList(), $this->allwaysIgnoreTables);
        $newpids = \array_values($this->pageMap);
        foreach ($GLOBALS['TCA'] as $table => $config) {
            if (! in_array($table, $aSkip)) {
                //foreach ($this->pageMap as $oldpid => $newpid) {
                $this->source->ping();
                $this->log('Content Cleanup ' . $table);

                $query = self::getQueryBuilderWithoutRestriction($table);

                $stmt = $query->select('*')
                              ->from($table)
                              ->where(
                                  $query->expr()->in('pid', $newpids)
                              )
                              ->execute();

                while ($origrow = $stmt->fetchAssociative()) {
                    // fetch a clean version, might have changed in between
                    $row = BackendUtility::getRecord($table, $origrow['uid']);
                    if (!$row) {
                        continue;
                    }
                    $this->log('Content Cleanup ' . $table . ' ' . $row['uid']);

                    if ($table === 'tt_content') {
                        $row = $this->finalContent_tt_content($row);
                    }

                    $event = new FinalContentEvent($table, $row, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = $this->runTCA('final', $config['columns'], $row, [
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

                        self::updateRecord($table, $update, [ 'uid' => $origrow['uid'] ]);
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
            self::updateRecord('be_groups', [ 'db_mountpoints' => $list ], [ 'uid' => $this->group['uid'] ]);
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
        self::updateRecord('be_users', [ 'db_mountpoints' => $list ], [ 'uid' => $this->user['uid'] ]);
    }

    private function finalYaml(): void
    {
        $path = Environment::getProjectPath();
        try {
            GeneralUtility::mkdir_deep($path . '/config/sites');
        } catch (\Exception $e) {
        }

        $event = new GenerateSiteIdentifierEvent($this->siteconfig, $path, $this);
        $this->eventDispatcher->dispatch($event);
        $identifier = $event->getIdentifier();

        /*
        $page = BackendUtility::getRecord('pages', $this->siteconfig['rootPageId']);
        $parent = BackendUtility::getRecord('pages', $page['pid']);
        $parentofparent = BackendUtility::getRecord('pages', $parent['pid']);

        $identifier = '';
        if ($parentofparent !== []) {
            $identifier =  Tools::generateslug(trim((string)$parentofparent['slug'], '/')) . '-';
        }

        $parentslug = Tools::generateslug(trim((string)$parent['title'], '/'));
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')
                      ->update(
                          'pages',
                          ['uid'=>$parent['uid']],
                          ['slug'=>'/' . $parentslug]
                      );

        $identifier .= $parentslug;
        if ($identifier == '-microsites') {
            $identifier = 'microsites-' . Tools::generateslug($page['title']);
        }

        */

        $this->siteconfig['websiteTitle'] = $this->getTask()->getProjektname();

        if (empty($identifier)) {
            $identifier = Tools::generateslug($this->getTask()->getShortname() ?? $this->getTask()->getProjektname());

            if (is_dir($path . '/config/sites/' . $identifier)) {
                $identifier = Tools::generateslug($this->getTask()->getLongname() ?? $this->getTask()->getDomainname());
            }
        }

        if (isset($this->siteconfig['errorHandling'])) {
            foreach ($this->siteconfig['errorHandling'] as $idx=>$config) {
                if (isset($config['errorContentSource']) && \str_starts_with($config['errorContentSource'], 't3://')) {
                    $this->siteconfig['errorHandling'][$idx]['errorContentSource'] = $this->translateT3LinkString($config['errorContentSource']);
                }
            }
        }

        GeneralUtility::mkdir($path . '/config/sites/' . $identifier);

        $event = new BeforeSiteConfigWriteEvent($this->siteconfig, $this);
        $this->eventDispatcher->dispatch($event);
        $this->siteconfig = $event->getSiteconfig();

        file_put_contents(
            $path . '/config/sites/' . $identifier . '/config.yaml',
            Yaml::dump($this->siteconfig, 99, 2)
        );
    }

    //private function cloneContent_final_column_header_link($column, $columnconfig, $row, $parameters)
    private function cloneContent_final_wizards_link($column, $columnconfig, $row, $parameters)
    {
        if (!empty($row[$column])) {
            $row[$column] = $this->translateTypolinkString($row[$column]);
        }
        return $row;
    }

    private function cloneContent_final_columntype_select($column, $columnconfig, $row, $parameters)
    {
        $skipTables = [];
        //$skipColumns = ['l18n_parent'];
        if (isset($columnconfig['config']['foreign_table']) && !in_array($columnconfig['config']['foreign_table'], $skipTables)) {
            $this->log('Clean select Column ' . $column);

            $table = $parameters['table'];

            $olduid = $row['t3_origuid'] ?? $this->getTranslateUidReverse($table, $row['uid']);

            /*
             if (empty($olduid)) {
                 throw new \Exception('Reference clean without t3_origuid in table '.$table);
             }
            */

            //$oldpid = $parameters['oldpid'];
            $newuid = $row['uid'];
            //$newpid = $parameters['newpid'];

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

    public function fixMMRelation($table, $mmtable, $olduid, $newuid): void
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
            self::insertRecord($mmtable, $row);
        }
    }

    private function cloneContent_final_columntype_group($column, $columnconfig, $row, $parameters)
    {
        if (isset($columnconfig['config']['internal_type']) && $columnconfig['config']['internal_type'] == 'db') {
            $this->log('Clean Group Column ' . $column);
            $table = $parameters['table'];
            $olduid = $row['t3_origuid'] ?? $this->getTranslateUidReverse($table, $row['uid']);

            /*
             if (empty($olduid)) {
                 throw new \Exception('Reference clean without t3_origuid in table '.$table);
             }
            */
            //$oldpid = $parameters['oldpid'];
            $newuid = $row['uid'];
            //$newpid = $parameters['newpid'];

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
        //$this->debug('Memory : ' . memory_get_usage());

        $this->log('Clean inline Column ' . $column);
        $table = $parameters['table'];
        $olduid = $row['t3_origuid'] ?? $this->getTranslateUidReverse($table, $row['uid']);
        if ($olduid) {
            $newuid = $row['uid'];
            $newpid = $row['pid'];

            $oldrow = $this->source->getRow($table, ['uid'=>$olduid]);
            $oldpid = 0;
            if (isset($oldpid['pid'])) {
                $oldpid = $oldrow['pid'];
            }

            $pidlist = array_keys($this->pageMap);
            $inlines = $this->source->getIrre($table, $olduid, $oldpid, $oldrow, $columnconfig, $pidlist);
            foreach ($inlines as $inline) {
                $inlineuid = $inline['uid'];
                $test = null;
                if (isset($this->contentmap[$columnconfig['config']['foreign_table']]) && isset($this->contentmap[$columnconfig['config']['foreign_table']][$inlineuid])) {
                    $this->source->ping();

                    $test = BackendUtility::getRecord($columnconfig['config']['foreign_table'], $this->contentmap[$columnconfig['config']['foreign_table']][$inlineuid]);

                    //$this->debug(__METHOD__ . ':' . __LINE__ . ':' . print_r(['*', $columnconfig['config']['foreign_table'], 'uid=' . $this->contentmap[$columnconfig['config']['foreign_table']][$inlineuid], $test], true));
                }

                if ($test) {
                    $orig = $test;

                    $event = new CleanContentEvent($columnconfig['config']['foreign_table'], $test, $this);
                    $this->eventDispatcher->dispatch($event);
                    $test = $event->getRecord();

                    // $this->debug(__METHOD__ . ':' . __LINE__ . print_r($test, true));
                    $test = $this->runTCA(
                        'clean',
                        $GLOBALS['TCA'][$columnconfig['config']['foreign_table']]['columns'],
                        $test,
                        [
                            'table' => $columnconfig['config']['foreign_table'],
                            'pObj' => $parameters['pObj'],
                        ]
                    );
                    //$this->debug(__METHOD__ . ':' . __LINE__ . ':' . print_r($test, true));

                    $update = [];
                    foreach ($test as $k => $v) {
                        if ($orig[$k] != $v) {
                            $update[$k] = $v;
                        }
                    }
                    $update[$columnconfig['config']['foreign_field']] = $newuid;
                    unset($update['uid']);
                    unset($update['pid']);

                    //$this->debug(__METHOD__ . ':' . __LINE__ . print_r($update, true));
                    if ($update !== []) {
                        $this->source->ping();

                        //$this->debug(__METHOD__ . ':' . __LINE__);
                        self::updateRecord($columnconfig['config']['foreign_table'], $update, ['uid'=>$orig['uid']]);
                    }
                } else {
                    //$columnconfig['config']['foreign_table']

                    if (self::tableHasField($columnconfig['config']['foreign_table'], 't3_origuid')) {
                        $inline['t3_origuid'] = $inlineuid;
                    }

                    unset($inline['uid']);
                    $inline['pid'] = $newpid;

                    $inline[$columnconfig['config']['foreign_field']] = $newuid;

                    $event = new BeforeContentCloneEvent($columnconfig['config']['foreign_table'], $inlineuid, $oldpid, $inline, $this);
                    $this->eventDispatcher->dispatch($event);
                    $inline = $event->getRecord();

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

                    if ($inline) {
                        $this->source->ping();

                        [$rowaffected,$newinlineuid] = self::insertRecord($columnconfig['config']['foreign_table'], $inline);

                        if (!$rowaffected) {
                            throw new \Exception(sprintf('error insert to %s with %s', $columnconfig['config']['foreign_table'], json_encode($inline)), 1_616_700_010);
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

                        $this->eventDispatcher->dispatch(new AfterContentCloneEvent($columnconfig['config']['foreign_table'], $inlineuid, $oldpid, $newinlineuid, $inline, $this));
                    }
                }
            }
        } else {
            $this->log('No t3_origuid in Table ' . $table . ' - skipped');
        }
        return $row;
    }

    public function staticValueReplacement(string $table, array $row): array
    {
        if (!empty($this->getTask()->getValuemapping())) {
            $config = $this->getTask()->getValuemappingArray();
            if (isset($config[$table])) {
                foreach ($config[$table] as $field=>$map) {
                    if (isset($row[$field])) {
                        $origvalue = $row[$field];
                        if (isset($map[$origvalue])) {
                            $row[$field] = $map[$origvalue];
                        }
                    }
                }
            }
        }
        return $row;
    }

    private function debug(string $s): void
    {
        $this->log($s, 'DEBUG2');
    }
    private static function myEnableFields($table): array
    {
        //BackendUtility::BEenableFields($table)
        return [];
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

    /**
     * @return array
     */
    public function getAllwaysIgnoreTables(): array
    {
        return $this->allwaysIgnoreTables;
    }

    /**
     * @param array $allwaysIgnoreTables
     */
    public function setAllwaysIgnoreTables(array $allwaysIgnoreTables): void
    {
        $this->allwaysIgnoreTables = $allwaysIgnoreTables;
    }

    /**
     * @return array
     */
    public function getSiteconfig(): array
    {
        return $this->siteconfig;
    }

    /**
     * @param array $siteconfig
     */
    public function setSiteconfig(array $siteconfig): void
    {
        $this->siteconfig = $siteconfig;
    }

    /**
     * @return array
     */
    public function getPageMap(): array
    {
        return $this->pageMap;
    }

    /**
     * @param array $pageMap
     */
    public function setPageMap(array $pageMap): void
    {
        $this->pageMap = $pageMap;
    }

    /**
     * @return Creator|null
     */
    public function getTask(): ?Creator
    {
        return $this->task;
    }

    /**
     * @param Creator|null $task
     */
    public function setTask(?Creator $task): void
    {
        $this->task = $task;
    }

    /**
     * @return SourceInterface
     */
    public function getSource(): SourceInterface
    {
        return $this->source;
    }

    /**
     * @param SourceInterface $source
     */
    public function setSource(SourceInterface $source): void
    {
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function getGroup(): array
    {
        return $this->group;
    }

    /**
     * @param array $group
     */
    public function setGroup(array $group): void
    {
        $this->group = $group;
    }

    /**
     * @return array
     */
    public function getUser(): array
    {
        return $this->user;
    }

    /**
     * @param array $user
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function getFilemount(): array
    {
        return $this->filemount;
    }

    /**
     * @param array $filemount
     */
    public function setFilemount(array $filemount): void
    {
        $this->filemount = $filemount;
    }

    /**
     * @return array
     */
    public function getContentmap(): array
    {
        return $this->contentmap;
    }

    /**
     * @param array $contentmap
     */
    public function setContentmap(array $contentmap): void
    {
        $this->contentmap = $contentmap;
    }

    /**
     * @return array
     */
    public function getCleanUpTodo(): array
    {
        return $this->cleanUpTodo;
    }

    /**
     * @param array $cleanUpTodo
     */
    public function setCleanUpTodo(array $cleanUpTodo): void
    {
        $this->cleanUpTodo = $cleanUpTodo;
    }

    /**
     * @return string
     */
    public function getDebugsection(): string
    {
        return $this->debugsection;
    }

    /**
     * @param string $debugsection
     */
    public function setDebugsection(string $debugsection): void
    {
        $this->debugsection = $debugsection;
    }

    /**
     * @return WizardProcessInterface
     */
    public function getTemplate(): WizardProcessInterface
    {
        return $this->template;
    }

    /**
     * @param WizardProcessInterface $template
     */
    public function setTemplate(WizardProcessInterface $template): void
    {
        $this->template = $template;
    }

    /**
     * @return string|null
     */
    public function getTemplatekey(): ?string
    {
        return $this->templatekey;
    }

    /**
     * @param string|null $templatekey
     */
    public function setTemplatekey(?string $templatekey): void
    {
        $this->templatekey = $templatekey;
    }

    /**
     * @return int
     */
    public function getTmplgroup(): int
    {
        return $this->tmplgroup;
    }

    /**
     * @param int $tmplgroup
     */
    public function setTmplgroup(int $tmplgroup): void
    {
        $this->tmplgroup = $tmplgroup;
    }

    /**
     * @return int
     */
    public function getTmpluser(): int
    {
        return $this->tmpluser;
    }

    /**
     * @param int $tmpluser
     */
    public function setTmpluser(int $tmpluser): void
    {
        $this->tmpluser = $tmpluser;
    }

    /**
     * @return int
     */
    public function getSiterootid(): int
    {
        return $this->siterootid;
    }

    /**
     * @param int $siterootid
     */
    public function setSiterootid(int $siterootid): void
    {
        $this->siterootid = $siterootid;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
