<?php
/**
 *   VGallery: CMS based on FormsFramework
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
 * @package VGallery
 * @subpackage core
 * @author Alessandro Stucchi <wolfgan@gmail.com>
 * @copyright Copyright (c) 2004, Alessandro Stucchi
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @link https://bitbucket.org/cmsff/vgallery
 */

class Validator
{
    const TYPE                                              = "validator";

    /**
     * cmsSchemaorg constructor.
     * @param $cms
     */
    public function __construct()
    {

    }

    public function isEmail($value, $rule = null) {
        $res                                                            = false;
        switch($rule) {
            default:
                $regex                                              = '/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';
                $res                                                = preg_match($regex, $value);
        }

        return $res;
    }

    public function isTel($value, $rule = null) {
        $res                                                            = false;
        switch($rule) {
            default:
                $res                                                = is_numeric(ltrim(str_replace(array(" ", ".", ",", "-"), array(""), $value), "+"));
        }

        return $res;
    }

    public function invalidUsername($value, $rule = null) {
        $res                                                            = false;

        if($value) {
            switch($rule) {
                default:
            }
        } else {
            $res                                                        = "username empty";
        }


        return $res;
    }

    public function invalidPassword($value, $rule = null) {
        $error                                                          = array();
        $res                                                            = false;
        if($value) {
            switch($rule) {
                case "kerberos":
                    if (strlen($value) < 8)                                 $error[] = "Password too short!";
                    if (!preg_match("#[0-9]+#", $value))             $error[] = "Password must include at least one number!";
                    if (!preg_match("#[a-z]+#", $value))             $error[] = "Password must include at least one letter!";
                    if (!preg_match("#[A-Z]+#", $value))             $error[] = "Password must include at least one upper letter!";
                    if (!preg_match("#[^a-zA-Z0-9]+#", $value)) $error[] = "Password must include at least one Special Character!";

                    /*$pspell_link                                            = pspell_new(vgCommon::LANG_CODE_TINY); //todo: non funziona il controllo
                    $word                                                   = preg_replace("#[^a-zA-Z]+#", "", $value);

                    if (!pspell_check($pspell_link, $word))                 $error[] = "Password must be impersonal!";
                    */
                    if(count($error))                                       $res = implode(", ", $error);
                    break;
                default:
                    if (strlen($value) < 8)                                 $error[] = "Password too short!";
                    if (!preg_match("#[0-9]+#", $value))             $error[] = "Password must include at least one number!";
                    if (!preg_match("#[a-z]+#", $value))             $error[] = "Password must include at least one letter!";
                    if (!preg_match("#[A-Z]+#", $value))             $error[] = "Password must include at least one upper letter!";

                    if(count($error))                                       $res = implode(", ", $error);
            }
        } else {
            $res                                                            = "password empty";
        }
        return $res;
    }

}