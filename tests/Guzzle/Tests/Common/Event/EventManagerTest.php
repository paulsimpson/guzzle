<?php
/**
 * @package Guzzle PHP <http://www.guzzlephp.org>
 * @license See the LICENSE file that was distributed with this source code.
 */

namespace Guzzle\Tests\Common\Event;

use Guzzle\Tests\Common\Mock\MockObserver;
use Guzzle\Tests\Common\Mock\MockSubject;
use Guzzle\Common\Event\Subject;
use Guzzle\Common\Event\EventManager;
use Guzzle\Common\Event\Observer;

/**
 * @author Michael Dowling <michael@guzzlephp.org>
 */
class EventManagerTest extends \Guzzle\Tests\GuzzleTestCase implements Observer
{
    /**
     * @covers Guzzle\Common\Event\EventManager::attach
     * @covers Guzzle\Common\Event\EventManager::getAttached
     * @covers Guzzle\Common\Event\EventManager::__construct
     * @covers Guzzle\Common\Event\EventManager::getSubject
     */
    public function testAttach()
    {
        $observer = new MockObserver();
        $mock = new MockSubject();
        $subject = new EventManager($mock, array($observer));
        $this->assertEquals(array($observer), $subject->getAttached());
        $this->assertEquals($mock, $subject->getSubject());

        // A single observer can only be attached once
        $subject->attach($observer);
        $this->assertEquals(array($observer), $subject->getAttached());
    }

    /**
     * @covers Guzzle\Common\Event\EventManager::getAttached
     */
    public function testGetAttachedByName()
    {
        $observer = new MockObserver();
        $subject = new EventManager(new MockSubject());
        $subject->attach($observer);
        $this->assertEquals(array($observer), $subject->getAttached());
        $this->assertEquals(array($observer), $subject->getAttached('Guzzle\Tests\Common\Mock\MockObserver'));
    }

    /**
     * @covers Guzzle\Common\Event\EventManager::detach
     * @covers Guzzle\Common\Event\EventManager::getAttached
     * @depends testAttach
     */
    public function testDetach()
    {
        $observer = new MockObserver();
        $subject = new EventManager(new MockSubject());
        $this->assertEquals($observer, $subject->detach($observer));
        $subject->attach($observer);
        $this->assertEquals(array($observer), $subject->getAttached());
        $this->assertEquals($observer, $subject->detach($observer));
        $this->assertEquals(array(), $subject->getAttached());

        // Now detach with more than one observer
        $subject->attach($this);
        $subject->attach($observer);
        $subject->detach($this);
        $this->assertEquals(array($observer), $subject->getAttached());
    }

    /**
     * @covers Guzzle\Common\Event\EventManager::detachAll
     * @depends testAttach
     */
    public function testDetachAll()
    {
        $observer = new MockObserver();
        $subject = new EventManager(new MockSubject());
        $this->assertEquals(array(), $subject->detachAll($observer));
        $subject->attach($observer);
        $this->assertEquals(array($observer), $subject->getAttached());
        $this->assertEquals(array($observer), $subject->detachAll($observer));
        $this->assertEquals(array(), $subject->getAttached());
    }

    /**
     * @covers Guzzle\Common\Event\EventManager::hasObserver
     * @depends testAttach
     */
    public function testHasObserver()
    {
        $observer = new MockObserver();
        $subject = new EventManager(new MockSubject());
        $this->assertFalse($subject->hasObserver($observer));
        $this->assertFalse($subject->hasObserver('Guzzle\Tests\Common\Mock\MockObserver'));
        $subject->attach($observer);
        $this->assertTrue($subject->hasObserver($observer));
        $this->assertTrue($subject->hasObserver('Guzzle\Tests\Common\Mock\MockObserver'));
    }

    /**
     * @covers Guzzle\Common\Event\EventManager::notify
     * @covers Guzzle\Common\Event\EventManager::attach
     */
    public function testNotify()
    {
        $priorities = array(10, 0, 999, 0, -10);

        $observers = array(
            new MockObserver(),
            new MockObserver(),
            new MockObserver(),
            new MockObserver(),
            new MockObserver()
        );
        
        $sub = new MockSubject();
        $subject = new EventManager($sub);

        foreach ($observers as $i => $o) {
            $subject->attach($o, $priorities[$i]);
        }

        // Make sure that the observers were properly sorted
        $attached = $subject->getAttached();
        $this->assertEquals(5, count($attached));
        $this->assertSame($attached[0], $observers[2]);
        $this->assertSame($attached[1], $observers[0]);
        $this->assertSame($attached[2], $observers[1]);
        $this->assertSame($attached[3], $observers[3]);
        $this->assertSame($attached[4], $observers[4]);

        $this->assertEquals(array(true, true, true, true, true), $subject->notify('test', 'context'));

        foreach ($observers as $o) {
            $this->assertEquals('test', $o->event);
            $this->assertEquals('context', $o->context);
            $this->assertEquals(1, $o->notified);
            $this->assertEquals($sub, $o->subject);
        }

        // Make sure the it will update them again
        $this->assertEquals(array(true, true, true, true, true), $subject->notify('test'));
        foreach ($observers as $o) {
            $this->assertEquals('test', $o->event);
            $this->assertEquals(null, $o->context);
            $this->assertEquals(2, $o->notified);
            $this->assertEquals($sub, $o->subject);
        }
    }

    /**
     * @covers Guzzle\Common\Event\EventManager::notify
     */
    public function testNotifyUntil()
    {
        $sub = new MockSubject();
        $subject = new EventManager($sub);

        $observer1 = new MockObserver();
        $observer2 = new MockObserver();
        $observer3 = new MockObserver();
        $observer4 = new MockObserver();
        
        $subject->attach($observer1);
        $subject->attach($observer2);
        $subject->attach($observer3);
        $subject->attach($observer4);

        $this->assertEquals(array(true), $subject->notify('test', null, true));
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(Subject $subject, $event, $context = null)
    {
        return;
    }
}