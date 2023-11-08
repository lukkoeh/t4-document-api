<?php

declare(strict_types=1);
namespace src;

use JetBrains\PhpStorm\NoReturn;

class ResponseController
{
    /**
     *
     * @param Response $response
     * @return void
     */
    #[NoReturn] public static function respondRaw(Response $response) : void {
        http_response_code($response->getCode());
        echo $response->getRaw();
        exit;
    }

    #[NoReturn] public static function respondJson(Response $response) : void {
        http_response_code($response->getCode());
        echo $response->getJson();
        exit;
    }
}