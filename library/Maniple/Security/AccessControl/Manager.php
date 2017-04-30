<?php

/**
 * Why not Zend_Acl? Because it is not flexible enough:
 * - resources and roles must be pre-registered
 * - resources must either implement ResourceInterface or be string, which
 *   limits the usefulness of the whole concept of dynamic assertions
 */
class Maniple_Security_AccessControl_Manager
{
    const EVENT_REGISTER_RULE = 'registerRule';

    /**
     * @var Maniple_Security_AccessControl_RuleInterface[]
     */
    protected $_rules;

    /**
     * @var bool
     */
    protected $_isBootstrapped = false;

    /**
     * @var Zend_EventManager_EventManager
     */
    protected $_events;

    /**
     * @return Zend_EventManager_EventManager
     */
    public function getEventManager()
    {
        if (null === $this->_events) {
            $this->_events = new Zend_EventManager_EventManager(__CLASS__);
        }
        return $this->_events;
    }

    /**
     * @param Maniple_Security_ContextAbstract $securityContext
     * @param $resource
     * @param $permission
     * @return bool
     */
    public function isAllowed(Maniple_Security_ContextAbstract $securityContext, $resource, $permission)
    {
        if ($securityContext->isSuperUser()) {
            return true;
        }

        $handler = $this->getRuleForResource($resource, $permission);
        if ($handler) {
            return $handler->isAllowed($securityContext, $resource, $permission);
        }

        return false;
    }

    /**
     * @param $resource
     * @param $permission
     * @return Maniple_Security_AccessControl_RuleInterface|null
     */
    public function getRuleForResource($resource, $permission)
    {
        foreach ($this->getRules() as $rule) {
            if ($rule->supports($resource, $permission)) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * @param Maniple_Security_AccessControl_RuleInterface|callable $rule
     * @param int $priority
     * @return $this
     * @throws Exception When handlers collection is already bootstrapped, i.e. handlers were retrieved
     * @throws InvalidArgumentException When handler is neither an instance of
     *                                  Maniple_Security_AccessDecisionInterface
     *                                  nor a callable
     */
    public function registerRule($rule, $priority = 1)
    {
        if ($this->_isBootstrapped) {
            throw new Exception('Access control is already bootstrapped, you cannot register more handlers');
        }

        if ($rule instanceof Maniple_Security_AccessControl_RuleInterface) {
            $callback = function () use ($rule) {
                return $rule;
            };
        } elseif (is_callable($rule)) {
            $callback = function (Zend_EventManager_Event $event) use ($rule) {
                $result = call_user_func($rule, $event->getTarget());
                if (!$result instanceof Maniple_Security_AccessControl_RuleInterface) {
                    $result = false;
                }
                return $result;
            };
        } else {
            throw new InvalidArgumentException('Handler must be an instanceof HandlerInterface or a callable');
        }

        $this->getEventManager()->attach(self::EVENT_REGISTER_RULE, $callback, $priority);

        return $this;
    }

    /**
     * @return Maniple_Security_AccessControl_RuleInterface[]
     */
    public function getRules()
    {
        if (!$this->_isBootstrapped) {
            $rules = array();

            $this->getEventManager()->trigger(self::EVENT_REGISTER_RULE, $this, array(), function ($rule) use (&$rules) {
                if ($rule instanceof Maniple_Security_AccessControl_RuleInterface) {
                    $rules[] = $rule;
                }
            });

            $this->_rules = $rules;
            $this->_isBootstrapped = true;
        }

        return $this->_rules;
    }
}
