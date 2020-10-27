<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Request;

/**
 * Trait DataAccess
 * @package phpformsframework\libs\storage
 */
trait StorageManager
{
    public static function file(string $type) : FilemanagerAdapter
    {
        return Filemanager::getInstance($type);
    }
    public static function fileManager() : FilemanagerDir
    {
        return new FilemanagerDir();
    }
    public static function fileGetContents(string $url, array $params = null, string $method = Request::METHOD_POST, array $headers = null, int $timeout = 10) : string
    {
        return Filemanager::fileGetContent($url, $params, $method, $timeout, null, null, null, null, $headers);
    }
    public static function filePutContents(string $file_path, string $data) : bool
    {
        return Filemanager::filePutContents($file_path, $data);
    }
    public static function fileScan(string $path, int $flag, array $extension = null) : ?array
    {
        return FilemanagerScan::scan([$path => ["flag" => $flag, "extension" => $extension]]);
    }
}
