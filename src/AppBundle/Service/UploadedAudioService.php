<?php


namespace AppBundle\Service;


use AppBundle\Entity\UploadedAudio;

class UploadedAudioService
{
    private $bs;
    private $kernelRoot;
    private $uploadedAudioPath;
    private $convertedAudioPath;
    private $apiConverterUrl;
    private $callbackUrl;
    private $logger;

    /**
     * Inject stuff for use in this service
     */
    public function __construct(BeatApiService $beatApiService, $kernelRoot, $uploadedAudioPath, $convertedAudioPath, $apiConverterUrl, $callbackUrl, $logger)
    {
        $this->bs = $beatApiService;
        $this->kernelRoot = $kernelRoot;
        $this->uploadedAudioPath = $this->kernelRoot . '/../' . $uploadedAudioPath;
        $this->convertedAudioPath = $this->kernelRoot . '/../' . $convertedAudioPath;
        $this->apiConverterUrl = $apiConverterUrl;
        $this->callbackUrl = $callbackUrl;
        $this->logger = $logger;
    }

    /**
     * @param UploadedAudio $uploadedAudio
     */
    public function convertUploadedAudio(UploadedAudio $uploadedAudio)
    {
        return $this->makeApiRequest($uploadedAudio);
    }

    /**
     * Make CURL request to Beat API Service and optionally receive compiled file
     *
     * @param UploadedAudio $uploadedAudio
     * @return string
     * @throws \Exception
     */
    public function makeApiRequest(UploadedAudio $uploadedAudio)
    {
        $distFileName = $uploadedAudio->getBaseName() . '.mp4';

        // Create mandatory Data
        $data = [
            'mr_id' => $uploadedAudio->getId(),
            'dstFile' => $distFileName,
            'callbackUrl' => $this->callbackUrl,
            'srcFile[0]' => new \CURLFile($this->uploadedAudioPath . '/' . $uploadedAudio->getFileName()),
        ];

        // Debugging request
//        echo "SENDING API-REQUEST WITH FOLLOWING DATA:" . "\r\n";
//        \Doctrine\Common\Util\Debug::dump($data);
        $time_start = microtime(true);
        $this->logger->info("API Convert-Request:", $data);

        // Init CURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiConverterUrl,
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

        // Debugging result
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $this->logger->info("API Convert-Response after $time seconds: " . $response);
//        echo "\r\n" . "\r\n" . "GOT RESPONSE AFTER $time SECONDS!" . "\r\n";
//        echo "ERROR RECEIVED (if empty, everything is good): ";
//        \Doctrine\Common\Util\Debug::dump($err);
//        echo "\r\n" . "RESPONSE RECEIVED (this should contain the file contents, if empty => not good): ";
//        \Doctrine\Common\Util\Debug::dump($response);
//        die;

        if ($err) {
            $this->logger->critical("API Convert-Error: " . $err);
            throw new \Exception("File could not be generated, error from CURL: " . $err);
        }

        $this->logger->info("API Convert-Response: " . $response);

        return $distFileName;
    }
}
