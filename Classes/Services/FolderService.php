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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FolderService
{
    protected StorageRepository $storageRepository;

    protected ResourceStorage $defaultStorage;

    public function __construct(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;

        $defaultStorage = $this->storageRepository->getDefaultStorage();
        if (!$defaultStorage instanceof ResourceStorage) {
            throw new \RuntimeException(
                'The default storage was not loadable',
                1700583057118
            );
        }
        $this->defaultStorage = $defaultStorage;
    }

    /**
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InsufficientFolderWritePermissionsException
     * @throws InsufficientFolderReadPermissionsException
     */
    public function getOrCreateFromIdentifier(string $identifier, ResourceStorage $storage = null): Folder
    {
        if ($storage === null) {
            $storage = $this->defaultStorage;
        }
        $identifier = trim($identifier, '/');
        $identifierList = GeneralUtility::trimExplode('/', $identifier);
        $folder = null;
        foreach ($identifierList as $folderName) {
            if ($folder === null) {
                if ($storage->hasFolder($folderName)) {
                    $folder = $storage->getFolder($folderName);
                } else {
                    $folder = $storage->createFolder($folderName);
                }
            } else {
                if ($folder->hasFolder($folderName)) {
                    $folder = $storage->getFolderInFolder($folderName, $folder);
                } else {
                    $folder = $folder->createFolder($folderName);
                }
            }
        }

        if (!$folder instanceof Folder) {
            throw new \InvalidArgumentException(
                sprintf('Folder "%s" not created or not accessible', $identifier),
                1700582823964
            );
        }
        return $folder;
    }
}
