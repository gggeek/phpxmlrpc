<?php

namespace PhpXmlRpc\Interfaces;

interface ObjectValue
{
    /**
     * @param string $className
     * @return $this
     */
    public function setClass($className);

    /**
     * @return string
     */
    public function getClass();
}
