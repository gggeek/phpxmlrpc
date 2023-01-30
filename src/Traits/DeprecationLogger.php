<?php

namespace PhpXmlRpc\Traits;

use PhpXmlRpc\PhpXmlRpc;

trait DeprecationLogger
{
    use LoggerAware;

    protected function logDeprecation($message)
    {
        if (PhpXmlRpc::$xmlrpc_silence_deprecations) {
            return;
        }

        $this->getLogger()->warning('XML-RPC Deprecated: ' . $message);
    }
}
