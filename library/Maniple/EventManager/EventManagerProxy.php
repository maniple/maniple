<?php

use Zend\EventManager\EventManager;

class Maniple_EventManager_EventManagerProxy implements Zend_EventManager_EventCollection
{
    /**
     * @var Zend\EventManager\EventManager
     */
    protected $_eventManager;

    public function __construct(EventManager $eventManager)
    {
        $this->_eventManager = $eventManager;
    }

    public function trigger($event, $target = null, $argv = array(), $callback = null)
    {
        return $this->_eventManager->trigger($event, $target, $argv, $callback);
    }

    public function triggerUntil($event, $target, $argv = null, $callback = null)
    {
        return $this->_eventManager->triggerUntil($event, $target, $argv, $callback);
    }

    public function attach($event, $callback = null, $priority = 1)
    {
        return $this->_eventManager->attach($event, $callback, $priority);
    }

    public function detach($listener)
    {
        return $this->_eventManager->detach($listener);
    }

    public function getEvents()
    {
        return $this->_eventManager->getEvents();
    }

    public function getListeners($event)
    {
        return $this->_eventManager->getListeners($event);
    }

    public function clearListeners($event)
    {
        return $this->_eventManager->clearListeners($event);
    }
}
