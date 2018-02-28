<?php

namespace ZipkinOpenTracing;

use OpenTracing\ScopeManager as OTScopeManager;
use OpenTracing\Span as OTSpan;

class ScopeManager implements OTScopeManager
{
    /**
     * @var Scope
     */
    private $activeScope;

    /**
     * {@inheritdoc}
     */
    public function activate(OTSpan $span)
    {
        $this->activeScope = new Scope($this, $span);

        return $this->activeScope;
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveScope()
    {
        return $this->activeScope;
    }

    public function getScope(OTSpan $span)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function setActiveScope(Scope $scope = null)
    {
        $this->activeScope = $scope;
    }
}
