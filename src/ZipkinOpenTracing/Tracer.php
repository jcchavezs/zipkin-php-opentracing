<?php

namespace ZipkinOpenTracing;

use InvalidArgumentException;
use OpenTracing\Exceptions\InvalidSpanOption;
use OpenTracing\Exceptions\SpanContextNotFound;
use OpenTracing\Exceptions\UnsupportedFormat;
use OpenTracing\Propagators\Reader;
use OpenTracing\Propagators\Writer;
use OpenTracing\Span;
use OpenTracing\SpanContext;
use OpenTracing\SpanOptions;
use OpenTracing\Tracer as OTTracer;
use Zipkin\Propagation\Getter;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\Setter;
use Zipkin\Propagation\Propagation as ZipkinPropagation;
use Zipkin\Timestamp;
use Zipkin\Tracing as ZipkinTracing;
use Zipkin\Tracer as ZipkinTracer;
use ZipkinOpenTracing\SpanContext as ZipkinOpenTracingContext;
use ZipkinOpenTracing\Span as ZipkinOpenTracingSpan;

final class Tracer implements OTTracer
{
    /**
     * @var ZipkinTracer
     */
    private $tracer;

    /**
     * @var ZipkinPropagation
     */
    private $propagation;

    /**
     * Tracer constructor.
     * @param ZipkinTracing $tracing
     */
    public function __construct(ZipkinTracing $tracing) {
        $this->tracer = $tracing->getTracer();
        $this->propagation = $tracing->getPropagation();
    }

    /**
     * @param string $operationName
     * @param array|SpanOptions $options
     * @return Span
     * @throws \OpenTracing\Exceptions\InvalidReferencesSet
     * @throws InvalidSpanOption for invalid option
     */
    public function startSpan($operationName, $options = [])
    {
        if (!($options instanceof SpanOptions)) {
            $options = SpanOptions::create($options);
        }

        if (empty($options->getReferences())) {
            $span = $this->tracer->newTrace();
        } else {
            /**
             * @var ZipkinOpenTracingContext $context
             */
            $context = $options->getReferences()[0];
            $span = $this->tracer->newChild($context->getContext());
        }

        $span->start($options->getStartTime() ?: Timestamp\now());

        foreach ($options->getTags() as $key => $value) {
            $span->tag($key, $value);
        }

        return ZipkinOpenTracingSpan::create($operationName, $span);
    }

    /**
     * @param ZipkinOpenTracingContext|SpanContext $spanContext
     * @param int $format
     * @param Writer $carrier
     * @throws UnsupportedFormat when the format is not recognized by the tracer
     * implementation
     * @throws \InvalidArgumentException when the SpanContext is not a ZipkinOpenTracingContext
     */
    public function inject(SpanContext $spanContext, $format, Writer $carrier)
    {
        if ($spanContext instanceof ZipkinOpenTracingContext) {
            $setter = $this->getSetterByFormat($format);
            $injector = $this->propagation->getInjector($setter);
            return $injector($spanContext->getContext(), $carrier);
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid SpanContext. Expected ZipkinOpenTracing\SpanContext, got %s.',
            get_class($spanContext)
        ));
    }

    /**
     * @param int $format
     * @param Reader $carrier
     * @return SpanContext
     * @throws SpanContextNotFound when a context could not be extracted from Reader
     * @throws UnsupportedFormat when the format is not recognized by the tracer
     * implementation
     */
    public function extract($format, Reader $carrier)
    {
        $getter = $this->getGetterByFormat($format);
        $extractor =  $this->propagation->getExtractor($getter);
        return $extractor($carrier);
    }

    /**
     * Allow tracer to send span data to be instrumented.
     *
     * This method might not be needed depending on the tracing implementation
     * but one should make sure this method is called after the request is finished.
     * As an implementor, a good idea would be to use an asynchronous message bus
     * or use the call to fastcgi_finish_request in order to not to delay the end
     * of the request to the client.
     *
     * @see fastcgi_finish_request()
     * @see https://www.google.com/search?q=message+bus+php
     */
    public function flush()
    {
        $this->tracer->flush();
    }

    /**
     * @param string $format
     * @return Setter
     */
    private function getSetterByFormat($format)
    {
        switch ($format) {
            default:
                return new Map();
                break;
        }
    }

    /**
     * @param string $format
     * @return Getter
     */
    private function getGetterByFormat($format)
    {
        switch ($format) {
            default:
                return new Map();
                break;
        }
    }
}