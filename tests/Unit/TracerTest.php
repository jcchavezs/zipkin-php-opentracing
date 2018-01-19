<?php

namespace ZipkinOpenTracing\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\TracingBuilder;
use ZipkinOpenTracing\PartialSpanContext;
use ZipkinOpenTracing\SpanContext;
use ZipkinOpenTracing\Tracer;
use OpenTracing\Formats;
use Zipkin\Propagation\TraceContext;

final class TracerTest extends PHPUnit_Framework_TestCase
{
    const TEST_TRACE_ID = '48485a3953bb6124';
    const TEST_SPAN_ID = '48485a3953bb6125';
    const TEST_SAMPLED = '1';
    const TEST_DEBUG = '1';

    public function testExtractOfSamplingFlagsSuccess()
    {
        $tracing = TracingBuilder::create()->build();
        $tracer = new Tracer($tracing);
        $extractedContext = $tracer->extract(Formats\TEXT_MAP, [
            'x-b3-sampled' => self::TEST_SAMPLED,
            'x-b3-flags' => self::TEST_DEBUG,
        ]);

        $this->assertTrue($extractedContext instanceof PartialSpanContext);
        $this->assertTrue(
            $extractedContext->getContext()->isEqual(
                DefaultSamplingFlags::create(self::TEST_SAMPLED === '1', self::TEST_DEBUG === '1')
            )
        );
    }

    public function testExtractOfTraceContextSuccess()
    {
        $tracing = TracingBuilder::create()->build();
        $tracer = new Tracer($tracing);
        $extractedContext = $tracer->extract(Formats\TEXT_MAP, [
            'x-b3-traceid' => self::TEST_TRACE_ID,
            'x-b3-spanid' => self::TEST_SPAN_ID,
            'x-b3-sampled' => self::TEST_SAMPLED,
            'x-b3-flags' => self::TEST_DEBUG,
        ]);

        $this->assertTrue($extractedContext instanceof SpanContext);
        $this->assertTrue(
            $extractedContext->getContext()->isEqual(TraceContext::create(
                self::TEST_TRACE_ID,
                self::TEST_SPAN_ID,
                null,
                self::TEST_SAMPLED === '1',
                self::TEST_DEBUG === '1'
            ))
        );
    }
}
