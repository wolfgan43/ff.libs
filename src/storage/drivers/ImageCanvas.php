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

use phpformsframework\libs\Error;

class ImageCanvas
{
    const ERROR_BUCKET                      = "storage";

    public $cvs_max_dim_x					= null;
    public $cvs_max_dim_y					= null;
    public $cvs_res_background_color_hex 	= null;
    public $cvs_res_background_color_alpha = 0;
    public $cvs_res_transparent_color_hex 	= null;
    /**
     * @var string[jpg|png]
     */
    public $format							= "jpg";

    private $cvs_res 						= null;
    private $cvs_res_dim_x 					= null;
    private $cvs_res_dim_y 					= null;

    private $tmb_res						= array();

    public function __construct($dim_x = null, $dim_y = null)
    {
        $this->cvs_res_dim_x = $dim_x;
        $this->cvs_res_dim_y = $dim_y;
    }


    /**
     * @param ImageThumb $obj
     * @param int $dim_x_start
     * @param int $dim_y_start
     * @param int $z
     * @param string $method
     * @param null $dim_x
     * @param null $dim_y
     * @param null $dim_max_x
     * @param null $dim_max_y
     */
    public function addChild($obj, $z = 0, $dim_x_start = 0, $dim_y_start = 0, $method = "none", $dim_x = null, $dim_y = null, $dim_max_x = null, $dim_max_y = null)
    {
        $this->tmb_res[$z][]                            = array();
        $count_image                                    = count($this->tmb_res[$z]) - 1;

        if ($dim_max_x && $obj->new_res_max_x > $dim_max_x) {
            $obj->new_res_max_x = $dim_max_x;
        }
        if ($dim_max_y && $obj->new_res_max_y > $dim_max_y) {
            $obj->new_res_max_y = $dim_max_y;
        }

        $this->tmb_res[$z][$count_image]["obj"]			= $obj;
        $this->tmb_res[$z][$count_image]["dim_x_start"]	= $dim_x_start;
        $this->tmb_res[$z][$count_image]["dim_y_start"] = $dim_y_start;
        //todo: non sono gestite
        $this->tmb_res[$z][$count_image]["dim_x"] 		= $dim_x;
        $this->tmb_res[$z][$count_image]["dim_y"] 		= $dim_y;
        $this->tmb_res[$z][$count_image]["max_x"] 		= $dim_max_x;
        $this->tmb_res[$z][$count_image]["max_y"] 		= $dim_max_y;
        $this->tmb_res[$z][$count_image]["method"] 		= $method;
    }

    /**
     * @param array $ref
     * @return ImageRender
     */
    private function getRender($ref)
    {
        return $ref["obj"];
    }
    public function process($filename = null)
    {
        ksort($this->tmb_res);

        if ($this->cvs_res_dim_x === null || $this->cvs_res_dim_y === null) {
            $calc_x = ($this->cvs_res_dim_x === null ? true : false);
            $calc_y = ($this->cvs_res_dim_y === null ? true : false);
            
            foreach ($this->tmb_res as $zkey => $zvalue) {
                foreach ($this->tmb_res[$zkey] as $key => $value) {
                    $render = $this->getRender($this->tmb_res[$zkey][$key]);
                    $render_dim = $render->getFinalDim();
                    $render->pre_process();

                    if ($calc_x && $this->cvs_res_dim_x < $render_dim->x) {
                        $this->cvs_res_dim_x = $render_dim->x;
                    }
                    if ($calc_y && $this->cvs_res_dim_y < $render_dim->y) {
                        $this->cvs_res_dim_y = $render_dim->y;
                    }
                    $watermark = $render->getWatermark();
                    if (is_array($watermark) && count($watermark)) {
                        foreach ($watermark as $watermark_key => $watermark_value) {
                            $watermark[$watermark_key]->new_res_max_x = $render_dim->x;
                            $watermark[$watermark_key]->new_res_max_y = $render_dim->y;
                            $watermark[$watermark_key]->pre_process();
                        }
                    }
                }
                reset($this->tmb_res[$zkey]);
            }
            reset($this->tmb_res);
            
            if ($this->cvs_max_dim_x !== null && $this->cvs_res_dim_x > $this->cvs_max_dim_x) {
                $this->cvs_res_dim_x = $this->cvs_max_dim_x;
            }
            if ($this->cvs_max_dim_y !== null && $this->cvs_res_dim_y > $this->cvs_max_dim_y) {
                $this->cvs_res_dim_y = $this->cvs_max_dim_y;
            }
        }
        
        $this->cvs_res = @imagecreatetruecolor($this->cvs_res_dim_x, $this->cvs_res_dim_y);
        imagealphablending($this->cvs_res, true);
        imagesavealpha($this->cvs_res, true);
        
        if (is_resource($this->cvs_res) == 1) {
            $color_transparent = null;
            if (strlen($this->cvs_res_transparent_color_hex) == 6) {
                $color_transparent = imagecolorallocate(
                    $this->cvs_res,
                    hexdec(substr($this->cvs_res_transparent_color_hex, 0, 2)),
                    hexdec(substr($this->cvs_res_transparent_color_hex, 2, 2)),
                    hexdec(substr($this->cvs_res_transparent_color_hex, 4, 2))
                                                        );

                imagecolortransparent($this->cvs_res, $color_transparent);
            }
            
            if (strlen($this->cvs_res_background_color_hex) == 6) {
                if ($this->cvs_res_transparent_color_hex == $this->cvs_res_background_color_hex) {
                    $color_background = $color_transparent;
                } else {
                    $color_background = imagecolorallocatealpha(
                        $this->cvs_res,
                        hexdec(substr($this->cvs_res_background_color_hex, 0, 2)),
                        hexdec(substr($this->cvs_res_background_color_hex, 2, 2)),
                        hexdec(substr($this->cvs_res_background_color_hex, 4, 2)),
                        $this->cvs_res_background_color_alpha
                                                                );
                }
            } else {
                $color_background = imagecolorallocatealpha(
                    $this->cvs_res,
                    0,
                    0,
                    0,
                    $this->cvs_res_background_color_alpha
                                                            );
            }
            imagefill($this->cvs_res, 0, 0, $color_background);

            foreach ($this->tmb_res as $zkey => $zvalue) {
                foreach ($this->tmb_res[$zkey] as $key => $value) {
                    $render = $this->getRender($this->tmb_res[$zkey][$key]);
                    $render_dim = $render->getFinalDim();
                    $src_res = $render->process();
                    if ($src_res) {
                        imagecopy(
                            $this->cvs_res,
                            $src_res,
                            $this->tmb_res[$zkey][$key]["dim_x_start"],
                            $this->tmb_res[$zkey][$key]["dim_y_start"],
                            0,
                            0,
                            $render_dim->x,
                            $render_dim->y
                                            );

                        @imagedestroy($src_res);

                        $watermark = $render->getWatermark();
                        if (is_array($watermark) && count($watermark)) {
                            foreach ($watermark as $watermark_key => $watermark_value) {
                                $watermark_dim = $watermark[$watermark_key]->getFinalDim();
                                if (($this->cvs_res_dim_x - $watermark_dim->x) > 0) {
                                    $watermark_x_start = ($this->cvs_res_dim_x - $watermark_dim->x);
                                } else {
                                    $watermark_x_start = 0;
                                }
                                if (($this->cvs_res_dim_y - $watermark_dim->y) > 0) {
                                    $watermark_y_start = ($this->cvs_res_dim_y - $watermark_dim->y);
                                } else {
                                    $watermark_y_start = 0;
                                }

                                $src_res = $watermark[$watermark_key]->process();

                                imagecopy(
                                    $this->cvs_res,
                                    $src_res,
                                    $watermark_x_start,
                                    $watermark_y_start,
                                    0,
                                    0,
                                    $watermark_dim->x,
                                    $watermark_dim->y
                                                    );

                                @imagedestroy($src_res);
                            }
                        }
                    }
                }
                reset($this->tmb_res[$zkey]);
            }
            reset($this->tmb_res);
        }

        switch ($this->format) {
            case "jpg":
                if ($filename === null) {
                    header("Content-Type: image/jpg");
                }
                if (!is_writable(dirname($filename)) || imagejpeg($this->cvs_res, $filename) === false) {
                    Error::register("Permission Denied: " . $filename, static::ERROR_BUCKET);
                } else {
                    chmod($filename, 0664);
                }
                break;

            case "png":
            default:
                if ($filename === null) {
                    header("Content-Type: image/png");
                }

                if (!is_writable(dirname($filename)) || imagepng($this->cvs_res, $filename) === false) {
                    Error::register("Permission Denied: " . $filename, static::ERROR_BUCKET);
                } else {
                    chmod($filename, 0664);
                }
        }
    }
}