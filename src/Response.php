<?php

namespace src;

class Response
{
    private $data;
    private $code;

    public function __construct($code, $data)
    {
        $this->code = $code;
        $this->data = $data;
    }

    public function getCode() : int
    {
        return $this->code;
    }

    public function getRaw() : string
    {
        return $this->data;
    }

    public function getJson() : string
    {
        return json_encode($this->data);
    }
}