<?php
namespace phpformsframework\libs\gui\components;

use Exception;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Orm;

/**
 * Class Field
 * @package phpformsframework\libs\gui\components
 */
class Field
{
    private static $count               = 0;

    /**
     * @param string $name
     * @param array|null $fill
     * @return Field
     */
    public static function select(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "select"
        ]))->setOptions($fill);
    }

    /**
     * @param string $name
     * @param array|null $fill
     * @return Field
     */
    public static function list(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "select",
            "properties"                => [
                "multiple"              => "null"
            ]
        ]))->setOptions($fill)
            ->isMulti(true);
    }

    /**
     * @param string $name
     * @param array $fill
     * @return static
     */
    public static function check(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "check",
            "type"                      => "checkbox"
        ]))->setOptions($fill)
            ->isMulti(true);
    }

    /**
     * @param string $name
     * @param array $fill
     * @return static
     */
    public static function radio(string $name, array $fill = [])
    {
        return (new static($name, [
            "template"                  => "check",
            "type"                      => "radio"
        ]))->setOptions($fill);
    }

    /**
     * @param string $name
     * @return static
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
     */
    public static function datetime(string $name) : self
    {
        return new static($name, [
            "type"              => "datetime-local",
            "validator"         => "datetime"
        ]);
    }

    /**
     * @param string $name
     * @return static
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
     */
    public static function password(string $name) : self
    {
        return new static($name, [
            "type"              => "password",
            "validator"         => "double"
        ]);
    }

    /**
     * @param string $name
     * @return static
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
     */
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

    protected const TEMPLATE_ENGINE     = [
        "label"     => '<label[CLASS][PROPERTIES][DATA]>[VALUE]</label>',
        "readonly"  => '<span[CLASS][DATA]>[VALUE_RAW]</span>',
        "select"    => '[LABEL]<select[NAME][CLASS][PROPERTIES][DATA]>[OPTIONS]</select>[FEEDBACK]',
        "check"     => '<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[LABEL][FEEDBACK]',
        "textarea"  => '[LABEL]<[TAG][NAME][CLASS][PROPERTIES][DATA]>[VALUE_RAW]</[TAG]>[FEEDBACK]',
        "default"   => '[LABEL]<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[FEEDBACK]',
        "group"     => '[LABEL]<div[GROUP_CLASS]>[PRE]<[TAG][TYPE][NAME][VALUE][CLASS][PROPERTIES][DATA] />[POST][FEEDBACK]</div>',
    ];

    protected const SEP_DEFAULT         = ',';

    private const TAG_DEFAULT           = 'input';
    private const TEMPLATE_DEFAULT      = 'default';
    private const TEMPLATE_LABEL        = 'label';

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

    protected $options          = [];
    protected $options_multi    = false;

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

    /**
     * @return string
     */
    protected function html() : string
    {
        return (empty(static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"])
            ? $this->control()
            : '<div class=' . static::TEMPLATE_CLASS[$this->control->template_class]["wrapper"] . '>' .
                $this->control() .
            '</div>'
        );
    }

    /**
     * @return string|null
     */
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
                $this->parseLabel(),
                $this->parseFeedBack(),
                ($this->control->tag ?? self::TAG_DEFAULT),
                $this->parseControlType(),
                $this->parseControlName(),
                $this->parseControlValue(),
                $this->value,
                $this->parseControlClass(),
                $this->parseControlProperties(),
                $this->parseControlData(),
                $this->parseOptions(),
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
        return $this->parseControlAttach($this->pre ?? $this->control->pre ?? null, static::TEMPLATE_CLASS["group"]["pre"]);
    }

    /**
     * @return string|null
     */
    private function parseControlPost() : ?string
    {
        return $this->parseControlAttach($this->post ?? $this->control->post ?? null, static::TEMPLATE_CLASS["group"]["post"]);
    }

    /**
     * @param string $value
     * @param string $class
     * @return string|null
     */
    private function parseControlAttach(string $value, string $class) : ?string
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
     */
    private function parseOptions() : ?string
    {
        ksort($this->options, SORT_NATURAL | SORT_FLAG_CASE);

        return '<option value="">' . Translator::getWordByCode("None") . '</option>' . $this->setOptionSelected(implode("\n", $this->options));
    }

    private function setOptionSelected(string $options) : string
    {
        $search                 = [];
        $replace                = [];
        if ($this->options_multi) {
            foreach (explode(static::SEP_DEFAULT, $this->value) as $value) {
                $search[]       = 'value="' . $value . '"';
                $replace[]      = 'value="' . $value . '" selected';
            }
        } else {
            $search[]           = 'value="' . $this->value . '"';
            $replace[]          = 'value="' . $this->value . '" selected';
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
            ? ' data-' . str_replace("&", " data-", http_build_query($data, "", ""))
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
     * @param string|null $value
     * @param string|null $validator
     * @return $this
     */
    public function value(string $value = null, string $validator = null) : self
    {
        if ($validator) {
            $this->control->validator = $validator;
        }

        $this->value = $value;

        return $this;
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

                $this->options[$value]          = $this->setOption($record[$key], $value);
            }
        } else {
            preg_match_all('#\[([^]]+)]#', $display_fields, $fields);
            $fields[1][]                        = $key;
            foreach ($orm->read($fields[1])->getAllArray() as $record) {
                $value                          = str_replace($fields[0], array_values($record), $display_fields);

                $this->options[$value]          = $this->setOption($record[$key], $value);
            }
        }

        return $this;
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
    private function setOptions(array $options) : self
    {
        foreach ($options as $key => $value) {
            $this->options[$value]          = $this->setOption($key, $value);
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
            $this->properties[$name] = 'null';
        } else {
            unset($this->properties[$name]);
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
