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
namespace ff\libs\security;

use ff\libs\util\Cookie;
use ff\libs\util\ServerManager;

/**
 * Class Discover
 * @package ff\libs\security
 */
class Discover
{
    use ServerManager;

    public static function device() : array
    { //todo: da scrivere bene
        $device 										= new mobileDetect();

        $res["name"] 						            = $device->isMobile();
        if ($res["name"]) {
            $res["type"] 					= "Mobile";
        } else {
            $res["name"] 					= $device->isTablet();
            if ($res["name"]) {
                $res["type"]				= "Tablet";
            } else {
                $res["type"] 				= "Desktop";
            }
        }

        return $res;
    }

    public static function browser() : array
    {
        return array(
            "name"  => "Chrome"
            , "ver" => "32"
        );

        /*
                $ua 										    = get_browser();

                return ($ua
                    ? array(
                        "name"  => $ua["browser"]
                        , "ver" => $ua["version"]
                    )
                    : null
                );*/
    }

    public static function platform() : string
    {
        return "windows";
        /*
                $ua 										    = get_browser();

                return $ua["platform"];
        */
    }

    /**
     * Parses a user agent string into its important parts
     * @param string|null $u_agent
     * @return array
     */
    public static function parseUserAgent(string $u_agent = null) : array
    {
        if (!$u_agent) {
            $u_agent = self::userAgent();
        }
        $platform = null;
        $browser  = null;
        $version  = null;
        $empty = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );
        if (!$u_agent) {
            return $empty;
        }
        if (preg_match('/\((.*?)\)/im', $u_agent, $parent_matches)) {
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iOS|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)
				(?:\ [^;]*)?
				(?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);
            $priority = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11' );
            $result['platform'] = array_unique($result['platform']);

            if (count($result['platform']) > 1) {
                $keys = array_intersect($priority, $result['platform']);
                $platform = (
                    empty($keys)
                    ? $result['platform'][0]
                    : reset($keys)
                );
            } elseif (isset($result['platform'][0])) {
                $platform = $result['platform'][0];
            }
        }
        if ($platform == 'linux-gnu' || $platform == 'X11') {
            $platform = 'Linux';
        } elseif ($platform == 'CrOS') {
            $platform = 'Chrome OS';
        }
        preg_match_all(
            '%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
				TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|UCBrowser|Puffin|OculusBrowser|SamsungBrowser|
				Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
				Valve\ Steam\ Tenfoot|
				NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
				(?:\)?;?)
				(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent,
            $result,
            PREG_PATTERN_ORDER
        );
        // If nothing matched, return null (to avoid undefined index errors)
        if (!isset($result['browser'][0]) || !isset($result['version'][0])) {
            if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result)) {
                return array( 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null );
            }
            return $empty;
        }
        if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $u_agent, $rv_result)) {
            $rv_result = $rv_result['version'];
        }
        $browser = $result['browser'][0];
        $version = $result['version'][0];
        $lowerBrowser = array_map('strtolower', $result['browser']);
        $find = function ($search, &$key, &$value = null) use ($lowerBrowser) {
            $search = (array)$search;
            foreach ($search as $val) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if ($xkey !== false) {
                    $value = $val;
                    $key   = $xkey;
                    return true;
                }
            }
            return false;
        };
        $key = 0;
        $val = '';
        if ($browser == 'Iceweasel' || strtolower($browser) == 'icecat') {
            $browser = 'Firefox';
        } elseif ($find('Playstation Vita', $key)) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } elseif ($find(array( 'Kindle Fire', 'Silk' ), $key, $val)) {
            $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif ($find('Kindle', $key, $platform)) {
            $browser = $result['browser'][$key];
            $version = $result['version'][$key];
        } elseif ($find('OPR', $key)) {
            $browser = 'Opera Next';
            $version = $result['version'][$key];
        } elseif ($find('Opera', $key, $browser)) {
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($find('Puffin', $key, $browser)) {
            $version = $result['version'][$key];
            if (strlen($version) > 3) {
                $part = substr($version, -2);
                if (ctype_upper($part)) {
                    $version = substr($version, 0, -2);
                    $flags = array( 'IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows' );
                    if (isset($flags[$part])) {
                        $platform = $flags[$part];
                    }
                }
            }
        } elseif ($find('YaBrowser', $key, $browser)) {
            $browser = 'Yandex';
            $version = $result['version'][$key];
        } elseif ($find(array( 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome' ), $key, $browser)) {
            $version = $result['version'][$key];
        } elseif ($rv_result && $find('Trident', $key)) {
            $browser = 'MSIE';
            $version = $rv_result;
        } elseif ($find('UCBrowser', $key)) {
            $browser = 'UC Browser';
            $version = $result['version'][$key];
        } elseif ($find('CriOS', $key)) {
            $browser = 'Chrome';
            $version = $result['version'][$key];
        } elseif ($browser == 'AppleWebKit') {
            if ($platform == 'Android') {
                // $key = 0;
                $browser = 'Android Browser';
            } elseif (strpos($platform, 'BB') === 0) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
                $browser = 'BlackBerry Browser';
            } else {
                $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
            }
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($pKey = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser']))) {
            $pKey = reset($pKey);
            $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $pKey);
            $browser  = 'NetFront';
        }

        return array( 'platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null );
    }

    /**
     * @param string|null $user_agent
     * @return array|null
     */
    public static function visitor(string $user_agent = null) : ?string
    {
        $visitor                        = null;
        if (!$user_agent) {
            $user_agent                 = self::userAgent();
        }

        if (!self::isCrawler($user_agent)) {
            $long_time = time() + (60 * 60 * 24 * 365 * 30);

            if (isset($_COOKIE["_ga"])) {
                $ga = explode(".", $_COOKIE["_ga"]);

                $visitor            = array(
                    "unique"        => $ga[2],
                    "created"       => $ga[3],
                    "last_update"   => $ga[3]
                );
            } elseif (isset($_COOKIE["__utma"])) {
                $utma = explode(".", $_COOKIE["__utma"]);

                $visitor            = array(
                    "unique"        => $utma[1],
                    "created"       => $utma[2],
                    "last_update"   => $utma[4]
                );
            } elseif (isset($_COOKIE["_uv"])) {
                $uv = explode(".", $_COOKIE["_uv"]);

                $visitor            = array(
                    "unique"        => $uv[0],
                    "created"       => $uv[1],
                    "last_update"   => $uv[2]
                );
                if ($visitor["last_update"] + (60 * 60 * 24) < time()) {
                    $visitor["last_update"] = time();

                    Cookie::create("_uv", implode(".", $visitor), $long_time);
                }
            } else {
                $access = explode("E", hexdec(md5(
                    self::remoteAddr()
                    . self::userAgent()
                )));

                $offset = (strlen($access[0]) - 9);
                $visitor            = array(
                    "unique"        => substr($access[0], $offset, 9),
                    "created"       => time(),
                    "last_update"   => time()
                );
                Cookie::create("_uv", implode(".", $visitor), $long_time);
            }
        }

        return $visitor["unique"] ?? null;
    }

    /**
     * @param string|null $user_agent
     * @return bool
     */
    public static function isCrawler(string $user_agent = null) : bool
    {
        $isCrawler = true;
        $crawlers = array(
            'Google'            => 'Google',
            'MSN'               => 'msnbot',
            'Rambler'           => 'Rambler',
            'Yahoo'             => 'Yahoo',
            'AbachoBOT'         => 'AbachoBOT',
            'accoona'           => 'Accoona',
            'AcoiRobot'         => 'AcoiRobot',
            'ASPSeek'           => 'ASPSeek',
            'CrocCrawler'       => 'CrocCrawler',
            'Dumbot'            => 'Dumbot',
            'FAST-WebCrawler'   => 'FAST-WebCrawler',
            'GeonaBot'          => 'GeonaBot',
            'Gigabot'           => 'Gigabot',
            'Lycos spider'      => 'Lycos',
            'MSRBOT'            => 'MSRBOT',
            'Altavista robot'   => 'Scooter',
            'AltaVista robot'   => 'Altavista',
            'ID-Search Bot'     => 'IDBot',
            'eStyle Bot'        => 'eStyle',
            'Scrubby robot'     => 'Scrubby',
            'Screaming SEO bot' => 'Screaming Frog',
            'GenericBot'        => 'bot',
            'GenericSEO'        => 'seo',
            'GenericCrawler'    => 'crawler'
        );

        if (!$user_agent) {
            $user_agent = self::userAgent();
        }
        if ($user_agent) {
            $crawlers_agents = implode("|", $crawlers);
            $isCrawler = (preg_match("/" . $crawlers_agents . "/i", $user_agent) > 0);
        }
        if (!$isCrawler) {
            $isCrawler = !(self::browser() && self::platform());
        }

        return $isCrawler;
    }
}
