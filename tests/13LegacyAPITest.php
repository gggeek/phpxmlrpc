<?php

include_once __DIR__ . '/ServerAwareTestCase.php';

/**
 * @todo Long-term, this should replace all testing of the legacy API done via the main test-suite...
 * - usage of xmlrpc_ class names
 * - calling deprecated methods
 * - calling of methods using deprecated conventions
 */
class LegacyAPITest extends PhpXmlRpc_ServerAwareTestCase
{
    public function testLegacyLoader()
    {
        /// @todo pass on as cli args for the executed script all the args that are already parsed by now, plus $this->testId

        exec('php ' . __DIR__ . '/legacy_loader_test.php', $out, $result);

        /// @todo dump output if in debug mode or if test fails

        $this->assertEquals(0, $result);
    }
}
