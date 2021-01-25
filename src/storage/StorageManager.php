<?php
namespace phpformsframework\libs\storage;

use Exception;
use phpformsframework\libs\Request;

/**
 * Trait DataAccess
 * @package phpformsframework\libs\storage
 */
trait StorageManager
{
    /**
    * @param string $filepath
    * @return bool
    */
    private static function fileExists(string $filepath): bool
    {
        return file_exists($filepath);
    }

    /**
     * @return FilemanagerFs
     */
    private static function fileManager() : FilemanagerFs
    {
        return new FilemanagerFs();
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param string $method
     * @param array|null $headers
     * @param int $timeout
     * @return string
     * @throws Exception
     */
    private static function fileGetContents(string $url, array $params = null, string $method = Request::METHOD_POST, array $headers = null, int $timeout = 10) : string
    {
        return FilemanagerWeb::fileGetContents($url, $params, $method, $timeout, null, null, null, null, $headers);
    }

    /**
     * @param string $file_path
     * @param string $data
     * @return bool
     */
    private static function filePutContents(string $file_path, string $data) : bool
    {
        return FilemanagerWeb::filePutContents($file_path, $data);
    }

    /**
     * @param string $path
     * @param int $flag
     * @param array|null $extension
     * @return array
     */
    private static function fileScan(string $path, int $flag, array $extension = null) : array
    {
        return FilemanagerScan::scan([$path => ["flag" => $flag, "extension" => $extension]]);
    }

    /**
     * @param string $filename
     * @return string
     */
    private static function getMimeByFilename(string $filename) : string
    {
        return Media::getMimeByFilename($filename);
    }

    public static function getExtensionByFile(string $file) : string
    {
        return Media::getExtensionByFile($file);
    }
    /**
     * @param string $ext
     * @return string
     */
    private static function getMimeByExtension(string $ext) : string
    {
        return Media::getMimeByExtension($ext);
    }

    /**
     * @param string $url
     * @param array|null $params
     * @return array
     */
    private static function getQueryByUrl(string &$url, array $params = null) : array
    {
        return FilemanagerWeb::getQueryByUrl($url, $params);
    }
}
