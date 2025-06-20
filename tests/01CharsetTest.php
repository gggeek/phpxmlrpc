<?php
/**
 * @author JoakimLofgren
 */

include_once __DIR__ . '/PolyfillTestCase.php';

use PhpXmlRpc\Helper\Charset;

/**
 * Test conversion between encodings via usage of the Charset class.
 * Note that quite a few other tests testing different classes also test character set conversion.
 *
 * For Windows if you want to test the output use Consolas font and run the following in cmd:
 *     chcp 28591 (latin1)
 *     chcp 65001 (utf8)
 *
 * @todo add tests for conversion: utf8 -> ascii (incl. chars 0-31 and 127)
 * @todo add tests for conversion: latin1 -> utf8
 * @todo add tests for conversion: latin1 -> ascii (incl. chars 0-31 and 127)
 */
class CharsetTest extends PhpXmlRpc_PolyfillTestCase
{
    // Consolas font should render these properly. Of course this file is encoded in UTF-8
    protected $runes = "ᚠᛇᚻ᛫ᛒᛦᚦ᛫ᚠᚱᚩᚠᚢᚱ᛫ᚠᛁᚱᚪ᛫ᚷᛖᚻᚹᛦᛚᚳᚢᛗ";
    protected $greek = "Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ";
    protected $russian = "Река неслася; бедный чёлн";
    protected $chinese = "我能吞下玻璃而不伤身体。";

    protected function utf8ToLatin1($data)
    {
        return Charset::instance()->encodeEntities(
            $data,
            'UTF-8',
            'ISO-8859-1'
        );
    }

    protected function utf8ToAscii($data)
    {
        return Charset::instance()->encodeEntities(
            $data,
            'UTF-8',
            'US-ASCII'
        );
    }

    public function testUtf8ToLatin1All()
    {
        $latinString = "\n\r\t";
        for($i = 32; $i < 127; $i++) {
            $latinString .= chr($i);
        }
        for($i = 160; $i < 256; $i++) {
            $latinString .= chr($i);
        }

        // the warning suppression is due to utf8_encode being deprecated in php 8.2
        $string = @utf8_encode($latinString);
        $encoded = $this->utf8ToLatin1($string);
        $this->assertEquals(str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $latinString), $encoded);
    }

    public function testUtf8ToLatin1EuroSymbol()
    {
        $string = 'a.b.c.å.ä.ö.€.';
        $encoded = $this->utf8ToLatin1($string);
        // the warning suppression is due to utf8_decode being deprecated in php 8.2
        $this->assertEquals(@utf8_decode('a.b.c.å.ä.ö.&#8364;.'), $encoded);
    }

    public function testUtf8ToLatin1Runes()
    {
        $string = $this->runes;
        $encoded = $this->utf8ToLatin1($string);
        $this->assertEquals('&#5792;&#5831;&#5819;&#5867;&#5842;&#5862;&#5798;&#5867;&#5792;&#5809;&#5801;&#5792;&#5794;&#5809;&#5867;&#5792;&#5825;&#5809;&#5802;&#5867;&#5815;&#5846;&#5819;&#5817;&#5862;&#5850;&#5811;&#5794;&#5847;', $encoded);
    }

    public function testUtf8ToLatin1Greek()
    {
        $string = $this->greek;
        $encoded = $this->utf8ToLatin1($string);
        $this->assertEquals('&#932;&#8052; &#947;&#955;&#8182;&#963;&#963;&#945; &#956;&#959;&#8166; &#7956;&#948;&#969;&#963;&#945;&#957; &#7953;&#955;&#955;&#951;&#957;&#953;&#954;&#8052;', $encoded);
    }

    public function testUtf8ToLatin1Russian()
    {
        $string = $this->russian;
        $encoded = $this->utf8ToLatin1($string);
        $this->assertEquals('&#1056;&#1077;&#1082;&#1072; &#1085;&#1077;&#1089;&#1083;&#1072;&#1089;&#1103;; &#1073;&#1077;&#1076;&#1085;&#1099;&#1081; &#1095;&#1105;&#1083;&#1085;', $encoded);
    }

    public function testUtf8ToLatin1Chinese()
    {
        $string = $this->chinese;
        $encoded = $this->utf8ToLatin1($string);
        $this->assertEquals('&#25105;&#33021;&#21534;&#19979;&#29627;&#29827;&#32780;&#19981;&#20260;&#36523;&#20307;&#12290;', $encoded);
    }

    public function testLatin15()
    {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Miss mbstring extension to test exotic charsets');
        }

        // euro symbol in ISO-8859-15
        $string = chr(164);
        $encoder = Charset::instance();
        $this->assertEquals('€', $encoder->encodeEntities($string, 'ISO-8859-15', 'UTF-8'));
        $this->assertEquals('&#8364;', $encoder->encodeEntities($string, 'ISO-8859-15', 'US-ASCII'));
        $this->assertEquals(chr(164), $encoder->encodeEntities($string, 'ISO-8859-15', 'ISO-8859-15'));
    }
}
