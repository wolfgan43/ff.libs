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

use phpformsframework\libs\international\Time;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Orm;
use phpformsframework\libs\Exception;

/**
 * Class Field
 * @package phpformsframework\libs\gui\components
 */
class Field
{
    public const BUCKET                 = 'df';
    public const BUCKET_MULTI           = 'dfm';

    protected const TEMPLATE_CLASS      = [
        "select"                => [
            "control"           => "custom-select",
            "label"             => null
        ],
        "check"                 => [
            "wrapper"           => "form-check",
            "control"           => "form-check-input",
            "label"             => "form-check-label",
        ],
        "check-inline"          => [
            "wrapper"           => "form-check form-check-inline",
            "control"           => "form-check-input",
            "label"             => "form-check-label"
        ],
        "textarea"              => [
            "control"           => "form-control",
            "label"             => null
        ],
        "file"                  => [
            "wrapper"           => "custom-file",
            "control"           => "custom-file-input",
            "label"             => "custom-file-label"
        ],
        "default"               => [
            "control"           => "form-control",
            "label"             => null,
        ],
        "readonly"              => [
            "control"           => "form-control-plaintext",
            "label"             => null,
        ],
        "group"                 => [
            "wrapper"           => "input-group",
            "pre"               => "input-group-prepend",
            "post"              => "input-group-append"
        ],
        "feedback"              => [
            null                => "feedback",
            "valid"             => "valid-feedback",
            "invalid"           => "invalid-feedback",
            "control"           => [
                null            => "",
                "valid"         => "is-valid",
                "invalid"       => "is-invalid"
            ]
        ]
    ];

    protected const TEMPLATE_ENGINE     = [
        "label"                 => '<label[CLASS][PROPERTIES][DATA]>[VALUE][REQUIRED]</label>',
        "readonly"              => '<span[CLASS][DATA]>[VALUE_RAW]</span>',
        "select"                => '[LABEL]<select[NAME][CLASS][PROPERTIES][DATA]>[OPTIONS]</select>[FEEDBACK]',
        "multi"                 => '[LABEL][OPTIONS][FEEDBACK]',
        "check"                 => '<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[LABEL][FEEDBACK]',
        "textarea"              => '[LABEL]<[TAG][NAME][CLASS][PROPERTIES][DATA]>[VALUE_RAW]</[TAG]>[FEEDBACK]',
        "default"               => '[LABEL]<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[FEEDBACK]',
        "group"                 => '[LABEL]<div[GROUP_CLASS]>[PRE]<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[POST][FEEDBACK]</div>',
    ];

    protected const REQUIRED_DEFAULT    = '*';
    protected const SEP_DEFAULT         = ',';

    private const TAG_DEFAULT           = 'input';
    private const TEMPLATE_DEFAULT      = 'default';
    private const TEMPLATE_LABEL        = 'label';

    private static $count               = 0;
    private static $count_multi         = 0;
    private static $names               = [];

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function markdown(string $name) : self
    {
        return new static($name, [
            "tag"               => "textarea",
            "template"          => "textarea",
            "validator"         => "markdown",
        ]);
    }

    /**
     * @param string $name
     * @param array|null $fill
     * @return Field
     * @throws Exception
     */
    public static function select(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "select"
        ]))->fillMulti($fill);
    }

    /**
     * @param string $name
     * @param array|null $fill
     * @return Field
     * @throws Exception
     */
    public static function list(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "select",
            "properties"                => [
                "multiple"              => "null"
            ]
        ]))->isMulti(true)
            ->fillMulti($fill);
    }

    /**
     * @param string $name
     * @param array $fill
     * @return static
     * @throws Exception
     */
    public static function check(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "multi",
            "template_class"            => "check-inline",
            "type"                      => "checkbox"
        ]))->isMulti(true)
            ->fillMulti($fill);
    }

    /**
     * @param string $name
     * @param array $fill
     * @return static
     * @throws Exception
     */
    public static function radio(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "multi",
            "template_class"            => "check-inline",
            "type"                      => "radio"
        ]))->fillMulti($fill);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function hex(string $name) : self
    {
        return new static($name, [
            "type"              => "color",
            "validator"         => "hex"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function date(string $name) : self
    {
        return new static($name, [
            "type"              => "date",
            "validator"         => "date"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function datetime(string $name) : self
    {
        return new static($name, [
            "type"              => "datetime-local",
            "validator"         => "datetime",
            "convert"           => "datetime"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function email(string $name) : self
    {
        return new static($name, [
            "type"              => "email",
            "validator"         => "email"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function upload(string $name) : self
    {
        return new static($name, [
            "type"              => "file",
            "template_class"    => "file",
            "validator"         => "file"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function image(string $name) : self
    {
        return new static($name, [
            "type"              => "file",
            "template_class"    => "file",
            "validator"         => "file"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function month(string $name) : self
    {
        return new static($name, [
            "type"              => "month",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function int(string $name) : self
    {
        return new static($name, [
            "type"              => "number",
            "validator"         => "int"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function double(string $name) : self
    {
        return new static($name, [
            "type"              => "number",
            "validator"         => "double",
            "properties"        => [
                "step"          => 0.01
            ]
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function currency(string $name) : self
    {
        return new static($name, [
            "type"              => "number",
            "validator"         => "double",
            "properties"        => [
                "step"          => 0.01
            ],
            "pre"               => "&euro;"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function password(string $name) : self
    {
        return new static($name, [
            "type"              => "password",
            "validator"         => "password"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function range(string $name) : self
    {
        return new static($name, [
            "type"              => "range"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function reset(string $name) : self
    {
        return new static($name, [
            "type"              => "reset"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function search(string $name) : self
    {
        return new static($name, [
            "type"              => "search"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function tel(string $name) : self
    {
        return new static($name, [
            "type"              => "tel",
            "validator"         => "tel",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function string(string $name) : self
    {
        return new static($name, [
            "type"              => "text"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function time(string $name) : self
    {
        return new static($name, [
            "type"              => "time",
            "validator"         => "time",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function url(string $name) : self
    {
        return new static($name, [
            "type"              => "url",
            "validator"         => "url",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function week(string $name) : self
    {
        return new static($name, [
            "type"              => "week"
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function video(string $name) : self
    {
        return new static($name, [
            "type"              => "url",
            "validator"         => "url",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function audio(string $name) : self
    {
        return new static($name, [
            "type"              => "url",
            "validator"         => "url",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function text(string $name) : self
    {
        return new static($name, [
            "tag"               => "textarea",
            "template"          => "textarea",
            "validator"         => "text",
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function readonly(string $name) : self
    {
        return new static($name, [
            "template"          => "readonly",
            "properties"        => [
                "disabled"      => 'null'
            ]
        ]);
    }

    /**
     * @param string $name
     * @return static
     * @throws Exception
     */
    public static function bool(string $name) : self
    {
        return new static($name, [
            "type"              => "checkbox",
            "template"          => "check",
            "validator"         => "bool",
            "default"           => 1
        ]);
    }

    private $name               = null;
    private $control            = null;
    private $required           = null;

    private $value              = null;
    private $classes            = [];
    private $properties         = [];
    private $data               = [];

    private $label              = null;
    private $label_class        = [];
    private $label_properties   = [];
    private $label_data         = [];

    private $message            = null;
    private $message_type       = null;
    private $pre                = null;
    private $post               = null;

    protected $options          = [];
    protected $options_multi    = false;

    /**
     * Field constructor.
     * @param string $name
     * @param array $control
     * @throws Exception
     */
    public function __construct(string $name, array $control)
    {
        if (isset(self::$names[$name])) {
            throw new Exception("Field name already exists: $name", 500);
        }
        self::$names[$name]                 = true;

        $this->name                         = $name;
        $this->control                      = (object) $control;
        if (empty($this->control->template)) {
            $this->control->template        = self::TEMPLATE_DEFAULT;
        }
        if (empty($this->control->template_class)) {
            $this->control->template_class  = $this->control->template;
        }
        if (empty($this->control->convert)) {
            $this->control->convert         = null;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function html() : string
    {
        return $this->parseWrapper($this->control());
    }

    /**
     * @param string $control
     * @return string
     */
    private function parseWrapper(string $control) : string
    {
        return (empty(static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"])
            ? $control
            : '<div class="' . static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"] . '">' .
                $control .
            '</div>'
        );
    }

    /**
     * @param bool $withID
     * @return string|null
     */
    private function parseLabel(bool $withID = true) : ?string
    {
        if ($this->label === null) {
            return null;
        }

        if ($withID) {
            $id                             = self::BUCKET . self::$count;

            $this->properties["id"]         = $id;
            $this->label_properties["for"]  = $id;
        }

        $this->label_class["default"]       = static::TEMPLATE_CLASS[$this->control->template_class]["label"];

        return str_replace(
            [
                "[VALUE]",
                "[REQUIRED]",
                "[CLASS]",
                "[PROPERTIES]",
                "[DATA]"
            ],
            [
                $this->label,
                $this->required,
                $this->parseClasses(array_filter($this->label_class)),
                $this->parseProperties($this->label_properties),
                $this->parseData($this->label_data),

            ],
            static::TEMPLATE_ENGINE[self::TEMPLATE_LABEL]
        );
    }

    /**
     * @return string|null
     */
    private function parseFeedBack() : ?string
    {
        $this->classes["feedback"] = static::TEMPLATE_CLASS["feedback"]["control"][$this->message_type];

        return ($this->message
            ? '<div class="' . static::TEMPLATE_CLASS["feedback"][$this->message_type] . '">' . $this->message . '</div>'
            : null
        );
    }


    /**
     * @return string
     * @throws Exception
     */
    private function control() : string
    {
        self::$count++;

        $this->validate();

        return str_replace(
            [
                "[LABEL]",
                "[FEEDBACK]",
                "[TAG]",
                "[TYPE]",
                "[NAME]",
                "[VALUE]",
                "[VALUE_RAW]",
                "[CLASS]",
                "[PROPERTIES]",
                "[DATA]",
                "[OPTIONS]"
            ],
            [
                $this->parseLabel($this->control->template != "multi"),
                $this->parseFeedBack(),
                ($this->control->tag ?? self::TAG_DEFAULT),
                $this->parseControlType(),
                $this->parseControlName(),
                $this->parseControlValue(),
                $this->value,
                $this->parseControlClass(),
                $this->parseControlProperties(),
                $this->parseControlData(),
                $this->parseMulti(),
            ],
            $this->parseTemplate()
        );
    }

    /**
     * @return string
     */
    private function parseTemplate() : string
    {
        return ($this->control->template == self::TEMPLATE_DEFAULT && ($this->pre || $this->post)
            ? $this->parseTemplateGroup()
            : static::TEMPLATE_ENGINE[$this->control->template]
        );
    }

    /**
     * @return string
     */
    private function parseTemplateGroup() : string
    {
        return str_replace(
            [
                "[GROUP_CLASS]",
                "[PRE]",
                "[POST]"
            ],
            [
                ' class="' . static::TEMPLATE_CLASS["group"]["wrapper"] . '"',
                $this->parseControlPre(),
                $this->parseControlPost(),
            ],
            static::TEMPLATE_ENGINE["group"]
        );
    }

    /**
     * @return string|null
     */
    private function parseControlPre() : ?string
    {
        return $this->parseControlAttach(static::TEMPLATE_CLASS["group"]["pre"], $this->pre ?? $this->control->pre ?? null);
    }

    /**
     * @return string|null
     */
    private function parseControlPost() : ?string
    {
        return $this->parseControlAttach(static::TEMPLATE_CLASS["group"]["post"], $this->post ?? $this->control->post ?? null);
    }

    /**
     * @param string $class
     * @param string|null $value
     * @return string|null
     */
    private function parseControlAttach(string $class, string $value = null) : ?string
    {
        return ($value
            ? '<div class="' . $class . '">' . $value . '</div>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function parseControlType() : ?string
    {
        return (!empty($this->control->type)
            ? ' type="' . $this->control->type . '"'
            : null
        );
    }

    /**
     * @return string
     */
    private function parseControlName() : string
    {
        return ' name="' . $this->name . '"';
    }

    /**
     * @return string|null
     */
    private function parseControlValue() : ?string
    {
        return (!empty($value = $this->value ?? $this->control->default ?? null)
            ? ' value="' . $value . '"'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function parseControlClass() : ?string
    {
        $this->classes["default"]   = static::TEMPLATE_CLASS[$this->control->template_class]["control"];

        return $this->parseClasses(array_filter($this->classes));
    }

    /**
     * @return string|null
     */
    private function parseControlProperties() : ?string
    {
        return $this->parseProperties(array_replace($this->properties, $this->control->properties ?? []));
    }

    /**
     * @return string|null
     */
    private function parseControlData() : ?string
    {
        return $this->parseData($this->data);
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function parseMulti() : ?string
    {
        if (empty($this->options)) {
            return null;
        }

        return ($this->control->template == "multi"
            ? $this->parseInputs()
            : $this->parseOptions()
        );
    }

    /**
     * @return string|null
     */
    private function parseInputs() : ?string
    {
        $this->control->template_class = self::TEMPLATE_DEFAULT;

        return '<div class="' . static::TEMPLATE_CLASS["group"]["wrapper"] . '"' . $this->parseProperties($this->properties) . $this->parseData($this->data) . '>' .
                    $this->setInputsDisabled($this->setOptionSelected(implode("\n", $this->options), "checked")) .
                '</div>';
    }

    /**
     * @param string $inputs
     * @return string|null
     */
    private function setInputsDisabled(string $inputs) : ?string
    {
        return (isset($this->control->properties["disabled"])
            ? str_replace(' value="', ' disabled value="', $inputs)
            : $inputs
        );
    }


    /**
     * @return string|null
     * @throws Exception
     */
    private function parseOptions() : ?string
    {
        ksort($this->options, SORT_NATURAL | SORT_FLAG_CASE);

        return $this->parseOptionEmpty() . $this->setOptionSelected(implode("\n", $this->options), "selected");
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function parseOptionEmpty() : ?string
    {
        return (!$this->options_multi
            ? '<option value="">' . Translator::getWordByCode("None") . '</option>'
            : null
        );
    }

    /**
     * @param string $options
     * @param string $attr
     * @return string
     */
    private function setOptionSelected(string $options, string $attr) : string
    {
        $search                 = [];
        $replace                = [];
        if ($this->options_multi) {
            foreach (explode(static::SEP_DEFAULT, $this->value) as $value) {
                $search[]       = 'value="' . $value . '"';
                $replace[]      = 'value="' . $value . '" ' . $attr;
            }
        } else {
            $search[]           = 'value="' . $this->value . '"';
            $replace[]          = 'value="' . $this->value . '" ' . $attr;
        }

        return str_replace($search, $replace, $options);
    }

    /**
     * @param array $classes
     * @return string|null
     */
    private function parseClasses(array $classes) : ?string
    {
        return (!empty($classes)
            ? ' class="' . implode(" ", $classes) . '"'
            : null
        );
    }

    /**
     * @param array $properties
     * @return string|null
     */
    private function parseProperties(array $properties) : ?string
    {
        return (!empty($properties)
            ? ' ' . str_replace('=null', '', http_build_query($properties, "", " "))
            : null
        );
    }

    /**
     * @param array $data
     * @return string|null
     */
    private function parseData(array $data) : ?string
    {
        return (!empty($data)
            ? ' data-' . str_replace(["&", "="], ["' data-", "='"], urldecode(http_build_query($data))) . "'"
            : null
        );
    }

    /**
     * @return string
     */
    public function display() : string
    {
        return $this->html();
    }

    /**
     * @param string $value
     * @param bool $translate
     * @param string|null $class
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function label(string $value, bool $translate = false, string $class = null, array $data = []) : self
    {
        $this->label = (
            $translate
            ? Translator::getWordByCode($value)
            : $value
        );
        $this->label_class["custom"] = $class;
        $this->label_data = $data;

        return $this;
    }

    /**
     * @param string $msg
     * @param bool|null $isError
     * @return $this
     */
    public function message(string $msg, bool $isError = null) : self
    {
        $this->message = $msg;

        if (isset($isError)) {
            $this->message_type = (
                $isError
                ? "invalid"
                : "valid"
            );
        }
        return $this;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function pre(string $html) : self
    {
        $this->pre = $html;

        return $this;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function post(string $html) : self
    {
        $this->post = $html;

        return $this;
    }

    /**
     * @param string|int|null $value
     * @param string|null $validator
     * @return $this
     */
    public function value($value = null, string $validator = null) : self
    {
        if ($validator) {
            $this->control->validator = $validator;
        }

        if ($value) {
            $this->value = $this->convert($value);
        }

        return $this;
    }

    /**
     * @param string|int|float|bool $value
     * @return string
     */
    public function convert($value) : string
    {
        if ($this->control->convert == "datetime") {
            $value = (
                is_numeric($value)
                ? (new Time($value))->toDateTimeLocal()
                : str_replace(" ", "T", $value)
            );
        } elseif ($this->control->validator == "bool" && $this->control->default === $value) {
            $this->control->properties["checked"] = 'null';
        }

        return $value;
    }

    /**
     * @param string $value
     * @param bool $translate
     * @return $this
     * @throws Exception
     */
    public function placeholder(string $value, bool $translate = false) : self
    {
        $this->properties["placeholder"] = (
            $translate
            ? Translator::getWordByCode($value)
            : $value
        );

        return $this;
    }

    /**
     * @param bool $isRequired
     * @return $this
     */
    public function isRequired(bool $isRequired = true) : self
    {
        $this->required = ($isRequired ? static::REQUIRED_DEFAULT : null);

        return $this->setAttrNull("required", $isRequired);
    }

    /**
     * @param bool $isReadOnly
     * @return $this
     */
    public function isReadOnly(bool $isReadOnly = true) : self
    {
        return $this->setAttrNull("disabled", $isReadOnly);
    }

    /**
     * @param string $classes
     * @return $this
     */
    public function class(string $classes) : self
    {
        $this->classes["custom"]    = $classes;

        return $this;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function properties(array $properties) : self
    {
        $this->properties = array_replace($this->properties, $properties);

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function data(array $data) : self
    {
        $this->data                             = $data;

        return $this;
    }

    /**
     * @param string $collection
     * @param string $table
     * @param string $display_fields
     * @param string|null $id
     * @return $this
     * @throws Exception
     */
    public function sourceOrm(string $collection, string $table, string $display_fields, string $id = null) : self
    {
        $fields                                 = null;
        $orm                                    = Orm::getInstance($collection, $table);
        $key                                    = $id ?? $orm->informationSchema($table)->key;

        if (strpos($display_fields, "[") === false) {
            $fields                             = [$key, $display_fields];
            foreach ($orm->read($fields)->getAllArray() as $record) {
                $value                          = $record[$display_fields];

                $this->options[$value . $record[$key]]   = $this->setMulti($record[$key], $value);
            }
        } else {
            preg_match_all('#\[([^]]+)]#', $display_fields, $fields);
            $fields[1][]                        = $key;
            foreach ($orm->read($fields[1])->getAllArray() as $record) {
                $value                          = str_replace($fields[0], array_values($record), $display_fields);

                $this->options[$value . $record[$key]]   = $this->setMulti($record[$key], $value);
            }
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function setMulti(string $key, string $value) : string
    {
        return ($this->control->template == "multi"
            ? $this->setInput($key, $value)
            : $this->setOption($key, $value)
        );
    }

    /**
     * @param string $key
     * @param string $value
     * @return string
     */
    private function setInput(string $key, string $value) : string
    {
        self::$count_multi++;

        $id = self::BUCKET_MULTI . self::$count_multi;

        return '<div class="' . static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"] . '">' .
                '<input type="' . $this->control->type . '" name="' . $this->name . ($this->options_multi ? "[]" : null) . '" value="' . $key . '" class="' . static::TEMPLATE_CLASS[$this->control->template_class]["control"] . '" id="' . $id . '"/>' .
                '<label class="' . static::TEMPLATE_CLASS[$this->control->template_class]["label"] . '" for="' . $id . '">' . $value . '</label>' .
            '</div>';
    }

    /**
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function setOption(string $key, string $value) : string
    {
        return '<option value="' . $key . '">' .  $value . '</option>';
    }

    /**
     * @param array $options
     * @return $this
     */
    private function fillMulti(array $options) : self
    {
        foreach ($options as $key => $value) {
            $this->options[$value . $key]       = $this->setMulti($key, $value);
        }

        return $this;
    }

    /**
     * @param bool $multi
     * @return $this
     */
    private function isMulti(bool $multi) : self
    {
        $this->options_multi = $multi;

        return $this;
    }
    /**
     * @param string $name
     * @param bool $isset
     * @return $this
     */
    private function setAttrNull(string $name, bool $isset) : self
    {
        if ($isset) {
            $this->control->properties[$name] = 'null';
        } else {
            unset($this->control->properties[$name]);
        }

        return $this;
    }

    /**
     *
     */
    private function validate() : void
    {
        if (isset($this->value) && !empty($this->control->validator)) {
            $validator = Validator::is($this->value, $this->value, $this->control->validator);
            if ($validator->isError()) {
                $this->message($validator->error, true);
            }
        }
    }
}
