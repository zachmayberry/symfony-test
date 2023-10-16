<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\TherapySession;
use AppBundle\Entity\UserTherapy;
use AppBundle\Form\TherapySessionType;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Voryx\RESTGeneratorBundle\Controller\VoryxController;


/**
 * TherapySession custom controller.
 */
class TherapySessionCustomController extends VoryxController
{
    /**
     * Create multiple TherapySession entities.
     *
     * @View(serializerEnableMaxDepthChecks=false)
     * @Route("therapysessions/batchcreate", name="post_therapysessions")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     */
    public function createSessionsAction(Request $request)
    {
        $arrCollection = new ArrayCollection();

        $em = $this->getDoctrine()->getManager();
        $errors = false;

        //$data = $request->request->all();
        $startTimes = $request->get('startTimes');

        // Iterate through rates/startTimes
        foreach($startTimes as $startTime) {

            $entities = [];

            // Create entity from form data
            $entity = new TherapySession();
            $userTherapy = $em->getRepository('AppBundle:UserTherapy')->find($request->get('userTherapy'));
            $entity->setUserTherapy($userTherapy);
            $entity->setStartDate($request->get('startDate'));
            $entity->setStartTime($startTime);

            $form = $this->createForm(get_class(new TherapySessionType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->persist($entity);
                $entities[] = $entity;
            }
            else {
                $errors = $form->getErrors();
            }
        }

        if ($errors) {
            return FOSView::create(array('errors' => $errors), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }

        $em->flush();

        return new JsonResponse([
            'items' => $entities,
        ]);
    }
}
