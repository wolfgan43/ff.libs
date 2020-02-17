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
use phpformsframework\libs\dto\DataError;
use phpformsframework\libs\Error;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\security\Validator;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Mailer
 * @package phpformsframework\libs\delivery\drivers
 */
abstract class Mailer
{
    const ERROR_BUCKET                                      = "mailer";

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
     * @todo da tipizzare
     * @param string|array $content
     * @return Mailer
     */
    abstract public function setMessage($content);

    abstract protected function processSubject();
    abstract protected function processBody();
    abstract protected function processBodyAlt();

    private static $singletons                              = null;


    /**
     * @param null|string $template
     * @return Mailer
     */
    public static function getInstance($template = null)
    {
        if (!self::$singletons[$template]) {
            self::$singletons[$template]   = (
                $template
                ? new MailerTemplate($template)
                : new MailerSimple()
            );
        }

        return self::$singletons[$template];
    }

    /**
     * Mailer constructor.
     */
    public function __construct()
    {
        $this->setAdapter();
    }

    /**
     * @param string|null $subject
     * @return Mailer
     */
    public function setSubject(string $subject = null) : self
    {
        $this->subject                  = $subject;

        return $this;
    }

    /**
     * @param string|null $email
     * @param string|null $name
     * @return Mailer
     */
    public function addTo(string $email = null, string $name = null) : self
    {
        $this->addAddress("to", $email, $name);

        return $this;
    }

    /**
     * @param string|null $email
     * @param string|null $name
     * @return Mailer
     */
    public function addCC(string $email = null, string $name = null) : self
    {
        $this->addAddress("cc", $email, $name);

        return $this;
    }

    /**
     * @param string|null $email
     * @param string|null $name
     * @return Mailer
     */
    public function addBCC(string $email = null, string $name = null) : self
    {
        $this->addAddress("bcc", $email, $name);

        return $this;
    }
    /**
     * @param array[email => name] $emails
     * @param string[to|cc|bcc] $type
     * @return Mailer
     */
    public function addAddresses(array $emails, string $type) : self
    {
        if (is_array($emails)) {
            foreach ($emails as $email => $name) {
                $this->addAddress($type, $email, $name);
            }
        }

        return $this;
    }

    /**
     * @param string[to|cc|bcc] $type
     * @param string|null $email
     * @param null|string $name
     * @return Mailer
     */
    public function addAddress(string $type, string $email = null, string $name = null) : self
    {
        if ($email && Validator::isEmail($email)) {
            $name                                           = (
                $name
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
                default:
                    Error::register("recipient not supported: " . $type, static::ERROR_BUCKET);
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
    public function addAttach(string $attach, string $name = null, string $mime = "application/octet-stream", string $encoded = "base64") : self
    {
        if (strpos($attach, DIRECTORY_SEPARATOR) === 0) {
            if (is_file($attach)) {
                if (!$name) {
                    $name = $attach;
                }
                $this->attach[$name]["path"]                = $attach;
                $this->attach[$name]["mime"]                = $mime;
                $this->attach[$name]["encoded"]             = $encoded;
            } else {
                Error::register("Attach not valid: " . $attach, static::ERROR_BUCKET);
            }
        } elseif ($attach) {
            if (!$name) {
                $name = microtime();
            }
            $this->attach[$name]["content"]                 = $attach;
        } else {
            Error::register("Attach Empty", static::ERROR_BUCKET);
        }


        return $this;
    }

    /**
     * @param string $image
     * @param string|null $name
     * @return Mailer
     */
    public function addImage(string $image, string $name = null) : self
    {
        if (is_file($image)) {
            if (!$name) {
                $name = pathinfo($image, PATHINFO_FILENAME);
            }
            $this->images[$image]                           = $name;
        } else {
            Error::register("Image not valid: " . $image, static::ERROR_BUCKET);
        }

        return $this;
    }

    /**
     * @param array $actions
     * @return Mailer
     */
    public function addActions(array $actions) : self
    {
        $this->actions                                      = array_replace((array) $this->actions, $actions);

        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @return Mailer
     */
    public function addAction(string $name, string $url) : self
    {
        $this->actions[$url]                                = $name;

        return $this;
    }

    /**
     * @param string $lang
     * @return Mailer
     */
    public function setLang(string $lang) : self
    {
        $this->lang                                         = $lang;

        return $this;
    }

    /**
     * @param string $charset
     * @return Mailer
     */
    public function setCharset(string $charset) : self
    {
        $this->charset                                      = $charset;

        return $this;
    }

    /**
     * @param string $encoding
     * @return Mailer
     */
    public function setEncoding(string $encoding) : self
    {
        $this->encoding                                     = $encoding;

        return $this;
    }

    /**
     * @param array|null $smtp
     * @return Mailer
     */
    public function setSmtp(array $smtp = null) : self
    {
        if (is_array($smtp)) {
            foreach ($smtp as $key => $value) {
                if (isset($this->adapter->$key)) {
                    $this->adapter->$key = $value;
                }
            }
        }
        return $this;
    }

    /**
     * @param string|null $email
     * @param string|null $name
     * @return Mailer
     */
    public function setFrom(string $email = null, string $name = null) : self
    {
        $this->fromEmail = $email;
        $this->fromName = (
            $name
            ? $name
            : $email
        );

        return $this;
    }

    /**
     * @param string|null $subject
     * @param string|null $to
     * @param string|null $message
     * @return DataError
     */
    public function send(string $subject = null, string $to = null, string $message = null) : DataError
    {
        Debug::stopWatch("mailer/send");

        if (!$this->fromEmail) {
            $this->fromEmail  = $this->adapter->from_email;
        }
        if (!$this->fromName) {
            $this->fromName   = $this->adapter->from_name;
        }
        if (!$this->lang) {
            $this->lang       = Locale::getLang("tiny_code");
        }
        if ($subject) {
            $this->setSubject($subject);
        }
        if ($to) {
            $this->addTo($to);
        }
        if ($message) {
            $this->setMessage($message);
        }

        if (Kernel::$Environment::DEBUG) {
            $this->addBCC($this->adapter->debug_email);
        }

        if ($this->to) {
            $this->phpmailer();
        } else {
            Error::registerWarning("Mailer: Recipients is empty", static::ERROR_BUCKET);
        }

        Debug::stopWatch("mailer/send");

        return $this->getResult();
    }

    /**
     *
     */
    private function phpmailer()
    {
        $mail                                               = new PHPMailer();
        $mail->SetLanguage($this->lang);
        $mail->Subject                                      = $this->processSubject();
        $mail->CharSet                                      = $this->charset;
        $mail->Encoding                                     = $this->encoding;



        if ($this->adapter->driver == "smtp") {
            $mail->IsSMTP();
        } elseif ($this->adapter->driver == "mail") {
            $mail->IsMail();
        }

        $mail->Host                                         = $this->adapter->host;
        $mail->SMTPAuth                                     = $this->adapter->auth;
        $mail->Username                                     = $this->adapter->username;
        $mail->Port                                         = $this->adapter->port;
        $mail->Password                                     = $this->adapter->password;
        $mail->SMTPSecure                                   = $this->adapter->secure;
        $mail->SMTPAutoTLS                                  = $this->adapter->autoTLS;
        if (!$mail->SMTPSecure) {
            $mail->SMTPSecure                               = "none";
        }

        $mail->FromName                                     = $this->fromName;
        $mail->From                                         = (
            strpos($this->adapter->username, "@") === false
                                                                ? $this->fromEmail
                                                                : $this->adapter->username
                                                            );
        if ($this->adapter->username != $this->fromEmail) {
            $mail->AddReplyTo($this->fromEmail, $this->fromName);
        }

        if (!empty($this->to)) {
            foreach ($this->to as $email => $name) {
                $mail->addAddress($email, $name);
            }
        }
        if (!empty($this->cc)) {
            foreach ($this->cc as $email => $name) {
                $mail->addCC($email, $name);
            }
        }
        if (!empty($this->bcc)) {
            foreach ($this->bcc as $email => $name) {
                $mail->addBCC($email, $name);
            }
        }

        $mail->IsHTML(true);
        $mail->AllowEmpty                                   = true;
        $mail->Body                                         = $this->processBody();
        $mail->AltBody                                      = $this->processBodyAlt();
        /*
         * Images
         */
        if (!empty($this->images)) {
            foreach ($this->images as $path => $name) {
                if (strpos($mail->Body, "cid:" . basename($name)) !== false) {
                    $mail->AddEmbeddedImage($path, basename($path), $name);
                }
            }
        }
        /*
         * Attachment
         */
        if (!empty($this->attach)) {
            foreach ($this->attach as $attach_key => $attach_value) {
                if ($attach_value["path"]) {
                    try {
                        $mail->addAttachment($attach_value["path"], $attach_key, $attach_value["encoded"], $attach_value["mime"]);
                    } catch (Exception $e) {
                        Error::register($e->getMessage(), static::ERROR_BUCKET);
                    }
                } elseif ($attach_value["content"]) {
                    $mail->addStringAttachment($attach_value["content"], $attach_key, $attach_value["encoded"], $attach_value["mime"]);
                }
            }
        }

        try {
            $rc                                             = $mail->Send();
            if (!$rc) {
                Error::register($mail->ErrorInfo, static::ERROR_BUCKET);
            }
        } catch (Exception $e) {
            Error::register($e->getMessage(), static::ERROR_BUCKET);
        }
    }

    /**
     *
     */
    private function setAdapter()
    {
        $this->adapter                                      = new MailerAdapter();
    }

    /**
     *
     */
    private function clearResult()
    {
        $this->to       = null;
        $this->cc       = null;
        $this->bcc      = null;

        Error::clear(static::ERROR_BUCKET);
    }

    /**
     * @return DataError
     */
    private function getResult()
    {
        $dataError                                          = new DataError();
        if (Error::check(static::ERROR_BUCKET) || Kernel::$Environment::DEBUG) {
            $dump = array(
                "source"        => Debug::stackTrace(),
                "subject"       => $this->subject,
                "fromEmail"     => $this->fromEmail,
                "fromName"      => $this->fromName,
                "to"            => $this->to,
                "cc"            => $this->cc,
                "bcc"           => $this->bcc,
                "error"         => Error::raise(static::ERROR_BUCKET),
                "exTime"        => Debug::exTime("mailer/send")
            );
            if (Error::check(static::ERROR_BUCKET)) {
                Log::critical($dump, static::ERROR_BUCKET, static::ERROR_BUCKET, "send");
            } else {
                Log::debugging($dump, static::ERROR_BUCKET, static::ERROR_BUCKET, "send");
            }
        }

        if (Error::check(static::ERROR_BUCKET)) {
            $dataError->error(500, Error::raise(static::ERROR_BUCKET));
        }

        return $dataError;
    }

    /**
     * @param null $subject
     * @param null $message
     * @return mixed
     */
    public function preview($subject = null, $message = null)
    {
        $this->clearResult();


        if ($subject) {
            $this->setSubject($subject);
        }
        if ($message) {
            $this->setMessage($message);
        }



        $res["header"]                                      = $this->getHeaders();
        $res["subject"]                                     = $this->processSubject();
        $res["body"]                                        = $this->processBody();
        $res["bodyalt"]                                     = $this->processBodyAlt();



        return $res;
    }

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        if (!$this->fromEmail) {
            $this->fromEmail  = $this->adapter->from_email;
        }
        if (!$this->fromName) {
            $this->fromName   = $this->adapter->from_name;
        }
        if (!$this->lang) {
            $this->lang       = Locale::getLang("tiny_code");
        }
        if (Kernel::$Environment::DEBUG) {
            $this->addBCC($this->adapter->debug_email);
        }

        $smtp                                               = $this->adapter;
        $smtp->password                                     = false;

        return array(
            "smtp"          => (array) $smtp
            , "from"        => array(
                                "name"          => $this->fromName
                                , "email"       => $this->fromEmail
                            )
            , "replyTo"     => (
                $smtp->username != $this->fromEmail
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
