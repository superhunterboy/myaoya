<?php

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
], [
    'prefix' => 'sample:'
]);

// for ($i = 0; $i < 100; $i++) {
//     $key = 'test#' . $i;
//     $client->set($key, $i);
//     $client->expire($key, 36);
//     // $client->expireat($key, strtotime("+1 week"));
// }

for ($i = 0; $i < 100; $i++) {
    $key = 'test#' . $i;
    // var_dump($client->exists($key), $client->get($key));
    if ($client->exists($key)) {
        echo "Exists\n";
    } else {
        echo "No exists\n";
    }
}

// $ttl = $client->ttl("expire in 1 week");
