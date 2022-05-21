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
namespace ff\libs\delivery\adapters;

use ff\libs\delivery\NoticeAdapter;
use ff\libs\dto\DataError;
use ff\libs\Exception;
use ff\libs\security\Validator;
use ff\libs\delivery\drivers\Messenger;

/**
 * Class NoticeSms
 * @package ff\libs\delivery\adapters
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
     * @throws Exception
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
     * @throws Exception
     */
    public function sendLongMessage(string $title, array $fields = null, string $template = null) : DataError
    {
        $this->content                      = $title;

        return $this->process();
    }

    /**
     * @return DataError
     * @throws Exception
     */
    protected function process() : DataError
    {
        return Messenger::getInstance($this->connection_service, $this->lang)
            ->setConnection($this->connection)
            ->setFrom($this->fromKey, $this->fromLabel)
            ->addAddresses($this->recipients)
            ->send($this->content);
    }
}
