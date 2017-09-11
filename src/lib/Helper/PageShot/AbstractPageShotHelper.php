<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:06
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\HttpRequestHelper;
use OCA\Passwords\Services\FileCacheService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class AbstractPageShotHelper
 *
 * @package OCA\Passwords\Helper\Pageshot
 */
abstract class AbstractPageShotHelper {

    /**
     * @var string
     */
    protected $prefix = 'af';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * BetterIdeaHelper constructor.
     *
     * @param FileCacheService $fileCacheService
     */
    public function __construct(FileCacheService $fileCacheService) {
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile
     */
    function getPageShot(string $domain, string $view): ISimpleFile {
        $pageshotFile = $this->getPageShotFilename($domain, $view);

        if($this->fileCacheService->hasFile($pageshotFile)) {
            return $this->fileCacheService->getFile($pageshotFile);
        }

        $url          = $this->getPageShotUrl($domain, $view);
        $pageShotData = $this->getHttpRequest($url);

        if($pageShotData === null) {
            return $this->getDefaultPageShot();
        }

        return $this->fileCacheService->putFile($pageshotFile, $pageShotData);
    }

    /**
     * @return ISimpleFile
     */
    public function getDefaultPageShot(): ISimpleFile {
        $random   = rand(1, 5);
        $fileName = "{$this->prefix}_default_{$random}.jpg";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $path    = dirname(dirname(dirname(__DIR__))).'/img/preview/preview_'.rand(1, 5).'.jpg';
        $content = file_get_contents($path);

        return $this->fileCacheService->putFile($fileName, $content);
    }

    /**
     * @param string   $domain
     * @param string   $view
     * @param int|null $minWidth
     * @param int|null $minHeight
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     *
     * @return string
     */
    public function getPageShotFilename(
        string $domain,
        string $view,
        int $minWidth = null,
        int $minHeight = null,
        int $maxWidth = null,
        int $maxHeight = null
    ): string {
        if($minWidth !== null) {
            return "{$this->prefix}_{$domain}_{$view}_{$minWidth}x{$minHeight}_{$maxWidth}x{$maxHeight}.jpg";
        }

        return "{$this->prefix}_{$domain}_{$view}.jpg";
    }

    /**
     * @param string $url
     *
     * @return mixed
     * @throws ApiException
     */
    protected function getHttpRequest(string $url) {
        $request = new HttpRequestHelper();
        $request->setUrl($url);
        $data = $request->sendWithRetry();

        $type = $request->getInfo()['content_type'];
        if(substr($type, 0, 5) != 'image') {
            throw new ApiException('API Request Failed');
        }

        return $data;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    abstract protected function getPageShotUrl(string $domain, string $view): string;
}