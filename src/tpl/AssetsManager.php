<?php
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Env;
use phpformsframework\libs\storage\Media;

/**
 * Trait AssetsManager
 * @package phpformsframework\libs\tpl
 */
trait AssetsManager
{
    public $js                               = array();
    public $css                              = array();
    public $images                           = array();
    public $fonts                            = array();

    public $html                                = null;


    /**
     * @param self $assets
     * @return self
     */
    public function injectAssets($assets) : self
    {
        $this->js                               = $this->js     + $assets->js;
        $this->css                              = $this->css    + $assets->css;
        $this->fonts                            = $this->fonts  + $assets->fonts;
        $this->images                           = $this->images + $assets->images;
        $this->html                             = $assets->html;

        return $this;
    }

    /**
     * @param string $url
     * @return string
     */
    private function mask(string $url = null) : string
    {
        $env                                    = Env::get();
        $env["{"]                               = "";
        $env["}"]                               = "";

        $url                                    = str_ireplace(array_keys($env), array_values($env), $url);

        return (strpos($url, Constant::DISK_PATH) === 0
            ? Media::getUrl($url)
            : $url
        );
    }

    /**
     * @param array $ref
     * @param string $type
     * @param string $key
     * @param string|null $url
     * @return self
     */
    private function addAsset(array &$ref, $type, string $key, string $url = null) : self
    {
        if ($this->resolveUrl($key, $type, $url)) {
            $ref[$key]                          = $this->mask($url);
        }
        return $this;
    }

    /**
     * @param string $key
     * @param string $type
     * @param string|null $url
     * @return bool
     */
    private function resolveUrl(string $key, string $type, string &$url = null) : bool
    {
        if (!$url) {
            $url                                = Resource::get($key, $type);
        }

        if (!$url) {
            if (strpos($key, "..") !== false) {
                $key                            = realpath($key);
            }

            if (strpos($key, "http") === 0
                || strpos($key, "//") === 0
                || strpos($key, Constant::DISK_PATH) === 0
                //|| strpos($key, Media::RENDER_ASSETS_PATH) === 0 //todo: da gestire i path relativi
            ) {
                $url                            = $key;
            }
        }

        return $url && (!Dir::checkDiskPath($url) || filesize($url));
    }

    /**
     * @param string $key
     * @param string|null $url
     * @return self
     */
    public function addJs(string $key, string $url = null) : self
    {
        return $this->addAsset($this->js, "js", $key, $url);
    }

    /**
     * @param string $key
     * @param string|null $url
     * @return self
     */
    public function addCss(string $key, string $url = null) : self
    {
        return $this->addAsset($this->css, "css", $key, $url);
    }

    /**
     * @param string $key
     * @param string|null $url
     * @return self
     */
    public function addFont(string $key, string $url = null) : self
    {
        return $this->addAsset($this->fonts, "fonts", $key, $url);
    }
    /**
     * @param string $key
     * @param string|null $url
     * @return self
     */
    public function addImage(string $key, string $url = null) : self
    {
        return $this->addAsset($this->images, "images", $key, $url);
    }
}
