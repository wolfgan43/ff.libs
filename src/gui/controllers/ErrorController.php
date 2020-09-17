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
    private $description        = null;
    private $email_support      = null;

    protected $http_status_code = 404;

    public function error(int $status, string $msg = null, string $description = null): Controller
    {
        $this->description      = $description;

        return parent::error($status, $msg);
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description) : self
    {
        $this->description      = $description;

        return $this;
    }

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
    public function get() : void
    {
        $error = Translator::getWordByCode($this->error);

        $errorView = $this->view()
            ->assign("site_path", Constant::SITE_PATH)
            ->assign("title", $error)
            ->assign("description", Translator::getWordByCode($this->description ?? Error::getErrorMessage($this->http_status_code)));

        if ($this->email_support) {
            $errorView->assign("email_support", $this->email_support);
            $errorView->parse("SezButtonSupport", false);
        }

        $this->layout()
            ->addContent($errorView)
            ->debug($error)
            ->display();
    }

    /**
     * @return mixed
     */
    public function post() : void
    {
        // TODO: Implement post() method.
    }

    /**
     * @return mixed
     */
    public function put() : void
    {
        // TODO: Implement put() method.
    }

    /**
     * @return mixed
     */
    public function delete() : void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return mixed
     */
    public function patch() : void
    {
        // TODO: Implement patch() method.
    }
}
