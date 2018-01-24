<?php

namespace ZipkinOpenTracing\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Span as ZipkinSpan;
use Zipkin\Propagation\TraceContext;
use ZipkinOpenTracing\Span;

final class SpanTest extends PHPUnit_Framework_TestCase
{
    const OPERATION_NAME = 'test_name';
    const TEST_SPAN_KIND = 'kind';
    const TEST_TAG_KEY = 'test_key';
    const TEST_TAG_VALUE = 'test_value';

    public function testASpanIsCreatedAndHasTheExpectedValues()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $zipkinSpan = $this->prophesize(ZipkinSpan::class);
        $zipkinSpan->getContext()->willReturn($context);
        $span = Span::create(self::OPERATION_NAME, $zipkinSpan->reveal());
        $this->assertEquals($span->getOperationName(), self::OPERATION_NAME);
    }

    public function testTagASpanSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());

        $zipkinSpan = $this->prophesize(ZipkinSpan::class);
        $zipkinSpan->getContext()->willReturn($context);
        $zipkinSpan->setKind(strtoupper(self::TEST_SPAN_KIND))->shouldBeCalled();
        $zipkinSpan->tag(self::TEST_TAG_KEY, self::TEST_TAG_VALUE)->shouldBeCalled();

        $span = Span::create(self::OPERATION_NAME, $zipkinSpan->reveal());
        $span->setTags([
            'span.kind' => self::TEST_SPAN_KIND,
            self::TEST_TAG_KEY => self::TEST_TAG_VALUE
        ]);
    }
}
