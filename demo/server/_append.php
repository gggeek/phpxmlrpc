<?php

// Out-of-band information: let the client manipulate the server operations.
// We do this to help the testsuite script: do not reproduce in production!
if (isset($_COOKIE['PHPUNIT_SELENIUM_TEST_ID']) && extension_loaded('xdebug')) {
    include_once __DIR__ . "/../../vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/append.php";
}
