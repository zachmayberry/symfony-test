<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use AppBundle\Entity\News;
use AppBundle\Entity\Therapy;
use AppBundle\Service\FileUploader;


/**
 * Class DoctrineUploadListener
 * @package AppBundle\EventListener
 */
class DoctrineUploadListener
{
    /**
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor
     */
    public function __construct(FileUploader $uploader)
    {
        $this->uploader = $uploader;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->uploadFile($entity);
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->uploadFile($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$this->isValidClass($entity)) {
            return;
        }

        if ($fileName = $entity->getBrochure()) {
            $entity->setBrochure(new File($this->uploader->getTargetDir().'/'.$fileName));
        }
    }

    /**
     * @param $entity
     */
    private function uploadFile($entity)
    {
        if (!$this->isValidClass($entity)) {
            return;
        }



        $file = $entity->getBrochure();

        // only upload new files
        if (!$file instanceof UploadedFile) {
            return;
        }

        $fileName = $this->uploader->upload($file);
        $entity->setBrochure($fileName);
    }

    /**
     * Upload only works certain entities
     *
     * @param $entity
     * @return bool
     */
    private function isValidClass($entity)
    {
        return $entity instanceof News
            || $entity instanceof Therapy;
    }

    /**
     * Get file attributes of an entity
     *
     * @param $entity
     * @return bool
     */
    private function getFileAttributes($entity)
    {
        switch (true) {
            case $entity instanceof News:
                return [
                    'image'
                ];
            case $entity instanceof Therapy:
                return [
                    'image'
                ];
        }
    }
}