<?php

namespace PhpXmlRpc\Traits;

use PhpXmlRpc\Helper\Charset;

trait CharsetEncoderAware
{
    protected static $charsetEncoder;
    protected static $charsetEncoderClass = '\\PhpXmlRpc\\Helper\\Charset';

    public function getCharsetEncoder()
    {
        if (self::$charsetEncoder === null) {
            self::$charsetEncoder = call_user_func(array(static::$charsetEncoderClass, 'instance'));
        }
        return self::$charsetEncoder;
    }

    /**
     * @param $charsetEncoder
     * @return void
     */
    public static function setCharsetEncoder($charsetEncoder)
    {
        self::$charsetEncoder = $charsetEncoder;
    }
}
