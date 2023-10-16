<?php

namespace AppBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
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
class ImportDataCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:database:import-data')
            ->setDescription('Import database data from excel file')
            ->setHelp("This command allows you to import database data from an excel file.")
            ->addArgument('file', InputArgument::REQUIRED, 'Which file?');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Database Importer',
            '=================',
            '',
        ]);

        $fileName = $input->getArgument('file');

        $databaseService = $this->getContainer()->get('app.database_service');

        // make output available in service
        $databaseService->setCommandOutput($output);

        $result = $databaseService->importCsv($fileName);

        return $result ? 1 : 0;
    }
}