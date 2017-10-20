<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Diamante\AutomationBundle\Model;

use Diamante\DeskBundle\Model\Shared\Entity;
use Ramsey\Uuid\Uuid;

class Action implements Entity
{
    protected $id;

    protected $type;

    protected $parameters;

    protected $rule;

    protected $weight;

    public function __construct($type, $parameters, $rule, $weight = 0)
    {
        $this->id = Uuid::uuid4();
        $this->type = $type;
        $this->parameters = $parameters;
        $this->rule = $rule;
        $this->weight = $weight;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return Rule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }
}
