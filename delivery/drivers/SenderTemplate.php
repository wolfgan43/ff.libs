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

use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\tpl\ffTemplate;
use phpformsframework\libs\Validator;

final class SenderTemplate extends Sender {
     //body
    private $fields                                         = array();

    private $tpl_html_path                                  = null;
    /**
     * @var ffTemplate
     */
    private $tpl_html                                       = null;

    private $tpl_text_path                                  = null;
    /**
     * @var ffTemplate
     */
    private $tpl_text                                       = null;

    private $body                                           = null;
    private $bodyAlt                                        = null;


    public function __construct($template, $mailerAdapter = null)
    {
        $this->loadTemplate($template);

        parent::__construct($mailerAdapter);
    }

    public function setMessage($fields) {
        $this->fields                                       = (is_array($fields)
                                                                ? $fields
                                                                : array("content" => $fields)
                                                            );
        return $this;
    }

    protected function processSubject()
    {
        $subject                                            = str_replace(
                                                                array_keys($this->fields)
                                                                , array_values($this->fields)
                                                                , Translator::get_word_by_code($this->subject)
                                                            );
        return $subject;
    }

    protected function processBody()
    {
        if($this->body === null) {
            $this->processTemplate();
        }
        return $this->body;
    }

    protected function processBodyAlt()
    {
        if($this->body === null) {
            $this->processTemplate();
        }
        return $this->bodyAlt;
    }

    private function loadTemplate($template)
    {
        if($template) {
            if(is_file($template)) {
                $this->tpl_html_path                = $this::$disk_path . $template;
            } else {
                $mail_disk_path                     = $this->getDiskPath("mail");
                if (is_file($mail_disk_path . $template)) {
                    $this->tpl_html_path            = $mail_disk_path . $template;
                }
            }
        } else {
            $tpl_name                               = (is_array($this->fields) && count($this->fields)
                                                        ? "default"
                                                        : "empty"
                                                    );

            $this->tpl_html_path                    = dirname(__DIR__) . "/assets/" . $tpl_name . ".html";
        }

		if($this->tpl_html_path) {
			$this->tpl_html = new ffTemplate();
            $this->tpl_html->load_file($this->tpl_html_path, "main");

            if(is_file(dirname($this->tpl_html_path) . "/default.txt")) {
                $this->tpl_text_path = dirname($this->tpl_html_path) . "/default.txt";

                $this->tpl_text = new ffTemplate();
                $this->tpl_text->load_file($this->tpl_text_path, "main");
            }
        } else {
            Error::register("Template not found" . (Debug::ACTIVE ? ": " . $this->tpl_html_path : ""), "mailer");
        }
    }
    private function processTemplate() {
        /**
         * Process Fields
         */
        if (is_array($this->fields))
        {
            $count_group = 0;
            $group_type = array("Table" => true);
            foreach ($this->fields AS $fields_key => $fields_value)
            {
                $field_type = $fields_value["settings"]["type"];
                if (is_array($fields_value) && count($fields_value))
                {
                    $count_row = 0;
                    foreach ($fields_value AS $fields_value_key => $fields_value_value)
                    {
                        if (strtolower($fields_value_key) == "settings")
                            continue;

                        switch ($field_type)
                        {
                            case "Table":
                                if (is_array($fields_value_value) && count($fields_value_value))
                                {
                                    foreach ($fields_value_value AS $fields_value_value_key => $fields_value_value_value)
                                    {
                                        if (strtolower($fields_value_value_key) == "settings")
                                            continue;

                                        $this->parse_mail_field($fields_value_value_value, $fields_value_value_key, $field_type, $count_row);
                                    }

                                    $this->parse_mail_row($field_type, true);
                                } else {
                                    $this->parse_mail_field($fields_value_value, $fields_key . "_" . $fields_value_key, $field_type);
                                    $this->parse_mail_row($field_type);
                                }
                                break;
                            default:
                                if (is_array($fields_value_value) && count($fields_value_value))
                                {
                                    foreach ($fields_value_value AS $fields_value_value_key => $fields_value_value_value) {
                                        if (strtolower($fields_value_value_key) == "settings") {
                                            continue;
                                        }
                                        $this->parse_mail_field($fields_value_value_value, $fields_value_value_key, $field_type, $count_row);

                                    }

                                    $this->parse_mail_row($field_type, true);
                                } else {
                                    $this->parse_mail_field($fields_value_value, $fields_key . "_" . $fields_value_key, $field_type);
                                    $this->parse_mail_row($field_type);
                                }
                        }
                        $count_row++;
                    }
                } else {
                    $this->tpl_html->set_var($fields_key, $fields_value); //custom vars
                    if($this->tpl_text) {
                        $this->tpl_text->set_var($fields_key, $fields_value); //custom vars
                    }
                }

                $this->parse_mail_group($fields_key, $group_type, $field_type);

                $count_group++;
            }

            $this->tpl_html->parse("SezFields", false);
            if($this->tpl_text) {
                $this->tpl_text->parse("SezFields", false);
            }
        }

        $this->body = ($this->tpl_html
            ? $this->tpl_html->rpparse("main", false)
            : ''
        );

        $this->bodyAlt = ($this->tpl_text
            ? $this->tpl_text->rpparse("main", false)
            : ''
        );
    }

    /**
     * @param $value
     * @param $groups
     * @param null $type
     */
    private function parse_mail_group($value, $groups, $type = null)
    {
        /*
         * Parse field html(label, value, real_name)
         */
        $this->tpl_html->parse("SezStyle" . $type, false);
        foreach ($groups AS $group_key => $group_value)
        {
            if ($group_key != $type) {
                $this->tpl_html->set_var("SezStyle" . $group_key, "");
            }
        }
        if ($type)
            $this->tpl_html->set_var("SezStyle", "");

        $this->tpl_html->set_var("real_name", $this->process_mail_field($value, "smart_url"));
        $this->tpl_html->set_var("group_name", $this->process_mail_field($value));
        $this->tpl_html->parse("SezGroups", true);

        $this->tpl_html->set_var("SezFieldLabel", "");
        $this->tpl_html->set_var("SezField", "");

        foreach ($groups AS $group_key => $group_value)
        {
            $this->tpl_html->set_var("Sez" . $group_key . "FieldLabel", "");
            $this->tpl_html->set_var("Sez" . $group_key . "Field", "");
            $this->tpl_html->set_var("Sez" . $group_key . "Row", "");
        }

        /*
         * Parse field text(label, value, real_name)
         */
        if($this->tpl_text)
        {
            $this->tpl_text->parse("SezStyle" . $type, false);
            foreach ($groups AS $group_key => $group_value) {
                if ($group_key != $type) {
                    $this->tpl_text->set_var("SezStyle" . $group_key, "");
                }
            }
            if ($type)
                $this->tpl_text->set_var("SezStyle", "");

            $this->tpl_text->set_var("real_name", $this->process_mail_field($value, "smart_url"));
            $this->tpl_text->set_var("group_name", $this->process_mail_field($value));
            $this->tpl_text->parse("SezGroups", true);

            $this->tpl_text->set_var("SezFieldLabel", "");
            $this->tpl_text->set_var("SezField", "");

            foreach ($groups AS $group_key => $group_value)
            {
                $this->tpl_text->set_var("Sez" . $group_key . "FieldLabel", "");
                $this->tpl_text->set_var("Sez" . $group_key . "Field", "");
                $this->tpl_text->set_var("Sez" . $group_key . "Row", "");
            }
        }
    }

    /**
     * @param $value
     * @param $name
     * @param null $type
     * @param bool $skip_label
     */
    private function parse_mail_field($value, $name, $type = null, $skip_label = false)
    {
        $this->tpl_html->set_var($name, $value); //custom vars
        if($this->tpl_text) {
            $this->tpl_text->set_var($name, $value); //custom vars
        }

        /*
         * Parse field html(label, value, real_name)
         */
        if (!$skip_label)
        {
            $this->tpl_html->set_var("fields_label", $this->process_mail_field($name));
            $this->tpl_html->parse("Sez" . $type . "FieldLabel", true);
        }

        $this->tpl_html->set_var("real_name", $this->process_mail_field($name, "smart_url"));
        $this->tpl_html->set_var("fields_value", $this->process_mail_field($value, $name));
        $this->tpl_html->parse("Sez" . $type . "Field", true);

        $this->tpl_html->set_var(                      //custom vars
            $this->process_mail_field($name)
            , $this->process_mail_field($value)
        );

        /*
         * Parse field text(label, value, real_name)
         */
        if($this->tpl_text)
        {
            if (!$skip_label)
            {
                $this->tpl_text->set_var("fields_label", $this->process_mail_field($name));
                $this->tpl_text->parse("Sez" . $type . "FieldLabel", true);
            }

            $this->tpl_text->set_var("fields_value", $this->process_mail_field($value, $name));
            $this->tpl_text->parse("SezField", true);

            $this->tpl_text->set_var(                  //custom vars
                $this->process_mail_field($name)
                , $this->process_mail_field($value)
            );
        }
    }

    /**
     * @param null $type
     * @param bool $reset_field
     */
    private function parse_mail_row($type = null, $reset_field = false)
    {
        $this->tpl_html->parse("Sez" . $type . "Row", false);
        $this->tpl_html->parse("SezRow" . $type, false); //custom vars

        if($reset_field) {
            $this->tpl_html->set_var("Sez" . $type . "Field", "");
            $this->tpl_html->set_var("Sez" . $type . "FieldLabel", "");
        }

        if($this->tpl_text) {
            $this->tpl_text->parse("Sez" . $type . "Row", false);
            $this->tpl_text->parse("SezRow" . $type, false); //custom vars
            if ($reset_field) {
                $this->tpl_text->set_var("Sez" . $type . "Field", "");
                $this->tpl_text->set_var("Sez" . $type . "FieldLabel", "");
            }
        }
    }


    /**
     * @param $value
     * @param null $type
     * @return string
     */
    protected function process_mail_field($value, $type = null)
    {
        switch($type) {
            case "link":
                $link = $value;
                if(strpos($value, "http") === 0) {
                    $link = "http" . ($_SERVER["HTTPS"] ? "s" : "") . "://" . $_SERVER["HTTP_HOST"] . substr($link, 4);
                }

                $res = $this->link_to_tagA($link, $value);
                break;
            case "smart_url":
                $res = Validator::urlRewrite($value);
                break;
            default:
                $res = $value;
        }

        return $res;
    }


    private function link_to_tagA($description, $alias = null, $email_alias = null) {
        if($alias) {
            $old_description = $description;
            $description = preg_replace( '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
                '<a href="http://\\0" target="_blank" rel="nofollow">' . "[--alias--]" . '</a>', $description);

            if($old_description != $description) {
                $description = str_replace("[--alias--]", Translator::get_word_by_code($alias), $description);
            }
        } else {
            $description = preg_replace( '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
                '<a href="http://\\0" target="_blank" rel="nofollow">' . '\\0' . '</a>', $description);
        }

        $description = str_replace("http://http://", "http://", $description);

        if($email_alias) {
            $old_description = $description;

            $description = preg_replace('/([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})/i','<a href="mailto:$1@$2">[--mailalias--]</a>', $description);
            if($old_description != $description) {
                $description = str_replace("[--mailalias--]", Translator::get_word_by_code($email_alias), $description);
            }
        } else {
            $description = preg_replace('/([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})/i','<a href="mailto:$1@$2">$1@$2</a>', $description);
        }

        return $description;
    }
}