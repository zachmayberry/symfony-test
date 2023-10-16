<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Activity;
use AppBundle\Entity\Playlist;
use AppBundle\Entity\PlaylistTrack;
use AppBundle\Entity\Therapy;
use AppBundle\Entity\Track;
use AppBundle\Form\TherapyType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\UnitOfWork;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use ProxyManager\Proxy\AccessInterceptorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Voryx\RESTGeneratorBundle\Controller\VoryxController;

/**
 * Therapy controller.
 * @RouteResource("Therapy")
 */
class TherapyController extends VoryxController
{
    /**
     * Get a Therapy entity
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @return Response
     *
     */
    public function getAction(Therapy $entity)
    {
        return $entity;
    }

    /**
     * Get all Therapy entities.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "List"})
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @QueryParam(name="page", requirements="\d+", nullable=true, description="Which page to return when more than limit.")
     * @QueryParam(name="limit", requirements="\d+", default="9999", description="How many notes to return.")
     * @QueryParam(name="order_by", nullable=true, array=true, description="Sorting by fields. Must be an array ie. &order_by[name]=ASC&order_by[description]=DESC")
     * @QueryParam(name="filters", nullable=true, array=true, description="Filter by fields. Must be an array ie. &filters[id]=3")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $user = $this->getUser();

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:Therapy')
            ->findAllQueryBuilder($paramFetcher, $user);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_therapies');

        return $paginatedCollection;
    }

    /**
     * Create a Therapy entity.
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

        $entity = new Therapy();
        $form = $this->createForm(get_class(new TherapyType()), $entity, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {

            // check if we only want to validate the form without submitting it
            if ($originalRequest->get('validateOnly')) {
                return FOSView::create(null, Codes::HTTP_OK);
            }

            $em = $this->getDoctrine()->getManager();

            // binaural playlist
            $binauralPlaylistData = $originalRequest->get("binauralPlaylist");
            $binauralSettings = $originalRequest->get("binauralSettings");
            $binauralPlaylist = new Playlist();
            $binauralPlaylist->setType(1);
            if (is_array($binauralPlaylistData)) {
                foreach($binauralPlaylistData as $key => $trackId) {

                    if ($track = $em->getRepository(Track::class)->find($trackId)) {
                        $playlistTrack = new PlaylistTrack();
                        $playlistTrack->setSorting($key); // $binauralSorting[$key]
                        $playlistTrack->setDuration($binauralSettings[$key]['duration'] * 60);
                        $playlistTrack->setTrack($track);
                        $playlistTrack->setPlaylist($binauralPlaylist);
                        $binauralPlaylist->getPlaylistTracks()->add($playlistTrack);
                    }
                }
            }
            $entity->setBinauralPlaylist($binauralPlaylist);


            // music playlist
            $musicPlaylistData = $originalRequest->get("musicPlaylist");
            $musicPlaylist = new Playlist();
            $musicPlaylist->setType(2);
            if (is_array($musicPlaylistData)) {
                foreach ($musicPlaylistData as $key => $trackId) {

                    if ($track = $em->getRepository(Track::class)->find($trackId)) {
                        $playlistTrack = new PlaylistTrack();
                        $playlistTrack->setSorting($key); // $binauralSorting[$key]
                        $playlistTrack->setTrack($track);
                        $playlistTrack->setPlaylist($musicPlaylist);
                        $musicPlaylist->getPlaylistTracks()->add($playlistTrack);
                    }
                }
            }
            $entity->setMusicPlaylist($musicPlaylist);

            $em->persist($entity);

            // Create activity log
            $this->createActivityLogForTherapy($entity);

            // save to database
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Update a Therapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, Therapy $entity)
    {
        $originalRequest = clone $request;

        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new TherapyType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                $em = $this->getDoctrine()->getManager();

                // binaural playlist
                if (Therapy::checkIfTypeHasTonePlaylist($entity->getType())) {

                    $binauralSettings = $originalRequest->get("binauralSettings");
                    $binauralPlaylistData = $originalRequest->get("binauralPlaylist");

                    // only update if playlist items have changed
                    /** @var Playlist $oldPlaylist */
                    $oldPlaylist = $entity->getBinauralPlaylist();
                    $hasChanges = Playlist::comparePlaylistWithFormData($oldPlaylist, $binauralPlaylistData, $binauralSettings);

                    if ($hasChanges) {

                        $binauralPlaylist = new Playlist();
                        $binauralPlaylist->setType(Playlist::TYPE_TONE);

                        if (is_array($binauralPlaylistData)) {
                            foreach($binauralPlaylistData as $key => $trackId) {

                                if ($track = $em->getRepository(Track::class)->find($trackId)) {
                                    $playlistTrack = new PlaylistTrack();
                                    $playlistTrack->setSorting($key); // $binauralSorting[$key]
                                    $playlistTrack->setDuration($binauralSettings[$key]['duration'] * 60);
                                    $playlistTrack->setTrack($track);
                                    $playlistTrack->setPlaylist($binauralPlaylist);
                                    $binauralPlaylist->getPlaylistTracks()->add($playlistTrack);
                                }
                            }
                        }
                        $entity->setBinauralPlaylist($binauralPlaylist);
                    }
                }


                // music playlist
                if (Therapy::checkIfTypeHasMusicPlaylist($entity->getType())) {

                    $musicPlaylistData = $originalRequest->get("musicPlaylist");

                    // only update if playlist items have changed
                    /** @var Playlist $oldPlaylist */
                    $oldPlaylist = $entity->getMusicPlaylist();
                    $hasChanges = Playlist::comparePlaylistWithFormData($oldPlaylist, $musicPlaylistData);

                    if ($hasChanges) {

                        $musicPlaylist = new Playlist();
                        $musicPlaylist->setType(Playlist::TYPE_MUSIC);

                        if (is_array($musicPlaylistData)) {
                            foreach ($musicPlaylistData as $key => $trackId) {

                                if ($track = $em->getRepository(Track::class)->find($trackId)) {
                                    $playlistTrack = new PlaylistTrack();
                                    $playlistTrack->setSorting($key); // $binauralSorting[$key]
                                    $playlistTrack->setTrack($track);
                                    $playlistTrack->setPlaylist($musicPlaylist);
                                    $musicPlaylist->getPlaylistTracks()->add($playlistTrack);
                                }
                            }
                        }
                        $entity->setMusicPlaylist($musicPlaylist);
                    }
                }

//                // update publish date of activity log if therapy gets published
//                /** @var Activity $activity */
//                $activity = $em->getRepository(Activity::class)->findOneByTherapy($entity);
//
//                if ($activity === null) {
//                    $this->createActivityLogForReference($entity);
//                }
//                else if ($entity->getPublic()) {
//
//                    // check if was public before
//                    /** @var UnitOfWork $uow */
//                    $uow = $em->getUnitOfWork();
//                    $uow->computeChangeSets(); // do not compute changes if inside a listener
//                    $changeSet = $uow->getEntityChangeSet($entity);
//
//                    // only update activity publish date if therapy is public
//                    if (isset($changeSet['public'])) {
//                        $activity->setPublishedAt(new \DateTime());
//                    }
//                }

                //$entity->setCompileStatus(Therapy::STATUS_UNCOMPILED); // done in preUpdate hook in entity

                // update here instead of preUpdate hook
                $entity->setUpdatedAt(new \DateTime());

                $em->persist($entity);
                $em->flush();
                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a Therapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, Therapy $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a Therapy entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, Therapy $entity)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($entity);
            $em->flush();

            return null;
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create Activity entry for a given news object
     *
     * @param Therapy $entity
     * @param boolean $doFlush
     * @return Activity
     */
    public function createActivityLogForTherapy(Therapy $entity, $doFlush = false)
    {
        $em = $this->getDoctrine()->getManager();

        $activity = new Activity();

        $activity->setType(Activity::TYPE_THERAPY);
        $activity->setTherapy($entity);
        $activity->setUser($this->getUser());
        $activity->setTitle($entity->getTitle());

        $em->persist($activity);

        if ($doFlush === true) {
            $em->flush();
        }

        return $activity;
    }
}
