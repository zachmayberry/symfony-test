<?php

namespace AppBundle\Command;

use AppBundle\Entity\TherapySession;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates Therapy audio files
 */
class CheckForMissedSessionsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:check-for-missed-sessions')
            ->setDescription('Check for missed sessions')
            ->setHelp("This command will check all sessions and update their status if missed.")
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Checking for missed sessions',
            '============================',
        ]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $therapySessionRepository = $em->getRepository(TherapySession::class);
        $newMissedSessionsCount = $therapySessionRepository->findAndSetMissedSessions();

        // update user therapy status afterwards
        if ($newMissedSessionsCount) {
            $this->getContainer()->get('app.user_therapy_service')->updateAllUserTherapyStatus();
        }

        $output->writeln("Found and updated $newMissedSessionsCount sessions!");
        $output->writeln('============================');

        return $newMissedSessionsCount;
    }
}