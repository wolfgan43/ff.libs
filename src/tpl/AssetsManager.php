<?php
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\Dir;

/**
 * Trait AssetsManager
 * @package phpformsframework\libs\tpl
 */
trait AssetsManager
{
    protected $js                               = null;
    protected $css                              = null;
    protected $fonts                            = null;

    private $html                               = null;

    private function addAsset($name, $key, $url)
    {
        if (!Dir::checkDiskPath($url) || filesize($url)) {
            $this->$name[$key]                  = $url;
        }

        return $this;
    }

    public function addJs($key, $url)
    {
        return $this->addAsset("js", $key, $url);
    }
    public function addCss($key, $url)
    {
        return $this->addAsset("css", $key, $url);
    }
    public function addFont($key, $url)
    {
        return $this->addAsset("fonts", $key, $url);
    }
}
