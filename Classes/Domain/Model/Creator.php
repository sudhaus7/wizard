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

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Model;

use SUDHAUS7\Sudhaus7Base\Tools\DB;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model Creator
 */
class Creator extends AbstractEntity
{
    public static array $statusList = [
        0 => 'In Bearbeitung',
        5 => 'Nicht freigegeben',
        10 => 'Freigegeben',
        15 => 'In Bearbeitung',
        20 => 'Fertig',
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
     * @return string t3ver_label
     */
    public function getT3verLabel()
    {
        return $this->t3ver_label;
    }

    /**
     * T3verLabel
     *
     * @param string $t3ver_label
     * @return $this
     */
    public function setT3verLabel($t3ver_label)
    {
        $this->t3ver_label = $t3ver_label;
        return $this;
    }

    /**
     * Hidden
     *
     * @return int hidden
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Hidden
     *
     * @param int $hidden
     * @return $this
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Sourcepid
     *
     * @return string sourcepid
     */
    public function getSourcepid()
    {
        return $this->sourcepid;
    }

    /**
     * Sourcepid
     *
     * @param string $sourcepid
     * @return $this
     */
    public function setSourcepid($sourcepid)
    {
        $this->sourcepid = $sourcepid;
        return $this;
    }

    /**
     * Base
     *
     * @return string base
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Base
     *
     * @param string $base
     * @return $this
     */
    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    /**
     * Projektname
     *
     * @return string projektname
     */
    public function getProjektname()
    {
        return $this->projektname;
    }

    /**
     * Projektname
     *
     * @param string $projektname
     * @return $this
     */
    public function setProjektname($projektname)
    {
        $this->projektname = $projektname;
        return $this;
    }

    /**
     * Longname
     *
     * @return string longname
     */
    public function getLongname()
    {
        return $this->longname;
    }

    /**
     * Longname
     *
     * @param string $longname
     * @return $this
     */
    public function setLongname($longname)
    {
        $this->longname = $longname;
        return $this;
    }

    /**
     * Shortname
     *
     * @return string shortname
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Shortname
     *
     * @param string $shortname
     * @return $this
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;
        return $this;
    }

    /**
     * Domainname
     *
     * @return string domainname
     */
    public function getDomainname()
    {
        return $this->domainname;
    }

    /**
     * Domainname
     *
     * @param string $domainname
     * @return $this
     */
    public function setDomainname($domainname)
    {
        $this->domainname = $domainname;
        return $this;
    }

    /**
     * Contact
     *
     * @return string contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Contact
     *
     * @param string $contact
     * @return $this
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Reduser
     *
     * @return string reduser
     */
    public function getReduser()
    {
        return $this->reduser;
    }

    /**
     * Reduser
     *
     * @param string $reduser
     * @return $this
     */
    public function setReduser($reduser)
    {
        $this->reduser = $reduser;
        return $this;
    }

    /**
     * Redemail
     *
     * @return string redemail
     */
    public function getRedemail()
    {
        return $this->redemail;
    }

    /**
     * Redemail
     *
     * @param string $redemail
     * @return $this
     */
    public function setRedemail($redemail)
    {
        $this->redemail = $redemail;
        return $this;
    }

    /**
     * Redpass
     *
     * @return string redpass
     */
    public function getRedpass()
    {
        return $this->redpass;
    }

    /**
     * Redpass
     *
     * @param string $redpass
     * @return $this
     */
    public function setRedpass($redpass)
    {
        $this->redpass = $redpass;
        return $this;
    }

    /**
     * Status
     *
     * @return string status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Status
     *
     * @param mixed $status
     * @return $this
     */
    public function setStatus($status)
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
     * Flexinfo
     *
     * @return array flexinfo
     */
    public function getFlexinfo()
    {
        if ($this->flexinfo === null && isset($GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['types'][$this->base]) && strpos($GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['types'][$this->base]['showitem'], 'flexinfo')) {
            $row = DB::getRecord('tx_sudhaus7wizard_domain_model_creator', $this->getUid());
            $this->flexinfo = $row['flexinfo'];
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
        $this->flexinfo = self::array2xml($flexinfo);
        return $this;
    }
    private static function array2xml($a)
    {
        /** @var $flexObj FlexFormTools */
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        return $flexObj->flexArray2Xml($a, true);
    }

    /**
     * Email
     *
     * @return string email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
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
    public function setCruserId($cruserId)
    {
        $this->cruserId = $cruserId;
    }
    /**
     * Sourceclass
     *
     * @return string sourcepid
     */
    public function getSourceclass()
    {
        return $this->sourceclass;
    }

    /**
     * @param $sourceclass
     *
     * @return $this
     */
    public function setSourceclass($sourceclass)
    {
        $this->sourceclass = $sourceclass;
        return $this;
    }
    /**
     * Status
     *
     * @return array status
     */
    public static function getStatusTca()
    {
        $a = [];
        foreach (self::$statusList as $k => $v) {
            $a[] = [$v, $k];
        }
        return $a;
    }
}
