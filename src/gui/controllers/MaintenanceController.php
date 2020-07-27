<?php
namespace phpformsframework\libs\gui\controllers;

use phpformsframework\libs\gui\Controller;

class MaintenanceController extends Controller
{
    /**
     *
     */
    public function get(): void
    {
        $this->layout()
            ->addContent("site under maintenance")
            ->display();
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