<?php
namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;

/**
 * Class Welcome
 * @package phpformsframework\libs\security\widgets
 */
class Welcome extends Widget
{
    use CommonTemplate;

    /**
     * @throws Exception
     */
    public function get(): void
    {
        $view       = $this->view("index");
        $config     = $view->getConfig();
        $this->displayUser($view, $config);
        $this->setLogo($view, $config);
    }

    public function post(): void
    {
        // TODO: Implement post() method.
    }

    public function put(): void
    {
        // TODO: Implement put() method.
    }

    public function delete(): void
    {
        // TODO: Implement delete() method.
    }

    public function patch(): void
    {
        // TODO: Implement patch() method.
    }
}
