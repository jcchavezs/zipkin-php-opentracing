# Zipkin OpenTracing PHP

[![Travis CI](https://travis-ci.org/jcchavezs/zipkin-opentracing-php.svg?branch=master)](https://travis-ci.org/jcchavezs/zipkin-opentracing-php)
[![OpenTracing Badge](https://img.shields.io/badge/OpenTracing-enabled-blue.svg)](http://opentracing.io)
[![Total Downloads](https://poser.pugx.org/jcchavezs/zipkin-opentracing/downloads)](https://packagist.org/packages/jcchavezs/zipkin-opentracing)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/jcchavezs/zipkin-opentracing.svg)](https://github.com/jcchavezs/zipkin-opentracing-php/blob/master/LICENSE)

OpenTracingTracer implementation for [Zipkin](http://zipkin.io) in PHP.

This library allows OpenTracing API consumers to use Zipkin as their tracing backend.
For details on how to work with spans and traces we suggest looking at the documentation
and README from the [OpenTracing API](https://github.com/opentracing/opentracing-php).

## Getting started

### Required Reading

In order to understand OpenTracing API, one must first be familiar with the [OpenTracing project](http://opentracing.io) and [terminology](http://opentracing.io/spec/) more generally.

To understand how Zipkin and Brave work, you can look at [Zipkin Architecture](http://zipkin.io/pages/architecture.html) and [Zipkin Api](https://github.com/jcchavezs/zipkin-php) documentation.

### Installation

```bash
composer require jcchavezs/zipkin-opentracing
```

### Setup

Firstly, we need to setup a tracer:

```php
use GuzzleHttp\Client;
use OpenTracing\GlobalTracer;
use Psr\Log\NullLogger;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;
use Zipkin\Reporters\HttpLogging;

$endpoint = Endpoint::create('my_service', '127.0.0.1', null, 8081);
$client = new Client();
$logger = new NullLogger();

$reporter = new HttpLogging($client, $logger);
$sampler = BinarySampler::createAsAlwaysSample();
$tracing = TracingBuilder::create()
	->havingLocalEndpoint($endpoint)
   ->havingSampler($sampler)
   ->havingReporter($reporter)
   ->build();

$zipkinTrcer = new ZipkinOpenTracing\Tracer($tracing);

GlobalTracer::set($tracer);
```