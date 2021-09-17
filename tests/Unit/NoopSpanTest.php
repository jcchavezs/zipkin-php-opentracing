<?php

namespace ZipkinOpenTracing\Tests\Unit;

use Zipkin\Span as ZipkinSpan;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\DefaultSamplingFlags;
use ZipkinOpenTracing\SpanContext;
use ZipkinOpenTracing\NoopSpan;
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\TestCase;

final class NoopSpanTest extends TestCase
{
    use ProphecyTrait;

    public const TAG_KEY = 'test_key';

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
