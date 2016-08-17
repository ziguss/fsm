<?php

namespace ziguss\fsm\tests;

use ziguss\fsm\StateMachine;
use ziguss\fsm\TransitionEvent;

/**
 * @author ziguss <yudoujia@163.com>
 */
class StateMachineTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigMissingGraph()
    {
        $this->setExpectedException(
            'ziguss\fsm\InvalidConfigException',
            'Missing state machine config graph'
        );
        new StateMachine(new StatefulObject(), array(
            'states' => array(),
            'transitions' => array(),
        ));
    }

    public function testConfigMissingStates()
    {
        $this->setExpectedException(
            'ziguss\fsm\InvalidConfigException',
            'Missing state machine config states'
        );
        new StateMachine(new StatefulObject(), array(
            'graph' => 'graph1',
            'transitions' => array(),
        ));
    }

    public function testConfigMissingTransitions()
    {
        $this->setExpectedException(
            'ziguss\fsm\InvalidConfigException',
            'Missing state machine config transitions'
        );
        new StateMachine(new StatefulObject(), array(
            'graph' => 'graph1',
            'states' => array(),
        ));
    }

    public function testConfigMissingDo()
    {
        $this->setExpectedException(
            'ziguss\fsm\InvalidConfigException',
            'Missing listener config do'
        );
        new StateMachine(new StatefulObject(), array(
            'graph' => 'graph1',
            'states' => array(),
            'transitions' => array(),
            'listeners' => array(
                'test' => array(array()),
            ),
        ));
    }

    /**
     * @dataProvider configProvider
     *
     * @param array $config
     */
    public function testGetObject(array $config)
    {
        $object = new StatefulObject();
        $sm = new StateMachine($object, $config);
        $this->assertEquals($object, $sm->getObject());
    }

    /**
     * @dataProvider configProvider
     *
     * @param array $config
     */
    public function testGetGraph(array $config)
    {
        $object = new StatefulObject();
        $sm = new StateMachine($object, $config);
        $this->assertEquals($config['graph'], $sm->getGraph());
    }

    /**
     * @dataProvider configProvider
     *
     * @param array $config
     */
    public function testGetState(array $config)
    {
        $object = new StatefulObject();
        $object->setFiniteState($config['initial']);
        $sm = new StateMachine($object, $config);
        $this->assertEquals($sm->getState(), $config['initial']);
    }

    public function testGetFinalStates()
    {
        $this->assertEquals(array('done'), $this->getTaskStateMachine()->getFinalStates());
    }

    public function testIsFinal()
    {
        $sm = $this->getTaskStateMachine();
        $this->assertFalse($sm->isFinal());
        $sm->getObject()->setFiniteState('done');
        $this->assertTrue($sm->isFinal());
    }

    public function testIsEnabled()
    {
        $sm = $this->getTaskStateMachine();
        $this->assertTrue($sm->isEnabled('assign'));
        $this->assertFalse($sm->isEnabled('unAssign'));
        $this->assertTrue($sm->isEnabled('take', false));
        $this->assertFalse($sm->isEnabled('take'));
        $this->assertFalse($sm->isEnabled('random'));
    }

    public function testGetEnabledTransitions()
    {
        $sm = $this->getTaskStateMachine();
        $this->assertEquals(array('assign'), $sm->getEnabledTransitions());
        $sm->getObject()->setFiniteState('assigned');
        $this->assertEquals(array('unAssign', 'finish'), $sm->getEnabledTransitions());
    }

    public function testApply()
    {
        $sm = $this->getTaskStateMachine();
        $this->assertEquals($sm->getState(), 'unassigned');
        $sm->apply('assign');
        $this->assertEquals($sm->getState(), 'assigned');
        $sm->apply('unAssign');
        $this->assertEquals($sm->getState(), 'unassigned');
        $sm->apply('assign');
        $sm->apply('finish');
        $this->assertEquals($sm->getState(), 'done');
    }

    public function testApplyException()
    {
        $sm = $this->getTaskStateMachine();
        $this->setExpectedException(
            'ziguss\fsm\InvalidTransitionApplyException',
            sprintf(
                'Transition "%s" cannot be applied on state "%s" of object "%s" with graph "%s"',
                'unAssign',
                $sm->getState(),
                get_class($sm->getObject()),
                $sm->getGraph()
            )
        );
        $sm->apply('unAssign');
    }

    /**
     * @dataProvider configProvider
     *
     * @param $config
     * @param $transition
     * @param $dispatchResult
     */
    public function testDispatchEvent($config, $transition, $dispatchResult)
    {
        $object = new StatefulObject();
        $object->setFiniteState($config['initial']);
        $sm = new StateMachine($object, $config);
        StatefulObject::reset();
        $this->assertEquals(
            array(false, false, false),
            array(StatefulObject::$testDispatched, StatefulObject::$beforeDispatched, StatefulObject::$afterDispatched)
        );
        $sm->apply($transition);
        $this->assertEquals(
            $dispatchResult,
            array(StatefulObject::$testDispatched, StatefulObject::$beforeDispatched, StatefulObject::$afterDispatched)
        );
    }

    /**
     * @return array
     */
    public function configProvider()
    {
        return array(
            array(
                $this->getTaskGraphConfig(),
                'assign',
                array(true, true, true),
            ),
            array(
                array(
                    'graph' => 'task2',
                    'states' => array('unassigned', 'assigned', 'done'),
                    'initial' => 'assigned',
                    'transitions' => array(
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
                                'on' => 'finish',
                                'do' => array('ziguss\fsm\tests\StatefulObject', 'onTest'),
                            ),
                        ),
                        'before' => array(
                            array(
                                'on' => 'unAssign',
                                'do' => array('ziguss\fsm\tests\StatefulObject', 'onBefore'),
                            ),
                        ),
                        'after' => array(
                            array('ziguss\fsm\tests\StatefulObject', 'onAfter'),
                        ),
                    ),
                ),
                'unAssign',
                array(false, true, true),
            ),
            array(
                array(
                    'graph' => 'task3',
                    'states' => array('unassigned', 'assigned', 'done'),
                    'initial' => 'unassigned',
                    'transitions' => array(
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
                        'cancel' => null,
                    ),
                    'listeners' => array(
                        'test' => array(
                            array(
                                'from' => 'assigned',
                                'do' => array('ziguss\fsm\tests\StatefulObject', 'onTest'),
                            ),
                        ),
                        'before' => array(
                            array(
                                'to' => 'assigned',
                                'do' => array('ziguss\fsm\tests\StatefulObject', 'onBefore'),
                            ),
                        ),
                        'after' => array(
                            array(
                                'excluded_to' => 'assigned',
                                'do' => array('ziguss\fsm\tests\StatefulObject', 'onAfter'),
                            ),
                        ),
                    ),
                ),
                'assign',
                array(false, true, false),
            ),
            array(
                array(
                    'graph' => 'task4',
                    'states' => array('unassigned', 'assigned', 'done'),
                    'initial' => 'unassigned',
                    'transitions' => array(
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
                        'cancel' => null,
                    ),
                    'listeners' => array(
                        'test' => array(
                            array(
                                'excluded_from' => 'unassigned',
                                'do' => array('ziguss\fsm\tests\StatefulObject', 'onTest'),
                            ),
                        ),
                    ),
                ),
                'assign',
                array(false, false, false),
            ),
        );
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
