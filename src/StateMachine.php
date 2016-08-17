<?php

namespace ziguss\fsm;

/**
 * @author ziguss <yudoujia@163.com>
 */
class StateMachine
{
    /**
     * @var StatefulInterface the underlying stateful object for the state machine
     */
    protected $object;

    /**
     * @var array the config of current state machine
     */
    protected $config;

    /**
     * @param StatefulInterface $object
     * @param array             $config
     */
    public function __construct(StatefulInterface $object, array $config)
    {
        $this->object = $object;
        $this->config = $this->normalizationConfig($config);
    }

    /**
     * Returns the current graph.
     *
     * @return string
     */
    public function getGraph()
    {
        return $this->config['graph'];
    }

    /**
     * Returns the underlying stateful object.
     *
     * @return StatefulInterface
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Returns the config of current state machine.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns the current state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->getObject()->getFiniteState();
    }

    /**
     * Returns the final states.
     *
     * @return array
     */
    public function getFinalStates()
    {
        $notFinalStates = array();
        foreach ($this->config['transitions'] as $def) {
            $notFinalStates = array_merge($notFinalStates, $def['from']);
        }

        return array_values(array_diff($this->config['states'], $notFinalStates));
    }

    /**
     * Check if the current state machine is final.
     *
     * @return bool
     */
    public function isFinal()
    {
        return in_array($this->getState(), $this->getFinalStates());
    }

    /**
     * Tests if we can apply the specified transition.
     *
     * @param string $transition   the transition name
     * @param bool   $dispatchTest whether dispatch the test event
     *
     * @return bool
     */
    public function isEnabled($transition, $dispatchTest = true)
    {
        if (!isset($this->config['transitions'][$transition])) {
            return false;
        }

        if (!in_array($this->getState(), $this->config['transitions'][$transition]['from'])) {
            return false;
        }

        if ($dispatchTest) {
            $event = new TransitionEvent($transition, $this->getState(), $this);
            $this->dispatchEvent($event, 'test');

            return !$event->isRejected();
        }

        return true;
    }

    /**
     * Returns the enabled transitions.
     *
     * @param bool $dispatchTest
     *
     * @return array
     */
    public function getEnabledTransitions($dispatchTest = true)
    {
        $self = $this;

        return array_values(array_filter(
            array_keys($this->config['transitions']),
            function ($transition) use ($self, $dispatchTest) {
                return $self->isEnabled($transition, $dispatchTest);
            }
        ));
    }

    /**
     * Drives the state machine.
     *
     * @param string $transition
     *
     * @throws InvalidTransitionApplyException
     */
    public function apply($transition)
    {
        if (!$this->isEnabled($transition)) {
            throw new InvalidTransitionApplyException(sprintf(
                'Transition "%s" cannot be applied on state "%s" of object "%s" with graph "%s"',
                $transition,
                $this->getState(),
                get_class($this->getObject()),
                $this->getGraph()
            ));
        }

        $event = new TransitionEvent($transition, $this->getState(), $this);
        $this->dispatchEvent($event, 'before');
        $this->getObject()->setFiniteState($this->config['transitions'][$transition]['to']);
        $this->dispatchEvent($event, 'after');
    }

    /**
     * @param TransitionEvent $event
     * @param string          $position
     */
    protected function dispatchEvent(TransitionEvent $event, $position)
    {
        if (empty($this->config['listeners'][$position])) {
            return;
        }

        foreach ($this->config['listeners'][$position] as $key => $listener) {
            if ($this->isSatisfied($listener, $event)) {
                call_user_func($listener['do'], $event, $position);
            }
        }
    }

    /**
     * @param array           $listener
     * @param TransitionEvent $event
     *
     * @return bool
     */
    protected function isSatisfied(array $listener, TransitionEvent $event)
    {
        $clauses = array(
            'on' => $event->getTransition(),
            'from' => $event->getFromState(),
            'to' => $event->getToState(),
        );

        foreach ($clauses as $clause => $value) {
            // first check positive
            if (0 < count($listener[$clause]) && !in_array($value, $listener[$clause])) {
                return false;
            }

            // then check negative
            if (0 < count($listener['excluded_'.$clause]) && in_array($value, $listener['excluded_'.$clause])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $config
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    protected function normalizationConfig(array $config)
    {
        foreach (array('graph', 'states', 'transitions') as $item) {
            if (!isset($config[$item])) {
                throw new InvalidConfigException("Missing state machine config $item");
            }
        }

        $config['states'] = array_values(array_unique($config['states']));

        $transitions = array();
        foreach ($config['transitions'] as $key => $value) {
            if (empty($value)) {
                continue;
            }
            if (!is_array($value['from'])) {
                $value['from'] = array($value['from']);
            }
            $transitions[$key] = $value;
        }
        $config['transitions'] = $transitions;

        if (isset($config['listeners'])) {
            foreach ($config['listeners'] as $position => $listeners) {
                foreach ($listeners as $key => $listener) {
                    if (is_callable($listener)) {
                        $listener = array('do' => $listener);
                    }

                    if (empty($listener['do'])) {
                        throw new InvalidConfigException('Missing listener config do');
                    }

                    foreach (array('from', 'to', 'on', 'excluded_from', 'excluded_to', 'excluded_on') as $clause) {
                        if (!isset($listener[$clause])) {
                            $listener[$clause] = array();
                        } elseif (!is_array($listener[$clause])) {
                            $listener[$clause] = array($listener[$clause]);
                        }
                    }

                    $config['listeners'][$position][$key] = $listener;
                }
            }
        }

        return $config;
    }
}
