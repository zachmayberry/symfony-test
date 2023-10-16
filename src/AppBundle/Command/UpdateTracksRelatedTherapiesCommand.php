<?php

namespace AppBundle\Command;

use AppBundle\Entity\Therapy;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update count for related therapies of tracks
 */
class UpdateTracksRelatedTherapiesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:tracks:update-related-therapies')
            ->setDescription('Update count for related therapies of tracks')
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
            ->set('t.relatedTherapiesCount', ':resetValue')
            ->setParameter('resetValue', 0)
            ->getQuery();

        $query->execute();


        $therapyRepository = $em->getRepository(Therapy::class);
        $therapies = $therapyRepository->findAll();

        foreach($therapies as $therapy) {

            $output->writeln("####### Checking therapy #" . $therapy->getId());
            $output->writeln("------------------------------");

            // AUDIO
            if ($playlist = $therapy->getMusicPlaylist()) {

                $output->writeln("Checking audio playlist #" . $playlist->getId());

                foreach($playlist->getPlaylistTracks() as $playlistTrack) {
                    $track = $playlistTrack->getTrack();

                    if ($track) {

                        $track->incrementRelatedTherapiesCount();
                        $output->writeln("Update track #" . $track->getId() . " => " . $track->getRelatedTherapiesCount() . "x");
                    }
                }
            }

            // TONES
            if ($playlist = $therapy->getBinauralPlaylist()) {

                $output->writeln("Checking tones playlist #" . $playlist->getId());

                foreach($playlist->getPlaylistTracks() as $playlistTrack) {
                    $track = $playlistTrack->getTrack();

                    if ($track) {

                        $track->incrementRelatedTherapiesCount();
                        $output->writeln("Update track #" . $track->getId() . " => " . $track->getRelatedTherapiesCount() . "x");
                    }
                }
            }

            $output->writeln("==============================");
        }

        $em->flush();
    }
}