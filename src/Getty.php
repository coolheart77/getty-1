<?php

namespace Stahlstift\Getty;

class Getty
{

    /**
     * @var string
     */
    private $defaultContentType = '';
    /**
     * @var int
     */
    private $defaultTimeout = 10;
    /**
     * @var array
     */
    private $defaultHTTPStreamOptions = [];
    /**
     * @var array
     */
    private $defaultHTTPHeader = [];
    /**
     * @var string|null
     */
    private $defaultBasicAuthUser = null;
    /**
     * @var string|null
     */
    private $defaultBasicAuthPassword = null;
    /**
     * @var Listener[]
     */
    private $listeners = [];

    /**
     * @param string $defaultContentType
     * @param int $defaultTimeout
     * @param array $defaultHTTPStreamOptions
     * @param array $defaultHTTPHeader
     * @param string|null $defaultBasicAuthUser
     * @param string|null $defaultBasicAuthPassword
     */
    public function __construct(
        $defaultContentType = 'application/json',
        $defaultTimeout = 10,
        array $defaultHTTPStreamOptions = [],
        array $defaultHTTPHeader = [],
        $defaultBasicAuthUser = null,
        $defaultBasicAuthPassword = null
    ) {
        $this->defaultContentType = $defaultContentType;
        $this->defaultTimeout = $defaultTimeout;
        $this->defaultHTTPStreamOptions = $defaultHTTPStreamOptions;
        $this->defaultHTTPHeader = $defaultHTTPHeader;
        $this->defaultBasicAuthUser = $defaultBasicAuthUser;
        $this->defaultBasicAuthPassword = $defaultBasicAuthPassword;
    }

    /**
     * @param int $defaultTimeout
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @param array $defaultHTTPStreamOptions
     */
    public function setDefaultHTTPStreamOptions(array $defaultHTTPStreamOptions)
    {
        $this->defaultHTTPStreamOptions = $defaultHTTPStreamOptions;
    }

    /**
     * @param array $defaultHTTPHeaders
     */
    public function setDefaultHTTPHeader(array $defaultHTTPHeaders)
    {
        $this->defaultHTTPHeader = $defaultHTTPHeaders;
    }

    /**
     * @param string $authUser
     * @param string $authPassword
     */
    public function setBasicAuth($authUser, $authPassword)
    {
        $this->defaultBasicAuthUser = $authUser;
        $this->defaultBasicAuthPassword = $authPassword;
    }

    /**
     * @param string $defaultContentType
     */
    public function setDefaultContentType($defaultContentType)
    {
        $this->defaultContentType = $defaultContentType;
    }

    /**
     * @param Listener $listener
     */
    public function addListener(Listener $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * @param int $id
     * @param string $url
     * @param array $streamOptions
     */
    private function notifyStart($id, $url, array $streamOptions)
    {
        if (!$this->listeners) {
            return;
        }

        $copy = $streamOptions;
        if ($this->defaultBasicAuthUser) {
            foreach ($copy['http']['header'] as &$header) {
                $header = preg_replace('/Authorization: Basic (.*)/', 'Authorization: Basic _removed_', $header);
            }
        }

        foreach ($this->listeners as $listener) {
            $listener->requestStart($id, $url, $copy);
        }
    }

    /**
     * @param int $id
     */
    private function notifyEnd($id)
    {
        if (!$this->listeners) {
            return;
        }

        foreach ($this->listeners as $listener) {
            $listener->requestEnd($id);
        }
    }

    /**
     * @param string $url
     * @param array $streamOptions
     *
     * @return Response
     */
    private function fire($url, array $streamOptions)
    {
        $randomId = mt_rand();

        $this->notifyStart($randomId, $url, $streamOptions);

        $result = @file_get_contents($url, false, stream_context_create($streamOptions));

        $this->notifyEnd($randomId);

        $responseCode = 0;
        $responseHeader = array();
        if (isset($http_response_header) && is_array($http_response_header) && count($http_response_header) > 0) {
            $responseHeader = $http_response_header;
            $matches = array();
            if (preg_match('/.*\s(\d+)\s.*/i', $responseHeader[0], $matches) !== false) {
                $responseCode = (int)$matches[1];
            }
        }

        return new Response(($result) ?: '', $responseHeader, $responseCode);
    }

    /**
     * @param string $method
     * @param array $header
     * @param string $content
     * @param int|null $timeout
     *
     * @return array
     */
    private function buildHttpOptions($method, array $header, $content, $timeout = null)
    {
        $httpOptions = $this->defaultHTTPStreamOptions;
        $httpOptions['method'] = $method;
        $httpOptions['header'] = $header;
        $httpOptions['content'] = $content;
        $httpOptions['timeout'] = ($timeout) ?: $this->defaultTimeout;
        $httpOptions['ignore_errors'] = true;

        return $httpOptions;
    }

    /**
     * @param string|null $contentType
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return array
     */
    private function buildHeaders($contentType = null, $authUser = null, $authPassword = null)
    {
        $header = $this->defaultHTTPHeader;

        $contentType = ($contentType) ?: $this->defaultContentType;
        $header[] = "Content-Type: $contentType";

        $basicAuthUser = ($authUser) ?: $this->defaultBasicAuthUser;
        $basicAuthPassword = ($authPassword) ?: $this->defaultBasicAuthPassword;
        if ($basicAuthUser !== null && $basicAuthPassword !== null) {
            $header[] = "Authorization: Basic " . base64_encode("$basicAuthUser:$basicAuthPassword");
        }

        return $header;
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $content
     * @param string|null $contentType
     * @param int|null $timeout
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return Response
     */
    public function request(
        $method,
        $url,
        $content = '',
        $contentType = null,
        $timeout = null,
        $authUser = null,
        $authPassword = null
    ) {
        $httpOptions = $this->buildHttpOptions(
            $method,
            $this->buildHeaders($contentType, $authUser, $authPassword),
            $content,
            $timeout
        );

        return $this->fire($url, ['http' => $httpOptions]);
    }

    /**
     * @param string $url
     * @param int|null $timeout
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return Response
     */
    public function get($url, $timeout = null, $authUser = null, $authPassword = null)
    {
        return $this->request("GET", $url, "", null, $timeout, $authUser, $authPassword);
    }

    /**
     * @param string $url
     * @param string $content
     * @param string|null $contentType
     * @param int|null $timeout
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return Response
     */
    public function post(
        $url,
        $content = '',
        $contentType = null,
        $timeout = null,
        $authUser = null,
        $authPassword = null
    ) {
        return $this->request("POST", $url, $content, $contentType, $timeout, $authUser, $authPassword);
    }

    /**
     * @param string $url
     * @param string $content
     * @param string|null $contentType
     * @param int|null $timeout
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return Response
     */
    public function put(
        $url,
        $content = '',
        $contentType = null,
        $timeout = null,
        $authUser = null,
        $authPassword = null
    ) {
        return $this->request("PUT", $url, $content, $contentType, $timeout, $authUser, $authPassword);
    }

    /**
     * @param string $url
     * @param string $content
     * @param string|null $contentType
     * @param int|null $timeout
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return Response
     */
    public function delete(
        $url,
        $content = '',
        $contentType = null,
        $timeout = null,
        $authUser = null,
        $authPassword = null
    ) {
        return $this->request("DELETE", $url, $content, $contentType, $timeout, $authUser, $authPassword);
    }

    /**
     * @param string $url
     * @param int|null $timeout
     * @param string|null $authUser
     * @param string|null $authPassword
     *
     * @return Response
     */
    public function head($url, $timeout = null, $authUser = null, $authPassword = null)
    {
        return $this->request("GET", $url, "", null, $timeout, $authUser, $authPassword);
    }
}
