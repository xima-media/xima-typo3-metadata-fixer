<?php

namespace Xima\XimaTypo3MetadataFixer\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class SysFileRepository extends Repository
{
    public function getWithoutMetaData(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        return $qb->select('f.*')
            ->addSelectLiteral('count(r.uid) as reference_count')
            ->from('sys_file', 'f')
            ->leftJoin('f', 'sys_file_metadata', 'm', $qb->expr()->eq('m.file', $qb->quoteIdentifier('f.uid')))
            ->leftJoin('f', 'sys_file_reference', 'r', $qb->expr()->eq('r.uid_local', $qb->quoteIdentifier('f.uid')))
            ->where($qb->expr()->isNull('m.uid'))
            ->groupBy('f.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
