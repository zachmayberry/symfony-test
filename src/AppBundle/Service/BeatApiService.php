<?php


namespace AppBundle\Service;

use AppBundle\Classes\MP3File;
use AppBundle\Entity\Playlist;
use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\Track;
use FFMpeg\Format\Audio\Mp3;
use Monolog\Logger;
use Vich\UploaderBundle\Storage\AbstractStorage;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;


/**
 * Class BeatApiService
 * @package AppBundle\Service
 */
class BeatApiService
{
    const THERAPY_PREVIEW_DURATION = 60;

    private $vichUploaderStorageService;

    private $kernelRoot;

    private $skipBinaries;

    private $ffmpegPath;

    private $ffprobePath;

    private $apiUrl;

    private $callbackUrl;


    /**
     * BeatApiService constructor.
     *
     * @param AbstractStorage $vichUploaderStorageService
     * @param $kernelRoot
     * @param $skipBinaries
     * @param $ffmpegPath
     * @param $ffprobePath
     */
    public function __construct(AbstractStorage $vichUploaderStorageService, $kernelRoot, $skipBinaries, $ffmpegPath, $ffprobePath, $apiUrl, $callbackUrl, Logger $logger)
    {
        $this->vichUploaderStorageService = $vichUploaderStorageService;
        $this->kernelRoot = $kernelRoot;
        $this->skipBinaries = $skipBinaries;
        $this->ffmpegPath = $ffmpegPath;
        $this->ffprobePath = $ffprobePath;
        $this->apiUrl = $apiUrl;
        $this->callbackUrl = $callbackUrl;
        $this->logger = $logger;
    }


    /**
     * Make CURL request to Beat API Service and optionally receive compiled file
     *
     * @param $filename
     * @param $targetFolder
     * @param array $apiParameters
     * @param array $beatPlaylistData
     * @param null $musicPlaylistData
     * @param bool $asyncMode
     * @return string
     * @throws \Exception
     */
    public function makeApiRequest($filename, $targetFolder, $apiParameters = [], $beatPlaylistData = [], $musicPlaylistData = null, $asyncMode = false)
    {
        $distFileName = $filename . '.mp4';
        $distFilePath = $targetFolder . $distFileName;

        // Create mandatory Data
        $data = $apiParameters;
        $data['dstFile'] = $distFileName;
        $data['async'] = $asyncMode ? 1 : 0;
        $data['callbackUrl'] = $this->callbackUrl;

        // Add beat playlist data
        $data = array_merge($data, $beatPlaylistData);

        // Add music playlist data
        $data = array_merge($data, $musicPlaylistData ? $musicPlaylistData : $this->createEmptyMusicPlaylist());

//        // Debugging request
//        echo "SENDING API-REQUEST WITH FOLLOWING DATA:" . "\r\n";
//        \Doctrine\Common\Util\Debug::dump($data);
//        $time_start = microtime(true);
        $this->logger->info("API Request:", $data);

        // Init CURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_FAILONERROR => true,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

//        // Debugging result
//        $time_end = microtime(true);
//        $time = $time_end - $time_start;
//        echo "\r\n" . "\r\n" . "GOT RESPONSE AFTER $time SECONDS!" . "\r\n";
//        echo "ERROR RECEIVED (if empty, everything is good): ";
//        \Doctrine\Common\Util\Debug::dump($err);
//        echo "\r\n" . "RESPONSE RECEIVED (this should contain the file contents, if empty => not good): ";
//        \Doctrine\Common\Util\Debug::dump($response);
//        die;

        if ($err) {
            $this->logger->critical("API Error: " . $err);
            throw new \Exception("File could not be generated, error from CURL: " . $err);
        }

        // Write file to disk
        if (!$asyncMode) {
            $this->logger->info("API sync. Response (filename): " . $distFilePath);
            $this->writeFileToDisk($distFilePath, $response);
        }
        else {
            $this->logger->info("API async. Response: " . $response);
        }

        return $distFilePath;
    }


    /**
     * Generate a short preview file in MP3 format
     *
     * @param $srcFileName
     * @param $targetFileName
     */
    public function generatePreviewFile($srcFileName, $targetFileName)
    {
        if ($this->skipBinaries) {
            $ffmpeg = FFMpeg::create();
        }
        else {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => $this->ffmpegPath,
                'ffprobe.binaries' => $this->ffprobePath,
            ]);
//            $ffmpeg = FFMpeg::create([
//                'ffmpeg.binaries' => exec('which(ffmpeg)'),
//                'ffprobe.binaries' => exec('which(ffprobe)'),
//                'avprobe.binaries' => exec('which(avprobe)'),
//            ]);
        }

        $format = new Mp3();
        $audio = $ffmpeg->open($srcFileName);

        $audio->filters()->clip(TimeCode::fromSeconds(0), TimeCode::fromSeconds(self::THERAPY_PREVIEW_DURATION));
        $audio->save($format, $targetFileName);
    }


    /**
     * Write a file to disk
     *
     * @param $filename
     * @param $content
     */
    public function writeFileToDisk($filename, $content)
    {
        $file = fopen($filename, 'w+'); // Create a new file, or overwrite the existing one.
        fwrite($file, $content);
        fclose($file);

        //echo "wrote file to " . $filename . " (" . filesize($filename) . "bytes)" . "\r\n";
    }

    /**
     * @param $track
     * @return string|void
     */
    public function getAbsoluteTrackPath($track)
    {
        return $this->vichUploaderStorageService->resolvePath($track, 'file');
    }

    /**
     * Get duration of an audio file
     *
     * @deprecated since its incorrect with mp4 files
     * @param $file
     * @param bool $estimate
     * @return float
     */
    public function getDuration($file, $estimate = false)
    {
        $mp3file = new MP3File($file);

        if ($estimate) {
            return $mp3file->getDurationEstimate(); //(faster) for CBR only
        }
        return $mp3file->getDuration(); //(slower) for VBR (or CBR)
    }

    /**
     * @return array
     */
    public function getEmptyBeatApiData()
    {
        $data = [];
        return $data;
    }

    /**
     * @param $therapy
     * @param bool $hq
     * @return array
     */
    public function getConfigurationDataFromTherapy($therapy, $hq = false)
    {
        return [
            'Duration' => $therapy->getDosage() * 60,
            'mr_id' => $therapy->getId(),
            'type' => 'therapy',
            'hq' => $hq === true ? 1 : 0,
        ];
    }

    /**
     * @param TherapySession $therapySession
     * @param bool $hq
     * @return array
     */
    public function getConfigurationDataFromTherapySession(TherapySession $therapySession, $hq = false)
    {
        return [
            'Duration' => $therapySession->getDosage() * 60,
            'mr_id' => $therapySession->getId(),
            'type' => 'session',
            'hq' => $hq === true ? 1 : 0,
        ];
    }

    /**
     * @param Playlist $playlist
     * @param bool $hq
     * @return array
     */
    public function getBeatApiDataFromPlaylist(Playlist $playlist, $hq = false)
    {
        $data = [];

        foreach ($playlist->getPlaylistTracks() as $key => $playlistTrack) {
            $track = $playlistTrack->getTrack();
            $duration = $playlistTrack->getDuration(); // get duration from PlaylistTrack
            $data["beatPart[$key]"] = $this->generateBeatQueryFromTrack($track, $duration, $hq);
        }

        return $data;
    }

    /**
     * Build parameter string for CURL request for a single tone Track
     *
     * @param $track
     * @param $duration
     * @param bool $hq
     * @param string $format
     * @return string
     */
    public function generateBeatQueryFromTrack(Track $track, $duration, $hq = false, $format = 'mp4')
    {
        if ($hq !== true) {
            $fLeft = $track->getFLeft();
            $fRight = $track->getFRight();
            $ampVal = $track->getAmpValue();
            $ampMod = $track->getAmpMod();
        }
        else {
            $fLeft = $track->getFLeftHq();
            $fRight = $track->getFRightHq();
            $ampVal = $track->getAmpValueHq();
            $ampMod = $track->getAmpModHq();
        }

        return sprintf(
            "%s;%s;%s;%s;%s;%s"
            , $duration
            , $fLeft
            , $fRight
            , $ampVal
            , $ampMod
            , $format
        );
    }


    /**
     * Build parameter array for CURL request for a single audio Track
     *
     * @param Playlist|null $playlist
     * @return array
     */
    public function getMusicApiDataFromPlaylist(Playlist $playlist = null)
    {
        $data = [];

        if (!$playlist) {
            return $data;
        }

        foreach ($playlist->getPlaylistTracks() as $key => $playlistTrack) {
            $absoluteFilePath = $this->getAbsoluteTrackPath($playlistTrack->getTrack());
            $cfile = new \CURLFile($absoluteFilePath);
            $data["srcFile[$key]"] = $cfile;
        }

        return $data;
    }


    /**
     * Create paramter array for CURL request for therapies without music playlist
     *
     * @return array
     */
    public function createEmptyMusicPlaylist()
    {
        $absoluteFilePath  = $this->kernelRoot . '/../files/empty.mp3';
        $cfile = new \CURLFile($absoluteFilePath);

        return ["srcFile[0]" => $cfile];
    }
}