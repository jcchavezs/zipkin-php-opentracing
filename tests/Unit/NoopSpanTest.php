<?php

namespace ZipkinOpenTracing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\TraceContext;
use Zipkin\Span as ZipkinSpan;
use ZipkinOpenTracing\NoopSpan;
use ZipkinOpenTracing\SpanContext;

final class NoopSpanTest extends TestCase
{
    const TAG_KEY = 'test_key';

    public function testANoopSpanIsCreatedAndHasTheExpectedValues()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $zipkinSpan = $this->prophesize(ZipkinSpan::class);
        $zipkinSpan->getContext()->willReturn($context);
        $noopSpan = NoopSpan::create($zipkinSpan->reveal());
        $this->assertEquals('', $noopSpan->getOperationName());
    }

    public function testNoopSpanWithGetContext()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $zipkinSpan = $this->prophesize(ZipkinSpan::class);
        $zipkinSpan->getContext()->willReturn($context);
        $noopSpan = NoopSpan::create($zipkinSpan->reveal());
        $this->assertInstanceOf(SpanContext::class, $noopSpan->getContext());
    }

    public function testNoopSpanWithGetBaggageItem()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $zipkinSpan = $this->prophesize(ZipkinSpan::class);
        $zipkinSpan->getContext()->willReturn($context);
        $noopSpan = NoopSpan::create($zipkinSpan->reveal());
        $this->assertEquals('', $noopSpan->getBaggageItem(self::TAG_KEY));
    }
}
