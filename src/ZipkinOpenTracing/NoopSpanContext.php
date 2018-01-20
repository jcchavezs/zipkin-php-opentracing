<?php

namespace ZipkinOpenTracing;

use ArrayIterator;
use Zipkin\Propagation\DefaultSamplingFlags;
use OpenTracing\SpanContext as OTSpanContext;

class NoopSpanContext implements OTSpanContext, WrappedTraceContext
{
    public static function create()
    {
        return new self();
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return DefaultSamplingFlags::createAsEmpty();
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new ArrayIterator([]);
    }

    /**
     * @inheritdoc
     */
    public function getBaggageItem($key)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function withBaggageItem($key, $value)
    {
        return $this;
    }
}
