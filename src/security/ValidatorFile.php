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

use ff\libs\Constant;

/**
 * Class ValidatorFile
 * @package ff\libs\security
 */
class ValidatorFile
{
    public const MIMETYPE                                   = array(
                                                                "3dm"       => "x-world/x-3dmf",
                                                                "3dmf"      => "x-world/x-3dmf",
                                                                "a"         => "application/octet-stream",
                                                                "aab"       => "application/x-authorware-bin",
                                                                "aam"       => "application/x-authorware-map",
                                                                "aas"       => "application/x-authorware-seg",
                                                                "abc"       => "text/vnd.abc",
                                                                "acgi"      => "text/html",
                                                                "afl"       => "video/animaflex",
                                                                "ai"        => "application/postscript",
                                                                "aif"       => "audio/aiff",
                                                                "aifc"      => "audio/aiff",
                                                                "aiff"      => "audio/aiff",
                                                                "aim"       => "application/x-aim",
                                                                "aip"       => "text/x-audiosoft-intra",
                                                                "ani"       => "application/x-navi-animation",
                                                                "aos"       => "application/x-nokia-9000-communicator-add-on-software",
                                                                "aps"       => "application/mime",
                                                                "arc"       => "application/octet-stream",
                                                                "arj"       => "application/arj",
                                                                "art"       => "image/x-jg",
                                                                "asf"       => "video/x-ms-asf",
                                                                "asm"       => "text/x-asm",
                                                                "asp"       => "text/asp",
                                                                "asx"       => "application/x-mplayer2",
                                                                "au"        => "audio/basic",
                                                                "avi"       => "application/x-troff-msvideo",
                                                                "avs"       => "video/avs-video",
                                                                "bcpio"     => "application/x-bcpio",
                                                                "bin"       => "application/mac-binary",
                                                                "bm"        => "image/bmp",
                                                                "bmp"       => "image/bmp",
                                                                "boo"       => "application/book",
                                                                "book"      => "application/book",
                                                                "boz"       => "application/x-bzip2",
                                                                "bsh"       => "application/x-bsh",
                                                                "bz"        => "application/x-bzip",
                                                                "bz2"       => "application/x-bzip2",
                                                                "txt"       => "text/plain",
                                                                "c"         => "text/plain",
                                                                "c++"       => "text/plain",
                                                                "cat"       => "application/vnd.ms-pki.seccat",
                                                                "cc"        => "text/plain",
                                                                "ccad"      => "application/clariscad",
                                                                "cco"       => "application/x-cocoa",
                                                                "cdf"       => "application/cdf",
                                                                "cer"       => "application/pkix-cert",
                                                                "cha"       => "application/x-chat",
                                                                "chat"      => "application/x-chat",
                                                                "class"     => "application/java",
                                                                "com"       => "application/octet-stream",
                                                                "conf"      => "text/plain",
                                                                "cpio"      => "application/x-cpio",
                                                                "cpp"       => "text/x-c",
                                                                "cpt"       => "application/mac-compactpro",
                                                                "crl"       => "application/pkcs-crl",
                                                                "crt"       => "application/pkix-cert",
                                                                "csh"       => "application/x-csh",
                                                                "css"       => "text/css",
                                                                "cxx"       => "text/plain",
                                                                "dcr"       => "application/x-director",
                                                                "deepv"     => "application/x-deepv",
                                                                "def"       => "text/plain",
                                                                "der"       => "application/x-x509-ca-cert",
                                                                "dif"       => "video/x-dv",
                                                                "dir"       => "application/x-director",
                                                                "dl"        => "video/dl",
                                                                "doc"       => "application/msword",
                                                                "docx"      => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                                                "dot"       => "application/msword",
                                                                "dotx"      => "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
                                                                "dp"        => "application/commonground",
                                                                "drw"       => "application/drafting",
                                                                "dump"      => "application/octet-stream",
                                                                "dv"        => "video/x-dv",
                                                                "dvi"       => "application/x-dvi",
                                                                "dwf"       => "drawing/x-dwf",
                                                                "dwg"       => "application/acad",
                                                                "dxf"       => "application/dxf",
                                                                "dxr"       => "application/x-director",
                                                                "el"        => "text/x-script.elisp",
                                                                "elc"       => "application/x-bytecode.elisp",
                                                                "env"       => "application/x-envoy",
                                                                "eps"       => "application/postscript",
                                                                "es"        => "application/x-esrehber",
                                                                "etx"       => "text/x-setext",
                                                                "evy"       => "application/envoy",
                                                                "exe"       => "application/octet-stream",
                                                                "f"         => "text/plain",
                                                                "f77"       => "text/x-fortran",
                                                                "f90"       => "text/plain",
                                                                "fdf"       => "application/vnd.fdf",
                                                                "fif"       => "application/fractals",
                                                                "fli"       => "video/fli",
                                                                "flo"       => "image/florian",
                                                                "flx"       => "text/vnd.fmi.flexstor",
                                                                "fmf"       => "video/x-atomic3d-feature",
                                                                "for"       => "text/plain",
                                                                "fpx"       => "image/vnd.fpx",
                                                                "frl"       => "application/freeloader",
                                                                "funk"      => "audio/make",
                                                                "g"         => "text/plain",
                                                                "g3"        => "image/g3fax",
                                                                "gif"       => "image/gif",
                                                                "gl"        => "video/gl",
                                                                "gsd"       => "audio/x-gsm",
                                                                "gsm"       => "audio/x-gsm",
                                                                "gsp"       => "application/x-gsp",
                                                                "gss"       => "application/x-gss",
                                                                "gtar"      => "application/x-gtar",
                                                                "gz"        => "application/x-compressed",
                                                                "gzip"      => "application/x-gzip",
                                                                "h"         => "text/plain",
                                                                "hdf"       => "application/x-hdf",
                                                                "help"      => "application/x-helpfile",
                                                                "hgl"       => "application/vnd.hp-hpgl",
                                                                "hh"        => "text/plain",
                                                                "hlb"       => "text/x-script",
                                                                "hlp"       => "application/hlp",
                                                                "hpg"       => "application/vnd.hp-hpgl",
                                                                "hpgl"      => "application/vnd.hp-hpgl",
                                                                "hqx"       => "application/binhex",
                                                                "hta"       => "application/hta",
                                                                "htc"       => "text/x-component",
                                                                "htm"       => "text/html",
                                                                "html"      => "text/html",
                                                                "htmls"     => "text/html",
                                                                "htt"       => "text/webviewhtml",
                                                                "htx"       => "text/html",
                                                                "ice"       => "x-conference/x-cooltalk",
                                                                "ico"       => "image/x-icon",
                                                                "idc"       => "text/plain",
                                                                "ief"       => "image/ief",
                                                                "iefs"      => "image/ief",
                                                                "iges"      => "application/iges",
                                                                "igs"       => "application/iges",
                                                                "ima"       => "application/x-ima",
                                                                "imap"      => "application/x-httpd-imap",
                                                                "inf"       => "application/inf",
                                                                "ins"       => "application/x-internett-signup",
                                                                "ip"        => "application/x-ip2",
                                                                "isu"       => "video/x-isvideo",
                                                                "it"        => "audio/it",
                                                                "iv"        => "application/x-inventor",
                                                                "ivr"       => "i-world/i-vrml",
                                                                "ivy"       => "application/x-livescreen",
                                                                "jam"       => "audio/x-jam",
                                                                "jav"       => "text/plain",
                                                                "java"      => "text/plain",
                                                                "jcm"       => "application/x-java-commerce",
                                                                "jpg"       => "image/jpeg",
                                                                "jpe"       => "image/jpeg",
                                                                "jpeg"      => "image/jpeg",
                                                                "jfif"      => "image/jpeg",
                                                                "jfif-tbnl" => "image/jpeg",
                                                                "jps"       => "image/x-jps",
                                                                "js"        => "application/x-javascript",
                                                                "jut"       => "image/jutvision",
                                                                "kar"       => "audio/midi",
                                                                "ksh"       => "application/x-ksh",
                                                                "la"        => "audio/nspaudio",
                                                                "lam"       => "audio/x-liveaudio",
                                                                "latex"     => "application/x-latex",
                                                                "lha"       => "application/lha",
                                                                "lhx"       => "application/octet-stream",
                                                                "list"      => "text/plain",
                                                                "lma"       => "audio/nspaudio",
                                                                "log"       => "text/plain",
                                                                "lsp"       => "application/x-lisp",
                                                                "lst"       => "text/plain",
                                                                "lsx"       => "text/x-la-asf",
                                                                "ltx"       => "application/x-latex",
                                                                "lzh"       => "application/octet-stream",
                                                                "lzx"       => "application/lzx",
                                                                "m"         => "text/plain",
                                                                "m1v"       => "video/mpeg",
                                                                "m2a"       => "audio/mpeg",
                                                                "m2v"       => "video/mpeg",
                                                                "m3u"       => "audio/x-mpequrl",
                                                                "man"       => "application/x-troff-man",
                                                                "map"       => "application/x-navimap",
                                                                "mar"       => "text/plain",
                                                                "mbd"       => "application/mbedlet",
                                                                "mc$"       => "application/x-magic-cap-package-1.0",
                                                                "mcd"       => "application/mcad",
                                                                "mcf"       => "image/vasa",
                                                                "mcp"       => "application/netmc",
                                                                "me"        => "application/x-troff-me",
                                                                "mht"       => "message/rfc822",
                                                                "mhtml"     => "message/rfc822",
                                                                "mid"       => "application/x-midi",
                                                                "midi"      => "application/x-midi",
                                                                "mif"       => "application/x-frame",
                                                                "mime"      => "message/rfc822",
                                                                "mjf"       => "audio/x-vnd.audioexplosion.mjuicemediafile",
                                                                "mjpg"      => "video/x-motion-jpeg",
                                                                "mm"        => "application/base64",
                                                                "mme"       => "application/base64",
                                                                "mod"       => "audio/mod",
                                                                "moov"      => "video/quicktime",
                                                                "mov"       => "video/quicktime",
                                                                "movie"     => "video/x-sgi-movie",
                                                                "mp2"       => "audio/mpeg",
                                                                "mp3"       => "audio/mpeg3",
                                                                "mpa"       => "audio/mpeg",
                                                                "mpc"       => "application/x-project",
                                                                "mpe"       => "video/mpeg",
                                                                "mpeg"      => "video/mpeg",
                                                                "mp4"       => "video/mp4",
                                                                "mpg"       => "audio/mpeg",
                                                                "mpga"      => "audio/mpeg",
                                                                "mpp"       => "application/vnd.ms-project",
                                                                "mpt"       => "application/x-project",
                                                                "mpv"       => "application/x-project",
                                                                "mpx"       => "application/x-project",
                                                                "mrc"       => "application/marc",
                                                                "ms"        => "application/x-troff-ms",
                                                                "mv"        => "video/x-sgi-movie",
                                                                "my"        => "audio/make",
                                                                "mzz"       => "application/x-vnd.audioexplosion.mzz",
                                                                "nap"       => "image/naplps",
                                                                "naplps"    => "image/naplps",
                                                                "nc"        => "application/x-netcdf",
                                                                "ncm"       => "application/vnd.nokia.configuration-message",
                                                                "nif"       => "image/x-niff",
                                                                "niff"      => "image/x-niff",
                                                                "nix"       => "application/x-mix-transfer",
                                                                "nsc"       => "application/x-conference",
                                                                "nvd"       => "application/x-navidoc",
                                                                "o"         => "application/octet-stream",
                                                                "oda"       => "application/oda",
                                                                "omc"       => "application/x-omc",
                                                                "omcd"      => "application/x-omcdatamaker",
                                                                "omcr"      => "application/x-omcregerator",
                                                                "p"         => "text/x-pascal",
                                                                "p10"       => "application/pkcs10",
                                                                "p12"       => "application/pkcs-12",
                                                                "p7a"       => "application/x-pkcs7-signature",
                                                                "p7c"       => "application/pkcs7-mime",
                                                                "p7m"       => "application/pkcs7-mime",
                                                                "p7r"       => "application/x-pkcs7-certreqresp",
                                                                "p7s"       => "application/pkcs7-signature",
                                                                "part"      => "application/pro_eng",
                                                                "pas"       => "text/pascal",
                                                                "pbm"       => "image/x-portable-bitmap",
                                                                "pcl"       => "application/vnd.hp-pcl",
                                                                "pct"       => "image/x-pict",
                                                                "pcx"       => "image/x-pcx",
                                                                "pdb"       => "chemical/x-pdb",
                                                                "pdf"       => "application/pdf",
                                                                "pfunk"     => "audio/make",
                                                                "pgm"       => "image/x-portable-graymap",
                                                                "pic"       => "image/pict",
                                                                "pict"      => "image/pict",
                                                                "pkg"       => "application/x-newton-compatible-pkg",
                                                                "pko"       => "application/vnd.ms-pki.pko",
                                                                "pl"        => "text/plain",
                                                                "plx"       => "application/x-pixclscript",
                                                                "pm"        => "image/x-xpixmap",
                                                                "pm4"       => "application/x-pagemaker",
                                                                "pm5"       => "application/x-pagemaker",
                                                                "png"       => "image/png",
                                                                "pnm"       => "application/x-portable-anymap",
                                                                "pot"       => "application/mspowerpoint",
                                                                "potx"      => "application/vnd.openxmlformats-officedocument.presentationml.template",
                                                                "pov"       => "model/x-pov",
                                                                "ppa"       => "application/vnd.ms-powerpoint",
                                                                "ppm"       => "image/x-portable-pixmap",
                                                                "pps"       => "application/mspowerpoint",
                                                                "ppsx"      => "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
                                                                "ppt"       => "application/mspowerpoint",
                                                                "pptx"      => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                                                                "ppz"       => "application/mspowerpoint",
                                                                "pre"       => "application/x-freelance",
                                                                "prt"       => "application/pro_eng",
                                                                "ps"        => "application/postscript",
                                                                "psd"       => "application/octet-stream",
                                                                "pvu"       => "paleovu/x-pv",
                                                                "pwz"       => "application/vnd.ms-powerpoint",
                                                                "py"        => "text/x-script.phyton",
                                                                "pyc"       => "applicaiton/x-bytecode.python",
                                                                "qcp"       => "audio/vnd.qcelp",
                                                                "qd3"       => "x-world/x-3dmf",
                                                                "qd3d"      => "x-world/x-3dmf",
                                                                "qif"       => "image/x-quicktime",
                                                                "qt"        => "video/quicktime",
                                                                "qtc"       => "video/x-qtc",
                                                                "qti"       => "image/x-quicktime",
                                                                "qtif"      => "image/x-quicktime",
                                                                "ra"        => "audio/x-pn-realaudio",
                                                                "ram"       => "audio/x-pn-realaudio",
                                                                "rar"       => "application/x-rar-compressed",
                                                                "ras"       => "application/x-cmu-raster",
                                                                "rast"      => "image/cmu-raster",
                                                                "rexx"      => "text/x-script.rexx",
                                                                "rf"        => "image/vnd.rn-realflash",
                                                                "rgb"       => "image/x-rgb",
                                                                "rm"        => "application/vnd.rn-realmedia",
                                                                "rmi"       => "audio/mid",
                                                                "rmm"       => "audio/x-pn-realaudio",
                                                                "rmp"       => "audio/x-pn-realaudio",
                                                                "rng"       => "application/ringing-tones",
                                                                "rnx"       => "application/vnd.rn-realplayer",
                                                                "roff"      => "application/x-troff",
                                                                "rp"        => "image/vnd.rn-realpix",
                                                                "rpm"       => "audio/x-pn-realaudio-plugin",
                                                                "rt"        => "text/richtext",
                                                                "rtf"       => "application/rtf",
                                                                "rtx"       => "application/rtf",
                                                                "rv"        => "video/vnd.rn-realvideo",
                                                                "s"         => "text/x-asm",
                                                                "s3m"       => "audio/s3m",
                                                                "saveme"    => "application/octet-stream",
                                                                "sbk"       => "application/x-tbook",
                                                                "scm"       => "application/x-lotusscreencam",
                                                                "sdml"      => "text/plain",
                                                                "sdp"       => "application/sdp",
                                                                "sdr"       => "application/sounder",
                                                                "sea"       => "application/sea",
                                                                "set"       => "application/set",
                                                                "sgm"       => "text/sgml",
                                                                "sgml"      => "text/sgml",
                                                                "sh"        => "application/x-bsh",
                                                                "shar"      => "application/x-bsh",
                                                                "shtml"     => "text/html",
                                                                "sid"       => "audio/x-psid",
                                                                "sit"       => "application/x-sit",
                                                                "skd"       => "application/x-koan",
                                                                "skm"       => "application/x-koan",
                                                                "skp"       => "application/x-koan",
                                                                "skt"       => "application/x-koan",
                                                                "sl"        => "application/x-seelogo",
                                                                "smi"       => "application/smil",
                                                                "smil"      => "application/smil",
                                                                "snd"       => "audio/basic",
                                                                "sol"       => "application/solids",
                                                                "spc"       => "application/x-pkcs7-certificates",
                                                                "spl"       => "application/futuresplash",
                                                                "spr"       => "application/x-sprite",
                                                                "sprite"    => "application/x-sprite",
                                                                "src"       => "application/x-wais-source",
                                                                "ssi"       => "text/x-server-parsed-html",
                                                                "ssm"       => "application/streamingmedia",
                                                                "sst"       => "application/vnd.ms-pki.certstore",
                                                                "step"      => "application/step",
                                                                "stl"       => "application/sla",
                                                                "stp"       => "application/step",
                                                                "sv4cpio"   => "application/x-sv4cpio",
                                                                "sv4crc"    => "application/x-sv4crc",
                                                                "svf"       => "image/vnd.dwg",
                                                                "svr"       => "application/x-world",
                                                                "swf"       => "application/x-shockwave-flash",
                                                                "t"         => "application/x-troff",
                                                                "talk"      => "text/x-speech",
                                                                "tar"       => "application/x-tar",
                                                                "tbk"       => "application/toolbook",
                                                                "tcl"       => "application/x-tcl",
                                                                "tcsh"      => "text/x-script.tcsh",
                                                                "tex"       => "application/x-tex",
                                                                "texi"      => "application/x-texinfo",
                                                                "texinfo"   => "application/x-texinfo",
                                                                "text"      => "text/plain",
                                                                "tgz"       => "application/gnutar",
                                                                "tif"       => "image/tiff",
                                                                "tiff"      => "image/tiff",
                                                                "tr"        => "application/x-troff",
                                                                "tsi"       => "audio/tsp-audio",
                                                                "tsp"       => "application/dsptype",
                                                                "tsv"       => "text/tab-separated-values",
                                                                "turbot"    => "image/florian",
                                                                "uil"       => "text/x-uil",
                                                                "uni"       => "text/uri-list",
                                                                "unis"      => "text/uri-list",
                                                                "unv"       => "application/i-deas",
                                                                "uri"       => "text/uri-list",
                                                                "uris"      => "text/uri-list",
                                                                "ustar"     => "application/x-ustar",
                                                                "uu"        => "application/octet-stream",
                                                                "uue"       => "text/x-uuencode",
                                                                "vcd"       => "application/x-cdlink",
                                                                "vcs"       => "text/x-vcalendar",
                                                                "vda"       => "application/vda",
                                                                "vdo"       => "video/vdo",
                                                                "vew"       => "application/groupwise",
                                                                "viv"       => "video/vivo",
                                                                "vivo"      => "video/vivo",
                                                                "vmd"       => "application/vocaltec-media-desc",
                                                                "vmf"       => "application/vocaltec-media-file",
                                                                "voc"       => "audio/voc",
                                                                "vos"       => "video/vosaic",
                                                                "vox"       => "audio/voxware",
                                                                "vqe"       => "audio/x-twinvq-plugin",
                                                                "vqf"       => "audio/x-twinvq",
                                                                "vql"       => "audio/x-twinvq-plugin",
                                                                "vrml"      => "application/x-vrml",
                                                                "vrt"       => "x-world/x-vrt",
                                                                "vsd"       => "application/x-visio",
                                                                "vst"       => "application/x-visio",
                                                                "vsw"       => "application/x-visio",
                                                                "w60"       => "application/wordperfect6.0",
                                                                "w61"       => "application/wordperfect6.1",
                                                                "w6w"       => "application/msword",
                                                                "wav"       => "audio/x-wav",
                                                                "wb1"       => "application/x-qpro",
                                                                "wbmp"      => "image/vnd.wap.wbmp",
                                                                "web"       => "application/vnd.xara",
                                                                "wiz"       => "application/msword",
                                                                "wk1"       => "application/x-123",
                                                                "wmf"       => "windows/metafile",
                                                                "wml"       => "text/vnd.wap.wml",
                                                                "wmlc"      => "application/vnd.wap.wmlc",
                                                                "wmls"      => "text/vnd.wap.wmlscript",
                                                                "wmlsc"     => "application/vnd.wap.wmlscriptc",
                                                                "word"      => "application/msword",
                                                                "wp"        => "application/wordperfect",
                                                                "wp5"       => "application/wordperfect",
                                                                "wp6"       => "application/wordperfect",
                                                                "wpd"       => "application/wordperfect",
                                                                "wq1"       => "application/x-lotus",
                                                                "wri"       => "application/mswrite",
                                                                "wrl"       => "application/x-world",
                                                                "wrz"       => "model/vrml",
                                                                "wsc"       => "text/scriplet",
                                                                "wsrc"      => "application/x-wais-source",
                                                                "wtk"       => "application/x-wintalk",
                                                                "xbm"       => "image/x-xbitmap",
                                                                "xdr"       => "video/x-amt-demorun",
                                                                "xgz"       => "xgl/drawing",
                                                                "xif"       => "image/vnd.xiff",
                                                                "xl"        => "application/excel",
                                                                "xla"       => "application/excel",
                                                                "xlb"       => "application/excel",
                                                                "xlc"       => "application/excel",
                                                                "xld"       => "application/excel",
                                                                "xlk"       => "application/excel",
                                                                "xll"       => "application/excel",
                                                                "xlm"       => "application/excel",
                                                                "xls"       => "application/excel",
                                                                "xlsx"      => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                                                "xlt"       => "application/excel",
                                                                "xltx"      => "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
                                                                "xlv"       => "application/excel",
                                                                "xlw"       => "application/excel",
                                                                "xm"        => "audio/xm",
                                                                "xml"       => "application/xml",
                                                                "xmz"       => "xgl/movie",
                                                                "xpix"      => "application/x-vnd.ls-xpix",
                                                                "xpm"       => "image/x-xpixmap",
                                                                "x-png"     => "image/png",
                                                                "xsr"       => "video/x-amt-showrun",
                                                                "xwd"       => "image/x-xwd",
                                                                "xyz"       => "chemical/x-pdb",
                                                                "z"         => "application/x-compress",
                                                                "zip"       => "application/x-compressed",
                                                                "zoo"       => "application/octet-stream",
                                                                "zsh"       => "text/x-script.zsh",
                                                                "eot"       => "application/vnd.ms-fontobject",
                                                                "ttf"       => "application/x-font-ttf",
                                                                "otf"       => "application/x-font-opentype",
                                                                "woff"      => "application/font-woff",
                                                                "woff2"      => "application/font-woff2",
                                                                "svg"       => "image/svg+xml",
                                                                "rss"       => "application/rss+xml",
                                                                "json"      => "application/json",
                                                                "webp"      => "image/webp",
                                                            );

    /**
     * https://en.wikipedia.org/wiki/List_of_file_signatures
     */
    private const SIGNATURES                                = array(
                                                                'image/jpeg'        => array(
                                                                                        'FFD8FFDB',
                                                                                        'FFD8FFE000104A4649460001',
                                                                                        'FFD8FFEE',
                                                                                        'FFD8FFE1....457869660000'
                                                                                    ),
                                                                'image/png'         => array(
                                                                                        '89504E470D0A1A0A'
                                                                                    ),
                                                                'application/pdf'   => array(
                                                                                        '255044462D'
                                                                                    )
                                                            );

    public function is()
    {
    }


    /**
     * @param string $value
     * @param string|null $mimetype_allowed
     * @return string|null
     */
    public static function check(string $value, string $mimetype_allowed = null) : ?string
    {
        $error                                                              = array();

        if (isset($_FILES[$value]) && $_FILES[$value] == UPLOAD_ERR_OK) {
            $names                                                          = (array) $_FILES[$value]["name"];
            if (!empty($names)) {
                foreach ($names as $name) {
                    if (!self::isFilePath($name)) {
                        $error[]                                            = $name . " is not valid path";
                    }
                }
            }

            $sizes                                                          = (array) $_FILES[$value]["size"];
            if (!empty($sizes)) {
                foreach ($sizes as $index => $size) {
                    if ($size > self::getMaxUploadSize()) {
                        $error[]                                            = $names[$index] . ": Upload Limit Exceeded";
                    }
                }
            }

            $types                                                          = (array) $_FILES[$value]["type"];
            if (!empty($types)) {
                $files                                                      = (array) $_FILES[$value]["tmp_name"];
                $mimeType                                                   = array_filter(explode(",", $mimetype_allowed));
                foreach ($types as $index => $type) {
                    if (!empty($mimeType) && array_search($type, $mimeType) === false) {
                        $error[]                                            = $names[$index] . ": MimeType not allowed. The permitted values are [" . $mimetype_allowed . "]";
                    }
                    if (!self::checkMimeType($type, pathinfo($names[$index], PATHINFO_EXTENSION))) {
                        $error[]                                            = $names[$index] . ": Wrong MimeType (" . $type . ")";
                    }
                    if (!self::checkMagicBytes($files[$index], $type)) {
                        $error[]                                            = $names[$index] . ": type mismatch";
                    }
                }
            }
        } elseif (!self::isFilePath($value)) {
            $error[]                                                        = $value . " is not valid path";
        }

        return (!empty($error)
            ? implode(", ", $error)
            : null
        );
    }

    /**
     * @param string $mimetype
     * @param string $ext
     * @return bool
     */
    private static function checkMimeType(string $mimetype, string $ext) : bool
    {
        return ($index = array_search($mimetype, self::MIMETYPE) !== false) && ($index == $ext);
    }

    /**
     * @param string $file
     * @param string $mimetype
     * @return bool
     */
    private static function checkMagicBytes(string $file, string $mimetype) : bool
    {
        $checks                                                             = self::getSignature($mimetype);
        $isValid                                                            = false;
        if (!empty($checks)) {
            $handle                                                         = @fopen($file, 'rb');
            if ($handle !== false && flock($handle, LOCK_EX)) {
                foreach ($checks as $check) {
                    fseek($handle, 0);
                    $byteCount                                              = strlen($check) / 2;
                    $contents                                               = fread($handle, $byteCount);
                    $byteArray                                              = bin2hex($contents);
                    $regex                                                  = '#' . $check . '#i';
                    $isValid                                                = (bool)preg_match($regex, $byteArray);
                    if ($isValid) {
                        break;
                    }
                }
                flock($handle, LOCK_UN);
            }
            @fclose($handle);
        } else {
            $isValid                                                        = true;
        }

        return $isValid;
    }

    /**
     * @param string $type
     * @return array|null
     */
    private static function getSignature(string $type) : ?array
    {
        return (self::SIGNATURES[$type] ?? null
        );
    }
    /**
     * @param string $value
     * @return bool
     */
    private static function isFilePath(string $value) : bool
    {
        if (strpos($value, Constant::DISK_PATH) === 0) {
            $res = false;
        } else {
            $res = !Validator::checkSpecialChars($value) && !preg_match('/[^A-Za-z0-9.\/\-_\s\$]/', $value);
        }
        return (bool) $res;
    }

    /**
     * Returns webserver max upload size in B/KB/MB/GB
     * @param string|null $return
     * @return int
     */
    public static function getMaxUploadSize(string $return = null) : int
    {
        $max_upload                                                         = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
        $max_upload                                                         = str_replace('M', '', $max_upload);
        switch ($return) {
            case "K":
                $res                                                        = $max_upload * 1024;
                break;
            case "M":
                $res                                                        = $max_upload;
                break;
            case "G":
                $res                                                        = (int) $max_upload / 1024;
                break;
            default:
                $res                                                        = $max_upload *1024 * 1024;
        }
        return $res;
    }

    public static function isInvalid(string $value) : bool
    {
        return !Validator::isFile($value);
    }
}
