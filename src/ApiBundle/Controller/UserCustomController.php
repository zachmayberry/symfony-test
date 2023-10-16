<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\FavouriteTherapy;
use AppBundle\Entity\Therapy;
use AppBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Voryx\RESTGeneratorBundle\Controller\VoryxController;

/**
 * User controller.
 */
class UserCustomController extends VoryxController
{
    /**
     * Get a user's profile image thumbnail
     *
     * @View(serializerEnableMaxDepthChecks=false)
     * @Route("users/avatar/{userId}/{size}", name="userAvatar")
     *
     * @return JsonResponse
     *
     */
    public function avatarAction($userId, $size = 'small')
    {
        if (!in_array($size, ['small', 'medium', 'large'])) {
            throw new HttpException(404, "Invalid size. Possible values: small, medium or large");
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User does not exist');
        }

        $thumbnail = $this->get('app.user_avatar')->getAvatar($user, 'medium');

        // return as json object
        return new JsonResponse($thumbnail);
    }

    /**
     * Add favourite therapy
     *
     * @View(serializerEnableMaxDepthChecks=true)
     * @Route("users/_addfavouritetherapy_", name="addFavouriteTherapy")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    /*public function addFavouriteTherapyAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // get current user and user to add therapy to
        $currentUser = $this->getUser();
        $user = $em->getRepository(User::class)->find($request->get('user'));

        if (!$user->getId() !== $currentUser->getId()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $therapy = $em->getRepository(Therapy::class)->find($request->get('therapy'));

        if (!$user || !$therapy) {
            throw $this->createNotFoundException('User or therapy does not exist');
        }

        // add therapy
        $favouriteTherapy = new FavouriteTherapy();
        $favouriteTherapy->setUser($user);
        $favouriteTherapy->setTherapy($therapy);
        $favouriteTherapy->setTitle($request->get('title', 'Unnamed Therapy'));
        $user->getFavouriteTherapies()->add($favouriteTherapy);
        $em->persist($user);
        $em->flush();

        // return as json object
        return new JsonResponse($favouriteTherapy->getData());
    }*/

    /**
     * Get favourite therapies
     *
     * @View(serializerEnableMaxDepthChecks=false)
     * @Route("users/_favouritetherapies_", name="favouriteTherapies")
     *
     * @param mixed $userId
     *
     * @return JsonResponse
     */
    /*public function favouriteTherapiesAction($userId)
    {
        $user = $this->getUser();
        if ($userId && $userId != $user->getId()) {
            $this->denyAccessUnlessGranted(['ROLE_ADMIN', 'ROLE_DOCTOR']);
            $user = $em->getRepository('AppBundle:User')->find($userId);
        }

        if (!$user) {
            throw $this->createNotFoundException('User does not exist');
        }

        $arrReturn = [];
        foreach ($user->getFavouriteTherapies() as $therapy) {
            $arrReturn[] = $therapy->getData();
        }

        // return as json object
        return new JsonResponse($arrReturn);
    }*/
}
