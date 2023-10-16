<?php

namespace AppBundle\Command;

use AppBundle\Entity\UploadedAudio;
use AppBundle\Repository\UploadedAudioRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Generates Therapy audio files
 */
class ConvertUploadedAudioCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:database:convert-uploaded-audio')
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
            'Uploaded audio converter',
            '========================',
            '',
        ]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var UploadedAudioRepository $uploadedAudioRepository */
        $uploadedAudioRepository = $em->getRepository(UploadedAudio::class);

        /** @var UploadedAudio $uploadedAudio */
        $uploadedAudio = $uploadedAudioRepository->findOldestUnconvertedUploadedAudio();

        if (!$uploadedAudio) {
            $output->writeln("No unconverted audio files found!");

            return 0;
        }

        $output->writeln("Found unconverted uploaded audio #{$uploadedAudio->getId()}.");

        // Set compiling status
        $uploadedAudio->setCompileStatus(UploadedAudio::STATUS_COMPILING);
        $em->persist($uploadedAudio);
        $em->flush();

        try {

            $uploadedAudioService = $this->getContainer()->get('app.uploaded_audio_service');
            $distFileName = $uploadedAudioService->convertUploadedAudio($uploadedAudio);

            $output->writeln(sprintf(
                "Requested convert-job for UploadedAudio with ID %s: %s",
                $uploadedAudio->getId(),
                $distFileName
            ));

            return 1;
        }
        catch (\Exception $exception) {

            // Set error status
            $uploadedAudio->setCompileStatus(UploadedAudio::STATUS_COMPILE_ERROR);
            $em->persist($uploadedAudio);
            $em->flush();

            $output->writeln("ERROR: " . $exception->getMessage());
        }
    }
}