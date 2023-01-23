<?php

include_once __DIR__ . '/parse_args.php';

include_once __DIR__ . '/PolyfillTestCase.php';

use PHPUnit\Extensions\SeleniumCommon\RemoteCoverage;

abstract class PhpXmlRpc_WebTestCase extends PhpXmlRpc_PolyfillTestCase
{
    public $args = array();

    protected $baseUrl;

    protected $testId;
    /** @var boolean $collectCodeCoverageInformation */
    protected $collectCodeCoverageInformation;
    protected $coverageScriptUrl;

    /**
     * Reimplemented to allow us to collect code coverage info for the target php files executed via an http request.
     * Code taken from PHPUnit_Extensions_Selenium2TestCase
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

    /**
     * @param string $path
     * @param string $method
     * @param string $payload
     * @param false $emptyPageOk
     * @return bool|string
     */
    protected function request($path, $method = 'GET', $payload = '', $emptyPageOk = false)
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true
        ));
        if ($method == 'POST')
        {
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload
            ));
        }
        if ($this->collectCodeCoverageInformation)
        {
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPUNIT_SELENIUM_TEST_ID='.$this->testId);
        }
        if ($this->args['DEBUG'] > 0) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        }
        $page = curl_exec($ch);
        curl_close($ch);

        $this->assertNotFalse($page);
        if (!$emptyPageOk) {
            $this->assertNotEquals('', $page);
        }
        $this->assertStringNotContainsStringIgnoringCase('Fatal error', $page);
        $this->assertStringNotContainsStringIgnoringCase('Notice:', $page);

        return $page;
    }

    protected function getClient($path)
    {
        $client = new xmlrpc_client($this->baseUrl . $path);
        if ($this->collectCodeCoverageInformation) {
            $client->setCookie('PHPUNIT_SELENIUM_TEST_ID', $this->testId);
        }
        // let's just assume that the client works fine for these tests, and avoid polluting output
        //$client->setAcceptedCompression(false);
        //$client->setDebug($this->args['DEBUG']);
        return $client;
    }
}
