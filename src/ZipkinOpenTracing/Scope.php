<?php

namespace ZipkinOpenTracing;

use OpenTracing\Scope as OTScope;
use OpenTracing\Span as OTSpan;

final class Scope implements OTScope
{
    /**
     * @var ScopeManager
     */
    private $scopeManger;

    /**
     * @var OTSpan
     */
    private $wrapped;

    public function __construct(ScopeManager $scopeManager, OTSpan $wrapped)
    {
        $this->scopeManager = $scopeManager;
        $this->wrapped = $wrapped;
        $this->toRestore = $scopeManager->getActive();
    }

    /**
     * {@inheritdoc}
     */
    public function getSpan()
    {
        return $this->wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->scopeManager->setActive($this->toRestore);
    }

    public function getToRestore()
    {
        return $this->toRestore;
    }
}
