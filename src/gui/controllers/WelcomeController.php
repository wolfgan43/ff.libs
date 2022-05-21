<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\gui\controllers;

use ff\libs\gui\Controller;
use ff\libs\util\ServerManager;
use ff\libs\Exception;

/**
 * Class Welcome
 * @package ff\libs\gui\pages
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
            ->assign(self::TPL_VAR_DEFAULT,
                $this->view()
                ->assign(
                    "content",
                    $this->getImageUrl("https://pim.beurer.com/images/produkt/{AUTH2_TOKEN_ALGO}/AS99-Side-HR_WEB.jpg", "100x100")
                    . $this->getImageUrl("", "100x100")
                    . $this->getImageTag("archive", "100x100")
                    . $this->getImageTag("https://dev.w3.org/SVG/tools/svgweb/samples/svg-files/USStates.svg")
                )
            );
        */
/*
        $this->view()
            ->assign(
                "content",
                $this->getImageUrl("https://pim.beurer.com/images/produkt/{AUTH2_TOKEN_ALGO}/AS99-Side-HR_WEB.jpg", "100x100")
                . $this->getImageUrl("", "100x100")
                . $this->getImageTag("archive", "100x100")
                . $this->getImageTag("https://dev.w3.org/SVG/tools/svgweb/samples/svg-files/USStates.svg")
            );*/

        //$this->addJavascriptAsync("https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js");
        /*
        $this->addJavascriptEmbed("console.log('ciao');", "BodyBottom");
        $this->addFont("times");
        $this->addFont("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/webfonts/fa-brands-400.ttf");
        $this->addStylesheet("icons.min", "print");
        $this->addStylesheet("https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css", "print");
        $this->addStylesheetEmbed("body { color:red;}");
        */
/*
        $this->addJsTemplate("<div></div>");
        $this->addStructuredData(array (
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => 'Executive Anvil',
            'image' =>
                array (
                    0 => 'https://example.com/photos/1x1/photo.jpg',
                    1 => 'https://example.com/photos/4x3/photo.jpg',
                    2 => 'https://example.com/photos/16x9/photo.jpg',
                ),
            'description' => 'Sleeker than ACME\'s Classic Anvil, the Executive Anvil is perfect for the business traveler looking for something to drop from a height.',
            'sku' => '0446310786',
            'mpn' => '925872'

        ));
        $this->addStructuredData([
            'mpn' => 'AAAAAAAAA',
            'offers' =>
            array (
                '@type' => 'Offer',
                'url' => 'https://example.com/anvil',
                'priceCurrency' => 'USD',
                'price' => '119.99',
                'priceValidUntil' => '2020-11-20',
                'itemCondition' => 'https://schema.org/UsedCondition',
                'availability' => 'https://schema.org/InStock',
            )]);*/

        $this->view()->assign("content", $this->hostname());
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
