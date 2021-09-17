<?php

namespace ZipkinOpenTracing\Tests\Unit;

use ZipkinOpenTracing\ScopeManager;
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use OpenTracing\Span;

final class ScopeManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ScopeManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = new ScopeManager();
    }

    public function testGetActiveScopeSuccessfully()
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

    public function testScopeClosingRestoresPreviousScope()
    {
        $span1 = $this->prophesize(Span::class)->reveal();
        $scope1 = $this->manager->activate($span1, false);

        $span2 = $this->prophesize(Span::class)->reveal();
        $scope2 = $this->manager->activate($span2, false);

        $scope2->close();

        $this->assertEquals($scope1, $this->manager->getActive());
    }

    public function testScopeClosingRestoresPreviousScopeEvenIfThereIsAnotherOpen()
    {
        $span1 = $this->prophesize(Span::class)->reveal();
        $scope1 = $this->manager->activate($span1, false);

        $span2 = $this->prophesize(Span::class)->reveal();
        $scope2 = $this->manager->activate($span2, false);

        $span3 = $this->prophesize(Span::class)->reveal();
        $scope3 = $this->manager->activate($span3, false);

        $scope2->close();

        $this->assertEquals($scope3, $this->manager->getActive());
    }

    public function testScopeClosingRestoresPreviousScopeInCascade()
    {
        $span1 = $this->prophesize(Span::class)->reveal();
        $scope1 = $this->manager->activate($span1, false);

        $span2 = $this->prophesize(Span::class)->reveal();
        $scope2 = $this->manager->activate($span2, false);

        $span3 = $this->prophesize(Span::class)->reveal();
        $scope3 = $this->manager->activate($span3, false);

        $scope2->close();
        $scope3->close();

        // Since scope2 was closed before scope3, the new active scope
        // now it is scope1 as scope3 attempts to restore scope2 but that
        // is closed already so it goes up to scope 1. This is because the
        // scopes have been closed in disorder and hence we do our best effort
        // to keep consistent states rather than get stuck in a unlogical scope
        // because subsequent closes might not have effect otherwise.
        // It can be thought as a reconcilliation of scope closing.
        $this->assertEquals($scope1, $this->manager->getActive());
    }
}
