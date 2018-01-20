<?php

namespace ZipkinOpenTracing;

use OpenTracing\Ext\Tags;
use OpenTracing\Span as OTSpan;
use OpenTracing\SpanContext;
use Zipkin\Span as ZipkinSpan;
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
     * @inheritdoc
     */
    public function getOperationName()
    {
        return $this->operationName;
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function finish($finishTime = null, array $logRecords = [])
    {
        $this->span->finish($finishTime ?: Timestamp\now());
    }

    /**
     * @inheritdoc
     */
    public function overwriteOperationName($newOperationName)
    {
        $this->operationName = $newOperationName;
        $this->span->setName($newOperationName);
    }

    /**
     * @inheritdoc
     */
    public function setTags(array $tags)
    {
        foreach ($tags as $key => $value) {
            if ($key === Tags\SPAN_KIND) {
                $this->span->setKind($value);
            } else {
                $this->span->tag($key, $value);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function log(array $fields = [], $timestamp = null)
    {
        foreach ($fields as $field) {
            $this->span->annotate($field, $timestamp);
        }
    }

    /**
     * @inheritdoc
     */
    public function addBaggageItem($key, $value)
    {
        $this->context = $this->context->withBaggageItem($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function getBaggageItem($key)
    {
        return $this->context->getBaggageItem($key);
    }
}
