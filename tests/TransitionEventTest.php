<?php

namespace ziguss\fsm\tests;

use ziguss\fsm\TransitionEvent;
use ziguss\fsm\StateMachine;

/**
 * @author ziguss <yudoujia@163.com>
 */
class TransitionEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTransition()
    {
        $sm = $this->getTaskStateMachine();
        $event = new TransitionEvent('assign', 'unassigned', $sm);
        $this->assertEquals('assign', $event->getTransition());
    }

    public function testGetStateMachine()
    {
        $sm = $this->getTaskStateMachine();
        $event = new TransitionEvent('assign', 'unassigned', $sm);
        $this->assertEquals($sm, $event->getStateMachine());
    }

    public function testGetFromState()
    {
        $sm = $this->getTaskStateMachine();
        $event = new TransitionEvent('assign', 'unassigned', $sm);
        $this->assertEquals('unassigned', $event->getFromState());
    }

    public function testGetToState()
    {
        $sm = $this->getTaskStateMachine();
        $event = new TransitionEvent('assign', 'unassigned', $sm);
        $this->assertEquals('assigned', $event->getToState());
    }

    public function testGetConfig()
    {
        $sm = $this->getTaskStateMachine();
        $event = new TransitionEvent('assign', 'unassigned', $sm);
        $this->assertEquals(
            array(
                'from' => array('unassigned'),
                'to' => 'assigned',
            ),
            $event->getConfig()
        );
    }

    public function testSetRejected()
    {
        $sm = $this->getTaskStateMachine();
        $event = new TransitionEvent('assign', 'unassigned', $sm);
        $event->setRejected();
        $this->assertTrue($event->isRejected());
    }

    /**
     * @return array
     */
    private function getTaskGraphConfig()
    {
        return array(
            'graph' => 'task',
            'states' => array('unassigned', 'assigned', 'done'),
            'initial' => 'unassigned',
            'transitions' => array(
                'take' => array(
                    'from' => 'unassigned',
                    'to' => 'assigned',
                ),
                'assign' => array(
                    'from' => 'unassigned',
                    'to' => 'assigned',
                ),
                'unAssign' => array(
                    'from' => 'assigned',
                    'to' => 'unassigned',
                ),
                'finish' => array(
                    'from' => 'assigned',
                    'to' => 'done',
                ),
            ),
            'listeners' => array(
                'test' => array(
                    array(
                        'on' => 'take',
                        'do' => function (TransitionEvent $event) {
                            $event->setRejected();
                        },
                    ),
                    array('ziguss\fsm\tests\StatefulObject', 'onTest'),
                ),
                'before' => array(
                    array('ziguss\fsm\tests\StatefulObject', 'onBefore'),
                ),
                'after' => array(
                    array('ziguss\fsm\tests\StatefulObject', 'onAfter'),
                ),
            ),
        );
    }

    /**
     * @return StateMachine
     */
    private function getTaskStateMachine()
    {
        $config = $this->getTaskGraphConfig();
        $object = new StatefulObject();
        $object->setFiniteState($config['initial']);

        return new StateMachine($object, $config);
    }
}
