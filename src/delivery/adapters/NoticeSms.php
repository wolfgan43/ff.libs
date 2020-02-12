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

use phpformsframework\libs\delivery\NoticeAdapter;
use phpformsframework\libs\dto\DataError;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\delivery\drivers\Messenger;

/**
 * Class NoticeSms
 * @package phpformsframework\libs\delivery\adapters
 */
class NoticeSms extends NoticeAdapter
{
    private $content                        = null;

    /**
     * @param string $target
     * @return bool
     */
    public function checkRecipient(string $target) : bool
    {
        return Validator::isTel($target);
    }

    /**
     * @param string $message
     * @return DataError
     */
    public function send(string $message) : DataError
    {
        $this->content                      = $message;

        return $this->process();
    }

    /**
     * @param string $title
     * @param array|null $fields
     * @param string|null $template
     * @return DataError
     */
    public function sendLongMessage(string $title, array $fields = null, string $template = null) : DataError
    {
        $this->content                      = $title;

        return $this->process();
    }

    /**
     * @return DataError
     */
    protected function process() : DataError
    {
        return Messenger::getInstance($this->connection_service)
            ->setConnection($this->connection)
            ->setFrom($this->fromKey, $this->fromLabel)
            ->addAddresses($this->recipients)
            ->send($this->content);
    }
}
