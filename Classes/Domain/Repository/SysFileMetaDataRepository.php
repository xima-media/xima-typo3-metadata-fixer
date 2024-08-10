<?php

namespace Xima\XimaTypo3MetadataFixer\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class SysFileMetaDataRepository extends Repository
{
    public function findMissingMetadata(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $result = $qb->select('*')
            ->from('sys_file_metadata', 'm')
            ->where($qb->expr()->eq('title', $qb->createNamedParameter('', \PDO::PARAM_STR)))
            ->executeQuery()
            ->fetchAllAssociative();

        return $result;
    }
}
