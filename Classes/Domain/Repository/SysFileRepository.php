<?php

namespace Xima\XimaTypo3MetadataFixer\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class SysFileRepository extends Repository
{
    public function deleteNotReferenced(): void
    {
        $files = $this->getWithoutMetaData();
        $notReferenced = array_filter($files, static function ($file) {
            return !$file['reference_count'];
        });
        $uids = array_column($notReferenced, 'uid');

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $qb->delete('sys_file')
            ->where(
                $qb->expr()->in('uid', $qb->quoteArrayBasedValueListToIntegerList($uids))
            )
            ->executeStatement();
    }

    public function getWithoutMetaData(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        return $qb->select('f.*')
            ->addSelectLiteral('count(r.uid) as reference_count', '"missing meta data" as file_status')
            ->from('sys_file', 'f')
            ->leftJoin('f', 'sys_file_metadata', 'm', $qb->expr()->eq('m.file', $qb->quoteIdentifier('f.uid')))
            ->leftJoin('f', 'sys_file_reference', 'r', $qb->expr()->eq('r.uid_local', $qb->quoteIdentifier('f.uid')))
            ->where($qb->expr()->isNull('m.uid'))
            ->groupBy('f.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getImagesWithoutDimensions(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        return $qb->select('f.*')
            ->addSelectLiteral('count(r.uid) as reference_count', '"invalid dimensions" as file_status')
            ->from('sys_file', 'f')
            ->innerJoin('f', 'sys_file_metadata', 'm', $qb->expr()->eq('m.file', $qb->quoteIdentifier('f.uid')))
            ->leftJoin('f', 'sys_file_reference', 'r', $qb->expr()->eq('r.uid_local', $qb->quoteIdentifier('f.uid')))
            ->where($qb->expr()->or(
                $qb->expr()->eq('m.width', 0),
                $qb->expr()->eq('m.height', 0)
            ))
            ->andWhere($qb->expr()->eq('f.type', 2))
            ->groupBy('f.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
