<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs\delivery\drivers;

use phpformsframework\libs\Error;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Request;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\tpl\View;

/**
 * Class MailerTemplate
 * @package phpformsframework\libs\delivery\drivers
 */
final class MailerTemplate extends Mailer
{
    //body
    private $fields                                         = array();

    private $tpl_html_path                                  = null;
    /**
     * @var View
     */
    private $tpl_html                                       = null;

    private $tpl_text_path                                  = null;
    /**
     * @var View
     */
    private $tpl_text                                       = null;

    private $body                                           = null;
    private $bodyAlt                                        = null;

    /**
     * MailerTemplate constructor.
     * @param string $template
     */
    public function __construct(string $template)
    {
        $this->loadTemplate($template);

        parent::__construct();
    }

    /**
     * @todo da tipizzare
     * @param array|string $fields
     * @return MailerTemplate
     */
    public function setMessage($fields) : self
    {
        $this->fields                                       = (
            is_array($fields)
                                                                ? $fields
                                                                : array("content" => $fields)
                                                            );
        return $this;
    }

    /**
     * @return string
     */
    protected function processSubject() : string
    {
        return str_replace(
            array_keys($this->fields),
            array_values($this->fields),
            Translator::get_word_by_code($this->subject)
        );
    }

    /**
     * @return string|null
     */
    protected function processBody() : ?string
    {
        if ($this->body === null) {
            $this->processTemplate();
        }
        return $this->body;
    }

    /**
     * @return string|null
     */
    protected function processBodyAlt() : ?string
    {
        if ($this->body === null) {
            $this->processTemplate();
        }
        return $this->bodyAlt;
    }

    /**
     * @param string $template
     */
    private function loadTemplate(string $template) : void
    {
        if ($template && is_file($template)) {
            $this->tpl_html_path                = $template;

        }

        if ($this->tpl_html_path) {
            if (is_dir(dirname($this->tpl_html_path) . "/images")) {
                Filemanager::scan(array(dirname($this->tpl_html_path) . "/images" => array("jpg", "png", "svg", "gif")), function ($image) {
                    $this->addImage($image);
                });
            }

            $this->tpl_html = new View("Html");
            $this->tpl_html->fetch($this->tpl_html_path);

            if (is_file(dirname($this->tpl_html_path) . "/default.txt")) {
                $this->tpl_text_path = dirname($this->tpl_html_path) . "/default.txt";

                $this->tpl_text = new View("Html");
                $this->tpl_text->fetch($this->tpl_text_path);
            }
        } else {
            Error::register("Template not found" . (Kernel::$Environment::DEBUG ? ": " . $this->tpl_html_path : ""), static::ERROR_BUCKET);
        }
    }

    /**
     *
     */
    private function processTemplate()
    {
        /**
         * Process Fields
         */
        if (is_array($this->fields)) {
            $count_group = 0;
            $group_type = array("Table" => true);
            foreach ($this->fields as $fields_key => $fields_value) {
                $field_type = (
                    isset($fields_value["settings"]["type"])
                                ? $fields_value["settings"]["type"]
                                : ""
                            );
                if (!empty($fields_value)) {
                    $count_row = 0;
                    foreach ($fields_value as $fields_value_key => $fields_value_value) {
                        if (strtolower($fields_value_key) == "settings") {
                            continue;
                        }
                        switch ($field_type) {
                            case "Table":
                            case "":
                                if (!empty($fields_value_value)) {
                                    foreach ($fields_value_value as $fields_value_value_key => $fields_value_value_value) {
                                        if (strtolower($fields_value_value_key) == "settings") {
                                            continue;
                                        }
                                        $this->parseMailField($fields_value_value_value, $fields_value_value_key, $field_type, $count_row);
                                    }

                                    $this->parseMailRow($field_type, true);
                                } else {
                                    $this->parseMailField($fields_value_value, $fields_key . "_" . $fields_value_key, $field_type);
                                    $this->parseMailRow($field_type);
                                }
                                break;
                            default:
                                Error::registerWarning("Type Template Mail not supported: " . $field_type, static::ERROR_BUCKET);
                        }
                        $count_row++;
                    }
                } else {
                    $this->tpl_html->assign($fields_key, $fields_value); //custom vars
                    if ($this->tpl_text) {
                        $this->tpl_text->assign($fields_key, $fields_value); //custom vars
                    }
                }

                $this->parseMailGroup($fields_key, $group_type, $field_type);

                $count_group++;
            }

            $this->tpl_html->parse("SezFields", false);
            if ($this->tpl_text) {
                $this->tpl_text->parse("SezFields", false);
            }
        }

        $this->body = (
            $this->tpl_html
            ? $this->tpl_html->display()
            : ''
        );

        $this->bodyAlt = (
            $this->tpl_text
            ? $this->tpl_text->display()
            : ''
        );
    }

    /**
     * @param string $value
     * @param array $groups
     * @param string|null $type
     */
    private function parseMailGroup(string $value, array $groups, string $type = null) : void
    {
        /*
         * Parse field html(label, value, real_name)
         */
        $this->tpl_html->parse("SezStyle" . $type, false);
        foreach ($groups as $group_key => $group_value) {
            if ($group_key != $type) {
                $this->tpl_html->assign("SezStyle" . $group_key, "");
            }
        }
        if ($type) {
            $this->tpl_html->assign("SezStyle", "");
        }
        $this->tpl_html->assign("real_name", $this->processMailField($value, "smart_url"));
        $this->tpl_html->assign("group_name", $this->processMailField($value));
        $this->tpl_html->parse("SezGroups", true);

        $this->tpl_html->assign("SezFieldLabel", "");
        $this->tpl_html->assign("SezField", "");

        foreach ($groups as $group_key => $group_value) {
            $this->tpl_html->assign("Sez" . $group_key . "FieldLabel", "");
            $this->tpl_html->assign("Sez" . $group_key . "Field", "");
            $this->tpl_html->assign("Sez" . $group_key . "Row", "");
        }

        /*
         * Parse field text(label, value, real_name)
         */
        if ($this->tpl_text) {
            $this->tpl_text->parse("SezStyle" . $type, false);
            foreach ($groups as $group_key => $group_value) {
                if ($group_key != $type) {
                    $this->tpl_text->assign("SezStyle" . $group_key, "");
                }
            }
            if ($type) {
                $this->tpl_text->assign("SezStyle", "");
            }
            $this->tpl_text->assign("real_name", $this->processMailField($value, "smart_url"));
            $this->tpl_text->assign("group_name", $this->processMailField($value));
            $this->tpl_text->parse("SezGroups", true);

            $this->tpl_text->assign("SezFieldLabel", "");
            $this->tpl_text->assign("SezField", "");

            foreach ($groups as $group_key => $group_value) {
                $this->tpl_text->assign("Sez" . $group_key . "FieldLabel", "");
                $this->tpl_text->assign("Sez" . $group_key . "Field", "");
                $this->tpl_text->assign("Sez" . $group_key . "Row", "");
            }
        }
    }


    /**
     * @param string $value
     * @param string $name
     * @param string|null $type
     * @param bool $skip_label
     */
    private function parseMailField(string $value, string $name, string $type = null, bool $skip_label = false) : void
    {
        $this->tpl_html->assign($name, $value); //custom vars
        if ($this->tpl_text) {
            $this->tpl_text->assign($name, $value); //custom vars
        }

        /*
         * Parse field html(label, value, real_name)
         */
        if (!$skip_label) {
            $this->tpl_html->assign("fields_label", $this->processMailField($name));
            $this->tpl_html->parse("Sez" . $type . "FieldLabel", true);
        }

        $this->tpl_html->assign("real_name", $this->processMailField($name, "smart_url"));
        $this->tpl_html->assign("fields_value", $this->processMailField($value, $name));
        $this->tpl_html->parse("Sez" . $type . "Field", true);

        $this->tpl_html->assign(                      //custom vars
            $this->processMailField($name),
            $this->processMailField($value)
        );

        /*
         * Parse field text(label, value, real_name)
         */
        if ($this->tpl_text) {
            if (!$skip_label) {
                $this->tpl_text->assign("fields_label", $this->processMailField($name));
                $this->tpl_text->parse("Sez" . $type . "FieldLabel", true);
            }

            $this->tpl_text->assign("fields_value", $this->processMailField($value, $name));
            $this->tpl_text->parse("SezField", true);

            $this->tpl_text->assign(                  //custom vars
                $this->processMailField($name),
                $this->processMailField($value)
            );
        }
    }

    /**
     * @param string $type
     * @param bool $reset_field
     */
    private function parseMailRow(string $type = null, bool $reset_field = false) : void
    {
        $this->tpl_html->parse("Sez" . $type . "Row", false);
        $this->tpl_html->parse("SezRow" . $type, false); //custom vars

        if ($reset_field) {
            $this->tpl_html->assign("Sez" . $type . "Field", "");
            $this->tpl_html->assign("Sez" . $type . "FieldLabel", "");
        }

        if ($this->tpl_text) {
            $this->tpl_text->parse("Sez" . $type . "Row", false);
            $this->tpl_text->parse("SezRow" . $type, false); //custom vars
            if ($reset_field) {
                $this->tpl_text->assign("Sez" . $type . "Field", "");
                $this->tpl_text->assign("Sez" . $type . "FieldLabel", "");
            }
        }
    }


    /**
     * @param string $value
     * @param string $type
     * @return string
     */
    private function processMailField(string $value, string $type = null) : string
    {
        switch ($type) {
            case "link":
                $link = $value;
                if (strpos($value, "http") === 0) {
                    $link = Request::protocolHost() . substr($link, 4);
                }

                $res = $this->link2TagA($link, $value);
                break;
            case "smart_url":
                $res = Validator::urlRewrite($value);
                break;
            default:
                $res = $value;
        }

        return $res;
    }

    /**
     * @param string $description
     * @param string|null $alias
     * @param string|null $email_alias
     * @return string
     */
    private function link2TagA(string $description, string $alias = null, string $email_alias = null) : string
    {
        if ($alias) {
            $old_description = $description;
            $description = preg_replace(
                '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
                '<a href="http://\\0" target="_blank" rel="nofollow">' . "[--alias--]" . '</a>',
                $description
            );

            if ($old_description != $description) {
                $description = str_replace("[--alias--]", Translator::get_word_by_code($alias), $description);
            }
        } else {
            $description = preg_replace(
                '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
                '<a href="http://\\0" target="_blank" rel="nofollow">' . '\\0' . '</a>',
                $description
            );
        }

        $description = str_replace("http://http://", "http://", $description);

        if ($email_alias) {
            $old_description = $description;

            $description = preg_replace('/([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})/i', '<a href="mailto:$1@$2">[--mailalias--]</a>', $description);
            if ($old_description != $description) {
                $description = str_replace("[--mailalias--]", Translator::get_word_by_code($email_alias), $description);
            }
        } else {
            $description = preg_replace('/([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})/i', '<a href="mailto:$1@$2">$1@$2</a>', $description);
        }

        return $description;
    }
}
