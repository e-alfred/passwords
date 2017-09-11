<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 18:50
 */

namespace OCA\Passwords\Helper\Image;

use Gmagick;
use Imagick;

/**
 * Class ImagickHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
class ImagickHelper extends AbstractImageHelper {

    /**
     * @param Imagick|Gmagick $image
     * @param int             $minWidth
     * @param int             $minHeight
     * @param int             $maxWidth
     * @param int             $maxHeight
     *
     * @return Imagick|Gmagick
     */
    public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight) {

        $size = $this->getBestImageFit($image->getImageWidth(), $image->getImageHeight(), $minWidth, $minHeight, $maxWidth,
                                       $maxHeight);

        $image->resizeImage($size['width'], $size['height'], $image::FILTER_LANCZOS, 1);
        if($size['cropNeeded']) {
            $image->cropImage($size['cropWidth'], $size['cropHeight'], $size['cropX'], $size['cropY']);
        }

        return $image;
    }

    /**
     * @param Imagick|Gmagick $image
     * @param int             $size
     *
     * @return Imagick|Gmagick
     */
    public function simpleResizeImage($image, int $size) {
        $image->resizeImage($size, $size, $image::FILTER_LANCZOS, 1, 1);

        return $image;
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return Imagick|Gmagick
     */
    protected function cropImageRectangular($image) {
        $width  = $image->getImageWidth();
        $height = $image->getImageHeight();

        if($width > $height) {
            $padding = ($width - $height) / 2;
            $image->cropImage($height, $height, $padding, 0);
        }
        if($width < $height) {
            $padding = ($height - $width) / 2;
            $image->cropImage($width, $width, 0, $padding);
        }

        return $image;
    }

    /**
     * @param $imageBlob
     *
     * @return Imagick|Gmagick
     */
    public function getImageFromBlob($imageBlob) {
        $size = getimagesizefromstring($imageBlob);

        if(in_array($size['mime'], ['image/icon', 'image/vnd.microsoft.icon'])) {
            $imageBlob = $this->convertIcoToPng($imageBlob);
        }

        $image = $this->getNewImageObject();
        $image->readImageBlob($imageBlob);
        $image->stripImage();

        return $image;
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return bool
     */
    public function destroyImage($image): bool {
        $image->clear();

        return $image->destroy();
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return string
     */
    public function exportJpeg($image) {

        $image->setImageFormat('jpg');
        $image->setImageCompression($image::COMPRESSION_JPEG);
        $image->setImageCompressionQuality(90);

        return $image->getImageBlob();
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return string
     */
    public function exportPng($image) {

        $image->setImageFormat('png');
        $image->setImageCompressionQuality(9);

        return $image->getImageBlob();
    }

    /**
     * @param $blob
     *
     * @return bool
     */
    public function supportsImage($blob): bool {
        $size = getimagesizefromstring($blob);

        return substr($size['mime'], 0, 5) == 'image';
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function convertIcoToPng($data) {

        $tempFile = '/tmp/'.uniqid().'.ico';
        file_put_contents($tempFile, $data);

        $image = $this->getNewImageObject();
        $image->readImage($tempFile);
        $image->setImageFormat('png');
        $content = $image->getImageBlob();

        $image->destroy();
        unlink($tempFile);

        return $content;
    }

    /**
     * @return Imagick|Gmagick
     */
    protected function getNewImageObject() {
        return class_exists(Imagick::class) ? new Imagick():new Gmagick();
    }
}