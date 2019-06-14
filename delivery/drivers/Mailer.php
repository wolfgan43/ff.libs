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
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Media;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if(!defined("MAILER_ADAPTER"))                          { define("MAILER_ADAPTER", "localhost"); }
abstract class Mailer
{
    const ADAPTER                                           = MAILER_ADAPTER;

    /**
     * @var MailerAdapter
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
     * @param string|array $content
     * @return Mailer
     */
    public abstract function setMessage($content);

    protected abstract function processSubject();
    protected abstract function processBody();
    protected abstract function processBodyAlt();

    private static $singletons                              = null;


    /**
     * @param null|string $template
     * @param null|string $mailerAdapter
     * @return Mailer
     */
    public static function getInstance($template = null, $mailerAdapter = null)
    {
        if(!self::$singletons[$template . $mailerAdapter]) {
            self::$singletons[$template . $mailerAdapter]   = ($template
                ? new MailerTemplate($template, $mailerAdapter)
                : new MailerSimple($mailerAdapter)
            );
        }

        return self::$singletons[$template . $mailerAdapter];
    }


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
     * @return Mailer
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
     * @return Mailer
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
     * @return Mailer
     */
    public function addAttach($attach, $name = null, $mime = "application/octet-stream", $encoded = "base64")
	{
	    if(strpos($attach, DIRECTORY_SEPARATOR) === 0) {
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

//        $this->clearResult();

        if(!$this->fromEmail)                               { $this->fromEmail  = $this->adapter->from("email"); }
        if(!$this->fromName)                                { $this->fromName   = $this->adapter->from("name"); }
        if(!$this->lang)                                    { $this->lang       = Locale::getLang("tiny_code"); }
        if($subject)                                        { $this->setSubject($subject); }
        if($to)                                             { $this->addTo($to); }
        if($message)                                        { $this->setMessage($message); }

        if(DEBUG::ACTIVE)                                   { $this->addBCC($this->adapter->debug("email")); }
        $this->addBCC($this->adapter->bcc("email"));

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
                if(strpos($mail->Body, "cid:" . basename($name)) !== false) {
                    $mail->AddEmbeddedImage($path, basename($path), $name);
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
        if(!$this->adapter && !$mailerAdapter)              { $mailerAdapter = static::ADAPTER; }

        $this->adapter                                      = new MailerAdapter($mailerAdapter);
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
                , "URL" => Request::url()
                , "REFERER" => Request::referer()
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