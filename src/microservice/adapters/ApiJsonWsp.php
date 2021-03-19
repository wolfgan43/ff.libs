<?php
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\util\ServerManager;
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
    use ServerManager;

    private const METHOD_ALLOWED                    = [
                                                        Request::METHOD_GET,
                                                        Request::METHOD_POST,
                                                        Request::METHOD_PUT,
                                                        Request::METHOD_PATCH,
                                                        Request::METHOD_DELETE
                                                    ];

    protected const ERROR_RESPONSE_INVALID_FORMAT   = "Response is not a valid Json";
    private const ERROR_METHOD_NOT_SUPPORTED        = "Request Method not Supported";

    protected $user_agent                           = null;
    protected $cookie                               = null;

    /**
     * @param string $method
     * @param array $arguments
     * @return object
     * @throws Exception
     */
    public function __call(string $method, array $arguments) : object
    {
        $this->endpoint                         .= DIRECTORY_SEPARATOR . $method;

        return parent::__call($method, $arguments);
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

        $this->user_agent                       = $this->userAgent();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function preflight() : ?array
    {
        return FilemanagerWeb::fileGetContentsWithHeaders(
            $this->endpoint(),
            null,
            Request::METHOD_HEAD
        );
    }

    /**
     * @param array|null $params
     * @param array|null $headers
     * @return stdClass|array|null
     * @throws Exception
     * @todo da tipizzare
     */
    protected function get(array $params = null, array $headers = null)
    {
        $this->protocol         = $this->protocol();

        $discover               = $this->discover();
        $this->request_method   = $discover["Access-Control-Allow-Methods"] ?? $this->requestMethod();
        if (isset($discover["Strict-Transport-Security"])) {
            $this->protocol     = self::PROTOCOL;
        }

        if (!in_array($this->request_method, self::METHOD_ALLOWED)) {
            throw new Exception(self::ERROR_METHOD_NOT_SUPPORTED, 501);
        }

        return FilemanagerWeb::fileGetContentsJson(
            $this->endpoint(),
            $params,
            $this->request_method,
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
