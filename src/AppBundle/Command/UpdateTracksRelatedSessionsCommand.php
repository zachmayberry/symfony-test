<?php

namespace AppBundle\Command;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\Track;
use AppBundle\Entity\UserTherapy;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update count for related therapies of tracks
 */
class UpdateTracksRelatedSessionsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:tracks:update-related-sessions')
            ->setDescription('Update count for related sessions of tracks')
            ->setHelp("This command is used in CRON job to update the stats periodically.")
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Updating related therapies of tracks',
            '====================================',
            '',
        ]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // reset all tracks first
        $qb = $em->createQueryBuilder();
        $query = $qb
            ->update('AppBundle:Track', 't')
            ->set('t.relatedSessionsCount', ':resetValue')
            ->setParameter('resetValue', 0)
            ->getQuery();
        $query->execute();

        //$therapyRepository = $em->getRepository(Therapy::class);
        $therapyRepository = $em->getRepository(UserTherapy::class);
        $therapies = $therapyRepository->findAll();

        /** @var UserTherapy $therapy */
        foreach($therapies as $therapy) {

            $output->writeln("####### Checking therapy #" . $therapy->getId());
            $output->writeln("------------------------------");

            // get last session, since this will always have the latest up-to-date playlist
            $lastSession = $therapy->getSessions()->last();

            // AUDIO (from last session)
            if ($playlist = $lastSession->getMusicPlaylist()) {

                $output->writeln("Checking audio playlist #" . $playlist->getId());

                foreach($playlist->getPlaylistTracks() as $playlistTrack) {
                    $track = $playlistTrack->getTrack();

                    if ($track) {

                        $track->incrementRelatedSessionsCount();
                        $output->writeln("Update track #" . $track->getId() . " => " . $track->getRelatedSessionsCount() . "x");
                    }
                }
            }

            // TONES (from user therapy)
            if ($playlist = $therapy->getBinauralPlaylist()) {

                $output->writeln("Checking tones playlist #" . $playlist->getId());

                foreach($playlist->getPlaylistTracks() as $playlistTrack) {
                    $track = $playlistTrack->getTrack();

                    if ($track) {

                        $track->incrementRelatedSessionsCount();
                        $output->writeln("Update track #" . $track->getId() . " => " . $track->getRelatedSessionsCount() . "x");
                    }
                }
            }

            $output->writeln("==============================");
        }

        $em->flush();
    }
}