<?php


namespace AppBundle\Service;


use AppBundle\Entity\UploadedAudio;
use AppBundle\Repository\ArtistRepository;
use AppBundle\Repository\GenreRepository;
use AppBundle\Repository\PublisherRepository;
use AppBundle\Repository\SymptomRepository;
use AppBundle\Repository\TherapyRecommendationRepository;
use AppBundle\Repository\TrackRepository;
use AppBundle\Repository\UploadedAudioRepository;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Track;
use AppBundle\Entity\Artist;
use AppBundle\Entity\Genre;
use AppBundle\Entity\Publisher;
use AppBundle\Entity\Symptom;
use AppBundle\Entity\TherapyRecommendation;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


class DatabaseService
{
    private $em;
    private $kernelRoot;
    private $convertedAudioPath;
    private $csvImportLogPath;
    private $csvExportPath;
    private $commandOutput; // for command usage
    private $log;

    /**
     * Inject stuff for use in this service
     */
    public function __construct(EntityManager $entityManager, $kernelRoot, $convertedAudioPath, $csvImportLogPath, $csvExportPath)
    {
        $this->em = $entityManager;
        $this->kernelRoot = $kernelRoot;
        $this->convertedAudioPath = $this->kernelRoot . '/../' . $convertedAudioPath;
        $this->csvImportLogPath = $this->kernelRoot . '/../' . $csvImportLogPath;
        $this->csvExportPath = $this->kernelRoot . '/../' . $csvExportPath;

        $this->log = [];
    }

    public function setCommandOutput(OutputInterface $output)
    {
        $this->commandOutput = $output;
    }

    public function log($message)
    {
        $this->log[] = $message;

        if ($this->commandOutput) {
            $this->commandOutput->writeln($message);
        }
    }

    public function parseFloatToComma($value)
    {
        return str_replace('.', ',', $value);
    }


    /**
     * Export CSV file for all audio tracks or tones currently in the database
     *
     * @param string $trackType
     * @param int $trackType
     * @return array
     * @throws \Exception
     */
    public function exportCsv($trackType = 'audio', $incrementId = 0)
    {
        if (!in_array($trackType, ['audio', 'tones'])) {
            throw new \InvalidArgumentException('Wrong type given. Choose between "audio" or "tones"');
        }

        $callStartTime = microtime(true);

        $trackRepo = $this->em->getRepository(Track::class);

        if ($trackType === 'tones') {
            $tracks = $trackRepo->findTones();
        } else {
            $tracks = $trackRepo->findAudio();
        }

        // Create CSV file
        $date = new \DateTime("now", new \DateTimeZone('America/Los_Angeles'));
        $fileName = 'HT_Database_' . ucfirst($trackType) . '-List_' . $date->format('ymd') . "_ID-$incrementId.csv";
        $filePath = $this->csvExportPath . '/' . $fileName;

        if (!$fp = fopen($filePath, "w")) {
            throw new \Exception("Could not write file. Please check write permissions in the root directory.");
        }

        // Add BOM Header (https://stackoverflow.com/questions/25686191/adding-bom-to-csv-file-using-fputcsv)
        $BOM = "\xEF\xBB\xBF"; // UTF-8 BOM
        fwrite($fp, $BOM);

        // Set seperator (always open with correct delimiter settings in excel)
//        fwrite($fp, 'sep=,' . "\r\n"); // NOT WORKING TOGETHER WITH BOM HEADER

        // Build header columns
        if ($trackType === 'tones') {

            $headerColumns = [
                'Track ID',
                'Track Type',
                'Tone Type',
                'Track Name',
                'Album Name',
                'Artist',
                'Composer',
                'Publisher',
                'Genre',
                'Moods',
                'Symptoms',
                'Description',
                'Dosage',
                'Therapy length',
                'Recommendations',
                'Audible freq. left',
                'Audible freq. right',
                'Amp',
                'includes HQ',
                'HQ freq. left',
                'HQ freq. right',
                'HQ Amp',
            ];
        } else {

            $headerColumns = [
                'Track ID',
                'Internal File Name',
                'Original File Name',
                'Source File Name',
                'SourceAudio ID',
                'Track Type',
                'Track Name',
                'Album Name',
                'Artist',
                'Composer',
                'Publisher',
                'Genre',
                'Moods',
                'Symptoms',
                'Description',
                'Dosage',
                'Therapy length',
                'Recommendations',
            ];
        }

        // Write header to csv
        fputcsv($fp, $headerColumns);
//        fputs($fp, implode($headerColumns, ';') . "\n");


        // Iterate over items
        $counter = 0;

        /** @var Track $track */
        foreach ($tracks as $track) {

            if ($trackType === 'tones') {

                $data = [
                    'Track ID' => $track->getId(),
                    'Track Type' => ucfirst(Track::getTypeString($track->getType())),
                    'Tone Type' => $track->getToneType(),
                    'Track Name' => $track->getTitle(),
                    'Album Name' => $track->getAlbum(),
                    'Artist' => $track->getArtistsString(),
                    'Composer' => $track->getComposersString(),
                    'Publisher' => $track->getPublishersString(),
                    'Genre' => $track->getGenreTitle(),
                    'Moods' => $track->getMoods(),
                    'Symptoms' => $track->getSymptomsString(),
                    'Description' => $track->getDescription(),
                    'Dosage' => $track->getDosage(),
                    'Therapy length' => $track->getTherapyLength(),
                    'Recommendations' => $track->getRecommendationsString(),
                    'Audible freq. left' => $this->parseFloatToComma($track->getFLeft()),
                    'Audible freq. right' => $this->parseFloatToComma($track->getFRight()),
                    'Amp' => $this->parseFloatToComma($track->getAmpValue()),
                    'includes HQ' => $track->getIncludesHq() ? 'Yes' : 'No',
                    'HQ freq. left' => $this->parseFloatToComma($track->getFLeftHq()),
                    'HQ freq. right' => $this->parseFloatToComma($track->getFRightHq()),
                    'HQ Amp' => $this->parseFloatToComma($track->getAmpValueHq()),
                ];
            } else {

                $data = [
                    'Track ID' => $track->getId(),
                    'Internal File Name' => $track->getFilename(),
                    'Original File Name' => $track->getOriginalFilename(),
                    'Source File Name' => '', // no output since this would recopy the file on import
                    'SourceAudio ID' => $track->getSourceAudioId(),
                    'Track Type' => ucfirst(Track::getTypeString($track->getType())),
                    'Track Name' => $track->getTitle(),
                    'Album Name' => $track->getAlbum(),
                    'Artist' => $track->getArtistsString(),
                    'Composer' => $track->getComposersString(),
                    'Publisher' => $track->getPublishersString(),
                    'Genre' => $track->getGenreTitle(),
                    'Moods' => $track->getMoods(),
                    'Symptoms' => $track->getSymptomsString(),
                    'Description' => $track->getDescription(),
                    'Dosage' => $track->getDosage(),
                    'Therapy length' => $track->getTherapyLength(),
                    'Recommendations' => $track->getRecommendationsString(),
                ];
            }

            // Append new row to CSV string
            fputcsv($fp, $data);
//            fputs($fp, implode($data, ';') . "\n");

            $this->log(sprintf("+  Exported track #%s: %s", $track->getId(), $track->getTitle()));

            $counter++;
        }

        fclose($fp);

        $callTime = microtime(true) - $callStartTime;
        $this->log("---------------------------------------------------");
        $this->log(sprintf(">  Processed %s rows in %s seconds. Selected type: %s",
            $counter,
            sprintf('%.2f', $callTime),
            $trackType
        ));

        sleep(0.5);

        $this->log("===================================================");
        $this->log("✓  Finish! File $fileName has been saved.");

        return [
            'log' => $this->log,
            'executionTime' => sprintf('%.2f', $callTime) . 's',
            'numRows' => $counter,
            'trackType' => $trackType,
            'fileName' => $fileName,
            'filePath' => $filePath,
        ];
    }


    /**
     * Import CSV file with audio tracks or tones
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function importCsv($filePath = null)
    {
        $callStartTime = microtime(true);

        $this->log("... reading file");
        sleep(0.5);

        // read file or stdin (https://gist.github.com/sroze/3e8d45d0cdc301debfd2)
        if ($filePath && is_file($filePath)) {
            $contents = file_get_contents($filePath);

            // Fix Internal Server Error: Malformed UTF-8 characters, possibly incorrectly encoded
            // see: https://stackoverflow.com/a/2236698/709987
            $contents = mb_convert_encoding($contents, 'UTF-8', mb_detect_encoding($contents, 'UTF-8, ISO-8859-1', true));

        } else if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please provide a fileName or pipe content to STDIN.");
        }

        // remove first line if its a separator descriptor (strip everything until the first occurrence of "Track ID")
        $contents = substr($contents, strpos($contents, 'Track ID'));

        // Detect delimiter from file
        $delimiter = $this->detectDelimiter($contents);
        $this->log("... detected '$delimiter' as CSV delimiter");

        // decoding CSV contents
        $this->log("... decoding data");
        sleep(0.5);

        $csvEncoder = new CsvEncoder($delimiter);
        $serializer = new Serializer([new ObjectNormalizer()], [$csvEncoder]);

        $encodedData = $serializer->decode($contents, 'csv', ['csv_delimiter' => $delimiter]);

        // use data
        $this->log("... adding tracks");
        sleep(0.5);

        /** @var TrackRepository $trackRepo */
        $trackRepo = $this->em->getRepository(Track::class);
        /** @var ArtistRepository $artistRepo */
        $artistRepo = $this->em->getRepository(Artist::class);
        /** @var PublisherRepository $publisherRepo */
        $publisherRepo = $this->em->getRepository(Publisher::class);
        /** @var SymptomRepository $symptomRepo */
        $symptomRepo = $this->em->getRepository(Symptom::class);
        /** @var TherapyRecommendationRepository $recommendationRepo */
        $recommendationRepo = $this->em->getRepository(TherapyRecommendation::class);
        /** @var GenreRepository $genreRepository */
        $genreRepository = $this->em->getRepository(Genre::class);
        /** @var UploadedAudioRepository $uploadedAudioRepository */
        $uploadedAudioRepository = $this->em->getRepository(UploadedAudio::class);

        $iSuccess = 0;
        $iSame = 0;
        $iCreated = 0;
        $iUpdated = 0;
        $iFailed = 0;
        $iArtists = 0;
        $iPublishers = 0;
        $iComposers = 0;
        $iGenres = 0;
        $iSymptoms = 0;
        $iRecommendations = 0;
        $filesNotFound = [];

        $rootDir = $this->kernelRoot . '/..';

        try {

            foreach ($encodedData as $i => $item) {

                $rowNumber = $i + 2;
                $iItemChanges = 0;

//                echo "<pre>";
//                var_dump($item);
//                echo "</pre>";

                try {

                    // Skip tracks without track name
                    if (!isset($item["Track Name"]) || !strlen(trim($item["Track Name"]))) {
                        $this->log("E  Skip row $rowNumber - no track name!");
                        continue;
                    }

                    $trackTitle = trim($item["Track Name"]);

                    $actionSlug = 'Created';

                    // If given, get existing track by track id
                    $trackId = isset($item["Track ID"]) ? $item["Track ID"] : 0;
                    if ($trackId) {
                        $track = $trackRepo->find($trackId);

                        if (null === $track) {
                            $track = new Track(); // create new if not found
                        } else {
                            $actionSlug = 'Updated';
                        }

                    } // Otherwise get track by track title
                    else {
                        $track = $trackRepo->findOneByTitle($trackTitle);

                        if (null === $track) {
                            $track = new Track(); // create new if not found
                        } else {
                            $actionSlug = 'Updated';
                        }
                    }

                    // Set track title
                    $iItemChanges += $this->setValueIfChanged($track, 'title', $trackTitle);

                    // Iterate through the list
                    foreach ($item as $key => $value) {

                        // Skip empty columns
                        if (!$value) {
                            continue;
                        }

                        // Handle each column
                        switch ($key) {

                            case "SourceAudio ID":

                                $iItemChanges += $this->setValueIfChanged($track, 'sourceAudioId', $value);

                                break;

                            case "Tone Type":

                                switch ($value) {

                                    case "bBeat":
                                    case "Binaural":
                                        $iItemChanges += $this->setValueIfChanged($track, 'toneType', Track::TONE_TYPE_BINAURAL);
                                        break;

                                    case "isochronic":
                                    case "Isochronic":
                                        $iItemChanges += $this->setValueIfChanged($track, 'toneType', Track::TONE_TYPE_ISOCHRONIC);
                                }

                                break;

                            case "Track Type":

                                switch ($value) {

                                    case "Environment":
                                        $iItemChanges += $this->setValueIfChanged($track, 'type', Track::TYPE_ENVIRO);
                                        $track->setType(Track::TYPE_ENVIRO);
                                        break;

                                    case "Tone":
                                        $iItemChanges += $this->setValueIfChanged($track, 'type', Track::TYPE_TONE);
                                        break;

                                    case "Music":
                                        $iItemChanges += $this->setValueIfChanged($track, 'type', Track::TYPE_MUSIC);
                                        break;
                                }
                                break;

                            case "Source File Name":

                                if (!$originalFilename = trim($value)) {
                                    break;
                                }

                                // get UploadedAudio entity by Source File Name
                                /** @var UploadedAudio $uploadedAudio */
                                if ($uploadedAudio = $uploadedAudioRepository->findOneByOriginalFileName($originalFilename)) {

                                    // link uploaded audio
                                    $track->setUploadedAudio($uploadedAudio);

                                    // remove old track
                                    // TODO: remove old track

                                    // increase change counter since we know we changed that field
                                    $iItemChanges++;

                                } // if not found, try get file from folder (like on initial import)
                                else {
                                    $album = isset($item["Album Name"]) ? $item["Album Name"] : '';
                                    $albumFolder = mb_substr($album, 0, 7);

                                    $sourceFile = $this->searchUploadedFile($originalFilename, $albumFolder);

                                    if (is_file($sourceFile)) {

                                        // copy to tmp path, before it gets moved and renamed by upload process
                                        $fileToImportAndDelete = $rootDir . '/var/tmp/' . $originalFilename;
                                        copy($sourceFile, $fileToImportAndDelete);

                                        // copy uploaded file from import folder to correct destination
                                        $uploadableFile = new UploadedFile($fileToImportAndDelete, $fileToImportAndDelete, null, null, null, true);
                                        $track->setFile($uploadableFile);

                                        // store original filename
                                        $track->setOriginalFilename($value);

                                        // increase change counter since we know we changed that field
                                        $iItemChanges++;

                                    } else {
                                        //$this->log("!  File not found: $albumFolder/$originalFilename");
                                        $filesNotFound[] = $originalFilename;

                                        throw new FileNotFoundException("File not found: $originalFilename");
                                    }
                                }

                                break;

                            case "Album Name":

                                $iItemChanges += $this->setValueIfChanged($track, 'album', $value);

                                break;

                            case "Artist":

                                $newArtists = new ArrayCollection();

                                foreach (explode(",", $value) as $title) {

                                    $title = trim($title);
                                    if ($title && $title !== 'N/A' && $title !== 'Unknown Artist') {

                                        $artist = $artistRepo->findOneByName($title);

                                        if (null === $artist) {

                                            $this->log("i  Artist $title not found. Creating it.");

                                            $artist = new Artist();
                                            $artist->setName($title);

                                            $this->em->persist($artist);
                                            $this->em->flush();

                                            $iArtists++;
                                        }

                                        // prevent duplicates
                                        if (!$newArtists->contains($artist)) {
                                            $newArtists->add($artist);
                                        }
                                    }
                                }

                                $iItemChanges += $this->setArrayCollectionIfChanged($track, 'artists', $newArtists);

                                break;

                            case "Composer":

                                $newComposers = new ArrayCollection();

                                foreach (explode(",", $value) as $title) {

                                    if ($title = trim($title)) {

                                        $composer = $artistRepo->findOneByName($title);

                                        if (null === $composer) {

                                            $this->log("i  Composer $title not found. Creating it.");

                                            $composer = new Artist();
                                            $composer->setName($title);

                                            $this->em->persist($composer);
                                            $this->em->flush();

                                            $iComposers++;
                                        }

                                        // prevent duplicates
                                        if (!$newComposers->contains($composer)) {
                                            $newComposers->add($composer);
                                        }
                                    }
                                }

                                $iItemChanges += $this->setArrayCollectionIfChanged($track, 'composers', $newComposers);

                                break;

                            case "Publisher":

                                $newPublishers = new ArrayCollection();

                                foreach (explode(",", $value) as $title) {

                                    if ($title = trim($title)) {

                                        $publisher = $publisherRepo->findOneByName($title);

                                        if (null === $publisher) {

                                            $this->log("i  Publisher $title not found. Creating it.");

                                            $publisher = new Publisher();
                                            $publisher->setName($title);

                                            $this->em->persist($publisher);
                                            $this->em->flush();

                                            $iPublishers++;
                                        }

                                        // prevent duplicates
                                        if (!$newPublishers->contains($publisher)) {
                                            $newPublishers->add($publisher);
                                        }
                                    }
                                }

                                $iItemChanges += $this->setArrayCollectionIfChanged($track, 'publishers', $newPublishers);

                                break;

                            case "Genre":

                                if ($value = trim($value)) {

                                    $genre = $genreRepository->findOneByTitle($value);

                                    if (null === $genre) {

                                        $this->log("i  Genre $value not found. Creating it.");

                                        $genre = new Genre();
                                        $genre->setTitle($value);

                                        $this->em->persist($genre);
                                        $this->em->flush();

                                        $iGenres++;
                                    }

                                    $track->setGenre($genre);
                                }

                                break;

                            case "Moods":

                                $iItemChanges += $this->setValueIfChanged($track, 'moods', $value);

                                break;

                            case "Symptoms":

                                $newSymptoms = new ArrayCollection();

                                foreach (explode(",", $value) as $title) {

                                    if ($title = trim($title)) {

                                        $symptom = $symptomRepo->fineOneByCaseInsensitiveTitle($title);

                                        if (null === $symptom) {

                                            $this->log("i  Symptom $title not found. Creating it.");

                                            $symptom = new Symptom();
                                            $symptom->setTitle($title);

                                            $this->em->persist($symptom);
                                            $this->em->flush();

                                            $iSymptoms++;
                                        }

                                        // prevent duplicates
                                        if (!$newSymptoms->contains($symptom)) {
                                            $newSymptoms->add($symptom);
                                        }
                                    }
                                }

                                $iItemChanges += $this->setArrayCollectionIfChanged($track, 'symptoms', $newSymptoms);

                                break;

                            case "Description":

                                $iItemChanges += $this->setValueIfChanged($track, 'description', $value);

                                break;

                            case "Dosage":

                                $iItemChanges += $this->setValueIfChanged($track, 'dosage', $value);

                                break;

                            case "Therapy length":

                                $iItemChanges += $this->setValueIfChanged($track, 'therapyLength', $value);

                                break;

                            case "Recommendations":

                                $newRecommendations = new ArrayCollection();

                                foreach (explode(",", $value) as $title) {

                                    if ($title = trim($title)) {

                                        $recommendation = $recommendationRepo->findOneByTitle($title);

                                        if (null === $recommendation) {

                                            $this->log("i  Recommendation $title not found. Creating it.");

                                            $recommendation = new TherapyRecommendation();
                                            $recommendation->setTitle($title);

                                            $this->em->persist($recommendation);
                                            $this->em->flush();

                                            $iRecommendations++;
                                        }

                                        // prevent duplicates
                                        if (!$newRecommendations->contains($recommendation)) {
                                            $newRecommendations->add($recommendation);
                                        }
                                    }
                                }

                                $iItemChanges += $this->setArrayCollectionIfChanged($track, 'recommendations', $newRecommendations);

                                break;

                            case "Audible freq":

                                if (isset($value[" left"], $value[" right"])) {
                                    $iItemChanges += $this->setValueIfChanged($track, 'fLeft', $this->parseCommaToFloat($value[" left"]));
                                    $iItemChanges += $this->setValueIfChanged($track, 'fRight', $this->parseCommaToFloat($value[" right"]));
                                }

                                break;

                            case "Amp":

                                $iItemChanges += $this->setValueIfChanged($track, 'ampValue', $this->parseCommaToFloat($value));

                                break;

//                        case "Amp Mod":
//
//                            $iItemChanges += $this->setValueIfChanged($track, 'ampMod', str_replace(',', '.', $value));
//                            $iItemChanges += $this->setValueIfChanged($track, 'ampModHq', str_replace(',', '.', $value));
//
//                            break;

                            case "includes HQ":

                                $iItemChanges += $this->setValueIfChanged($track, 'includesHq', $value === "Yes" ? true : false);

                                break;

                            case "HQ freq":

                                if (isset($value[" left"], $value[" right"])) {
                                    $iItemChanges += $this->setValueIfChanged($track, 'fLeftHq', $this->parseCommaToFloat($value[" left"]));
                                    $iItemChanges += $this->setValueIfChanged($track, 'fRightHq', $this->parseCommaToFloat($value[" right"]));
                                }

                                break;

                            case "HQ Amp":

                                $iItemChanges += $this->setValueIfChanged($track, 'ampValueHq', $this->parseCommaToFloat($value));

                                break;
                        }
                    }

                    // only persist if title and type are set
                    if (!$iItemChanges) {

                        $iSame++;

                        $this->log(sprintf("=  unchanged track #%s: '%s'",
                            $track->getId(),
                            $track->getTitle()
                        ));

                    } else if ($track->getTitle() && $track->getType()) {

                        // link and delete UploadedAudio and its converted file (if it has been converted, otherwise
                        // it gets linked and deleted when the API gives the response after compiling)
                        $uploadedAudio = $track->getUploadedAudio();
                        if ($uploadedAudio !== null && $uploadedAudio->virtualIsCompiled()) {

                            $convertedFile = $this->convertedAudioPath . '/' . $uploadedAudio->getConvertedFile();

                            // copy uploaded file from import folder to correct destination
                            $uploadableFile = new UploadedFile($convertedFile, $convertedFile, null, null, null, true);
                            $track->setFile($uploadableFile);

                            // store original filename
                            $track->setOriginalFilename($uploadedAudio->getOriginalFileName());

                            // delete UploadedAudio
                            $track->setUploadedAudio(null);
                            $this->em->remove($uploadedAudio);
                            $this->em->flush();
                        }

                        // update timestamp
                        $track->setUpdatedAt(new \DateTime());

                        try {
                            $this->em->persist($track);
                            $this->em->flush();

                            $iSuccess++;

                            // increase counter
                            if ($actionSlug === 'Created') {
                                $iCreated++;
                            } else {
                                $iUpdated++;
                            }

                            $this->log(sprintf("+  %s track #%s: '%s'",
                                $actionSlug,
                                $track->getId(),
                                $track->getTitle()
                            ));

                        } catch (\Exception $e) {

                            $iFailed++;

                            $this->log(sprintf("E  Failed saving track '%s'. Error: %s",
                                $track->getTitle(),
                                $e->getMessage()
                            ));
                        }

                    } else {
                        $iFailed++;
                        $this->log("x  Ignored row $rowNumber because no title or type is defined!");
                    }

                } catch (\Exception $e) {

                    $iFailed++;
                    $this->log("E  Error in row $rowNumber: " . $e->getMessage());
                }

            } // endforeach

            $total = $i + 1;
            $callTime = microtime(true) - $callStartTime;

            $this->log("---------------------------------------------------");
            $this->log(sprintf(">  Processed %s rows in %s seconds.",
                $total,
                sprintf('%.2f', $callTime)
            ));

            if (count($filesNotFound)) {
                $this->log("---------------------------------------------------");
                $this->log("The following files could not be found:");

                foreach ($filesNotFound as $msg) {
                    $this->log("-   $msg");
                }
            }

            sleep(0.5);

            $this->log("===================================================");
            $this->log("✓  Finish! $iCreated tracks created. $iUpdated updated. $iFailed failed and $iSame unchanged. " . count($filesNotFound) . " files not found.");


            // create log file
            $date = new \DateTime("now", new \DateTimeZone('America/Los_Angeles'));
            $logfileName = $date->format('y-m-d_h-i') . ".log";
            $logfilePath = $this->csvImportLogPath . '/' . $logfileName;

            $this->writeLogArrayToFile($this->log, $logfilePath);

            // return array with all info
            return [
                'success' => true,
                'filePath' => $filePath,
                'fileName' => basename($filePath),
                'log' => $this->log,
                'executionTime' => sprintf('%.2f', $callTime) . 's',
                'iTotal' => $total,
                'iSuccess' => $iSuccess,
                'iSame' => $iSame,
                'iFailed' => $iFailed,
                'iCreated' => $iCreated,
                'iUpdated' => $iUpdated,
                'iNotFound' => count($filesNotFound),
                'iArtists' => $iArtists,
                'iPublishers' => $iPublishers,
                'iComposers' => $iComposers,
                'iGenres' => $iGenres,
                'iSymptoms' => $iSymptoms,
                'iRecommendations' => $iRecommendations,
                'filesNotFound' => $filesNotFound,
                'logfileName' => $logfileName,
                'logfileSize' => filesize($logfilePath),
            ];

        } catch (\Exception $e) {

            $this->log("ERROR: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }


    /**
     * Parse frequency values
     *
     * @param $value
     * @return float
     */
    public function parseCommaToFloat($value)
    {
        return floatval(str_replace(',', '.', str_replace('.', '', $value)));
    }


    /**
     * Get increment ID for database export
     *
     * @param $type
     * @return int
     */
    public function getDatabaseExportIncrementIdByType($type, $doIncrement = false)
    {
        $idFile = $this->kernelRoot . "/../var/admin/export-$type.id";

        // create ID file if not existing
        if (!file_exists($idFile)) {
            file_put_contents($idFile, '1');
        }

        // read ID
        $id = (int)file_get_contents($idFile);

        // increment ID
        if ($doIncrement) {
            $id++;
            file_put_contents($idFile, $id);
        }

        // and return the new ID
        return $id;
    }


    /**
     * @param string $filename
     * @param string $albumFolder
     * @return string
     */
    private function searchUploadedFile($fileName, $albumFolder = null)
    {
        // search file in converted audio folder
        $sourceFile = $this->convertedAudioPath . '/' . $fileName;

        // get from album folder of copied sources of thomas server
        if (!is_file($sourceFile) && $albumFolder) {
            $sourceFile = $this->kernelRoot . '/../soundlib/' . $albumFolder . '/' . $fileName;
        }

        return $sourceFile;
    }


    /**
     * Write a string array to text file separated by new line
     *
     * @param $arrayLogs
     * @param $filePath
     * @throws \Exception
     */
    private function writeLogArrayToFile($arrayLogs, $filePath)
    {
        if (!$fp = fopen($filePath, "w")) {
            throw new \Exception("Could not write file. Please check write permissions for $filePath.");
        }

        foreach ($arrayLogs as $value) {
            fwrite($fp, $value . PHP_EOL);
        }

        fclose($fp);
    }


    /**
     * Set a new value on an entity only if the value has changed
     *
     * @param $entity
     * @param $attribute
     * @param $newValue
     * @return int
     */
    public static function setValueIfChanged($entity, $attribute, $newValue)
    {
        if (!$entity) {
            return 0;
        }

        $getter = 'get' . ucfirst($attribute);
        $setter = 'set' . ucfirst($attribute);

//        var_dump($attribute . ': ' . $entity->$getter() . ' => ' . $newValue);

        if ($entity->$getter() !== $newValue) {
            $entity->$setter($newValue);

            return 1;
        }

        return 0;
    }


    /**
     * Set a new value on an entity multi-relation only if the value has changed
     *
     * @param $entity
     * @param $attribute
     * @param ArrayCollection $newValue
     * @return int
     */
    public function setArrayCollectionIfChanged($entity, $attribute, ArrayCollection $newValue)
    {
        if (!$entity) {
            return 0;
        }

        $getter = 'get' . ucfirst($attribute);
        $setter = 'set' . ucfirst($attribute);

        /** @var ArrayCollection $oldValue */
        $oldValue = $entity->$getter();

        if ($oldValue->count() !== $newValue->count()) {

            $entity->$setter($newValue);

            return 1;
        }

        $diff = array_diff($this->getIdsFromArrayCollection($oldValue), $this->getIdsFromArrayCollection($newValue));

        if (count($diff)) {
            $entity->$setter($newValue);

            return 1;
        }

        return 0;
    }


    /**
     * @return array
     */
    public function getIdsFromArrayCollection($collection)
    {
        $ids = [];

        foreach ($collection as $item) {

            $ids[] = $item->getId();
        }

        sort($ids, SORT_NUMERIC | SORT_FLAG_CASE);

        return $ids;
    }


    /**
     * Detect delimiter of csv content
     * credits: https://stackoverflow.com/a/37557537/709987
     *
     * @param string $content
     * @return string
     */
    private function detectDelimiter($content)
    {
        $delimiters = ["\t", ";", "|", ","];
        $data_1 = null;
        $data_2 = null;
        $delimiter = $delimiters[0];

        foreach ($delimiters as $d) {

            $data_1 = str_getcsv($content, $d);

            if (sizeof($data_1) > sizeof($data_2)) {
                $delimiter = sizeof($data_1) > sizeof($data_2) ? $d : $delimiter;
                $data_2 = $data_1;
            }

            //rewind($content);
        }

        return $delimiter;
    }
}
