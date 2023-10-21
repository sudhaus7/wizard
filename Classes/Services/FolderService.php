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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FolderService
{
    protected StorageRepository $storage_repository;

    protected ResourceStorage $defaultStorage;

    public function __construct(StorageRepository $storage_repository)
    {
        $this->storage_repository = $storage_repository;
        $this->defaultStorage = $this->storage_repository->getDefaultStorage();
    }

    public function getOrCreateFromIdentifier(string $identifier, ResourceStorage $storage = null): Folder
    {
        if ($storage === null) {
            $storage = $this->defaultStorage;
        }
        $identifier = trim($identifier, '/');
        $identifierList = GeneralUtility::trimExplode('/', $identifier);
        $folder = null;
        foreach ($identifierList as $foldername) {
            if ($folder === null) {
                if ($storage->hasFolder($foldername)) {
                    $folder = $storage->getFolder($foldername);
                } else {
                    $folder = $storage->createFolder($foldername);
                }
            } else {
                if ($folder->hasFolder($foldername)) {
                    $folder = $storage->getFolderInFolder($foldername, $folder);
                } else {
                    $folder = $folder->createFolder($foldername);
                }
            }
        }
        return $folder;
    }
}
