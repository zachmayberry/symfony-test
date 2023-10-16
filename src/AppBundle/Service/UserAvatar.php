<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\AbstractStorage;


class UserAvatar
{
    private $vichUploaderStorageService;

    private $liipImageService;

    private $defaultUserImage;

    /**
     * @param RequestStack $requestStack
     * @param CacheManager $liipImageService
     * @param string $defaultUserImage
     */
    public function __construct(AbstractStorage $vichUploaderStorageService,
                                CacheManager $liipImageService,
                                $defaultUserImage)
    {
        $this->vichUploaderStorageService = $vichUploaderStorageService; // Injected in services.yml
        $this->liipImageService = $liipImageService;
        $this->defaultUserImage = $defaultUserImage;
    }

    public function getAvatar(User $user, $size, $returnArray = true)
    {
        // get user image or fallback
        $image = $user->getProfileImageName();
        if (!$image) {
            $image = $this->defaultUserImage;
        }

        // get filter name by size paramter
        switch ($size) {
            case 'large':
                $filer = 'quad_300';
                $width = $height = 300;
                break;
            case 'medium':
                $filer = 'quad_180';
                $width = $height = 150;
                break;
            case 'small':
                $filer = 'quad_80';
                $width = $height = 80;
        }

        // make thumbnail
        $thumbnail = $this->liipImageService->getBrowserPath($image, 'quad_180');

        // return array or image strin
        if ($returnArray) return [
            'url' => $thumbnail,
            'width' => $width,
            'height' => $height,
        ];
        return $image;
    }
}