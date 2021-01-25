<?php
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\Request;
use phpformsframework\libs\storage\FilemanagerWeb;
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

    protected $user_agent                       = null;
    protected $cookie                           = null;

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
     * @param string|null $url
     * @param string|null $username
     * @param string|null $secret
     */
    public function __construct(string $url = null, string $username = null, string $secret = null)
    {
        parent::__construct($url, $username, $secret);

        $this->user_agent                       = Request::userAgent();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function preflight() : ?array
    {
        if (!isset(self::$preflight[$this->endpoint])) {
            self::$preflight[$this->endpoint]   = FilemanagerWeb::fileGetContentsWithHeaders(
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
        return FilemanagerWeb::fileGetContentsJson(
            $this->endpoint,
            $params,
            $method,
            $this->timeout,
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
}
