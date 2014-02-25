<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Domain\DomainEventMessageInterface;
use Governor\Framework\Domain\GenericDomainEventMessage;
use Governor\Framework\Domain\MetaData;
use Governor\Framework\Stubs\StubDomainEvent;
use Governor\Framework\Stubs\StubAggregate;

/**
 * Description of AbstractEventSourcedEntityTest
 *
 * @author 255196
 */
class AbstractEventSourcedEntityTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = new StubEntity();
    }

    public function testRecursivelyApplyEvent()
    {
        $aggregateRoot = $this->getMockForAbstractClass('Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot');
        $this->testSubject->registerAggregateRoot($aggregateRoot);

        $this->testSubject->handleRecursively($this->domainEvent(new StubDomainEvent()));
        $this->assertEquals(1, $this->testSubject->invocationCount);
        $this->testSubject->handleRecursively($this->domainEvent(new StubDomainEvent()));
        $this->assertEquals(2, $this->testSubject->invocationCount);
        $this->assertEquals(1, $this->testSubject->child->invocationCount);
    }

    private function domainEvent(StubDomainEvent $stubDomainEvent)
    {
        return new GenericDomainEventMessage(Uuid::uuid1(), 0, $stubDomainEvent,
                MetaData::emptyInstance());
    }

    public function testApplyDelegatesToAggregateRoot()
    {
        $aggregateRoot = $this->getMockBuilder('Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot')
                ->disableOriginalConstructor()
                ->setMethods(array('apply'))
                ->getMockForAbstractClass();

        $this->testSubject->registerAggregateRoot($aggregateRoot);
        $event = new StubDomainEvent();

        $aggregateRoot->expects($this->once())
                ->method('apply');

        $this->testSubject->apply($event);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDuplicateAggregateRootRegistration_DifferentAggregate()
    {
        $aggregateRoot1 = $this->getMockForAbstractClass('Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot');
        $aggregateRoot2 = $this->getMockForAbstractClass('Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot');

        $this->testSubject->registerAggregateRoot($aggregateRoot1);
        $this->testSubject->registerAggregateRoot($aggregateRoot2);
    }

    public function testDuplicateAggregateRootRegistration_SameAggregate()
    {
        $aggregateRoot = $this->getMockForAbstractClass('Governor\Framework\EventSourcing\AbstractEventSourcedAggregateRoot');
        $this->testSubject->registerAggregateRoot($aggregateRoot);
        $this->testSubject->registerAggregateRoot($aggregateRoot);
    }

}

class StubEntity extends AbstractEventSourcedEntity
{

    public $invocationCount = 0;
    public $child;

    protected function getChildEntities()
    {
        return array($this->child);
    }

    protected function handle(DomainEventMessageInterface $event)
    {
        if (1 === $this->invocationCount && null === $this->child) {
            $this->child = new StubEntity();
        }
        $this->invocationCount++;
    }

}