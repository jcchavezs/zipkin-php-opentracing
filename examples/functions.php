<?php

use GuzzleHttp\Client;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

function build_tracer($serviceName, $ipv4, $port = null)
{
    $endpoint = Endpoint::create($serviceName, $ipv4, null, $port);
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
