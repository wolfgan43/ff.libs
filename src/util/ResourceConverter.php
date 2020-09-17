<?php
namespace phpformsframework\libs\util;

use Exception;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\storage\Media;

/**
 * Trait ResourceConverter
 * @package phpformsframework\libs\util
 */
trait ResourceConverter
{
    /**
     * @param string $relative_path
     * @return string
     */
    protected function getWebUrl(string $relative_path) : string
    {
        return Kernel::$Environment::SITE_PATH . $relative_path;
    }

    /**
     * @param string $file_disk_path
     * @param null|string $mode
     * @param string $key
     * @return array|string
     * @throws Exception
     */
    protected function getWebUrlAsset(string $file_disk_path, string $mode = null, string $key = "url") : ?string
    {
        return Media::getUrl($file_disk_path, $mode, $key);
    }

    /**
     * @param string $code
     * @param null|string $language
     * @return string
     * @throws Exception
     */
    protected function translate(string $code, string $language = null) : string
    {
        return Translator::getWordByCode($code, $language);
    }
}
