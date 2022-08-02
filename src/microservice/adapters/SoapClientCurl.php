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
namespace ff\libs\microservice\adapters;

if (!class_exists("SoapClient")) {
    /**
     * Class SoapClientCurl
     * @package ff\libs\microservice\adapters
     */
    class SoapClientCurl
    {
    }
    return null;
}

use ff\libs\Kernel;
use ff\libs\util\Normalize;
use SoapClient;
use SoapFault;

/**
 * Class SoapClientCurl
 * @package ff\libs\microservice\adapters
 */
class SoapClientCurl extends SoapClient
{
    private const ENCODING                  = "UTF-8";
    private const ERROR_RESPONSE_IS_EMPTY  = 'Response is Empty';

    public $__action                        = null;
    private $_login                         = null;
    private $_password                      = null;


    public function __construct(string $wsdl = null, array $options = [])
    {
        parent::__construct($wsdl, $options);
        $this->_login                       = $options["login"]     ?? null;
        $this->_password                    = $options["password"]  ?? null;
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $one_way
     * @return string
     * @throws SoapFault
     */
    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false) : ?string
    {
        return $this->cUrl($request, $location, $action);
    }

    /**
     * @param string $requestXML
     * @param string $location
     * @param string $action
     * @return string
     * @throws SoapFault
     */
    private function cUrl(string $requestXML, string $location, string $action) : ?string
    {
        $ch = curl_init();

        $this->setAction($action);

        curl_setopt($ch, CURLOPT_URL, $location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXML);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!empty($this->_login) && !empty($this->_password)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->_login . ":" . $this->_password);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Kernel::$Environment::SSL_VERIFYPEER);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, Kernel::$Environment::SSL_VERIFYHOST);

        $headers = array();
        $headers[] = 'Content-Type: text/xml; charset=' . (empty($this->_encoding) ? self::ENCODING : $this->_encoding);
        $headers[] = 'Soapaction: ' . $action;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $res = curl_exec($ch);
        Normalize::removeBom($res);

        $httpCode = curl_getinfo($ch , CURLINFO_HTTP_CODE); // this results 0 every time
        if (curl_errno($ch)) {
            throw new SoapFault($httpCode, curl_error($ch));
        } elseif (empty($res)) {
            throw new SoapFault($httpCode, self::ERROR_RESPONSE_IS_EMPTY . ": " . $httpCode);
        } elseif (substr($res, 0, 1) != "<") {
            throw new SoapFault($httpCode, $res);
        }

        curl_close($ch);

        return $res ?: null;
    }

    /**
     * @param string|null $action
     * @return void
     */
    private function setAction(string &$action = null) : void
    {
        if (!$action) {
            $action = $this->__action;
        }
    }
}
