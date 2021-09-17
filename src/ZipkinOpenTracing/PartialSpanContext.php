<?php

namespace ZipkinOpenTracing;

use Zipkin\Propagation\SamplingFlags;
use OpenTracing\SpanContext as OTSpanContext;
use ArrayIterator;

/**
 * Used to wrap SamplingFlags coming from extractor
 */
final class PartialSpanContext implements OTSpanContext, WrappedTraceContext
{
    private SamplingFlags $samplingFlags;

    private array $baggageItems;

    private function __construct(SamplingFlags $samplingFlags, array $baggageItems = [])
    {
        $this->samplingFlags = $samplingFlags;
        $this->baggageItems = $baggageItems;
    }

    public static function fromSamplingFlags(SamplingFlags $samplingFlags): self
    {
        return new self($samplingFlags);
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
        return new self($this->samplingFlags, [$key => $value] + $this->baggageItems);
    }

    /**
     * @inheritdoc
     */
    public function getContext(): SamplingFlags
    {
        return $this->samplingFlags;
    }
}
