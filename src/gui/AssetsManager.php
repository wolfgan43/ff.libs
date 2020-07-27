<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Env;
use phpformsframework\libs\storage\Media;

/**
 * Trait AssetsManager
 * @package phpformsframework\libs\gui
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
    private function addAsset(array &$ref, string $type, string $key, string $url = null) : self
    {
        if (!$url) {
            $url                                = $this->addAssetDeps($ref, $type, $key);
        }

        if ($this->resolveUrl($key, $url)) {
            $ref[$key]                          = $this->mask($url);
        }
        return $this;
    }

    /**
     * @param array $ref
     * @param string $type
     * @param string $key
     * @return string|null
     */
    private function addAssetDeps(array &$ref, string $type, string $key) : ?string
    {
        $url                                    = Resource::get($key, $type);
        if ($url) {
            $asset_name                         = "";
            $assets                             = explode(".", $key);
            array_pop($assets);
            foreach ($assets as $asset) {
                $asset_name                     .= $asset;
                $asset_url                      = Resource::get($asset_name, $type);
                if ($asset_url) {
                    $this->addAsset($ref, $type, $asset_name, $asset_url);
                }
                $asset_name                     .= ".";
            }
        }
        return $url;
    }

    /**
     * @param string $key
     * @param string|null $url
     * @return bool
     */
    private function resolveUrl(string $key, string &$url = null) : bool
    {
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
        return !Dir::checkDiskPath($url) || filesize($url);
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

    public function addJavascript(string $key, string $location = null, bool $async = false) : self
    {
    }
    public function addJavascriptEmbed(string $content, string $location = null, bool $async = false) : self
    {
    }
    public function addStyleSheet(string $key_or_path_or_url, string $media = null) : self
    {
    }
    public function addStyleSheetEmbed(string $content, string $media = null) : self
    {
    }
    /**
     * @return array
     */
    public function toArray() : array
    {
        return [
            "js"        => $this->js,
            "css"       => $this->css,
            "fonts"     => $this->fonts,
            "images"    => $this->images,
            "html"      => $this->html
        ];
    }
}