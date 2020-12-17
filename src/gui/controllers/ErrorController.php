<?php
namespace phpformsframework\libs\gui\controllers;

use phpformsframework\libs\Constant;
use phpformsframework\libs\gui\Controller;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Error;
use Exception;

/**
 * Class Error
 * @package phpformsframework\libs\gui\pages
 */
class ErrorController extends Controller
{
    private $email_support      = null;

    protected $http_status_code = 404;

    /**
     * @param string $email
     * @return $this
     */
    public function setEmailSupport(string $email) : self
    {
        $this->email_support    = $email;

        return $this;
    }
    /**
     * @return mixed
     * @throws Exception
     */
    protected function get() : void
    {
        $this->addStylesheet("main");
        $this->addStylesheet("error");

        $error = Translator::getWordByCode($this->error);

        $errorView = $this->view()
            ->assign("site_path", Constant::SITE_PATH)
            ->assign("title", $error ?? Translator::getWordByCode(Error::getErrorMessage($this->http_status_code)))
            ->assign("error_code", $this->http_status_code);

        if ($this->email_support) {
            $errorView->assign("email_support", $this->email_support);
            $errorView->parse("SezButtonSupport", false);
        }
        
        $this->layout()
            ->addContent($errorView)
            ->debug($error);

    }

    /**
     * @return mixed
     */
    protected function post() : void
    {
        // TODO: Implement post() method.
    }

    /**
     * @return mixed
     */
    protected function put() : void
    {
        // TODO: Implement put() method.
    }

    /**
     * @return mixed
     */
    protected function delete() : void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return mixed
     */
    protected function patch() : void
    {
        // TODO: Implement patch() method.
    }
}
