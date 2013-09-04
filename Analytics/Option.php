<?php
/**
 * apollon
 *
 * Copyright (c) 2012-2013, Trivago GmbH
 * All rights reserved.
 *
 * @since 04.09.13
 * @author Innovation Center Leipzig <team.leipzig@trivago.com>
 * @author Roman Lasinski <roman.lasinski@trivago.com>
 * @copyright 2012-2013 Trivago GmbH
 */
namespace AntiMattr\GoogleBundle\Analytics;

/**
 * Class Option
 *
 * @package AntiMattr\GoogleBundle\Analytics
 * @author Innovation Center Leipzig <team.leipzig@trivago.com>
 * @author Roman Lasinski <roman.lasinski@trivago.com>
 * @copyright 2012-2013 Trivago GmbH
 */
class Option
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param $name
     * @param $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Set the {@see $name} property.
     *
     * @param mixed $name
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the {@see $name} property.
     *
     * @return mixed Returns the <em>$name</em> property.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the {@see $value} property.
     *
     * @param mixed $value
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get the {@see $value} property.
     *
     * @return mixed Returns the <em>$value</em> property.
     */
    public function getValue()
    {
        return $this->value;
    }
}