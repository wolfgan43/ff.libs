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
namespace phpformsframework\libs\gui\components;

use Exception;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\util\ServerManager;

/**
* Class Field
* @package phpformsframework\libs\gui\components
*/
class Button
{
    use ServerManager;

    public const TYPE_PRIMARY       = "btn btn-primary";
    public const TYPE_SECONDARY     = "btn btn-secondary";
    public const TYPE_SUCCESS       = "btn btn-success";
    public const TYPE_DANGER        = "btn btn-danger";
    public const TYPE_WARNING       = "btn btn-warning";
    public const TYPE_INFO          = "btn btn-info";
    public const TYPE_LINK          = "btn";

    public const XHR_NONE           = null;
    public const XHR_MODAL          = "cm-modal";
    public const XHR_REQUEST        = "cm-xhr";

    public const TAG_BUTTON      = "button";
    public const TAG_LINK        = "a";

    private const DEFAULT_HREF      = "javascript:void(0);";

    private $id                     = null;
    private $label                  = null;
    private $icon                   = null;
    private $placeholder            = null;
    private $jsCallback             = null;

    private $url                    = null;
    private $tag                    = self::TAG_LINK;
    private $type                   = self::TYPE_LINK;
    private $xhr                    = self::XHR_NONE;
    private $redirect               = null;
    private $params                 = [];

    /**
     * @param string $id
     * @param string|null $jsCallback
     * @return Button
     */
    public static function primary(string $id, string $jsCallback = null) : self
    {
        return (new static($id, $jsCallback))
            ->type(static::TYPE_PRIMARY);
    }

    /**
     * @param string $id
     * @param string|null $jsCallback
     * @return Button
     */
    public static function secondary(string $id, string $jsCallback = null) : self
    {
        return (new static($id, $jsCallback))
            ->type(static::TYPE_SECONDARY);
    }

    /**
     * @param string $id
     * @param string|null $jsCallback
     * @return Button
     */
    public static function success(string $id, string $jsCallback = null) : self
    {
        return (new static($id, $jsCallback))
            ->type(static::TYPE_SUCCESS);
    }

    /**
     * @param string $id
     * @param string|null $jsCallback
     * @return Button
     */
    public static function danger(string $id, string $jsCallback = null) : self
    {
        return (new static($id, $jsCallback))
            ->type(static::TYPE_DANGER);
    }

    /**
     * @param string $id
     * @param string|null $jsCallback
     * @return Button
     */
    public static function warning(string $id, string $jsCallback = null) : self
    {
        return (new static($id, $jsCallback))
            ->type(static::TYPE_WARNING);
    }

    /**
     * @param string $id
     * @param string|null $jsCallback
     * @return Button
     */
    public static function info(string $id, string $jsCallback = null) : self
    {
        return (new static($id, $jsCallback))
            ->type(static::TYPE_INFO);
    }

    public static function create(string $id, array $params) : self
    {
        $button = new static($id);
        foreach ($params as $property => $value) {
            $button->$property($value);
        }

        return $button;
    }

    /**
     * @param string $id
     * @param string|null $jsCallback
     */
    public function __construct(string $id, string $jsCallback = null)
    {
        $this->id                   = $id;
        $this->jsCallback           = $jsCallback;
    }

    /**
     * @param string|null $content
     * @param bool $translate
     * @return $this
     * @throws Exception
     */
    public function placeHolder(string $content = null, bool $translate = true) : self
    {
        $this->placeholder          = $content ?? $this->id;
        if ($translate) {
            $this->placeholder      = Translator::getWordByCode($this->placeholder);
        }

        return $this;
    }

    /**
     * @param string|null $content
     * @param bool $translate
     * @param bool $encode
     * @return $this
     * @throws Exception
     */
    public function label(string $content = null, bool $translate = true, bool $encode = false) : self
    {
        $this->label                = $content ?? $this->id;
        if ($translate) {
            $this->label            = Translator::getWordByCode($this->label);
        }
        if ($encode) {
            $this->label            = htmlspecialchars($this->label);
        }

        return $this;
    }

    public function icon(string $class) : self
    {
        $this->icon                 = $class;

        return $this;
    }

    /**
     * @param string $relative_or_absolute
     * @param bool $redirect_current_url
     * @return $this
     */
    public function url(string $relative_or_absolute, bool $redirect_current_url = true) : self
    {
        $this->url                  = $relative_or_absolute;

        if ($redirect_current_url) {
            $this->redirect         = $this->requestURI();
        }

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function redirect(string $url) : self
    {
        $this->redirect             = $url;

        return $this;
    }

    public function params(array $params) : self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return $this
     */
    public function ajaxModal() : self
    {
        $this->xhr = " " . self::XHR_MODAL;

        return $this;
    }

    /**
     * @return $this
     */
    public function ajaxRequest() : self
    {
        $this->xhr = " " . self::XHR_REQUEST;

        return $this;
    }

    /**
     * @return $this
     */
    public function ajaxNone() : self
    {
        $this->xhr = "";

        return $this;
    }

    /**
     * @param bool $button
     * @return $this
     */
    public function aspect(bool $button) : self
    {
        $this->tag                  = $button ? self::TAG_BUTTON : self::TAG_LINK;

        return $this;
    }


    public function display(array $params = [], bool $isXhr = false) : string
    {
        return '<' . $this->tag . $this->parseClass($isXhr ? " " . self::XHR_MODAL : null) . $this->parseHref((bool) ($this->xhr ?? $isXhr), $params) . $this->parsePlaceholder() . $this->parseClick($params) . '>' . $this->parseIcon() . $this->parseLabel() .'</' . $this->tag . '>';
    }

    public function displayAjaxModal(array $params = []) : string
    {
        return '<' . $this->tag . $this->parseClass(" " . self::XHR_MODAL) . $this->parseHref(true, $params) . $this->parsePlaceholder() . $this->parseClick($params) . '>' . $this->parseIcon() . $this->parseLabel() .'</' . $this->tag . '>';
    }

    public function displayAjaxRequest(array $params = []) : string
    {
        return '<' . $this->tag . $this->parseClass(" " . self::XHR_REQUEST) . $this->parseHref(true, $params) . $this->parsePlaceholder() . $this->parseClick($params) . '>' . $this->parseIcon() . $this->parseLabel() .'</' . $this->tag . '>';
    }

    public function displayTpl(bool $isXhr = false) : string
    {
        return '<' . $this->tag . $this->parseClass($isXhr ? " " . self::XHR_MODAL : null) . $this->parseHref((bool) ($this->xhr ?? $isXhr)) . $this->parsePlaceholder() . $this->parseClick() . '>' . $this->parseIcon() . $this->parseLabel() .'</' . $this->tag . '>';
    }

    private function type(string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    private function parseHref(bool $isXhr, array $params = []) : ?string
    {
        return ($this->tag == self::TAG_LINK
            ? ' href="' . $this->parseUrlWithQuery($this->url ? $this->parseUrl() : $this->defaultUrl(), $params, $isXhr) . '"'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function parseUrl() : ?string
    {
        return (strpos($this->url, "://") === false && strpos($this->url, "/") !== 0
            ? $this->pathinfo() . "/" . $this->url
            : $this->url
        );
    }

    /**
     * @return string
     */
    private function defaultUrl() : string
    {
        return $this->pathinfo() . "/" . $this->id;
    }

    private function parseUrlWithQuery(string $url, array $params, bool $isXhr) : ?string
    {
        $params = $params + $this->params;

        if (!$isXhr) {
            $params["redirect"] = $this->redirect ?? $this->requestURI();
        }

        return $url . (
            $params
            ?  (strpos($url, "?") === false ? "?" : "&") . http_build_query($params)
            : null
        );
    }



    private function parseClass(string $xhr = null) : string
    {
        return ' class="' . $this->type . ($this->xhr ?? $xhr) . '"';
    }

    private function parseIcon() : ?string
    {
        return ($this->icon
            ? '<i class="' . $this->icon . '"></i> '
            : null
        );
    }

    private function parsePlaceholder() : ?string
    {
        return ($this->placeholder
            ? ' title="' . htmlspecialchars($this->placeholder, ENT_QUOTES, null, false) . '"'
            : null
        );
    }

    private function parseLabel() : ?string
    {
        return $this->label ?? (!$this->icon ? $this->id : null);
    }

    private function parseClick(array $params = null) : ?string
    {
        return ($this->jsCallback
            ? ' onclick="' . str_replace(['"', '();'], ['&quot;', ''], $this->jsCallback) . '(' . $this->callbackParams($params) . ');'. '"'
            : null
        );
    }

    private function callbackParams(array $params = null) : ?string
    {
        $params = ($params ?? []) + $this->params;
        return ($params
            ? str_replace('"', "'", json_encode($params))
            : null
        );
    }
}
