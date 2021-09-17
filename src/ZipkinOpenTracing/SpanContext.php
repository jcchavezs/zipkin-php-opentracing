<?php

namespace ZipkinOpenTracing;

use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;
use OpenTracing\SpanContext as OTSpanContext;
use ArrayIterator;

final class SpanContext implements OTSpanContext, WrappedTraceContext
{
    private TraceContext $traceContext;

    private array $baggageItems;

    private function __construct(TraceContext $traceContext, array $baggageItems = [])
    {
        $this->traceContext = $traceContext;
        $this->baggageItems = $baggageItems;
    }

    /**
     * @param TraceContext $traceContext
     * @return SpanContext
     */
    public static function fromTraceContext(TraceContext $traceContext): self
    {
        return new self($traceContext);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->baggageItems);
    }

    /**
     * @inheritdoc
     */
    public function getBaggageItem(string $key): ?string
    {
        return \array_key_exists($key, $this->baggageItems) ? $this->baggageItems[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function withBaggageItem(string $key, string $value): OTSpanContext
    {
        return new self($this->traceContext, [$key => $value] + $this->baggageItems);
    }

    /**
     * @inheritdoc
     */
    public function getContext(): SamplingFlags
    {
        return $this->traceContext;
    }
}
