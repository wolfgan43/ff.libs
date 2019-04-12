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

use phpformsframework\libs\Error;
use phpformsframework\libs\Validator;

class noticeAdapterEmail extends noticeAdapter {
    private $title                          = null;
    private $fields                         = null;
    private $template                       = null;

    public  function checkRecipient($target)
    {
        return Validator::isEmail($target);
    }
    public function send($message) {
        $this->title                        = $message;

        return $this->process();
    }
    public function sendLongMessage($title, $fields = null, $template = null) {
        $this->title                        = $title;
        $this->fields                       = $fields;

        $this->setTemplate($template);

        return $this->process();
    }
    private function setTemplate($template) {
        if ($template) {
            if(strpos($template, "::") !== false) {
                $objSource                  = explode("::", $template);

                $this->template             = (is_file($this->getDiskPath("mail") . "/" . $objSource[0] . $objSource[1])
                                                ? "/" . $objSource[0] . $objSource[1]
                                                : $this->getClassPath(ucfirst($objSource[0])) . "/mailer" . $objSource[1]
                                            );
            } else {
                $this->template             = $template;
            }
        }
    }

    protected function process()
    {
        Mailer::getInstance($this->template, $this->connection)
            ->addAddresses($this->recipients, "to")
            ->addActions($this->actions)
            ->setSubject($this->title)
            ->setMessage($this->fields)
            ->send();

        Error::transfer("mailer", "notice");

        return $this->getResult();
    }
}

