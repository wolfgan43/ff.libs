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

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;
use phpformsframework\libs\Hook;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;
use phpformsframework\libs\storage\drivers\ImageCanvas;
use phpformsframework\libs\storage\drivers\ImageThumb;
use phpformsframework\libs\tpl\Resource;

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
    const ERROR_BUCKET                                              = "storage";

    const STRICT                                                    = false;
    const RENDER_MEDIA_PATH                                         = DIRECTORY_SEPARATOR . "media";
    const RENDER_ASSETS_PATH                                        = DIRECTORY_SEPARATOR . "assets";
    const RENDER_WIDGET_PATH                                        = DIRECTORY_SEPARATOR . "widgets";
    const RENDER_IMAGE_PATH                                         = DIRECTORY_SEPARATOR . "images";
    const RENDER_SCRIPT_PATH                                        = DIRECTORY_SEPARATOR . "js";
    const RENDER_STYLE_PATH                                         = DIRECTORY_SEPARATOR . "css";
    const RENDER_FONT_PATH                                          = DIRECTORY_SEPARATOR . "fonts";

    const MODIFY_PATH                                               = Constant::SITE_PATH . DIRECTORY_SEPARATOR . "restricted" . DIRECTORY_SEPARATOR . "media" . DIRECTORY_SEPARATOR . "modify";
    const MIMETYPE                                                  = array(
                                                                        "3dm" => "x-world/x-3dmf",
                                                                        "3dmf" => "x-world/x-3dmf",
                                                                        "a" => "application/octet-stream",
                                                                        "aab" => "application/x-authorware-bin",
                                                                        "aam" => "application/x-authorware-map",
                                                                        "aas" => "application/x-authorware-seg",
                                                                        "abc" => "text/vnd.abc",
                                                                        "acgi" => "text/html",
                                                                        "afl" => "video/animaflex",
                                                                        "ai" => "application/postscript",
                                                                        "aif" => "audio/aiff",
                                                                        "aifc" => "audio/aiff",
                                                                        "aiff" => "audio/aiff",
                                                                        "aim" => "application/x-aim",
                                                                        "aip" => "text/x-audiosoft-intra",
                                                                        "ani" => "application/x-navi-animation",
                                                                        "aos" => "application/x-nokia-9000-communicator-add-on-software",
                                                                        "aps" => "application/mime",
                                                                        "arc" => "application/octet-stream",
                                                                        "arj" => "application/arj",
                                                                        "art" => "image/x-jg",
                                                                        "asf" => "video/x-ms-asf",
                                                                        "asm" => "text/x-asm",
                                                                        "asp" => "text/asp",
                                                                        "asx" => "application/x-mplayer2",
                                                                        "au" => "audio/basic",
                                                                        "avi" => "application/x-troff-msvideo",
                                                                        "avs" => "video/avs-video",
                                                                        "bcpio" => "application/x-bcpio",
                                                                        "bin" => "application/mac-binary",
                                                                        "bm" => "image/bmp",
                                                                        "bmp" => "image/bmp",
                                                                        "boo" => "application/book",
                                                                        "book" => "application/book",
                                                                        "boz" => "application/x-bzip2",
                                                                        "bsh" => "application/x-bsh",
                                                                        "bz" => "application/x-bzip",
                                                                        "bz2" => "application/x-bzip2",
                                                                        "txt" => "text/plain",
                                                                        "c" => "text/plain",
                                                                        "c++" => "text/plain",
                                                                        "cat" => "application/vnd.ms-pki.seccat",
                                                                        "cc" => "text/plain",
                                                                        "ccad" => "application/clariscad",
                                                                        "cco" => "application/x-cocoa",
                                                                        "cdf" => "application/cdf",
                                                                        "cer" => "application/pkix-cert",
                                                                        "cha" => "application/x-chat",
                                                                        "chat" => "application/x-chat",
                                                                        "class" => "application/java",
                                                                        "com" => "application/octet-stream",
                                                                        "conf" => "text/plain",
                                                                        "cpio" => "application/x-cpio",
                                                                        "cpp" => "text/x-c",
                                                                        "cpt" => "application/mac-compactpro",
                                                                        "crl" => "application/pkcs-crl",
                                                                        "crt" => "application/pkix-cert",
                                                                        "csh" => "application/x-csh",
                                                                        "css" => "text/css",
                                                                        "cxx" => "text/plain",
                                                                        "dcr" => "application/x-director",
                                                                        "deepv" => "application/x-deepv",
                                                                        "def" => "text/plain",
                                                                        "der" => "application/x-x509-ca-cert",
                                                                        "dif" => "video/x-dv",
                                                                        "dir" => "application/x-director",
                                                                        "dl" => "video/dl",
                                                                        "doc" => "application/msword",
                                                                        "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                                                        "dot" => "application/msword",
                                                                        "dotx" =>	"application/vnd.openxmlformats-officedocument.wordprocessingml.template",
                                                                        "dp" => "application/commonground",
                                                                        "drw" => "application/drafting",
                                                                        "dump" => "application/octet-stream",
                                                                        "dv" => "video/x-dv",
                                                                        "dvi" => "application/x-dvi",
                                                                        "dwf" => "drawing/x-dwf",
                                                                        "dwg" => "application/acad",
                                                                        "dxf" => "application/dxf",
                                                                        "dxr" => "application/x-director",
                                                                        "el" => "text/x-script.elisp",
                                                                        "elc" => "application/x-bytecode.elisp",
                                                                        "env" => "application/x-envoy",
                                                                        "eps" => "application/postscript",
                                                                        "es" => "application/x-esrehber",
                                                                        "etx" => "text/x-setext",
                                                                        "evy" => "application/envoy",
                                                                        "exe" => "application/octet-stream",
                                                                        "f" => "text/plain",
                                                                        "f77" => "text/x-fortran",
                                                                        "f90" => "text/plain",
                                                                        "fdf" => "application/vnd.fdf",
                                                                        "fif" => "application/fractals",
                                                                        "fli" => "video/fli",
                                                                        "flo" => "image/florian",
                                                                        "flx" => "text/vnd.fmi.flexstor",
                                                                        "fmf" => "video/x-atomic3d-feature",
                                                                        "for" => "text/plain",
                                                                        "fpx" => "image/vnd.fpx",
                                                                        "frl" => "application/freeloader",
                                                                        "funk" => "audio/make",
                                                                        "g" => "text/plain",
                                                                        "g3" => "image/g3fax",
                                                                        "gif" => "image/gif",
                                                                        "gl" => "video/gl",
                                                                        "gsd" => "audio/x-gsm",
                                                                        "gsm" => "audio/x-gsm",
                                                                        "gsp" => "application/x-gsp",
                                                                        "gss" => "application/x-gss",
                                                                        "gtar" => "application/x-gtar",
                                                                        "gz" => "application/x-compressed",
                                                                        "gzip" => "application/x-gzip",
                                                                        "h" => "text/plain",
                                                                        "hdf" => "application/x-hdf",
                                                                        "help" => "application/x-helpfile",
                                                                        "hgl" => "application/vnd.hp-hpgl",
                                                                        "hh" => "text/plain",
                                                                        "hlb" => "text/x-script",
                                                                        "hlp" => "application/hlp",
                                                                        "hpg" => "application/vnd.hp-hpgl",
                                                                        "hpgl" => "application/vnd.hp-hpgl",
                                                                        "hqx" => "application/binhex",
                                                                        "hta" => "application/hta",
                                                                        "htc" => "text/x-component",
                                                                        "htm" => "text/html",
                                                                        "html" => "text/html",
                                                                        "htmls" => "text/html",
                                                                        "htt" => "text/webviewhtml",
                                                                        "htx" => "text/html",
                                                                        "ice" => "x-conference/x-cooltalk",
                                                                        "ico" => "image/x-icon",
                                                                        "idc" => "text/plain",
                                                                        "ief" => "image/ief",
                                                                        "iefs" => "image/ief",
                                                                        "iges" => "application/iges",
                                                                        "igs" => "application/iges",
                                                                        "ima" => "application/x-ima",
                                                                        "imap" => "application/x-httpd-imap",
                                                                        "inf" => "application/inf",
                                                                        "ins" => "application/x-internett-signup",
                                                                        "ip" => "application/x-ip2",
                                                                        "isu" => "video/x-isvideo",
                                                                        "it" => "audio/it",
                                                                        "iv" => "application/x-inventor",
                                                                        "ivr" => "i-world/i-vrml",
                                                                        "ivy" => "application/x-livescreen",
                                                                        "jam" => "audio/x-jam",
                                                                        "jav" => "text/plain",
                                                                        "java" => "text/plain",
                                                                        "jcm" => "application/x-java-commerce",
                                                                        "jpg" => "image/jpeg",
                                                                        "jpe" => "image/jpeg",
                                                                        "jpeg" => "image/jpeg",
                                                                        "jfif" => "image/jpeg",
                                                                        "jfif-tbnl" => "image/jpeg",
                                                                        "jps" => "image/x-jps",
                                                                        "js" => "application/x-javascript",
                                                                        "jut" => "image/jutvision",
                                                                        "kar" => "audio/midi",
                                                                        "ksh" => "application/x-ksh",
                                                                        "la" => "audio/nspaudio",
                                                                        "lam" => "audio/x-liveaudio",
                                                                        "latex" => "application/x-latex",
                                                                        "lha" => "application/lha",
                                                                        "lhx" => "application/octet-stream",
                                                                        "list" => "text/plain",
                                                                        "lma" => "audio/nspaudio",
                                                                        "log" => "text/plain",
                                                                        "lsp" => "application/x-lisp",
                                                                        "lst" => "text/plain",
                                                                        "lsx" => "text/x-la-asf",
                                                                        "ltx" => "application/x-latex",
                                                                        "lzh" => "application/octet-stream",
                                                                        "lzx" => "application/lzx",
                                                                        "m" => "text/plain",
                                                                        "m1v" => "video/mpeg",
                                                                        "m2a" => "audio/mpeg",
                                                                        "m2v" => "video/mpeg",
                                                                        "m3u" => "audio/x-mpequrl",
                                                                        "man" => "application/x-troff-man",
                                                                        "map" => "application/x-navimap",
                                                                        "mar" => "text/plain",
                                                                        "mbd" => "application/mbedlet",
                                                                        "mc$" => "application/x-magic-cap-package-1.0",
                                                                        "mcd" => "application/mcad",
                                                                        "mcf" => "image/vasa",
                                                                        "mcp" => "application/netmc",
                                                                        "me" => "application/x-troff-me",
                                                                        "mht" => "message/rfc822",
                                                                        "mhtml" => "message/rfc822",
                                                                        "mid" => "application/x-midi",
                                                                        "midi" => "application/x-midi",
                                                                        "mif" => "application/x-frame",
                                                                        "mime" => "message/rfc822",
                                                                        "mjf" => "audio/x-vnd.audioexplosion.mjuicemediafile",
                                                                        "mjpg" => "video/x-motion-jpeg",
                                                                        "mm" => "application/base64",
                                                                        "mme" => "application/base64",
                                                                        "mod" => "audio/mod",
                                                                        "moov" => "video/quicktime",
                                                                        "mov" => "video/quicktime",
                                                                        "movie" => "video/x-sgi-movie",
                                                                        "mp2" => "audio/mpeg",
                                                                        "mp3" => "audio/mpeg3",
                                                                        "mpa" => "audio/mpeg",
                                                                        "mpc" => "application/x-project",
                                                                        "mpe" => "video/mpeg",
                                                                        "mpeg" => "video/mpeg",
                                                                        "mpg" => "audio/mpeg",
                                                                        "mpga" => "audio/mpeg",
                                                                        "mpp" => "application/vnd.ms-project",
                                                                        "mpt" => "application/x-project",
                                                                        "mpv" => "application/x-project",
                                                                        "mpx" => "application/x-project",
                                                                        "mrc" => "application/marc",
                                                                        "ms" => "application/x-troff-ms",
                                                                        "mv" => "video/x-sgi-movie",
                                                                        "my" => "audio/make",
                                                                        "mzz" => "application/x-vnd.audioexplosion.mzz",
                                                                        "nap" => "image/naplps",
                                                                        "naplps" => "image/naplps",
                                                                        "nc" => "application/x-netcdf",
                                                                        "ncm" => "application/vnd.nokia.configuration-message",
                                                                        "nif" => "image/x-niff",
                                                                        "niff" => "image/x-niff",
                                                                        "nix" => "application/x-mix-transfer",
                                                                        "nsc" => "application/x-conference",
                                                                        "nvd" => "application/x-navidoc",
                                                                        "o" => "application/octet-stream",
                                                                        "oda" => "application/oda",
                                                                        "omc" => "application/x-omc",
                                                                        "omcd" => "application/x-omcdatamaker",
                                                                        "omcr" => "application/x-omcregerator",
                                                                        "p" => "text/x-pascal",
                                                                        "p10" => "application/pkcs10",
                                                                        "p12" => "application/pkcs-12",
                                                                        "p7a" => "application/x-pkcs7-signature",
                                                                        "p7c" => "application/pkcs7-mime",
                                                                        "p7m" => "application/pkcs7-mime",
                                                                        "p7r" => "application/x-pkcs7-certreqresp",
                                                                        "p7s" => "application/pkcs7-signature",
                                                                        "part" => "application/pro_eng",
                                                                        "pas" => "text/pascal",
                                                                        "pbm" => "image/x-portable-bitmap",
                                                                        "pcl" => "application/vnd.hp-pcl",
                                                                        "pct" => "image/x-pict",
                                                                        "pcx" => "image/x-pcx",
                                                                        "pdb" => "chemical/x-pdb",
                                                                        "pdf" => "application/pdf",
                                                                        "pfunk" => "audio/make",
                                                                        "pgm" => "image/x-portable-graymap",
                                                                        "pic" => "image/pict",
                                                                        "pict" => "image/pict",
                                                                        "pkg" => "application/x-newton-compatible-pkg",
                                                                        "pko" => "application/vnd.ms-pki.pko",
                                                                        "pl" => "text/plain",
                                                                        "plx" => "application/x-pixclscript",
                                                                        "pm" => "image/x-xpixmap",
                                                                        "pm4" => "application/x-pagemaker",
                                                                        "pm5" => "application/x-pagemaker",
                                                                        "png" => "image/png",
                                                                        "pnm" => "application/x-portable-anymap",
                                                                        "pot" => "application/mspowerpoint",
                                                                        "potx" => "application/vnd.openxmlformats-officedocument.presentationml.template",
                                                                        "pov" => "model/x-pov",
                                                                        "ppa" => "application/vnd.ms-powerpoint",
                                                                        "ppm" => "image/x-portable-pixmap",
                                                                        "pps" => "application/mspowerpoint",
                                                                        "ppsx" => "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
                                                                        "ppt" => "application/mspowerpoint",
                                                                        "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                                                                        "ppz" => "application/mspowerpoint",
                                                                        "pre" => "application/x-freelance",
                                                                        "prt" => "application/pro_eng",
                                                                        "ps" => "application/postscript",
                                                                        "psd" => "application/octet-stream",
                                                                        "pvu" => "paleovu/x-pv",
                                                                        "pwz" => "application/vnd.ms-powerpoint",
                                                                        "py" => "text/x-script.phyton",
                                                                        "pyc" => "applicaiton/x-bytecode.python",
                                                                        "qcp" => "audio/vnd.qcelp",
                                                                        "qd3" => "x-world/x-3dmf",
                                                                        "qd3d" => "x-world/x-3dmf",
                                                                        "qif" => "image/x-quicktime",
                                                                        "qt" => "video/quicktime",
                                                                        "qtc" => "video/x-qtc",
                                                                        "qti" => "image/x-quicktime",
                                                                        "qtif" => "image/x-quicktime",
                                                                        "ra" => "audio/x-pn-realaudio",
                                                                        "ram" => "audio/x-pn-realaudio",
                                                                        "rar" => "application/x-rar-compressed",
                                                                        "ras" => "application/x-cmu-raster",
                                                                        "rast" => "image/cmu-raster",
                                                                        "rexx" => "text/x-script.rexx",
                                                                        "rf" => "image/vnd.rn-realflash",
                                                                        "rgb" => "image/x-rgb",
                                                                        "rm" => "application/vnd.rn-realmedia",
                                                                        "rmi" => "audio/mid",
                                                                        "rmm" => "audio/x-pn-realaudio",
                                                                        "rmp" => "audio/x-pn-realaudio",
                                                                        "rng" => "application/ringing-tones",
                                                                        "rnx" => "application/vnd.rn-realplayer",
                                                                        "roff" => "application/x-troff",
                                                                        "rp" => "image/vnd.rn-realpix",
                                                                        "rpm" => "audio/x-pn-realaudio-plugin",
                                                                        "rt" => "text/richtext",
                                                                        "rtf" => "application/rtf",
                                                                        "rtx" => "application/rtf",
                                                                        "rv" => "video/vnd.rn-realvideo",
                                                                        "s" => "text/x-asm",
                                                                        "s3m" => "audio/s3m",
                                                                        "saveme" => "application/octet-stream",
                                                                        "sbk" => "application/x-tbook",
                                                                        "scm" => "application/x-lotusscreencam",
                                                                        "sdml" => "text/plain",
                                                                        "sdp" => "application/sdp",
                                                                        "sdr" => "application/sounder",
                                                                        "sea" => "application/sea",
                                                                        "set" => "application/set",
                                                                        "sgm" => "text/sgml",
                                                                        "sgml" => "text/sgml",
                                                                        "sh" => "application/x-bsh",
                                                                        "shar" => "application/x-bsh",
                                                                        "shtml" => "text/html",
                                                                        "sid" => "audio/x-psid",
                                                                        "sit" => "application/x-sit",
                                                                        "skd" => "application/x-koan",
                                                                        "skm" => "application/x-koan",
                                                                        "skp" => "application/x-koan",
                                                                        "skt" => "application/x-koan",
                                                                        "sl" => "application/x-seelogo",
                                                                        "smi" => "application/smil",
                                                                        "smil" => "application/smil",
                                                                        "snd" => "audio/basic",
                                                                        "sol" => "application/solids",
                                                                        "spc" => "application/x-pkcs7-certificates",
                                                                        "spl" => "application/futuresplash",
                                                                        "spr" => "application/x-sprite",
                                                                        "sprite" => "application/x-sprite",
                                                                        "src" => "application/x-wais-source",
                                                                        "ssi" => "text/x-server-parsed-html",
                                                                        "ssm" => "application/streamingmedia",
                                                                        "sst" => "application/vnd.ms-pki.certstore",
                                                                        "step" => "application/step",
                                                                        "stl" => "application/sla",
                                                                        "stp" => "application/step",
                                                                        "sv4cpio" => "application/x-sv4cpio",
                                                                        "sv4crc" => "application/x-sv4crc",
                                                                        "svf" => "image/vnd.dwg",
                                                                        "svr" => "application/x-world",
                                                                        "swf" => "application/x-shockwave-flash",
                                                                        "t" => "application/x-troff",
                                                                        "talk" => "text/x-speech",
                                                                        "tar" => "application/x-tar",
                                                                        "tbk" => "application/toolbook",
                                                                        "tcl" => "application/x-tcl",
                                                                        "tcsh" => "text/x-script.tcsh",
                                                                        "tex" => "application/x-tex",
                                                                        "texi" => "application/x-texinfo",
                                                                        "texinfo" => "application/x-texinfo",
                                                                        "text" => "text/plain",
                                                                        "tgz" => "application/gnutar",
                                                                        "tif" => "image/tiff",
                                                                        "tiff" => "image/tiff",
                                                                        "tr" => "application/x-troff",
                                                                        "tsi" => "audio/tsp-audio",
                                                                        "tsp" => "application/dsptype",
                                                                        "tsv" => "text/tab-separated-values",
                                                                        "turbot" => "image/florian",
                                                                        "uil" => "text/x-uil",
                                                                        "uni" => "text/uri-list",
                                                                        "unis" => "text/uri-list",
                                                                        "unv" => "application/i-deas",
                                                                        "uri" => "text/uri-list",
                                                                        "uris" => "text/uri-list",
                                                                        "ustar" => "application/x-ustar",
                                                                        "uu" => "application/octet-stream",
                                                                        "uue" => "text/x-uuencode",
                                                                        "vcd" => "application/x-cdlink",
                                                                        "vcs" => "text/x-vcalendar",
                                                                        "vda" => "application/vda",
                                                                        "vdo" => "video/vdo",
                                                                        "vew" => "application/groupwise",
                                                                        "viv" => "video/vivo",
                                                                        "vivo" => "video/vivo",
                                                                        "vmd" => "application/vocaltec-media-desc",
                                                                        "vmf" => "application/vocaltec-media-file",
                                                                        "voc" => "audio/voc",
                                                                        "vos" => "video/vosaic",
                                                                        "vox" => "audio/voxware",
                                                                        "vqe" => "audio/x-twinvq-plugin",
                                                                        "vqf" => "audio/x-twinvq",
                                                                        "vql" => "audio/x-twinvq-plugin",
                                                                        "vrml" => "application/x-vrml",
                                                                        "vrt" => "x-world/x-vrt",
                                                                        "vsd" => "application/x-visio",
                                                                        "vst" => "application/x-visio",
                                                                        "vsw" => "application/x-visio",
                                                                        "w60" => "application/wordperfect6.0",
                                                                        "w61" => "application/wordperfect6.1",
                                                                        "w6w" => "application/msword",
                                                                        "wav" => "audio/x-wav",
                                                                        "wb1" => "application/x-qpro",
                                                                        "wbmp" => "image/vnd.wap.wbmp",
                                                                        "web" => "application/vnd.xara",
                                                                        "wiz" => "application/msword",
                                                                        "wk1" => "application/x-123",
                                                                        "wmf" => "windows/metafile",
                                                                        "wml" => "text/vnd.wap.wml",
                                                                        "wmlc" => "application/vnd.wap.wmlc",
                                                                        "wmls" => "text/vnd.wap.wmlscript",
                                                                        "wmlsc" => "application/vnd.wap.wmlscriptc",
                                                                        "word" => "application/msword",
                                                                        "wp" => "application/wordperfect",
                                                                        "wp5" => "application/wordperfect",
                                                                        "wp6" => "application/wordperfect",
                                                                        "wpd" => "application/wordperfect",
                                                                        "wq1" => "application/x-lotus",
                                                                        "wri" => "application/mswrite",
                                                                        "wrl" => "application/x-world",
                                                                        "wrz" => "model/vrml",
                                                                        "wsc" => "text/scriplet",
                                                                        "wsrc" => "application/x-wais-source",
                                                                        "wtk" => "application/x-wintalk",
                                                                        "xbm" => "image/x-xbitmap",
                                                                        "xdr" => "video/x-amt-demorun",
                                                                        "xgz" => "xgl/drawing",
                                                                        "xif" => "image/vnd.xiff",
                                                                        "xl" => "application/excel",
                                                                        "xla" => "application/excel",
                                                                        "xlb" => "application/excel",
                                                                        "xlc" => "application/excel",
                                                                        "xld" => "application/excel",
                                                                        "xlk" => "application/excel",
                                                                        "xll" => "application/excel",
                                                                        "xlm" => "application/excel",
                                                                        "xls" => "application/excel",
                                                                        "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                                                        "xlt" => "application/excel",
                                                                        "xltx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
                                                                        "xlv" => "application/excel",
                                                                        "xlw" => "application/excel",
                                                                        "xm" => "audio/xm",
                                                                        "xml" => "application/xml",
                                                                        "xmz" => "xgl/movie",
                                                                        "xpix" => "application/x-vnd.ls-xpix",
                                                                        "xpm" => "image/x-xpixmap",
                                                                        "x-png" => "image/png",
                                                                        "xsr" => "video/x-amt-showrun",
                                                                        "xwd" => "image/x-xwd",
                                                                        "xyz" => "chemical/x-pdb",
                                                                        "z" => "application/x-compress",
                                                                        "zip" => "application/x-compressed",
                                                                        "zoo" => "application/octet-stream",
                                                                        "zsh" => "text/x-script.zsh",
                                                                        "eot" => "application/vnd.ms-fontobject",
                                                                        "ttf" => "application/x-font-ttf",
                                                                        "otf" => "application/octet-stream",
                                                                        "woff" => "application/x-font-woff",
                                                                        "svg" => "image/svg+xml",
                                                                        "rss" => "application/rss+xml",
                                                                        "json" => "application/json",
                                                                        "webp" => "image/webp",
                                                                    );
    const MIMETYPE_DEFAULT                                          = "text/plain";
    const MIMETYPE_IMAGE                                            = array(
                                                                        "jpg" => self::MIMETYPE["jpg"],
                                                                        "jpeg" => self::MIMETYPE["jpeg"],
                                                                        "png" => self::MIMETYPE["png"],
                                                                        "gif" => self::MIMETYPE["gif"],
                                                                        "svg" => self::MIMETYPE["svg"]
                                                                    );
    const MIMETYPE_FONT                                             = array(
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
     */
    public function get(string $pathinfo)
    {
        $this->setPathInfo($pathinfo);
        $status                                                     = null;
        $content_type                                               = (
            empty($this->pathinfo->extension)
            ? static::MIMETYPE_DEFAULT
            : $this->getMimeByExtension($this->pathinfo->extension)
        );

        $res                                                        = $content_type != static::MIMETYPE_DEFAULT && $this->process();
        if (!$res) {
            //todo: non renderizza bene l'output. forse per colpa degli headers
            $status                                                 = 404;
            if ($this->isImage()) {
                $res                                                = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
                $content_type                                       = "image/png";
            }
        }

        Response::sendRawData($res, $content_type, $status);
    }

    /**
     * @param string $name
     * @param string|null $mode
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
     * @return array|null
     */
    public static function getInfo(string $file, string $mode = null) : array
    {
        if (strpos($file, "://") !== false) {
            return array(
                "url"                   => $file,
                "web_url"               => $file,
                "extension"             => null,
                "file"                  => null,
                "mode"                  => null
            );
        }

        $query                                                      = null;
        if (strpos($file, "/") === false) {
            $file                                                   = Resource::get($file, "images");
        }
        if (empty($file)) {
            return array(
                "url"                   => null,
                "web_url"               => null,
                "extension"             => null,
                "file"                  => null,
                "mode"                  => null
            );
        }
        $arrFile                                                    = pathinfo($file);
        if ($arrFile["extension"] == "svg") {
            return array(
                                                                        "url"                   => self::image2base64($file, $arrFile["extension"]),
                                                                        "web_url"               => null,
                                                                        "extension"             => $arrFile["extension"],
                                                                        "file"                  => $file,
                                                                        "mode"                  => $mode
                                                                    );
        }

        $showfiles                                                  = Constant::SITE_PATH . static::RENDER_MEDIA_PATH;
        if (strpos($arrFile["dirname"], static::RENDER_ASSETS_PATH) !== false) {
            $showfiles                                              = Constant::SITE_PATH . static::RENDER_ASSETS_PATH;
            $arrFile["dirname"]                                     = explode(static::RENDER_ASSETS_PATH, $arrFile["dirname"], 2)[1];
            if (strpos($arrFile["dirname"], static::RENDER_WIDGET_PATH) === 0
                && (DIRECTORY_SEPARATOR . $arrFile["extension"] == static::RENDER_STYLE_PATH || DIRECTORY_SEPARATOR . $arrFile["extension"] == static::RENDER_SCRIPT_PATH)
            ) {
                $arrFile["dirname"]                                 .= DIRECTORY_SEPARATOR . $arrFile["extension"];
            }
        }

        $dirfilename                                                = $showfiles . ($arrFile["dirname"] == DIRECTORY_SEPARATOR ? "" : $arrFile["dirname"]) . DIRECTORY_SEPARATOR . $arrFile["filename"];
        $url                                                        = $dirfilename . ($arrFile["filename"] && $mode ? "-" : "") . $mode . ($arrFile["extension"] ? "." . $arrFile["extension"] : "") . $query;
        return array(
            "url"                   => $url,
            "web_url"               => Request::protocolHost() . $url,
            "extension"             => $arrFile["extension"],
            "file"                  => $dirfilename,
            "mode"                  => $mode
        );
    }

    /**
     * @param string $file
     * @param string|null $mode
     * @param string $key
     * @return string
     */
    public static function getUrl(string $file, string $mode = null, string $key = "url") : ?string
    {
        return self::getInfo($file, $mode)[$key];
    }

    /**
     * @param string $path
     * @param string $ext
     * @return string
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

            $abs_path                                               = Resource::get($filename, "images");
            if (!$abs_path) {
                $abs_path = Resource::get("error", "images");
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
            $abs_path = Resource::get("unknown", "images");
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
    private static function getModeByFile(string $file) : ?array
    {
        $res                                                        = null;
        $source                                                     = pathinfo($file);

        $mode                                                       = self::getModeByNoImg($source["basename"]);
        if ($mode) {
            $res["mode"]                                            = $mode;
            $res["filename"]                                        = str_replace("-". $mode . "." . $source["extension"], "", $source["basename"]);
            $res["basename"]                                        = $res["filename"] . "." . $source["extension"];
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
     * @param string $mode
     */
    public function resize(string $mode)
    {
    }

    /**
     * @param string $render_path
     * @return bool
     */
    private function isValidStatic(string $render_path) : bool
    {
        $res                                                        = false;
        switch ($render_path) {
            case static::RENDER_SCRIPT_PATH:
            case static::RENDER_STYLE_PATH:
                if (basename($render_path) == $this->pathinfo["extension"]) {
                    $res                                            = true;
                }
                break;
            case static::RENDER_IMAGE_PATH:
                if ($this->isImage()) {
                    $res                                            = true;
                }
                break;
            case static::RENDER_FONT_PATH:
                if ($this->isFont()) {
                    $res                                            = true;
                }
                break;
            default:
        }

        return $res;
    }

    /**
     * @param string $resource_name
     * @return string|null
     */
    private function staticProcessWidget(string $resource_name) : ?string
    {
        $arrDirname                                                 = explode(DIRECTORY_SEPARATOR, ltrim($this->pathinfo["dirname"], DIRECTORY_SEPARATOR));
        return (count($arrDirname) === 3 && $this->isValidStatic(DIRECTORY_SEPARATOR . $arrDirname[2])
            ? Resource::widget($arrDirname[1])->find($arrDirname[2], $resource_name)
            : null
        );
    }

    /**
     * @param string $resource_name
     * @return string|null
     */
    private function staticProcessAsset(string $resource_name) : ?string
    {
        $arrDirname                                                 = explode(DIRECTORY_SEPARATOR, ltrim($this->pathinfo["dirname"], DIRECTORY_SEPARATOR));
        return ($this->isValidStatic(DIRECTORY_SEPARATOR . $arrDirname[0])
            ? Resource::get($resource_name, $arrDirname[0])
            : null
        );
    }

    /**
     * @param string|null $mode
     * @return string|null
     */
    private function staticResource(string &$mode = null) : ?string
    {
        $name                                                       = $this->staticResourceName($mode);
        return (strpos($this->pathinfo["dirname"], static::RENDER_WIDGET_PATH) === 0
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
        if ($mode) {
            $name                                                   = str_replace("-" . $mode, "", $this->pathinfo["filename"]);
        } else {
            $arrFilename                                            = explode("-", $this->pathinfo["filename"], 2);
            $name                                                   = $arrFilename[0];
            if (isset($arrFilename[1])) {
                $mode                                               = $arrFilename[1];
            }
        }
        return $name;
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
        return (isset(static::MIMETYPE_IMAGE[$this->pathinfo["extension"]])
            ? static::MIMETYPE_IMAGE[$this->pathinfo["extension"]]
            : null
        );
    }
    /**
     * @return string|null
     */
    private function isFont() : ?string
    {
        return (isset(static::MIMETYPE_FONT[$this->pathinfo["extension"]])
            ? static::MIMETYPE_FONT[$this->pathinfo["extension"]]
            : null
        );
    }
    /**
     * @param string|null $mode
     * @return bool
     */
    private function process(string $mode = null) : bool
    {
        if ($this->pathinfo["render"] == static::RENDER_ASSETS_PATH) {
            if ($this->staticProcess($mode)) {
                $final_file = $this->processFinalFile();
                if ($final_file) {
                    $this->renderNoImg($final_file);
                }
            } elseif ($this->isImage()) {
                Response::redirect($this->getIconPath("noimg"), 302);
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
            $this->pathinfo                                         = pathinfo($path);
            $this->pathinfo["render"]                               = $render;
            $this->pathinfo["orig"]                                 = $path;
        }
    }

    /**
     * @param string|null $mode
     * @return bool
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
                if ($cache_basepath && !is_file($cache_basepath . $this->pathinfo["orig"])) {
                    $this->saveFromOriginal($this->basepath . $this->filesource, $cache_basepath . $this->pathinfo["orig"]);
                    $final_file                                     = $cache_basepath . $this->pathinfo["orig"];
                }
            }
        }

        if (!$final_file) {
            $filename                                               = $this->pathinfo["filename"];
            if ($this->mode) {
                $filename                                           = str_replace(array("-" . $this->mode, $this->mode), "", $filename);
                if (!$filename) {
                    $filename = $this->pathinfo["extension"];
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
        $orig                                                       = $this->pathinfo["orig"];
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
        return str_replace($this->filesource, "", Resource::get($this->source["filename"], "images"));
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
                $this->pathinfo["render"] == static::RENDER_ASSETS_PATH
                ? $this->basepathAsset()
                : $this->basepathMedia()
            );
        }
    }

    /**
     * @param string|null $filename
     * @return array|null
     */
    private function makeFinalFile(string $filename = null) : ?array
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
                $filepath                                           = pathinfo($this->filesource);
                $format_is_different                                = ($this->source["extension"] && $this->source["extension"] != $this->pathinfo["extension"]);
                $this->final["dirname"]                             = $this->pathinfo["dirname"];
                $this->final["filename"]                            = (
                    $filename
                    ? $filename
                    : $filepath["filename"]
                )
                . (
                    $format_is_different
                    ? "-" . $this->source["extension"]
                    : ""
                )
                . "-" . $this->mode
                . $str_wmk_file;
                $this->final["extension"]                           = $this->pathinfo["extension"];

                $this->final["exist"]                               = is_file($this->getFinalFile());
            } else {
                $this->final                                        = $this->pathinfo;
                $this->final["exist"]                               = is_file($this->getFinalFile());
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
                                                                        . $this->final["dirname"]
                                                                        . (
                                                                            $this->final["dirname"] == DIRECTORY_SEPARATOR
                                                                            ? ""
                                                                            : DIRECTORY_SEPARATOR
                                                                        )
                                                                        . $this->final["filename"]
                                                                        . "." . $this->final["extension"];
        }

        if (!empty($this->final["exist"])) {
            $file_stored = $final_path;
        }

        return $final_path;
    }

    /**
     * @param array $params
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
        $params                                                     = array_replace_recursive($default_params, $params);
        $extend                                                     = true;

        if ($extend) {
            $params["filesource"]                                   = (
                isset($params["force_icon"]) && $params["force_icon"]
                                                                        ? Constant::DISK_PATH . $params["force_icon"]
                                                                        : $this->basepath . $this->filesource
                                                                    );

            if ($params["resize"] && $params["mode"] != "crop") {
                $params["max_x"]                                    = $params["dim_x"];
                $params["max_y"]                                    = $params["dim_y"];

                $params["dim_x"]                                    = null;
                $params["dim_y"]                                    = null;
            } else {
                $params["max_x"]                                    = null;
                $params["max_y"]                                    = null;
            }

            if ($params["format"] == "png" && $params["transparent"]) {
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



            $params["wmk_word_enable"]                              = (
                is_dir($this->basepath . $this->filesource)
                                                                        ? $params["enable_thumb_word_dir"]
                                                                        : $params["enable_thumb_word_file"]
                                                                    );
        } else {
            if ($params["dim_x"] == 0) {
                $params["dim_x"] = null;
            }
            if ($params["dim_y"] == 0) {
                $params["dim_y"] = null;
            }
            if ($params["dim_x"] || $params["max_x"] == 0) {
                $params["max_x"] = null;
            }
            if ($params["dim_y"] || $params["max_y"] == 0) {
                $params["max_y"] = null;
            }

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

        $cCanvas                                                    = new ImageCanvas();

        $cCanvas->cvs_res_background_color_hex 			            = $params["bgcolor_csv"];
        $cCanvas->cvs_res_background_color_alpha 		            = $params["alpha_new"];
        $cCanvas->format 								            = $params["format"];

        $cThumb                                                     = new ImageThumb($params["dim_x"], $params["dim_y"]);
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
        if ($params["wmk_enable"]) {
            $cThumb_wmk                                             = new ImageThumb($params["dim_x"], $params["dim_y"]);
            $cThumb_wmk->new_res_max_x 					            = $params["max_x"];
            $cThumb_wmk->new_res_max_y 					            = $params["max_y"];
            $cThumb_wmk->src_res_path 					            = $params["wmk_file"];

            $cThumb_wmk->new_res_background_color_alpha	            = "127";

            $cThumb_wmk->new_res_method 				            = $params["mode"];
            $cThumb_wmk->new_res_resize_when 			            = $params["when"];
            $cThumb_wmk->new_res_align 					            = $params["wmk_alignment"];
            $cThumb_wmk->new_res_method 				            = $params["wmk_method"];

            $cThumb->addWatermark($cThumb_wmk);
        }

        //Multi Watermark Image
        if (!empty($this->wmk)) {
            foreach ($this->wmk as $wmk_file) {
                $cThumb_wmk                                         = new ImageThumb($params["dim_x"], $params["dim_y"]);
                $cThumb_wmk->new_res_max_x 						    = $params["max_x"];
                $cThumb_wmk->new_res_max_y 						    = $params["max_y"];
                $cThumb_wmk->src_res_path 						    = $wmk_file["file"];

                $cThumb_wmk->new_res_background_color_alpha		    = "127";

                $cThumb_wmk->new_res_method						    = $params["mode"];
                $cThumb_wmk->new_res_resize_when 				    = $params["when"];
                $cThumb_wmk->new_res_align 						    = $params["wmk_alignment"];
                $cThumb_wmk->new_res_method 					    = $params["wmk_method"];

                $cThumb->addWatermark($cThumb_wmk);
            }
        }

        //Watermark Text
        if ($params["wmk_word_enable"]) {
            $cThumb->new_res_font["caption"]                        = $params["shortdesc"];
            if (preg_match('/^[A-F0-9]{1,}$/is', strtoupper($params["word_color"]))) {
                $cThumb->new_res_font["color"]                      = $params["word_color"];
            }
            if (is_numeric($params["word_size"]) && $params["word_size"] > 0) {
                $cThumb->new_res_font["size"]                       = $params["word_size"];
            }
            if (strlen($params["word_type"])) {
                $cThumb->new_res_font["type"]                       = $params["word_type"];
            }
            if (strlen($params["word_align"])) {
                $cThumb->new_res_font["align"]                      = $params["word_align"];
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
        return ($this->pathinfo["render"] == static::RENDER_ASSETS_PATH
            ? Dir::findCachePath("assets")
            : Dir::findCachePath("thumbs")
        );
    }

    /**
     * @param string $mode
     * @return array|null
     */
    private function getModeWizard(string $mode) : ?array
    {
        $char                                                       = strtolower(preg_replace('/[0-9]+/', '', $mode));
        $wizard_mode                                                = array(
                                                                        "alignment" => "center"
                                                                        , "mode"    => explode($char, $mode)
                                                                        , "method"  => "crop"
                                                                        , "resize"  => false
                                                                    );

        switch ($char) {
            case "x":
                $wizard_mode["alignment"]                           = "center";
                $wizard_mode["method"]                              = "proportional";
                break;
            case "q":
                $wizard_mode["alignment"]                           = "top-left";
                break;
            case "w":
                $wizard_mode["alignment"]                           = "top-middle";
                break;
            case "e":
                $wizard_mode["alignment"]                           = "top-right";
                break;
            case "a":
                $wizard_mode["alignment"]                           = "middle-left";
                break;
            case "d":
                $wizard_mode["alignment"]                           = "middle-right";
                break;
            case "z":
                $wizard_mode["alignment"]                           = "bottom-left";
                break;
            case "s":
                $wizard_mode["alignment"]                           = "bottom-middle";
                break;
            case "c":
                $wizard_mode["alignment"]                           = "bottom-right";
                break;
            default:
                $wizard_mode                                        = null;
        }

        return $wizard_mode;
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
        }

        if (!$setting) {
            if (!$this->wizard["mode"]) {
                $this->wizard                                       = $this->getModeWizard($this->mode);
            }

            if (is_array($this->wizard) && count($this->wizard["mode"]) == 2 && is_numeric($this->wizard["mode"][0]) && is_numeric($this->wizard["mode"][1])) {
                $setting                                            = array(
                                                                        "dim_x"             => $this->wizard["mode"][0]
                                                                        , "dim_y"           => $this->wizard["mode"][1]
                                                                        , "format"          => $this->final["extension"]
                                                                        , "alignment"       => $this->wizard["alignment"]
                                                                        , "mode"            => $this->wizard["method"]
                                                                        , "resize"          => $this->wizard["resize"]
                                                                        , "last_update"     => time()
                                                                    );
            }
        }

        return $setting;
    }

    public function cacheIsValid(string $source_file, string $cache_file = null) : bool
    {
        return $cache_file
            && (!Kernel::$Environment::DISABLE_CACHE || filemtime($cache_file) >= filemtime($source_file));
    }
    /**
     * @param string|null $filename
     * @return string|null
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
                    if (!$this->cacheIsValid($this->basepath . $this->filesource, $final_file_stored)) {
                        $this->createImage($modeCurrent);
                        Hook::handle("media_on_create_image", $final_file);
                    }
                } elseif (!$modeCurrent && is_file($this->basepath . $this->filesource)) {
                    if (!$this->cacheIsValid($this->basepath . $this->filesource, $final_file_stored)) {
                        $this->saveFromOriginal($this->basepath . $this->filesource, $final_file);
                    }
                } else {
                    $icon                                           = $this->getIconPath(basename($this->filesource), true);

                    if (!$this->cacheIsValid($this->basepath . $this->filesource, $final_file_stored) && $icon) {
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
     */
    private function saveFromOriginal(string $source, string $destination) : void
    {
        Filemanager::makeDir($destination, 0775, $this->basepathCache());

        if ($this->pathinfo["render"] == static::RENDER_ASSETS_PATH) {
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
     */
    private function setNoImg(string $mode = null, string $icon_name = null) : bool
    {
        if (!$icon_name) {
            $icon_name                                              = (
                isset($this->pathinfo["extension"])
                ? $this->pathinfo["extension"]
                : $this->pathinfo["basename"]
            );
        }
        if (!$mode) {
            $mode                                                   = self::getModeByNoImg($this->pathinfo["basename"]);
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
        $this->headers["filename"]                                  = $this->pathinfo["basename"];
        $this->headers["mimetype"]                                  = $this::getMimeByFilename($final_file);

        if ($code) {
            Response::httpCode($code);
        }
        //todo: https://local.hcore.app/assets/images/nobrand-100x50.png non funziona cancellando la cache
        $this->sendHeaders($final_file, $this->headers);
        readfile($final_file);
        exit;
    }

    /**
     * @param array $source
     * @param array $image
     * @param string $sep
     * @param string|null $mode
     * @return string
     */
    private function overrideSrcPath(array &$source, array $image, string $sep, string $mode = null) : string
    {
        $file 					                                    = explode("-" . $sep . "-", $image["filename"]);
        $source["extension"] 	                                    = $sep;
        $source["filename"] 	                                    = $file[0];

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
        $image                                                      = $this->pathinfo;

        $source["dirname"] 			                                = ($image["dirname"] == DIRECTORY_SEPARATOR ? "" : $image["dirname"]);
        $source["extension"] 		                                = $image["extension"];
        $source["filename"] 	                                    = $image["filename"];

        if (strpos($image["filename"], "-png-") !== false) {
            $mode                                                   = $this->overrideSrcPath($source, $image, "png", $mode);
        } elseif (strpos($image["filename"], "-jpg-") !== false) {
            $mode                                                   = $this->overrideSrcPath($source, $image, "jpg", $mode);
        } elseif (strpos($image["filename"], "-jpeg-") !== false) {
            $mode                                                   = $this->overrideSrcPath($source, $image, "jpeg", $mode);
        } elseif (!$mode) {
            $res                                                    = $this->getModeByFile($source["dirname"] . DIRECTORY_SEPARATOR . $image["filename"] . "." . $source["extension"]);
            if ($res) {
                $source["filename"]                                 = $res["filename"];
                $mode                                               = $res["mode"];
            } else {
                $mode                                               = false;
            }
        }

        if ($source["filename"] && $source["extension"]) {
            $source["basename"] 	                                = $source["filename"] . "." . $source["extension"];
            $this->source                                           = $source;
            $this->mode                                             = $mode;
            $this->filesource 				                        = $source["dirname"] . DIRECTORY_SEPARATOR . $source["basename"];
        }
    }
}
