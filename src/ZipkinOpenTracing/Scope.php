<?php

namespace ZipkinOpenTracing;

use OpenTracing\Scope as OTScope;

final class Scope implements OTScope
{
    /**
     * @var ScopeManager
     */
    private $scopeManger;

    /**
     * @var OTScope
     */
    private $wrapped;

    public function __construct(ScopeManager $scopeManager, OTScope $wrapped)
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
}
