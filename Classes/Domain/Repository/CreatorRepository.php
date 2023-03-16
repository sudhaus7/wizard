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

namespace SUDHAUS7\Sudhaus7Wizard\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class CreatorRepository
 */
class CreatorRepository extends Repository
{
    public function findAll()
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->setQuerySettings($querySettings);
        return $query->execute();
    }

    public function findNext()
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->setQuerySettings($querySettings);
        $query->matching($query->equals('status', 10));
        return $query->execute()->getFirst();
    }
}
