<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


/**
 * Generates Therapy audio files
 */
class ExportDataCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:database:export-data')
            ->setDescription('Export database data to excel file')
            ->setHelp("This command allows you to export database data to an excel file.")
            ->addArgument('type', InputArgument::REQUIRED, 'Set type "audio" or "tones"');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Database Exporter',
            '=================',
            '',
        ]);

        $trackType = $input->getArgument('type');

        $databaseService = $this->getContainer()->get('app.database_service');

        // make output available in service
        $databaseService->setCommandOutput($output);

        $result = $databaseService->exportCsv($trackType);

        return $result ? 1 : 0;

    }
}