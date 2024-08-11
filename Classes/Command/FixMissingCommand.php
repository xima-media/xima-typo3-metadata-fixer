<?php

namespace Xima\XimaTypo3MetadataFixer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use Xima\XimaTypo3MetadataFixer\Service\FileService;
use Xima\XimaTypo3MetadataFixer\Service\MetaDataService;

class FixMissingCommand extends Command
{
    protected const ANSWER_CREATE_METADATA = 'Create meta data for available files';
    protected const ANSWER_DELETE_NON_REFERENCED = 'Delete sys_files without references';
    protected const ANSWER_DELETE_ALL = 'Delete all sys_files including their references';

    public function __construct(private MetaDataService $metaDataService, private FileService $fileService, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fixes metadata in TYPO3 database.')
            ->setHelp('This command allows you to fix metadata in TYPO3 database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();
        $io = new SymfonyStyle($input, $output);

        $files = $this->metaDataService->getFilesWithoutMetaData();

        if (!count($files)) {
            $io->success('No sys_files with missing meta data found.');
            return Command::SUCCESS;
        }

        $missingFileCount = $this->fileService->getCountOfMissingFiles($files);

        $this->renderTableForFiles($files, $io);

        $io->warning('Found ' . count($files) . ' sys_files with missing meta data.');

        if ($missingFileCount) {
            $io->error('There are ' . $missingFileCount . ' missing files.');
        }

        $question = new ChoiceQuestion('What should be done?', [
            self::ANSWER_CREATE_METADATA,
            self::ANSWER_DELETE_NON_REFERENCED,
            self::ANSWER_DELETE_ALL,
        ], 0);
        $answer = $io->askQuestion($question);

        if ($answer === self::ANSWER_CREATE_METADATA) {
            $availableFiles = array_filter($files, static function ($file) {
                return $file['file_exists'];
            });
            $io->progressStart(count($availableFiles));
            foreach ($availableFiles as $file) {
                $this->metaDataService->createMetaDataForFile($file);
                $io->progressAdvance();
            }
            $io->progressFinish();

            return $this->displayServiceResult($io);
        }

        if ($answer === self::ANSWER_DELETE_NON_REFERENCED) {
            $this->metaDataService->deleteNonReferencedFilesWithoutMetaData($files);
            return $this->displayServiceResult($io);
        }

        return Command::FAILURE;
    }

    protected function renderTableForFiles(array $files, SymfonyStyle $io): void
    {
        $table = $io->createTable();
        $table->setHeaders(['uid', 'identifier', 'references', 'file exists', 'dimensions']);

        $rows = array_map(static function ($file) {
            $fileExists = $file['file_exists'] ?? '?';
            $fileExists = $fileExists === true ? 'yes' : $fileExists;
            $fileExists = $fileExists === false ? 'no' : $fileExists;
            $dimensions = '';
            if (isset($file['file_is_image']) && $file['file_is_image']) {
                $dimensions = '?';
            }
            if (isset($file['file_image_width'], $file['file_image_height'])) {
                $dimensions = $file['file_image_width'] . 'x' . $file['file_image_height'];
            }
            return [$file['uid'], $file['identifier'], $file['reference_count'], $fileExists, $dimensions];
        }, $files);

        usort($rows, static function ($a, $b) {
            return $a[2] <=> $b[2];
        });

        $table->addRows($rows);
        $table->render();
    }

    protected function displayServiceResult(SymfonyStyle $io): bool
    {
        $errors = $this->metaDataService->getErrors();
        foreach ($errors as $error) {
            $io->error($error->message);
        }

        if (!count($errors)) {
            $io->success('Success');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('There were %s errors', count($errors)));
        return Command::FAILURE;
    }
}
