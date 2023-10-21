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

namespace Services;

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FolderService
{
    public static function getOrCreateFromIdentifier(string $identifier, ResourceStorage $storage = null): Folder
    {
        if ($storage === null) {
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            /** @var ResourceStorage $storage */
            $storage           = $storageRepository->getDefaultStorage();
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
