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
namespace phpformsframework\libs\delivery\adapters;

use Exception;
use phpformsframework\libs\delivery\NoticeAdapter;
use phpformsframework\libs\dto\DataError;
use phpformsframework\libs\Error;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\delivery\drivers\Mailer;
use phpformsframework\libs\gui\Resource;

/**
 * Class NoticeEmail
 * @package phpformsframework\libs\delivery\adapters
 */
class NoticeEmail extends NoticeAdapter
{
    private $title                          = null;
    private $fields                         = null;
    private $template                       = null;

    /**
     * @param string $target
     * @return bool
     */
    public function checkRecipient(string $target) : bool
    {
        return Validator::isEmail($target);
    }

    /**
     * @param string $message
     * @return DataError
     * @throws Exception
     */
    public function send(string $message) : DataError
    {
        $this->title                        = $message;

        return $this->process();
    }

    /**
     * @param string $title
     * @param array|null $fields
     * @param string|null $template
     * @return DataError
     * @throws Exception
     */
    public function sendLongMessage(string $title, array $fields = null, string $template = null) : DataError
    {
        $this->title                        = $title;
        $this->fields                       = $fields;

        $this->setTemplate($template);

        return $this->process();
    }

    /**
     * @param string|null $template
     * @throws Exception
     */
    private function setTemplate(string $template = null) : void
    {
        if (!$template) {
            Error::register("Template mail empty", static::ERROR_BUCKET);
        }

        $this->template = (
            strpos($template, "/") === false
            ? Resource::get($template, Resource::TYPE_EMAIL)
            : $template
        );
        if (!$this->template) {
            Error::register("Template mail not found: " .  $template . " (check cache also)", static::ERROR_BUCKET);
        }
    }

    /**
     * @return DataError
     * @throws Exception
     */
    protected function process() : DataError
    {
        return Mailer::getInstance($this->template)
            ->setSmtp($this->connection)
            ->setFrom($this->fromKey, $this->fromLabel)
            ->addAddresses($this->recipients, "to")
            ->addActions($this->actions)
            ->setSubject($this->title)
            ->setMessage($this->fields)
            ->send();
    }
}
