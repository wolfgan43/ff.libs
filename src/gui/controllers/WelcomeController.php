<?php
namespace phpformsframework\libs\gui\controllers;

use phpformsframework\libs\gui\Controller;
use phpformsframework\libs\Request;

/**
 * Class Welcome
 * @package phpformsframework\libs\gui\pages
 */
class WelcomeController extends Controller
{

    /**
     *
     */
    public function get(): void
    {
        $this->default(["content" => Request::hostname()]);

    }

    /**
     *
     */
    public function post(): void
    {
        // TODO: Implement post() method.
    }

    /**
     *
     */
    public function put(): void
    {
        // TODO: Implement put() method.
    }

    /**
     *
     */
    public function delete(): void
    {
        // TODO: Implement delete() method.
    }

    /**
     *
     */
    public function patch(): void
    {
        // TODO: Implement patch() method.
    }
}
