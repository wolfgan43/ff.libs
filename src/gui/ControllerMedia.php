<?php
namespace phpformsframework\libs\gui;

use Exception;
use phpformsframework\libs\Env;
use phpformsframework\libs\storage\Media;

/**
 * Class ControllerMedia
 * @package phpformsframework\libs\gui
 */
class ControllerMedia
{
    /**
     * @param string $key_or_url
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    public function imageUrl(string $key_or_url, string $mode = null) : string
    {
        return $this->mask(Media::getUrl(Resource::get($key_or_url, Resource::TYPE_ASSET_IMAGES) ?? $key_or_url, $mode));
    }

    /**
     * @param string $key_or_url
     * @param string|null $mode
     * @param string|null $alt
     * @return string
     * @throws Exception
     */
    public function imageTag(string $key_or_url, string $mode = null, string $alt = null) : string
    {
        $altTag = (
            $alt
            ? ' alt="' . $alt . '"'
            : null
        );
        
        return '<img src="' . ($this->imageUrl($key_or_url, $mode) ?? Media::getIcon("spacer", $mode)) . '"' . $altTag . ' />';
    }

    protected function audioUrl()
    {
    }
    protected function videoUrl()
    {
    }

    /**
     * @param string $url
     * @return string
     */
    private function mask(string $url) : string
    {
        $env                                    = Env::getAll();
        $env["{"]                               = "";
        $env["}"]                               = "";

        return str_ireplace(array_keys($env), array_values($env), $url);
    }
}
