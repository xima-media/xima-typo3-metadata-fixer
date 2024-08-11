<?php

namespace Xima\XimaTypo3MetadataFixer\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
        private readonly ResourceFactory $resourceFactory
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

    public function createMetaDataForFiles(array &$files): bool
    {
        $this->errors = [];
        foreach ($files as $file) {
            if (!$file['file_exists']) {
                continue;
            }
        }
        return !count($this->errors);
    }

    public function deleteNonReferencedFilesWithoutMetaData(): bool
    {
        return true;
    }
}
