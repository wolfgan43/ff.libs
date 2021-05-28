<?php
namespace phpformsframework\libs\gui\components;

use Exception;
use phpformsframework\libs\international\Translator;

/**
 * Class Field
 * @package phpformsframework\libs\gui\components
 */
class Field
{
    private static $count               = 0;

    public static function select(string $name, string $source, array $display_fields = null, string $key = null)
    {
        return new static($name, [
            "template"                  => "select"
        ]);
    }

    public static function checkbox(string $name, string $source, array $display_fields = null, string $key = null)
    {
        return new static($name, [
            "template"                  => "check",
            "type"                      => "checkbox"
        ]);
    }

    public static function radio(string $name, string $source, array $display_fields = null, string $key = null)
    {
        return new static($name, [
            "template"                  => "check",
            "type"                      => "radio"
        ]);
    }


    public static function hex(string $name) : self
    {
        return new static($name, [
            "type"              => "color",
            "validator"         => "hex"
        ]);
    }
    public static function date(string $name) : self
    {
        return new static($name, [
            "type"              => "date",
            "validator"         => "date"
        ]);
    }
    public static function datetime(string $name) : self
    {
        return new static($name, [
            "type"              => "datetime-local",
            "validator"         => "datetime"
        ]);
    }
    public static function email(string $name) : self
    {
        return new static($name, [
            "type"              => "email",
            "validator"         => "email"
        ]);
    }
    public static function upload(string $name) : self
    {
        return new static($name, [
            "type"              => "file",
            "template_class"    => "file",
            "validator"         => "file"
        ]);
    }
    public static function image(string $name) : self
    {
        return new static($name, [
            "type"              => "file",
            "template_class"    => "file",
            "validator"         => "file"
        ]);
    }
    public static function month(string $name) : self
    {
        return new static($name, [
            "type"              => "month",
        ]);
    }
    public static function int(string $name) : self
    {
        return new static($name, [
            "type"              => "number",
            "validator"         => "int"
        ]);
    }
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
    public static function password(string $name) : self
    {
        return new static($name, [
            "type"              => "password",
            "validator"         => "double"
        ]);
    }
    public static function range(string $name) : self
    {
        return new static($name, [
            "type"              => "range"
        ]);
    }
    public static function reset(string $name) : self
    {
        return new static($name, [
            "type"              => "reset"
        ]);
    }
    public static function search(string $name) : self
    {
        return new static($name, [
            "type"              => "search"
        ]);
    }
    public static function tel(string $name) : self
    {
        return new static($name, [
            "type"              => "tel",
            "validator"         => "tel",
        ]);
    }
    public static function string(string $name) : self
    {
        return new static($name, [
            "type"              => "text"
        ]);
    }
    public static function time(string $name) : self
    {
        return new static($name, [
            "type"              => "time",
            "validator"         => "time",
        ]);
    }
    public static function url(string $name) : self
    {
        return new static($name, [
            "type"              => "url",
            "validator"         => "url",
        ]);
    }
    public static function week(string $name) : self
    {
        return new static($name, [
            "type"              => "week"
        ]);
    }
    public static function video(string $name) : self
    {
        return new static($name, [
            "type"              => "url",
            "validator"         => "url",
        ]);
    }
    public static function audio(string $name) : self
    {
        return new static($name, [
            "type"              => "url",
            "validator"         => "url",
        ]);
    }
    public static function text(string $name) : self
    {
        return new static($name, [
            "tag"               => "textarea",
            "template"          => "textarea",
            "validator"         => "text",
        ]);
    }
    public static function readonly(string $name) : self
    {
        return new static($name, [
            "template"          => "readonly",
            "properties"        => [
                "disabled"      => 'null'
            ]
        ]);
    }
    public static function bool(string $name) : self
    {
        return new static($name, [
            "type"              => "checkbox",
            "template"          => "check",
            "validator"         => "bool",
            "default"           => true
        ]);
    }

    public const BUCKET                 = 'df';

    protected const TEMPLATE_CLASS      = [
        "select"                => [
            "control"           => "custom-select",
            "label"             => null
        ],
        "check"                 => [
            "wrapper"           => "form-check",
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
                                    null        => "",
                                    "valid"     => "is-valid",
                                    "invalid"   => "is-invalid"
                                ]
        ]
    ];

    private const TAG_DEFAULT           = 'input';
    private const TEMPLATE_DEFAULT      = 'default';
    private const TEMPLATE_LABEL        = 'label';

    private const TEMPLATE_ENGINE = [
        "label"     => '<label[CLASS][PROPERTIES][DATA]>[VALUE]</label>',
        "readonly"  => '<span[CLASS][DATA]>[VALUE_RAW]</span>',
        "select"    => '[LABEL]<select[NAME][CLASS][PROPERTIES][DATA]>[OPTIONS]</select>[FEEDBACK]',
        "check"     => '<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[LABEL][FEEDBACK]',
        "textarea"  => '[LABEL]<[TAG][NAME][CLASS][PROPERTIES][DATA]>[VALUE_RAW]</[TAG]>[FEEDBACK]',
        "default"   => '[LABEL]<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[FEEDBACK]',
        "group"     => '[LABEL]<div[GROUP_CLASS]>[PRE]<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[POST][FEEDBACK]</div>',
    ];

    private $name               = null;
    private $control            = null;

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

    /**
     * Field constructor.
     * @param string $name
     * @param array $control
     */
    public function __construct(string $name, array $control)
    {
        $this->name             = $name;
        $this->control          = (object) $control;
        if (empty($this->control->template)) {
            $this->control->template = self::TEMPLATE_DEFAULT;
        }
        if (empty($this->control->template_class)) {
            $this->control->template_class = $this->control->template;
        }
    }

    protected function html() : string
    {
        return (empty(static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"])
            ? $this->control()
            : '<div class=' . static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"] . '>' .
                $this->control() .
            '</div>'
        );
    }

    private function parseLabel() : ?string
    {
        if ($this->label === null) {
            return null;
        }

        $id                             = self::BUCKET . self::$count;
        $this->properties["id"]         = $id;
        $this->label_properties["for"]  = $id;

        $this->label_class["default"]   = static::TEMPLATE_CLASS[$this->control->template_class]["label"];

        return str_replace(
            [
                "[VALUE]",
                "[CLASS]",
                "[PROPERTIES]",
                "[DATA]"
            ],
            [
                $this->label,
                $this->parseClasses(array_filter($this->label_class)),
                $this->parseProperties($this->label_properties),
                $this->parseData($this->label_data),

            ],
            static::TEMPLATE_ENGINE[self::TEMPLATE_LABEL]
        );
    }

    private function parseFeedBack() : ?string
    {
        $this->classes["feedback"] = static::TEMPLATE_CLASS["feedback"]["control"][$this->message_type];

        return ($this->message
            ? '<div class="' . static::TEMPLATE_CLASS["feedback"][$this->message_type] . '">' . $this->message . '</div>'
            : null
        );
    }

    private function control() : string
    {
        self::$count++;

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
                "[DATA]"
            ],
            [
                $this->parseLabel(),
                $this->parseFeedBack(),
                ($this->control->tag ?? self::TAG_DEFAULT),
                $this->parseControlType(),
                $this->parseControlName(),
                $this->parseControlValue(),
                $this->value,
                $this->parseControlClass(),
                $this->parseControlProperties(),
                $this->parseControlData()
            ],
            $this->parseTemplate()
        );
    }

    private function parseTemplate() : string
    {
        return ($this->control->template == self::TEMPLATE_DEFAULT && ($this->pre || $this->post)
            ? $this->parseTemplateGroup()
            : static::TEMPLATE_ENGINE[$this->control->template]
        );
    }

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

    private function parseControlPre() : ?string
    {
        return $this->parseControlAttach($this->pre ?? $this->control->pre ?? null, static::TEMPLATE_CLASS["group"]["pre"]);
    }

    private function parseControlPost() : ?string
    {
        return $this->parseControlAttach($this->post ?? $this->control->post ?? null, static::TEMPLATE_CLASS["group"]["post"]);
    }

    private function parseControlAttach(string $value, string $class) : ?string
    {
        return ($value
            ? '<div class="' . $class . '">' . $value . '</div>'
            : null
        );
    }

    private function parseControlType() : ?string
    {
        return (!empty($this->control->type)
            ? ' type="' . $this->control->type . '"'
            : null
        );
    }
    private function parseControlName() : string
    {
        return ' name="' . $this->name . '"';
    }
    private function parseControlValue() : ?string
    {
        return (!empty($value = $this->value ?? $this->control->default ?? null)
            ? ' value="' . $value . '"'
            : null
        );
    }
    private function parseControlClass() : ?string
    {
        $this->classes["default"]   = static::TEMPLATE_CLASS[$this->control->template_class]["control"];

        return $this->parseClasses(array_filter($this->classes));
    }

    private function parseControlProperties() : ?string
    {
        return $this->parseProperties(array_replace($this->properties, $this->control->properties ?? []));
    }

    private function parseControlData() : ?string
    {
        return $this->parseData($this->data);
    }

    private function parseClasses(array $classes) : ?string
    {
        return (!empty($classes)
            ? ' class="' . implode(" ", $classes) . '"'
            : null
        );
    }
    private function parseProperties(array $properties) : ?string
    {
        return (!empty($properties)
            ? ' ' . str_replace('=null', '', http_build_query($properties, "", " "))
            : null
        );
    }

    private function parseData(array $data) : ?string
    {
        return (!empty($data)
            ? ' data-' . str_replace("&", " data-", http_build_query($data, "", ""))
            : null
        );
    }

    public function display() : string
    {
        return $this->html();
    }

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

    public function pre(string $html) : self
    {
        $this->pre = $html;

        return $this;
    }
    public function post(string $html) : self
    {
        $this->post = $html;

        return $this;
    }

    public function value(string $value) : self
    {
        $this->value = $value;

        return $this;
    }

    public function placeholder(string $value, bool $translate = false) : self
    {
        $this->properties["placeholder"] = (
            $translate
            ? Translator::getWordByCode($value)
            : $value
        );

        return $this;
    }

    public function isRequired(bool $isRequired = true) : self
    {
        return $this->setAttrNull("required", $isRequired);
    }

    public function isReadOnly(bool $isReadOnly = true) : self
    {
        return $this->setAttrNull("disabled", $isReadOnly);
    }

    public function class(string $classes) : self
    {
        $this->classes["custom"] = $classes;

        return $this;
    }

    public function properties(array $properties) : self
    {
        $this->properties = array_replace($this->properties, $properties);

        return $this;
    }

    public function data(array $data) : self
    {
        $this->data = $data;

        return $this;
    }

    public function validator(string $name) : self
    {
        //@todo da finire
        return $this;
    }

    public function source(string $name, array $display, string $key) : self
    {
        //@todo da finire
        return $this;
    }

    private function setAttrNull(string $name, bool $isset) : self
    {
        if ($isset) {
            $this->properties[$name] = 'null';
        } else {
            unset($this->properties[$name]);
        }

        return $this;
    }
}
