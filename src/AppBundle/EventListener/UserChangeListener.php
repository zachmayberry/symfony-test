<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\User;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Doctrine\UserManager;

class UserChangeListener
{
    protected $userManager;
    protected $mailer;
    protected $twig;

    public function __construct(UserManager $entityManager, $mailer, $twig, $fromEmail, $fromName)
    {
        $this->em = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(User $user, PreUpdateEventArgs $event)
    {
        //$this->sendDischargePatientMail($user);
    }

//    public function prePersist(LifecycleEventArgs $args)
//    {
//        $object = $args->getObject();
//
//        // only act on some "UserTherapy" entity
//        if (!$object instanceof User) {
//            return;
//        }
//
//        $this->checkForUserTypeChange($object);
//    }

    public function sendDischargePatientMail(User $user)
    {
        $template = $this->twig->loadTemplate(':email:discharge_patient.email.twig');
        $parameters = [
            'user' => $user,
        ];

        $subject = $template->renderBlock('subject', $parameters);
        $bodyText = $template->renderBlock('body_text', $parameters);
        $bodyHtml = $template->renderBlock('body_html', $parameters);

        $message = new \Swift_Message();
        $message->setSubject($subject);
        $message->setBody($bodyText, 'text/plain');
        $message->addPart($bodyHtml, 'text/html');

        $message->setFrom($this->fromEmail, $this->fromName);
        $message->setTo($user->getEmail());

        $this->mailer->send($message);
    }
}