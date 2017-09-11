<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 21:32
 */

namespace OCA\Passwords\Helper\Image;

use OCP\Image;

/**
 * Class GdHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
class GdHelper extends AbstractImageHelper {

    /**
     * @param Image $image
     * @param int   $minWidth
     * @param int   $minHeight
     * @param int   $maxWidth
     * @param int   $maxHeight
     *
     * @return Image
     */
    public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight) {

        $size = $this->getBestImageFit($image->width(), $image->height(), $minWidth, $minHeight, $maxWidth, $maxHeight);

        $image->preciseResize($size['width'], $size['height']);
        if($size['cropNeeded']) {
            if($size['cropHeight'] === 0) $size['cropHeight'] = $size['height'];
            if($size['cropWidth'] === 0) $size['cropWidth'] = $size['width'];
            $image->crop($size['cropX'], $size['cropY'], $size['cropWidth'], $size['cropHeight']);
        }

        return $image;
    }

    /**
     * @param Image $image
     * @param int   $size
     *
     * @return Image
     */
    public function simpleResizeImage($image, int $size) {

        $image->preciseResize($size, $size);

        return $image;
    }

    /**
     * @param Image $image
     *
     * @return Image
     */
    protected function cropImageRectangular($image) {

        $width  = $image->width();
        $height = $image->height();

        if($width > $height) {
            $padding = ($width - $height) / 2;
            $image->crop($padding, 0, $height, $height);
        }
        if($width < $height) {
            $padding = ($height - $width) / 2;
            $image->crop(0, $padding, $width, $width);
        }

        return $image();
    }

    /**
     * @param $imageBlob
     *
     * @return Image
     */
    public function getImageFromBlob($imageBlob) {
        $size    = getimagesizefromstring($imageBlob);
        $mime    = substr($size['mime'], 6);
        $tmpFile = '/tmp/'.uniqid().'.'.$mime;
        file_put_contents($tmpFile, $imageBlob);

        $image = $this->getNewImageObject();
        $image->load($tmpFile);
        unlink($tmpFile);

        return $image;
    }

    /**
     * @param Image $image
     *
     * @return bool
     */
    public function destroyImage($image): bool {
        $image->destroy();

        return true;
    }

    /**
     * @param Image $image
     *
     * @return string
     */
    public function exportJpeg($image) {
        $tmpFile = '/tmp/'.uniqid();
        $image->save($tmpFile, 'image/jpeg');
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    /**
     * @param Image $image
     *
     * @return string
     */
    public function exportPng($image) {
        $tmpFile = '/tmp/'.uniqid();
        $image->save($tmpFile, 'image/png');
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    /**
     * @param $blob
     *
     * @return bool
     */
    public function supportsImage($blob): bool {
        $size = getimagesizefromstring($blob);

        if($size['mime'] == 'image/icon') {
            return false;
        } else if($size['mime'] == 'image/vnd.microsoft.icon') {
            return false;
        }

        return substr($size['mime'], 0, 5) == 'image';
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function convertIcoToPng($data) {
        return $data;
    }

    /**
     * @return Image
     */
    protected function getNewImageObject() {
        return new Image();
    }
}