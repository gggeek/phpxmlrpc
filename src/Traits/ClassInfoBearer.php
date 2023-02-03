<?php

namespace PhpXmlRpc\Traits;

trait ClassInfoBearer
{
    /** @var string|null */
    public $_php_class = null;

    /**
     * @param string $className
     * @return $this
     */
    public function setClass($className)
    {
        $this->_php_class = $className;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->_php_class;
    }
}
