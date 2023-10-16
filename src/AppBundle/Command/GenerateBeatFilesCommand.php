<?php

namespace AppBundle\Command;

use AppBundle\Entity\Track;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Generates Therapy audio files
 */
class GenerateBeatFilesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:database:generate-beat-files')
            ->setDescription('Generate preview audio files for binaural beats')
            ->setHelp("This command allows you to create short audio files for previewing binaural beats.")
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Database beats creator',
            '======================',
            '',
        ]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $trackRepository = $em->getRepository(Track::class);
        $track = $trackRepository->findOldestBinauralTrackWithoutFile();

        if (!$track) {
            $output->writeln("No uncompiled tracks found!");

            return 0;
        }

        $output->writeln("Found uncompiled track #{$track->getId()}.");

        // Set compiling status
        $track->setCompileStatus(Track::STATUS_COMPILING);
        $em->persist($track);
        $em->flush();

        try {
            $trackService = $this->getContainer()->get('app.track_service');
            $filePath = $trackService->generateBinauralPreviewFile($track);

            if ($track->getIncludesHq()) {
                $filePathHq = $trackService->generateBinauralPreviewFile($track, true);
            }

            if (is_file($filePath) && (!$track->getIncludesHq() || is_file($filePathHq))) {

                $basename = basename($filePath);
                $output->writeln("Created file $basename for track {$track->getId()}.");

                $uploadedFile = new UploadedFile($filePath, $filePath, null, filesize($filePath), null, true); // last param has to be true
                $track->setFile($uploadedFile);

                if ($track->getIncludesHq()) {

                    $basenameHq = basename($filePathHq);
                    $output->writeln("Created HQ file $basenameHq for track {$track->getId()}.");

                    $uploadedFileHq = new UploadedFile($filePathHq, $filePathHq, null, filesize($filePathHq), null, true);
                    $track->setFileHq($uploadedFileHq);
                    $track->setCompileStatus(Track::STATUS_COMPILED); // Set success status
                }
                else {
                    $track->setCompileStatus(Track::STATUS_COMPILED); // Set success status
                }

                $em->persist($track);
                $em->flush();

                $output->writeln("Track updated successfully!");

                return 1;
            }

            $output->writeln("An error occured. Track not updated!");

            return 0;

        }
        catch (\Exception $exception) {

            // Set error status
            $track->setCompileStatus(Track::STATUS_COMPILE_ERROR);
            $em->persist($track);
            $em->flush();

            $output->writeln("ERROR: " . $exception->getMessage());
        }
    }
}