<?php

namespace ZipkinOpenTracing\Tests\Unit;

use OpenTracing\Span;
use PHPUnit\Framework\TestCase;
use ZipkinOpenTracing\ScopeManager;

final class ScopeManagerTest extends TestCase
{
    /**
     * @var ScopeManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = new ScopeManager();
    }

    public function testAbleGetActiveScope()
    {
        $this->assertNull($this->manager->getActive());

        $span = $this->prophesize(Span::class)->reveal();
        $scope = $this->manager->activate($span, false);

        $this->assertEquals($scope, $this->manager->getActive());
    }

    public function testScopeClosingDeactivates()
    {
        $span = $this->prophesize(Span::class)->reveal();
        $scope = $this->manager->activate($span, false);

        $scope->close();

        $this->assertNull($this->manager->getActive());
    }
}
