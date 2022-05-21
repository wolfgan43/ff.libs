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
namespace ff\libs\dto;

/**
 * Class ConfigPage
 * @package ff\libs\dto
 *
 * name
 * scope
 * hide
 * default
 * validator
 * validator_range
 * validator_mime
 * required
 * required_ifnot
 *
 * vpn**
 * auth**
 */
class RequestPageRules
{
    public $header          = array();
    public $query           = array();
    public $body            = array();

    /**
     * @param array $pageRules
     */
    public function set(array $pageRules) : void
    {
        if (isset($pageRules["header"])) {
            $this->setVar($this->header, $pageRules["header"]);
        }
        if (isset($pageRules["query"])) {
            $this->setVar($this->query, $pageRules["query"]);
        }
        if (isset($pageRules["body"])) {
            $this->setVar($this->body, $pageRules["body"]);
        }
    }

    /**
     * @param array|null $rules
     * @param array $vars
     */
    private function setVar(array &$vars, array $rules = null) : void
    {
        $vars = array_replace((array) $rules, $vars);
    }
}
