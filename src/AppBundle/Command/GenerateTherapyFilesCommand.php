<?php

namespace AppBundle\Command;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Repository\TherapyRepository;
use AppBundle\Repository\TherapySessionRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates Therapy audio files
 */
class GenerateTherapyFilesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:generate-therapy-files')
            ->setDescription('Generate audio files for Therapies')
            ->setHelp("This command allows you to create therapy audio files.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Therapy audio creator',
            '=====================',
            '',
        ]);

        // GATHER THERAPY DATA

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $bs = $this->getContainer()->get('app.beat_api_service');

        /** @var TherapyRepository $therapyRepository */
        $therapyRepository = $em->getRepository(Therapy::class);

        /** @var Therapy $therapy */
        if ($therapy = $therapyRepository->findOldestOutdatedTherapy()) {

            // save therapy id to fetch again after reestablish database
            $itemId = $therapy->getId();

            $output->writeln("Found uncompiled therapy #{$therapy->getId()}.");

            // get this before changing the status
            $createHq = $therapy->getIncludesHq()
                && !$therapy->isCompiledHq()
                && !$therapy->isCompilingHq();

            // Set status of the requested version to compiling
            if ($createHq) {
                $therapy->setCompileStatusHq(Therapy::STATUS_COMPILING);
            } else {
                $therapy->setCompileStatus(Therapy::STATUS_COMPILING);
            }

            // save
            $em->persist($therapy);
            $em->flush();

            $musicPlaylist = $therapy->getMusicPlaylist();
            $beatsPlaylist = $therapy->getBinauralPlaylist();
            $hasTonesPlaylist = $therapy->hasTonesPlaylist();
            $filePrefix = 'therapy_' . $therapy->getId();
            $apiParameters = $bs->getConfigurationDataFromTherapy($therapy, $createHq);

        } else {

            $output->writeln("No uncompiled therapies found! Searching for Therapy Sessions..");

            /** @var TherapySessionRepository $therapySessionRepository */
            $therapySessionRepository = $em->getRepository(TherapySession::class);

            /** @var TherapySession $therapySession */
            if ($therapySession = $therapySessionRepository->findOldestOutdatedTherapySession()) {

                // save session id to fetch again after reestablish database
                $itemId = $therapySession->getId();

                $output->writeln("Found uncompiled therapy session #{$therapySession->getId()}.");

                // get this before changing the status
                $createHq = $therapySession->getIncludesHq()
                    && !$therapySession->isCompiledHq()
                    && !$therapySession->isCompilingHq();

                // Set status of the requested version to compiling
                if ($createHq) {
                    $therapySession->setCompileStatusHq(Therapy::STATUS_COMPILING);
                } else {
                    $therapySession->setCompileStatus(Therapy::STATUS_COMPILING);
                }

                // save
                $em->persist($therapySession);

                // Set compiling status for all sibling sessions
                /** @var TherapySession $siblingSession */
                foreach ($therapySessionRepository->findPendingSiblingSession($therapySession) as $siblingSession) {
                    if ($createHq) {
                        $siblingSession->setCompileStatusHq(Therapy::STATUS_COMPILING);
                    } else {
                        $siblingSession->setCompileStatus(Therapy::STATUS_COMPILING);
                    }
                    $em->persist($siblingSession);
                }

                $em->flush();

                $musicPlaylist = $therapySession->getMusicPlaylist();
                $beatsPlaylist = $therapySession->getBinauralPlaylist();
                $hasTonesPlaylist = $therapySession->hasTonesPlaylist();
                $filePrefix = 'session_' . $therapySession->getId();
                $apiParameters = $bs->getConfigurationDataFromTherapySession($therapySession, $createHq);

            } else {

                $output->writeln("No uncompiled therapy session found!");

                return 0;
            }
        }

        // REQUEST COMPILATION

        // Check if only request for audio file. If this parameter is false or not set, we will wait until the file is
        // returned by the API, otherwise we the API has to call the api-callback endpoint if compilation is finished
        $asyncMode = $this->getContainer()->getParameter('api_server_async');

        try {
            // Call API to generate the file
            $filePath = $this->getContainer()->get('app.therapy_service')->generateTherapyFile(
                $filePrefix,
                $hasTonesPlaylist,
                $musicPlaylist,
                $beatsPlaylist,
                $apiParameters,
                $createHq,
                false, // only for therapy builder prelisten function
                !$createHq && $therapy !== null, // only create preview for audible therapies
                $asyncMode
            );

            // Get name of the requested file
            $basename = basename($filePath);

            // Wait for compilation or quit here
            if ($asyncMode) {

                $output->writeln(sprintf(
                    "Requested %s file for %s with ID %s: %s",
                    $createHq ? "HQ" : "audible",
                    $therapy ? "Therapy" : "Session",
                    $therapy ? $therapy->getId() : $therapySession->getId(),
                    $basename
                ));

            } else {

                // refresh connection (https://github.com/facile-it/doctrine-mysql-come-back)
                $em->getConnection()->refresh();

                // THERAPY

                if ($therapy) {

                    /** @var Therapy $therapy */
                    $therapy = $em->getRepository(Therapy::class)->find($itemId);

                    if ($createHq) {
                        $output->writeln("Created HQ file $basename for therapy {$therapy->getId()}.");
                        $therapy->setFileNameHq($basename);
                        $therapy->setFileSizeHq(filesize($filePath));
                        $therapy->setCompileStatusHq(Therapy::STATUS_COMPILED);

                    } else {
                        $output->writeln("Created file $basename for therapy {$therapy->getId()}.");
                        $therapy->setFileName($basename);
                        $therapy->setFileSize(filesize($filePath));
                        $therapy->setCompileStatus(Therapy::STATUS_COMPILED);

                        // generate preview file (only for audible version)
                        $bs->generatePreviewFile($filePath, Therapy::getPreviewFileName($filePath));
                    }

                    // set updatedAt here so shouldComponentUpdate gets triggered in therapy react components
                    $therapy->setUpdatedAt(new \DateTime());

                    $em->persist($therapy);
                    $em->flush();

                    $output->writeln("Therapy {$therapy->getId()} has been updated.");

                } // SESSION

                else {

                    /** @var TherapySessionRepository $therapySessionRepository */
                    $therapySessionRepository = $em->getRepository(TherapySession::class);
                    /** @var TherapySession $therapySession */
                    $therapySession = $therapySessionRepository->find($itemId);

                    if ($createHq) {
                        $output->writeln("Created HQ file $basename for session {$therapySession->getId()}.");

                        $fileSize = filesize($filePath);

                        $therapySession->setFileNameHq($basename);
                        $therapySession->setFileSizeHq($fileSize);
                        $therapySession->setCompileStatusHq(Therapy::STATUS_COMPILED);

                    } else {
                        $output->writeln("Created file $basename for session {$therapySession->getId()}.");

                        $fileSize = filesize($filePath);

                        $therapySession->setFileName($basename);
                        $therapySession->setFileSize($fileSize);
                        $therapySession->setCompileStatus(Therapy::STATUS_COMPILED);
                    }

                    $em->persist($therapySession);

                    $output->writeln("Session {$therapySession->getId()} has been updated.");

                    $siblingSessions = $therapySessionRepository->findPendingSiblingSession($therapySession);

                    /** @var TherapySession $siblingSession */
                    foreach ($siblingSessions as $siblingSession) {

                        if ($createHq) {
                            $siblingSession->setFileNameHq($basename);
                            $siblingSession->setFileSizeHq($fileSize);
                            $siblingSession->setCompileStatusHq(Therapy::STATUS_COMPILED);

                        } else {
                            $siblingSession->setFileName($basename);
                            $siblingSession->setFileSize($fileSize);
                            $siblingSession->setCompileStatus(Therapy::STATUS_COMPILED);
                        }

                        $em->persist($siblingSession);

                        $output->writeln("Session {$siblingSession->getId()} has been updated.");
                    }

                    $em->flush();
                }
            }

            return 1;

        } catch (\Exception $exception) {

            // Set error status
            $therapy->setCompileStatus(Therapy::STATUS_COMPILE_ERROR);
            $em->persist($therapy);
            $em->flush();

            $output->writeln("Error: " . $exception->getMessage());

            return 0;
        }
    }
}