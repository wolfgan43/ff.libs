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
namespace ff\libs\storage;

use ff\libs\Env;
use ff\libs\Kernel;
use ff\libs\cache\Buffer;
use ff\libs\Config;
use ff\libs\Configurable;
use ff\libs\Constant;
use ff\libs\Dir;
use ff\libs\dto\ConfigRules;
use ff\libs\Hook;
use ff\libs\Response;
use ff\libs\security\ValidatorFile;
use ff\libs\storage\drivers\ImageCanvas;
use ff\libs\storage\drivers\ImageThumb;
use ff\libs\gui\Resource;
use ff\libs\util\ServerManager;
use ff\libs\Exception;
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
 * @package ff\libs\storage
 */
class Media implements Configurable
{
    use ServerManager;

    protected const ERROR_BUCKET                                            = "storage";

    private const HOOK_ON_AFTER_CREATE                                      = "Media::onAfterCreate";

    private const RENDER_MEDIA_PATH                                         = DIRECTORY_SEPARATOR . "media";
    private const RENDER_ASSETS_PATH                                        = DIRECTORY_SEPARATOR . Constant::RESOURCE_ASSETS;
    private const RENDER_WIDGET_PATH                                        = DIRECTORY_SEPARATOR . Constant::RESOURCE_WIDGETS;
    private const RENDER_IMAGE_PATH                                         = DIRECTORY_SEPARATOR . Constant::RESOURCE_ASSET_IMAGES;
    private const MIMETYPE                                                  = ValidatorFile::MIMETYPE;

    private const MIMETYPE_DEFAULT                                          = "text/plain";
    private const MIMETYPE_IMAGE                                            = array(
                                                                                "jpg"   => self::MIMETYPE["jpg"],
                                                                                "jpeg"  => self::MIMETYPE["jpeg"],
                                                                                "png"   => self::MIMETYPE["png"],
                                                                                "gif"   => self::MIMETYPE["gif"],
                                                                                "svg"   => self::MIMETYPE["svg"]
                                                                            );
    private const MIMETYPE_FONT                                             = array(
                                                                                "eot"   => self::MIMETYPE["eot"],
                                                                                "ttf"   => self::MIMETYPE["ttf"],
                                                                                "otf"   => self::MIMETYPE["otf"],
                                                                                "woff"  => self::MIMETYPE["woff"]
                                                                            );
    private const MIMETYPE_VIDEO                                            = array(
                                                                                "avi"   => self::MIMETYPE["avi"],
                                                                                "mp4"   => self::MIMETYPE["mp4"],
                                                                                "mpe"   => self::MIMETYPE["mpe"],
                                                                                "mpeg"  => self::MIMETYPE["mpeg"]
                                                                            );
    private const MIMETYPE_AUDIO                                            = array(
                                                                                "mid"   => self::MIMETYPE["mid"],
                                                                                "rmi"   => self::MIMETYPE["rmi"],
                                                                                "mp3"   => self::MIMETYPE["mp3"],
                                                                                "mpg"   => self::MIMETYPE["mpg"]
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
    public static function getInstance($pathinfo = null): Media
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
        $headers                                                    = [];
        $status                                                     = null;
        $content_type                                               = (
            empty($this->pathinfo->extension)
            ? static::MIMETYPE_DEFAULT
            : $this->getMimeByExtension($this->pathinfo->extension)
        );
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
        $icon->readfile($icon->processFinalFile());

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
            $file                                                   = Resource::image($file);
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
            if (preg_match('#' . static::RENDER_WIDGET_PATH . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)' . '(.*)#i', $arrFile->dirname, $subdir)) {
                $arrFile->dirname                                   = DIRECTORY_SEPARATOR . $subdir[1] . DIRECTORY_SEPARATOR . self::ext2dirBucket(Constant::RESOURCE_WIDGETS, $arrFile, $subdir[2]);
            }
        } elseif (strpos($arrFile->dirname, static::RENDER_ASSETS_PATH) !== false && ($asset_path = self::ext2dirBucket(Constant::RESOURCE_ASSETS, $arrFile))) {
            $showfiles                                              = Constant::SITE_PATH . static::RENDER_ASSETS_PATH;
            $arrFile->dirname                                       = DIRECTORY_SEPARATOR . $asset_path;
        } elseif (strpos($arrFile->dirname, Constant::UPLOAD_PATH) === 0) {
            $arrFile->dirname                                       = substr($arrFile->dirname, strlen(Constant::UPLOAD_PATH));
        }

        $arrFile->dirname                                           = str_replace(Constant::DISK_PATH, Constant::SITE_PATH, $arrFile->dirname);
        $dirfilename                                                = $showfiles . ($arrFile->dirname == DIRECTORY_SEPARATOR ? "" : $arrFile->dirname) . DIRECTORY_SEPARATOR . $arrFile->filename;
        $diskfile                                                   = $dirfilename . ($arrFile->filename && $mode ? "-" : "") . $mode . ($arrFile->extension ? "." . $arrFile->extension : "") . $query;

        return self::returnInfo(
            $diskfile,
            $arrFile->extension,
            $dirfilename,
            $mode,
            self::protocolHost() . $diskfile
        );
    }

    /**
     * @param string $bucket
     * @param stdClass $arrFile
     * @param string|null $path
     * @return string
     */
    private static function ext2dirBucket(string $bucket, stdClass $arrFile, string $path = null) : string
    {
        if (isset(static::MIMETYPE_IMAGE[$arrFile->extension])) {
            $key = "images";
        } elseif (isset(static::MIMETYPE_FONT[$arrFile->extension])) {
            $key = "fonts";
        } elseif (isset(static::MIMETYPE_VIDEO[$arrFile->extension])) {
            $key = "video";
        } elseif (isset(static::MIMETYPE_AUDIO[$arrFile->extension])) {
            $key = "audio";
        } else {
            $key = $arrFile->extension;
        }

        if (!$path && ($asset_path = Config::getDirBucket($bucket)[$key]["path"] ?? null) && count(($fileAsset = explode($asset_path, $arrFile->dirname, 2)))== 2) {
            $path = $fileAsset[1];
        }

        return (strpos($path, DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR) === 0
            ? ltrim($path, DIRECTORY_SEPARATOR)
            : $key . $path
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
        $data = FilemanagerWeb::fileGetContents($path);

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
     * @param ConfigRules $configRules
     * @return ConfigRules
     */
    public static function loadConfigRules(ConfigRules $configRules) : ConfigRules
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
                if (empty($attr["name"])) {
                    continue;
                }

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
        if (!empty($ext)) {
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
    public function getIconPath(string $ext = null, bool $abs = false) : string
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

            $abs_path                                               = Resource::image($filename);
            if (!$abs_path) {
                $abs_path = Resource::image("error");
            }
            if (!$abs_path) {
                throw new Exception("Icon " . $filename . " not found", 404);
            }
            $this->resolveSrcPath();
            if ($this->mode) {
                $basename = pathinfo($abs_path, PATHINFO_FILENAME) . "-" . $this->mode . "." . pathinfo($abs_path, PATHINFO_EXTENSION);
            } else {
                $basename                                           = basename($abs_path);
            }

            if ($abs === false) {
                $res                                                = Constant::SITE_PATH . static::RENDER_ASSETS_PATH . static::RENDER_IMAGE_PATH . DIRECTORY_SEPARATOR . $basename;
            } elseif ($abs === true) {
                $res                                                = $abs_path;
            } else {
                $res                                                = $abs . $basename;
            }
        } else {
            $abs_path = Resource::image("unknown");
            if (!$abs_path) {
                throw new Exception("Icon unknown not found", 404);
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
    private function getModeByNoImg(string $basename) : ?string
    {
        $mode                                                       = null;
        $source                                                     = explode(".", strrev($basename), 2);
        $filename                                                   = strrev($source[1] ?? $source[0]);
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
            } elseif ($this->getModeAuto($arrFilename[$offset], "x")
                || $this->getModeAuto($arrFilename[$offset], "q")
                || $this->getModeAuto($arrFilename[$offset], "w")
                || $this->getModeAuto($arrFilename[$offset], "e")
                || $this->getModeAuto($arrFilename[$offset], "a")
                || $this->getModeAuto($arrFilename[$offset], "s")
                || $this->getModeAuto($arrFilename[$offset], "d")
                || $this->getModeAuto($arrFilename[$offset], "z")
                || $this->getModeAuto($arrFilename[$offset], "c")
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
    private function getModeAuto(string $value, string $char) : bool
    {
        return is_numeric(str_replace($char, "", $value)) && substr_count($value, $char) == 1;
    }

    /**
     * @param string $file
     * @return array|null
     */
    private function getModeByFile(string $file) : ?stdClass
    {
        $res                                                        = null;
        $source                                                     = (object) pathinfo($file);

        $mode                                                       = $this->getModeByNoImg($source->basename);
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
        $arrDirname                                                 = explode(DIRECTORY_SEPARATOR, $this->pathinfo->dirname, 5);
        $widget                                                     = Resource::widget($arrDirname[2]);
        $prefix                                                     = (
            !empty($arrDirname[4])
            ? $arrDirname[4] . DIRECTORY_SEPARATOR
            : null
        );

        return $widget[$arrDirname[3]][$prefix . $resource_name] ?? null;
    }

    /**
     * @param string $resource_name
     * @return string|null
     */
    private function staticProcessAsset(string $resource_name) : ?string
    {
        $arrDirname                                                 = explode(DIRECTORY_SEPARATOR, $this->pathinfo->dirname, 3);
        $prefix                                                     = (
            !empty($arrDirname[2])
            ? $arrDirname[2] . DIRECTORY_SEPARATOR
            : null
        );

        return Resource::get($prefix . $resource_name, $arrDirname[1]) ?? self::getRealPathAsset($this->pathinfo->orig, Kernel::$Environment::getAssetDiskPath());
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
            if (!$this->isImage() && pathinfo($resource, PATHINFO_EXTENSION) != $this->pathinfo->extension) {
                return false;
            }
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
                if (!empty($this->mode) &&
                    !empty(Env::get("MEDIA_AUTO_RENDER_LIMIT")) &&
                    array_search($this->mode, explode(",", Env::get("MEDIA_AUTO_RENDER_LIMIT"))) === false
                ) {
                    Response::httpCode(404);
                }

                $final_file = $this->processFinalFile();
                if ($final_file) {
                    $this->readfile($final_file);
                }
            } elseif ($this->isImage()) {
                $final_file = $this->getIconPath("noimg", true);
                if ($final_file) {
                    $this->readfile($final_file, 404);
                }
            } elseif (($asset_disk_path = self::getRealPathAsset($this->pathinfo->orig, Kernel::$Environment::getAssetDiskPath()))) {
                $this->saveFromOriginal($asset_disk_path, $this->basepathCache() . $this->pathinfo->orig);
                $this->sendHeaders($asset_disk_path, $this->headers);
                readfile($asset_disk_path);
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
            if (!isset($this->pathinfo->extension)) {
                $this->pathinfo->extension                          = "";
            }
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
            } elseif (($cache_asset_disk_path = self::getRealPathAsset($this->pathinfo->orig, $this->basepathCache())) && !is_file($cache_asset_disk_path)) {
                $this->saveFromOriginal($this->basepath . $this->filesource, $cache_asset_disk_path);
                $final_file                                         = $cache_asset_disk_path;
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
            $this->readfile($final_file, $status);
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
                    if (!empty($arrWmk_file) && is_file($wmk_abs_file)) {
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
        return str_replace($this->filesource, "", Resource::image($this->source->filename));
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
     * @return string|null
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
        if (Kernel::useCache()) {
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
            $cCanvas->format 								            = Env::get("MEDIA_FORCE_FORMAT") ?: $params->format;

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
                if (!empty($params->word_type)) {
                    $cThumb->new_res_font["type"]                       = $params->word_type;
                }
                if (!empty($params->word_align)) {
                    $cThumb->new_res_font["align"]                      = $params->word_align;
                }
            }

            $cCanvas->addChild($cThumb);

            $final_file                                                 = $this->getFinalFile();

            FilemanagerFs::makeDir(dirname($final_file), $this->basepathCache());

            $cCanvas->process($final_file);
        }
    }

    /**
     * @return string|null
     */
    private function basepathCache() : ?string
    {
        return ($this->pathinfo->render == static::RENDER_ASSETS_PATH
            ? Dir::findCachePath(Constant::RESOURCE_CACHE_ASSETS)
            : Dir::findCachePath(Constant::RESOURCE_CACHE_THUMBS)
        );
    }

    /**
     * @param string $mode
     * @return stdClass|null
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
        if (empty($this->mode)) {
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
                if (Response::httpCode() >= 400 || !Kernel::useCache()) {
                    return $this->basepath . $this->filesource;
                }

                if (is_array($modeCurrent)) {
                    if (!Buffer::cacheIsValid($this->basepath . $this->filesource, $final_file_stored)) {
                        $this->createImage($modeCurrent);
                        Hook::handle(self::HOOK_ON_AFTER_CREATE, $final_file);
                    }
                } elseif (!$modeCurrent) {
                    if (!Buffer::cacheIsValid($this->basepath . $this->filesource, $final_file_stored)) {
                        $this->saveFromOriginal($this->basepath . $this->filesource, $final_file);
                    }
                } else {
                    $final_file                                     = null;
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
        if (Kernel::useCache()) {
            FilemanagerFs::makeDir($destination, $this->basepathCache());

            if ($this->pathinfo->render == static::RENDER_ASSETS_PATH) {
                if (!@copy($source, $destination)) {
                    throw new Exception("Copy Failed. Check read permission on: " . $source . " and if directory exist and have write permission on " . $destination, 500);
                }
            } else {
                if (is_writable($source) && !@link($source, $destination)) {
                    throw new Exception("Link Failed. Check write permission on: " . $source . " and if directory exist and have write permission on " . $destination, 500);
                }
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
            $icon_name                                              = $this->pathinfo->extension ?? $this->pathinfo->basename;
        }
        if (!$mode) {
            $mode                                                   = $this->getModeByNoImg($this->pathinfo->basename);
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
    private function readfile(string $final_file, int $code = null)
    {
        $this->headers["filename"]                                  = $this->pathinfo->basename;
        $this->headers["mimetype"]                                  = $this->getMimeByFilename($final_file);

        if ($code) {
            $this->headers["cache"]                                = "must-revalidate";

            Response::httpCode($code);
        }

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

        return ($mode ?: $file[1]);
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

    /**
     * @param string $relative_path
     * @param string|null $base_disk_path
     * @return string|null
     */
    private static function getRealPathAsset(string $relative_path, string $base_disk_path = null) : ?string
    {
        return ($base_disk_path
            ? realpath($base_disk_path . $relative_path) ?: null
            : null
        );
    }
}
