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

namespace phpformsframework\libs\storage;

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Config;
use phpformsframework\libs\Configurable;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;
use phpformsframework\libs\Hook;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;
use phpformsframework\libs\security\ValidatorFile;
use phpformsframework\libs\storage\drivers\ImageCanvas;
use phpformsframework\libs\storage\drivers\ImageThumb;
use phpformsframework\libs\gui\Resource;
use Exception;
use stdClass;

/**
 * Class Media
 *
 * Immagine Originale
 * @example http://xoduslab.com/test/demo/domains/skeleton/uploads/mod_article/32/img/tiroide-malfunzionamento-esami.jpg
 *
 * Immagine originale Ottimizzata (hardlink)
 * @example http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami.jpg
 *
 * //Nuovi metodi
 * @example Proporzionale automatico:   http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50x100.jpg
 * @example Crop automatico:
 *      TOP-LEFT:      http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50q100.jpg
 *      TOP-MIDDLE:    http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50w100.jpg
 *      TOP-RIGHT:     http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50e100.jpg
 *      MIDDLE-LEFT:   http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50a100.jpg
 *      CENTER:        http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50-100.jpg
 *      MIDDLE-RIGHT:  http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50d100.jpg
 *      BOTTOM-LEFT:   http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50z100.jpg
 *      BOTTOM-MIDDLE: http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50s100.jpg
 *      BOTTOM-RIGHT:  http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-50c100.jpg
 * @example Da impostazioni DB (showfiles_modes): http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-thumb.jpg
 * @example Cambiando il mime dell'immagine: http://xoduslab.com/test/demo/domains/skeleton/static/mod_article/32/img/tiroide-malfunzionamento-esami-jpg-thumb.png
 *
 * Vecchi metodi
 * @example Proporzionale automatico: http://xoduslab.com/test/demo/domains/skeleton/static/50x100/mod_article/32/img/tiroide-malfunzionamento-esami.jpg
 * @example Crop automatico: http://xoduslab.com/test/demo/domains/skeleton/static/50-100/mod_article/32/img/tiroide-malfunzionamento-esami.jpg
 * @example Da impostazioni DB (showfiles_modes): http://xoduslab.com/test/demo/domains/skeleton/static/thumb/mod_article/32/img/tiroide-malfunzionamento-esami.jpg
 * @example Cambiando il mime dell'immagine: http://xoduslab.com/test/demo/domains/skeleton/static/thumb-jpg/mod_article/32/img/tiroide-malfunzionamento-esami.png
 * @package phpformsframework\libs\storage
 */
class Media implements Configurable
{
    protected const ERROR_BUCKET                                            = "storage";

    private const RENDER_MEDIA_PATH                                         = DIRECTORY_SEPARATOR . "media";
    private const RENDER_ASSETS_PATH                                        = DIRECTORY_SEPARATOR . Constant::RESOURCE_ASSETS;
    private const RENDER_WIDGET_PATH                                        = DIRECTORY_SEPARATOR . Constant::RESOURCE_WIDGETS;
    private const RENDER_IMAGE_PATH                                         = DIRECTORY_SEPARATOR . Constant::RESOURCE_ASSET_IMAGES;
    private const MIMETYPE                                                  = ValidatorFile::MIMETYPE;

    private const MIMETYPE_DEFAULT                                          = "text/plain";
    private const MIMETYPE_IMAGE                                            = array(
                                                                                "jpg" => self::MIMETYPE["jpg"],
                                                                                "jpeg" => self::MIMETYPE["jpeg"],
                                                                                "png" => self::MIMETYPE["png"],
                                                                                "gif" => self::MIMETYPE["gif"],
                                                                                "svg" => self::MIMETYPE["svg"]
                                                                            );
    private const MIMETYPE_FONT                                             = array(
                                                                                "eot" => self::MIMETYPE["eot"],
                                                                                "ttf" => self::MIMETYPE["ttf"],
                                                                                "otf" => self::MIMETYPE["otf"],
                                                                                "woff" => self::MIMETYPE["woff"]
                                                                            );

    /**
     * @var Media
     */
    private static $singleton                                       = null;
    private static $modes                                           = null;

    private $basepath                                               = null;

    private $pathinfo                                               = null;

    private $wmk                                                    = null;
    private $filesource                                             = null;
    private $source                                                 = null;
    private $mode                                                   = null;
    private $wizard                                                 = null;
    private $final                                                  = null;

    public $headers                                                 = array(
                                                                        "max_age"           => null
                                                                        , "disposition"     => "inline"
                                                                        , "fake_filename"   => null
                                                                    );

    /**
     * @param null $pathinfo
     * @return Media
     */
    public static function getInstance($pathinfo = null)
    {
        if (self::$singleton === null) {
            self::$singleton                                        = new Media($pathinfo);
        } else {
            self::$singleton->setPathInfo($pathinfo);
        }

        return self::$singleton;
    }

    /**
     * @param string $pathinfo
     * @throws Exception
     */
    public function get(string $pathinfo)
    {
        $this->setPathInfo($pathinfo);
        $headers                                                    = null;
        $status                                                     = null;
        $content_type                                               = $this->getMimeByExtension($this->pathinfo->extension);

        $res                                                        = $content_type != static::MIMETYPE_DEFAULT && $this->process();
        if (!$res) {
            //todo: non renderizza bene l'output. forse per colpa degli headers
            $headers                                                = ["cache" => "no-cache"];
            $status                                                 = 404;
            if ($this->isImage()) {
                $res                                                = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
                $content_type                                       = "image/png";
            }
        }

        Response::sendRawData($res, $content_type, $headers, $status);
    }

    /**
     * @param string $name
     * @param string|null $mode
     * @throws Exception
     */
    public static function getIcon(string $name, string $mode = null) : void
    {
        $icon = new Media(false);
        $icon->setNoImg($mode, $name);
        $icon->renderNoImg($icon->processFinalFile());

        //deve renderizzare l'icona
        //da fare con la gestione delle iconde di ffImafge
    }

    /**
     * @param string $file
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    public static function getUrl(string $file, string $mode = null) : string
    {
        return self::getInfo($file, $mode)->url ?? "";
    }

    /**
     * @param string $file
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    public static function getUrlRelative(string $file, string $mode = null) : string
    {
        return self::getInfo($file, $mode)->relative ?? "";
    }
    /**
     * @param string $file
     * @param string|null $mode
     * @return stdClass
     * @throws Exception
     */
    private static function getInfo(string $file, string $mode = null) : stdClass
    {
        if (strpos($file, "://") !== false) {
            return self::returnInfo(
                null,
                null,
                null,
                null,
                $file
            );
        }

        $query                                                      = null;
        if (strpos($file, "/") === false) {
            $file                                                   = Resource::get($file, Resource::TYPE_ASSET_IMAGES);
        }
        if (empty($file)) {
            return self::returnInfo();
        }

        if (ValidatorFile::isInvalid($file)) {
            return self::returnInfo();
        }


        $arrFile                                                    = (object) pathinfo($file);
        if ($arrFile->extension == "svg") {
            return self::returnInfo(
                null, //@todo da ricavare il percorso svg web renderizzabile
                $arrFile->extension,
                null,
                $mode,
                self::image2base64($file, $arrFile->extension)
            );
        }

        $showfiles                                                  = Constant::SITE_PATH . static::RENDER_MEDIA_PATH;
        if (strpos($arrFile->dirname, static::RENDER_WIDGET_PATH) !== false) {
            $showfiles                                              = Constant::SITE_PATH . static::RENDER_ASSETS_PATH . static::RENDER_WIDGET_PATH;
            if (preg_match('#' . static::RENDER_WIDGET_PATH . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)' . DIRECTORY_SEPARATOR . '?' .  '([^' . DIRECTORY_SEPARATOR . ']*)(.*)#i', $arrFile->dirname, $subdir)) {
                if (empty($subdir[2])) {
                    $arrFile->dirname                               = DIRECTORY_SEPARATOR . $subdir[1] . DIRECTORY_SEPARATOR . $arrFile->extension;
                } else {
                    foreach (Config::getDirBucket(Constant::RESOURCE_WIDGETS) as $type => $asset) {
                        if ($asset["path"] == DIRECTORY_SEPARATOR . $subdir[2]) {
                            $arrFile->dirname = DIRECTORY_SEPARATOR . $subdir[1] . DIRECTORY_SEPARATOR . $type . $subdir[3];
                            break;
                        }
                    }
                }
            }
        } else {
            foreach (Config::getDirBucket(Constant::RESOURCE_ASSETS) as $type => $asset) {
                $fileAsset = explode($asset["path"], $arrFile->dirname, 2);
                if (count($fileAsset) == 2) {
                    $showfiles                                      = Constant::SITE_PATH . static::RENDER_ASSETS_PATH;
                    $arrFile->dirname                               = DIRECTORY_SEPARATOR . $type . $fileAsset[1];
                    break;
                }
            }
        }

        $arrFile->dirname                                           = str_replace(Constant::DISK_PATH, Constant::SITE_PATH, $arrFile->dirname);

        $dirfilename                                                = $showfiles . ($arrFile->dirname == DIRECTORY_SEPARATOR ? "" : $arrFile->dirname) . DIRECTORY_SEPARATOR . $arrFile->filename;
        $diskfile                                                   = $dirfilename . ($arrFile->filename && $mode ? "-" : "") . $mode . ($arrFile->extension ? "." . $arrFile->extension : "") . $query;

        return self::returnInfo(
            $diskfile,
            $arrFile->extension,
            $dirfilename,
            $mode,
            Request::protocolHost() . $diskfile
        );
    }

    private static function returnInfo(string $relative = null, string $extension = null, string $pathfilename = null, string $mode = null, string $url = null) : stdClass
    {
        return (object) array(
            "relative"              => $relative,
            "pathfilename"          => $pathfilename,
            "ext"                   => $extension,
            "mode"                  => $mode,
            "url"                   => $url
        );
    }

    /**
     * @param string $path
     * @param string $ext
     * @return string
     * @throws Exception
     */
    private static function image2base64(string $path, string $ext = "svg") : string
    {
        $data = Filemanager::fileGetContent($path);

        return 'data:' . self::MIMETYPE[$ext] . ';base64,' . base64_encode($data);
    }

    /**
     * @param string|array $file
     * @param null|array $params
     */
    public static function sendHeaders($file, $params = null)
    {
        if (is_array($file) && $params === null) {
            $params                                                 = $file;
            $file                                                   = null;
        }

        if ($file) {
            if (!isset($params["mimetype"])) {
                $params["mimetype"]   = self::getMimeByFilename($file);
            }
            if (!isset($params["filename"])) {
                $params["filename"]   = basename($file);
            }
            if (!isset($params["size"])) {
                $params["size"]       = filesize($file);
            }
            if (!isset($params["etag"])) {
                $params["etag"]       = md5($file . filemtime($file));
            }
            if (!isset($params["mtime"])) {
                $params["mtime"]      = filemtime($file);
            }
        }

        Response::sendHeaders($params);
    }

    /**
     * @access private
     * @param \phpformsframework\libs\dto\ConfigRules $configRules
     * @return \phpformsframework\libs\dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("media", self::METHOD_REPLACE);
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$modes                                                = $config["modes"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        if (!empty($rawdata)) {
            $schema                                                 = array();
            foreach ($rawdata as $thumb) {
                $attr                                               = Dir::getXmlAttr($thumb);
                $key                                                = $attr["name"];
                unset($attr["name"]);
                $schema[$key]                                       = $attr;
            }

            self::$modes                                            = $schema;
        }

        return array(
            "modes" => self::$modes
        );
    }

    /**
     * @param string $file
     * @return string
     */
    public static function getMime(string $file) : string
    {
        $ext                                                        = pathinfo($file, PATHINFO_EXTENSION);
        return ($ext || !function_exists("mime_content_type")
            ? self::getMimeByExtension($ext, "")
            : mime_content_type($file)
        );
    }

    /**
     * @param string $file
     * @return string
     */
    public static function getExtensionByFile(string $file) : string
    {
        return self::getExtensionByMime(self::getMime($file));
    }

    /**
     * @param string $mime
     * @return string
     */
    public static function getExtensionByMime(string $mime) : string
    {
        return (string) array_search($mime, self::MIMETYPE);
    }


    /**
     * @param string $filename
     * @param string $default
     * @return string
     */
    public static function getMimeByFilename(string $filename, string $default = self::MIMETYPE_DEFAULT) : string
    {
        $ext                                                        = pathinfo($filename, PATHINFO_EXTENSION);

        return self::getMimeByExtension($ext, $default);
    }

    /**
     * @param string $ext
     * @param string $default
     * @return string
     */
    public static function getMimeByExtension(string $ext, string $default = self::MIMETYPE_DEFAULT) : string
    {
        $mime                                                       = $default;
        if ($ext) {
            $ext                                                    = strtolower($ext);
            $mime_type                                              = static::MIMETYPE;
            if (isset($mime_type[$ext])) {
                $mime                                               = $mime_type[$ext];
            }
        }
        return $mime;
    }

    /**
     * @param string|null $ext
     * @param bool $abs
     * @return string
     * @throws Exception
     */
    public static function getIconPath(string $ext = null, bool $abs = false) : string
    {
        if ($ext) {
            $arrExt                                                 = explode(".", $ext);
            $filename                                               = $arrExt[0];

            switch ($filename) {
                case "png":
                case "jpg":
                case "jpeg":
                case "gif":
                    $filename                                       = "noimg";
                    break;
                case "zip":
                case "gz":
                case "rar":
                case "bz2":
                    $filename                                       = "archive";
                    break;
                case "mp3":
                case "wav":
                    $filename                                       = "audio";
                    break;
                case "avi":
                case "mpg":
                    $filename                                       = "video";
                    break;
                case "spacer":
                    $filename                                       = "spacer";
                    break;
                default:
            }

            $abs_path                                               = Resource::get($filename, Resource::TYPE_ASSET_IMAGES);
            if (!$abs_path) {
                $abs_path = Resource::get("error", Resource::TYPE_ASSET_IMAGES);
            }
            if (!$abs_path) {
                Error::register("Icon " . $filename . " not found", static::ERROR_BUCKET);
            }
            $basename                                               = basename($abs_path);
            if ($abs === false) {
                $res                                                = Constant::SITE_PATH . static::RENDER_ASSETS_PATH . static::RENDER_IMAGE_PATH . DIRECTORY_SEPARATOR . $basename;
            } elseif ($abs === true) {
                $res                                                = $abs_path;
            } else {
                $res                                                = $abs . $basename;
            }
        } else {
            $abs_path = Resource::get("unknown", Resource::TYPE_ASSET_IMAGES);
            if (!$abs_path) {
                Error::register("Icon unknown not found", static::ERROR_BUCKET);
            }
            $res                                                    = (
                $abs
                                                                        ? $abs_path
                                                                        : Constant::SITE_PATH . static::RENDER_ASSETS_PATH . static::RENDER_IMAGE_PATH . DIRECTORY_SEPARATOR . basename($abs_path)
                                                                    );
        }

        return $res;
    }

    /**
     * @param string $basename
     * @return string|null
     */
    private static function getModeByNoImg(string $basename) : ?string
    {
        $mode                                                       = null;
        $source                                                     = explode(".", strrev($basename), 2);
        $filename                                                   = strrev(
            isset($source[1])
                                                                        ? $source[1]
                                                                        : $source[0]
                                                                    );
        $arrFilename   			                                    = explode("-", $filename);

        $offset                                                     = count($arrFilename) - 1;
        if (!empty(self::$modes)) {
            foreach (self::$modes as $key => $value) {
                if (strpos($basename, ($offset ? "-" : "") . $key . ".")) {
                    $mode                                           = $key;
                    break;
                }
            }
        }
        if (!$mode) {
            if ($offset >= 2 && is_numeric($arrFilename[$offset]) && is_numeric($arrFilename[$offset - 1])) {
                $mode                                               = $arrFilename[$offset - 1] . "-" . $arrFilename[$offset];
            } elseif (self::getModeAuto($arrFilename[$offset], "x")
                || self::getModeAuto($arrFilename[$offset], "q")
                || self::getModeAuto($arrFilename[$offset], "w")
                || self::getModeAuto($arrFilename[$offset], "e")
                || self::getModeAuto($arrFilename[$offset], "a")
                || self::getModeAuto($arrFilename[$offset], "s")
                || self::getModeAuto($arrFilename[$offset], "d")
                || self::getModeAuto($arrFilename[$offset], "z")
                || self::getModeAuto($arrFilename[$offset], "c")
            ) {
                $mode                                               = $arrFilename[$offset];
            }
        }

        if ($filename == $mode) {
            $mode = null;
        }

        return $mode;
    }

    /**
     * @param string $value
     * @param string $char
     * @return bool
     */
    private static function getModeAuto(string $value, string $char) : bool
    {
        return is_numeric(str_replace($char, "", $value)) && substr_count($value, $char) == 1;
    }

    /**
     * @param string $file
     * @return array|null
     */
    private static function getModeByFile(string $file) : ?stdClass
    {
        $res                                                        = null;
        $source                                                     = (object) pathinfo($file);

        $mode                                                       = self::getModeByNoImg($source->basename);
        if ($mode) {
            $res                                                    = new stdClass();
            $res->mode                                              = $mode;
            $res->filename                                          = str_replace("-". $mode . "." . $source->extension, "", $source->basename);
            $res->basename                                          = $res->filename . "." . $source->extension;
        }

        return $res;
    }

    /**
     * Media constructor.
     * @param string|null $pathinfo
     */
    public function __construct(string $pathinfo = null)
    {
        $this->setPathInfo($pathinfo);
    }

    /**
     * @param string $resource_name
     * @return string|null
     */
    private function staticProcessWidget(string $resource_name) : ?string
    {
        $arrDirname                                                 = explode(DIRECTORY_SEPARATOR, $this->pathinfo->dirname);
        $widget                                                     = Resource::widget($arrDirname[2]);
        return $widget[$arrDirname[3]][$resource_name] ?? null;
    }

    /**
     * @param string $resource_name
     * @return string|null
     */
    private function staticProcessAsset(string $resource_name) : ?string
    {
        $arrDirname                                                 = explode(DIRECTORY_SEPARATOR, $this->pathinfo->dirname);
        return Resource::get($resource_name, $arrDirname[1]);
    }

    /**
     * @param string|null $mode
     * @return string|null
     */
    private function staticResource(string &$mode = null) : ?string
    {
        $name                                                       = $this->staticResourceName($mode);

        return (strpos($this->pathinfo->dirname, static::RENDER_WIDGET_PATH) === 0
            ? $this->staticProcessWidget($name)
            : $this->staticProcessAsset($name)
        );
    }

    /**
     * @param string|null $mode
     * @return string
     */
    private function staticResourceName(string &$mode = null) : string
    {
        if (!$mode) {
            $mode                                                   = $this->getModeByNoImg($this->pathinfo->filename);
        }

        return ($mode
            ? str_replace("-" . $mode, "", $this->pathinfo->filename)
            : $this->pathinfo->filename
        );
    }
    /**
     * @param string|null $mode
     * @return bool
     */
    private function staticProcess(string $mode = null) : bool
    {
        $resource                                                   = $this->staticResource($mode);
        if ($resource) {
            $this->basepath                                         = dirname($resource);
            $this->filesource                                       = DIRECTORY_SEPARATOR . basename($resource);
            $this->mode                                             = $mode;
        }
        return (bool) $resource;
    }

    /**
     * @return string|null
     */
    private function isImage() : ?string
    {
        return static::MIMETYPE_IMAGE[$this->pathinfo->extension] ?? null;
    }
    /**
     * @return string|null
     */
    private function isFont() : ?string
    {
        return static::MIMETYPE_FONT[$this->pathinfo->extension] ?? null;
    }

    /**
     * @param string|null $mode
     * @return bool
     * @throws Exception
     */
    private function process(string $mode = null) : bool
    {
        if ($this->pathinfo->render == static::RENDER_ASSETS_PATH) {
            if ($this->staticProcess($mode)) {
                $final_file = $this->processFinalFile();
                if ($final_file) {
                    $this->renderNoImg($final_file);
                }
            } elseif ($this->isImage()) {
                Response::redirect($this->getIconPath("noimg"), 302);
            } elseif (file_exists(Constant::DISK_PATH . "/app/themes/default/assets/dist" . $this->pathinfo->orig)) {
                //@todo da sistemare i path rendendoli dinamici
                $this->saveFromOriginal(Constant::DISK_PATH . "/app/themes/default/assets/dist" . $this->pathinfo->orig, $this->basepathCache() . $this->pathinfo->orig);
                $this->sendHeaders(Constant::DISK_PATH . "/app/themes/default/assets/dist" . $this->pathinfo->orig, $this->headers);
                readfile(Constant::DISK_PATH . "/app/themes/default/assets/dist" . $this->pathinfo->orig);
                exit;
            }
        } else {
            return $this->renderProcess($mode);
        }

        return false;
    }

    /**
     * @param string|null $path
     */
    public function setPathInfo(string $path = null) : void
    {
        if ($path) {
            if (strpos($path, $this::RENDER_MEDIA_PATH) === 0) {
                $path                                               = substr($path, strlen($this::RENDER_MEDIA_PATH));
                $render                                             = $this::RENDER_MEDIA_PATH;
            } elseif (strpos($path, $this::RENDER_ASSETS_PATH) === 0) {
                $path                                               = substr($path, strlen($this::RENDER_ASSETS_PATH));
                $render                                             = $this::RENDER_ASSETS_PATH;
            } else {
                $render                                             = $this::RENDER_MEDIA_PATH;
            }

            $path                                                   = parse_url($path, PHP_URL_PATH);
            $this->pathinfo                                         = (object) pathinfo($path);
            $this->pathinfo->render                                 = $render;
            $this->pathinfo->orig                                   = $path;
        }
    }

    /**
     * @param string|null $mode
     * @return bool
     * @throws Exception
     */
    private function renderProcess(string $mode = null) : bool
    {
        $this->clear();
        $this->waterMark();
        $this->findSource($mode);

        $status                                                     = null;
        $final_file                                                 = null;

        if ($this->filesource && $this->basepath && is_file($this->basepath . $this->filesource)) {
            if ($this->mode) {
                $final_file                                         = $this->processFinalFile();
            } else {
                $cache_basepath                                     = $this->basepathCache();
                if ($cache_basepath && !is_file($cache_basepath . $this->pathinfo->orig)) {
                    $this->saveFromOriginal($this->basepath . $this->filesource, $cache_basepath . $this->pathinfo->orig);
                    $final_file                                     = $cache_basepath . $this->pathinfo->orig;
                }
            }
        }

        if (!$final_file) {
            $filename                                               = $this->pathinfo->filename;
            if ($this->mode) {
                $filename                                           = str_replace(array("-" . $this->mode, $this->mode), "", $filename);
                if (!$filename) {
                    $filename = $this->pathinfo->extension;
                }
            }
            $this->setNoImg($this->mode);
            $final_file                                             = $this->processFinalFile($filename);
            if ($filename != pathinfo($this->filesource, PATHINFO_FILENAME)) {
                $status = 404;
            }
        }

        if ($final_file) {
            $this->renderNoImg($final_file, $status);
        }

        return false;
    }

    private function clear()
    {
        $this->wmk                                                  = null;
        $this->source                                               = null;
        $this->mode                                                 = null;
        $this->wizard                                               = null;
        $this->basepath                                             = null;
        $this->filesource                                           = null;
        $this->final                                                = null;
    }

    private function waterMark()
    {
        $this->wmk                                                  = array();
        $orig                                                       = $this->pathinfo->orig;
        if (strpos($orig, "/wmk") !== false) {
            $arrWmk                                                 = explode("/wmk", substr($orig, strpos($orig, "/wmk") + strlen("/wmk")));
            if (!empty($arrWmk)) {
                foreach ($arrWmk as $arrWmk_file) {
                    $wmk_abs_file                                   = Constant::UPLOAD_DISK_PATH . $arrWmk_file;
                    if (strlen($arrWmk_file) && is_file($wmk_abs_file)) {
                        $this->wmk[]["file"]                        = $wmk_abs_file;
                    }
                }
            }

            $this->setPathInfo(substr($orig, 0, strpos($orig, "/wmk")));
        }
    }

    /**
     * @return string
     */
    private function basepathAsset() : string
    {
        return str_replace($this->filesource, "", Resource::get($this->source->filename, Resource::TYPE_ASSET_IMAGES));
    }

    /**
     * @return string
     */
    private function basepathMedia() : string
    {
        return Constant::UPLOAD_DISK_PATH;
    }

    /**
     * @param string|null $mode
     */
    private function findSource(string $mode = null)
    {
        $this->resolveSrcPath($mode);
        if ($this->filesource) {
            $this->basepath = (
                $this->pathinfo->render == static::RENDER_ASSETS_PATH
                ? $this->basepathAsset()
                : $this->basepathMedia()
            );
        }
    }

    /**
     * @param string|null $filename
     * @return array|null
     */
    private function makeFinalFile(string $filename = null) : ?stdClass
    {
        if ($this->filesource) {
            $str_wmk_file                                           = "";
            if (!empty($this->wmk)) {
                $str_wmk_file_time                                  = "";
                $str_wmk_file_path                                  = "";
                foreach ($this->wmk as $wmk_file) {
                    $str_wmk_file_time                              .= filectime($wmk_file);
                    $str_wmk_file_path                              .= $wmk_file;
                }
                $str_wmk_file                                       = "-" . md5($str_wmk_file_time . $str_wmk_file_path);
            }

            if ($this->mode) {
                $this->final                                        = new stdClass();

                $filepath                                           = (object) pathinfo($this->filesource);
                $format_is_different                                = (isset($this->source->extension) && $this->source->extension != $this->pathinfo->extension);
                $this->final->dirname                               = $this->pathinfo->dirname;
                $this->final->filename                              = $filename ?? $filepath->filename
                                                                        . (
                                                                            $format_is_different
                                                                            ? "-" . $this->source->extension
                                                                            : ""
                                                                        )
                                                                        . "-" . $this->mode
                                                                        . $str_wmk_file;
                $this->final->extension                             = $this->pathinfo->extension;

                $this->final->exist                                 = is_file($this->getFinalFile());
            } else {
                $this->final                                        = $this->pathinfo;
                $this->final->exist                                 = is_file($this->getFinalFile());
            }
        }

        return $this->final;
    }

    /**
     * @param string|null $file_stored
     * @return string
     */
    private function getFinalFile(string &$file_stored = null) : ?string
    {
        $final_path                                                 = null;
        if ($this->final) {
            $final_path                                             = $this->basepathCache()
                                                                        . $this->final->dirname
                                                                        . (
                                                                            $this->final->dirname == DIRECTORY_SEPARATOR
                                                                            ? ""
                                                                            : DIRECTORY_SEPARATOR
                                                                        )
                                                                        . $this->final->filename
                                                                        . "." . $this->final->extension;
        }

        if (!empty($this->final->exist)) {
            $file_stored = $final_path;
        }

        return $final_path;
    }

    /**
     * @param array $params
     * @throws Exception
     */
    private function createImage(array $params) : void
    {
        $default_params                                             = array(
                                                                        "dim_x"                     => null,
                                                                        "dim_y"                     => null,
                                                                        "resize"                    => false,
                                                                        "when"                      => "ever",
                                                                        "alignment"                 => "center",
                                                                        "mode"                      => "proportional",
                                                                        "transparent"               => true,
                                                                        "bgcolor"                   => "FFFFFF",
                                                                        "alpha"                     => 0,
                                                                        "format"                    => "jpg",
                                                                        "frame_size"                => 0,
                                                                        "frame_color"               => "FFFFFF",
                                                                        "wmk_enable"                => false,
                                                                        "enable_thumb_word_dir"     => false,
                                                                        "enable_thumb_word_file"    => false
                                                                    );
        $params                                                     = (object) array_replace_recursive($default_params, $params);
        $extend                                                     = true;

        if ($extend) {
            $params->filesource                                   = (
                !empty($params->force_icon)
                                                                        ? Constant::DISK_PATH . $params->force_icon
                                                                        : $this->basepath . $this->filesource
                                                                    );

            if ($params->resize && $params->mode != "crop") {
                $params->max_x                                      = $params->dim_x;
                $params->max_y                                      = $params->dim_y;

                $params->dim_x                                      = null;
                $params->dim_y                                      = null;
            } else {
                $params->max_x                                      = null;
                $params->max_y                                      = null;
            }

            if ($params->format == "png" && $params->transparent) {
                $params->bgcolor_csv                                = $params->bgcolor;
                $params->alpha_csv                                  = 127;

                $params->bgcolor_new                                = $params->bgcolor;
                $params->alpha_new                                  = 127;
            } else {
                $params->bgcolor_csv                                = null;
                $params->alpha_csv                                  = 0;

                $params->bgcolor_new                                = $params->bgcolor;
                $params->alpha_new                                  = $params->alpha;
            }



            $params->wmk_word_enable                                = (
                is_dir($this->basepath . $this->filesource)
                                                                        ? $params->enable_thumb_word_dir
                                                                        : $params->enable_thumb_word_file
                                                                    );
        } else {
            if ($params->dim_x == 0) {
                $params->dim_x                                      = null;
            }
            if ($params->dim_y == 0) {
                $params->dim_y                                      = null;
            }
            if ($params->dim_x || $params->max_x == 0) {
                $params->max_x                                      = null;
            }
            if ($params->dim_y || $params->max_y == 0) {
                $params->max_y                                      = null;
            }

            $params->bgcolor_csv                                    = $params->bgcolor;
            $params->alpha_csv                                      = $params->alpha;
            $params->bgcolor_new                                    = $params->bgcolor;
            $params->alpha_new                                      = $params->alpha;
            $params->filesource                                     = $this->basepath . $this->filesource;
            $params->frame_color                                    = null;
            $params->frame_size                                     = 0;
            $params->wmk_method                                     = "proportional";
            $params->wmk_word_enable                                = false;
        }

        $cCanvas                                                    = new ImageCanvas();

        $cCanvas->cvs_res_background_color_hex 			            = $params->bgcolor_csv;
        $cCanvas->cvs_res_background_color_alpha 		            = $params->alpha_new;
        $cCanvas->format 								            = $params->format;

        $cThumb                                                     = new ImageThumb($params->dim_x, $params->dim_y);
        $cThumb->new_res_max_x 							            = $params->max_x;
        $cThumb->new_res_max_y 							            = $params->max_y;
        $cThumb->src_res_path 							            = $params->filesource;

        $cThumb->new_res_background_color_hex 			            = $params->bgcolor_new;
        $cThumb->new_res_background_color_alpha			            = $params->alpha_new;

        $cThumb->new_res_frame_size 					            = $params->frame_size;
        $cThumb->new_res_frame_color_hex 				            = $params->frame_color;

        $cThumb->new_res_method 						            = $params->mode;
        $cThumb->new_res_resize_when 					            = $params->when;
        $cThumb->new_res_align 							            = $params->alignment;

        //Default Watermark Image
        if ($params->wmk_enable) {
            $cThumb_wmk                                             = new ImageThumb($params->dim_x, $params->dim_y);
            $cThumb_wmk->new_res_max_x 					            = $params->max_x;
            $cThumb_wmk->new_res_max_y 					            = $params->max_y;
            $cThumb_wmk->src_res_path 					            = $params->wmk_file;

            $cThumb_wmk->new_res_background_color_alpha	            = "127";

            $cThumb_wmk->new_res_method 				            = $params->mode;
            $cThumb_wmk->new_res_resize_when 			            = $params->when;
            $cThumb_wmk->new_res_align 					            = $params->wmk_alignment;
            $cThumb_wmk->new_res_method 				            = $params->wmk_method;

            $cThumb->addWatermark($cThumb_wmk);
        }

        //Multi Watermark Image
        if (!empty($this->wmk)) {
            foreach ($this->wmk as $wmk_file) {
                $cThumb_wmk                                         = new ImageThumb($params->dim_x, $params->dim_y);
                $cThumb_wmk->new_res_max_x 						    = $params->max_x;
                $cThumb_wmk->new_res_max_y 						    = $params->max_y;
                $cThumb_wmk->src_res_path 						    = $wmk_file->file;

                $cThumb_wmk->new_res_background_color_alpha		    = "127";

                $cThumb_wmk->new_res_method						    = $params->mode;
                $cThumb_wmk->new_res_resize_when 				    = $params->when;
                $cThumb_wmk->new_res_align 						    = $params->wmk_alignment;
                $cThumb_wmk->new_res_method 					    = $params->wmk_method;

                $cThumb->addWatermark($cThumb_wmk);
            }
        }

        //Watermark Text
        if ($params->wmk_word_enable) {
            $cThumb->new_res_font["caption"]                        = $params->shortdesc;
            if (preg_match('/^[A-F0-9]+$/is', strtoupper($params->word_color))) {
                $cThumb->new_res_font["color"]                      = $params->word_color;
            }
            if (is_numeric($params->word_size) && $params->word_size > 0) {
                $cThumb->new_res_font["size"]                       = $params->word_size;
            }
            if (strlen($params->word_type)) {
                $cThumb->new_res_font["type"]                       = $params->word_type;
            }
            if (strlen($params->word_align)) {
                $cThumb->new_res_font["align"]                      = $params->word_align;
            }
        }

        $cCanvas->addChild($cThumb);

        $final_file                                                 = $this->getFinalFile();

        Filemanager::makeDir(dirname($final_file), 0775, $this->basepathCache());

        $cCanvas->process($final_file);
    }

    /**
     * @return string
     */
    private function basepathCache() : string
    {
        return ($this->pathinfo->render == static::RENDER_ASSETS_PATH
            ? Dir::findCachePath(Constant::RESOURCE_CACHE_ASSETS)
            : Dir::findCachePath(Constant::RESOURCE_CACHE_THUMBS)
        );
    }

    /**
     * @param string $mode
     * @return stdClass
     */
    private function getModeWizard(string $mode) : ?stdClass
    {
        $char                                                       = strtolower(preg_replace('/[0-9]+/', '', $mode));
        $arrMode                                                    = explode($char, $mode);
        if (!(count($arrMode) == 2 && is_numeric($arrMode[0]) && is_numeric($arrMode[1]))) {
            $char                                                   = null;
        }

        $wizard                                                     = new stdClass();
        $wizard->alignment                                          = "center";
        $wizard->mode                                               = $arrMode;
        $wizard->method                                             = "crop";
        $wizard->resize                                             = false;

        switch ($char) {
            case "x":
                $wizard->alignment                                  = "center";
                $wizard->method                                     = "proportional";
                break;
            case "q":
                $wizard->alignment                                  = "top-left";
                break;
            case "w":
                $wizard->alignment                                  = "top-middle";
                break;
            case "e":
                $wizard->alignment                                  = "top-right";
                break;
            case "a":
                $wizard->alignment                                  = "middle-left";
                break;
            case "d":
                $wizard->alignment                                  = "middle-right";
                break;
            case "z":
                $wizard->alignment                                  = "bottom-left";
                break;
            case "s":
                $wizard->alignment                                  = "bottom-middle";
                break;
            case "c":
                $wizard->alignment                                  = "bottom-right";
                break;
            default:
                $wizard                                             = null;
        }

        return $wizard;
    }

    /**
     * @return array|null
     */
    private function getMode() : ?array
    {
        if (!$this->mode) {
            return null;
        }
        $setting                                                    = null;

        if (isset(self::$modes[$this->mode])) {
            $setting                                                = self::$modes[$this->mode];
        } else {
            if ($this->wizard = $this->getModeWizard($this->mode)) {
                $setting                                            = array(
                                                                        "dim_x"             => $this->wizard->mode[0],
                                                                        "dim_y"             => $this->wizard->mode[1],
                                                                        "format"            => $this->final->extension,
                                                                        "alignment"         => $this->wizard->alignment,
                                                                        "mode"              => $this->wizard->method,
                                                                        "resize"            => $this->wizard->resize,
                                                                        "last_update"       => time()
                                                                    );
            }
        }

        return $setting;
    }

    /**
     * @param string|null $filename
     * @return string|null
     * @throws Exception
     */
    private function processFinalFile(string $filename = null) : ?string
    {
        $final_file                                                 = null;
        if ($this->filesource) {
            if (!$this->final) {
                $this->makeFinalFile($filename);
            }

            if ($this->final) {
                $final_file_stored                                  = null;
                $final_file                                         = $this->getFinalFile($final_file_stored);

                $modeCurrent                                        = $this->getMode();
                if (is_array($modeCurrent)) {
                    if (!Buffer::cacheIsValid($this->basepath . $this->filesource, $final_file_stored)) {
                        $this->createImage($modeCurrent);
                        Hook::handle("media_on_create_image", $final_file);
                    }
                } elseif (!$modeCurrent && is_file($this->basepath . $this->filesource)) {
                    if (!Buffer::cacheIsValid($this->basepath . $this->filesource, $final_file_stored)) {
                        $this->saveFromOriginal($this->basepath . $this->filesource, $final_file);
                    }
                } else {
                    $icon                                           = $this->getIconPath(basename($this->filesource), true);

                    if (!Buffer::cacheIsValid($this->basepath . $this->filesource, $final_file_stored) && $icon) {
                        $this->saveFromOriginal($icon, $final_file);
                    }
                }
            }
        }

        return $final_file;
    }

    /**
     * @param string $source
     * @param string $destination
     * @throws Exception
     */
    private function saveFromOriginal(string $source, string $destination) : void
    {
        Filemanager::makeDir($destination, 0775, $this->basepathCache());

        if ($this->pathinfo->render == static::RENDER_ASSETS_PATH) {
            if (!@copy($source, $destination)) {
                Error::register("Copy Failed. Check read permission on: " . $source . " and if directory exist and have write permission on " . $destination, static::ERROR_BUCKET);
            }
        } else {
            if (is_writable($source) && !@link($source, $destination)) {
                Error::register("Link Failed. Check write permission on: " . $source . " and if directory exist and have write permission on " . $destination, static::ERROR_BUCKET);
            }
        }
    }


    /**
     * @param string|null $mode
     * @param string|null $icon_name
     * @return bool
     * @throws Exception
     */
    private function setNoImg(string $mode = null, string $icon_name = null) : bool
    {
        if (!$icon_name) {
            $icon_name                                              = (
                isset($this->pathinfo->extension)
                ? $this->pathinfo->extension
                : $this->pathinfo->basename
            );
        }
        if (!$mode) {
            $mode                                                   = self::getModeByNoImg($this->pathinfo->basename);
        }
        if ($mode) {
            $icon_name                                              = str_replace("-". $mode, "", $icon_name);
        }
        $icon                                                       = $this->getIconPath($icon_name, true);
        if ($icon) {
            $this->basepath                                         = dirname($icon);
            $this->filesource                                       = DIRECTORY_SEPARATOR . basename($icon);
            $this->mode                                             = $mode;
        }

        return (bool) $icon;
    }

    /**
     * @param string $final_file
     * @param int|null $code
     */
    private function renderNoImg(string $final_file, int $code = null)
    {
        $this->headers["cache"]                                     = "must-revalidate";
        $this->headers["filename"]                                  = $this->pathinfo->basename;
        $this->headers["mimetype"]                                  = $this->getMimeByFilename($final_file);

        if ($code) {
            Response::httpCode($code);
        }
        //todo: https://local.hcore.app/assets/images/nobrand-100x50.png non funziona cancellando la cache
        $this->sendHeaders($final_file, $this->headers);
        readfile($final_file);
        exit;
    }

    /**
     * @param stdClass $source
     * @param stdClass $image
     * @param string $sep
     * @param string|null $mode
     * @return string
     */
    private function overrideSrcPath(stdClass &$source, stdClass $image, string $sep, string $mode = null) : string
    {
        $file 					                                    = explode("-" . $sep . "-", $image->filename);
        $source->extension 	                                        = $sep;
        $source->filename 	                                        = $file[0];

        return ($mode
            ? $mode
            : $file[1]
        );
    }

    /**
     * @param string|null $mode
     */
    private function resolveSrcPath(string $mode = null) : void
    {
        $source                                                     = new stdClass();
        $image                                                      = $this->pathinfo;

        $source->dirname 			                                = ($image->dirname == DIRECTORY_SEPARATOR ? "" : $image->dirname);
        $source->extension 		                                    = $image->extension;
        $source->filename 	                                        = $image->filename;

        if (strpos($image->filename, "-png-") !== false) {
            $mode                                                   = $this->overrideSrcPath($source, $image, "png", $mode);
        } elseif (strpos($image->filename, "-jpg-") !== false) {
            $mode                                                   = $this->overrideSrcPath($source, $image, "jpg", $mode);
        } elseif (strpos($image->filename, "-jpeg-") !== false) {
            $mode                                                   = $this->overrideSrcPath($source, $image, "jpeg", $mode);
        } elseif (!$mode) {
            $res                                                    = $this->getModeByFile($source->dirname . DIRECTORY_SEPARATOR . $image->filename . "." . $source->extension);
            if ($res) {
                $source->filename                                   = $res->filename;
                $mode                                               = $res->mode;
            } else {
                $mode                                               = false;
            }
        }

        if ($source->filename && $source->extension) {
            $source->basename 	                                    = $source->filename . "." . $source->extension;
            $this->source                                           = $source;
            $this->mode                                             = $mode;
            $this->filesource 				                        = $source->dirname . DIRECTORY_SEPARATOR . $source->basename;
        }
    }
}
