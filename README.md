A PHP Finite State Machine Library
============================================

It's miniature and independent but powerful enough.

Getting started
---------------

### Define your Stateful Object

```php
use ziguss\fsm\StatefulInterface;
use ziguss\fsm\StateMachine;
use ziguss\fsm\TransitionEvent;

class Task implements StatefulInterface
{
    /**
     * @var StateMachine
     */
    private $sm;

    /**
     * @var string
     */
    private $state = 'unassigned';

    /**
     * {@inheritdoc}
     */
    public function getFiniteState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function setFiniteState($state)
    {
        $this->state = $state;
    }

    /**
     * @return StateMachine
     */
    public function getStateMachine()
    {
        if (null === $this->sm) {
            $this->sm = new StateMachine($this, array(
                'graph' => 'task',
                'states' => array('unassigned', 'assigned', 'done'),
                'transitions' => array(
                    'assign' => array(
                        'from' => array('unassigned', 'assigned'),
                        'to' => 'assigned',
                    ),
                    'unAssign' => array(
                        'from' => 'assigned',
                        'to' => 'unassigned',
                    ),
                    'finish' => array(
                        'from' => 'assigned',
                        'to' => 'done',
                        'condition' => array($this, 'justMe'),
                    ),
                ),
                'listeners' => array(
                    'test' => array(
                        array($this, 'testCondition'),
                    ),
                    'after' => array(
                        array('on' => 'assign', 'do' => array($this, 'emailSomeone')),
                    ),
                ),
            ));
        }

        return $this->sm;
    }

    /**
     * @param TransitionEvent $event
     */
    public function emailSomeone(TransitionEvent $event)
    {
        echo 'You have new work to do.';
    }

    /**
     * @param TransitionEvent $event
     */
    public function testCondition(TransitionEvent $event)
    {
        $config = $event->getConfig();
        if (!empty($config['condition'])) {
            $event->setRejected(!call_user_func($config['condition']));
        }
    }

    /**
     * @return bool
     */
    public function justMe()
    {
        // your can check if the task is assigned to someone
        return true;
    }
}

```

### Using it.

```php
$task = new Task();

// Retrieve all current enabled transitions
$transitions = $task->getStateMachine()->getEnabledTransitions();

// pick one and apply
$task->getStateMachine()->apply($transitions[array_rand($transitions)]);

// Retrieve current state by state machine
$task->getStateMachine()->getState();
```