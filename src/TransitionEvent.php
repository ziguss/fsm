<?php

namespace ziguss\fsm;

/**
 * @author ziguss <yudoujia@163.com>
 */
class TransitionEvent
{
    /**
     * @var string
     */
    protected $transition;

    /**
     * @var string
     */
    protected $fromState;

    /**
     * @var StateMachine
     */
    protected $stateMachine;
    /**
     * @var bool
     */
    protected $rejected = false;

    /**
     * @param string       $transition
     * @param string       $fromState
     * @param StateMachine $stateMachine
     */
    public function __construct($transition, $fromState, $stateMachine)
    {
        $this->transition = $transition;
        $this->fromState = $fromState;
        $this->stateMachine = $stateMachine;
    }

    /**
     * Returns the transition name.
     *
     * @return string
     */
    public function getTransition()
    {
        return $this->transition;
    }

    /**
     * Returns the state machine.
     *
     * @return StateMachine
     */
    public function getStateMachine()
    {
        return $this->stateMachine;
    }

    /**
     * Returns the from state.
     *
     * @return string
     */
    public function getFromState()
    {
        return $this->fromState;
    }

    /**
     * Returns the to state.
     *
     * @return string
     */
    public function getToState()
    {
        $config = $this->getConfig();

        return $config['to'];
    }

    /**
     * Returns the transition config.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->stateMachine->getConfig();

        return $config['transitions'][$this->transition];
    }

    /**
     * @param bool $reject
     */
    public function setRejected($reject = true)
    {
        $this->rejected = $reject;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return $this->rejected;
    }
}
