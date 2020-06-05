<?php
namespace phpformsframework\libs;

use phpformsframework\libs\international\Translator;
use phpformsframework\libs\storage\Database;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\storage\Media;
use Exception;

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
     * @param mixed|null $value
     * @param bool $permanent
     * @return mixed|null
     */
    public static function env(string $name, $value = null, bool $permanent = false)
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
     * @param int $status
     * @param string $message
     * @throws Exception
     */
    public static function throwError(int $status, string $message) : void
    {
        throw new Exception($message, $status);
    }

    /**
     * @param string $message
     */
    public static function throwException(string $message) : void
    {
        Error::register($message, static::ERROR_BUCKET);
    }

    /**
     * @param $data
     * @param string|null $bucket
     */
    public static function debug($data, string $bucket = null) : void
    {
        Debug::set($data, $bucket ?? static::ERROR_BUCKET);
    }

    /**
     * @param bool $return
     * @return string|null
     */
    public static function dumpError(bool $return = false) : ?string
    {
        if (!$return) {
            echo Error::raise(static::ERROR_BUCKET);
            exit;
        }

        return Error::raise(static::ERROR_BUCKET);
    }

    /**
     * @param bool $return
     * @return array
     * @todo da sistemare non funziona
     */
    public static function dumpDatabase(bool $return = false) : array
    {
        if (!$return) {
            print_r(Database::dump());
            exit;
        }

        return Database::dump();
    }

    /**
     * @param bool $return
     * @return array
     */
    public static function dumpAjaxContent(bool $return = false) : ?array
    {
        if (!$return) {
            print_r(Filemanager::dumpContent());
            exit;
        }

        return Filemanager::dumpContent();
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
     * @param string|null $collection
     * @param string|null $mainTable
     * @return storage\Orm
     */
    public static function orm(string $collection = null, string $mainTable = null) : storage\Orm
    {
        return Model::orm($collection, $mainTable);
    }

    /**
     * @return bool
     */
    public static function debugEnabled() : bool
    {
        return Kernel::$Environment::DEBUG;
    }

    /**
     * @return bool
     */
    public static function cacheEnabled() : bool
    {
        return Kernel::$Environment::CACHE_BUFFER;
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
     * @return bool
     */
    public static function aclVerify()
    {
        if (($acl = App::configuration()->page->acl)  && ($user = App::getCurrentUser())) {
            $user_acl   = explode(",", $user->acl_profile);
            $acls       = explode(",", $acl);

            return !empty(array_intersect($acls, $user_acl));
        }
        return true;
    }
}
