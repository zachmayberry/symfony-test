<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Activity;
use AppBundle\Entity\Playlist;
use AppBundle\Entity\PlaylistTrack;
use AppBundle\Entity\TempTherapy;
use AppBundle\Entity\Track;
use AppBundle\Form\TempTherapyType;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Voryx\RESTGeneratorBundle\Controller\VoryxController;

/**
 * TempTherapy controller.
 * @RouteResource("TempTherapy")
 */
class TempTherapyController extends VoryxController
{
    /**
     * Get a TempTherapy entity
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return Response
     *
     */
    public function getAction(TempTherapy $entity)
    {
        return $entity;
    }

    /**
     * Get all TempTherapy entities.
     *
     * @View(serializerEnableMaxDepthChecks=true)
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
        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:TempTherapy')
            ->findAllQueryBuilder($paramFetcher);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_temptherapies');

        return $paginatedCollection;
    }

    /**
     * Create a TempTherapy entity.
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postAction(Request $request)
    {
        $originalRequest = clone $request;

        $entity = new TempTherapy();
        $form = $this->createForm(get_class(new TempTherapyType()), $entity, array("method" => $request->getMethod()));
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

            // save to database
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Update a TempTherapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, TempTherapy $entity)
    {
        $originalRequest = clone $request;

        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new TempTherapyType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                $em = $this->getDoctrine()->getManager();

                // binaural playlist
                $binauralSettings = $originalRequest->get("binauralSettings");
                $binauralPlaylistData = $originalRequest->get("binauralPlaylist");
                $binauralPlaylist = new Playlist();
                $binauralPlaylist->setType(1);
                /*$binauralPlaylist = $entity->getBinauralPlaylist();
                if (!$binauralPlaylist) {
                    $binauralPlaylist = new Playlist();
                    $binauralPlaylist->setType(1);
                }
                else {
                    // empty existing playlist
                    $binauralPlaylist->setPlaylistTracks(new ArrayCollection());
                }*/
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
                /*$musicPlaylist = $entity->getMusicPlaylist();
                if (!$musicPlaylist) {
                    $musicPlaylist = new Playlist();
                    $musicPlaylist->setType(2);
                }
                else {
                    // empty existing playlist
                    $musicPlaylist->setPlaylistTracks(new ArrayCollection());
                }*/
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
                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a TempTherapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, TempTherapy $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a TempTherapy entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, TempTherapy $entity)
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
}
