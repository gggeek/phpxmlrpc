<?php

include_once __DIR__ . '/parse_args.php';

class DemoFilesTest extends PHPUnit_Framework_TestCase
{
    public $args = array();

    protected $baseUrl;

    protected $testId;
    /** @var boolean $collectCodeCoverageInformation */
    protected $collectCodeCoverageInformation;
    protected $coverageScriptUrl;

    public function run(PHPUnit_Framework_TestResult $result = NULL)
    {
        $this->testId = get_class($this) . '__' . $this->getName();

        if ($result === NULL) {
            $result = $this->createResult();
        }

        $this->collectCodeCoverageInformation = $result->getCollectCodeCoverageInformation();

        parent::run($result);

        if ($this->collectCodeCoverageInformation) {
            $coverage = new PHPUnit_Extensions_SeleniumCommon_RemoteCoverage(
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

    public function setUp()
    {
        $this->args = argParser::getArgs();

        $this->baseUrl = $this->args['LOCALSERVER'] . str_replace( '/demo/server/server.php', '/demo/', $this->args['URI'] );

        $this->coverageScriptUrl = 'http://' . $this->args['LOCALSERVER'] . '/' . str_replace( '/demo/server/server.php', 'tests/phpunit_coverage.php', $this->args['URI'] );
    }

    protected function request($file, $method = 'GET', $payload = '')
    {
        $url = $this->baseUrl . $file;

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
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPUNIT_SELENIUM_TEST_ID=true');
        }
        $page = curl_exec($ch);
        curl_close($ch);

        $this->assertNotFalse($page);
        $this->assertNotContains('Fatal error', $page);

        return $page;
    }

    public function testAgeSort()
    {
        $page = $this->request('client/agesort.php');
    }

    public function testClient()
    {
        $page = $this->request('client/client.php');

        // we could test many more calls to the client demo, but the upstream server is gone anyway...

        $page = $this->request('client/client.php', 'POST', array('stateno' => '1'));
    }

    public function testComment()
    {
        $page = $this->request('client/comment.php');
        $page = $this->request('client/client.php', 'POST', array('storyid' => '1'));
    }

    public function testIntrospect()
    {
        $page = $this->request('client/introspect.php');
    }

    public function testMail()
    {
        $page = $this->request('client/mail.php');
        $page = $this->request('client/client.php', 'POST', array(
            'server' => '',
            "mailto" => '',
            "mailsub" => '',
            "mailmsg" => '',
            "mailfrom" => '',
            "mailcc" => '',
            "mailbcc" => '',
        ));
    }

    public function testSimpleCall()
    {
        $page = $this->request('client/simple_call.php');
    }

    public function testWhich()
    {
        $page = $this->request('client/which.php');
    }

    public function testWrap()
    {
        $page = $this->request('client/wrap.php');
    }

    public function testZopeTest()
    {
        $page = $this->request('client/zopetest.php');
    }

    public function testDiscussServer()
    {
        $page = $this->request('server/discuss.php');
        $this->assertContains('<name>faultCode</name>', $page);
        $this->assertContains('<int>105</int>', $page);
    }

    public function testProxyServer()
    {
        $page = $this->request('server/proxy.php');
        $this->assertContains('<name>faultCode</name>', $page);
        $this->assertContains('<int>105</int>', $page);
    }
}
