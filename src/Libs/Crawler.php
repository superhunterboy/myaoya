<?php

namespace Weiming\Libs;

use GuzzleHttp\Client;

class Crawler
{
    private $client;

    private static $_instance = null;

    private function __construct()
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$_instance) || isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function updatePayOutData($url, $params = [])
    {
        $response = $this->client->request('POST', $url, [
            'form_params' => $params,
        ]);
        if ($response->getStatusCode() == '200') {
            return $response->getBody()->getContents();
        }
        return null;
    }

}
