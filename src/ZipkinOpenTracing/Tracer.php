<?php

namespace ZipkinOpenTracing;

use OpenTracing\Formats;
use OpenTracing\Reference;
use OpenTracing\SpanContext as OTSpanContext;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer as OTTracer;
use Zipkin\Propagation\Getter;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\Propagation as ZipkinPropagation;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\Setter;
use Zipkin\Propagation\TraceContext;
use Zipkin\Timestamp;
use Zipkin\Tracer as ZipkinTracer;
use Zipkin\Tracing as ZipkinTracing;
use ZipkinOpenTracing\PartialSpanContext as ZipkinOpenPartialTracingContext;
use ZipkinOpenTracing\Span as ZipkinOpenTracingSpan;
use ZipkinOpenTracing\NoopSpan as ZipkinOpenTracingNoopSpan;
use ZipkinOpenTracing\SpanContext as ZipkinOpenTracingContext;

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
        $this->scopeManager = new ScopeManager();
    }

    /**
     * @inheritdoc
     */
    public function getScopeManager()
    {
        return $this->scopeManager;
    }

    /**
     * @inheritdoc
     */
    public function getActiveSpan()
    {
        $activeScope = $this->scopeManager->getActive();
        if ($activeScope === null) {
            return null;
        }

        return $activeScope->getSpan();
    }

    /**
     * @inheritdoc
     */
    public function startActiveSpan($operationName, $options = [])
    {
        if (!$options instanceof StartSpanOptions) {
            $options = StartSpanOptions::create($options);
        }

        $span = $this->startSpan($operationName, $options);
        $scope = $this->scopeManager->activate($span, $options->shouldFinishSpanOnClose());

        return $scope;
    }

    /**
     * @inheritdoc
     * @return OTSpan|Span
     */
    public function startSpan($operationName, $options = [])
    {
        if (!($options instanceof StartSpanOptions)) {
            $options = StartSpanOptions::create($options);
        }

        if (!$this->hasParentInOptions($options) && $this->getActiveSpan() !== null) {
            $parent = $this->getActiveSpan()->getContext();
            $options = $options->withParent($parent);
        }

        if (empty($options->getReferences())) {
            $span = $this->tracer->newTrace();
        } else {
            /**
             * @var ZipkinOpenTracingContext $refContext
             */
            $refContext = $options->getReferences()[0]->getContext();
            $context = $refContext->getContext();

            if ($context instanceof TraceContext) {
                $span = $this->tracer->newChild($context);
            } else {
                $span = $this->tracer->nextSpan($context);
            }
        }

        if ($span->isNoop()) {
            return ZipkinOpenTracingNoopSpan::create($span);
        }

        $span->start($options->getStartTime() ?: Timestamp\now());
        $span->setName($operationName);

        $otSpan = ZipkinOpenTracingSpan::create($operationName, $span, null);

        foreach ($options->getTags() as $key => $value) {
            $otSpan->setTag($key, $value);
        }

        return $otSpan;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function inject(OTSpanContext $spanContext, $format, &$carrier)
    {
        if ($spanContext instanceof ZipkinOpenTracingContext) {
            $setter = $this->getSetterByFormat($format);
            $injector = $this->propagation->getInjector($setter);
            return $injector($spanContext->getContext(), $carrier);
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid span context. Expected ZipkinOpenTracing\SpanContext, got %s.',
            is_object($spanContext) ? get_class($spanContext) : gettype($spanContext)
        ));
    }

    /**
     * @inheritdoc
     * @throws \UnexpectedValueException
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

        throw new \UnexpectedValueException(sprintf(
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
     * @throws \UnexpectedValueException
     */
    private function getSetterByFormat($format)
    {
        if ($format === Formats\TEXT_MAP) {
            return new Map();
        }

        throw new \UnexpectedValueException(sprintf('Format %s not implemented', $format));
    }

    /**
     * @param string $format
     * @return Getter
     * @throws \UnexpectedValueException
     */
    private function getGetterByFormat($format)
    {
        if ($format === Formats\TEXT_MAP) {
            return new Map();
        }

        throw new \UnexpectedValueException(sprintf('Format %s not implemented', $format));
    }

    private function hasParentInOptions(StartSpanOptions $options)
    {
        $references = $options->getReferences();
        foreach ($references as $ref) {
            if ($ref->isType(Reference::CHILD_OF)) {
                return $ref->getContext();
            }
        }

        return null;
    }
}
