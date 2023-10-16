<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\Track;
use AppBundle\Entity\UploadedAudio;
use phpseclib\Net\SFTP;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AdminController extends Controller
{
    /**
     * Endpoint to request therapy and track file downloads
     *
     * @Route("/download", name="download")
     * @Method({"GET"})
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function downloadAction(Request $request)
    {
        $file = $request->query->get('file');
        $hq = $request->query->get('hq');

        $therapyId = $request->query->get('therapy');
        if ($therapyId && $therapy = $this->getDoctrine()->getRepository(Therapy::class)->find($therapyId)) {
            $file = $hq ? $therapy->getPublicFilePathHq() : $therapy->getPublicFilePath();
        }

        $absoluteFilePath = $this->get('kernel')->getRootDir() . '/../web/' . $file;

        // check if file is available
        if (!is_file($absoluteFilePath)) {
            throw $this->createNotFoundException('The requested file could not be found!');
        }

        // overwrite filename
        if ($filename = $request->query->get('name')) {
            $path_info = pathinfo($file);
            $exportFileName = $filename . '.' . $path_info['extension'];
        } else {
            $exportFileName = basename($absoluteFilePath);
        }

        // prepare BinaryFileResponse
        $response = new BinaryFileResponse($absoluteFilePath);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $exportFileName,
            iconv('UTF-8', 'ASCII//TRANSLIT', $exportFileName)
        );

        return $response;
    }

    /**
     * Upload files to tmp directory
     *
     * @Route("/upload", name="upload")
     * @Method({"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // disable execution timeout for uploads
        set_time_limit(0);

        //$data = $request->request->all();
        $files = $request->files;

        $arrUploadedFiles = [];

        foreach ($files as $key => $file) {
            $arrUploadedFiles[] = $this->get('app.tmp_file_uploader')->upload($file);
        }

        return new JsonResponse([
            'files' => $arrUploadedFiles
        ]);
    }

    /**
     * Endpoint for async API-Callback
     *
     * @Route("/api-callback", name="api_callback")
     * @Method({"POST"})
     * @param Request $request
     * @return JsonResponse
     * @throws \InvalidArgumentException|\Exception
     */
    public function aapiCallbackAction(Request $request)
    {
        $logger = $this->get('monolog.logger.beat_api');

        $callStartTime = microtime(true);
        $fileSize = 0;
        $fileName = '';
        $message = '';

        $type = $request->get('type', null);
        $id = (int)$request->get('id');
        $hq = (int)$request->get('hq');
        $file = $request->get('file');
        $status = trim($request->get('status'));

        if (!$type || !in_array($type, array('therapy', 'session', 'audio'))) {

            $logger->critical(sprintf("API Callback Error: Unknown type given. Expected therapy, session or audio, but got '%s'", $type), $request->request->all());
            throw new \InvalidArgumentException(
                sprintf("Unknown type given. Expected therapy, session or audio, but got '%s'", $type)
            );
        }

        if (!$id) {
            $logger->critical("API Callback Error: No id given!", $request->request->all());
            throw new \InvalidArgumentException("No id given");
        }

        if (!$file) {
            $logger->critical("API Callback Error: No file given!", $request->request->all());
            throw new \InvalidArgumentException("No file given");
        }

        $logger->info("----> API Callback:", $request->request->all());

        // Get doctrine entity manager
        $em = $this->getDoctrine()->getManager();

        switch ($type) {

            case "therapy":

                // Check if is preview therapy
                if (strpos($file, 'preview_') === 0) {

                    // Copy file or throw error
                    if (!$this->copyFileFromApiServer($file, $this->getParameter('library_therapy_previews_path'))) {
                        $logger->critical("File $file could not be found on remote server.");
                        throw new \Exception("File $file could not be found on remote server.");
                    }

                    // quit since we just want to copy the file
                    break;
                }

                // Find therapy by id (if not preview)
                /** @var Therapy $therapy */
                $therapy = $em->getRepository(Therapy::class)->find($id);

                if (null === $therapy) {
                    $logger->critical("Therapy with ID $id could not be found in the database.");
                    throw new \Exception("Therapy with ID $id could not be found in the database.");
                }

                // Check status of callback
                if ($status !== 'ok') {

                    // this is essential for live update in TherapyList react component
                    $therapy->setUpdatedAt(new \DateTime());

                    if ($hq) {
                        $therapy->setCompileStatusHq(Therapy::STATUS_COMPILE_ERROR);
                    } else {
                        $therapy->setCompileStatus(Therapy::STATUS_COMPILE_ERROR);
                    }

                    $logger->critical("API Callback failed!", $request->request->all());
                    throw new \Exception("File for therapy with ID $id not written since status of callback was not OK!");
                }

                // Copy file
                if ($localFilePath = $this->copyFileFromApiServer($file, $this->getParameter('library_therapies_path'))) {
                    $fileName = basename($localFilePath);
                    $fileSize = filesize($localFilePath);

                } else {
                    $logger->critical("File $file could not be found on remote server.");
                    throw new \Exception("File $file could not be found on remote server.");
                }

                if ($hq) {
                    $therapy->setFileNameHq($fileName);
                    $therapy->setFileSizeHq($fileSize);
                    $therapy->setCompileStatusHq(Therapy::STATUS_COMPILED);

                    $message = "Updated HQ file for therapy {$id}: $fileName";

                } else {
                    $therapy->setFileName($fileName);
                    $therapy->setFileSize($fileSize);
                    $therapy->setCompileStatus(Therapy::STATUS_COMPILED);

                    $message = "Updated audible file for therapy {$id}: $fileName";

                    // Create therapy preview file from audible version
                    $this->get('app.beat_api_service')->generatePreviewFile($localFilePath, Therapy::getPreviewFileName($localFilePath));
                }


                // this is essential for live update in TherapyList react component
                $therapy->setUpdatedAt(new \DateTime());

                $em->persist($therapy);
                $em->flush();

                break;

            case "session":

                $therapySessionRepository = $em->getRepository(TherapySession::class);

                // Find session by id
                /** @var TherapySession $therapySession */
                $therapySession = $therapySessionRepository->find($id);

                if (null === $therapySession) {
                    $logger->critical("Session with ID $id could not be found in the database.");
                    throw new \Exception("Session with ID $id could not be found in the database.");
                }

                // Check status of callback
                if ($status !== 'ok') {

                    if ($hq) {
                        $therapySession->setCompileStatusHq(Therapy::STATUS_COMPILE_ERROR);
                    } else {
                        $therapySession->setCompileStatus(Therapy::STATUS_COMPILE_ERROR);
                    }

                    $logger->critical("API Callback failed!", $request->request->all());
                    throw new \Exception("File for session with ID $id not written since status of callback was not OK!");
                }

                // Copy file
                if ($localFilePath = $this->copyFileFromApiServer($file, $this->getParameter('library_therapies_path'))) {
                    $fileName = basename($localFilePath);
                    $fileSize = filesize($localFilePath);

                } else {
                    $logger->critical("File $file could not be found on remote server.");
                    throw new \Exception("File $file could not be found on remote server.");
                }

                if ($hq) {
                    $therapySession->setFileNameHq($fileName);
                    $therapySession->setFileSizeHq($fileSize);
                    $therapySession->setCompileStatusHq(Therapy::STATUS_COMPILED);

                    $message = "Updated HQ file for session {$id}: $fileName" . "\r\n";

                } else {
                    $therapySession->setFileName($fileName);
                    $therapySession->setFileSize($fileSize);
                    $therapySession->setCompileStatus(Therapy::STATUS_COMPILED);

                    $message = "Updated audible file for session {$id}: $fileName" . "\r\n";
                }

                $em->persist($therapySession);

                $siblingSessions = $therapySessionRepository->findPendingSiblingSession($therapySession);

                foreach ($siblingSessions as $siblingSession) {

                    if ($hq) {
                        $siblingSession->setFileNameHq($file);
                        $siblingSession->setFileSizeHq($fileSize);
                        $siblingSession->setCompileStatusHq(Therapy::STATUS_COMPILED);

                    } else {
                        $siblingSession->setFileName($file);
                        $siblingSession->setFileSize($fileSize);
                        $siblingSession->setCompileStatus(Therapy::STATUS_COMPILED);
                    }

                    $em->persist($siblingSession);
                }

                if (count($siblingSessions)) {
                    $message .= " and updated " . count($siblingSessions) . " Sessions accordingly";
                }

                $em->flush();

                break;

            case "audio":

                // Find uploaded audio by id
                /** @var UploadedAudio $therapy */
                $uploadedAudio = $em->getRepository(UploadedAudio::class)->find($id);

                if (null === $uploadedAudio) {
                    $logger->critical("UploadedAudio with ID $id could not be found in the database.");
                    throw new \Exception("UploadedAudio with ID $id could not be found in the database.");
                }

                // Check status of callback
                if ($status !== 'ok') {

                    // this is essential for live update in TherapyList react component
                    $uploadedAudio->setUpdatedAt(new \DateTime());
                    $uploadedAudio->setCompileStatus(UploadedAudio::STATUS_COMPILE_ERROR);

                    $logger->critical("API Convert-Callback failed!", $request->request->all());
                    throw new \Exception("File for uploaded audio with ID $id not written since status of callback was not OK!");
                }

                // Copy file from API server to local server
                if ($localFilePath = $this->copyFileFromApiServer($file, $this->getParameter('converted_audio_path'))) {
                    $fileName = basename($localFilePath);
                    $fileSize = filesize($localFilePath);

                    $uploadedAudio->setConvertedFile($fileName);
                    $uploadedAudio->setCompileStatus(Therapy::STATUS_COMPILED);

                    $message = "Converted uploaded audio {$id}: $fileName";

                    // this is essential for live update in TherapyList react component
                    $uploadedAudio->setUpdatedAt(new \DateTime());

                    // if the uploaded audio has a related track, update the track accordingly
                    /** @var Track $track */
                    if ($track = $uploadedAudio->getTrack()) {

                        $uploadedFile = new UploadedFile($localFilePath, $uploadedAudio->getConvertedFile(), null, null, null, true);
                        $track->setFile($uploadedFile);
                        $track->setOriginalFilename($uploadedAudio->getOriginalFileName());
                        $track->setCompileStatus(Track::STATUS_COMPILED);

                        // delete the uploaded audio
                        $em->remove($uploadedAudio);
                    }
                    // only persist uploaded audio
                    else {
                        $em->persist($uploadedAudio);
                    }

                    // save to database
                    $em->flush();

                    // delete unconverted file after successful conversion
                    $rootPath = $this->getParameter('kernel.root_dir') . '/../';
                    $unconvertedFile = $rootPath . $this->getParameter('uploaded_audio_path') . '/' . $uploadedAudio->getFileName();

                    if (is_file($unconvertedFile)) {
                        unlink($unconvertedFile);
                    }

                } else {
                    $logger->critical("Converted file $file could not be found on remote server.");
                    throw new \Exception("Converted file $file could not be found on remote server.");
                }

                break;
        }

        $callTime = microtime(true) - $callStartTime;

        return new JsonResponse([
            "file" => $fileName,
            "file_size" => $fileSize,
            "process_duration" => sprintf('%.2f', $callTime) . ' seconds',
            "message" => $message,
        ]);
    }


    /**
     * Copy file from API server to local server and return local file path
     *
     * @param string $filename
     * @param string $targetFolder
     * @return string|null
     */
    private function copyFileFromApiServer($filename, $targetFolder)
    {
        // disable execution timeout for copy job
        set_time_limit(0);

        // Get server information from config
        $apiServer = [
            "host" => $this->getParameter('api_server_host'),
            "port" => $this->getParameter('api_server_port'),
            "username" => $this->getParameter('api_server_username'),
            "password" => $this->getParameter('api_server_password'),
            "folder" => $this->getParameter('api_server_folder'),
        ];

        // Prepare target variables
        $projectRoot = $this->getParameter('kernel.root_dir') . '/../';
        $targetPath = $projectRoot . $targetFolder;
        $targetFile = $targetPath . '/' . $filename;

        // Copy from local root in development environment
        if (false && $this->getParameter("kernel.environment") === 'dev') {
            $sourceFile = $projectRoot . '/' . $this->getParameter('uploaded_audio_path') . '/' . $filename;
            $success = copy($sourceFile, $targetFile);
        }
        // Copy from external server
        else {

            $sourceFile = $apiServer['folder'] . '/' . $filename;

            // via phpseclib
            $sftp = new SFTP($apiServer['host']);
            if (!$sftp->login($apiServer['username'], $apiServer['password'])) {
                throw new AuthenticationException('Login on remote server failed for username ' . $apiServer['username']);
            }

            // copy with different name so that its not downloaded or played as filepart
            $success = $sftp->get($sourceFile, $targetFile . '.filepart');

            // rename after copy job
            rename($targetFile . '.filepart', $targetFile);

            // delete file from remote export folder
            if ($success) {
				
                $sftp->delete($sourceFile);
            }

            // via ssh2
    //        $connection = ssh2_connect($apiServer['host'], $apiServer['port']);
    //        ssh2_auth_password($connection, $apiServer['username'], $apiServer['password']);
    //        ssh2_scp_recv($connection, $sourceFile, $targetFile);

        }

        return $success ? $targetFile : null;
    }

    /**
     * Export audio to CSV and download as file
     *
     * @Route("/download-csv/{type}", name="download_csv", defaults={"type" = "audio"})
     * @param string $type
     * @param string $type
     * @return BinaryFileResponse
     * @throws \InvalidArgumentException|\Exception
     */
    public function downloadAudioCsvAction($type)
    {
        if (!in_array($type, ['audio', 'tones'])) {
            throw new \InvalidArgumentException('Wrong type given. Choose between "audio" or "tones"');
        }

        // prevent execution timeouts
        set_time_limit(300);

        $databaseService = $this->get('app.database_service');

        // get next increment ID
        $incrementId = $databaseService->getDatabaseExportIncrementIdByType($type, true);

        // generate CSV
        $result = $databaseService->exportCsv($type, $incrementId);

        // check if file is available
        if (!isset($result['filePath']) || !is_file($result['filePath'])) {
            throw $this->createNotFoundException('The requested file could not be found!');
        }

        // prepare BinaryFileResponse
        $response = new BinaryFileResponse($result['filePath']);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $result['fileName'],
            iconv('UTF-8', 'ASCII//TRANSLIT', $result['fileName'])
        );

        return $response;
    }

    /**
     * Upload files to tmp directory
     *
     * @Route("/upload-csv", name="upload_csv")
     * @Method({"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadCsvAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // disable execution timeout for uploads
        set_time_limit(0);

        // get track type (audio or tones)
        $type = $request->get('type');

        if (!$type || !in_array($type, ['tones', 'audio'])) {

            return new JsonResponse([
                'success' => false,
                'message' => $type ? "Wrong type given." : "No type given."
            ]);
        }

        // Upload with ajax
        $files = $request->files;

        $uploadedFile = null;
        $uploadedFileId = 0;

        foreach ($files as $key => $file) {

            // move file to tmp folder
            $uploadedFile = $this->get('app.tmp_file_uploader')->upload($file);

            // get ID of uploaded file
            $uploadedFileId = $this->getCsvIdFromFileName($uploadedFile['original_filename']);

            // only get first
            break;
        }

        if (!$uploadedFile) {

            return new JsonResponse([
                'success' => false,
                'message' => "No uplaoded file."
            ]);
        }

        // get database service
        $databaseService = $this->get('app.database_service');

        // get latest increment ID
        $incrementId = $databaseService->getDatabaseExportIncrementIdByType($type, false);

        // only allow upload of files with an id that is the same as the last increment id
        if ($uploadedFileId !== $incrementId) {

            return new JsonResponse([
                'success' => false,
                'message' => "Wrong file ID given. Expected $incrementId but got $uploadedFileId."
            ]);
        }

        // get uploaded file path
        $tmpPath = $this->getParameter('kernel.root_dir') . '/../' . $this->getParameter('tmp_path');
        $uploadedFilePath = $tmpPath . '/' . $uploadedFile['filename'];

        // import CSV
        $result = $databaseService->importCsv($uploadedFilePath);
        $result['incrementId'] = $incrementId;

        // add log file if successful
        if ($result['success'] === true) {

            $result['uploadedFileName'] = $uploadedFile['original_filename'];
            $result['logfileDownloadName'] = $this->removeExtension($uploadedFile['original_filename']);
        }

        return new JsonResponse($result);
    }

    /**
     * Upload audio files and add them up uploaded_audio database
     *
     * @Route("/upload-audio", name="upload_audio")
     * @Method({"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAudioAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // disable execution timeout for uploads
        set_time_limit(0);

        $files = $request->files;

        $arrUploadedFiles = [];

        $em = $this->getDoctrine()->getManager();

        /** @var UploadedFile $file */
        foreach ($files as $key => $file) {

            // check if uploaded audio with the same name already existing
            $originalFileName = $file->getClientOriginalName();
            $existingFile = $em->getRepository(UploadedAudio::class)->findOneBy([
                'originalFileName' => $file->getClientOriginalName()
            ]);

            if (null != $existingFile) {
                throw new \Exception("File with name $originalFileName already taken.");
            }


            if ($uploadResponse = $this->get('app.batch_audio_uploader')->upload($file)) {

                $uploadedAudio = new UploadedAudio();
                $uploadedAudio->setOriginalFileName($uploadResponse['original_filename']);
                $uploadedAudio->setFileName($uploadResponse['filename']);
                $uploadedAudio->setBaseName($uploadResponse['basename']);
                $uploadedAudio->setFileExtension($uploadResponse['extension']);

                $em->persist($uploadedAudio);

                $arrUploadedFiles[] = $uploadResponse;
            }

        }

        $em->flush();

        return new JsonResponse([
            'files' => $arrUploadedFiles
        ]);
    }

    /**
     * Endpoint to request import log file
     *
     * @Route("/download-csv-log", name="download_csv_log")
     * @Method({"GET"})
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function downloadCsvLogAction(Request $request)
    {
        $fileName = $request->query->get('file');

        $filePath = $this->getParameter('kernel.root_dir') . '/../'
            . $this->getParameter('csv_import_log_path') . '/' . $fileName;

        // check if file is available
        if (!is_file($filePath)) {
            throw $this->createNotFoundException('The requested file could not be found!');
        }

        // overwrite filename
        if ($fileName = $request->query->get('name')) {
            $path_info = pathinfo($filePath);
            $exportFileName = $fileName . '.' . $path_info['extension'];
        } else {
            $exportFileName = basename($filePath);
        }

        // prepare BinaryFileResponse
        $response = new BinaryFileResponse($filePath);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $exportFileName,
            iconv('UTF-8', 'ASCII//TRANSLIT', $exportFileName)
        );

        return $response;
    }

    /**
     * Send all uncompiled UploadedAudio to convert-API
     *
     * @Route("/check-for-uncompiled-uploaded-audio", name="check_for_uncompiled_uploaded_audio")
     * @Method({"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function checkForUncompiledUploadedAudio()
    {
        $success = true;

        try {
            $batchConvertPath = $this->getParameter('kernel.root_dir') . '/../batch-convert.sh';
            exec($batchConvertPath);

        } catch (\Exception $e) {
            $success = false;
        }

        return new JsonResponse([
            'success' => $success,
            'message' => $success ? "" : "Could not batch-request convert-API since 'exec' is disabled."
        ]);
    }


    /**
     * Extract the ID of an uploaded CSV file from its file name
     *
     * @param $fileName
     * @return int
     */
    private function getCsvIdFromFileName($fileName)
    {
        $start = strrpos($fileName, '_ID-') + 4;
        $length = strrpos($fileName, '.') - $start;

        return (int)substr($fileName, $start, $length);
    }


    /**
     * Replace file extension
     *
     * @param $filename
     * @param $new_extension
     * @return string
     */
    private function replaceExtension($filename, $new_extension) {

        $info = pathinfo($filename);

        return $info['filename'] . '.' . $new_extension;
    }


    /**
     * Remove file extension
     *
     * @param $filename
     * @return string
     */
    private function removeExtension($filename) {

        $info = pathinfo($filename);

        return $info['filename'];
    }

}
