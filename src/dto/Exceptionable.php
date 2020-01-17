<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Debug;

/**
 * Trait Exceptionable
 */
trait Exceptionable
{
    private $label_internal_server_error    = "Internal Server Error";

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
    private $debug                           = array();

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
            $this->error = $this->label_internal_server_error;
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
        if (!empty($data)) {
            if ($bucket) {
                $this->debug[$bucket] = $data;
            } elseif (is_array($data)) {
                $this->debug = array_replace($this->debug, $data);
            } else {
                array_push($this->debug, $data);
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
            $vars["debug"]["exTime - App"]  = Debug::exTimeApp();
        }
    }

    /**
     * @return string
     */
    public function toLog() : string
    {
        return $this->error;
    }
}
