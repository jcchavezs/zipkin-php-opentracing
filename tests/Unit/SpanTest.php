<?php

namespace ZipkinOpenTracingTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Span as ZipkinSpan;
use Zipkin\TraceContext;
use ZipkinOpenTracing\Span;

final class SpanTest extends PHPUnit_Framework_TestCase
{
    const OPERATION_NAME = 'test_name';

    public function testASpanIsCreatedAndHasTheExpectedValues()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $zipkinSpan = $this->prophesize(ZipkinSpan::class);
        $zipkinSpan->getContext()->willReturn($context);
        $span = Span::create(self::OPERATION_NAME, $zipkinSpan->reveal());
        $this->assertEquals($span->getOperationName(), self::OPERATION_NAME);
    }
}