<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserType;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Voryx\RESTGeneratorBundle\Controller\VoryxController;

/**
 * User controller.
 * @RouteResource("User")
 */
class UserController extends VoryxController
{
    /**
     * Get a User entity
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @return Response
     *
     */
    public function getAction(User $entity)
    {
//        $view->setSerializationContext(
//        // This will not work because HateoasBundle only work with "Default" group
//            $view->getSerializationContext()->setGroups(['Custom_Group'])
//        );

        return $entity;
    }

    /**
     * Get all User entities.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "List"})
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @QueryParam(name="page", requirements="\d+", nullable=true, description="Which page to return when more than limit.")
     * @QueryParam(name="limit", requirements="\d+", default="9999", description="How many notes to return.")
     * @QueryParam(name="order_by", nullable=true, array=true, description="Order by fields. Must be an array ie. &order_by[name]=ASC&order_by[description]=DESC")
     * @QueryParam(name="filters", nullable=true, array=true, description="Filter by fields. Must be an array ie. &filters[id]=3")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $user = $this->getUser();

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findAllQueryBuilder($paramFetcher, $user);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_users');

        return $paginatedCollection;
    }

    /**
     * Create a User entity.
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postAction(Request $request)
    {
        $originalRequest = clone $request;

        // use user manager to correctly handle password and canonical fields
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $form = $this->createForm(get_class(new UserType()), $user, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {

            // check if we only want to validate the form without submitting it
            if ($originalRequest->get('validateOnly')) {
                return FOSView::create(null, Codes::HTTP_OK);
            }

            // Add roles according to user type
            if ($user->isAdmin()) {
                $user->addRole('ROLE_DOCTOR');
                $user->addRole('ROLE_ADMIN');
            }
            else if ($user->isDoctor()) {
                $user->addRole('ROLE_DOCTOR');
            }
            else if ($user->isPatient()) {
                $user->addRole('ROLE_PATIENT');
            }

            $userManager->updateUser($user);

            return $user;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Update a User entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, User $entity)
    {
        $originalRequest = clone $request;

        $em = $this->getDoctrine()->getManager();

        // get original user data to compare what has changed
        $user = $em->getRepository('AppBundle:User')->find($request->get('id'));
        $type = $user->getType();
        $doctorIds = $user->virtualDoctors();
        $doctorNames = $user->virtualDoctorsNames();
        $enabled = $user->isEnabled();

        try {
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new UserType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // Check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                // Check if user type changed
                if ($type !== $entity->getType()) {

                    // Change roles
                    if ($entity->isDoctor()) {
                        $entity->removeRole('ROLE_PATIENT');
                        $entity->addRole('ROLE_DOCTOR');
                    }
                    else if ($entity->isPatient()) {
                        $entity->removeRole('ROLE_DOCTOR');
                        $entity->addRole('ROLE_PATIENT');
                    }
                    else if ($entity->isClient()) {
                        $entity->removeRole('ROLE_DOCTOR');
                        $entity->removeRole('ROLE_PATIENT');
                    }

                    // Notify per email if user type changes to client
                    if ($entity->getType() === User::USER_TYPE_USER && $this->getParameter('enable_sendmail_user_discharge')) {
                        $this->sendUserMail($entity, 'discharge_patient');
                    }
                }

                // Check if enabled status changed
                if ($enabled !== $entity->isEnabled()) {

                    // APPROVED
                    if ($entity->isEnabled()) {

                        // Add roles when approved
                        if ($entity->isDoctor()) {
                            $entity->addRole('ROLE_DOCTOR');

                            // send approval link
                            $this->sendUserMail($entity, 'confirmed_doctor');
                            $isApproved = true;

                        }
                        else if ($entity->isPatient()) {
                            $entity->addRole('ROLE_PATIENT');
                        }

                        // Notify per email
                        if (!isset($isApproved) && $this->getParameter('enable_sendmail_user_unlock')) {
                            $this->sendUserMail($entity, 'unlock_user');
                        }
                    }
                    // DISAPPROVED
                    else if(!$entity->isEnabled()) {

                        // Remove roles when disapproved
                        if ($entity->isDoctor()) {
                            $entity->removeRole('ROLE_DOCTOR');
                        }
                        else if ($entity->isPatient()) {
                            $entity->removeRole('ROLE_PATIENT');
                        }

                        // Notify per email
                        if ($this->getParameter('enable_sendmail_user_lock')) {
                            $this->sendUserMail($entity, 'lock_user');
                        }
                    }
                }

                // Notify per email if patient gets new doctor (and still has a doctor)
                if ($this->getParameter('enable_sendmail_user_refer')) {

                    $oldDocs = array_diff($doctorIds, $entity->virtualDoctors());
                    $newDocs = array_diff($entity->virtualDoctors(), $doctorIds);

                    if (count($newDocs) || (count($oldDocs) && count($entity->virtualDoctors()))) {

                        // get names comma separated for template => "doc1, doc2, ... and docX"

                        $oldDocs = array_diff($doctorNames, $entity->virtualDoctorsNames());
                        $newDocs = array_diff($entity->virtualDoctorsNames(), $doctorNames);

                        $lastOldDoc = (count($oldDocs) <= 1) ? '' : ' and ' . array_pop($oldDocs);
                        $lastNewDoc = (count($newDocs) <= 1) ? '' : ' and ' . array_pop($newDocs);

                        $oldDocs = implode(', ', $oldDocs) . $lastOldDoc;
                        $newDocs = implode(', ', $newDocs) . $lastNewDoc;

                        $parameters = [
                            'oldDogs' => $oldDocs,
                            'newDogs' => $newDocs,
                        ];
                        $this->sendUserMail($entity, 'referred_patient', $parameters);
                    }
                }

                // use user manager to correctly handle password and canonical fields
                $userManager = $this->get('fos_user.user_manager');
                $userManager->updateUser($entity);

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
        catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a User entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, User $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a User entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, User $entity)
    {
        try {

            // Notify user that he was deleted
            if ($this->getParameter('enable_sendmail_user_delete')) {
                $this->sendUserMail($entity, 'delete_user');
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($entity);
            $em->flush();


            return null;
        }
        catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendUserMail(User $user, $templateName, $parameters = [])
    {
        try {
            $template = $this->get('twig')->loadTemplate(":email:$templateName.email.twig");

            $parameters['user'] = $user;

            $subject = $template->renderBlock('subject', $parameters);
            $bodyText = $template->renderBlock('body_text', $parameters);
            $bodyHtml = $template->renderBlock('body_html', $parameters);

            $message = new \Swift_Message();
            $message->setSubject($subject);
            $message->setBody($bodyText, 'text/plain');
            $message->addPart($bodyHtml, 'text/html');

            $message->setFrom($this->getParameter('default_from_email'), $this->getParameter('default_from_name'));
            $message->setTo($user->getEmail());

            $this->get('mailer')->send($message);
        }
        catch (Exception $e) {
            // ... sorry user, no info
            // TODO: log this somehow
            throw $e;
        }
    }
}
