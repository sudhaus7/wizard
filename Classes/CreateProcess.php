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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
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
use SUDHAUS7\Sudhaus7Wizard\Events\TCAFieldActiveForThisRecordEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\TranslateUidEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\TranslateUidReverseEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\TtContent\FinalContentByCtypeEvent;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Services\TyposcriptService;
use SUDHAUS7\Sudhaus7Wizard\Sources\SourceInterface;
use SUDHAUS7\Sudhaus7Wizard\Traits\DbTrait;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CreateProcess implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use DbTrait;

    public array $alwaysIgnoreTables = [];
    /**
     * @var array<array-key, mixed>
     */
    public array $siteConfig = [];

    /**
     * @var array<array-key, mixed>
     */
    public array $pageMap = [];

    public Creator $task;

    public ?SourceInterface $source = null;

    /**
     * @var array<array-key, mixed>
     */
    public array $group = [];
    /**
     * @var array<array-key, mixed>
     */
    public array $user = [];
    /**
     * @var array<array-key, mixed>
     */
    public array $filemount = [];
    /**
     * @var array<array-key, mixed>
     */
    public array $contentMap = [];
    /**
     * @var array<array-key, mixed>
     */
    public array $cleanUpTodo = [];

    public string $debugSection = 'Init';

    public $errorPage = 0;

    protected $pObj;

    protected WizardProcessInterface $template;

    protected ?string $templateKey = null;

    protected int $tmplGroup = 0;

    protected int $tmplUser = 0;

    protected int $siteRootId = 0;
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var array<array-key, mixed>
     */
    private array $checkUsers = [];
    private array $confArr = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws \Exception
     */
    public function run($mapFolder = null): bool
    {
        if ($this->logger === null) {
            $this->setLogger(new NullLogger());
        }

        //Globals::db()->store_lastBuiltQuery = true;
        $this->confArr = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_wizard');

        $this->log('Start', 'INFO', 'Start');

        $this->createFilemount();
        $this->createGroup();
        $this->createUser();

        $sourcePid = $this->source->sourcePid();

        $sourcePage = $this->source->getRow('pages', ['uid' => $sourcePid]);

        $this->log('Quelle: ' . $sourcePage['title']);
        if ($sourcePid > 0) {
            $this->pageMap[$sourcePid] = 0;
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

        $this->task->setPid($this->pageMap[$sourcePid]);
        if (!\is_null($mapFolder)) {
            if ($fp = fopen($mapFolder . '/page.csv', 'w')) {
                foreach ($this->pageMap as $k => $v) {
                    fwrite($fp, sprintf("%s;%s\n", $k, $v));
                }
                fclose($fp);
            }
            foreach ($this->contentMap as $table => $map) {
                if ($fp = fopen($mapFolder . '/' . $table . '.csv', 'w')) {
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
        if (!\is_null($section)) {
            $this->debugSection = $section;
        }

        match ($info) {
            'DEBUG2', 'DEBUG' => $this->logger->debug($c . ' - ' . $this->debugSection, $context),
            default => $this->logger->info($c . ' - ' . $this->debugSection, $context),
        };
    }

    private function debug(string $s): void
    {
        $this->log($s, 'DEBUG2');
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    private function createFilemount(): void
    {
        $shortname = $this->task->getShortname();
        $shortname = Tools::generateSlug($shortname);

        $dir = $this->template->getMediaBaseDir() . $shortname . '/';

        $name = 'Medien ' . $this->task->getProjektname();

        $event = new CreateFilemountEvent([
            'title' => $name,
            'path' => $dir,
            'base' => 1,
            'pid' => 0,
        ], $this);
        $this->eventDispatcher->dispatch($event);
        $tmpl = $event->getRecord();

        $dir = $tmpl['path'];
        $name = $tmpl['title'];

        $this->log('Create Filemount 1 ' . $name . ' - ' . $dir);
        $this->source->ping();

        $res = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_filemounts')
            ->select(
                ['*'],
                'sys_filemounts',
                [
                    'path' => $dir,
                ]
            );

        $test = $res->fetchAssociative();
        if (!empty($test)) {
            $this->filemount = $test;
            $event = new AfterCreateFilemountEvent($this->filemount, $this);
            $this->eventDispatcher->dispatch($event);
            return;
        }

        $this->log('Create Filemount ' . 'mkdir -p ' . Environment::getPublicPath() . '/fileadmin/' . $tmpl['path']);
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/' . $tmpl['path']);

        $this->source->ping();

        [$rows, $newUid] = self::insertRecord('sys_filemounts', $tmpl);
        if (!$rows) {
            throw new \Exception('Failed to insert filemount', 1700484068172);
        }
        $tmpl['uid'] = $newUid;

        $this->filemount = $tmpl;
        $event = new AfterCreateFilemountEvent($this->filemount, $this);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * @throws Exception
     */
    private function createGroup(): void
    {
        $tmpl = $this->template->getTemplateBackendUserGroup($this);
        $this->tmplGroup = $tmpl['uid'];

        $groupName = $this->confArr['groupprefix'] . ' ' . $this->task->getProjektname();
        $this->log('Create Group ' . $groupName);
        $this->source->ping();

        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_groups');
        $res = $query->select(
            ['*'],
            'be_groups',
            ['title' => $groupName]
        );

        $test = $res->fetchAssociative();

        if (!empty($test)) {
            $this->group = $test;
            return;
        }

        unset($tmpl['uid']);
        $tmpl['title'] = $groupName;
        $tmp = GeneralUtility::trimExplode(',', $tmpl['file_mountpoints']);
        $tmp[] = $this->filemount['uid'];
        $tmpl['file_mountpoints'] = implode(',', $tmp);
        $tmpl['crdate'] = time();
        $tmpl['tstamp'] = time();

        $event = new CreateBackendUserGroupEvent($tmpl, $this);
        $this->eventDispatcher->dispatch($event);
        $tmpl = $event->getRecord();

        $this->source->ping();

        [$rows, $newUid] = self::insertRecord('be_groups', $tmpl);

        if (!$rows) {
            throw new \Exception('cant create group', 1_616_680_548);
        }
        $tmpl['uid'] = $newUid;
        $this->group = $tmpl;
    }

    /**
     * @throws InvalidPasswordHashException
     * @throws Exception
     * @throws \Exception
     */
    private function createUser(): void
    {
        $this->log('Create User ' . $this->task->getReduser());
        $tmpl = $this->template->getTemplateBackendUser($this);
        $this->tmplUser = $tmpl['uid'];
        $this->source->ping();

        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users');
        $res = $query->select(
            ['*'],
            'be_users',
            ['username' => $this->task->getReduser()]
        );
        $test = $res->fetchAssociative();

        if (!empty($test)) {
            $groups = GeneralUtility::trimExplode(',', $test['usergroup'], true);
            array_unshift($groups, $this->group['uid']);
            foreach ($groups as $k => $gid) {
                if ($gid == $this->tmplGroup) {
                    unset($groups[$k]);
                }
            }
            $test['usergroup'] = implode(',', $groups);
            $mountpoints = GeneralUtility::trimExplode(',', $test['file_mountpoints'], true);
            $mountpoints[] = $this->filemount['uid'];
            $test['file_mountpoints'] = implode(',', $mountpoints);
            $test['tstamp'] = time();
            $this->source->ping();

            self::updateRecord('be_users', [
                'file_mountpoints' => $test['file_mountpoints'],
                'usergroup' => $test['usergroup'],
                'tstamp' => time(),
            ], ['uid' => $test['uid']]);
            $this->user = $test;

            return;
        }

        unset($tmpl['uid']);
        $tmpl['username'] = $this->task->getReduser();
        $tmpl['realName'] = $this->task->getProjektname();
        if (!empty($this->task->getRedemail())) {
            $tmpl['email'] = $this->task->getRedemail();
        }
        $tmpl['file_mountpoints'] = $this->filemount['uid'];
        $tmpl['admin'] = 0;
        $tmpl['lastlogin'] = 0;
        $tmpl['crdate'] = time();
        $tmpl['tstamp'] = time();
        $tmpl['description'] = 'Angelegt durch Wizard';
        $tmpl['TSconfig'] = '';
        $uc = [];

        $uc['titleLen'] = 50;
        $uc['edit_RTE'] = 1;
        $uc['resizeTextareas_MaxHeight'] = 500;
        $uc['lang'] = 'default';

        $event = new BeforeUserCreationUCDefaultsEvent($uc, $this);
        $this->eventDispatcher->dispatch($event);
        $uc = $event->getUc();

        $tmpl['uc'] = serialize($uc);

        $salting = (new PasswordHashFactory())->getDefaultHashInstance('BE');
        $tmpl['password'] = $salting->getHashedPassword($this->task->getRedpass());

        $tmpl['deleted'] = 0;
        $tmpl['disable'] = 0;
        $groups = GeneralUtility::trimExplode(',', $tmpl['usergroup'], true);
        array_unshift($groups, $this->group['uid']);
        foreach ($groups as $k => $gid) {
            if ($gid == $this->tmplGroup) {
                unset($groups[$k]);
            }
        }
        $tmpl['usergroup'] = implode(',', $groups);
        $event = new CreateBackendUserEvent($tmpl, $this);
        $this->eventDispatcher->dispatch($event);
        $tmpl = $event->getRecord();
        $this->source->ping();

        [$rows, $newUid] = self::insertRecord('be_users', $tmpl);

        if (!$rows) {
            throw new \Exception('could not create user', 1700484162037);
        }
        $tmpl['uid'] = $newUid;
        $this->user = $tmpl;
    }

    private function buildTree(int $start): void
    {
        $tree = $this->source->getTree($start);
        foreach ($tree as $uid) {
            if (!isset($this->pageMap[$uid])) {
                $this->pageMap[$uid] = 0;
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function cloneTree(): void
    {
        $this->log('Clone Tree Start');
        $sourcePid = (int)$this->source->sourcePid();
        foreach (array_keys($this->pageMap) as $old) {
            $page = $this->source->getRow('pages', ['uid' => $old]);

            $this->log('Cloning Page ' . $page['title']);
            unset($page['uid']);

            $page['t3_origuid'] = $old;

            $page = $this->staticValueReplacement('pages', $page);

            if (!$this->isAdmin($page['perms_userid'])) {
                $page['perms_userid'] = $this->user['uid'];
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

            if (isset($this->pageMap[$page['pid']]) && $this->pageMap[$page['pid']] > 0) {
                $page['pid'] = (int)$this->pageMap[$page['pid']];
            }

            $event = new BeforeClonedTreeInsertEvent($old, $page, $this);
            $this->eventDispatcher->dispatch($event);
            $page = $event->getRecord();

            $this->source->ping();

            [$rowsAffected, $newPageId] = self::insertRecord('pages', $page);
            if (!$rowsAffected) {
                throw new \Exception('Create page failed', 1700484228392);
            }
            $this->pageMap[$old] = $newPageId;
            $this->addContentMap('pages', $old, $newPageId);
            $this->addCleanupInline('pages', $newPageId);

            if ($page['is_siteroot']) {
                $this->createDomain($this->pageMap[$old]);
                $this->siteRootId = $this->pageMap[$old];
            }
            $this->eventDispatcher->dispatch(new AfterClonedTreeInsertEvent($old, $page, $this));
        }
        $this->log('Clone Tree End');
    }

    public function staticValueReplacement(string $table, array $row): array
    {
        if (!empty($this->getTask()->getValuemapping())) {
            $config = $this->getTask()->getValuemappingArray();
            if (isset($config[$table])) {
                foreach ($config[$table] as $field => $map) {
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

    public function getTask(): Creator
    {
        return $this->task;
    }

    public function setTask(Creator $task): void
    {
        $this->task = $task;
    }

    private function isAdmin(int $uid): bool
    {
        if (!isset($this->checkUsers[$uid])) {
            $this->source->ping();
            $this->checkUsers[$uid] = BackendUtility::getRecord('be_users', $uid);
        }
        if (is_array($this->checkUsers[$uid])) {
            return (bool)$this->checkUsers[$uid]['admin'];
        }

        return false;
    }

    /**
     * @param $table
     * @param $old
     * @param $new
     * @internal
     */
    public function addContentMap($table, $old, $new): void
    {
        if (!isset($this->contentMap[$table])) {
            $this->contentMap[$table] = [];
        }

        $this->contentMap[$table][$old] = $new;
    }

    private function createDomain($pid): void
    {
        $this->siteConfig['rootPageId'] = $pid;
        // this is the case if the hostname has a port added, then http:// will be chosen
        $proto = str_contains($this->task->getDomainname(), ':') ? 'http://' : 'https://';
        $this->siteConfig['base'] = $proto . $this->task->getDomainname() . '/';
    }

    /**
     * @throws \Exception
     */
    private function cloneContent(): void
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

        foreach ($GLOBALS['TCA'] as $TCATable => $tca) {
            if (isset($tca['ctrl']['rootLevel']) && (int)$tca['ctrl']['rootLevel'] === 1 && !in_array($TCATable, $aSkip)) {
                $aSkip[] = $TCATable;
            }
        }

        $event = new ModifyCloneContentSkipTableEvent($aSkip, $this);
        $this->eventDispatcher->dispatch($event);
        $aSkip = $event->getSkipList();

        $aSkip = array_merge($aSkip, $this->alwaysIgnoreTables);
        foreach ($runTables as $table) {
            $config = $GLOBALS['TCA'][$table];
            if (!in_array($table, $aSkip)) {
                $filteredPids = $this->getSource()->filterByPid($table, array_keys($this->pageMap));

                foreach ($filteredPids as $oldpid) {
                    $newpid = $this->pageMap[$oldpid];
                    $where = self::myEnableFields($table);
                    $where['pid'] = $oldpid;
                    $rows = $this->source->getRows($table, $where);
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
                            'table' => $table,
                            'olduid' => $olduid,
                            'oldpid' => $oldpid,
                            'newpid' => $newpid,
                            'pObj' => $this,
                        ]);

                        $this->source->ping();
                        if ($row) {
                            [$rowsAffected, $newuid] = self::insertRecord($table, $row);

                            if (!$rowsAffected) {
                                throw new \Exception(sprintf(
                                    'cannot insert into %s payload %s',
                                    $table,
                                    json_encode($row)
                                ), 1700484357787);
                            }

                            $this->log('Insert ' . $table . ' olduid ' . $olduid . ' oldpid ' . $oldpid . ' newuid ' . $newuid . ' newpid ' . $newpid);

                            $this->addContentMap($table, $olduid, $newuid);

                            $this->addCleanupInline($table, $newuid);
                            $row = $this->runTCA('post', $config['columns'], $row, [
                                'table' => $table,
                                'olduid' => $olduid,
                                'newuid' => $newuid,
                                'oldpid' => $oldpid,
                                'newpid' => $newpid,
                                'pObj' => $this,
                            ]);

                            $this->eventDispatcher->dispatch(new AfterContentCloneEvent($table, $olduid, $oldpid, $newuid, $row, $this));
                        } else {
                            $this->log('ERROR NO ROW ' . print_r([
                                    $table,
                                    [
                                        'table' => $table,
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

    private static function myEnableFields($table): array
    {
        //BackendUtility::BEenableFields($table)
        return [];
    }

    /**
     * @param array<array-key, mixed> $config
     * @param array<array-key, mixed> $row
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     * @throws \Exception
     */
    private function runTCA(
        string $state,
        array $config,
        array $row,
        array $parameters
    ): array {
        foreach ($config as $column => $columnConfig) {
            if (!$this->isTCAFieldActiveForThisRecord($parameters['table'], $column, $row)) {
                continue;
            }
            $columnConfig = $this->applyTCAFieldOverrideIfNecessary($parameters['table'], $column, $columnConfig, $row);
            //$this->out('runTCA '.$state.' '.$parameters['table'].' '.$column);
            $columnType = strtolower($columnConfig['config']['type']);
            switch ($state) {
                case 'pre':
                    $event = new Column\BeforeEvent($parameters['table'], $column, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $event = new ColumnType\BeforeEvent($parameters['table'], $column, $columnType, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();
                    break;
                case 'post':
                    $event = new Column\AfterEvent($parameters['table'], $column, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $event = new ColumnType\AfterEvent($parameters['table'], $column, $columnType, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();
                    break;
                case 'final':

                    $event = new Column\FinalEvent($parameters['table'], $column, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = match ($columnType) {
                        'group' => $this->cloneContent_final_columntype_group($column, $columnConfig, $row, $parameters),
                        'select' => $this->cloneContent_final_columntype_select($column, $columnConfig, $row, $parameters),
                        // no break
                        default => $row
                    };

                    $event = new ColumnType\FinalEvent($parameters['table'], $column, $columnType, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    if (isset($columnconfig['config']['renderType']) && $columnconfig['config']['renderType'] === 'inputLink') {
                        $row = $this->cloneContent_final_wizards_link($column, $columnconfig, $row, $parameters);
                    } elseif (isset($columnconfig['config']['softref']) && $columnconfig['config']['softref'] === 'typolink') {
                        $row = $this->cloneContent_final_wizards_link($column, $columnconfig, $row, $parameters);
                    }

                    if (isset($columnConfig['config']['wizards'])) {
                        foreach ($columnConfig['config']['wizards'] as $wizard => $wizardConfig) {
                            $row = $this->cloneContent_final_wizards_link($wizard, $wizardConfig, $row, $parameters);
                        }
                    }

                    break;
                case 'clean':
                    $event = new Column\CleanEvent($parameters['table'], $column, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = match ($columnType) {
                        'inline' => $this->cloneContent_clean_columntype_inline($column, $columnConfig, $row, $parameters),
                        // no break
                        default => $row
                    };

                    $event = new ColumnType\CleanEvent($parameters['table'], $column, $columnType, $columnConfig, $row, $parameters, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();
                    break;
            }
        }
        return $row;
    }

    /**
     * @param array<array-key, mixed> $record
     */
    public function isTCAFieldActiveForThisRecord(
        string $table,
        string $column,
        array $record
    ): bool {
        if (!isset($GLOBALS['TCA'][$table])) {
            return false;
        }
        $tca = $GLOBALS['TCA'][$table];
        $TCAType = $tca['ctrl']['type'] ?? 'type';
        $tcaTypeValue = $record[$TCAType] ?? 0;
        if (isset($tca['types'][$tcaTypeValue]) &&  isset($tca['types'][$tcaTypeValue]['showitem'])) {
            $showitem = $tca['types'][$tcaTypeValue]['showitem'];
        } elseif (isset($tca['types'][0]) && isset($tca['types'][0]['showitem'])) { //fallback 0
            $tcaTypeValue = 0;
            $showitem = $tca['types'][$tcaTypeValue]['showitem'];
        } elseif (isset($tca['types'][1]) && isset($tca['types'][1]['showitem'])) { //fallback 1
            $tcaTypeValue = 1;
            $showitem = $tca['types'][$tcaTypeValue]['showitem'];
        } else {
            return false;
        }

        $fields = GeneralUtility::trimExplode(',', $showitem, true);
        foreach ($fields as $field) {
            if (\str_starts_with($field, '--div--')) {
                continue;
            }
            if (\str_starts_with($field, '--linebreak--')) {
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

        $event = new TCAFieldActiveForThisRecordEvent($table, $column, $record, $this);
        $this->eventDispatcher->dispatch($event);

        return $event->isAllowed();
    }

    public function applyTCAFieldOverrideIfNecessary(
        string $table,
        string $column,
        array $columnConfig,
        array $record
    ): array {
        $tca = $GLOBALS['TCA'][$table];
        $TCAType = $tca['ctrl']['type'] ?? 'type';
        $tcaTypeValue = $record[$TCAType] ?? 0;
        if (isset($tca['types'][$tcaTypeValue]) && isset($tca['types'][$tcaTypeValue]['columnsOverrides']) && isset($tca['types'][$tcaTypeValue]['columnsOverrides'][$column]) && isset($tca['types'][$tcaTypeValue]['columnsOverrides'][$column]['config'])) {
            $columnConfig['config'] = \array_merge($columnConfig['config'], $tca['types'][$tcaTypeValue]['columnsOverrides'][$column]['config']);
        }
        return $columnConfig;
    }

    /**
     * @param array<array-key, mixed> $columnConfig
     * @param array<array-key, mixed> $row
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     */
    private function cloneContent_final_columntype_group(
        string $column,
        array $columnConfig,
        array $row,
        array $parameters
    ): array {
        if (isset($columnConfig['config']['internal_type']) && $columnConfig['config']['internal_type'] == 'db') {
            $this->log('Clean Group Column ' . $column);
            $table = $parameters['table'];
            $oldUid = $row['t3_origuid'] ?? $this->getTranslateUidReverse($table, $row['uid']);

            $newUid = $row['uid'];

            if (isset($columnConfig['config']['MM'])) {
                if (isset($columnConfig['config']['foreign_table'])) {
                    $tables = [$columnConfig['config']['foreign_table']];
                } elseif ($columnConfig['config']['allowed'] == '*') {
                    $tables = array_keys($GLOBALS['TCA']);
                } else {
                    $tables = GeneralUtility::trimExplode(',', $columnConfig['config']['allowed'], true);
                }

                foreach ($tables as $tbl) {
                    $this->fixMMRelation($tbl, $columnConfig['config']['MM'], $oldUid, $newUid);
                }
            } else {
                $val = $row[$column];
                $list = GeneralUtility::trimExplode(',', $val, true);
                $newList = [];
                foreach ($list as $tmpOldUid) {
                    $tmp = GeneralUtility::trimExplode('_', $tmpOldUid, true);
                    if ((is_countable($tmp) ? count($tmp) : 0) > 1) {
                        $refTable = $tmp[0];
                        $oldUid = $tmp[1];
                    } else {
                        $refTable = $columnConfig['config']['allowed'];
                        $oldUid = $tmp[0];
                    }
                    $newList[] = (is_countable($tmp) ? count($tmp) : 0) > 1 ? $refTable . '_' . $this->getTranslateUid($refTable, $oldUid) : $this->getTranslateUid($refTable, $oldUid);
                }
                if ($newList !== []) {
                    $row[$column] = implode(',', $newList);
                }
            }
        }
        return $row;
    }

    public function getTranslateUidReverse(string $table, int $uid): bool|int|string
    {
        $newUid = $uid;
        if ($table == 'pages') {
            if (in_array($uid, $this->pageMap)) {
                $newUid = array_search($uid, $this->pageMap);
            }
        } elseif (isset($this->contentMap[$table]) && in_array($uid, $this->contentMap[$table])) {
            $newUid = array_search($uid, $this->contentMap[$table]);
        }

        $event = new TranslateUidReverseEvent($table, (int)$uid, (int)$newUid);
        $this->eventDispatcher->dispatch($event);
        return $event->getFoundUid();
    }

    public function fixMMRelation(
        string $table,
        string $mmTable,
        int $oldUid,
        int $newUid
    ): void {
        $mm = $this->source->getMM($mmTable, $oldUid, $table);
        foreach ($mm as $row) {
            if (isset($row['uid'])) {
                unset($row['uid']);
            }
            $newForeign = $this->getTranslateUid($table, $row['uid_foreign']);
            $row['uid_local'] = $newUid;
            $row['uid_foreign'] = $newForeign;
            $this->source->ping();
            self::insertRecord($mmTable, $row);
        }
    }

    public function getTranslateUid(string $table, string|int $uid): int|string
    {
        $tablePrefix = false;
        if (\str_contains((string)$uid, '_')) {
            $tablePrefix = true;
            $x = explode('_', (string)$uid);
            $uid = array_pop($x);
            $table = implode('_', $x);
        }
        $newuid = $uid;
        if ($table == 'pages') {
            if (isset($this->pageMap[(int)$uid])) {
                $newuid = (int)$this->pageMap[(int)$uid] > 0 ? (int)$this->pageMap[(int)$uid] : (int)$uid;
            }
        } elseif (isset($this->contentMap[$table][(int)$uid])) {
            $newuid = (int)$this->contentMap[$table][(int)$uid] > 0 ? (int)$this->contentMap[$table][(int)$uid] : (int)$uid;
        }

        $event = new TranslateUidEvent($table, (int)$uid, (int)$newuid);
        $this->eventDispatcher->dispatch($event);
        $uid = $event->getFoundUid();
        //return (int)$uid;
        return $tablePrefix ? $table . '_' . (string)$uid : $uid;
    }

    /**
     * @param array<array-key, mixed> $columnConfig
     * @param array<array-key, mixed> $row
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     */
    private function cloneContent_final_columntype_select(
        string $column,
        array $columnConfig,
        array $row,
        array $parameters
    ): array {
        $skipTables = [];
        if (isset($columnConfig['config']['foreign_table']) && !in_array($columnConfig['config']['foreign_table'], $skipTables)) {
            $this->log('Clean select Column ' . $column);

            $table = $parameters['table'];

            $oldUid = $row['t3_origuid'] ?? $this->getTranslateUidReverse($table, $row['uid']);

            $newUid = $row['uid'];

            if (isset($columnConfig['config']['MM'])) {
                $this->fixMMRelation($columnConfig['config']['foreign_table'], $columnConfig['config']['MM'], $oldUid, $newUid);
            } else {
                $val = $row[$column];
                $list = GeneralUtility::trimExplode(',', $val, true);
                $newList = [];
                foreach ($list as $tmpOldUid) {
                    $tmp = GeneralUtility::trimExplode('_', $tmpOldUid, true);
                    if ((is_countable($tmp) ? count($tmp) : 0) > 1) {
                        $refTable = $tmp[0];
                        $oldUid = $tmp[1];
                    } else {
                        $refTable = $columnConfig['config']['foreign_table'];
                        $oldUid = $tmp[0];
                    }

                    $newList[] = (is_countable($tmp) ? count($tmp) : 0) > 1 ? $refTable . '_' . $this->getTranslateUid($refTable, $oldUid) : $this->getTranslateUid($refTable, $oldUid);
                }
                if ($newList !== []) {
                    $row[$column] = implode(',', $newList);
                }
            }
        }
        return $row;
    }

    /**
     * @param array<array-key, mixed> $columnConfig
     * @param array<array-key, mixed> $row
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     */
    private function cloneContent_final_wizards_link(
        string $column,
        array $columnConfig,
        array $row,
        array $parameters
    ): array {
        if (!empty($row[$column])) {
            $row[$column] = $this->translateTypolinkString($row[$column]);
        }
        return $row;
    }

    public function translateTypolinkString(string $s): string
    {
        $s = trim($s);
        $a = str_getcsv($s, ' ', 'dasdhasdsalkdjsalk13');
        $id = $a[0];
        if ($id === null) {
            return $s;
        }
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

    /**
     * <p>You can insert <a class="link-page" href="65">internal links</a> (links to pages within the website), <a class="link-external" href="http://typo3.org">external links</a> (links to external sites) or <a class="link-mail" href="test@test.net">e-mail links</a> (links that open the user's email client when clicked).</p>
     * <p>Additional link stylings:</p>
     * <ul>    <li><a class="link-arrow" href="65">Arrow</a></li>    <li><a class="link-page" href="65">Page</a></li>    <li><a class="link-file" href="file:1">File</a></li>    <li><a class="link-folder" href="t3://folder?storage=1&amp;identifier=%2Fintroduction%2Fimages%2F">Folder</a></li>    <li><a class="link-mail" href="john.doe@example.com">E-Mail&nbsp;</a></li> </ul>
     */
    public function translateT3LinkString(string $s): string
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
                    default => (int)$queryParts['uid']
                };
            }
            foreach ($queryParts as $k => $v) {
                if (\str_starts_with($k, 'amp;')) {
                    $k2 = substr($k, 4);
                    unset($queryParts[$k]);
                    $queryParts[$k2] = $v;
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
            $x = 1;
        } elseif (isset($urlParts['host']) && $urlParts['host'] === 'file') {
            $s = $urlParts['host'] . ':' . $this->getTranslateUid('sys_file', (int)$urlParts['port']);
        } elseif (isset($urlParts['host']) && $urlParts['host'] === 'page') {
            $s = $urlParts['host'] . ':' . $this->getTranslateUid('pages', (int)$urlParts['port']);
        }
        return $s;
    }

    /**
     * @param array<array-key, mixed> $columnConfig
     * @param array<array-key, mixed> $row
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     * @throws \Exception
     */
    private function cloneContent_clean_columntype_inline(
        string $column,
        array $columnConfig,
        array $row,
        array $parameters
    ): array {
        $this->log('Clean inline Column ' . $column);
        $table = $parameters['table'];
        $oldUid = $row['t3_origuid'] ?? $this->getTranslateUidReverse($table, $row['uid']);
        if ($oldUid) {
            $newUid = $row['uid'];
            $newPid = $row['pid'];

            $oldRow = $this->source->getRow($table, ['uid' => $oldUid]);
            $oldPid = 0;
            if (isset($oldRow['pid'])) {
                $oldPid = $oldRow['pid'];
            }

            $pidList = array_keys($this->pageMap);
            $inlines = $this->source->getIrre($table, $oldUid, $oldPid, $oldRow, $columnConfig, $pidList, $column);

            // this is for the case we don't have a foreign_field, which means the list is stored in a varchar field in the db
            $csvInlineNewIds = [];

            foreach ($inlines as $inline) {
                $inlineUid = $inline['uid'];
                $test = null;

                if (isset($this->contentMap[$columnConfig['config']['foreign_table']][$inlineUid])) {
                    $this->source->ping();

                    $test = BackendUtility::getRecord(
                        $columnConfig['config']['foreign_table'],
                        $this->contentMap[$columnConfig['config']['foreign_table']][$inlineUid]
                    );
                }

                if ($test) {
                    $orig = $test;

                    $event = new CleanContentEvent($columnConfig['config']['foreign_table'], $test, $this);
                    $this->eventDispatcher->dispatch($event);
                    $test = $event->getRecord();

                    $test = $this->runTCA(
                        'clean',
                        $GLOBALS['TCA'][$columnConfig['config']['foreign_table']]['columns'],
                        $test,
                        [
                            'table' => $columnConfig['config']['foreign_table'],
                            'pObj' => $parameters['pObj'],
                        ]
                    );

                    $update = [];
                    foreach ($test as $k => $v) {
                        if ($orig[$k] != $v) {
                            $update[$k] = $v;
                        }
                    }

                    if (isset($columnConfig['config']['foreign_field']) && !empty($columnConfig['config']['foreign_field'])) {
                        $update[$columnConfig['config']['foreign_field']] = $newUid;
                    } else {
                        $csvInlineNewIds[] = $test['uid'];
                    }
                    unset($update['uid']);
                    unset($update['pid']);

                    if ($update !== []) {
                        $this->source->ping();

                        self::updateRecord($columnConfig['config']['foreign_table'], $update, ['uid' => $orig['uid']]);
                    }
                } else {
                    // this seems an inline element we have not discovered yet
                    // we will create it now and add it to the clean-up stack again

                    if (self::tableHasField($columnConfig['config']['foreign_table'], 't3_origuid')) {
                        $inline['t3_origuid'] = $inlineUid;
                    }

                    unset($inline['uid']);
                    $inline['pid'] = $newPid;

                    $inline[$columnConfig['config']['foreign_field']] = $newUid;

                    $event = new BeforeContentCloneEvent($columnConfig['config']['foreign_table'], $inlineUid, $oldPid, $inline, $this);
                    $this->eventDispatcher->dispatch($event);
                    $inline = $event->getRecord();

                    $inline = $this->runTCA(
                        'pre',
                        $GLOBALS['TCA'][$columnConfig['config']['foreign_table']]['columns'],
                        $inline,
                        [
                            'table' => $columnConfig['config']['foreign_table'],
                            'oldUid' => $inlineUid,
                            'oldPid' => $oldPid,
                            'newPid' => $row['pid'],
                            'pObj' => $parameters['pObj'],
                        ]
                    );

                    if ($inline) {
                        $this->source->ping();

                        [$rowAffected, $newInlineUid] = self::insertRecord($columnConfig['config']['foreign_table'], $inline);

                        if (!$rowAffected) {
                            throw new \Exception(sprintf('error insert to %s with %s', $columnConfig['config']['foreign_table'], json_encode($inline)), 1_616_700_010);
                        }

                        $this->addContentMap($columnConfig['config']['foreign_table'], $inlineUid, $newInlineUid);
                        $this->addCleanupInline($columnConfig['config']['foreign_table'], $newInlineUid);

                        $this->runTCA(
                            'post',
                            $GLOBALS['TCA'][$columnConfig['config']['foreign_table']]['columns'],
                            $inline,
                            [
                                'table' => $columnConfig['config']['foreign_table'],
                                'oldUid' => $inlineUid,
                                'newUid' => $newInlineUid,
                                'oldPid' => $oldPid,
                                'newPid' => $newPid,
                                'pObj' => $parameters['pObj'],
                            ]
                        );

                        $this->eventDispatcher->dispatch(new AfterContentCloneEvent($columnConfig['config']['foreign_table'], $inlineUid, $oldPid, $newInlineUid, $inline, $this));
                    }
                }
            }

            if (!isset($columnConfig['config']['foreign_field']) && !empty($csvInlineNewIds)) {
                $translated = $this->translateIDlist($columnConfig['config']['foreign_table'], $row[$column]);
                self::updateRecord($table, [ $column=>$translated ], ['uid'=>$newUid]);
                $row[$column] = $translated;
            }
        } else {
            $this->log('No t3_origuid in Table ' . $table . ' - skipped');
        }
        return $row;
    }

    /**
     * @interal
     */
    public function addCleanupInline(string $table, int $uid): void
    {
        if (!isset($this->cleanUpTodo[$table])) {
            $this->cleanUpTodo[$table] = [];
        }
        $this->cleanUpTodo[$table][] = $uid;
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
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

        $aSkip = array_merge($event->getSkipList(), $this->alwaysIgnoreTables);
        $map = $this->cleanUpTodo;
        //print_r([ 'cleanupTodo' => $this->cleanUpTodo ]);
        $this->cleanUpTodo = [];

        foreach ($map as $table => $newUid) {
            $config = $GLOBALS['TCA'][$table];

            if (!in_array($table, $aSkip)) {
                $this->source->ping();

                $query = self::getQueryBuilderWithoutRestriction($table);
                $stmt = $query->select('*')
                    ->from($table)
                    ->where(
                        $query->expr()->in('uid', $newUid)
                    )
                    ->execute();

                while ($originalRow = $stmt->fetchAssociative()) {
                    // fetch a clean version, might have changed in between
                    $row = BackendUtility::getRecord($table, $originalRow['uid']);
                    if (!$row) {
                        continue;
                    }
                    $this->log('Content Cleanup ' . $table . ' ' . $row['uid']);
                    $event = new Inlines\CleanEvent($table, $row, $this);
                    $this->eventDispatcher->dispatch($event);
                    $row = $event->getRecord();

                    $row = $this->runTCA('clean', $config['columns'], $row, [
                        'table' => $table,
                        'pObj' => $this,
                    ]);

                    $update = [];
                    foreach ($row as $k => $v) {
                        if ($originalRow[$k] != $v) {
                            $update[$k] = $v;
                        }
                    }
                    unset($update['uid']);
                    unset($update['pid']);

                    if ($update !== []) {
                        $this->source->ping();

                        self::updateRecord($table, $update, ['uid' => $originalRow['uid']]);
                    }
                }
            }
        }
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    private function cleanPages(): void
    {
        $this->log('Start Pages Cleanup ');
        $table = 'pages';
        $config = $GLOBALS['TCA']['pages'];

        foreach ($this->pageMap as $oldPid => $newPid) {
            $this->source->ping();

            $query = self::getQueryBuilderWithoutRestriction($table);
            $res = $query->select('*')
                ->from($table)
                ->where(
                    $query->expr()->eq('uid', $newPid)
                )->execute();

            while ($originalRow = $res->fetchAssociative()) {
                // fetch a clean version, might have changed in between
                $row = BackendUtility::getRecord($table, $originalRow['uid']);
                if (!$row) {
                    continue;
                }
                $this->log('Page Cleanup ' . $row['title'] . ' ' . $row['uid']);

                $row = $this->finalContent_pages($row, $this);

                $event = new FinalContentEvent($table, $row, $this);
                $this->eventDispatcher->dispatch($event);
                $row = $event->getRecord();

                $row = $this->runTCA('final', $config['columns'], $row, [
                    'table' => $table,
                    'pObj' => $this,
                ]);

                $update = [];
                foreach ($row as $k => $v) {
                    if ($originalRow[$k] != $v) {
                        $update[$k] = $v;
                    }
                }
                unset($update['uid']);
                unset($update['pid']);
                if ($update !== []) {
                    $this->source->ping();
                    $this->log(__FILE__ . ':' . __LINE__ . ' ' . $table . ' update ' . print_r($update, true));

                    self::updateRecord($table, $update, ['uid' => $originalRow['uid']]);
                }
            }
        }
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    public function finalContent_pages(array $row, CreateProcess $pObj): array
    {
        if ($row['doktype'] == 4 && !empty($row['shortcut'])) {
            $row['shortcut'] = $pObj->getTranslateUid('pages', $row['shortcut']);
        }

        return $row;
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
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

        $aSkip = array_merge($event->getSkipList(), $this->alwaysIgnoreTables);
        $newPids = \array_values($this->pageMap);
        foreach ($GLOBALS['TCA'] as $table => $config) {
            if (!in_array($table, $aSkip)) {
                $this->source->ping();
                $this->log('Content Cleanup ' . $table);

                $query = self::getQueryBuilderWithoutRestriction($table);

                $stmt = $query->select('*')
                    ->from($table)
                    ->where(
                        $query->expr()->in('pid', $newPids)
                    )
                    ->execute();

                while ($originalRow = $stmt->fetchAssociative()) {
                    // fetch a clean version, might have changed in between
                    $row = BackendUtility::getRecord($table, $originalRow['uid']);
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
                        'pObj' => $this,
                    ]);

                    $update = [];
                    foreach ($row as $k => $v) {
                        if ($originalRow[$k] != $v) {
                            $update[$k] = $v;
                        }
                    }
                    unset($update['uid']);
                    unset($update['pid']);
                    if ($update !== []) {
                        $this->source->ping();

                        self::updateRecord($table, $update, ['uid' => $originalRow['uid']]);
                    }
                }
            }
        }
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array<array-key, mixed>
     */
    public function finalContent_tt_content(array $row): array
    {
        $event = new FinalContentByCtypeEvent($row['CType'], $row['CType'] === 'list' ? $row['list_type'] : null, $row, $this);
        $this->eventDispatcher->dispatch($event);
        return $event->getRecord();
    }

    public function pageSort(): void
    {
        $old = $this->source->sourcePid();
        $new = $this->pageMap[$old];
        $this->eventDispatcher->dispatch(new PageSortEvent($old, BackendUtility::getRecord('pages', $new), $this));
    }

    private function finalGroup(): void
    {
        $list = $this->translateIDlist('pages', $this->group['db_mountpoints']);
        if ($list != 0) {
            $this->group['db_mountpoints'] = $list;
            $this->source->ping();
            self::updateRecord('be_groups', ['db_mountpoints' => $list], ['uid' => $this->group['uid']]);
        }
    }

    public function translateIDlist(string $table, string $list): string
    {
        $ids = GeneralUtility::trimExplode(',', $list);
        if ($ids === []) {
            return $list;
        }
        $newList = [];
        foreach ($ids as $id) {
            $newList[] = $this->getTranslateUid($table, $id);
        }

        return implode(',', $newList);
    }

    private function finalUser(): void
    {
        $list = $this->translateIDlist('pages', (string)$this->user['db_mountpoints']);
        if ($list == $this->user['db_mountpoints']) {
            $list = $this->siteRootId;
        } else {
            $aList = GeneralUtility::trimExplode(',', $list, true);
            $aList[] = $this->siteRootId;
            $list = implode(',', $aList);
        }
        $this->user['db_mountpoints'] = $list;
        $this->source->ping();
        self::updateRecord('be_users', ['db_mountpoints' => $list], ['uid' => $this->user['uid']]);
    }

    /**
     * @throws SiteConfigurationWriteException
     */
    private function finalYaml(): void
    {
        $path = Environment::getProjectPath();
        try {
            GeneralUtility::mkdir_deep($path . '/config/sites');
        } catch (\Exception $e) {
        }

        $event = new GenerateSiteIdentifierEvent($this->siteConfig, $path, $this);
        $this->eventDispatcher->dispatch($event);
        $identifier = $event->getIdentifier();

        $this->siteConfig['websiteTitle'] = $this->getTask()->getProjektname();

        if (empty($identifier)) {
            $identifier = Tools::generateSlug($this->getTask()->getShortname() ?? $this->getTask()->getProjektname());

            if (is_dir($path . '/config/sites/' . $identifier)) {
                $identifier = Tools::generateSlug($this->getTask()->getLongname() ?? $this->getTask()->getDomainname());
            }
        }

        if (isset($this->siteConfig['errorHandling'])) {
            foreach ($this->siteConfig['errorHandling'] as $idx => $config) {
                if (isset($config['errorContentSource']) && \str_starts_with($config['errorContentSource'], 't3://')) {
                    $this->siteConfig['errorHandling'][$idx]['errorContentSource'] = $this->translateT3LinkString($config['errorContentSource']);
                }
            }
        }

        $event = new BeforeSiteConfigWriteEvent($this->siteConfig, $this);
        $this->eventDispatcher->dispatch($event);
        $this->siteConfig = $event->getSiteconfig();

        GeneralUtility::makeInstance(SiteConfiguration::class)->write($identifier, $this->siteConfig);
    }

    /**
     * @return array
     */
    public function getSiteConfig(): array
    {
        return $this->siteConfig;
    }

    /**
     * @param array $siteConfig
     */
    public function setSiteConfig(array $siteConfig): void
    {
        $this->siteConfig = $siteConfig;
    }

    /**
     * @return array
     */
    public function getAlwaysIgnoreTables(): array
    {
        return $this->alwaysIgnoreTables;
    }

    /**
     * @param array $alwaysIgnoreTables
     */
    public function setAlwaysIgnoreTables(array $alwaysIgnoreTables): void
    {
        $this->alwaysIgnoreTables = $alwaysIgnoreTables;
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
     * @return array
     */
    public function getGroup(): array
    {
        return $this->group;
    }

    /**
     * @param array<array-key, mixed> $group
     */
    public function setGroup(array $group): void
    {
        $this->group = $group;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getUser(): array
    {
        return $this->user;
    }

    /**
     * @param array<array-key, mixed> $user
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getFilemount(): array
    {
        return $this->filemount;
    }

    /**
     * @param array<array-key, mixed> $filemount
     */
    public function setFilemount(array $filemount): void
    {
        $this->filemount = $filemount;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getContentMap(): array
    {
        return $this->contentMap;
    }

    /**
     * @param array<array-key, mixed> $contentMap
     */
    public function setContentMap(array $contentMap): void
    {
        $this->contentMap = $contentMap;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getCleanUpTodo(): array
    {
        return $this->cleanUpTodo;
    }

    /**
     * @param array<array-key, mixed> $cleanUpTodo
     */
    public function setCleanUpTodo(array $cleanUpTodo): void
    {
        $this->cleanUpTodo = $cleanUpTodo;
    }

    /**
     * @return string
     */
    public function getDebugSection(): string
    {
        return $this->debugSection;
    }

    /**
     * @param string $debugSection
     */
    public function setDebugSection(string $debugSection): void
    {
        $this->debugSection = $debugSection;
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

    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    public function setTemplateKey(?string $templateKey): void
    {
        $this->templateKey = $templateKey;
    }

    /**
     * @return int
     */
    public function getTmplGroup(): int
    {
        return $this->tmplGroup;
    }

    /**
     * @param int $tmplGroup
     */
    public function setTmplGroup(int $tmplGroup): void
    {
        $this->tmplGroup = $tmplGroup;
    }

    /**
     * @return int
     */
    public function getTmplUser(): int
    {
        return $this->tmplUser;
    }

    /**
     * @param int $tmplUser
     */
    public function setTmplUser(int $tmplUser): void
    {
        $this->tmplUser = $tmplUser;
    }

    /**
     * @return int
     */
    public function getSiteRootId(): int
    {
        return $this->siteRootId;
    }

    /**
     * @param int $siteRootId
     */
    public function setSiteRootId(int $siteRootId): void
    {
        $this->siteRootId = $siteRootId;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    private function addToFormConfig(string $path): void
    {
        $config = Yaml::parseFile(Environment::getPublicPath() . '/fileadmin/bk_form_config.yaml');

        if (!\array_search('1:' . $path, $config['TYPO3']['CMS']['Form']['persistenceManager']['allowedFileMounts'], true)) {
            $keys = \array_keys($config['TYPO3']['CMS']['Form']['persistenceManager']['allowedFileMounts']);
            $lastkey = array_pop($keys);
            $config['TYPO3']['CMS']['Form']['persistenceManager']['allowedFileMounts'][$lastkey + 10] = '1:' . $path;
        }
        \file_put_contents(Environment::getPublicPath() . '/fileadmin/bk_form_config.yaml', Yaml::dump($config, 99, 2));
    }
}
