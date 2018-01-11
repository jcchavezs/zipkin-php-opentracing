<?php

namespace ZipkinOpenTracing;

use InvalidArgumentException;
use OpenTracing\Formats;
use OpenTracing\SpanContext;
use OpenTracing\SpanOptions;
use OpenTracing\Tracer as OTTracer;
use UnexpectedValueException;
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

    public function __construct(ZipkinTracing $tracing)
    {
        $this->tracer = $tracing->getTracer();
        $this->propagation = $tracing->getPropagation();
    }

    /**
     * @inheritdoc
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

        $span->start($options->getStartTime() ?: Timestamp\now());

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
    public function inject(SpanContext $spanContext, $format, &$carrier)
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
     * @inheritdoc
     * @throws UnexpectedValueException
     */
    public function extract($format, $carrier)
    {
        $getter = $this->getGetterByFormat($format);
        $extractor =  $this->propagation->getExtractor($getter);
        return ZipkinOpenTracingContext::fromTraceContext($extractor($carrier));
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
