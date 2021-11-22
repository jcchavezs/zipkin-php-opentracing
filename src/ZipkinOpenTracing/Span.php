<?php

namespace ZipkinOpenTracing;

use Zipkin\Timestamp;
use Zipkin\Span as ZipkinSpan;
use Zipkin\Endpoint;
use ZipkinOpenTracing\SpanContext as ZipkinOpenTracingContext;
use OpenTracing\Tags;
use OpenTracing\SpanContext;
use OpenTracing\Span as OTSpan;
use InvalidArgumentException;

final class Span implements OTSpan
{
    private ZipkinSpan $span;

    private string $operationName;

    private SpanContext $context;

    private bool $hasRemoteEndpoint;

    private array $remoteEndpointArgs;

    private function __construct($operationName, ZipkinSpan $span, array $remoteEndpointArgs = null)
    {
        $this->operationName = $operationName;
        $this->span = $span;
        $this->context = ZipkinOpenTracingContext::fromTraceContext($span->getContext());
        $this->hasRemoteEndpoint = $remoteEndpointArgs !== null;
        $this->remoteEndpointArgs = $this->hasRemoteEndpoint ?
            $remoteEndpointArgs : ['', null, null, null];
    }

    public static function create(string $operationName, ZipkinSpan $span, array $remoteEndpointArgs = null): Span
    {
        return new self($operationName, $span, $remoteEndpointArgs);
    }

    /**
     * @inheritdoc
     */
    public function getOperationName(): string
    {
        return $this->operationName;
    }

    /**
     * @inheritdoc
     */
    public function getContext(): SpanContext
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function finish($finishTime = null, array $logRecords = []): void
    {
        if ($this->hasRemoteEndpoint) {
            $this->span->setRemoteEndpoint(Endpoint::create(...$this->remoteEndpointArgs));
        }

        $this->span->finish($finishTime ?: Timestamp\now());
    }

    /**
     * @inheritdoc
     */
    public function overwriteOperationName(string $newOperationName): void
    {
        $this->operationName = $newOperationName;
        $this->span->setName($newOperationName);
    }

    /**
     * @inheritdoc
     */
    public function setTag(string $key, $value): void
    {
        if ($value === (bool) $value) {
            $value = $value ? 'true' : 'false';
        }

        if ($key === Tags\SPAN_KIND) {
            $this->span->setKind(\strtoupper($value));
            return;
        }

        if ($key === Tags\PEER_SERVICE) {
            $this->hasRemoteEndpoint = true;
            $this->remoteEndpointArgs[0] = $value;
            return;
        }

        if ($key === Tags\PEER_HOST_IPV4) {
            $this->hasRemoteEndpoint = true;
            $this->remoteEndpointArgs[1] = $value;
            return;
        }

        if ($key === Tags\PEER_HOST_IPV6) {
            $this->hasRemoteEndpoint = true;
            $this->remoteEndpointArgs[2] = $value;
            return;
        }

        if ($key === Tags\PEER_PORT) {
            $this->hasRemoteEndpoint = true;
            $this->remoteEndpointArgs[3] = $value;
            return;
        }

        $this->span->tag($key, (string) $value);
    }

    /**
     * @inheritdoc
     */
    public function log(array $fields = [], $timestamp = null): void
    {
        if ($timestamp === null) {
            $timestamp = Timestamp\now();
        } else {
            if ($timestamp instanceof \DateTimeInterface) {
                $timestamp = $timestamp->getTimestamp();
            } elseif (!is_float($timestamp) && !is_int($timestamp)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid timestamp. Expected float, int or DateTime, got %s', $timestamp)
                );
            }
            $timestamp = $timestamp * 1000 * 1000;
        }

        foreach ($fields as $field) {
            $this->span->annotate($field, $timestamp);
        }
    }

    /**
     * @inheritdoc
     */
    public function addBaggageItem(string $key, string $value): void
    {
        $this->context = $this->context->withBaggageItem($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function getBaggageItem(string $key): ?string
    {
        return $this->context->getBaggageItem($key);
    }
}
