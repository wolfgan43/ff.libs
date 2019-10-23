<?php
namespace phpformsframework\libs;

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\storage\Database;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\storage\Media;
use phpformsframework\libs\storage\Orm;

/**
 * Trait EndUserManager
 * @package phpformsframework\libs
 */
trait EndUserManager
{
    /**
     * @todo da tipizzare
     * @return Kernel
     */
    public static function &Server()
    {
        return Kernel::load();
    }

    /**
     * @todo da tipizzare
     * @return Request
     */
    public static function &request()
    {
        return Kernel::load()->Request;
    }

    /**
     * @todo da tipizzare
     * @return Response
     */
    public static function &response()
    {
        return Kernel::load()->Response;
    }
    /**
     * @todo da tipizzare
     * @param string $name
     * @param null|string $value
     * @param bool $permanent
     * @return mixed|null
     */
    public static function env(string $name, string $value = null, bool $permanent = false)
    {
        return ($value === null
            ? Env::get($name)
            : Env::set($name, $value, $permanent)
        );
    }

    /**
     * @param array $value
     */
    public static function envFill(array $value) : void
    {
        Env::fill($value);
    }

    /**
     * @param string $name
     * @param callable $func
     * @param int $priority
     */
    public static function on(string $name, callable $func, int $priority = Hook::HOOK_PRIORITY_NORMAL) : void
    {
        Hook::register($name, $func, $priority);
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param null|mixed $ref
     * @param null|mixed $params
     * @return array|null
     */
    public static function hook(string $name, &$ref = null, $params = null)
    {
        return Hook::handle($name, $ref, $params);
    }

    /**
     * @param string $message
     */
    public static function throwException(string $message) : void
    {
        Error::register($message, static::ERROR_BUCKET);
    }

    /**
     * @param string $message
     */
    public static function throwWarning(string $message) : void
    {
        Error::register($message, static::ERROR_BUCKET);
    }

    /**
     * @return bool
     */
    public static function isError() : bool
    {
        return Error::check(static::ERROR_BUCKET);
    }

    /**
     * @return string|null
     */
    public static function dumpError() : ?string
    {
        return Error::raise(static::ERROR_BUCKET);
    }

    /**
     * @return array
     */
    public static function dumpDatabase() : array
    {
        return Database::dump();
    }
    /**
     * @param string $code
     * @param null|string $language
     * @return string
     */
    public static function translate(string $code, string $language = null) : string
    {
        return Translator::get_word_by_code($code, $language);
    }

    /**
     * @param string $ormModel
     * @return storage\OrmModel
     */
    public static function orm(string $ormModel) : storage\OrmModel
    {
        return Orm::getInstance($ormModel);
    }
    /**
     * @param string $file_type
     * @param null|string $file_disk_path
     * @return storage\FilemanagerAdapter
     */
    public static function fileGetContent(string $file_type, string $file_disk_path = null) : storage\FilemanagerAdapter
    {
        return Filemanager::getInstance($file_type, $file_disk_path);
    }

    /**
     * @param string $bucket
     * @return float|null
     */
    public static function stopWatch(string $bucket) : ?float
    {
        return Debug::stopWatch($bucket);
    }

    /**
     * @todo da tipizzare
     * @param string $file_disk_path
     * @param null|string $mode
     * @param string $key
     * @return array|string
     */
    public static function mediaUrl(string $file_disk_path, string $mode = null, string $key = "url")
    {
        return Media::getUrl($file_disk_path, $mode, $key);
    }

    /**
     * @return cache\MemAdapter
     */
    public static function cacheMem() : cache\MemAdapter
    {
        return Mem::getInstance();
    }
}
