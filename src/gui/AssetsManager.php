<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Env;
use phpformsframework\libs\storage\Media;
use Exception;

/**
 * Trait AssetsManager
 * @package phpformsframework\libs\gui
 */
trait AssetsManager
{
    public $js                                  = array();
    public $css                                 = array();
    public $fonts                               = array();

    public $html                                = null;


    /**
     * @param object $assets
     * @return self
     */
    public function injectAssets(object $assets) : self
    {
        $this->js                               = $this->js     + $assets->js;
        $this->css                              = $this->css    + $assets->css;
        $this->fonts                            = $this->fonts  + $assets->fonts;
        $this->html                             = $assets->html;

        return $this;
    }

    /**
     * @param string|null $url
     * @return string
     * @throws Exception
     */
    private function mask(string $url = null) : string
    {
        $env                                    = Env::getAll();
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function addJs(string $key, string $url = null) : self
    {
        return $this->addAsset($this->js, Resource::TYPE_ASSET_JS, $key, $url);
    }

    /**
     * @param string $key
     * @param string|null $url
     * @return self
     * @throws Exception
     */
    public function addCss(string $key, string $url = null) : self
    {
        return $this->addAsset($this->css, Resource::TYPE_ASSET_CSS, $key, $url);
    }

    /**
     * @param string $key
     * @param string|null $url
     * @return self
     * @throws Exception
     */
    public function addFont(string $key, string $url = null) : self
    {
        return $this->addAsset($this->fonts, Resource::TYPE_ASSET_FONTS, $key, $url);
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
            "html"      => $this->html
        ];
    }
}
