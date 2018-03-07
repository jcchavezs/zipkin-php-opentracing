<?php

namespace ZipkinOpenTracing;

use InvalidArgumentException;
use OpenTracing\Formats;
use OpenTracing\Span as OTSpan;
use OpenTracing\SpanContext as OTSpanContext;
use OpenTracing\SpanOptions;
use OpenTracing\Tracer as OTTracer;
use UnexpectedValueException;
use Zipkin\Propagation\Getter;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\Setter;
use Zipkin\Propagation\Propagation as ZipkinPropagation;
use Zipkin\Propagation\TraceContext;
use Zipkin\Timestamp;
use Zipkin\Tracing as ZipkinTracing;
use Zipkin\Tracer as ZipkinTracer;
use ZipkinOpenTracing\SpanContext as ZipkinOpenTracingContext;
use ZipkinOpenTracing\PartialSpanContext as ZipkinOpenPartialTracingContext;
use ZipkinOpenTracing\Span as ZipkinOpenTracingSpan;
use ZipkinOpenTracing\NoopSpan as ZipkinOpenTracingNoopSpan;

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

    public function __construct(ZipkinTracing $tracing)
    {
        $this->tracer = $tracing->getTracer();
        $this->propagation = $tracing->getPropagation();
    }

    /**
     * @inheritdoc
     * @return OTSpan|Span
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
            $context = $options->getReferences()[0]->getContext();
            $span = $this->tracer->newChild($context->getContext());
        }

        if ($span->isNoop()) {
            return ZipkinOpenTracingNoopSpan::create($span);
        }

        $span->start($options->getStartTime() ?: Timestamp\now());
        $span->setName($operationName);

        foreach ($options->getTags() as $key => $value) {
            $span->tag($key, $value);
        }

        return ZipkinOpenTracingSpan::create($operationName, $span);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function inject(OTSpanContext $spanContext, $format, &$carrier)
    {
        if ($spanContext instanceof ZipkinOpenTracingContext) {
            $setter = $this->getSetterByFormat($format);
            $injector = $this->propagation->getInjector($setter);
            return $injector($spanContext->getContext(), $carrier);
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid span context. Expected ZipkinOpenTracing\SpanContext, got %s.',
            is_object($spanContext) ? get_class($spanContext) : gettype($spanContext)
        ));
    }

    /**
     * @inheritdoc
     * @throws UnexpectedValueException
     */
    public function extract($format, $carrier)
    {
        $getter = $this->getGetterByFormat($format);
        $extractor =  $this->propagation->getExtractor($getter);
        $extractedContext = $extractor($carrier);

        if ($extractedContext instanceof TraceContext) {
            return ZipkinOpenTracingContext::fromTraceContext($extractedContext);
        }

        if ($extractedContext instanceof SamplingFlags) {
            return ZipkinOpenPartialTracingContext::fromSamplingFlags($extractedContext);
        }

        throw new UnexpectedValueException(sprintf(
            'Invalid extracted context. Expected Zipkin\SamplingFlags, got %s',
            is_object($extractedContext) ? get_class($extractedContext) : gettype($extractedContext)
        ));
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->tracer->flush();
    }

    /**
     * @param string $format
     * @return Setter
     * @throws UnexpectedValueException
     */
    private function getSetterByFormat($format)
    {
        if ($format === Formats\TEXT_MAP) {
            return new Map();
        }

        throw new UnexpectedValueException(sprintf('Format %s not implemented', $format));
    }

    /**
     * @param string $format
     * @return Getter
     * @throws UnexpectedValueException
     */
    private function getGetterByFormat($format)
    {
        if ($format === Formats\TEXT_MAP) {
            return new Map();
        }

        throw new UnexpectedValueException(sprintf('Format %s not implemented', $format));
    }
}
