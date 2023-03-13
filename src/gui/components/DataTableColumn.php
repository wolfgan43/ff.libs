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
namespace ff\libs\gui\components;

use ff\libs\Exception;
use ff\libs\international\Translator;

/**
 * Class DataTableColumn
 * @package ff\libs\gui\components
 */
class DataTableColumn
{
    private const TYPE_STRING   = 'string';

    private $id;

    private $type               = self::TYPE_STRING;
    private $label              = null;
    private $icon               = null;
    private $sort               = null;
    private $placeholder        = null;
    private $hide               = false;

    private $tplValue           = null;

    /**
     * @param string $id
     * @param array $params
     * @return static
     */
    public static function create(string $id, array $params = []) : self
    {
        $column                     = new static($id);
        foreach ($params as $property => $value) {
            $column->$property      = $value;
        }

        return $column;
    }

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id                   = $id;
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

    /**
     * @param string $class
     * @return $this
     */
    public function icon(string $class) : self
    {
        $this->icon                 = $class;

        return $this;
    }

    public function sortBy(string $key) : self
    {
        $this->sort = $key;

        return $this;
    }

    /**
     * @param bool $hide
     * @return $this
     */
    public function hide(bool $hide = true) : self
    {
        $this->hide = $hide;

        return $this;
    }

    /**
     * @param string|callable $tpl
     * @return $this
     */
    public function tpl($tpl) : self
    {
        $this->tplValue = (
        is_callable($tpl)
            ? $tpl()
            : $tpl
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden() : bool
    {
        return $this->hide;
    }

    /**
     * @param string $exclude
     * @return string|null
     */
    public function getType(string $exclude) : ?string
    {
        return ($this->type != $exclude
            ? $this->type
            : null
        );
    }

    /**
     * @param string|null $url
     * @return string|null
     * @throws Exception
     */
    public function displayLabel(string $url = null) : ?string
    {
        return ($this->hide
            ? null
            : $this->parse($url)
        );
    }

    /**
     * @return string|null
     */
    public function displayValue() : ?string
    {
        return $this->tplValue ?? $this->tplDefault();
    }

    /**
     * @return string
     */
    public function getID() : string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getSortID() : ?string
    {
        return $this->sort;
    }

    /**
     * @return string|null
     */
    private function tplDefault() : ?string
    {
        return (!empty($this->sort)
            ? "{" . $this->sort . "}"
            : "{" . $this->id . "}"
        );
    }

    /**
     * @param string|null $url
     * @return string
     * @throws Exception
     */
    private function parse(string $url = null) : string
    {
        $label = $this->label ?? Translator::getWordByCode($this->id);
        return ($this->sort && !empty($url)
            ? '<a href="' . $url . '">' . $this->parseIcon() . $label . '</a>'
            : '<span>' . $this->parseIcon() . $label . '</span>'
        );
    }

    /**
     * @return string|null
     */
    private function parseIcon() : ?string
    {
        return ($this->icon
            ? '<i class="' . $this->icon .'"></i>'
            : null
        );
    }
}
