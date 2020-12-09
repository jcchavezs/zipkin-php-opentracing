<?php

namespace ZipkinOpenTracing;

use OpenTracing\Span as OTSpan;
use OpenTracing\ScopeManager as OTScopeManager;

final class ScopeManager implements OTScopeManager
{
    /**
     * @var Scope|null
     */
    private $active;

    /**
     * {@inheritdoc}
     */
    public function activate(OTSpan $span, $finishSpanOnClose = true)
    {
        // restorer allows to the scope to restore a parent scope without the
        // scope manager to expose a specific method for it.
        $restorer = function (?Scope $scope): void {
            $this->active = $scope;
        };

        $this->active = new Scope($this, $span, $finishSpanOnClose, $this->active, $restorer);
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function getActive()
    {
        return $this->active;
    }
}
