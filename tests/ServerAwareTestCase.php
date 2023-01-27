<?php

include_once __DIR__ . '/LoggerAwareTestCase.php';

use PHPUnit\Extensions\SeleniumCommon\RemoteCoverage;
use PHPUnit\Framework\TestResult;

abstract class PhpXmlRpc_ServerAwareTestCase extends PhpXmlRpc_LoggerAwareTestCase
{
    /** @var string */
    protected $baseUrl;
    /** @var string */
    protected $testId;
    /** @var boolean */
    protected $collectCodeCoverageInformation;
    /** @var string */
    protected $coverageScriptUrl;

    /**
     * Reimplemented to allow us to collect code coverage info from the target server.
     * Code taken from PHPUnit_Extensions_Selenium2TestCase
     *
     * @param TestResult $result
     * @return TestResult
     * @throws Exception
     *
     * @todo instead of overriding run via _run, try to achieve this by implementing Yoast\PHPUnitPolyfills\TestListeners\TestListenerDefaultImplementation
     */
    public function _run($result = NULL)
    {
        $this->testId = get_class($this) . '__' . $this->getName();

        if ($result === NULL) {
            $result = $this->createResult();
        }

        $this->collectCodeCoverageInformation = $result->getCollectCodeCoverageInformation();

        parent::_run($result);

        if ($this->collectCodeCoverageInformation) {
            $coverage = new RemoteCoverage(
                $this->coverageScriptUrl,
                $this->testId
            );
            $result->getCodeCoverage()->append(
                $coverage->get(), $this
            );
        }

        // do not call this before to give the time to the Listeners to run
        //$this->getStrategy()->endOfTest($this->session);

        return $result;
    }

    public function set_up()
    {
        parent::set_up();

        // assumes HTTPURI to be in the form /tests/index.php?etc...
        $this->baseUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|\?.+|', '', $this->args['HTTPURI']);
        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|/tests/index\.php(\?.*)?|', '/tests/phpunit_coverage.php', $this->args['HTTPURI']);
    }
}
