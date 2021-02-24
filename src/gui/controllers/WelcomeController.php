<?php
namespace phpformsframework\libs\gui\controllers;

use Exception;
use phpformsframework\libs\gui\Controller;
use phpformsframework\libs\util\ServerManager;

/**
 * Class Welcome
 * @package phpformsframework\libs\gui\pages
 */
class WelcomeController extends Controller
{
    use ServerManager;
    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $this->addStylesheet("main");

        /*
        $this->layout(null, true)
            ->addFont("font.fileuploader")
            ->addContent(
                $this->view()
                ->assign(
                    "content",
                    $this->media->imageUrl("https://pim.beurer.com/images/produkt/{AUTH2_TOKEN_ALGO}/AS99-Side-HR_WEB.jpg", "100x100")
                    . $this->media->imageUrl("", "100x100")
                    . $this->media->imageTag("archive", "100x100")
                    . $this->media->imageTag("https://dev.w3.org/SVG/tools/svgweb/samples/svg-files/USStates.svg")
                )
            );
        */
/*
        $this->view()
            ->assign(
                "content",
                $this->media->imageUrl("https://pim.beurer.com/images/produkt/{AUTH2_TOKEN_ALGO}/AS99-Side-HR_WEB.jpg", "100x100")
                . $this->media->imageUrl("", "100x100")
                . $this->media->imageTag("archive", "100x100")
                . $this->media->imageTag("https://dev.w3.org/SVG/tools/svgweb/samples/svg-files/USStates.svg")
            );*/



        $this->default(["content" => Request::hostname()]);
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
