<?php


namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;


class FileUploader
{
    private $targetDir;

    public function __construct($kernelRoot, $targetDir)
    {
        $this->targetDir = $kernelRoot . '/../' . $targetDir;
    }

    public function upload(UploadedFile $file)
    {
        $newFileName = md5(uniqid()).'.'.$file->getClientOriginalExtension();
        $originalFileName = $file->getClientOriginalName();

        $fileSize = $file->getSize();

        list( $dirname, $basename, $extension, $filename ) = array_values( pathinfo($newFileName) );

        $file->move($this->targetDir, $newFileName);

        return [
            'original_filename' => $originalFileName,
            'filename' => $basename,
            'basename' => $filename,
            'extension' => $extension,
            'file_size' => $fileSize,
        ];
    }

    public function getTargetDir()
    {
        return $this->targetDir;
    }
}