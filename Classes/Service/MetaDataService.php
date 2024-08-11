<?php

namespace Xima\XimaTypo3MetadataFixer\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3MetadataFixer\Domain\Model\Error;
use Xima\XimaTypo3MetadataFixer\Domain\Repository\SysFileRepository;

class MetaDataService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Error[] */
    protected array $errors = [];

    public function __construct(private readonly SysFileRepository $sysFileRepository, private readonly FileService $fileService)
    {
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
                $this->errors[] = new Error('Local file for sys_file [uid:' . $file['uid'] . '] does not exist');
            }
        }
        return !count($this->errors);
    }

    public function deleteNonReferencedFilesWithoutMetaData(): bool
    {
        return true;
    }
}
