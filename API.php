<?php

use Rx\Observable;
use Rxnet\Http\Http;
use Rxnet\Operator\RetryWithDelay;

class API
{
    private $http;
    private $endpoint;

    public function __construct($endpoint = "https://api.cryptokitties.co")
    {
        $this->http = new Http();
        $this->endpoint = $endpoint;
    }

    public function getKitties(int $limit = 20, int $offset = 0): Observable
    {
        return $this->call("/kitties?limit={$limit}&offset={$offset}");
    }

    public function getKitten(int $id): Observable
    {
        return $this->call("/kitties/{$id}");
    }

    private function call(string $uri, int $timeout = 10000, int $retry = 5): Observable
    {
        return $this->http->get($this->endpoint . $uri)
            ->timeout($timeout)
            ->retryWhen(new RetryWithDelay($retry, random_int(1000, $timeout)))
            ->map(function(Psr\Http\Message\ResponseInterface $response) {
                return json_decode((string) $response->getBody(), true);
            })
            ->doOnError(function(\Exception $e) use ($uri) {
                echo "\nError on {$uri} \n";
                echo $e->getMessage();
            })
        ;
    }
}