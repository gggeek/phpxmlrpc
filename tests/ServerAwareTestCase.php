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

    protected static $randId;

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

    public static function set_up_before_class()
    {
        parent::set_up_before_class();

        // Set up a database connection or other fixture which needs to be available.
        self::$randId = uniqid();
        file_put_contents(sys_get_temp_dir() . '/phpunit_rand_id.txt', self::$randId);
    }

    public static function tear_down_after_class()
    {
        if (is_file(sys_get_temp_dir() . '/phpunit_rand_id.txt')) {
            unlink(sys_get_temp_dir() . '/phpunit_rand_id.txt');
        }

        parent::tear_down_after_class();
    }

    public function set_up()
    {
        parent::set_up();

        // assumes HTTPURI to be in the form /tests/index.php?etc...
        $this->baseUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|\?.+|', '', $this->args['HTTPURI']);
        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|/tests/index\.php(\?.*)?|', '/tests/phpunit_coverage.php', $this->args['HTTPURI']);
    }

    protected function getClient()
    {
        $server = explode(':', $this->args['HTTPSERVER']);
        /// @todo use the non-legacy API calling convention, except in a dedicated test
        if (count($server) > 1) {
            $client = new xmlrpc_client($this->args['HTTPURI'], $server[0], $server[1]);
        } else {
            $client = new xmlrpc_client($this->args['HTTPURI'], $this->args['HTTPSERVER']);
        }

        $client->setDebug($this->args['DEBUG']);

        $client->setCookie('PHPUNIT_RANDOM_TEST_ID', static::$randId);

        if ($this->collectCodeCoverageInformation) {
            $client->setCookie('PHPUNIT_SELENIUM_TEST_ID', $this->testId);
        }

        return $client;
    }

    /**
     * Dataprovider method: generates the list of test cases for tests which have to be run on curl vs. socket
     * @return array[]
     */
    public function getAvailableUseCurlOptions()
    {
        $opts = array(array(\PhpXmlRpc\Client::USE_CURL_NEVER));
        if (function_exists('curl_init'))
        {
            $opts[] = array(\PhpXmlRpc\Client::USE_CURL_ALWAYS);
        }

        return $opts;
    }
}
