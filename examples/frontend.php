<?php

use GuzzleHttp\Client;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$tracer = build_tracer('frontend', '127.0.0.1');

\OpenTracing\GlobalTracer::set($tracer);

$span = $tracer->startSpan('http_request');

usleep(100 * random_int(1, 3));

$childSpan = $tracer->startSpan('users:get_list', [
    'child_of' => $span
]);

$headers = [];

$tracer->inject($span->getContext(), OpenTracing\Formats\TEXT_MAP, $headers);

$request = new \GuzzleHttp\Psr7\Request('POST', 'localhost:8002', $headers);

$client = new Client();
$response = $client->send($request);

echo $response->getBody();

$childSpan->finish();

usleep(100 * random_int(1, 3));

$span->finish();

register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
