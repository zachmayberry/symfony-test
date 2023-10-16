<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Form\TherapySessionType;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Voryx\RESTGeneratorBundle\Controller\VoryxController;


/**
 * UserTherapy custom controller.
 */
class TherapyCustomController extends VoryxController
{
    /**
     * Create therapy file for previewing.
     *
     * @Route("therapies/generatepreview/{id}/{hq}", name="create_therapy_preview")
     * @Method({"GET"})
     *
     * @param mixed $id
     * @param mixed $hq
     *
     * @return JsonResponse
     *
     */
    public function createTherapyPreview($id, $hq = 0)
    {
		$this->denyAccessUnlessGranted('ROLE_ADMIN');
		
        // cast hq value, otherwise its always true because its a string
        $generateHq = (int)$hq === 1 ? true : false;

        $async = $this->getParameter('api_server_async');

        // allow long execution time for synchronous and calls which can take a long time
        if (!$async) {
            set_time_limit(7200);
        }

        if ($therapy = $this->getDoctrine()->getRepository('AppBundle:TempTherapy')->find($id)) {

            try {
                $therapyService = $this->get('app.therapy_service');

                $filePath = $therapyService->generateTherapyFile(
                    'preview_' . $id,
                    $therapy->hasTonesPlaylist(),
                    $therapy->getMusicPlaylist(),
                    $therapy->getBinauralPlaylist(),
                    $this->get('app.beat_api_service')->getConfigurationDataFromTherapy($therapy, $generateHq),
                    $generateHq,
                    true,
                    false,
                    $async
                );

                return new JsonResponse([
                    'fileName' => basename($filePath),
                    //'fileSize' => filesize($filePath), // not available in async mode
                ]);
            }
            catch (\Exception $exception) {
                throw $exception;
            }
        }

        throw $this->createNotFoundException('The temp. Therapy does not exist');

    }
    /**
     * Check if preview file is ready and available on the server.
     *
     * @Route("therapies/checkifpreviewready/{fileName}", name="check_if_therapy_preview_ready")
     * @Method({"GET"})
     *
     * @param string $fileName
     *
     * @return JsonResponse
     *
     */
    public function checkIfTherapyPreviewIsReady($fileName)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $previewFolder = $this->getParameter('kernel.root_dir') . '/../' . $this->getParameter('library_therapy_previews_path');
        $filePath = $previewFolder . '/' . $fileName;

        if (file_exists($filePath)) {

            return new JsonResponse([
                'fileName' => $fileName,
                'fileSize' => filesize($filePath),
            ]);
        }

        throw $this->createNotFoundException('File not found.');
    }
}
