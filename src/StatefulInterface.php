<?php

namespace ziguss\fsm;

/**
 * StatefulInterface is the interface implemented by class
 * which instance want to be handled by the state machine.
 *
 * @author ziguss <yudoujia@163.com>
 */
interface StatefulInterface
{
    /**
     * Gets the object state.
     *
     * @return string
     */
    public function getFiniteState();

    /**
     * Sets the object state.
     *
     * @param string $state
     */
    public function setFiniteState($state);
}
