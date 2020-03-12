<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Config;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\storage\Orm;

/**
 * Trait Exceptionable
 */
trait Exceptionable
{
    /**
     * @var string
     */
    public $error                           = "";
    /**
     * @var int
     */
    public $status                          = 0;
    /**
     * @var mixed|null
     */
    private static $debug                           = array();

    /**
     * @param int|null $code
     * @return bool
     */
    public function isError(int $code = null) : bool
    {
        return (bool) (
            $code
            ? isset($this->status[$code])
            : $this->status
        );
    }

    /**
     * @param string|null $msg
     */
    private function setError(string $msg = null) : void
    {
        if (Debug::isEnabled() || $this->status < 500) {
            $this->error                        = (
                $this->error
                    ? $this->error . " "
                    : ""
                ) . $msg;
        } else {
            $this->error = "Internal Server Error";
        }
    }
    /**
     * @param int $status
     * @param string|null $msg
     * @param null $debug
     * @return $this
     */
    public function error(int $status, string $msg = null, $debug = null) : self
    {
        $this->status                       = $status;
        $this->setError($msg);
        if ($debug) {
            $this->debug($debug);
        }

        return $this;
    }

    /**
     * @param mixed $data
     * @param string|null $bucket
     * @return $this
     * @todo da tipizzare
     */
    public function debug($data, string $bucket = null) : self
    {
        static $count = null;
        if (!empty($data)) {
            if ($bucket) {
                if (isset(self::$debug[$bucket])) {
                    $count[$bucket] = ($count[$bucket] ?? 1) + 1;

                    $bucket .= " - " . $count[$bucket];
                }
                self::$debug[$bucket] = $data;

            } elseif (is_array($data)) {
                self::$debug = array_replace(self::$debug, $data);
            } else {
                array_push(self::$debug, $data);
            }
        }
        return $this;
    }

    /**
     * @param array $vars
     */
    private function setDebugger(array &$vars) : void
    {
        if (!Debug::isEnabled()) {
            unset($vars["debug"]);
        } else {
            $vars["debug"]                      = self::$debug;
            $vars["debug"]["exTime - Orm"]      = array_sum(Orm::exTime());
            $vars["debug"]["exTime - Conf"]     = Config::exTime();
            $vars["debug"]["exTime - App"]      = Debug::exTimeApp();
            $vars["debug"]["App - Cache"]       = (Debug::cacheDisabled() ? "off" : "on (" . Kernel::$Environment::CACHE_MEM_ADAPTER . ", " . Kernel::$Environment::CACHE_DATABASE_ADAPTER . ", " . Kernel::$Environment::CACHE_MEDIA_ADAPTER . ")");
        }
    }

    /**
     * @return string
     */
    public function toLog() : ?string
    {
        return $this->error;
    }

    /**
     * @param array $vars
     */
    private function removeExceptionVars(array &$vars)
    {
        unset($vars["status"], $vars["error"], $vars["debug"]);
    }
}
