<?php

require "vendor/autoload.php";

use EventLoop\EventLoop;
use Rxnet\Redis\Redis;

require "API.php";

$api = new API();
$redis = new Redis();

$loop = EventLoop::getLoop();
$scheduler = new \Rx\Scheduler\EventLoopScheduler($loop);

$api->getKitties(0)
    ->flatMap(function($data) use ($redis, $api) {
        $total = $data['total'] ?? null;

        if ($total === null) {
            throw new Exception("No result from Crypto Kitties API");
        }
        echo "{$total} Kitties\n";

        return \Rx\Observable::range(1, $total)
            ->flatMap(function($number) use ($api) {
                echo "Call /kitties/$number\n";

                return $api->getKitten($number)
                    ->map(function($data) {
                        return [
                            "key" => $data['id'],
                            "value" => $data['image_url']
                        ];
                    })
                ;
            })
            ->doOnNext(function(array $data) use ($redis) {
                return $redis
                    ->connect('localhost:6379')
                    ->doOnNext(function() use($redis, $data) {
                        echo "Save {$data['key']} to Redis\n";
                        $redis->set($data['key'], $data['value'])->subscribeCallback();
                    })
                    ->subscribeCallback()
                ;
            })
        ;
    })
    ->subscribeCallback(null,null, null, $scheduler)
;