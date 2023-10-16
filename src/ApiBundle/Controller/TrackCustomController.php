<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Track;
use AppBundle\Form\TrackType;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Voryx\RESTGeneratorBundle\Controller\VoryxController;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;

/**
 * Track controller.
 */
class TrackCustomController extends VoryxController
{

    /**
     * Update tracl
     *
     * @View(serializerEnableMaxDepthChecks=true)
     * @Route("tracks/update/{id}", name="updateTrack")
     *
     * @param Request $request
     * @param $entity
     *
     * @return JsonResponse
     */
    public function updateTrack(Request $request, $id, Track $entity)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        $track = $em->getRepository(Track::class)->find($id);

        if (!$track) {
            throw $this->createNotFoundException('Track not found');
        }

        try {
            //$request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new TrackType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->flush();

                return $entity;
            }

            return new JsonResponse($entity->getData()); // return as json object
            //return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse($entity->getData());
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
