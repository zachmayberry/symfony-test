<?php

namespace AppBundle\Controller;

use AppBundle\Entity\TherapySession;
use AppBundle\Entity\Track;
use AppBundle\Entity\TrackLog;
use AppBundle\Entity\UserTherapy;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


/**
 * User controller.
 *
 * @Route("statistics")
 */
class StatisticsController extends Controller
{


    /**
     * Get online web clients
     *
     * @Route("/registereduserscount", name="statistics_registereduserscount")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function registeredUsersCountAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');

        $resultWebClients = (int)$userRepository->getRegisteredCount(0);
        $resultPatients = (int)$userRepository->getRegisteredCount(1);
        $resultDoctors = (int)$userRepository->getRegisteredCount(2);

        return new JsonResponse([
            'total' => $resultWebClients + $resultPatients + $resultDoctors,
            'clients' => $resultWebClients,
            'patients' => $resultPatients,
            'doctors' => $resultDoctors,
        ]);
    }


    /**
     * Get online web clients
     *
     * @Route("/activeuserscount/{type}", name="statistics_activeuserscount", requirements={"type" = "\d+"}, defaults={"type" = 0})
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function activeUsersCountAction()
    {
        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');

        $resultWebClients = $userRepository->getActiveCount(0);
        $resultPatients = $userRepository->getActiveCount(1);
        $resultDoctors = $userRepository->getActiveCount(2);

        return new JsonResponse([
            'total' => $resultWebClients + $resultPatients + $resultDoctors,
            'clients' => $resultWebClients,
            'patients' => $resultPatients,
            'doctors' => $resultDoctors,
        ]);
    }


    /**
     * Get total logins and registrations for this year
     *
     * @Route("/userstatistics", name="statistics_userstatistics")
     * @Method("GET")
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return JsonResponse
     */
    public function userStatisticsAction()
    {
        $result = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('user_log.eventType, COUNT(user_log) AS total, YEAR(user_log.createdAt) AS year, MONTH(user_log.createdAt) AS month')
            ->from('AppBundle:UserLog', 'user_log')
            ->where('YEAR(user_log.createdAt) = :year')
            ->setParameter('year', date('Y'))
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('user_log.eventType')
            ->orderBy('user_log.createdAt', 'DESC')
            ->getQuery()->getScalarResult();

        return new JsonResponse($result);
    }


    /**
     * Get therapy statistics
     *
     * @Route("/therapystatistics", name="statistics_therapystatistics")
     * @Method("GET")
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return JsonResponse
     */
    public function therapyStatisticsAction()
    {
        $sessionData = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('session.status, COUNT(session) AS total, YEAR(session.createdAt) AS year, MONTH(session.createdAt) AS month')
            ->from('AppBundle:TherapySession', 'session')
            ->where('YEAR(session.createdAt) = :year')
            ->setParameter('year', date('Y'))
            ->andWhere('session.status = :status')
            ->setParameter('status', TherapySession::STATUS_COMPLETED)
            ->orWhere('session.status = :status2')
            ->setParameter('status2', TherapySession::STATUS_MISSED)
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('session.status')
            ->getQuery()->getScalarResult();

        // Get completed sessions count from user therapies
//        $completedSessionsCount = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('COUNT(session)')
//            ->from('AppBundle:TherapySession', 'session')
//            ->where('session.status = :status')
//            ->setParameter('status', TherapySession::STATUS_COMPLETED)
//            ->getQuery()
//            ->getSingleScalarResult();

        // Get completed sessions count from therapies
        $completedSessionsCount = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(therapy.completedSessionsCounter)')
            ->from('AppBundle:Therapy', 'therapy')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        // Get missed sessions count from user therapies
//        $missedSessionsCount = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('COUNT(session)')
//            ->from('AppBundle:TherapySession', 'session')
//            ->where('session.status = :status')
//            ->setParameter('status', TherapySession::STATUS_MISSED)
//            ->getQuery()
//            ->getSingleScalarResult();

        // Get missed sessions count from therapies
        $missedSessionsCount = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(therapy.missedSessionsCounter)')
            ->from('AppBundle:Therapy', 'therapy')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

//        $sessionsCountsByStatus = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('session.status, COUNT(session) AS total')
//            ->from('AppBundle:TherapySession', 'session')
//            ->where('session.status = :status')
//            ->setParameter('status', TherapySession::STATUS_COMPLETED)
//            ->orWhere('session.status = :status2')
//            ->setParameter('status2', TherapySession::STATUS_MISSED)
//            ->addGroupBy('session.status')
//            ->getQuery()
//            ->getScalarResult();

        // Get total played time from users
//        $totalPlayTime = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('SUM(user.totalPlayTime)')
//            ->from('AppBundle:User', 'user')
//            ->setMaxResults(1)
//            ->getQuery()
//            ->getSingleScalarResult();

        // Get total played time from therapies
        $totalPlayTime = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(therapy.totalPlayedTime)')
            ->from('AppBundle:Therapy', 'therapy')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        // Get completed therapies count from user therapies
//        $completedTherapiesCount = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('COUNT(therapy)')
//            ->from('AppBundle:UserTherapy', 'therapy')
//            ->where('therapy.status = :status')
//            ->setParameter('status', UserTherapy::STATUS_COMPLETED)
//            ->getQuery()
//            ->getSingleScalarResult();

        // Get completed therapies count from therapies
        $completedTherapiesCount = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(therapy.completedTherapiesCounter)')
            ->from('AppBundle:Therapy', 'therapy')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        // Get active therapies count from user therapies
        $activeTherapiesCount = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(therapy)')
            ->from('AppBundle:UserTherapy', 'therapy')
            ->where('therapy.status = :status')
            ->setParameter('status', UserTherapy::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

//        $therapyCountsByStatus = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('therapy.status, COUNT(therapy) AS total')
//            ->from('AppBundle:UserTherapy', 'therapy')
//            ->where('therapy.status = :status')
//            ->setParameter('status', UserTherapy::STATUS_COMPLETED)
//            ->orWhere('therapy.status = :status2')
//            ->setParameter('status2', UserTherapy::STATUS_PENDING)
//            ->addGroupBy('therapy.status')
//            ->getQuery()
//            ->getScalarResult();

        return new JsonResponse([
            'history' => $sessionData,
            'totalPlayTime' => (int)$totalPlayTime * 60,
            'completedSessions' => (int)$completedSessionsCount,
            'missedSessions' => (int)$missedSessionsCount,
            'activeTherapies' => (int)$activeTherapiesCount,
            'completedTherapies' => (int)$completedTherapiesCount,
        ]);
    }


    /**
     * Get track statistics
     *
     * @Route("/trackstatistics", name="statistics_trackstatistics")
     * @Method("GET")
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return JsonResponse
     */
    public function trackStatisticsAction()
    {
        $trackData = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(tracklog.duration) as duration, COUNT(tracklog) AS total, YEAR(tracklog.createdAt) AS year, MONTH(tracklog.createdAt) AS month')
            ->from('AppBundle:TrackLog', 'tracklog')
            ->where('YEAR(tracklog.createdAt) = :year')
            ->setParameter('year', date('Y'))
            ->groupBy('year')
            ->addGroupBy('month')
            ->getQuery()->getScalarResult();

//        $totalPlayedTracksCount = $this->getDoctrine()->getManager()->createQueryBuilder()
//            ->select('COUNT(tracklog)')
//            ->from('AppBundle:TrackLog', 'tracklog')
//            ->where('tracklog.type != :type')
//            ->setParameter('type', Track::TYPE_TONE)
//            ->getQuery()
//            ->getSingleScalarResult();

        $totalPlayTimeAudio = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(tracklog.duration)')
            ->from('AppBundle:TrackLog', 'tracklog')
            ->where('tracklog.type != :type')
            ->setParameter('type', Track::TYPE_TONE)
            ->getQuery()
            ->getSingleScalarResult();

        $totalPlayTimeTones = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('SUM(tracklog.duration)')
            ->from('AppBundle:TrackLog', 'tracklog')
            ->where('tracklog.type = :type')
            ->setParameter('type', Track::TYPE_TONE)
            ->getQuery()
            ->getSingleScalarResult();

        $tracksCountByTrackId = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(tracklog) AS total, tracklog.trackSavedId')
            ->from('AppBundle:TrackLog', 'tracklog')
            ->addGroupBy('tracklog.trackSavedId')
            ->getQuery()
            ->getScalarResult();

        $tracksCountByTrackIdSorted = [];
        foreach($tracksCountByTrackId as $track) {
            $tracksCountByTrackIdSorted[$track['trackSavedId']] = (int)$track['total'];
        }

        /*$qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $AllTracks = $qb
            ->select('track.id, track.fileName, track.fileNameHq, track.title, track.artist, artist.name')
            ->from('AppBundle:Track', 'track')
            ->leftJoin('AppBundle:Artist', 'artist', 'ON', 'track.artist = artist.id')
            //->where('artist.id = track.artist')
            //->rightJoin('')
            ->getQuery()
            ->getScalarResult();*/


        return new JsonResponse([
            'history' => $trackData,
            //'totalPlayedTracksCount' => (int)$totalPlayedTracksCount,
            'totalPlayTimeAudio' => (int)$totalPlayTimeAudio,
            'totalPlayTimeTones' => (int)$totalPlayTimeTones,
            'tracksCountByTrackId' => $tracksCountByTrackIdSorted,
            //'allTracks' => $AllTracks,
        ]);
    }


    /**
     * Get online web clients
     *
     * @Route("/activeuserscount/{type}", name="statistics_activeuserscount", requirements={"type" = "\d+"}, defaults={"type" = 0})
     * @Method("GET")
     *
     * @return JsonResponse
     */
    /*public function totalPlayedTimeAction()
    {
        $this->getDoctrine()->getManager()
        $qb = $this->getDoctrine()->getManager()->cre
            ->getRepository('AppBundle:Track')
            ->findAllQueryBuilder()
            ->select('track', 'track_log')
            ->from('AppBundle:TrackLog', 'track_log')
            ->leftJoin('a.user', 'u')
            ->where('u = :user')
            ->setParameter('user', $users)
            ->orderBy('a.created_at', 'DESC');
            ->leftJoin('')
            ->select('SUM(track_log.numberPrinted) as fortunesPrinted');

        return new JsonResponse([
            'total' => $resultWebClients + $resultPatients + $resultDoctors,
            'clients' => $resultWebClients,
            'patients' => $resultPatients,
            'doctors' => $resultDoctors,
        ]);
    }*/


    /**
     * Get track logs in xml format
     *
     * @Route("/tracks", name="statistics_tracks")
     * @Method("GET")
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @QueryParam(name="page", requirements="\d+", nullable=true, description="Which page to return when more than limit.")
     * @QueryParam(name="limit", requirements="\d+", default="9999", description="How many notes to return.")
     * @QueryParam(name="order_by", nullable=true, array=true, description="Sorting by fields. Must be an array ie. &order_by[name]=ASC&order_by[description]=DESC")
     * @QueryParam(name="filters", nullable=true, array=true, description="Filter by fields. Must be an array ie. &filters[id]=3")
     *
     * @return Response
     */
    public function tracksAction(ParamFetcherInterface $paramFetcher)
    {
        // get filters
        $filters = $paramFetcher->get('filters');

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:TrackLog')
            ->findAllQueryBuilder($paramFetcher);

//        $data = $qb->getQuery()->getArrayResult();
//        var_dump($data);die;

        $data = $qb->getQuery()->getResult();

        // get time period
        $from = isset($filters['from']) ? $filters['from'] : '';
        $till = isset($filters['till']) ? $filters['till'] : '';

        // get track type
        //$type = isset($filters['type']) ? $filters['type'] : '';

        if (!$data) {

            $fromStr = $from ? "from " . $from : '';
            $tillStr = $till ? "till ". $till : '';

            $response = new Response();
            if ($from || $till) {
                $response->setContent("No tracks found for the time period $fromStr $tillStr");
            }
            else {
                $response->setContent("No tracks found according to your selection.");
            }
            return $response;
        }

        // Iterate over items
        /** @var TrackLog $trackLog */
        foreach($data as $trackLog) {

            /** @var Track $sourceTrack */
            $sourceTrack = $trackLog->getTrack();
            $trackId = $trackLog->getTrackSavedId();

            $isTone = $trackLog->getType() === Track::TYPE_TONE;

            $trackDuration = floor($trackLog->getDuration());

            if ( !isset($result[$trackId]) ) {

                $result[$trackId] = [
                    'Track ID' => $sourceTrack ? $sourceTrack->getId() : $trackId,
                    'SourceAudio ID' => $isTone ? "n/a" : ($sourceTrack ? $sourceTrack->getSourceAudioId() : ''),
                    'Track Type' => ucfirst(Track::getTypeString($trackLog->getType())),
                    'Track Name' => $trackLog->getTitle(),
                    'Album Name' => $sourceTrack ? $sourceTrack->getAlbum() : '',
                    'Artist' => $sourceTrack ? $sourceTrack->getArtistsString() : '',
                    'Composer' => $sourceTrack ? $sourceTrack->getComposersString() : '',
                    'Publisher' => $sourceTrack ? $sourceTrack->getPublishersString() : '',
                    'Genre' => $sourceTrack ? $sourceTrack->getGenreTitle() : '',
                    //'Symptoms' => $sourceTrack ? $sourceTrack->getSymptomsString() : '',
                    //'Description' => $sourceTrack ? $sourceTrack->getDescription() : '',
                    //'Moods' => $sourceTrack ? $sourceTrack->getMoods() : '',
                    //'Therapy' => $trackLog->getTherapyTitle(),
                    //'Therapy ID' => $trackLog->getTherapy()->getId(),
                    'Track Length' => $isTone ? 'n/a' : gmdate("H:i:s", $trackDuration),
                    'Listen Count' => $isTone ? 'n/a' : 0,
                    'Listen Time' => 0,
                ];
            }

            $result[$trackId]['Listen Time'] += $trackDuration;

            // only count for non-tones (since tones can vary in duration)
            if (!$isTone) {
                $result[$trackId]['Listen Count'] ++;
            }
        }

        // Iterate over result items and add additional data
        foreach($result as $key => $item) {

            $result[$key]['Listen Time'] = gmdate("H:i:s", $result[$key]['Listen Time']);
            //$result[$key]['Listen Time'] = round(($result[$key]['Listen Time'] / 60), 2) . ' min';

            $result[$key]['From'] = $this->formatDate($from);
            $result[$key]['Till'] = $this->formatDate($till);
        }

        // Sort by type and listen time
        array_multisort(
            array_column($result, 'Track Type'),  SORT_ASC,
            array_column($result, 'Listen Time'), SORT_DESC,
            $result
        );


        ////////////////////////////////////////////

        $csvHeader = [
            'Track ID',
            'SourceAudio ID',
            'Track Type',
            'Track Name',
            'Album Name',
            'Artist',
            'Composer',
            'Publisher',
            'Genre',
            //'Symptoms',
            //'Description',
            //'Moods',
            //'Therapy',
            //'Therapy ID',
            'Track Length',
            'Listen Count',
            'Listen Time',
            'From',
            'Till',
        ];

        // generate file name for export
        $targetFolder = $this->getParameter('kernel.root_dir') . '/../' . $this->getParameter('track_statistic_export_path');
        $fileName = $this->getTrackExportFileName($from, $till, 'csv');
        $filePath = $targetFolder . '/' . $fileName;


        // create CSV
        if (!$fp = fopen($filePath,"w")) {
            throw new Exception("Could not write file. Please check write permissions for track_statistic_export_path.");
        }

        // Add BOM Header (https://stackoverflow.com/questions/25686191/adding-bom-to-csv-file-using-fputcsv)
        $BOM = "\xEF\xBB\xBF"; // UTF-8 BOM
        fwrite($fp, $BOM);

        // Set seperator (always open with correct delimiter settings in excel)
//        fwrite($fp, 'sep=,' . "\r\n"); // NOT WORKING TOGETHER WITH BOM HEADER

        // write header row
        fputcsv($fp, $csvHeader);
        //fputs($fp, implode($csvHeader, ';')."\n");

        // write rows
        foreach($result as $data) {
            fputcsv($fp, $data);
//            fputs($fp, implode($data, ';')."\n");
        }

        fclose($fp);

        // force file download
        $response = new BinaryFileResponse($filePath);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            iconv('UTF-8', 'ASCII//TRANSLIT', $fileName)
        );

        return $response;
    }


    /**
     * Get file name for track export
     *
     * @param $from
     * @param $till
     * @param $format
     * @return string
     */
    public function getTrackExportFileName($from, $till, $format)
    {
        return sprintf(
            'HealthTunes_Track-Statistics%s%s.%s',
            $from ? '_from_' . $from : '',
            $till ? '_till_' . $till : '',
            $format
        );
    }


    /**
     * @param $dateStr
     * @return string
     */
    public function formatDate($dateStr)
    {
        if (!$dateStr) {
            return '';
        }

        $date = new \DateTime($dateStr);
        return $date->format('d/m/Y');
    }
}
