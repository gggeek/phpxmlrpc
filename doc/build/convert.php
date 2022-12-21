<?php
/**
 * Script used to convert docbook source to human-readable docs
 *
 * @copyright (c) 2007-2022 G. Giunta
 *
 * @todo rename to something akin to xsltproc
 */

if ($_SERVER['argc'] < 4) {
    error("Usage: php convert.php docbook.xml stylesheet.xsl output-dir|output_file");
}

$doc = $_SERVER['argv'][1];
$xss = $_SERVER['argv'][2];
$target = $_SERVER['argv'][3];

if (!file_exists($doc))
  error("KO: file $doc cannot be found");
if (!file_exists($xss))
  error("KO: file $xss cannot be found");

info("Starting xsl conversion process...");

// Replace tokens in the existing xslt file
$docbookFoXslPath = realpath('./build/vendor/docbook/docbook-xsl/fo/docbook.xsl');
$docbookChunkXslPath = realpath('./build/vendor/docbook/docbook-xsl/xhtml/chunk.xsl');
file_put_contents(
    $xss,
    str_replace(
        array('%fo-docbook.xsl%', '%docbook-chunk.xsl%'),
        array($docbookFoXslPath, $docbookChunkXslPath),
        file_get_contents($xss)
    )
);

// Load the XML source
$xml = new DOMDocument;
$xml->load($doc);
$xsl = new DOMDocument;
$xsl->load($xss);

// Configure the transformer
$processor = new XSLTProcessor;
if (version_compare(PHP_VERSION, '5.4', "<")) {
    if (defined('XSL_SECPREF_WRITE_FILE')) {
        ini_set("xsl.security_prefs", XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_WRITE_FILE);
    }
} else {
    // the php online docs only mention setSecurityPrefs, but somehow some installs have setSecurityPreferences...
    if (method_exists('XSLTProcessor', 'setSecurityPrefs')) {
        $processor->setSecurityPrefs(XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_WRITE_FILE);
    } else {
        $processor->setSecurityPreferences(XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_WRITE_FILE);
    }
}
if (is_dir($target))
{
    if (!$processor->setParameter('', 'base.dir', $target)) {
        error("KO setting param base.dir");
    }
}

// attach the xsl rules
$processor->importStyleSheet($xsl);

$out = $processor->transformToXML($xml);

// bring back the xsl file to its pristine state
file_put_contents(
    $xss,
    str_replace(
        array($docbookFoXslPath, $docbookChunkXslPath),
        array('%fo-docbook.xsl%', '%docbook-chunk.xsl%'),
        file_get_contents($xss)
    )
);

if (!is_dir($target)) {
    if (!file_put_contents($target, $out)) {
        error("KO saving output to '{$target}'");
    }
}

info("OK");

// *** functions ***

function info($msg)
{
    echo "$msg\n";
}

function error($msg, $errcode=1)
{
    fwrite(STDERR, "$msg\n");
    exit($errcode);
}
