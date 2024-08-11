<?php

namespace Xima\XimaTypo3MetadataFixer\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Xima\XimaTypo3MetadataFixer\Domain\Model\Storage;

class FileService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Storage[] */
    protected array $storages;

    public function __construct(private readonly ResourceFactory $resourceFactory)
    {
    }

    public function getCountOfMissingFiles(array &$files): int
    {
        $missing = 0;
        foreach ($files as &$file) {
            $exists = $this->checkFileExistence($file);
            if ($exists) {
                $missing++;
            }
        }
        return $missing;
    }

    public function checkFileExistence(array &$file): bool
    {
        try {
            $fileObject = $this->resourceFactory->getFileObject($file['uid']);
        } catch (\Exception) {
            $this->logger->info('Could not find file object', ['sys_file' => $file]);
            $file['file_exists'] = false;
            return false;
        }

        $resourceStorage = $fileObject->getStorage();

        if (!$resourceStorage) {
            $this->logger->info('Could not find ResourceStorage', ['sys_file' => $file]);
            $file['file_exists'] = false;
            return false;
        }

        $storage = $this->cacheStorageSettings($resourceStorage);
        if (!$storage) {
            $file['file_exists'] = false;
            return false;
        }

        $filePath = $storage->absolutePath . $file['identifier'];
        if (!file_exists($filePath)) {
            $file['file_exists'] = false;
            return false;
        }

        $file['file_is_image'] = self::isImage($file);
        if ($file['file_is_image']) {
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $filePath);
            $file['file_image_width'] = $imageInfo->getWidth();
            $file['file_image_height'] = $imageInfo->getHeight();
        }

        $file['file_path'] = $filePath;
        $file['file_exists'] = true;
        return true;
    }

    protected function cacheStorageSettings(ResourceStorage $resourceStorage): ?Storage
    {
        $storage = $this->storages[$resourceStorage->getUid()] ?? null;
        if ($storage) {
            return $storage;
        }

        if ($resourceStorage->getDriverType() !== 'Local') {
            $this->logger->error('Storage driver not supported', ['storage' => $resourceStorage]);
            return null;
        }

        $config = $resourceStorage->getConfiguration();
        $path = $config['pathType'] === 'relative' ? Environment::getPublicPath() . '/' . $config['basePath'] : $config['basePath'];

        $storage = new Storage();
        $storage->uid = $resourceStorage->getUid();
        $storage->absolutePath = rtrim($path, '\/');

        $this->storages[$resourceStorage->getUid()] = $storage;

        return $storage;
    }

    protected function extractRequiredMetaData(array $file): array
    {
        $metaData = [];

        if (!self::isImage($file)) {
            return $metaData;
        }
        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $file['file_path']);
        $metaData = [
            'width' => $imageInfo->getWidth(),
            'height' => $imageInfo->getHeight(),
        ];

        return $metaData;
    }

    protected static function isImage(array $file): bool
    {
        $pathinfo = PathUtility::pathinfo($file['name']);
        $extension = strtolower($pathinfo['extension'] ?? '');
        return GeneralUtility::inList(
            strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? ''),
            $extension
        ) && $file['size'] > 0;
    }
}
