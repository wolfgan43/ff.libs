<?php
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\Request;
use phpformsframework\libs\storage\Filemanager;
use stdClass;
use Exception;

/**
 * Class Rest
 * @package hcore\adapters
 */
class ApiJsonWsp extends ApiAdapter
{
    protected const ERROR_RESPONSE_INVALID_FORMAT = "Response is not a valid Json";

    protected $method                           = Request::METHOD_POST;

    private $timeout                            = self::REQUEST_TIMEOUT;
    private $user_agent                         = null;
    private $cookie                             = null;
    private $https                              = null;

    /**
     * @param string $method
     * @param array $arguments
     * @return object
     * @throws Exception
     */
    public function __call(string $method, array $arguments) : object
    {
        $this->endpoint                         .= "/" . $method;

        $arguments["action"]                    = $method;
        return parent::__call($this->method, $arguments);
    }

    /**
     * JsonWsp constructor.
     * @param string $url
     * @param string|null $username
     * @param string|null $secret
     */
    public function __construct(string $url = null, string $username = null, string $secret = null)
    {
        parent::__construct($url, $username, $secret);

        $this->user_agent                       = Request::userAgent();
        $this->https                            = false;
    }

    /**
     * @return array
     */
    public function preflight() : ?array
    {
        if (!isset(self::$preflight[$this->endpoint])) {
            self::$preflight[$this->endpoint]   = Filemanager::fileGetContentWithHeaders(
                $this->endpoint,
                null,
                Request::METHOD_HEAD
            );
        }

        return self::$preflight[$this->endpoint];
    }

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @return stdClass|array|null
     * @throws Exception
     * @todo da tipizzare
     */
    protected function get(string $method, array $params = null, array $headers = null)
    {
        return Filemanager::fileGetContentJson(
            $this->endpoint,
            $params,
            $method,
            $this->timeout,
            $this->https,
            $this->user_agent,
            $this->cookie,
            $this->http_auth_username,
            $this->http_auth_secret,
            $this->getHeader($headers)
        );
    }

    /**
     * @param string $method
     * @return array
     */
    protected function getResponseSchema(string $method) : ?array
    {
        return null;
    }

    /**
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout) : self
    {
        $this->timeout                          = $timeout;

        return $this;
    }

    /**
     * @param string $user_agent
     * @return self
     */
    public function setUserAgent(string $user_agent) : self
    {
        $this->user_agent                       = $user_agent;

        return $this;
    }

    /**
     * @param string $cookie
     * @return self
     */
    public function setCookie(string $cookie) : self
    {
        $this->cookie                           = $cookie;

        return $this;
    }

    /**
     * @param string $username
     * @param string $secret
     * @return self
     */
    public function setHttpAuth(string $username, string $secret) : self
    {
        $this->http_auth_username               = $username;
        $this->http_auth_secret                 = $secret;

        return $this;
    }

    /**
     * @param bool $enable
     * @return self
     */
    public function setHttps(bool $enable) : self
    {
        $this->https                            = $enable;

        return $this;
    }
}
