<?php

namespace Xima\XimaTypo3MetadataFixer\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Xima\XimaTypo3MetadataFixer\Domain\Model\Error;
use Xima\XimaTypo3MetadataFixer\Domain\Repository\SysFileRepository;

class MetaDataService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Error[] */
    protected array $errors = [];

    public function __construct(private readonly SysFileRepository $sysFileRepository)
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

    public function createMetaDataForFiles(): bool
    {
        return true;
    }

    public function deleteNonReferencedFilesWithoutMetaData(): bool
    {
        return true;
    }
}
