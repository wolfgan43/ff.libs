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

abstract class Render
{
	// PUBLIC
    public $new_res_max_x						= NULL;
    public $new_res_max_y						= NULL;

    public $new_res_frame_size					= 0;
    public $new_res_frame_color_hex			    = NULL;

    public $new_res_transparent_color_hex		= NULL;
    public $new_res_background_color_hex		= NULL;
    public $new_res_background_color_alpha		= 0;

	public $src_res_path                        = NULL;
	public $src_res_transparent_color_hex	    = NULL;
    public $src_res_alpha				        = 100;

    public $new_res_font 						= array(
													"caption" => ""
														, "color_hex" => NULL
														, "size" => 0
														, "type" => "times.ttf"
														, "align" => "center"
													);

    public $new_res_method						= "proportional";
    public $new_res_resize_when				    = "ever";
    public $new_res_crop_type					= "auto"; // può essere: width, height o auto
    public	$new_res_align						= "center";


	// PRIVATE
	private $new_res_dim_x						= 0;	// dimensioni finali immagine sorgente
    private $new_res_dim_y						= 0;
    private $new_res_dim_x_start				= 0;	// posizione finale immagine sorgente
    private $new_res_dim_y_start				= 0;
    private $new_res_dim_real_x					= NULL;	// dimensione finale immagine (tagliata se richiesto)
    private $new_res_dim_real_y					= NULL;

    private $src_res							= NULL;
    private $src_res_dim_x						= 0;	// dimensioni originali immagine sorgente
    private $src_res_dim_y						= 0;
    private $src_res_dim_x_start				= 0;	// porzione di immagine da ridimensionare
    private $src_res_dim_x_end					= 0;
    private $src_res_dim_y_start				= 0;
    private $src_res_dim_y_end					= 0;

    private $src_res_font_method                = "none";

    private $pre_processed                      = FALSE;
    private $watermark                          = array();
	protected abstract function load_image($src_res_path);

	function __construct($new_res_dim_real_x = NULL, $new_res_dim_real_y = NULL, $src_res = NULL)
	{
		if($new_res_dim_real_x !== NULL)
			$this->new_res_dim_real_x = $new_res_dim_real_x;

		if($new_res_dim_real_y !== NULL)
			$this->new_res_dim_real_y = $new_res_dim_real_y;

		if($src_res !== NULL && is_resource($src_res) === true)
			$this->src_res = $src_res;
	}

    /**
     * @param Thumb $Thumb
     */
    public function addWatermark($Thumb) {
	    $this->watermark[] = $Thumb;
    }

    /**
     * @return Thumb[]
     */
    public function getWatermark() {
        return $this->watermark;
    }

    /**
     * @return object
     */
    public function getFinalDim() {
        return (object) array(
            "x" => $this->new_res_dim_real_x
            , "y" => $this->new_res_dim_real_y
        );
    }

	public function pre_process()
	{
		if ($this->src_res === NULL)
			$this->src_res = $this->load_image($this->src_res_path);

		if($this->src_res) {
            $this->calc_dim();

            $this->pre_processed = true;
        }
	}

    private function calc_dim()
	{
		// Recupera le informazioni sulla dimensione della risorsa immagine caricata da file
		$this->src_res_dim_x = imagesx($this->src_res);
		$this->src_res_dim_y = imagesy($this->src_res);

		// Determina se è necessario calcolare la dimensione finale dell'immagine
		$calc_x = ($this->new_res_dim_real_x === NULL ? true : false);
		$calc_y = ($this->new_res_dim_real_y === NULL ? true : false);

		$res_method = $this->new_res_method;

		$bigger = false;
		$check = false;

		if ($this->new_res_dim_real_x !== NULL)
		{
			$check = true;
			if ($this->src_res_dim_x > $this->new_res_dim_real_x) {
                $bigger = true;
            }
		}
		elseif($this->new_res_max_x !== NULL)
		{
			$check = true;
			if ($this->src_res_dim_x > $this->new_res_max_x) {
                $bigger = true;
            }
		}

		if ($this->new_res_dim_real_y !== NULL)
		{
			$check = true;
			if ($this->src_res_dim_y > $this->new_res_dim_real_y) {
                $bigger = true;
            }
		}
		elseif($this->new_res_max_y !== NULL)
		{
			$check = true;
			if ($this->src_res_dim_y > $this->new_res_max_y) {
                $bigger = true;
            }
		}

		if ($check)
		{
			if ($this->new_res_resize_when == "smaller" && $bigger)
				$res_method = "none";
			elseif ($this->new_res_resize_when == "bigger" && !$bigger)
				$res_method = "none";
		}

		switch ($res_method)
		{
			case "crop":
				$max_x = NULL;
				$max_y = NULL;

				if ($this->new_res_max_x !== NULL)
					$max_x = $this->new_res_max_x;
				elseif ($this->new_res_dim_real_x !== NULL)
					$max_x = $this->new_res_dim_real_x;

				if ($this->new_res_max_y !== NULL)
					$max_y = $this->new_res_max_y;
				elseif ($this->new_res_dim_real_y !== NULL)
					$max_y = $this->new_res_dim_real_y;

				switch ($this->new_res_crop_type)
				{
					case "width":
						break;

					case "height":
						break;

					case "auto":
					default:
						//die("asd");
						$this->new_res_dim_x = $max_x;
						$this->new_res_dim_y = ceil($this->src_res_dim_y * $max_x / $this->src_res_dim_x);
						if ($this->new_res_dim_y < $max_y)
						{
							$this->new_res_dim_y = $max_y;
							$this->new_res_dim_x = ceil($this->src_res_dim_x * $max_y / $this->src_res_dim_y);
						}

						if ($this->src_res_dim_y > $this->src_res_dim_x)
							$this->new_res_align = "top-middle";
/*							if (($this->new_res_dim_y = ceil($max_x * $this->src_res_dim_y / $this->src_res_dim_x)) >= $this->new_res_max_y)
						{
							$this->new_res_dim_x = $max_x;
						}
						else
						{
							$this->new_res_dim_y = $max_y;
							$this->new_res_dim_x = ceil($this->src_res_dim_x * $max_y / $this->new_res_dim_y);
						}
						*/
						//die("asd1 " . $this->new_res_dim_x . " - asd2 " . $this->new_res_dim_y);
				}
				break;

			case "none":
				$this->new_res_dim_x = $this->src_res_dim_x;
				$this->new_res_dim_y = $this->src_res_dim_y;
				if ($calc_x)
					$this->new_res_dim_real_x = $this->src_res_dim_x;
				if ($calc_y)
					$this->new_res_dim_real_y = $this->src_res_dim_y;

				// Determina le dimensioni finali dell'immagine nel caso siano state impostate delle soglie
				if ($this->new_res_max_x !== NULL && $this->new_res_dim_real_x > $this->new_res_max_x)
				{
					$this->new_res_dim_real_x = $this->new_res_max_x;
				}

				if ($this->new_res_max_y !== NULL && $this->new_res_dim_real_y > $this->new_res_max_y)
				{
					$this->new_res_dim_real_y = $this->new_res_max_y;
				}
				break;

			case "stretch":
				if ($this->new_res_dim_real_x !== NULL)
				{
					$this->new_res_dim_x = $this->new_res_dim_real_x;
				}
				elseif ($this->new_res_max_x !== NULL)
				{
					if ($this->src_res_dim_x > $this->new_res_max_x)
					{
						$this->new_res_dim_x = $this->new_res_max_x;
					}
					else
					{
						$this->new_res_dim_x = $this->src_res_dim_x;
					}
					$this->new_res_dim_real_x = $this->new_res_dim_x;
				}
				else
				{
					$this->new_res_dim_x = $this->src_res_dim_x;
					$this->new_res_dim_real_x = $this->new_res_dim_x;
				}

				if ($this->new_res_dim_real_y !== NULL)
				{
					$this->new_res_dim_y = $this->new_res_dim_real_y;
				}
				elseif ($this->new_res_max_y !== NULL)
				{
					if ($this->src_res_dim_y > $this->new_res_max_y)
					{
						$this->new_res_dim_y = $this->new_res_max_y;
					}
					else
					{
						$this->new_res_dim_y = $this->src_res_dim_y;
					}
					$this->new_res_dim_real_y = $this->new_res_dim_y;
				}
				else
				{
					$this->new_res_dim_y = $this->src_res_dim_y;
					$this->new_res_dim_real_y = $this->new_res_dim_y;
				}
				break;

			case "proportional":
			default:
				$max_x = NULL;
				$max_y = NULL;

				if ($this->new_res_max_x !== NULL)
					$max_x = $this->new_res_max_x;
				elseif ($this->new_res_dim_real_x !== NULL)
					$max_x = $this->new_res_dim_real_x;

				if ($this->new_res_max_y !== NULL)
					$max_y = $this->new_res_max_y;
				elseif ($this->new_res_dim_real_y !== NULL)
					$max_y = $this->new_res_dim_real_y;

				if ($max_x === NULL && $max_y === NULL)
				{
					$this->new_res_dim_x = $this->src_res_dim_x;
					$this->new_res_dim_y = $this->src_res_dim_y;
					$this->new_res_dim_real_x = $this->new_res_dim_x;
					$this->new_res_dim_real_y = $this->new_res_dim_y;
				}
				elseif($max_x !== NULL && $max_y === NULL)
				{
					$this->new_res_dim_x = $max_x;
					$this->new_res_dim_y = ceil($this->src_res_dim_y * $max_x / $this->src_res_dim_x);
					$this->new_res_dim_real_x = $this->new_res_dim_x;
					$this->new_res_dim_real_y = $this->new_res_dim_y;
				}
				elseif($max_x === NULL && $max_y !== NULL)
				{
					$this->new_res_dim_y = $max_y;
					$this->new_res_dim_x = ceil($this->src_res_dim_x * $max_y / $this->src_res_dim_y);
					$this->new_res_dim_real_x = $this->new_res_dim_x;
					$this->new_res_dim_real_y = $this->new_res_dim_y;
				}
				else
				{
					if ($this->src_res_dim_x > $this->src_res_dim_y)
					{
						$this->new_res_dim_x = $max_x;
						$this->new_res_dim_y = ceil($this->src_res_dim_y * $max_x / $this->src_res_dim_x);
						if ($this->new_res_dim_y > $max_y)
						{
							$this->new_res_dim_y = $max_y;
							$this->new_res_dim_x = ceil($this->src_res_dim_x * $max_y / $this->src_res_dim_y);
						}
					}
					else
					{
						$this->new_res_dim_y = $max_y;
						$this->new_res_dim_x = ceil($this->src_res_dim_x * $max_y / $this->src_res_dim_y);
						if ($this->new_res_dim_x > $max_x)
						{
							$this->new_res_dim_x = $max_x;
							$this->new_res_dim_y = ceil($this->src_res_dim_y * $max_x / $this->src_res_dim_x);
						}
					}
				}

				if ($calc_x)
					$this->new_res_dim_real_x = $this->new_res_dim_x;
				if ($calc_y)
					$this->new_res_dim_real_y = $this->new_res_dim_y;
		}

		$this->src_res_dim_x_start	= 0;
		$this->src_res_dim_x_end 	= $this->src_res_dim_x;

		$this->src_res_dim_y_start	= 0;
		$this->src_res_dim_y_end	= $this->src_res_dim_y;

		// calculate alignment
		switch ($this->new_res_align)
		{
			case "top-left":
				$this->new_res_dim_x_start = 0;
				$this->new_res_dim_y_start = 0;
				break;
			case "top-middle":
				$this->new_res_dim_x_start = ceil(($this->new_res_dim_real_x - ($this->new_res_dim_x)) / 2);
				$this->new_res_dim_y_start = 0;
				break;
			case "top-right":
				$this->new_res_dim_x_start = $this->new_res_dim_real_x - ($this->new_res_dim_x);
				$this->new_res_dim_y_start = 0;
				break;
			case "bottom-left":
				$this->new_res_dim_x_start = 0;
				$this->new_res_dim_y_start = $this->new_res_dim_real_y - ($this->new_res_dim_y);
				break;
			case "bottom-middle":
				$this->new_res_dim_x_start = ceil(($this->new_res_dim_real_x - ($this->new_res_dim_x)) / 2);
				$this->new_res_dim_y_start = $this->new_res_dim_real_y - ($this->new_res_dim_y);
				break;
			case "bottom-right":
				$this->new_res_dim_x_start = $this->new_res_dim_real_x - ($this->new_res_dim_x);
				$this->new_res_dim_y_start = $this->new_res_dim_real_y - ($this->new_res_dim_y);
				break;
			case "middle-left":
				$this->new_res_dim_x_start = 0;
				$this->new_res_dim_y_start = ceil(($this->new_res_dim_real_y - ($this->new_res_dim_y)) / 2);
				break;
			case "middle-right":
				$this->new_res_dim_x_start = $this->new_res_dim_real_x - ($this->new_res_dim_x);
				$this->new_res_dim_y_start = ceil(($this->new_res_dim_real_y - ($this->new_res_dim_y)) / 2);
				break;
			case "center":
			default:
				$this->new_res_dim_x_start = ceil(($this->new_res_dim_real_x - ($this->new_res_dim_x)) / 2);
				$this->new_res_dim_y_start = ceil(($this->new_res_dim_real_y - ($this->new_res_dim_y)) / 2);
				break;
		}
        $this->new_res_dim_x_start = $this->new_res_dim_x_start + $this->new_res_frame_size;
        $this->new_res_dim_y_start = $this->new_res_dim_y_start + $this->new_res_frame_size;
        $this->new_res_dim_x = $this->new_res_dim_x - ($this->new_res_frame_size * 2);
        $this->new_res_dim_y = $this->new_res_dim_y - ($this->new_res_frame_size * 2);
	}

    private function drawText($new_res)
	{
		if (!strlen($this->new_res_font["caption"]))
			return;

		$color_font = imagecolorallocate($new_res
											, hexdec(substr($this->new_res_font["color_hex"], 0, 2))
											, hexdec(substr($this->new_res_font["color_hex"], 2, 2))
											, hexdec(substr($this->new_res_font["color_hex"], 4, 2))
											);

		$src_res_font_size 			= $this->new_res_font["size"];
		$src_res_print_x_start		= 0;
		$src_res_print_y_start		= 0;
		$src_res_print_x_end		= $this->new_res_dim_real_x;
		$src_res_print_y_end		= $this->new_res_dim_real_y;

		$real_w = $src_res_print_x_end - $src_res_print_x_start;
		$real_h = $src_res_print_y_end - $src_res_print_y_start;

		do
		{
			$bbox = imagettfbbox($this->new_res_font["size"], 0, __DIR__ . "/fonts/" . $this->new_res_font["type"], $this->new_res_font["caption"]);

			$text_width_top = abs($bbox[6]) + abs($bbox[4]);
			$text_width_bottom = abs($bbox[0]) + abs($bbox[2]);
			$text_width = $text_width_top > $text_width_bottom
							? $text_width_top
							: $text_width_bottom;

			$text_height_left = abs($bbox[7]) + abs($bbox[1]);
			$text_height_right = abs($bbox[5]) + abs($bbox[3]);
			$text_height = $text_height_left > $text_height_right
							? $text_height_left
							: $text_height_right;

			switch ($this->new_res_font["align"])
			{
				case "top-left":
					$src_res_font_x_start = $src_res_print_x_start;
					$src_res_font_y_start = $src_res_print_y_start + abs($bbox[7]);
					break;
				case "top-middle":
					$src_res_font_x_start = ceil($src_res_print_x_start + ($real_w - $text_width) / 2);
					$src_res_font_y_start = $src_res_print_y_start + abs($bbox[7]);
					break;
				case "top-right":
					$src_res_font_x_start = $src_res_print_x_start + ($real_w - $text_width);
					$src_res_font_y_start = $src_res_print_y_start + abs($bbox[7]);
					break;
				case "bottom-left":
					$src_res_font_x_start = $src_res_print_x_start;
					$src_res_font_y_start = $src_res_print_y_start + ($real_h - $text_height + abs($bbox[7]));
					break;
				case "bottom-middle":
					$src_res_font_x_start = ceil($src_res_print_x_start + ($real_w - $text_width) / 2);
					$src_res_font_y_start = $src_res_print_y_start + ($real_h - $text_height + abs($bbox[7]));
					break;
				case "bottom-right":
					$src_res_font_x_start = $src_res_print_x_start + ($real_w - $text_width);
					$src_res_font_y_start = $src_res_print_y_start + ($real_h - $text_height + abs($bbox[7]));
					break;
				case "middle-left":
					$src_res_font_x_start = $src_res_print_x_start;
					$src_res_font_y_start = ceil($src_res_print_y_start + ($real_h - $text_height + abs($bbox[7]) + abs($bbox[1])) / 2);
					break;
				case "middle-right":
					$src_res_font_x_start = $src_res_print_x_start + ($real_w - $text_width);
					$src_res_font_y_start = ceil($src_res_print_y_start + ($real_h - $text_height + abs($bbox[7]) + abs($bbox[1])) / 2);
					break;
				case "center":
				default:
					$src_res_font_x_start = ceil($src_res_print_x_start + ($real_w - $text_width) / 2);
					$src_res_font_y_start = ceil($src_res_print_y_start + ($real_h - $text_height + abs($bbox[7]) + abs($bbox[1])) / 2);
			}
		}
		while (($this->src_res_font_method != "none") && (($text_width >= $src_res_print_x_end) && ($src_res_font_size = $src_res_font_size - 1)));

		imagettftext($new_res
						, $src_res_font_size
						, 0
						, $src_res_font_x_start
						, $src_res_font_y_start
						, $color_font
						, __DIR__ . "/fonts/" . $this->new_res_font["type"]
						, $this->new_res_font["caption"]
					);
	}

    public function process()
	{
		if ($this->src_res === NULL && !$this->pre_processed) {
            $this->pre_process();
        }

		if(!$this->src_res) {
            return null;
        }

		$tmp_res = imagecreatetruecolor(
											intval($this->new_res_dim_x), intval($this->new_res_dim_y)
										);
		imagealphablending($tmp_res, true);
		imagesavealpha($tmp_res, true);

		$tmp_transparent = null;
		if(strlen($this->src_res_transparent_color_hex) == 6)
		{
			$tmp_color_transparent = imagecolorallocate($tmp_res
													, hexdec(substr($this->src_res_transparent_color_hex, 0, 2))
													, hexdec(substr($this->src_res_transparent_color_hex, 2, 2))
													, hexdec(substr($this->src_res_transparent_color_hex, 4, 2))
													);

			$tmp_transparent = imagecolortransparent($tmp_res, $tmp_color_transparent);
		}
		else
		{
            $tmp_color_transparent = imagecolortransparent($this->src_res);
			if ($tmp_color_transparent != -1)
				$tmp_transparent = imagecolortransparent($tmp_res, $tmp_color_transparent);
		}

		if (strlen($this->new_res_background_color_hex) == 6)
		{
			if ($this->new_res_transparent_color_hex == $this->new_res_background_color_hex)
				$color_background = $tmp_transparent;
			else
				$color_background = imagecolorallocatealpha($tmp_res
																, hexdec(substr($this->new_res_background_color_hex, 0, 2))
																, hexdec(substr($this->new_res_background_color_hex, 2, 2))
																, hexdec(substr($this->new_res_background_color_hex, 4, 2))
																, $this->new_res_background_color_alpha
															);
		}
		else
		{
			$color_background = imagecolorallocatealpha(
															$tmp_res
															, 0
															, 0
															, 0
															, $this->new_res_background_color_alpha
														);
		}

		imagefill($tmp_res, 0, 0, $color_background);

		imagecopyresampled($tmp_res
							, $this->src_res
							, 0
							, 0
							, 0
							, 0
							, $this->new_res_dim_x
							, $this->new_res_dim_y
							, $this->src_res_dim_x_end
							, $this->src_res_dim_y_end
							);

		@imagedestroy($this->src_res);

		$new_res = @imagecreatetruecolor($this->new_res_dim_real_x, $this->new_res_dim_real_y);
		imagealphablending($new_res, true);
		imagesavealpha($new_res, true);

		$color_transparent = null;
		if(strlen($this->new_res_transparent_color_hex) == 6)
		{
			$color_transparent = imagecolorallocate($new_res
													, hexdec(substr($this->new_res_transparent_color_hex, 0, 2))
													, hexdec(substr($this->new_res_transparent_color_hex, 2, 2))
													, hexdec(substr($this->new_res_transparent_color_hex, 4, 2))
													);
			imagecolortransparent($new_res, $color_transparent);
		}

		if (strlen($this->new_res_background_color_hex) == 6)
		{
			if ($this->new_res_transparent_color_hex == $this->new_res_background_color_hex)
				$color_background = $color_transparent;
			else
				$color_background = imagecolorallocatealpha($new_res
																, hexdec(substr($this->new_res_background_color_hex, 0, 2))
																, hexdec(substr($this->new_res_background_color_hex, 2, 2))
																, hexdec(substr($this->new_res_background_color_hex, 4, 2))
																, $this->new_res_background_color_alpha
															);
		}
		else
		{
			$color_background = imagecolorallocatealpha(
															$new_res
															, 0
															, 0
															, 0
															, $this->new_res_background_color_alpha
														);
		}
		imagefill($new_res, 0, 0, $color_background);

		if($this->new_res_frame_size && strlen($this->new_res_frame_color_hex) == 6)
		{
			$color_frame = imagecolorallocate($new_res
												, hexdec(substr($this->new_res_frame_color_hex, 0, 2))
												, hexdec(substr($this->new_res_frame_color_hex, 2, 2))
												, hexdec(substr($this->new_res_frame_color_hex, 4, 2))
												);

			imagefilledrectangle($new_res
									, 0
									, 0
									, $this->new_res_dim_x + ($this->new_res_frame_size * 2) - 1
									, $this->new_res_dim_y + ($this->new_res_frame_size * 2) - 1
									, $color_frame
								);
           /* if($this->new_res_frame_size > 1)
			{
				imagerectangle($new_res
										, $this->new_res_dim_x_start + $intPrintAreaLeft + $this->new_res_frame_size -1
										, $this->new_res_dim_y_start + $intPrintAreaTop + $this->new_res_frame_size -1
										, $this->new_res_dim_x_start + $this->new_res_dim_x  + $intPrintAreaLeft + $this->new_res_frame_size
										, $this->new_res_dim_y_start + $this->new_res_dim_y  + $intPrintAreaTop + $this->new_res_frame_size
										, $color_frame
									);
				if($this->new_res_frame_size > 2)
				{
					imagefill($new_res
									, $this->new_res_dim_x_start + $intPrintAreaLeft + floor($this->new_res_frame_size / 2)
									, $this->new_res_dim_y_start + $intPrintAreaTop + floor($this->new_res_frame_size / 2)
									, $color_frame);
				}
			}	*/
		}

		imagecopy($new_res
							, $tmp_res
							, $this->new_res_dim_x_start
							, $this->new_res_dim_y_start
							, $this->src_res_dim_x_start
							, $this->src_res_dim_y_start
							, $this->new_res_dim_x
							, $this->new_res_dim_y
							);

		@imagedestroy($tmp_res);

		$this->drawText($new_res);

		return $new_res;
	}
}