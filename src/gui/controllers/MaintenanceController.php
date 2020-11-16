<?php
namespace phpformsframework\libs\gui\controllers;

use Exception;
use phpformsframework\libs\gui\Controller;

/**
 * Class MaintenanceController
 * @package phpformsframework\libs\gui\controllers
 */
class MaintenanceController extends Controller
{
    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $this->layout()
            ->addContent("site under maintenance")
            ->display();
    }

    /**
     *
     */
    protected function post(): void
    {
        // TODO: Implement post() method.
    }

    /**
     *
     */
    protected function put(): void
    {
        // TODO: Implement put() method.
    }

    /**
     *
     */
    protected function delete(): void
    {
        // TODO: Implement delete() method.
    }

    /**
     *
     */
    protected function patch(): void
    {
        // TODO: Implement patch() method.
    }
}
