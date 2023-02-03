<?php

namespace PhpXmlRpc;

use PhpXmlRpc\Interfaces\ObjectValue as ObjectValueInterface;
use PhpXmlRpc\Traits\ClassInfoBearer;

class ObjectValue extends Value implements ObjectValueInterface
{
    use ClassInfoBearer;

    protected function serializeData($typ, $val, $charsetEncoding = '')
    {
        $rs = parent::serializeData($typ, $val, $charsetEncoding);
        if ($this->_php_class != '' && isset(static::$xmlrpcTypes[$typ]) && static::$xmlrpcTypes[$typ] == 3) {
            $rs = preg_replace('/^<struct>/', '<struct php_class="' . $this->_php_class . '">', $rs);
        }
        return $rs;
    }
}
