<?php

namespace ZipkinOpenTracing\Tests\Unit;

use PHPUnit_Framework_TestCase;
use ZipkinOpenTracing\ScopeManager;
use OpenTracing\Span;

final class ScopeManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager
     */
    private $manager;

    protected function setUp()
    {
        $this->manager = new ScopeManager();
    }

    public function testAbleGetActiveScope()
    {
        $this->assertNull($this->manager->getActive());

        $span = $this->prophesize(Span::class)->reveal();
        $scope = $this->manager->activate($span);

        $this->assertEquals($scope, $this->manager->getActive());
    }

    public function testScopeClosingDeactivates()
    {
        $span = $this->prophesize(Span::class)->reveal();
        $scope = $this->manager->activate($span);

        $scope->close();

        $this->assertNull($this->manager->getActive());
    }

    public function testAbilityToGetScopeByASpan()
    {
        $span1 = $this->prophesize(Span::class)->reveal();
        $span2 = $this->prophesize(Span::class)->reveal();
        $span3 = $this->prophesize(Span::class)->reveal();

        $expectedScope = $this->manager->activate($span1);
        $this->manager->activate($span2);
        $this->manager->activate($span3);

        $actualScope = $this->manager->getScope($span1);
        $this->assertEquals($expectedScope, $actualScope);
    }
}
