<?php

namespace ZipkinOpenTracing;

use OpenTracing\Scope as OTScope;

class Scope implements OTScope
{
    public function __construct($scopeManager, $wrapped)
    {
        $this->scopeManager = $scopeManager;
        $this->wrapped = $wrapped;
        $this->toRestore = $scopeManager->getActiveScope();
    }

    /**
     * {@inheritdoc}
     */
    public function getSpan()
    {
        return $this->wrapped;
    }

    public function close()
    {
        $this->scopeManager->setActiveScope($this->toRestore);
    }
}
