<?php

use GuzzleHttp\Client;
use OpenTracing\Carriers\HttpHeaders;
use OpenTracing\Tracer;
use Zipkin\Annotation;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Timestamp;
use Zipkin\TracingBuilder;

require_once __DIR__ . '/vendor/autoload.php';

function build_tracer()
{
    $endpoint = Endpoint::create('frontend', '127.0.0.2', null, 2555);
    $client = new Client();
    $logger = new \Psr\Log\NullLogger();

    $reporter = new Zipkin\Reporters\HttpLogging($client, $logger);
    $sampler = BinarySampler::createAsAlwaysSample();
    $tracing = TracingBuilder::create()
        ->havingLocalEndpoint($endpoint)
        ->havingSampler($sampler)
        ->havingReporter($reporter)
        ->build();

    return new ZipkinOpenTracing\Tracer($tracing);
}

$tracer = build_tracer();

\OpenTracing\GlobalTracer::set($tracer);

$span = $tracer->startSpan('http_request');
$span->log([Annotation::CLIENT_START => Timestamp\now()]);

usleep(100 * random_int(1, 3));

$childSpan = $tracer->startSpan('users:get_list', [
    'child_of' => $span
]);

$carrier = HttpHeaders::fromHeaders([]);

$tracer->inject($span->getContext(), Tracer::FORMAT_HTTP_HEADERS, $carrier);

$request = new \GuzzleHttp\Psr7\Request('POST', 'localhost:8002', iterator_to_array($carrier));
$response = $client->send($request);

$childSpan->setTags([Annotation::CLIENT_RECEIVE => Timestamp\now()]);

$childSpan->finish();

$span->finish();

register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
