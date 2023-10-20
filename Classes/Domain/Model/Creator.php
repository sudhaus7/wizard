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

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Model;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model Creator
 */
class Creator extends AbstractEntity implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    public static array $statusList = [
        0 => 'editing',
        5 => 'Not ready',
        10 => 'ready',
        15 => 'processing',
        20 => 'done',
    ];
    protected ?string $t3ver_label = null;
    protected ?int $hidden = null;
    protected ?string $sourcepid = null;
    protected ?string $base = null;
    protected ?string $projektname = null;
    protected ?string $longname = null;
    protected ?string $shortname = null;
    protected ?string $domainname = null;
    protected ?string $contact = null;
    protected ?string $reduser = null;
    protected ?string $redemail = null;
    protected ?string $redpass = null;
    protected string $status = '';
    protected ?string $flexinfo = null;
    protected ?string $email = null;
    protected ?string $valuemapping = null;
    private array $valuemappingcache = [];
    protected int $sourceuser = 0;
    protected int $sourcefilemount = 0;
    /**
     * CruserId
     *
     * @var int
     */
    protected $cruserId = 0;
    /**
     * Source Class
     */
    protected ?string $sourceclass = null;

    /**
     * T3verLabel
     *
     * @return string|null t3ver_label
     */
    public function getT3verLabel(): ?string
    {
        return $this->t3ver_label;
    }

    /**
     * T3verLabel
     *
     * @return $this
     */
    public function setT3verLabel(?string $t3ver_label)
    {
        $this->t3ver_label = $t3ver_label;
        return $this;
    }

    /**
     * Hidden
     *
     * @return int|null hidden
     */
    public function getHidden(): ?int
    {
        return $this->hidden;
    }

    /**
     * Hidden
     *
     * @return $this
     */
    public function setHidden(?int $hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Sourcepid
     *
     * @return string|null sourcepid
     */
    public function getSourcepid(): ?string
    {
        if (\str_starts_with((string)$this->sourcepid, 't3://')) {
            return (string)GeneralUtility::trimExplode('=', $this->sourcepid)[1];
        }
        return $this->sourcepid;
    }

    /**
     * Sourcepid
     *
     * @return $this
     */
    public function setSourcepid(?string $sourcepid)
    {
        $this->sourcepid = $sourcepid;
        return $this;
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
     * Base
     *
     * @return $this
     */
    public function setBase(?string $base)
    {
        $this->base = $base;
        return $this;
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
     * Projektname
     *
     * @return $this
     */
    public function setProjektname(?string $projektname)
    {
        $this->projektname = $projektname;
        return $this;
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
     * Longname
     *
     * @return $this
     */
    public function setLongname(?string $longname)
    {
        $this->longname = $longname;
        return $this;
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
     * Shortname
     *
     * @return $this
     */
    public function setShortname(?string $shortname)
    {
        $this->shortname = $shortname;
        return $this;
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
     * Domainname
     *
     * @return $this
     */
    public function setDomainname(?string $domainname)
    {
        $this->domainname = $domainname;
        return $this;
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
     * Contact
     *
     * @return $this
     */
    public function setContact(?string $contact)
    {
        $this->contact = $contact;
        return $this;
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
     * Reduser
     *
     * @return $this
     */
    public function setReduser(?string $reduser)
    {
        $this->reduser = $reduser;
        return $this;
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
     * Redemail
     *
     * @return $this
     */
    public function setRedemail(?string $redemail)
    {
        $this->redemail = $redemail;
        return $this;
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

    /**
     * Redpass
     *
     * @return $this
     */
    public function setRedpass(?string $redpass)
    {
        $this->redpass = $redpass;
        return $this;
    }

    /**
     * Status
     *
     * @return string status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Status
     *
     * @param mixed $status
     * @return $this
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Status
     *
     * @return string status
     */
    public function getStatusLabel()
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
     * Email
     *
     * @return $this
     */
    public function setEmail(?string $email)
    {
        $this->email = $email;
        return $this;
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
     * Sets the CruserId
     *
     * @param string $cruserId
     */
    public function setCruserId($cruserId): void
    {
        $this->cruserId = $cruserId;
    }
    /**
     * Sourceclass
     *
     * @return string|null sourcepid
     */
    public function getSourceclass(): ?string
    {
        return $this->sourceclass;
    }

    /**
     * @param $sourceclass
     *
     * @return $this
     */
    public function setSourceclass(?string $sourceclass)
    {
        $this->sourceclass = $sourceclass;
        return $this;
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
        return (string)$this->valuemapping;
    }

    public function setValuemapping(?string $valuemapping): void
    {
        $this->valuemapping = (string)$valuemapping;
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

    public function setSourceuser(int $sourceuser): void
    {
        $this->sourceuser = $sourceuser;
    }

    public function getSourcefilemount(): int
    {
        return $this->sourcefilemount;
    }

    public function setSourcefilemount(int $sourcefilemount): void
    {
        $this->sourcefilemount = $sourcefilemount;
    }
}
