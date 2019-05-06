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
namespace phpformsframework\libs\delivery\notice;

use phpformsframework\libs\Error;
use phpformsframework\libs\Validator;
use phpformsframework\libs\delivery\drivers\Messenger;


class Sms extends Adapter {
    private $content                        = null;

    public  function checkRecipient($target)
    {
        return Validator::isTel($target);
    }
    public function send($message) {
        $this->content                      = $message;

        return $this->process();
    }
    public function sendLongMessage($title, $fields = null, $template = null) {
        $this->content                      = $title;

        return $this->process();
    }

    protected function process()
    {
        Messenger::getInstance($this->connection)
            ->addAddresses($this->recipients)
            ->send($this->content);

        Error::transfer("messenger", "notice");

        return $this->getResult();
    }
}

