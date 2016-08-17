<?php

namespace ziguss\fsm\tests;

use ziguss\fsm\StatefulInterface;
use ziguss\fsm\TransitionEvent;

/**
 * @author ziguss <yudoujia@163.com>
 */
class StatefulObject implements StatefulInterface
{
    public static $testDispatched = false;
    public static $beforeDispatched = false;
    public static $afterDispatched = false;

    /**
     * @var string
     */
    private $state;

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
     * @param TransitionEvent $event
     */
    public static function onTest(TransitionEvent $event)
    {
        self::$testDispatched = true;
    }

    /**
     * @param TransitionEvent $event
     */
    public static function onBefore(TransitionEvent $event)
    {
        self::$beforeDispatched = true;
    }

    /**
     * @param TransitionEvent $event
     */
    public static function onAfter(TransitionEvent $event)
    {
        self::$afterDispatched = true;
    }

    public static function reset()
    {
        static::$testDispatched = false;
        static::$beforeDispatched = false;
        static::$afterDispatched = false;
    }
}
