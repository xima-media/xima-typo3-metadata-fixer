<?php

namespace Xima\XimaTypo3MetadataFixer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixCommand extends Command
{
    protected static $defaultName = 'fix';

    protected function configure(): void
    {
        $this
            ->setDescription('Fixes metadata in TYPO3 database.')
            ->setHelp('This command allows you to fix metadata in TYPO3 database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Some imaginary code here

        $io->success('Metadata fixed successfully!');

        return Command::SUCCESS;
    }
}
