<?php

namespace Xima\XimaTypo3MetadataFixer\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Service\ExtractorService;
use Xima\XimaTypo3MetadataFixer\Domain\Model\Error;
use Xima\XimaTypo3MetadataFixer\Domain\Repository\SysFileRepository;

class MetaDataService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Error[] */
    protected array $errors = [];

    public function __construct(
        private readonly SysFileRepository $sysFileRepository,
        private readonly FileService $fileService,
        private readonly ResourceFactory $resourceFactory,
        private readonly ExtractorService $extractorService
    ) {
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFilesWithoutMetaData(): array
    {
        return $this->sysFileRepository->getWithoutMetaData();
    }

    public function createMetaDataForFile(array &$file): bool
    {
        if (!isset($file['file_exists']) || !$file['file_exists']) {
            return false;
        }

        try {
            $this->addMetaDataForFile($file);
        } catch (\Exception $e) {
            $this->errors[] = new Error($e->getMessage());
        }

        return true;
    }

    private function addMetaDataForFile(mixed $file)
    {
        $fileObject = $this->resourceFactory->getFileObject($file['uid']);
        $metaData = [];
        if ($file['file_is_image']) {
            $metaData['width'] = $file['file_image_width'];
            $metaData['height'] = $file['file_image_height'];
        }
        array_merge($metaData, $this->extractorService->extractMetaData($fileObject));
        $fileObject->getMetaData()->add($metaData)->save();
    }

    public function deleteNonReferencedFilesWithoutMetaData(): bool
    {
        return true;
    }
}
