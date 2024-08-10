<?php

namespace Xima\XimaTypo3MetadataFixer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xima\XimaTypo3MetadataFixer\Service\MetaDataService;

class FixMissingCommand extends Command
{
    protected const ANSWER_CREATE_METADATA = 'Create meta data for all files';
    protected const ANSWER_DELETE_NON_REFERENCED = 'Delete files without references';
    protected const ANSWER_DELETE_ALL = 'Delete all files including their references';

    public function __construct(private MetaDataService $metaDataService, string $name = null)
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
        $io = new SymfonyStyle($input, $output);

        $files = $this->metaDataService->getFilesWithoutMetaData();

        if (!count($files)) {
            $io->success('No files with missing meta data found.');
            return Command::SUCCESS;
        }

        $table = $io->createTable();
        $table->setHeaders(['uid', 'identifier', 'references']);
        $table->addRows(array_map(static function ($file) {
            return [$file['uid'], $file['identifier'], $file['reference_count']];
        }, $files));
        $table->render();

        $io->warning('Found ' . count($files) . ' files with missing meta data.');

        $question = new ChoiceQuestion('What should be done?', [
            self::ANSWER_CREATE_METADATA,
            self::ANSWER_DELETE_NON_REFERENCED,
            self::ANSWER_DELETE_ALL,
        ], 0);
        $answer = $io->askQuestion($question);

        if ($answer === self::ANSWER_CREATE_METADATA) {
            $success = $this->metaDataService->createMetaDataForFiles();
            if ($success) {
                $io->success('Successfully created meta data for ' . count($files) . ' files');
                return Command::SUCCESS;
            }

            $errors = $this->metaDataService->getErrors();
            foreach ($errors as $error) {
                $io->error($error->message);
            }

            $io->warning('There have been ' . count($errors) . 'errors while creating meta data for ' . count($files));
        }

        return Command::FAILURE;
    }
}
