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
namespace ff\libs\delivery;

use Exception;
use ff\libs\Constant;
use ff\libs\Debug;
use ff\libs\dto\DataError;
use ff\libs\gui\Resource;
use ff\libs\gui\View;
use ff\libs\international\InternationalManager;
use ff\libs\security\Validator;
use ff\libs\storage\FilemanagerFs;
use ff\libs\storage\FilemanagerScan;
use ff\libs\storage\FilemanagerWeb;
use ff\libs\util\AdapterManager;

/**
 * Class Notice
 * @package ff\libs\delivery
 * @property NoticeAdapter $adapter
 */
class Notice
{
    use AdapterManager;
    use InternationalManager;

    protected const ERROR_BUCKET                            = "delivery";

    protected const IMAGE_ALLOWED                           = ["jpg", "png", "svg", "gif"];
    private const STRUCT                                    = [
        "title"     => null,
        "from"      => null,
        "params"    => [],
        "data"      => [],
    ];

    private static $exTime                                  = 0;
    private $channel                                        = null;
    private $locale                                         = null;
    private $lang                                           = null;

    private $title                                          = "";
    private $params                                         = [];

    /**
     * @return float
     */
    public static function exTime() : float
    {
        return self::$exTime;
    }

    /**
     * @param string $noticeAdapter
     * @return Notice
     */
    public static function getInstance(string $noticeAdapter) : self
    {
        return new Notice($noticeAdapter);
    }

    /**
     * Messenger constructor.
     * @param string $noticeAdapter
     */
    public function __construct(string $noticeAdapter)
    {
        $this->channel = $noticeAdapter;
        $this->setAdapter($noticeAdapter);
    }

    /**
     * @param array $targets
     * @return Notice
     */
    public function addRecipients(array $targets) : self
    {
        foreach ($targets as $target) {
            $this->adapter->addRecipient($target);
        }

        return $this;
    }

    /**
     * @param string $target
     * @param string|null $name
     * @return Notice
     */
    public function addRecipient(string $target, string $name = null) : self
    {
        $this->adapter->addRecipient($target, is_numeric($name) ? null: $name);

        return $this;
    }

    /**
     * @param array $map
     * @param array $channel
     * @return $this
     */
    public function setFrom(array $map, array $channel) : self
    {
        $this->adapter->setFrom($channel["from"] ?: $map["from"] ?? []);

        return $this;
    }

    /**
     * @param string|null $locale
     * @return $this
     */
    public function setLocale(string $locale = null) : self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @return Notice
     */
    public function addAction(string $name, string $url) : self
    {
        $this->adapter->addAction($name, $url);

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data) : self
    {
        $this->adapter->setData($data);

        return $this;
    }

    /**
     * @param string $template_or_message
     * @param string|null $title
     * @param array $params
     * @return DataError
     * @throws Exception
     */
    public function send(string $template_or_message, string $title = null, array $params = []) : DataError
    {
        Debug::stopWatch(static::ERROR_BUCKET);

        $this->lang                                         = $this->lang($this->locale, true);
        $this->adapter->setLang($this->lang);

        $this->title                                        = $title;
        $this->params                                       = $params;

        $message                                            = $this->setTemplate($template_or_message);
        $dataResponse                                       = $this->adapter->send($message, $this->setTitle());

        self::$exTime = Debug::stopWatch(static::ERROR_BUCKET);

        return $dataResponse;
    }

    /**
     * @param string $template_or_message
     * @return string
     * @throws Exception
     */
    private function setTemplate(string $template_or_message) : string
    {
        $content    = $template_or_message;
        if (Validator::isUrl($template_or_message)) {
            $view = View::fetchContent(FilemanagerWeb::fileGetContents($template_or_message), $this->lang);
        } else {
            $template_or_message    = rtrim($template_or_message, "_");
            do {
                if ($view = $this->findTemplate($template_or_message)) {
                    $this->loadImages($view);

                    $map            = $this->findTemplateMap($view, $template_or_message);
                    $channel        = $this->findChannel($map);

                    $this->title    = $this->findTitle($channel, $map);
                    $this->params   = array_replace($map["params"], $channel["params"], $this->params);

                    $this->setData(array_replace($map["data"], $channel["data"]));
                    $this->setFrom($map, $channel);

                    Debug::set(basename($view), static::ERROR_BUCKET);
                    $view = View::fetchFile($view, $this->lang);
                    break;
                }
                $template_or_message = (
                    ($end = strrpos($template_or_message, "_")) !== false
                        ? substr($template_or_message, 0, $end)
                        : null
                );
            } while (!empty($template_or_message));
        }

        if (empty($view)) {
            $view = View::fetchContent($content, $this->lang);
        }

        return $view
            ->assign($this->params)
            ->html();
    }

    /**
     * @return string
     * @throws Exception
     */
    private function setTitle() : string
    {
        return (!empty($this->title)
            ? View::fetchContent($this->title, $this->lang)
                ->assign($this->params)
                ->html()
            : ""
        );
    }

    /**
     * @param $tpl_path
     * @return string|null
     */
    private function findTemplate($tpl_path) : ?string
    {
        return Resource::get($tpl_path . "_" . $this->lang . "_" . $this->channel, Resource::TYPE_NOTICE)
            ?? Resource::get($tpl_path . "_" . $this->channel, Resource::TYPE_NOTICE)
            ?? Resource::get($tpl_path . "_" . $this->lang, Resource::TYPE_NOTICE)
            ?? Resource::get($tpl_path, Resource::TYPE_NOTICE)
            ?? null;
    }

    private function findTitle(array $channel, array $map) : string
    {
        if (!empty($title = $this->title ?: $channel["title"][$this->lang] ?? $map["title"][$this->lang] ?? "")) {
            return $title;
        } elseif (!is_array($channel["title"])) {
            return $channel["title"];
        } elseif (!is_array($map["title"])) {
            return $map["title"];
        }

        return "";
    }
    /**
     * @param $tpl_path
     * @param $tpl_name
     * @return array
     * @throws Exception
     */
    private function findTemplateMap($tpl_path, $tpl_name) : array
    {
        return (is_file($map_path = dirname($tpl_path) . DIRECTORY_SEPARATOR . $tpl_name . ".map")
            ? array_replace(self::STRUCT, FilemanagerFs::fileGetContentsJson($map_path, true))
            : self::STRUCT
        );
    }

    private function findChannel(array $map) : array
    {
        return array_replace(self::STRUCT, $map["channels"][$this->channel] ?? []);
    }

    /**
     * @param string $tpl_file
     */
    private function loadImages(string $tpl_file) : void
    {
        $email_images_path = dirname($tpl_file) . DIRECTORY_SEPARATOR . Constant::RESOURCE_NOTICE_IMAGES;
        if (is_dir($email_images_path)) {
            FilemanagerScan::scan([
                str_replace(Constant::DISK_PATH, "", $email_images_path) => [
                    "flag" => FilemanagerScan::SCAN_FILE, "filter" => static::IMAGE_ALLOWED
                ]], function ($image) {
                    $this->adapter->addImage($image, pathinfo($image, PATHINFO_FILENAME));
                });
        }
    }
}
