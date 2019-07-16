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

namespace phpformsframework\libs\storage\drivers;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\Media;

class ImageThumb extends ImageRender
{
    const IMAGES_DISK_PATH                      = Constant::LIBS_FF_DISK_PATH . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "images";
    public $icon_path 			                = null;
    private $icons = array(
                "text/plain" 					=> "txt.png",
                "text/html"                     => "html.png",
                "application/pdf" 				=> "[CONVERT]",
                "application/x-shockwave-flash" => "swf.png",
                "application/msword"			=> "doc.png",
                "application/octet-stream"		=> "exe.png",
                "video/quicktime"				=> "video.png",
                "audio/mpeg3" 					=> "audio.png",
                "audio/mpeg" 					=> "audio.png",
                "audio/x-wav" 					=> "audio.png",
                "application/mspowerpoint"		=> "ppt.png",
                //"application/octet-stream"		=> "psd.png",
                "application/excel"				=> "xls.png",
                "application/x-compressed" 		=> "archive.png",
                "application/vnd.openxmlformats-officedocument.presentationml.presentation" 	=> "ppt.png",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" 			=> "xls.png",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document" 		=> "doc.png",
                "image/jpeg" 					=> "[IMAGEPATH]",
                "image/gif" 					=> "[IMAGEPATH]",
                "image/png" 					=> "[IMAGEPATH]",
                "directory" 					=> "dir.png",
                "unknown" 						=> "unknown.png",
                "empty" 						=> "empty.png",
                "error"  						=> "error.png",
                "error-img"  				    => "noimg.png"
            );

    /**
     * Carica un immagine da disco per la creazione del thumbnail, basandosi sul mime-type
     * E' in grado di convertire i file PDF usando l'utility convert di linux
     * E' anche possibile associare icone a mime-type predefiniti
     * @param String $src_res_path Il percorso dell'immagine da caricare
     * @return resource La risorsa immagine caricata
     */
    protected function load_image($src_res_path)
    {
        $mime = Media::getMimeTypeByFilename($src_res_path);
        if (is_dir($src_res_path)) {
            $src_res_path_tmp = $this->icons["directory"];
        } elseif (is_file($src_res_path)) {
            $src_res_path_tmp = (isset($this->icons[$mime]) ? $this->icons[$mime] : $this->icons["unknown"]);
        } elseif (!strlen($src_res_path)) {
            $src_res_path_tmp = $this->icons["empty"];
        } else {
            $src_res_path_tmp = null;
        }
        $error_icon = "error";
        switch ($src_res_path_tmp) {
            case "[IMAGEPATH]":
                $src_res_path_tmp = $src_res_path;
                $error_icon = "error-img";
                break;

            case "[CONVERT]":
                $src_res_path_tmp = $src_res_path . "-conversion.png";
                exec('convert -antialias -density 300 -depth 24 "' . $src_res_path . '"[0] "' . $src_res_path_tmp . '"');
                if (!is_file($src_res_path_tmp) && is_file($src_res_path . "-conversion-0.png")) {
                    exec('mv "' . $src_res_path . '-conversion-0.png" "' . $src_res_path_tmp . '"');
                    exec('rm -f "' . $src_res_path . '-conversion-*.png"');
                }
                if (!is_file($src_res_path_tmp)) {
                    $src_res_path_tmp = $this->get_template_dir("pdf.png");
                }
                break;
            default:
                $src_res_path_tmp = $this->get_template_dir($src_res_path_tmp);
                if (!is_file($src_res_path_tmp)) {
                    $src_res_path_tmp = $this->get_template_dir($this->icons["error"]);
                }
        }

        $src_res = null;
        if ($src_res_path_tmp) {
            $mime = Media::getMimeTypeByFilename($src_res_path_tmp);
            if (!function_exists(str_replace("/", "", $mime))) {
                $src_res_path_tmp = $this->get_template_dir($this->icons["unknown"]);
            }
            switch ($mime) {
                case "image/jpeg":
                    $src_res = @imagecreatefromjpeg($src_res_path_tmp);
                    break;
                case "image/png":
                    $src_res = @imagecreatefrompng($src_res_path_tmp);
                    break;
                case "image/gif":
                    $src_res = @imagecreatefromgif($src_res_path_tmp);
                    break;
                default:
                    Error::registerWarning("source file: mime not supported", static::ERROR_BUCKET);
            }
        }

        if (!$src_res) {
            $src_res_path_tmp = $this->get_template_dir($this->icons[$error_icon]);
            $mime = Media::getMimeTypeByFilename($src_res_path_tmp);

            switch ($mime) {
                case "image/jpeg":
                    $src_res = @imagecreatefromjpeg($src_res_path_tmp);
                    break;
                case "image/png":
                    $src_res = @imagecreatefrompng($src_res_path_tmp);
                    break;
                case "image/gif":
                    $src_res = @imagecreatefromgif($src_res_path_tmp);
                    break;
                default:
                    Error::registerWarning("source icon: mime not supported", static::ERROR_BUCKET);
            }
        }

        if ($src_res) {
            imagealphablending($src_res, true);
            imagesavealpha($src_res, true);
            
            return $src_res;
        } else {
            return null;
        }
    }
        
    /**
     * Recupera il percorso del tema corrente
     * Utile per caricare le icone per i mime-type predefiniti
     * @param string $file
     * @return String il percorso del tema corrente
     */
    private function get_template_dir($file)
    {
        if ($this->icon_path && is_file($this->icon_path . DIRECTORY_SEPARATOR . $file)) {
            return $this->icon_path . DIRECTORY_SEPARATOR . $file;
        } elseif (is_file($this::IMAGES_DISK_PATH . DIRECTORY_SEPARATOR . $file)) {
            return $this::IMAGES_DISK_PATH . DIRECTORY_SEPARATOR . $file;
        } else {
            return false;
        }
    }
}
