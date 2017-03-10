<?php

namespace Stahlstift\Getty;

use stdClass;

class Response
{

    /**
     * @var string
     */
    private $body = '';
    /**
     * @var array
     */
    private $header = [];
    /**
     * @var int
     */
    private $code = 0;

    /**
     * @param string $body
     * @param array $header
     * @param int $code
     */
    public function __construct($body, array $header, $code)
    {
        $this->body = $body;
        $this->header = $header;
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param bool $assoc
     *
     * @return array|stdClass
     */
    public function getBodyJSONDecoded($assoc = true)
    {
        return json_decode($this->body, $assoc);
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

}
