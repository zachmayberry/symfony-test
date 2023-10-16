<?php

namespace AppBundle\Service;


use AppBundle\Entity\Therapy;
use Doctrine\ORM\EntityManager;

class TherapyService
{
    private $entityManager;
    private $bs;
    private $kernelRoot;
    private $libraryTherapiesPath;
    private $libraryPreviewsPath;
    private $asyncMode;

    /**
     * Inject stuff for use in this service
     */
    public function __construct(EntityManager $entityManager, BeatApiService $beatApiService, $kernelRoot, $libraryTherapiesPath, $libraryPreviewsPath, $asyncMode)
    {
        $this->em = $entityManager;
        $this->bs = $beatApiService;
        $this->kernelRoot = $kernelRoot;
        $this->libraryTherapiesPath = $libraryTherapiesPath;
        $this->libraryPreviewsPath = $libraryPreviewsPath;
        $this->asyncMode = $asyncMode;
    }

    public function generateTherapyFileForTherapy(Therapy $therapy)
    {
        // get this before changing the status
        $includesHq = $therapy->getIncludesHq();
//        $createHq = $therapy->isUncompiledHq(); // if this is true, we have to generate the HQ file

        // Set compiling status
        $therapy->setCompileStatus(Therapy::STATUS_COMPILING);
        $this->em->persist($therapy);
        $this->em->flush();

        $musicPlaylist = $therapy->getMusicPlaylist();
        $beatsPlaylist = $therapy->getBinauralPlaylist();
        $hasTonesPlaylist = $therapy->hasTonesPlaylist();
        $filePrefix = 'therapy_' . $therapy->getId();

        // AUDIBLE VERSION
        try {
            $filePath = $this->generateTherapyFile(
                $filePrefix,
                $hasTonesPlaylist,
                $musicPlaylist,
                $beatsPlaylist,
                $this->bs->getConfigurationDataFromTherapy($therapy, false),
                false,
                false,
                true,
                $this->asyncMode
            );

            // refresh connection (https://github.com/facile-it/doctrine-mysql-come-back)
            $this->em->getConnection()->refresh();

            $therapy->setFileName(basename($filePath));
            $therapy->setFileSize(filesize($filePath));

            $this->em->persist($therapy);
            $this->em->flush();
        }
        catch (\Exception $exception) {

            // Set error status
            $therapy->setCompileStatus(Therapy::STATUS_COMPILE_ERROR);
            $this->em->persist($therapy);
            $this->em->flush();

            throw new \Exception("Error: " . $exception->getMessage());

            return 0;
        }


        // HQ VERSION
        if ($includesHq) {

            try {
                $filePath = $this->generateTherapyFile(
                    $filePrefix,
                    $hasTonesPlaylist,
                    $musicPlaylist,
                    $beatsPlaylist,
                    $this->bs->getConfigurationDataFromTherapy($therapy, true),
                    true,
                    false,
                    false, // only create preview for audible version
                    $this->asyncMode
                );

                $therapy->setFileNameHq(basename($filePath));
                $therapy->setFileSizeHq(filesize($filePath));
                $therapy->setCompileStatus(Therapy::STATUS_COMPILED);

                $this->em->persist($therapy);
                $this->em->flush();
            }
            catch (\Exception $exception) {

                // Set error status
                $therapy->setCompileStatus(Therapy::STATUS_COMPILE_ERROR);
                $this->em->persist($therapy);
                $this->em->flush();

                throw new \Exception("Error: " . $exception->getMessage());

                return 0;
            }
        }

        return 1;
    }


    public function generateTherapyFile(
        $prefix = '', $hasTonesPlaylist = false, $musicPlaylist = null, $binauralPlaylist = null,
        $apiParameters = [], $hq = false, $createPreview = false, $generateTeaser = false, $asyncMode = false
    ){
        $filename = $this->generateUniqueFilename($prefix, $hq);

        if ($createPreview) {
            $targetFolder = $this->getPreviewFolder();
        }
        else {
            $targetFolder = $this->getTargetFolder();
        }

        $musicPlaylistData = $this->bs->getMusicApiDataFromPlaylist($musicPlaylist);

        $beatPlaylistData = $hasTonesPlaylist
            ? $this->bs->getBeatApiDataFromPlaylist($binauralPlaylist, $hq)
            : $this->bs->getEmptyBeatApiData();

        $filePath = $this->bs->makeApiRequest($filename, $targetFolder, $apiParameters, $beatPlaylistData, $musicPlaylistData, $asyncMode);

        return $filePath;
    }

    public function generateUniqueFilename($prefix = '', $hq = false)
    {
        $uniqueId = md5($prefix . time());
        if ($hq) $uniqueId = 'HQ_' . $uniqueId;

        return $prefix . '_' . $uniqueId;
    }

    public function getTargetFolder()
    {
        //return $this->kernelRoot . '/../var/tmp/';
        return $this->kernelRoot . '/../' . $this->libraryTherapiesPath . '/';
    }

    public function getPreviewFolder()
    {
        //return $this->kernelRoot . '/../var/tmp/';
        return $this->kernelRoot . '/../' . $this->libraryPreviewsPath . '/';
    }
}
