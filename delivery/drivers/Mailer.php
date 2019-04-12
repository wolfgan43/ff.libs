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
namespace phpformsframework\libs\delivery;

use phpformsframework\libs\DirStruct;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\Log;
use phpformsframework\libs\tpl\ffTemplate;
use phpformsframework\libs\Validator;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\international\Translator;

if(!defined("MAILER_ADAPTER"))                          define("MAILER_ADAPTER", "localhost");

abstract class mailerAdapter {
    const PREFIX                                            = null;

    private $from                                           = null;
    private $debug                                          = null;
    private $bcc                                            = null;

    abstract public function smtp();

    public function from($key = null) {
        if(!$this->from) {
            $prefix                                         = (defined(self::PREFIX . "_FROM_EMAIL")
                                                                ? self::PREFIX . "_FROM_"
                                                                : "FROM_"
                                                            );

            $this->from["email"]                            = (defined($prefix . "EMAIL")
                                                                ? constant($prefix . "EMAIL")
                                                                : ""
                                                            );
            $this->from["name"]                             = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->from["email"]
                                                            );
        }

        return ($key
            ? $this->from[$key]
            : $this->from
        );
    }


    public function bcc() {
        if(!$this->bcc) {
            $prefix                                         = (defined(self::PREFIX . "_BCC_EMAIL")
                                                                ? self::PREFIX . "_BCC_"
                                                                : "BCC_"
                                                            );

            $this->bcc["email"]                             = (defined($prefix . "EMAIL")
                                                                ? constant($prefix . "EMAIL")
                                                                : ""
                                                            );
            $this->bcc["name"]                              = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->bcc["email"]
                                                            );
        }

        return $this->bcc;
    }

    public function debug() {
        if(!$this->debug) {
            $prefix                                         = (defined(self::PREFIX . "_DEBUG_EMAIL")
                                                                ? self::PREFIX . "_DEBUG_"
                                                                : "DEBUG_"
                                                            );

            $this->debug["email"]                           = (defined($prefix . "EMAIL")
                                                                ? constant($prefix . "EMAIL")
                                                                : ""
                                                            );
            $this->debug["name"]                            = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->debug["email"]
                                                            );
        }

        return $this->debug;
    }
}

class Mailer {
    const TYPE                                              = "mailer";

    const ADAPTER                                           = MAILER_ADAPTER;

    /**
     * @var Sender[]
     */
    private static $singletons                              = null;


    /**
     * @param null|string $template
     * @param null|string $mailerAdapter
     * @return Sender
     */
    public static function getInstance($template = null, $mailerAdapter = null)
    {
        if(!self::$singletons[$template . $mailerAdapter]) {
            self::$singletons[$template . $mailerAdapter]   = ($template
                                                                ? new SenderTemplate($template, $mailerAdapter)
                                                                : new SenderSimple($mailerAdapter)
                                                            );
        }

        return self::$singletons[$template . $mailerAdapter];
    }


}

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

final class SenderSimple extends Sender {
    private $content                                        = null;

    public function setMessage($content) {
        $this->content                                      = $content;

        return $this;
    }

    protected function processSubject()
    {
        return Translator::get_word_by_code($this->subject);
    }

    protected function processBody()
    {
        return $this->content;
    }

    protected function processBodyAlt()
    {
        return '';
    }
}


abstract class Sender extends DirStruct
{
    /**
     * @var mailerAdapter
     */
    private $adapter                                        = null;

    private $lang                                           = null;
    private $charset                                        = "utf8";
    private $encoding                                       = "quoted-printable";

    //header
    protected $subject                                      = null;
    private $fromEmail                                      = null;
    private $fromName                                       = null;
    private $to                                             = null;
    private $cc                                             = null;
    private $bcc                                            = null;

    private $attach                                         = null;

    private $images                                         = null;
    private $actions                                        = null;

    /**
     * @param $content
     * @return Sender
     */
    public abstract function setMessage($content);
    protected abstract function processSubject();
    protected abstract function processBody();
    protected abstract function processBodyAlt();

    public function __construct($mailerAdapter = null)
    {
        $this->setAdapter($mailerAdapter);
    }

    public function from($email, $name = null) {
        $this->fromEmail                                    = $email;
        $this->fromName                                     = ($name ? $name : $email);

        return $this;
    }
    public function setSubject($subject) {
        $this->subject                  = $subject;

        return $this;
    }

    public function addTo($email, $name = null) {
        $this->addAddress($email, "to", $name);

        return $this;
    }
    public function addCC($email, $name = null) {
        $this->addAddress($email, "cc", $name);

        return $this;
    }
    public function addBCC($email, $name = null) {
        $this->addAddress($email, "bcc", $name);

        return $this;
    }
    /**
     * @param array[email => name] $emails
     * @param string[to|cc|bcc] $type
     * @return Sender
     */
    public function addAddresses($emails, $type) {
        if(is_array($emails)) {
            foreach ($emails as $email => $name) {
                $this->addAddress($email, $type, $name);
            }
        }

        return $this;
    }

    /**
     * @param string $email
     * @param string[to|cc|bcc] $type
     * @param null|string $name
     * @return Sender
     */
    public function addAddress($email, $type, $name = null)
    {
        if($email && Validator::isEmail($email)) {
            $name                                           = ($name
                                                                ? $name
                                                                : $email
                                                            );
            switch ($type) {
                case "to":
					$this->to[$email] 			            = $name;
                    break;
                case "cc":
                    $this->cc[$email] 			            = $name;
                    break;
                case "bcc":
                    $this->bcc[$email] 			            = $name;
                    break;
            }
        }

        return $this;
    }

    /**
     * @param string $attach
     * @param null|string $name
     * @param null|string $mime
     * @param null|string $encoded
     * @return Sender
     */
    public function addAttach($attach, $name = null, $mime = "application/octet-stream", $encoded = "base64")
	{
	    if(strpos($attach, "/") === 0) {
	        if(!is_file($attach))                           { $attach = $this::getDiskPath("uploads") . $attach; }
            if(is_file($attach)) {
                if(!$name)                                  { $name = $attach; }
                $this->attach[$name]["path"]                = $attach;
                $this->attach[$name]["mime"]                = $mime;
                $this->attach[$name]["encoded"]             = $encoded;
	        } else {
                Error::register("Attach not valid: " . $attach, "Mailer");
            }
        } elseif($attach) {
            if(!$name)                                      { $name = microtime(); }
            $this->attach[$name]["content"]                 = $attach;
        } else {
            Error::register("Attach Empty", "Mailer");
        }


        return $this;
	}


	public function addImage($image, $name = null) {
        if(!is_file($image))                                { $image = $this::getDiskPath("uploads") . $image; }
        if(is_file($image)) {
            if(!$name)                                      { $name = pathinfo($image, PATHINFO_FILENAME); }
            $this->images[$image]                           = $name;
        } else {
            Error::register("Image not valid: " . $image, "Mailer");
        }

        return $this;
    }
    public function addActions($actions) {
        $this->actions                                      = array_replace((array) $this->actions, $actions);

        return $this;
    }
    public function addAction($name, $url) {
        $this->actions[$url]                                = $name;

        return $this;
    }

    public function setLang($lang) {
        $this->lang                                         = $lang;

        return $this;
    }
    public function setCharset($charset) {
        $this->charset                                      = $charset;

        return $this;
    }
    public function setEncoding($encoding) {
        $this->encoding                                     = $encoding;

        return $this;
    }
    public function send($subject = null, $to = null, $message = null) {

        Debug::startWatch();

        $this->clearResult();

        if(!$this->fromEmail)                               { $this->fromEmail  = $this->adapter->from("email"); }
        if(!$this->fromName)                                { $this->fromName   = $this->adapter->from("name"); }
        if(!$this->lang)                                    { $this->lang       = Locale::getLang("tiny_code"); }
        if($subject)                                        { $this->setSubject($subject); }
        if($to)                                             { $this->addTo($to); }
        if($message)                                        { $this->setMessage($message); }

        if($this->to)                                       { $this->phpmailer(); }

        return $this->getResult(Debug::stopWatch());
    }

    private function phpmailer() {
        $mail                                               = new PHPMailer();
        $mail->SetLanguage($this->lang);
        $mail->Subject                                      = $this->processSubject();
        $mail->CharSet                                      = $this->charset;
        $mail->Encoding                                     = $this->encoding;

        $smtp                                               = $this->adapter->smtp();
        if($smtp["auth"]) {
            $mail->IsSMTP();
        } else {
            $mail->IsMail();
        }

        $mail->Host                                         = $smtp["host"];
        $mail->SMTPAuth                                     = $smtp["auth"];
        $mail->Username                                     = $smtp["username"];
        $mail->Port                                         = $smtp["port"];
        $mail->Password                                     = $smtp["password"];
        //$mail->SMTPSecure                                 = $smtp["secure"];
        $mail->SMTPAutoTLS                                  = $smtp["autoTLS"];

        $mail->FromName                                     = $this->fromName;
        $mail->From                                         = (strpos($smtp["username"], "@") === false
                                                                ? $this->fromEmail
                                                                : $smtp["username"]
                                                            );
        if ($smtp["username"] != $this->fromEmail)          { $mail->AddReplyTo($this->fromEmail, $this->fromName); }

        if(is_array($this->to) && count($this->to)) {
            foreach($this->to AS $email => $name)           { $mail->addAddress($email, $name); }
        }
        if(is_array($this->cc) && count($this->cc)) {
            foreach($this->cc AS $email => $name)           { $mail->addCC($email, $name); }
        }
        if(is_array($this->bcc) && count($this->bcc)) {
            foreach($this->bcc AS $email => $name)          { $mail->addBCC($email, $name); }
        }

        $mail->IsHTML(true);

        $mail->Body                                         = $this->processBody();
        $mail->AltBody                                      = $this->processBodyAlt();

        /*
         * Images
         */
        if (is_array($this->images) && count($this->images)) {
            foreach ($this->images AS $path => $name) {
                if(strpos($mail->Body, "cid:" . $name) !== false) {
                    $mail->AddEmbeddedImage($path, $name, "cid:" . $name);
                }
            }
        }

        /*
         * Attachment
         */
        if (is_array($this->attach) && count($this->attach)) {
            foreach ($this->attach AS $attach_key => $attach_value) {
                if($attach_value["path"]) {
                    try {
                        $mail->addAttachment($attach_value["path"], $attach_key, $attach_value["encoded"], $attach_value["mime"]);
                    } catch (Exception $exception) {
                        Error::register($exception->getMessage(), "Mailer");
                    }
                } elseif($attach_value["content"]) {
                    $mail->addStringAttachment($attach_value["content"], $attach_key, $attach_value["encoded"], $attach_value["mime"]);
                }
            }
        }

        try {
            $rc                                             = $mail->Send();
            if (!$rc)                                       { Error::register($mail->ErrorInfo, "Mailer"); }
        } catch (Exception $exception) {
            Error::register($exception->getMessage(), "Mailer");
        }
    }

    /**
     * @param null|string $mailerAdapter
     */
    private function setAdapter($mailerAdapter = null) {
        if(!$this->adapter && !$mailerAdapter)              { $mailerAdapter = Mailer::ADAPTER; }
        if($mailerAdapter) {
            $class_name                                     = Mailer::TYPE . ucfirst($mailerAdapter);

            $this->adapter                                  = new $class_name();
        }
    }

    private function clearResult()
    {
        $this->to       = null;
        $this->cc       = null;
        $this->bcc      = null;

        Error::clear("mailer");
    }

    /**
     * @param float $exTime
     * @return array
     */
    private function getResult($exTime = null)
    {
        if(Error::check("mailer") || Debug::ACTIVE) {
            $dump = array(
                "source" => Debug::stackTrace()
                , "URL" => $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . " REFERER: " . $_SERVER["HTTP_REFERER"]
                , " subject" => $this->subject
                , " fromEmail" => $this->fromEmail
                , " fromName" => $this->fromName
                , " to" => $this->to
                , " cc" => $this->cc
                , " bcc" => $this->bcc
                , " error" => Error::raise("mailer")
                , " exTime" => $exTime
            );
            if(Error::check("mailer")) {
                Log::error($dump);
            } else {
                Log::debugging($dump);
            }
        }


        return (Error::check("mailer")
            ? array(
                "status"    => 500
                , "error"   => Error::raise("mailer")
                , "exTime"  => $exTime
            )
            : array(
                "status"    => 0
                , "error"   => ""
                , "exTime"  => $exTime
            )
        );
    }

    public function preview($subject = null, $message = null) {
        $this->clearResult();


        if($subject)                                        { $this->setSubject($subject); }
        if($message)                                        { $this->setMessage($message); }



        $res["header"]                                      = $this->getHeaders();
        $res["subject"]                                     = $this->processSubject();
        $res["body"]                                        = $this->processBody();
        $res["bodyalt"]                                     = $this->processBodyAlt();



        return $res;
    }

    public function getHeaders() {
        if(!$this->fromEmail)                               { $this->fromEmail  = $this->adapter->from("email"); }
        if(!$this->fromName)                                { $this->fromName   = $this->adapter->from("name"); }
        if(!$this->lang)                                    { $this->lang       = Locale::getLang("tiny_code"); }

        $smtp                                               = $this->adapter->smtp();
        $smtp["password"]                                   = false;

        return array(
            "smtp"          => $smtp
            , "from"        => array(
                                "name"          => $this->fromName
                                , "email"       => $this->fromEmail
                            )
            , "replyTo"     => ($smtp["username"] != $this->fromEmail
                                ? array(
                                    "name"      => $this->fromName
                                    , "email"   => $this->fromEmail
                                )
                                : null
                            )
            , "bcc"         => $this->bcc
        );
    }
}



