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

if (!defined("FF_DISK_PATH"))	                            define("FF_DISK_PATH", dirname(dirname(__DIR__)));
if (!defined("__TOP_DIR__"))	                            define("__TOP_DIR__", FF_DISK_PATH);
if (!defined("__PRJ_DIR__"))	                            define("__PRJ_DIR__", FF_DISK_PATH);
if (!defined("FF_UPDIR"))                                 define("FF_UPDIR", "/uploads");


if (!defined("FF_THEME_DIR"))	                            define("FF_THEME_DIR", "/themes");
if (!defined("CM_DEFAULT_THEME"))	                        define("CM_DEFAULT_THEME", "responsive");
if (!defined("FRONTEND_THEME"))                           define("FRONTEND_THEME", "site");

if (!defined("CM_SHOWFILES_OPTIMIZE"))		                define("CM_SHOWFILES_OPTIMIZE", true);
if (!defined("CM_SHOWFILES_THUMB_NAME"))	                define("CM_SHOWFILES_THUMB_NAME", ".thumbs");

if (!defined("CM_CACHE_PATH"))	                            define("CM_CACHE_PATH", "/cache");
if (!defined("CM_CACHE_DISK_PATH"))	                    define("CM_CACHE_DISK_PATH", FF_DISK_PATH . "/cache");

if (!defined("CM_SHOWFILES"))	                            define("CM_SHOWFILES", FF_SITE_PATH . "/media"); // /cm/showfiles
if (!defined("CM_TABLE_PREFIX"))	                        define("CM_TABLE_PREFIX", "cm_");

define("FF_DISABLE_CACHE", defined("DEBUG_MODE") && isset($_REQUEST["__nocache__"]));


/**
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
 */
class ffMedia {
    const STRICT                                                    = false;

    const DISK_PATH                                                 = FF_DISK_PATH;
    const SITE_PATH                                                 = FF_SITE_PATH;
    const TOP_DIR                                                   = __TOP_DIR__;
    const PRJ_DIR                                                   = __PRJ_DIR__;
    const UPLOADS_DIR                                               = FF_UPDIR;
    const CACHE_DIR                                                 = CM_CACHE_PATH;
    const MEDIA_PATH                                                = self::SITE_PATH . "/media";
    const MODIFY_PATH                                               = self::SITE_PATH . "/restricted/media/modify";



    const DISABLE_CACHE                                             = FF_DISABLE_CACHE;

    const THEME_DIR                                                 = FF_THEME_DIR;
    const THEME                                                     = FRONTEND_THEME;
    const THEME_DEFAULT                                             = CM_DEFAULT_THEME;
    const SHOWFILES                                                 = CM_SHOWFILES;

    const DB_TABLE_MODES                                            = CM_TABLE_PREFIX . "showfiles_modes";

    const OPTIMIZE                                                  = CM_SHOWFILES_OPTIMIZE;

    const STORING_BASE_NAME                                         = CM_SHOWFILES_THUMB_NAME;
    const STORING_BASE_PATH                                         = CM_CACHE_DISK_PATH . "/" . ffMedia::STORING_BASE_NAME;
    const STORING_SITE_PATH                                         = FF_SITE_PATH . ffMedia::CACHE_DIR . "/" . ffMedia::STORING_BASE_NAME;

    const THEME_BASE_PATH                                           = ffMedia::DISK_PATH . ffMedia::THEME_DIR;
    const STATIC_DEFAULT_PATH                                       = ffMedia::TOP_DIR . ffMedia::THEME_DIR . "/" . ffMedia::THEME_DEFAULT . "/images";
    const STATIC_BASE_PATH                                          = ffMedia::THEME_BASE_PATH . "/" . ffMedia::THEME . "/images";
    const UPLOAD_BASE_PATH                                          = FF_DISK_UPDIR;

    const ICON_DEFAULT_PATH                                         = __DIR__ . "/ffImage/icons";
    const ICON_BASE_PATH                                            = ffMedia::THEME_BASE_PATH . "/" . ffMedia::THEME . "/icons";


    const MIMETYPE                                                  = array(
                                                                        "3dm" => "x-world/x-3dmf"
                                                                        , "3dmf" => "x-world/x-3dmf"
                                                                        , "a" => "application/octet-stream"
                                                                        , "aab" => "application/x-authorware-bin"
                                                                        , "aam" => "application/x-authorware-map"
                                                                        , "aas" => "application/x-authorware-seg"
                                                                        , "abc" => "text/vnd.abc"
                                                                        , "acgi" => "text/html"
                                                                        , "afl" => "video/animaflex"
                                                                        , "ai" => "application/postscript"
                                                                        , "aif" => "audio/aiff"
                                                                        , "aifc" => "audio/aiff"
                                                                        , "aiff" => "audio/aiff"
                                                                        , "aim" => "application/x-aim"
                                                                        , "aip" => "text/x-audiosoft-intra"
                                                                        , "ani" => "application/x-navi-animation"
                                                                        , "aos" => "application/x-nokia-9000-communicator-add-on-software"
                                                                        , "aps" => "application/mime"
                                                                        , "arc" => "application/octet-stream"
                                                                        , "arj" => "application/arj"
                                                                        , "art" => "image/x-jg"
                                                                        , "asf" => "video/x-ms-asf"
                                                                        , "asm" => "text/x-asm"
                                                                        , "asp" => "text/asp"
                                                                        , "asx" => "application/x-mplayer2"
                                                                        , "au" => "audio/basic"
                                                                        , "avi" => "application/x-troff-msvideo"
                                                                        , "avs" => "video/avs-video"
                                                                        , "bcpio" => "application/x-bcpio"
                                                                        , "bin" => "application/mac-binary"
                                                                        , "bm" => "image/bmp"
                                                                        , "bmp" => "image/bmp"
                                                                        , "boo" => "application/book"
                                                                        , "book" => "application/book"
                                                                        , "boz" => "application/x-bzip2"
                                                                        , "bsh" => "application/x-bsh"
                                                                        , "bz" => "application/x-bzip"
                                                                        , "bz2" => "application/x-bzip2"
                                                                        , "c" => "text/plain"
                                                                        , "c++" => "text/plain"
                                                                        , "cat" => "application/vnd.ms-pki.seccat"
                                                                        , "cc" => "text/plain"
                                                                        , "ccad" => "application/clariscad"
                                                                        , "cco" => "application/x-cocoa"
                                                                        , "cdf" => "application/cdf"
                                                                        , "cer" => "application/pkix-cert"
                                                                        , "cha" => "application/x-chat"
                                                                        , "chat" => "application/x-chat"
                                                                        , "class" => "application/java"
                                                                        , "com" => "application/octet-stream"
                                                                        , "conf" => "text/plain"
                                                                        , "cpio" => "application/x-cpio"
                                                                        , "cpp" => "text/x-c"
                                                                        , "cpt" => "application/mac-compactpro"
                                                                        , "crl" => "application/pkcs-crl"
                                                                        , "crt" => "application/pkix-cert"
                                                                        , "csh" => "application/x-csh"
                                                                        , "css" => "text/css"
                                                                        , "cxx" => "text/plain"
                                                                        , "dcr" => "application/x-director"
                                                                        , "deepv" => "application/x-deepv"
                                                                        , "def" => "text/plain"
                                                                        , "der" => "application/x-x509-ca-cert"
                                                                        , "dif" => "video/x-dv"
                                                                        , "dir" => "application/x-director"
                                                                        , "dl" => "video/dl"
                                                                        , "doc" => "application/msword"
                                                                        , "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                                        , "dot" => "application/msword"
                                                                        , "dotx" =>	"application/vnd.openxmlformats-officedocument.wordprocessingml.template"
                                                                        , "dp" => "application/commonground"
                                                                        , "drw" => "application/drafting"
                                                                        , "dump" => "application/octet-stream"
                                                                        , "dv" => "video/x-dv"
                                                                        , "dvi" => "application/x-dvi"
                                                                        , "dwf" => "drawing/x-dwf"
                                                                        , "dwg" => "application/acad"
                                                                        , "dxf" => "application/dxf"
                                                                        , "dxr" => "application/x-director"
                                                                        , "el" => "text/x-script.elisp"
                                                                        , "elc" => "application/x-bytecode.elisp"
                                                                        , "env" => "application/x-envoy"
                                                                        , "eps" => "application/postscript"
                                                                        , "es" => "application/x-esrehber"
                                                                        , "etx" => "text/x-setext"
                                                                        , "evy" => "application/envoy"
                                                                        , "exe" => "application/octet-stream"
                                                                        , "f" => "text/plain"
                                                                        , "f77" => "text/x-fortran"
                                                                        , "f90" => "text/plain"
                                                                        , "fdf" => "application/vnd.fdf"
                                                                        , "fif" => "application/fractals"
                                                                        , "fli" => "video/fli"
                                                                        , "flo" => "image/florian"
                                                                        , "flx" => "text/vnd.fmi.flexstor"
                                                                        , "fmf" => "video/x-atomic3d-feature"
                                                                        , "for" => "text/plain"
                                                                        , "fpx" => "image/vnd.fpx"
                                                                        , "frl" => "application/freeloader"
                                                                        , "funk" => "audio/make"
                                                                        , "g" => "text/plain"
                                                                        , "g3" => "image/g3fax"
                                                                        , "gif" => "image/gif"
                                                                        , "gl" => "video/gl"
                                                                        , "gsd" => "audio/x-gsm"
                                                                        , "gsm" => "audio/x-gsm"
                                                                        , "gsp" => "application/x-gsp"
                                                                        , "gss" => "application/x-gss"
                                                                        , "gtar" => "application/x-gtar"
                                                                        , "gz" => "application/x-compressed"
                                                                        , "gzip" => "application/x-gzip"
                                                                        , "h" => "text/plain"
                                                                        , "hdf" => "application/x-hdf"
                                                                        , "help" => "application/x-helpfile"
                                                                        , "hgl" => "application/vnd.hp-hpgl"
                                                                        , "hh" => "text/plain"
                                                                        , "hlb" => "text/x-script"
                                                                        , "hlp" => "application/hlp"
                                                                        , "hpg" => "application/vnd.hp-hpgl"
                                                                        , "hpgl" => "application/vnd.hp-hpgl"
                                                                        , "hqx" => "application/binhex"
                                                                        , "hta" => "application/hta"
                                                                        , "htc" => "text/x-component"
                                                                        , "htm" => "text/html"
                                                                        , "html" => "text/html"
                                                                        , "htmls" => "text/html"
                                                                        , "htt" => "text/webviewhtml"
                                                                        , "htx" => "text/html"
                                                                        , "ice" => "x-conference/x-cooltalk"
                                                                        , "ico" => "image/x-icon"
                                                                        , "idc" => "text/plain"
                                                                        , "ief" => "image/ief"
                                                                        , "iefs" => "image/ief"
                                                                        , "iges" => "application/iges"
                                                                        , "igs" => "application/iges"
                                                                        , "ima" => "application/x-ima"
                                                                        , "imap" => "application/x-httpd-imap"
                                                                        , "inf" => "application/inf"
                                                                        , "ins" => "application/x-internett-signup"
                                                                        , "ip" => "application/x-ip2"
                                                                        , "isu" => "video/x-isvideo"
                                                                        , "it" => "audio/it"
                                                                        , "iv" => "application/x-inventor"
                                                                        , "ivr" => "i-world/i-vrml"
                                                                        , "ivy" => "application/x-livescreen"
                                                                        , "jam" => "audio/x-jam"
                                                                        , "jav" => "text/plain"
                                                                        , "java" => "text/plain"
                                                                        , "jcm" => "application/x-java-commerce"
                                                                        , "jfif" => "image/jpeg"
                                                                        , "jfif-tbnl" => "image/jpeg"
                                                                        , "jpe" => "image/jpeg"
                                                                        , "jpeg" => "image/jpeg"
                                                                        , "jpg" => "image/jpeg"
                                                                        , "jps" => "image/x-jps"
                                                                        , "js" => "application/x-javascript"
                                                                        , "jut" => "image/jutvision"
                                                                        , "kar" => "audio/midi"
                                                                        , "ksh" => "application/x-ksh"
                                                                        , "la" => "audio/nspaudio"
                                                                        , "lam" => "audio/x-liveaudio"
                                                                        , "latex" => "application/x-latex"
                                                                        , "lha" => "application/lha"
                                                                        , "lhx" => "application/octet-stream"
                                                                        , "list" => "text/plain"
                                                                        , "lma" => "audio/nspaudio"
                                                                        , "log" => "text/plain"
                                                                        , "lsp" => "application/x-lisp"
                                                                        , "lst" => "text/plain"
                                                                        , "lsx" => "text/x-la-asf"
                                                                        , "ltx" => "application/x-latex"
                                                                        , "lzh" => "application/octet-stream"
                                                                        , "lzx" => "application/lzx"
                                                                        , "m" => "text/plain"
                                                                        , "m1v" => "video/mpeg"
                                                                        , "m2a" => "audio/mpeg"
                                                                        , "m2v" => "video/mpeg"
                                                                        , "m3u" => "audio/x-mpequrl"
                                                                        , "man" => "application/x-troff-man"
                                                                        , "map" => "application/x-navimap"
                                                                        , "mar" => "text/plain"
                                                                        , "mbd" => "application/mbedlet"
                                                                        , "mc$" => "application/x-magic-cap-package-1.0"
                                                                        , "mcd" => "application/mcad"
                                                                        , "mcf" => "image/vasa"
                                                                        , "mcp" => "application/netmc"
                                                                        , "me" => "application/x-troff-me"
                                                                        , "mht" => "message/rfc822"
                                                                        , "mhtml" => "message/rfc822"
                                                                        , "mid" => "application/x-midi"
                                                                        , "midi" => "application/x-midi"
                                                                        , "mif" => "application/x-frame"
                                                                        , "mime" => "message/rfc822"
                                                                        , "mjf" => "audio/x-vnd.audioexplosion.mjuicemediafile"
                                                                        , "mjpg" => "video/x-motion-jpeg"
                                                                        , "mm" => "application/base64"
                                                                        , "mme" => "application/base64"
                                                                        , "mod" => "audio/mod"
                                                                        , "moov" => "video/quicktime"
                                                                        , "mov" => "video/quicktime"
                                                                        , "movie" => "video/x-sgi-movie"
                                                                        , "mp2" => "audio/mpeg"
                                                                        , "mp3" => "audio/mpeg3"
                                                                        , "mpa" => "audio/mpeg"
                                                                        , "mpc" => "application/x-project"
                                                                        , "mpe" => "video/mpeg"
                                                                        , "mpeg" => "video/mpeg"
                                                                        , "mpg" => "audio/mpeg"
                                                                        , "mpga" => "audio/mpeg"
                                                                        , "mpp" => "application/vnd.ms-project"
                                                                        , "mpt" => "application/x-project"
                                                                        , "mpv" => "application/x-project"
                                                                        , "mpx" => "application/x-project"
                                                                        , "mrc" => "application/marc"
                                                                        , "ms" => "application/x-troff-ms"
                                                                        , "mv" => "video/x-sgi-movie"
                                                                        , "my" => "audio/make"
                                                                        , "mzz" => "application/x-vnd.audioexplosion.mzz"
                                                                        , "nap" => "image/naplps"
                                                                        , "naplps" => "image/naplps"
                                                                        , "nc" => "application/x-netcdf"
                                                                        , "ncm" => "application/vnd.nokia.configuration-message"
                                                                        , "nif" => "image/x-niff"
                                                                        , "niff" => "image/x-niff"
                                                                        , "nix" => "application/x-mix-transfer"
                                                                        , "nsc" => "application/x-conference"
                                                                        , "nvd" => "application/x-navidoc"
                                                                        , "o" => "application/octet-stream"
                                                                        , "oda" => "application/oda"
                                                                        , "omc" => "application/x-omc"
                                                                        , "omcd" => "application/x-omcdatamaker"
                                                                        , "omcr" => "application/x-omcregerator"
                                                                        , "p" => "text/x-pascal"
                                                                        , "p10" => "application/pkcs10"
                                                                        , "p12" => "application/pkcs-12"
                                                                        , "p7a" => "application/x-pkcs7-signature"
                                                                        , "p7c" => "application/pkcs7-mime"
                                                                        , "p7m" => "application/pkcs7-mime"
                                                                        , "p7r" => "application/x-pkcs7-certreqresp"
                                                                        , "p7s" => "application/pkcs7-signature"
                                                                        , "part" => "application/pro_eng"
                                                                        , "pas" => "text/pascal"
                                                                        , "pbm" => "image/x-portable-bitmap"
                                                                        , "pcl" => "application/vnd.hp-pcl"
                                                                        , "pct" => "image/x-pict"
                                                                        , "pcx" => "image/x-pcx"
                                                                        , "pdb" => "chemical/x-pdb"
                                                                        , "pdf" => "application/pdf"
                                                                        , "pfunk" => "audio/make"
                                                                        , "pgm" => "image/x-portable-graymap"
                                                                        , "pic" => "image/pict"
                                                                        , "pict" => "image/pict"
                                                                        , "pkg" => "application/x-newton-compatible-pkg"
                                                                        , "pko" => "application/vnd.ms-pki.pko"
                                                                        , "pl" => "text/plain"
                                                                        , "plx" => "application/x-pixclscript"
                                                                        , "pm" => "image/x-xpixmap"
                                                                        , "pm4" => "application/x-pagemaker"
                                                                        , "pm5" => "application/x-pagemaker"
                                                                        , "png" => "image/png"
                                                                        , "pnm" => "application/x-portable-anymap"
                                                                        , "pot" => "application/mspowerpoint"
                                                                        , "potx" => "application/vnd.openxmlformats-officedocument.presentationml.template"
                                                                        , "pov" => "model/x-pov"
                                                                        , "ppa" => "application/vnd.ms-powerpoint"
                                                                        , "ppm" => "image/x-portable-pixmap"
                                                                        , "pps" => "application/mspowerpoint"
                                                                        , "ppsx" => "application/vnd.openxmlformats-officedocument.presentationml.slideshow"
                                                                        , "ppt" => "application/mspowerpoint"
                                                                        , "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation"
                                                                        , "ppz" => "application/mspowerpoint"
                                                                        , "pre" => "application/x-freelance"
                                                                        , "prt" => "application/pro_eng"
                                                                        , "ps" => "application/postscript"
                                                                        , "psd" => "application/octet-stream"
                                                                        , "pvu" => "paleovu/x-pv"
                                                                        , "pwz" => "application/vnd.ms-powerpoint"
                                                                        , "py" => "text/x-script.phyton"
                                                                        , "pyc" => "applicaiton/x-bytecode.python"
                                                                        , "qcp" => "audio/vnd.qcelp"
                                                                        , "qd3" => "x-world/x-3dmf"
                                                                        , "qd3d" => "x-world/x-3dmf"
                                                                        , "qif" => "image/x-quicktime"
                                                                        , "qt" => "video/quicktime"
                                                                        , "qtc" => "video/x-qtc"
                                                                        , "qti" => "image/x-quicktime"
                                                                        , "qtif" => "image/x-quicktime"
                                                                        , "ra" => "audio/x-pn-realaudio"
                                                                        , "ram" => "audio/x-pn-realaudio"
                                                                        , "rar" => "application/x-rar-compressed"
                                                                        , "ras" => "application/x-cmu-raster"
                                                                        , "rast" => "image/cmu-raster"
                                                                        , "rexx" => "text/x-script.rexx"
                                                                        , "rf" => "image/vnd.rn-realflash"
                                                                        , "rgb" => "image/x-rgb"
                                                                        , "rm" => "application/vnd.rn-realmedia"
                                                                        , "rmi" => "audio/mid"
                                                                        , "rmm" => "audio/x-pn-realaudio"
                                                                        , "rmp" => "audio/x-pn-realaudio"
                                                                        , "rng" => "application/ringing-tones"
                                                                        , "rnx" => "application/vnd.rn-realplayer"
                                                                        , "roff" => "application/x-troff"
                                                                        , "rp" => "image/vnd.rn-realpix"
                                                                        , "rpm" => "audio/x-pn-realaudio-plugin"
                                                                        , "rt" => "text/richtext"
                                                                        , "rtf" => "application/rtf"
                                                                        , "rtx" => "application/rtf"
                                                                        , "rv" => "video/vnd.rn-realvideo"
                                                                        , "s" => "text/x-asm"
                                                                        , "s3m" => "audio/s3m"
                                                                        , "saveme" => "application/octet-stream"
                                                                        , "sbk" => "application/x-tbook"
                                                                        , "scm" => "application/x-lotusscreencam"
                                                                        , "sdml" => "text/plain"
                                                                        , "sdp" => "application/sdp"
                                                                        , "sdr" => "application/sounder"
                                                                        , "sea" => "application/sea"
                                                                        , "set" => "application/set"
                                                                        , "sgm" => "text/sgml"
                                                                        , "sgml" => "text/sgml"
                                                                        , "sh" => "application/x-bsh"
                                                                        , "shar" => "application/x-bsh"
                                                                        , "shtml" => "text/html"
                                                                        , "sid" => "audio/x-psid"
                                                                        , "sit" => "application/x-sit"
                                                                        , "skd" => "application/x-koan"
                                                                        , "skm" => "application/x-koan"
                                                                        , "skp" => "application/x-koan"
                                                                        , "skt" => "application/x-koan"
                                                                        , "sl" => "application/x-seelogo"
                                                                        , "smi" => "application/smil"
                                                                        , "smil" => "application/smil"
                                                                        , "snd" => "audio/basic"
                                                                        , "sol" => "application/solids"
                                                                        , "spc" => "application/x-pkcs7-certificates"
                                                                        , "spl" => "application/futuresplash"
                                                                        , "spr" => "application/x-sprite"
                                                                        , "sprite" => "application/x-sprite"
                                                                        , "src" => "application/x-wais-source"
                                                                        , "ssi" => "text/x-server-parsed-html"
                                                                        , "ssm" => "application/streamingmedia"
                                                                        , "sst" => "application/vnd.ms-pki.certstore"
                                                                        , "step" => "application/step"
                                                                        , "stl" => "application/sla"
                                                                        , "stp" => "application/step"
                                                                        , "sv4cpio" => "application/x-sv4cpio"
                                                                        , "sv4crc" => "application/x-sv4crc"
                                                                        , "svf" => "image/vnd.dwg"
                                                                        , "svr" => "application/x-world"
                                                                        , "swf" => "application/x-shockwave-flash"
                                                                        , "t" => "application/x-troff"
                                                                        , "talk" => "text/x-speech"
                                                                        , "tar" => "application/x-tar"
                                                                        , "tbk" => "application/toolbook"
                                                                        , "tcl" => "application/x-tcl"
                                                                        , "tcsh" => "text/x-script.tcsh"
                                                                        , "tex" => "application/x-tex"
                                                                        , "texi" => "application/x-texinfo"
                                                                        , "texinfo" => "application/x-texinfo"
                                                                        , "text" => "application/plain"
                                                                        , "tgz" => "application/gnutar"
                                                                        , "tif" => "image/tiff"
                                                                        , "tiff" => "image/tiff"
                                                                        , "tr" => "application/x-troff"
                                                                        , "tsi" => "audio/tsp-audio"
                                                                        , "tsp" => "application/dsptype"
                                                                        , "tsv" => "text/tab-separated-values"
                                                                        , "turbot" => "image/florian"
                                                                        , "txt" => "text/plain"
                                                                        , "uil" => "text/x-uil"
                                                                        , "uni" => "text/uri-list"
                                                                        , "unis" => "text/uri-list"
                                                                        , "unv" => "application/i-deas"
                                                                        , "uri" => "text/uri-list"
                                                                        , "uris" => "text/uri-list"
                                                                        , "ustar" => "application/x-ustar"
                                                                        , "uu" => "application/octet-stream"
                                                                        , "uue" => "text/x-uuencode"
                                                                        , "vcd" => "application/x-cdlink"
                                                                        , "vcs" => "text/x-vcalendar"
                                                                        , "vda" => "application/vda"
                                                                        , "vdo" => "video/vdo"
                                                                        , "vew" => "application/groupwise"
                                                                        , "viv" => "video/vivo"
                                                                        , "vivo" => "video/vivo"
                                                                        , "vmd" => "application/vocaltec-media-desc"
                                                                        , "vmf" => "application/vocaltec-media-file"
                                                                        , "voc" => "audio/voc"
                                                                        , "vos" => "video/vosaic"
                                                                        , "vox" => "audio/voxware"
                                                                        , "vqe" => "audio/x-twinvq-plugin"
                                                                        , "vqf" => "audio/x-twinvq"
                                                                        , "vql" => "audio/x-twinvq-plugin"
                                                                        , "vrml" => "application/x-vrml"
                                                                        , "vrt" => "x-world/x-vrt"
                                                                        , "vsd" => "application/x-visio"
                                                                        , "vst" => "application/x-visio"
                                                                        , "vsw" => "application/x-visio"
                                                                        , "w60" => "application/wordperfect6.0"
                                                                        , "w61" => "application/wordperfect6.1"
                                                                        , "w6w" => "application/msword"
                                                                        , "wav" => "audio/x-wav"
                                                                        , "wb1" => "application/x-qpro"
                                                                        , "wbmp" => "image/vnd.wap.wbmp"
                                                                        , "web" => "application/vnd.xara"
                                                                        , "wiz" => "application/msword"
                                                                        , "wk1" => "application/x-123"
                                                                        , "wmf" => "windows/metafile"
                                                                        , "wml" => "text/vnd.wap.wml"
                                                                        , "wmlc" => "application/vnd.wap.wmlc"
                                                                        , "wmls" => "text/vnd.wap.wmlscript"
                                                                        , "wmlsc" => "application/vnd.wap.wmlscriptc"
                                                                        , "word" => "application/msword"
                                                                        , "wp" => "application/wordperfect"
                                                                        , "wp5" => "application/wordperfect"
                                                                        , "wp6" => "application/wordperfect"
                                                                        , "wpd" => "application/wordperfect"
                                                                        , "wq1" => "application/x-lotus"
                                                                        , "wri" => "application/mswrite"
                                                                        , "wrl" => "application/x-world"
                                                                        , "wrz" => "model/vrml"
                                                                        , "wsc" => "text/scriplet"
                                                                        , "wsrc" => "application/x-wais-source"
                                                                        , "wtk" => "application/x-wintalk"
                                                                        , "xbm" => "image/x-xbitmap"
                                                                        , "xdr" => "video/x-amt-demorun"
                                                                        , "xgz" => "xgl/drawing"
                                                                        , "xif" => "image/vnd.xiff"
                                                                        , "xl" => "application/excel"
                                                                        , "xla" => "application/excel"
                                                                        , "xlb" => "application/excel"
                                                                        , "xlc" => "application/excel"
                                                                        , "xld" => "application/excel"
                                                                        , "xlk" => "application/excel"
                                                                        , "xll" => "application/excel"
                                                                        , "xlm" => "application/excel"
                                                                        , "xls" => "application/excel"
                                                                        , "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                                                        , "xlt" => "application/excel"
                                                                        , "xltx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.template"
                                                                        , "xlv" => "application/excel"
                                                                        , "xlw" => "application/excel"
                                                                        , "xm" => "audio/xm"
                                                                        , "xml" => "application/xml"
                                                                        , "xmz" => "xgl/movie"
                                                                        , "xpix" => "application/x-vnd.ls-xpix"
                                                                        , "xpm" => "image/x-xpixmap"
                                                                        , "x-png" => "image/png"
                                                                        , "xsr" => "video/x-amt-showrun"
                                                                        , "xwd" => "image/x-xwd"
                                                                        , "xyz" => "chemical/x-pdb"
                                                                        , "z" => "application/x-compress"
                                                                        , "zip" => "application/x-compressed"
                                                                        , "zoo" => "application/octet-stream"
                                                                        , "zsh" => "text/x-script.zsh"
                                                                        , "eot" => "application/vnd.ms-fontobject"
                                                                        , "ttf" => "application/x-font-ttf"
                                                                        , "otf" => "application/octet-stream"
                                                                        , "woff" => "application/x-font-woff"
                                                                        , "svg" => "image/svg+xml"
                                                                        , "rss" => "application/rss+xml"
                                                                        , "json" => "application/json"
                                                                        );

    static $singleton                                               = null;

    private $basepath                                               = null;
    private $pathinfo                                               = null;

    private $modes                                                  = null;

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

    public static function getInstance($pathinfo = null)
    {
        if (self::$singleton === null) {
            self::$singleton                                        = new ffMedia($pathinfo);
        } else {
            self::$singleton->setPathInfo($pathinfo);
        }

        return self::$singleton;
    }

    public static function getIcon($name, $mode = null) {
        $icon = new ffMedia(false);
        $icon->setNoImg($mode, $name);
        $icon->renderNoImg($icon->processFinalFile(true));

        //deve renderizzare l'icona
        //da fare con la gestione delle iconde di ffImafge
    }

    public static function getInfo($file) {
        return self::getModeByFile($file);
    }

    public static function getUrl($file, $mode = null, $key = null) {
        $file                       = str_replace(array(
                                            self::DISK_PATH . self::UPLOADS_DIR . "/"
                                            , self::UPLOADS_DIR . "/"
                                            , ffMedia::CACHE_DIR . "/"
                                        )
                                        , "/"
                                        , $file
                                    );

        $arrFile                    = pathinfo($file);
        if(substr($arrFile["dirname"], 0, 1) !== "/") {
            $arrFile["dirname"]     = "/";
            $arrFile["filename"]    = "";
            $arrFile["extension"]   = "png";

        }
        if(!$arrFile["extension"]) {
            $arrFile["dirname"]     = "/";
            $arrFile["filename"]    = "unknown";
            $arrFile["extension"]   = "png";
        }

        switch($arrFile["extension"]) {
            case "svg";
                $mode               = null;
                $showfiles          = self::SITE_PATH;
                break;
            case "jpg";
            case "jpeg";
            case "png";
            case "gif";
            default:
                $showfiles          = (strpos($arrFile["dirname"], self::THEME_DIR) === 0
                                        ? self::MEDIA_PATH
                                        : $showfiles      = self::SHOWFILES
                                    );
        }

        $dirfilename                = $showfiles . ($arrFile["dirname"] == "/" ? "" : $arrFile["dirname"]) . "/" . $arrFile["filename"];
        $url                        = $dirfilename . ($arrFile["filename"] && $mode ? "-" : "") . $mode . "." . $arrFile["extension"];
        $pathinfo                   = array(
            "url"                   => $url
            , "abs_url"             => (strpos($url, "://") === false
                                        ? "http" . ($_SERVER["HTTPS"] ? "s": "") . "://" . $_SERVER["HTTP_HOST"] . $url
                                        : $url
                                    )
            , "extension"           => $arrFile["extension"]
            , "file"                => $dirfilename
            , "mode"                => $mode
        );

        return ($key
            ? $pathinfo[$key]
            : $pathinfo
        );

    }


    public static function sendHeaders($file, $params = null) {
        if(is_array($file) && $params === null) {
            $params = $file;
            $file = null;
        }

        //header_remove();
        $days                       = 7;
        $hours                      = 1;

        $keep_alive			        = $params["keep_alive"]		    ? $params["keep_alive"]			: false;
        $max_age				    = $params["max_age"]            ? $params["max_age"]            : null;
        $expires				    = $params["expires"]            ? $params["expires"]            : null;
        $compress			        = $params["compress"]           ? $params["compress"]           : false;
        $cache					    = $params["cache"]			    ? $params["cache"]				: "public";
        $disposition			    = $params["disposition"]		? $params["disposition"]		: "inline";
        $filename			        = $params["filename"]           ? $params["filename"]           : null;
        $mimetype			        = $params["mimetype"]		    ? $params["mimetype"]			: null;
        $size				        = $params["size"]               ? $params["size"]               : null;
        $etag				        = $params["etag"]				? $params["etag"]				: true;

        if($file) {
            if(!$mimetype)              { $mimetype     = self::getMimeTypeByFilename($file); }
            if(!$filename)              { $filename     = basename($file); }
            if(!$size && !$compress)    { $size         = filesize($file); }

            if ($etag === true)         { $etag         = md5($file . filemtime($file)); }

        } elseif($etag === true) {
            $etag = null;
        }

        if($size)                   { header("Content-Length: " . $size); }
        if(strlen($etag))           { header("ETag: " . $etag); }

        if($mimetype) {
            $content_type = $mimetype;
            if ($mimetype == "text/css" || $mimetype == "application/x-javascript") {
                header("Vary: Accept-Encoding");
            } elseif ($mimetype == "text/html") {
                $content_type .= "; charset=UTF-8";
                header("Vary: Accept-Encoding");
            } elseif (strpos($mimetype, "image/") === 0) {
                $days = 365;
                $hours = 24;
            }

            header("Content-type: $content_type");
        }

        if($disposition) {
            $content_disposition = $disposition;
            if ($filename)
                $content_disposition .= "; filename=" . rawurlencode($filename);

            header("Content-Disposition: " . $content_disposition);
        }


        if($keep_alive)
            header("Connection: Keep-Alive");

        if($compress) {
            header("Content-encoding: " . ($compress === true ? "gzip" : $compress));
        }

        switch($cache) {
            case "no-store":
                header('Cache-Control: no-store');
                header("Pragma: no-cache");
                break;
            case "no-cache":
                header('Cache-Control: no-cache');
                header("Pragma: no-cache");
                break;
            case "must-revalidate":
                $expires = time() - 1;
                $exp_gmt = gmdate("D, d M Y H:i:s", $expires) . " GMT";

                header("Expires: $exp_gmt");
                header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                header("Pragma: no-cache");
                break;
            case "public":
            default:
                if($expires === false && $max_age === false)
                {
                    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                    header("Pragma: no-cache");
                } else {
                    if ($expires !== false && $expires !== null) {
                        if ($expires < 0) {
                            $expires = time() - $expires;
                        }
                        $exp_gmt = gmdate("D, d M Y H:i:s", $expires) . " GMT";
                        header("Expires: $exp_gmt");
                    }

                    /*if ($expires !== false)
                    {
                        if ($expires === null)
                        {
                            //$expires = filemtime($file) + (60 * 60 * 24 * 7);
                            //$expires = time() + (60 * 60 * 24 * $days);
                        }
                        elseif ($expires < 0)
                        {
                            $expires = time() - $expires;
                        }
                        $exp_gmt = gmdate("D, d M Y H:i:s", $expires) . " GMT";
                        header("Expires: $exp_gmt");
                    }*/

                    if ($max_age !== false) {
                        $mod_gmt = gmdate("D, d M Y H:i:s", filemtime($file)) . " GMT";
                        header("Last-Modified: $mod_gmt");

                        if ($max_age === null) {
                            //$max_age = 60 * 60 * $hours;
                            $max_age = 60 * 60 * 24 * $days;
                            header("Cache-Control: public, max-age=$max_age");
                        }
                        else {
                            header("Cache-Control: public, max-age=$max_age");
                        }
                    }
                }

                header("Pragma: !invalid");
        }
    }
    public static function getFileOptimized($filename) {
        if(strpos($filename, ".min.") === false) {
            $arrFilename                                            = pathinfo($filename);
            $filename_min                                           = $arrFilename["dirname"] . "/" . $arrFilename["filename"] . ".min." . $arrFilename["extension"];
            if(!is_file($filename_min) || filemtime($filename) > filemtime($filename_min)) {
                if(!ffMedia::optimize($filename, array("wait" => true, "filename_min" => $filename_min)));
                    $filename_min                                   = $filename;
            }
        } else {
            $filename_min                                           = $filename;
        }

        return $filename_min;
    }
    public static function optimize($filename, $params = null) {
        if(!self::OPTIMIZE)                                         return;

        $filename_min                                               = ($params["filename_min"]
                                                                        ? $params["filename_min"]
                                                                        : $filename
                                                                    );

        $optiBin                                                    = array(
                                                                        "image/jpeg"                    => array(
                                                                                                            "convert"       => array( //https://www.imagemagick.org/script/index.php
                                                                                                                "bin"       => "convert"
                                                                                                                , "cmd" 	=> 'convert ' . $filename . ' -strip ' . $filename // -quality 85
                                                                                                            )
                                                                                                            , "JpegTran"    => array( //http://jpegclub.org/jpegtran/
                                                                                                                "bin"       => "jpegtran"
                                                                                                                , "cmd" 	=> 'jpegtran -optimize -progressive -copy none ' . $filename
                                                                                                            )
                                                                                                            , "JpegOptim"   => array( //https://github.com/tjko/jpegoptim
                                                                                                                "bin"       => "jpegoptim"
                                                                                                                , "cmd" 	=> 'jpegoptim --strip-all --all-progressive ' . $filename
                                                                                                            )
                                                                                                        )
                                                                        , "image/png"                   => array(
                                                                                                            "convert"       => array( //https://www.imagemagick.org/script/index.php
                                                                                                                "bin"       => "convert"
                                                                                                                , "cmd" 	=> 'convert ' . $filename . ' -strip ' . $filename
                                                                                                            )
                                                                                                            , "OptiPng"     => array( //http://optipng.sourceforge.net/
                                                                                                                "bin"       => "optipng"
                                                                                                                , "cmd" 	=> 'optipng -i0 -o2 ' . $filename
                                                                                                            )
                                                                                                            , "PngOut"      => array(
                                                                                                                "bin"       => "pngout"
                                                                                                                , "cmd" 	=> 'pngout -s0 -q -y ' . $filename
                                                                                                            )
                                                                                                        )
                                                                        , "image/gif"                   => array(
                                                                                                            "convert"       => array( //https://www.imagemagick.org/script/index.php
                                                                                                                "bin"       => "convert"
                                                                                                                , "cmd" 	=> 'convert ' . $filename . ' -strip ' . $filename
                                                                                                            )
                                                                                                        )
                                                                        , "application/x-javascript"    => array(
                                                                                                            "uglifyjs"      => array( //https://www.imagemagick.org/script/index.php
                                                                                                                "bin"       => "uglifyjs"
                                                                                                                , "cmd" 	=> 'uglifyjs ' . $filename . ' --compress --mangle --output ' . $filename_min
                                                                                                            )
                                                                                                        )
                                                                        , "text/css"                    => array(
                                                                                                            "cssnano"       => array(
                                                                                                                "bin"       => "cssnano"
                                                                                                                , "cmd" 	=> 'cssnano ' . $filename . ' ' . $filename_min
                                                                                                            )
                                                                                                            , "postcss"       => array( //https://github.com/cssnano/cssnano
                                                                                                                "bin"       => "postcss"
                                                                                                                , "cmd" 	=> 'postcss ' . $filename . ' --no-map -o ' . $filename_min
                                                                                                            )
                                                                                                        )
                                                                        , "text/html"                    => array(
                                                                                                            "html-minifier" => array( //https://github.com/cssnano/cssnano
                                                                                                                "bin"       => "html-minifier"
                                                                                                                , "cmd" 	=> 'html-minifier --collapse-whitespace --remove-comments --minify-css --minify-js uglify-js ' . $filename
                                                                                                            )
                                                                                                        )
                                                                    );
        $mime                                                       = self::getMimeTypeByFilename($filename);
        if(isset($optiBin[$mime])) {
            $cmd                                                    = null;
            $bins                                                   = $optiBin[$mime];
            foreach($bins AS $optim) {
                if(self::commandExist($optim["bin"])) {
                    $cmd                                            = $optim["cmd"];
                    break;
                }
            }
            if($cmd) {
                if(!$params["wait"])                                $nowait_cmd = ' > /dev/null 2>/dev/null & ';
                $shell_cmd                                          = 'nice -n 13 ' . $cmd . $nowait_cmd;

                @shell_exec($shell_cmd);

                return true;
            }

            //@shell_exec("(" . $shell_cmd . ") > /dev/null 2>/dev/null &");
    //echo $shell_cmd;
    //exit;
            //@shell_exec("nohup nice -n 13 " . $shell_cmd . " > /dev/null 2>&1");
        }
    }
    public static function compress($data, $output_result = true, $method = null, $level = 9)
	{
		if ($method === null)
		{
			$encodings = explode(",", $_SERVER["HTTP_ACCEPT_ENCODING"]);
			switch ($encodings[0]) {
                case "gzip":
                    $method = "gzip";
                    break;
                case "deflate":
                    $method = "deflate";
                    break;
                default:
            }
		}

		if ($method == "deflate")
		{
			if ($output_result)
			{
				header("Content-Encoding: deflate");
				echo gzdeflate($data, $level);
				/*gzcompress($this->tpl[0]->rpparse("main", false), 9);
				gzencode($this->tpl[0]->rpparse("main", false), 9, FORCE_DEFLATE);
				gzencode($this->tpl[0]->rpparse("main", false), 9, FORCE_GZIP);*/
			}
			else
				return array(
					"method" => "deflate"
					, "data" => gzdeflate($data, $level)
				);
		}
		elseif ($method == "gzip")
		{
			if ($output_result)
			{
				header("Content-Encoding: gzip");
				echo gzencode($data, $level);
			}
			else
				return array(
					"method" => "gzip"
					, "data" => gzencode($data, $level)
				);
		}
		else
		{
			if ($output_result)
				echo $data;
			else
				return array(
					"method" => null
					, "data" => $data
				);
		}
	}

    /**
     * @param null $mode
     * @return mixed
     */
    public static function getModes($mode = null) {
        static $loaded_modes = null;

        if(!is_array($loaded_modes)) {
            $cache = ffCache::getInstance();
            $loaded_modes = $cache->get("/cm/showfiles/modes");

            if (!$loaded_modes) {
                $loaded_modes                                       = array();
/*
                $services                                           = array(
                                                                        "sql" => null
                                                                        //, "nosql" => null
                                                                    );
                $def                                                = array(
                                                                        "struct" => array(
                                                                            "ID"                            => "primary"
                                                                            , "name"                        => "string"
                                                                            , "last_update"                 => "number"
                                                                            , "dim_x"                       => "number"
                                                                            , "dim_y"                       => "number"
                                                                            , "max_x"                       => "number"
                                                                            , "max_y"                       => "number"
                                                                            , "bgcolor"                     => "string"
                                                                            , "alpha"                       => "number"
                                                                            , "mode"                        => "string"
                                                                            , "when"                        => "string"
                                                                            , "alignment"                   => "string"
                                                                            , "theme"                       => "string"
                                                                            , "format"                      => "string"
                                                                            , "wmk_enable"                  => "boolean"
                                                                            , "wmk_image"                   => "string"
                                                                            , "wmk_alignment"               => "string"
                                                                            , "transparent"                 => "boolean"
                                                                            , "resize"                      => "boolean"
                                                                            , "frame_size"                  => "number"
                                                                            , "frame_color"                 => "string"
                                                                            , "enable_thumb_image_dir"      => "boolean"
                                                                            , "enable_thumb_image_file"     => "boolean"
                                                                            , "wmk_alpha"                   => "number"
                                                                            , "wmk_mode"                    => "string"
                                                                            , "shortdesc"                   => "string"
                                                                            , "enable_thumb_word_dir"       => "boolean"
                                                                            , "enable_thumb_word_file"      => "boolean"
                                                                            , "word_color"                  => "string"
                                                                            , "word_type"                   => "string"
                                                                            , "word_align"                  => "string"
                                                                            , "word_size"                   => "number"
                                                                            , "max_upload"                  => "number"
                                                                            , "force_icon"                  => "string"
                                                                            , "allowed_ext"                 => "string"
                                                                        )
                                                                        , "indexes"                         => array()
                                                                        , "relationship"                    => array()
                                                                        , "table"                           => self::DB_TABLE_MODES
                                                                        , "alias"                           => array()
                                                                        , "connectors"                      => false
                                                                    );
                $modes = Storage::getInstance($services, $def)->read(true);*/

                $db = new ffDB_Sql();
                $db->query("SELECT * FROM " . self::DB_TABLE_MODES . " ORDER BY name");
                $modes = $db->getRecordset();

                foreach($modes AS $params) {
                    $ID_mode                                        = $params["ID"];
                    $mode_key                                       = strtolower(str_replace(array(" ", "_"), "-", $params["name"]));

                    $loaded_modes["thumbs"][$mode_key]              = $params;
                    $loaded_modes["keys"][$ID_mode]                 = $mode_key;

                    //if(!$loaded_modes["thumb"][$mode_key]["format"])
                    //    $loaded_modes["thumb"][$mode_key]["format"]                    = "jpg";

                   /* if ($loaded_modes["thumb"][$mode_key]["wmk_enable"])
                    {
                        $loaded_modes["thumb"][$mode_key]["wmk_enable"] = false;
                        if(strlen($loaded_modes["thumb"][$mode_key]["wmk_image"]))
                        {
                            if(is_file(FF_DISK_UPDIR . "/showfiles/" . $ID_mode . "//" . $loaded_modes["thumb"][$mode_key]["wmk_image"]))
                            {
                                $loaded_modes["thumb"][$mode_key]["wmk_file"] 			= FF_DISK_UPDIR . "/showfiles/" . $ID_mode . "/watermarks/" . $loaded_modes["thumb"][$mode_key]["wmk_image"];
                                $loaded_modes["thumb"][$mode_key]["wmk_enable"] 		= true;
                            }
                        }
                    }*/

                    //$imgParams["keys"][$ID_mode]                                    = $mode_key;
                }

                $cache->set("/cm/showfiles/modes", $loaded_modes);
            }
        }

        if($mode) {
            return ($loaded_modes["thumbs"][$loaded_modes["keys"][$mode]]
                ? $loaded_modes["thumbs"][$loaded_modes["keys"][$mode]]
                : $loaded_modes["thumbs"][$mode]
            );
        } else {
            return $loaded_modes["thumbs"];
        }
    }

    public static function getMimeTypeByFilename($filename, $default = "text/plain") {
        $ext                                                        = pathinfo($filename, PATHINFO_EXTENSION);

        return self::getMimeTypeByExtension($ext, $default);
    }

    public static function getMimeTypeByExtension($ext, $default = "text/plain") {
        $mime                                                       = self::MIMETYPE[strtolower($ext)];

        return ($mime
            ? $mime
            : $default
        );
    }

    public static function getExtensionByMimeType($mime, $default = null) {
        $ext                                                       = array_search(strtolower($mime), self::MIMETYPE);

        return ($ext
            ? $ext
            : $default
        );
    }
    public static function getIconPath($ext = null, $abs = false) {
        //deve renderizzare l'icona
        //da fare con la gestione delle iconde di ffImafge



        if($ext === false) {
            $res                                                    = self::ICON_DEFAULT_PATH;
        } elseif($ext) {
            $arrExt                                                 = explode(".", $ext);
            $filename                                               = $arrExt[0];

            switch ($filename) {
                case "png":
                case "jpg":
                case "jpeg":
                case "gif":
                    $basename                                       = "noimg.png";
                    break;
                case "zip":
                case "gz":
                case "rar":
                case "bz2":
                    $basename                                       = "archive.png";
                    break;
                case "mp3":
                case "wav":
                    $basename                                       = "audio.png";
                    break;
                case "avi":
                case "mpg":
                    $basename                                       = "video.png";
                    break;
                case "spacer":
                    $basename                                       = "spacer.gif";
                    break;
                default:
                    $basename                                       = $filename . ".png";

            }
            $abs_path                                               = (is_file(self::ICON_BASE_PATH . "/" . $basename)
                                                                        ? self::ICON_BASE_PATH
                                                                        : self::ICON_DEFAULT_PATH
                                                                    );

            if(!is_file($abs_path . "/" . $basename))             $basename = "error.png";

            if($abs === false) {
                $res                                                = self::SHOWFILES . "/" . $basename;
            } elseif($abs === true) {
                $res                                                = $abs_path . "/" . $basename;
            } else {
                $res                                                = $abs . $basename;
            }
        } else {
            $res                                                    = ($abs
                                                                        ? self::ICON_DEFAULT_PATH
                                                                        : self::SHOWFILES
                                                                    ) . "/" . "unknown.png";
        }

        return $res;
    }



    /*
    public static function getNoImg($filename, $mode) {
        $ext                                                        = pathinfo($filename, PATHINFO_EXTENSION);
        $mode                                                       = self::getModeByNoImg($filename);

        $res                                                        = self::noImg($ext);

        $final_file                                                 = $res["basepath"] . "/" . $res["filesource"];
        exit;
    }*/

    private static function getModeByNoImg($basename) {
        $mode                                                       = null;
        $source                                                     = explode(".", strrev($basename), 2);
        $filename                                                   = strrev($source[1]);
        $arrFilename   			                                    = explode("-", $filename);

        $offset                                                     = count($arrFilename) - 1;
        $modes                                                      = self::getModes();
        foreach ($modes AS $key => $value) {
            if(strpos($basename, ($offset ? "-" : "") . $key . "."))  {
                $mode                                           = $key;
                break;
            }
        }
        if(!$mode) {
            if($offset >= 2 && is_numeric($arrFilename[$offset]) && is_numeric($arrFilename[$offset - 1])) {
                $mode                                                   = $arrFilename[$offset - 1] . "-" . $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("x", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "x") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("q", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "q") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("w", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "w") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("e", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "e") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("a", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "a") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("s", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "s") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("d", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "d") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("z", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "z") == 1) {
                $mode                                                   = $arrFilename[$offset];
            } else if(/*$offset >= 1 &&*/ is_numeric(str_replace("c", "", $arrFilename[$offset])) && substr_count($arrFilename[$offset], "c") == 1) {
                $mode                                                   = $arrFilename[$offset];
            }
        }

        return $mode;
    }

    private static function getModeByFile($file, $key = null) {
        $res                                                        = null;
        $source                                                     = pathinfo($file);

        $mode                                                       = self::getModeByNoImg($source["basename"]);
        if($mode) {
            $res["mode"]                                            = $mode;
            $res["filename"]                                        = str_replace("-". $mode . "." . $source["extension"], "", $source["basename"]);
            $res["basename"]                                        = $res["filename"] . "." . $source["extension"];
        }

        /*
        $arrFilename   			                                    = explode("-", $source["filename"]);
        if(count($arrFilename)) {
            for($i = 0; $i<=2; $i++) {
                $res["mode"]                                        = array_pop($arrFilename) . ($res["mode"] ? "-" : "") . $res["mode"];
                $filename                                           = implode("-", $arrFilename);

                if(is_file( $source["dirname"] . "/" . $filename . "." . $source["extension"])) {
                    $res["filename"]                                = $filename;
                    break;
                }
            }
        }*/

        return ($key
            ? $res[$key]
            : $res
        );
    }

    private static function commandExist($cmd) {
        $return                                                     = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }

    public function __construct($pathinfo)
    {
        $this->setPathInfo($pathinfo);
    }

    public function resize($mode) {

    }

    public function process($mode = null) {
        return $this->renderProcess($mode,false);
    }

    public function render($mode = null, $compress = false) {
        if($this->pathinfo && $this->pathinfo["orig"] != "/") {
            $final_file                                             = $this->renderProcess($mode);
            if($final_file) {
                $etag                                               = md5($final_file . filemtime($final_file));

                // implementare If-Modified-Since
                if (strlen($_SERVER["HTTP_IF_NONE_MATCH"]) && substr($_SERVER["HTTP_IF_NONE_MATCH"], 0, strlen($etag)) == $etag) {
                    http_response_code(304);
                } else {
                    http_response_code(200);

                    $this->headers["filename"]                      = $this->source["basename"];
                    $this->headers["etag"]                          = $etag;

                    $this->sendHeaders($final_file, $this->headers);
                    if($compress) {
                        $this::compress(file_get_contents($final_file));
                    } else {

                        readfile($final_file);
                    }
                }
            } else {
                $this->headers["cache"]                             = "must-revalidate";
                $this->headers["filename"]                          = $this->pathinfo["basename"];
                $this->headers["mimetype"]                          = ffMedia::getMimeTypeByExtension($this->pathinfo["extension"]);

                $this->sendHeaders(null, $this->headers);
                http_response_code("404");
            }
        } else {
            http_response_code("403");
        }
        exit;
    }

    public function setPathInfo($pathinfo = null) {
        if($pathinfo) {
            $this->pathinfo                                         = pathinfo($pathinfo);
            $this->pathinfo["orig"]                                 = $pathinfo;

            //todo: deprecato da togliere una volta adeguato i percorsi obsoleti
            if(substr_count($this->pathinfo["orig"], "/") >= 2) {
                $old = explode("/", ltrim($this->pathinfo["orig"], "/"), "2");
                $this->pathinfo["mode"] = $old[0];
                $this->pathinfo["old"] = "/" . $old[1];
            }
        }
    }

    private function renderProcess($mode = null, $render = true) {
        $this->clear();
        $this->waterMark();
        $final_file                                                 = $this->findSource($mode);
        if(!$final_file) {
            if (!$this->filesource && !$this::STRICT)               { $this->findSourceOld($mode); }
            if (!is_file($this->basepath . $this->filesource))  { $noimg = $this->setNoImg($mode); }

            $final_file                                             = $this->processFinalFile($noimg);
        }

        //se noimg renderizza ed esce
        if($noimg) {
            if($this->pathinfo["dirname"] == "/") {
                $pathinfo                                           = $this->pathinfo["filename"];
                if($this->mode) {
                    $pathinfo                                       = str_replace(array("-" . $this->mode, $this->mode), "", $pathinfo);
                    if(!$pathinfo)                                  $pathinfo = $this->pathinfo["extension"];
                }
            }
            if($pathinfo != pathinfo($this->filesource, PATHINFO_FILENAME)) {
                $this->renderNoImg($final_file, 404);
            }
        }

        return ($render
            ? $final_file
            : substr($final_file, strlen($this::STORING_BASE_PATH))
        );
    }

    private function clear() {
        $this->wmk                                                  = null;
        $this->source                                               = null;
        $this->mode                                                 = null;
        $this->wizard                                               = null;
        $this->basepath                                             = null;
        $this->filesource                                           = null;
        $this->final                                                = null;
    }
    private function waterMark() {
        $this->wmk                                                  = array();
        $pathinfo                                                   = $this->pathinfo["orig"];
        if(strpos($pathinfo, "/wmk") !== false)
        {
            $arrWmk                                                 = explode("/wmk", substr($pathinfo, strpos($pathinfo, "/wmk") + strlen("/wmk")));
            if(is_array($arrWmk) && count($arrWmk))
            {
                foreach($arrWmk AS $arrWmk_file)
                {
                    $wmk_abs_file                                   = $this::UPLOAD_BASE_PATH . $arrWmk_file;
                    if(strlen($arrWmk_file) && is_file($wmk_abs_file))
                    {
                        $this->wmk[]["file"]                        = $wmk_abs_file;
                    }
                }
            }

            $this->setPathInfo(substr($pathinfo, 0, strpos($pathinfo, "/wmk")));
        }
    }


    private function findSource($mode = null) {
        $is_symlink                                                 = false;
        $this->basepath                                             = $this::UPLOAD_BASE_PATH;

        if(strpos($this->pathinfo["dirname"], "/" . $this::THEME) === 0)
        {
            /*
            * quando il path inizia con il nome del tema frontend se la trova nel fs
            * Abilita una compia simbolica dell'immagine
            *
            * Non supporta il sistema di ridimensionamento dinamico
            */
            $this->basepath                                         = ffMedia::DISK_PATH;
            $this->filesource                                       = $this::THEME_DIR . $this->pathinfo["orig"];
            $is_symlink                                             = true;
        }
        elseif(is_file($this::UPLOAD_BASE_PATH . $this->pathinfo["orig"]))
        {
            /*
             * l'immagine renderizzata e quella originale
             * Abilita una compia simbolica dell'immagine
             */
            $this->filesource                                       = $this->pathinfo["orig"];
            $is_symlink                                             = true;
        }
        elseif($this->pathinfo["old"] && is_file($this::UPLOAD_BASE_PATH . $this->pathinfo["old"]))
        {  //todo: deprecato da togliere una volta adeguato i percorsi obsoleti
            $this->filesource                                       = $this->pathinfo["old"];
            $this->mode                                             = $this->pathinfo["mode"];
        }
        else {
            $this->resolveSourcePath($mode);
        }

        /*
        * Se non riesce a recuperare il file di origine tenta di recuperarlo da /themes/[theme]/images
        * Abilita una compia simbolica dell'immagine
        *
        * Non supporta il sistema di ridimensionamento dinamico
        */
        if(!$this->filesource && is_file($this::STATIC_BASE_PATH . $this->pathinfo["orig"]))
        {
            $this->basepath                                         = $this::STATIC_BASE_PATH;
            $this->filesource                                       = $this->pathinfo["orig"];
            $is_symlink                                             = true;
        }

        /*
        * fa una compia simbolica dell'immagine
        * processa l'immagine se richiesto
        */
        if($is_symlink) {
            if(!is_file($this::STORING_BASE_PATH . $this->pathinfo["orig"])) {
                if(!is_dir($this::STORING_BASE_PATH . $this->pathinfo["dirname"]))
                    mkdir($this::STORING_BASE_PATH . $this->pathinfo["dirname"], 0777, true);

                link($this->basepath . $this->filesource, $this::STORING_BASE_PATH . $this->pathinfo["orig"]);
            }

            return $this::STORING_BASE_PATH . $this->pathinfo["orig"];
        }
    }

    private function findSourceOld($mode = null) {
        $fake_filename                                              = null;
        $fake_dirname                                               = null;
        $mode_source                                                = null;
        $path_parts                                                 = explode("/", trim($this->pathinfo["orig"], "/"));

        /**
         * Remove Fake Name resolved by strpos(dirname, basename) === 0
         */
        if(strpos($path_parts[count($path_parts) - 1], $path_parts[count($path_parts) - 2] . "-") === 0) {
            $fake_filename                                          = $path_parts[count($path_parts) - 1];
            $fake_dirname                                           = $path_parts[count($path_parts) - 2];
            $path_parts[count($path_parts) - 1]                     = str_replace($fake_dirname . "-", "", $fake_filename);
            array_splice($path_parts, count($path_parts) - 2, 1);
        }

        if(strpos($path_parts[count($path_parts) - 1], ".") === false) {
            $source["filename"]                                     = $path_parts[count($path_parts) - 1];
            $source["extension"]                                    = "";
        } else {
            $source_file                                            = explode(".", $path_parts[count($path_parts) - 1]);
            $source["extension"]                                    = $source_file[count($source_file) - 1];
            unset($source_file[count($source_file) - 1]);
            $source["filename"]                                     = implode(".", $source_file);
        }

        if(count($path_parts) > 1)
        {
            $tmp_ext                                                = null;
            if(strpos($path_parts[0], "-") !== false)
                $tmp_ext                                            = substr($path_parts[0], strrpos($path_parts[0], "-") + 1);

            if($tmp_ext
                && ($tmp_ext == "jpg"
                    || $tmp_ext == "jpeg"
                    || $tmp_ext == "png"
                    || $tmp_ext == "gif"
                )
            ) {
                $mode_source                                        = substr($path_parts[0], 0, strrpos($path_parts[0], "-"));
                $source["extension"]                                = $tmp_ext;
            } else {
                $mode_source                                        = $path_parts[0];
            }
            unset($path_parts[0]);
        }
        $source["dirname"]                                          = dirname(implode("/", $path_parts));
        if(strlen($source["dirname"]))                              $source["dirname"]  = "/" . $source["dirname"];

        $source["filename"]                                         = str_replace("+", " ", $source["filename"]);
        $source["basename"]                                         = $source["filename"] . ($source["extension"]
                                                                        ? "." . $source["extension"]
                                                                        : ""
                                                                    );
        if(is_file($this->basepath . $source["dirname"] . "/" . $source["basename"])) {
            $this->filesource                                       = $source["dirname"] . "/" . $source["basename"];
            $this->mode                                             = ($mode
                                                                        ? $mode
                                                                        : $mode_source
                                                                    );
        } elseif($mode_source && is_file($this->basepath . "/" . $mode_source . $source["dirname"] . "/" . $source["basename"])) {
            $this->filesource                                       = "/" . $mode_source . $source["dirname"] . "/" . $source["basename"];
            $this->mode                                             = $mode;
        } elseif($fake_filename && is_file($this->basepath . $source["dirname"] . "/" . $fake_dirname . "/" . $fake_filename)) {
            $this->filesource                                       = $source["dirname"] . "/" . $fake_dirname . "/" . $fake_filename;
            $this->mode                                             = ($mode
                                                                        ? $mode
                                                                        : $mode_source
                                                                    );
        } elseif($mode_source && $fake_filename && is_file($this->basepath . "/" . $mode_source . $source["dirname"] . "/" . $fake_dirname . "/" . $fake_filename)) {
            $this->filesource                                       = "/" . $mode_source . $source["dirname"] . "/" . $fake_dirname . "/" . $fake_filename;
            $this->mode                                             = $mode;
        } elseif(is_file($this->basepath . dirname($source["dirname"]) . "/" . $source["basename"])) {
            $this->filesource                                       = dirname($source["dirname"]) . "/" . $source["basename"];
            $this->mode                                             = ($mode
                                                                        ? $mode
                                                                        : $mode_source
                                                                    );
        } else {
            if(is_file(ffMedia::DISK_PATH . $this::THEME_DIR . $this->pathinfo["orig"]))
            {
                $this->basepath                                     = ffMedia::DISK_PATH;
                $this->filesource                                   = $this::THEME_DIR . $this->pathinfo["orig"];
                $this->mode                                         = $mode;
            }
            elseif(is_file(ffMedia::DISK_PATH . $this::THEME_DIR . substr($this->pathinfo["orig"], strpos($this->pathinfo["orig"], "/", 1))))
            {
                $this->basepath                                     = ffMedia::DISK_PATH;
                $this->filesource                                   = substr($this->pathinfo["orig"], strpos($this->pathinfo["orig"], "/", 1));
                $this->mode                                         = ($mode
                                                                        ? $mode
                                                                        : basename(substr($this->pathinfo["orig"], 0, strpos($this->pathinfo["orig"], "/", 1)))
                                                                    );
            } else {
                $mode_source                                        = basename(substr($this->pathinfo["orig"], 0, strpos($this->pathinfo["orig"], "/", 1)));
                $source_path                                        = substr($this->pathinfo["orig"], strpos($this->pathinfo["orig"], "/", 1));
                if(strpos($mode_source, "png") !== false) {
                    $source_path                                    = substr($source_path, 0, strrpos($source_path, ".")) . ".png";
                    $mode_source                                    = str_replace("-png", "", $mode_source);
                } elseif(strpos($mode_source, "jpg") !== false) {
                    $source_path                                    = substr($source_path, 0, strrpos($source_path, ".")) . ".jpg";
                    $mode_source                                    = str_replace("-jpg", "", $mode_source);
                } elseif(strpos($mode_source, "jpeg") !== false) {
                    $source_path                                    = substr($source_path, 0, strrpos($source_path, ".")) . ".jpeg";
                    $mode_source                                    = str_replace("-jpeg", "", $mode_source);
                }

                if(is_file($this->basepath . $source_path))
                {
                    $this->mode                                     = ($mode
                                                                        ? $mode
                                                                        : $mode_source
                                                                    );
                    $this->filesource                               = $source_path;
                }
            }
        }

        if($this->filesource)
            $this->source                                           = $source;
    }
      private function makeFinalFile($ext = null) {
        if($this->filesource)
        {
            $str_wmk_file                                           = "";
            if(is_array($this->wmk) && count($this->wmk))
            {
                $str_wmk_file_time                                  = "";
                $str_wmk_file_path                                  = "";
                foreach($this->wmk AS $wmk_key => $wmk_file)
                {
                    $str_wmk_file_time                              .= filectime($wmk_file);
                    $str_wmk_file_path                              .= $wmk_file;
                }
                $str_wmk_file                                       = "-" . md5($str_wmk_file_time . $str_wmk_file_path);
            }

            if($this->mode) {
                $filepath                                           = pathinfo($this->filesource);
                $format_is_different                                = ($this->source["extension"] && $this->source["extension"] != $this->pathinfo["extension"]);
                $this->final["dirname"]                             = $filepath["dirname"];
                $this->final["filename"]                            = $filepath["filename"]
                                                                        . ($format_is_different
                                                                            ? "-" . $this->source["extension"]
                                                                            : ""
                                                                        )
                                                                        . "-" . $this->mode
                                                                        . $str_wmk_file;
                $this->final["extension"]                           = ($ext ? $ext : $this->pathinfo["extension"]);

                $this->final["exist"]                               = is_file($this->getFinalFile());

                /*if(!$this->final["exist"] && $format_is_different) { //e sbagliato il final. qui dovrebbe fare il check sul source al massimo
                    $final_ext                                      = null;
                    switch($this->final["extension"]) {
                        case "jpg":
                        case "jpeg":
                            $final_ext                              = "png";
                            break;
                        case "png":
                            $final_ext                              = "jpg";
                            break;
                        default:

                    }

                    if($final_ext) {
                        $this->final["extension"]                       = $final_ext;
                        $this->final["exist"]                           = is_file($this->getFinalFile());
                    }
                }*/
            } else {
                $this->final                                        = pathinfo($this->filesource);
                $this->final["exist"]                               = is_file($this->getFinalFile());
            }
        }

        return $this->final;
    }

    private function getFinalFile($abs = true) {
        return ($this->final
            ? ($abs
                ? $this::STORING_BASE_PATH
                : ""
            ) . $this->final["dirname"] . ($this->final["dirname"] == "/" ? "" : "/") . $this->final["filename"] . "." . $this->final["extension"]
            : false
        );
    }

    private function createImage($params) {
        $default_params                                             = array(
                                                                        "dim_x"                     => null
                                                                        , "dim_y"                   => null
                                                                        , "resize"                  => false
                                                                        , "alignment"               => "center"
                                                                        , "mode"                    => "proportional"
                                                                        , "transparent"             => true
                                                                        , "bgcolor"                 => "FFFFFF"
                                                                        , "alpha"                   =>  0
                                                                        , "format"                  => "jpg"
                                                                    );
        $params                                                     = array_replace_recursive($default_params, $params);
        $extend                                                     = true;

        if($extend)
        {
            $params["filesource"]                                   = ($params["force_icon"]
                                                                        ? ffMedia::DISK_PATH . $params["force_icon"]
                                                                        : $this->basepath . $this->filesource
                                                                    );

            //if(!$params["dim_x"] > 0)                               $params["dim_x"] = 1;
            //if(!$params["dim_y"] > 0)                               $params["dim_y"] = 1;

            if($params["resize"] && $params["mode"] != "crop") {
                $params["max_x"]                                    = $params["dim_x"];
                $params["max_y"]                                    = $params["dim_y"];

                $params["dim_x"]                                    = null;
                $params["dim_y"]                                    = null;
            } else {
                $params["max_x"]                                    = null;
                $params["max_y"]                                    = null;
            }

            if($params["format"] == "png" && $params["transparent"]) {
                $params["bgcolor_csv"]                              = $params["bgcolor"];
                $params["alpha_csv"]                                = 127;

                $params["bgcolor_new"]                              = $params["bgcolor"];
                $params["alpha_new"]                                = 127;
            } else {
                $params["bgcolor_csv"]                              = null;
                $params["alpha_csv"]                                = 0;

                $params["bgcolor_new"]                              = $params["bgcolor"];
                $params["alpha_new"]                                = $params["alpha"];
            }



            $params["wmk_word_enable"]                              = (is_dir($this->basepath . $this->filesource)
                                                                        ? $params["enable_thumb_word_dir"]
                                                                        : $params["enable_thumb_word_file"]
                                                                    );
        } else {
            if ($params["dim_x"] == 0)                              $params["dim_x"] = null;
            if ($params["dim_y"] == 0)                              $params["dim_y"] = null;
            if ($params["dim_x"] || $params["max_x"] == 0)          $params["max_x"] = null;
            if ($params["dim_y"] || $params["max_y"] == 0)          $params["max_y"] = null;

            $params["bgcolor_csv"]                                  = $params["bgcolor"];
            $params["alpha_csv"]                                    = $params["alpha"];
            $params["bgcolor_new"]                                  = $params["bgcolor"];
            $params["alpha_new"]                                    = $params["alpha"];
            $params["filesource"]                                   = $this->basepath . $this->filesource;
            $params["frame_color"]                                  = null;
            $params["frame_size"]                                   = 0;
            $params["wmk_method"]                                   = "proportional";
            $params["wmk_word_enable"]                              = false;

        }
        //if(!strlen($params["format"]))                              $params["format"] = "jpg";

        $cCanvas                                                    = new ffCanvas();

        $cCanvas->cvs_res_background_color_hex 			            = $params["bgcolor_csv"];
        $cCanvas->cvs_res_background_color_alpha 		            = $params["alpha_new"];
        $cCanvas->format 								            = $params["format"];

        $cThumb                                                     = new ffThumb($params["dim_x"], $params["dim_y"]);
        $cThumb->new_res_max_x 							            = $params["max_x"];
        $cThumb->new_res_max_y 							            = $params["max_y"];
        $cThumb->src_res_path 							            = $params["filesource"];

        $cThumb->new_res_background_color_hex 			            = $params["bgcolor_new"];
        $cThumb->new_res_background_color_alpha			            = $params["alpha_new"];

        $cThumb->new_res_frame_size 					            = $params["frame_size"];
        $cThumb->new_res_frame_color_hex 				            = $params["frame_color"];

        $cThumb->new_res_method 						            = $params["mode"];
        $cThumb->new_res_resize_when 					            = $params["when"];
        $cThumb->new_res_align 							            = $params["alignment"];

        //Default Watermark Image
        if ($params["wmk_enable"])
        {
            $cThumb_wmk                                             = new ffThumb($params["dim_x"], $params["dim_y"]);
            $cThumb_wmk->new_res_max_x 					            = $params["max_x"];
            $cThumb_wmk->new_res_max_y 					            = $params["max_y"];
            $cThumb_wmk->src_res_path 					            = $params["wmk_file"];

            //$cThumb->new_res_background_color_hex                 = $params["bgcolor"];
            $cThumb_wmk->new_res_background_color_alpha	            = "127";

            $cThumb_wmk->new_res_method 				            = $params["mode"];
            $cThumb_wmk->new_res_resize_when 			            = $params["when"];
            $cThumb_wmk->new_res_align 					            = $params["wmk_alignment"];
            $cThumb_wmk->new_res_method 				            = $params["wmk_method"];

            $cThumb->watermark 							            = $cThumb_wmk;

            //$cCanvas->addChild($cThumb_wmk);
        }

        //Multi Watermark Image
        if(is_array($this->wmk) && count($this->wmk))
        {
            foreach($this->wmk AS $wmk_key => $wmk_file)
            {
                $cThumb_wmk                                         = new ffThumb($params["dim_x"], $params["dim_y"]);
                $cThumb_wmk->new_res_max_x 						    = $params["max_x"];
                $cThumb_wmk->new_res_max_y 						    = $params["max_y"];
                $cThumb_wmk->src_res_path 						    = $wmk_file["file"];

                //$cThumb->new_res_background_color_hex             = $params["bgcolor"];
                $cThumb_wmk->new_res_background_color_alpha		    = "127";

                $cThumb_wmk->new_res_method						    = $params["mode"];
                $cThumb_wmk->new_res_resize_when 				    = $params["when"];
                $cThumb_wmk->new_res_align 						    = $params["wmk_alignment"];
                $cThumb_wmk->new_res_method 					    = $params["wmk_method"];

                $cThumb->watermark[] 							    = $cThumb_wmk;
            }
        }

        //Watermark Text
        if($params["wmk_word_enable"]) {
            $cThumb->new_res_font["caption"]                        = $params["shortdesc"];
            if(preg_match('/^[A-F0-9]{1,}$/is', strtoupper($params["word_color"])))
                $cThumb->new_res_font["color"]                      = $params["word_color"];
            if(is_numeric($params["word_size"]) && $params["word_size"] > 0)
                $cThumb->new_res_font["size"]                       = $params["word_size"];
            if(strlen($params["word_type"]))
                $cThumb->new_res_font["type"]                       = $params["word_type"];
            if(strlen($params["word_align"]))
                $cThumb->new_res_font["align"]                      = $params["word_align"];
        }

        $cCanvas->addChild($cThumb);

        $final_file                                                 = $this->getFinalFile();

        @mkdir(dirname($final_file), 0777, true);
        $cCanvas->process($final_file);
    }

    private function getMode() {
        if(!$this->modes)                                           $this->modes = $this->getModes();

        $setting                                                    = $this->modes[$this->mode];
        if(!$setting) {
            if(!$this->wizard["mode"]) {
                if(stripos($this->mode, "x") !== false) {
                    $this->wizard["alignment"] 				        = "center";
                    $this->wizard["mode"] 				            = explode("x", strtolower($this->mode));
                    $this->wizard["method"] 				        = "proportional";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "q") !== false) {
                    $this->wizard["alignment"] 				        = "top-left";
                    $this->wizard["mode"] 				            = explode("q", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "w") !== false) {
                    $this->wizard["alignment"] 				        = "top-middle";
                    $this->wizard["mode"] 				            = explode("w", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "e") !== false) {
                    $this->wizard["alignment"] 				        = "top-right";
                    $this->wizard["mode"] 				            = explode("e", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "a") !== false) {
                    $this->wizard["alignment"] 				        = "middle-left";
                    $this->wizard["mode"] 				            = explode("a", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "-") !== false) {
                    $this->wizard["alignment"] 				        = "center";
                    $this->wizard["mode"] 				            = explode("-", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "d") !== false) {
                    $this->wizard["alignment"] 				        = "middle-right";
                    $this->wizard["mode"] 				            = explode("d", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "z") !== false) {
                    $this->wizard["alignment"] 				        = "bottom-left";
                    $this->wizard["mode"] 				            = explode("z", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "s") !== false) {
                    $this->wizard["alignment"] 				        = "bottom-middle";
                    $this->wizard["mode"] 				            = explode("s", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                } elseif(strpos($this->mode, "c") !== false) {
                    $this->wizard["alignment"] 				        = "bottom-right";
                    $this->wizard["mode"] 				            = explode("c", $this->mode);
                    $this->wizard["method"] 				        = "crop";
                    $this->wizard["resize"] 				        = false;
                }
            }

            if(count($this->wizard["mode"]) == 2 && is_numeric($this->wizard["mode"][0]) && is_numeric($this->wizard["mode"][1])) {
                $setting                                            = array(
                                                                        "dim_x"     => $this->wizard["mode"][0]
                                                                        , "dim_y"   => $this->wizard["mode"][1]
                                                                        , "format"  => $this->final["extension"]
                                                                        , "alignment"  => $this->wizard["alignment"]
                                                                        , "mode"    => $this->wizard["method"]
                                                                        , "resize"  => $this->wizard["resize"]
                                                                    );
            }
        }

        return ($setting
            ? $setting
            : false
        );
    }


    private function processFinalFile($isIcon = false) {
        $final_file                                                 = null;
        if($this->filesource) {
            if(!$this->final)                                       $this->makeFinalFile($isIcon ? "png" : null);
            if($this->final) {
                $final_file                                         = $this->getFinalFile();

                $mode                                               = $this->getMode();
                //$mode["format"]                                     = $this->final["extension"];
                if (is_array($mode)) {
                    $fctime                                         = ($this->final["exist"]
                                                                        ? filectime($final_file)
                                                                        : "-1"
                                                                    );

                    if ($this::DISABLE_CACHE
                        || $fctime      <= $mode["last_update"]
                        || $fctime      <= filectime($this->basepath . $this->filesource)
                    ) {
                        $this->createImage($mode);
                        $this::optimize($final_file);
                    }
                } elseif($this->mode === false && is_file($this->basepath . $this->filesource)) {
                    if(!is_file($final_file)) {
                        link($this->basepath . $this->filesource, $final_file);
                    }
                } else {
                    $icon = $this->getIconPath(basename($this->filesource), true);
                    if(!is_file($final_file) && $icon) {
                        link($icon, $final_file);
                    }
                }
            }
        }

        return $final_file;
    }

    private function setNoImg($mode = null, $icon = null) {
        $icon_name                                                  = ($icon
                                                                        ? $icon
                                                                        : ($this->pathinfo["extension"]
                                                                            ? $this->pathinfo["extension"]
                                                                            : $this->pathinfo["basename"]
                                                                        )
                                                                    );

        $icon                                                       = $this->getIconPath($icon_name, true);
        if($icon) {
            $this->basepath                                         = dirname($icon);
            $this->filesource                                       = "/" . basename($icon);
            $this->mode                                             = ($mode
                                                                        ? $mode
                                                                        : self::getModeByNoImg($this->pathinfo["basename"])
                                                                    );
            return true;
        }
    }

    private function renderNoImg($final_file, $code = null) {
        $this->headers["cache"]                                     = "must-revalidate";
        $this->headers["filename"]                                  = $this->pathinfo["basename"];
        $this->headers["mimetype"]                                  = ffMedia::getMimeTypeByFilename($final_file);

        if($code)                                                   http_response_code($code);

        $this->sendHeaders($final_file, $this->headers);
        readfile($final_file);
        exit;
    }

    private function resolveSourcePath($mode = null) {
        if(!$this->filesource) {
            $image                                                  = $this->pathinfo;
            $basepath                                               = $this->basepath;

            /*
            * default: cerca l'immagine di origine in uploads
            * supporta il sistema di ridimensionamento dinamico
            */
            $source["dirname"] 			                            = ($image["dirname"] == "/" ? "" : $image["dirname"]);
            $source["extension"] 		                            = $image["extension"];
            $source["filename"] 	                                = $image["filename"];

            if (strpos($source["dirname"], ffMedia::THEME_DIR . "/") === 0) {
                $basepath = ffMedia::THEME_BASE_PATH;
                $source["dirname"] = substr($source["dirname"], strlen(ffMedia::THEME_DIR));
            }

            /*if (strpos($source["dirname"], ffMedia::THEME_DIR . "/" . ffMedia::THEME . "/images") === 0) {
                $basepath = ffMedia::STATIC_BASE_PATH;
                $source["dirname"] = substr($source["dirname"], strlen(ffMedia::THEME_DIR . "/" . ffMedia::THEME . "/images"));
            } elseif (strpos($source["dirname"], ffMedia::THEME_DIR . "/" . ffMedia::THEME_DEFAULT . "/images") === 0) {
                $basepath = ffMedia::STATIC_DEFAULT_PATH;
                $source["dirname"] = substr($source["dirname"], strlen(ffMedia::THEME_DIR . "/" . ffMedia::THEME_DEFAULT . "/images"));
            }*/

            if(strpos($image["filename"], "-png-") !== false) {
                $file 					                            = explode("-png-", $image["filename"]);
                $mode 					                            = ($mode
                                                                        ? $mode
                                                                        : $file[1]
                                                                    );
                $source["extension"] 	                            = "png";
                $source["filename"] 	                            = $file[0];
            } elseif(strpos($image["filename"], "-jpg-") !== false) {
                $file 					                            = explode("-jpg-", $image["filename"]);
                $mode 					                            = ($mode
                                                                        ? $mode
                                                                        : $file[1]
                                                                    );
                $source["extension"] 	                            = "jpg";
                $source["filename"] 	                            = $file[0];
            } elseif(strpos($image["filename"], "-jpeg-") !== false) {
                $file 					                            = explode("-jpeg-", $image["filename"]);
                $mode 					                            = ($mode
                                                                        ? $mode
                                                                        : $file[1]
                                                                    );
                $source["extension"] 	                            = "jpeg";
                $source["filename"] 	                            = $file[0];
            } elseif(!$mode) {
                $res = $this->getModeByFile($basepath . $source["dirname"] . "/" . $image["filename"] . "." . $source["extension"]);
                if($res) {
                    $source["filename"]                             = $res["filename"];
                    $mode                                           = $res["mode"];
                } else {
                    $mode                                           = false;
                }
            }

            if($source["filename"] && $source["extension"]) {
                $source["basename"] 	                            = $source["filename"] . "." . $source["extension"];
                $this->source                                       = $source;
                $this->mode                                         = $mode;
                $this->filesource 				                    = $source["dirname"] . "/" . $source["basename"];
                $this->basepath                                     = $basepath;

                return true;
            }
        }
    }
}
