<?php

namespace ZipkinOpenTracing;

use OpenTracing\Ext\Tags;
use OpenTracing\Span as OTSpan;
use Zipkin\Span as ZipkinSpan;
use OpenTracing\SpanContext;
use Zipkin\Timestamp;
use ZipkinOpenTracing\SpanContext as ZipkinOpenTracingContext;

final class Span implements OTSpan
{
    /**
     * @var ZipkinSpan
     */
    private $span;

    /**
     * @var string
     */
    private $operationName;

    /**
     * @var SpanContext
     */
    private $context;

    /**
     * @param $operationName
     * @param ZipkinSpan $span
     */
    private function __construct($operationName, ZipkinSpan $span)
    {
        $this->operationName = $operationName;
        $this->span = $span;
        $this->context = ZipkinOpenTracingContext::fromTraceContext($span->getContext());
    }

    /**
     * @param string $operationName
     * @param ZipkinSpan $span
     * @return Span
     */
    public static function create($operationName, ZipkinSpan $span)
    {
        return new self($operationName, $span);
    }

    /**
     * @return string
     */
    public function getOperationName()
    {
        return $this->operationName;
    }

    /**
     * Yields the SpanContext for this Span. Note that the return value of
     * Span::getContext() is still valid after a call to Span::finish(), as is
     * a call to Span::getContext() after a call to Span::finish().
     *
     * @return SpanContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the end timestamp and finalizes Span state.
     *
     * With the exception of calls to Context() (which are always allowed),
     * finish() must be the last call made to any span instance, and to do
     * otherwise leads to undefined behavior
     *
     * If the span is already finished, a warning should be logged.
     *
     * @param float|int|\DateTimeInterface|null $finishTime if passing float or int
     * it should represent the timestamp (including as many decimal places as you need)
     * @param array $logRecords
     */
    public function finish($finishTime = null, array $logRecords = [])
    {
        $this->span->finish($finishTime ?: Timestamp\now());
    }

    /**
     * If the span is already finished, a warning should be logged.
     *
     * @param string $newOperationName
     */
    public function overwriteOperationName($newOperationName)
    {
        $this->operationName = $newOperationName;
        $this->span->setName($newOperationName);
    }

    /**
     * Sets tags to the Span in key:value format, key must be a string and tag must be either
     * a string, a boolean value, or a numeric type.
     *
     * As an implementor, consider using "standard tags" listed in {@see \OpenTracing\Ext\Tags}
     *
     * If the span is already finished, a warning should be logged.
     *
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        foreach ($tags as $key => $value) {
            if ($key === Tags\SPAN_KIND) {
                $this->span->setKind($key);
            } else {
                $this->span->tag($key, $value);
            }
        }
    }

    /**
     * Adds a log record to the span
     *
     * If the span is already finished, a warning should be logged.
     *
     * @param array $fields
     * @param int|float|\DateTimeInterface $timestamp
     */
    public function log(array $fields = [], $timestamp = null)
    {
        foreach($fields as $field) {
            $this->span->annotate($field, $timestamp);
        }
    }

    /**
     * Adds a baggage item to the SpanContext which is immutable so it is required to use SpanContext::withBaggageItem
     * to get a new one.
     *
     * If the span is already finished, a warning should be logged.
     *
     * @param string $key
     * @param string $value
     */
    public function addBaggageItem($key, $value)
    {
        $this->context = $this->context->withBaggageItem($key, $value);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getBaggageItem($key)
    {
        return $this->context->getBaggageItem($key);
    }
}