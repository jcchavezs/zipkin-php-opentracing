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
    $endpoint = Endpoint::create('frontend', '127.0.0.3', null, 1234);
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

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$carrier = array_map(function ($header) {
    return $header[0];
}, $request->headers->all());

$traceContext = $tracer->extract(Tracer::FORMAT_HTTP_HEADERS, $carrier);

$span = $tracer->startSpan('http_request', [
    'child_of' => $traceContext
]);

$span->log([Annotation::SERVER_RECEIVE => Timestamp\now()]);

usleep(100);

$childSpan = $tracer->startSpan('user:get_list:mysql_query', [
    'child_of' => $span
]);

$carrier = HttpHeaders::fromHeaders([]);

$childSpan->setTags([Annotation::SERVER_SEND => Timestamp\now()]);

$childSpan->finish();

$span->finish();

register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
