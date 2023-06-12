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
namespace ff\libs\delivery\drivers;

use Exception;
use ff\libs\delivery\NoticeDriver;
use ff\libs\dto\DataError;
use ff\libs\Kernel;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer as Mailer;

/**
 * Class MessengerTwilio
 * @package ff\libs\delivery\adapters
 */
class PhpMailer extends NoticeDriver
{
    public const PREFIX                 = null;

    protected $charset                  = "utf8";
    protected $encoding                 = "quoted-printable";

    protected $driver                   = null;
    protected $host                     = null;
    protected $auth                     = null;
    protected $user                     = null;
    protected $secret                   = null;
    protected $port                     = null;
    protected $secure                   = null;
    protected $autoTLS                  = false;

    protected $from_email               = null;
    protected $from_name                = null;
    protected $debug_email              = null;

    protected $cc                       = [];
    protected $bcc                      = [];

    /**
     * @param string $message
     * @param string|null $title
     * @return DataError
     * @throws Exception
     */
    public function send(string $message, string $title = null) : DataError
    {
        try {
            $fromEmail  = $this->from->key      ?? $this->from_email;
            $fromName   = $this->from->label    ?? $this->from->key ?? $this->from_name;

            $mail                                               = new Mailer();
            $mail->SetLanguage($this->lang);
            $mail->Subject                                      = $title;
            $mail->CharSet                                      = $this->charset;
            $mail->Encoding                                     = $this->encoding;


            if ($this->driver == "smtp") {
                $mail->IsSMTP();
            } elseif ($this->driver == "mail") {
                $mail->IsMail();
            }

            $mail->Host                                         = $this->host;
            $mail->SMTPAuth                                     = $this->auth;
            $mail->Username                                     = $this->user;
            $mail->Port                                         = $this->port;
            $mail->Password                                     = $this->secret;
            $mail->SMTPSecure                                   = $this->secure;
            $mail->SMTPAutoTLS                                  = $this->autoTLS;
            if (!$mail->SMTPSecure) {
                $mail->SMTPSecure                               = "none";
            }

            $mail->FromName                                     = $fromName;
            $mail->From                                         = $this->from_email;

            if ($this->from_email != $fromEmail) {
                $mail->AddReplyTo($fromEmail, $fromName);
            }

            if (!empty($this->recipients)) {
                foreach ($this->recipients as $email => $name) {
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

            if (Kernel::$Environment::DEBUG) {
                $mail->addBCC($this->debug_email);
            }

            $mail->IsHTML(true);
            $mail->AllowEmpty                                   = true;
            $mail->Body                                         = $message;
            $mail->AltBody                                      = strip_tags($message);

            /*
             * Images
             */
            if (!empty($this->data["images"])) {
                foreach ($this->data["images"] as $path => $name) {
                    if (strpos($mail->Body, "cid:" . basename($name)) !== false) {
                        $mail->AddEmbeddedImage($path, basename($path), $name);
                    }
                }
            }

            /*
             * Attachment
             */
            if (!empty($this->data["attachments"])) {
                foreach ($this->data["attachments"] as $attachment) {
                    if (!empty($attachment["path"])) {
                        $mail->addAttachment($attachment["path"], $attachment["name"], $attachment["encoded"], $attachment["mime"]);
                    } elseif (!empty($attachment["content"])) {
                        $mail->addStringAttachment($attachment["content"], $attachment["name"], $attachment["encoded"], $attachment["mime"]);
                    }
                }
            }

            if (!$mail->Send()) {
                throw new Exception($mail->ErrorInfo, 500);
            }
        } catch (MailerException $e) {
            throw new Exception($e->getMessage(), 500);
        }

        return new DataError();
    }
}
