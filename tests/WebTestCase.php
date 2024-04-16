<?php

include_once __DIR__ . '/ServerAwareTestCase.php';

abstract class PhpXmlRpc_WebTestCase extends PhpXmlRpc_ServerAwareTestCase
{
    /**
     * Make an HTTP request, check that the result is a 200 OK page with no php fatal error or warning messages.
     *
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
        $cookie = 'PHPUNIT_RANDOM_TEST_ID=' . static::$randId;
        if ($this->collectCodeCoverageInformation)
        {
            $cookie .= '; PHPUNIT_SELENIUM_TEST_ID=' . $this->testId;
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        if ($this->args['DEBUG'] > 0) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        }
        $page = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $this->assertNotFalse($page, 'Curl request should not fail. Url: ' . @$info['url'] . ', Http code: ' . @$info['http_code']);
        if (!$emptyPageOk) {
            $this->assertNotEquals('', $page, 'Retrieved web page should not be empty');
        }
        $this->assertStringNotContainsStringIgnoringCase('Fatal error', $page, 'Retrieved web page should not contain a fatal error string');
        $this->assertStringNotContainsStringIgnoringCase('Notice:', $page, 'Retrieved web page should not contain a notice string');

        return $page;
    }

    /**
     * Build an xml-rpc client, tweaked if needed to collect code-coverage information of the server.
     * @see also ServerTest::set_up
     *
     * @param string $path
     * @return \PhpXmlRpc\Client
     */
    protected function newClient($path)
    {
        $client = new \PhpXmlRpc\Client($this->baseUrl . $path);
        $client->setCookie('PHPUNIT_RANDOM_TEST_ID', static::$randId);
        if ($this->collectCodeCoverageInformation) {
            $client->setCookie('PHPUNIT_SELENIUM_TEST_ID', $this->testId);
        }
        // let's just assume that the client works fine for these tests, and avoid polluting output, even in debug mode
        //$client->setOption(Client::OPT_ACCEPTED_COMPRESSION, false);
        //$client->setDebug($this->args['DEBUG']);
        return $client;
    }
}
