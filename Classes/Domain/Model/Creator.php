<?php

declare(strict_types=1);

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

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Model;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use function str_starts_with;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Model Creator
 */
class Creator implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    public static array $statusList = [
        0 => 'editing',
        5 => 'Not ready',
        10 => 'ready',
        15 => 'processing',
        17 => 'failed',
        20 => 'done',
    ];

    protected function __construct(
        protected int $uid,
        protected int $pid,
        protected int $cruserId, // @todo cruser is removed by default in v12, if needed, handle in other ways
        protected string $sourcepid,
        protected string $base,
        protected ?string $projektname,
        protected ?string $longname,
        protected ?string $shortname,
        protected ?string $domainname,
        protected ?string $contact,
        protected ?string $reduser,
        protected ?string $redemail,
        protected ?string $redpass,
        protected int $status,
        protected ?string $flexinfo,
        protected ?string $email,
        protected ?string $valuemapping,
        protected int $sourceuser,
        protected int $sourcefilemount,
        protected string $sourceclass,
        private array $valuemappingcache = []
    ) {
    }

    /**
     * @param array{
     *     uid: int,
     *     cruser_id: int,
     *     sourcepid: string,
     * base: string,
     * projektname: string,
     * longname: string,
     * shortname: string,
     * domainname: string,
     * contact: string,
     * reduser: string,
     * redemail: string,
     * redpass: string,
     *  status: string,
     *  flexinfo: string,
     * email: string,
     * valuemapping: string,
     * sourceuser: int,
     * sourcefilemount: int,
     * sourceclass: string
     * } $row
     */
    public static function createFromDatabaseRow(array $row): Creator
    {
        return new self(
            $row['uid'],
            $row['pid'],
            $row['cruser_id'],
            $row['sourcepid'],
            $row['base'],
            $row['projektname'],
            $row['longname'],
            $row['shortname'],
            $row['domainname'],
            $row['contact'],
            $row['reduser'],
            $row['redemail'],
            $row['redpass'],
            $row['status'],
            $row['flexinfo'],
            $row['email'] ?? '',
            $row['valuemapping'],
            (int)$row['sourceuser'],
            (int)$row['sourcefilemount'],
            $row['sourceclass']
        );
    }

    public function getSourcepid(): int
    {
        if (str_starts_with((string)$this->sourcepid, 't3://')) {
            return (int)GeneralUtility::trimExplode('=', $this->sourcepid)[1];
        }
        return (int)$this->sourcepid;
    }

    /**
     * Base
     *
     * @return string|null base
     */
    public function getBase(): ?string
    {
        return $this->base;
    }

    /**
     * Projektname
     *
     * @return string|null projektname
     */
    public function getProjektname(): ?string
    {
        return $this->projektname;
    }

    /**
     * Longname
     *
     * @return string|null longname
     */
    public function getLongname(): ?string
    {
        return $this->longname;
    }

    /**
     * Shortname
     *
     * @return string|null shortname
     */
    public function getShortname(): ?string
    {
        return $this->shortname;
    }

    /**
     * Domainname
     *
     * @return string|null domainname
     */
    public function getDomainname(): ?string
    {
        return $this->domainname;
    }

    /**
     * Contact
     *
     * @return string|null contact
     */
    public function getContact(): ?string
    {
        return $this->contact;
    }

    /**
     * Reduser
     *
     * @return string|null reduser
     */
    public function getReduser(): ?string
    {
        return $this->reduser;
    }

    /**
     * Redemail
     *
     * @return string|null redemail
     */
    public function getRedemail(): ?string
    {
        return $this->redemail;
    }

    /**
     * Redpass
     *
     * @return string|null redpass
     */
    public function getRedpass(): ?string
    {
        return $this->redpass;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): Creator
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::$statusList[$this->status];
    }

    /**
     * The template specific configuration from the Creator Task
     * This is a standard flexform result array
     *
     * @param bool $useTypo3Service returns in a flattened format
     *
     * @return array flexform array
     */
    public function getFlexinfo(bool $useTypo3Service = false)
    {
        if ($this->flexinfo === null && isset($GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['types'][$this->base]) && strpos((string)$GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['types'][$this->base]['showitem'], 'flexinfo')) {
            $row = BackendUtility::getRecord('tx_sudhaus7wizard_domain_model_creator', $this->getUid());
            $this->flexinfo = $row['flexinfo'];
        }

        if ($useTypo3Service) {
            return  GeneralUtility::makeInstance(FlexFormService::class)
                ->convertFlexFormContentToArray($this->flexinfo);
        }

        return GeneralUtility::xml2array($this->flexinfo);
    }

    /**
     * Flexinfo
     *
     * @param array $flexinfo
     * @return $this
     */
    public function setFlexinfo($flexinfo)
    {
        $this->flexinfo = Tools::array2xml($flexinfo);
        return $this;
    }

    /**
     * Email
     *
     * @return string|null email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Returns the CruserId
     *
     * @return string $cruserId
     */
    public function getCruserId()
    {
        return $this->cruserId;
    }

    /**
     * Sourceclass
     *
     * @return string sourcepid
     */
    public function getSourceclass(): string
    {
        return (string)$this->sourceclass;
    }

    /**
     * Status
     *
     * @return array<int, mixed[]> status
     */
    public static function getStatusTca(): array
    {
        $a = [];
        foreach (self::$statusList as $k => $v) {
            $a[] = [$v, $k];
        }
        return $a;
    }

    public function getValuemapping(): string
    {
        return $this->valuemapping;
    }

    public function getValuemappingArray(): array
    {
        if (!empty($this->valuemapping)) {
            if (empty($this->valuemappingcache)) {
                $this->valuemappingcache = GeneralUtility::makeInstance(YamlFileLoader::class)->load($this->getValuemapping());
            }
            return $this->valuemappingcache;
        }
        return [];
    }

    public function getSourceuser(): int
    {
        return $this->sourceuser;
    }

    public function getSourcefilemount(): int
    {
        return $this->sourcefilemount;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setPid(int $pid): Creator
    {
        $this->pid = $pid;
        return $this;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setReduser(string $reduser): Creator
    {
        $this->reduser = $reduser;
        return $this;
    }

    public function setRedemail(string $redemail): Creator
    {
        $this->redemail = $redemail;
        return $this;
    }

    public function setRedpass(string $redpass): Creator
    {
        $this->redpass = $redpass;
        return $this;
    }
}
