<?php

namespace ZipkinOpenTracing;

use ArrayIterator;
use OpenTracing\SpanContext as OTSpanContext;
use Traversable;
use Zipkin\TraceContext;

final class SpanContext implements OTSpanContext
{
    private $traceContext;

    private $baggageItems;

    private function __construct(TraceContext $traceContext, array $baggageItems = [])
    {
        $this->traceContext = $traceContext;
        $this->baggageItems = $baggageItems;
    }

    public static function fromTraceContext(TraceContext $traceContext)
    {
        return new self($traceContext);
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->baggageItems);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getBaggageItem($key)
    {
        return array_key_exists($key, $this->baggageItems) ? $this->baggageItems[$key] : null;
    }

    /**
     * Creates a new SpanContext out of the existing one and the new key:value pair.
     *
     * @param string $key
     * @param string $value
     * @return OTSpanContext
     */
    public function withBaggageItem($key, $value)
    {
        return new self($this->traceContext, [$key => $value] + $this->baggageItems);
    }

    /**
     * @return TraceContext
     */
    public function getContext()
    {
        return $this->traceContext;
    }
}