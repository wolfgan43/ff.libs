<?php
namespace phpformsframework\libs;

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\international\Translator;
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
     * @return Kernel
     */
    public static function &Server()
    {
        return Kernel::load();
    }

    /**
     * @return Request
     */
    public static function &request()
    {
        return Kernel::load()->Request;
    }

    /**
     * @return Response
     */
    public static function &response()
    {
        return Kernel::load()->Response;
    }
    /**
     * @param string $name
     * @param null|string $value
     * @param bool $permanent
     * @return mixed|null
     */
    public static function env($name, $value = null, $permanent = false)
    {
        if ($value === null) {
            if (is_array($name)) {
                Env::fill($name);
            } else {
                return Env::get($name);
            }
        } else {
            Env::set($name, $value, $permanent);
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $func
     * @param null|int $priority
     */
    public static function on($name, $func, $priority = null)
    {
        Hook::register($name, $func, $priority);
    }

    /**
     * @param string $name
     * @param null|mixed $ref
     * @param null|mixed $params
     * @return array|null
     */
    public static function hook($name, &$ref = null, $params = null)
    {
        return Hook::handle($name, $ref, $params);
    }

    /**
     * @param string $message
     */
    public static function throwException($message)
    {
        Error::register($message, static::ERROR_BUCKET);
    }

    /**
     * @param string $message
     */
    public static function throwWarning($message)
    {
        Error::register($message, static::ERROR_BUCKET);
    }

    /**
     * @return bool
     */
    public static function isError()
    {
        return Error::check(static::ERROR_BUCKET);
    }
    /**
     * @param string $code
     * @param null|string $language
     * @return string
     */
    public static function translate($code, $language = null)
    {
        return Translator::get_word_by_code($code, $language);
    }

    /**
     * @param string $ormModel
     * @return storage\OrmModel
     */
    public static function orm($ormModel)
    {
        return Orm::getInstance($ormModel);
    }
    /**
     * @param string $file_type
     * @param null|string $file_disk_path
     * @return storage\FilemanagerAdapter
     */
    public static function fileGetContent($file_type, $file_disk_path = null)
    {
        return Filemanager::getInstance($file_type, $file_disk_path);
    }

    /**
     * @param string $bucket
     * @return float|null
     */
    public static function stopWatch($bucket)
    {
        return Debug::stopWatch($bucket);
    }

    /**
     * @param string $file_disk_path
     * @param null|string $mode
     * @param string $key
     * @return array|string
     */
    public static function mediaUrl($file_disk_path, $mode = null, $key = "url")
    {
        return Media::getUrl($file_disk_path, $mode, $key);
    }

    /**
     * @return cache\MemAdapter
     */
    public static function cacheMem()
    {
        return Mem::getInstance();
    }
}
