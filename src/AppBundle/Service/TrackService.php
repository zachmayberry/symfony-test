<?php


namespace AppBundle\Service;


use AppBundle\Entity\Track;

class TrackService
{
    const PREVIEW_DURATION = 30;

    private $bs;
    private $kernelRoot;
    private $libraryTracksPath;

    /**
     * Inject stuff for use in this service
     */
    public function __construct(BeatApiService $beatApiService, $kernelRoot, $libraryTracksPath)
    {
        $this->bs = $beatApiService;
        $this->kernelRoot = $kernelRoot;
        $this->libraryTracksPath = $libraryTracksPath;
    }

    public function generateBinauralPreviewFile($track, $hq = false)
    {
        $filename = $this->generateUniqueFilename($track, $hq);
        $targetFolder = $this->getTargetFolder();

        $configurationData = [
            'Duration' => self::PREVIEW_DURATION,
            'type' => 'tone',
            'mr_id' => $track->getId(),
            'hq' => $hq ? 1 : 0,
        ];

        $beatPlaylistData = [
            "beatPart[0]" => $this->bs->generateBeatQueryFromTrack($track, self::PREVIEW_DURATION, $hq)
        ];

        return $this->bs->makeApiRequest($filename, $targetFolder, $configurationData, $beatPlaylistData);
    }

    public function generateUniqueFilename(Track $track, $hq = false)
    {
        $uniqueId = md5($track->getId() + time());
        if ($hq) $uniqueId = 'HQ_' . $uniqueId;
        return 'tone_' . $track->getId() . '_' . $uniqueId;
    }

    public function getTargetFolder()
    {
        return $this->kernelRoot . '/../var/tmp/';// . $this->libraryTracksPath . '/';
    }
}
