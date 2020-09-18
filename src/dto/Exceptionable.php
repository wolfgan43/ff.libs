<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Kernel;

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
     * @param int|null $code
     * @return bool
     */
    public function isError(int $code = null) : bool
    {
        return (
            $code
            ? $this->status == $code
            : $this->status >= 400
        );
    }

    /**
     * @param string|null $msg
     */
    private function setError(string $msg = null) : void
    {
        if (Kernel::$Environment::DEBUG || $this->status < 500) {
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
     * @return $this
     */
    public function error(int $status, string $msg = null) : self
    {
        $this->status                       = $status;
        $this->setError($msg);

        return $this;
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
