<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Xml_Security
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * @category   Zend
 * @package    Zend_Xml_Security
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
#[Group('Zend_Xml')]
#[Group('ZF2015-06')]
class Zend_Xml_MultibyteTest extends PHPUnit\Framework\TestCase
{
    public static function multibyteEncodings()
    {
        return array(
            'UTF-16LE' => array('UTF-16LE', pack('CC', 0xff, 0xfe), 3),
            'UTF-16BE' => array('UTF-16BE', pack('CC', 0xfe, 0xff), 3),
            'UTF-32LE' => array('UTF-32LE', pack('CCCC', 0xff, 0xfe, 0x00, 0x00), 4),
            'UTF-32BE' => array('UTF-32BE', pack('CCCC', 0x00, 0x00, 0xfe, 0xff), 4),
        );
    }

    public function getXmlWithXXE()
    {
        return <<<XML
<?xml version="1.0" encoding="{ENCODING}"?>
<!DOCTYPE methodCall [
  <!ENTITY pocdata SYSTEM "file:///etc/passwd">
]>
<methodCall>
    <methodName>retrieved: &pocdata;</methodName>
</methodCall>
XML;
    }

    /**
     * Invoke Zend_Xml_Security::heuristicScan with the provided XML.
     *
     * @param string $xml
     * @return void
     * @throws Zend_Xml_Exception
     */
    public function invokeHeuristicScan($xml)
    {
        Zend_Xml_TestAsset_Security::heuristicScan($xml);
    }

    #[DataProvider('multibyteEncodings')]
    #[Group('heuristicDetection')]
    public function testDetectsMultibyteXXEVectorsUnderFPMWithEncodedStringMissingBOM($encoding, $bom, $bomLength)
    {
        $xml = $this->getXmlWithXXE();
        $xml = str_replace('{ENCODING}', $encoding, $xml);
        $xml = iconv('UTF-8', $encoding, $xml);
        $this->assertNotSame(0, strncmp($xml, $bom, $bomLength));
        $this->expectException('Zend_Xml_Exception');
        $this->expectExceptionMessage('ENTITY');
        $this->invokeHeuristicScan($xml);
    }

    #[DataProvider('multibyteEncodings')]
    public function testDetectsMultibyteXXEVectorsUnderFPMWithEncodedStringUsingBOM($encoding, $bom, $bomLength)
    {
        $xml  = $this->getXmlWithXXE();
        $xml  = str_replace('{ENCODING}', $encoding, $xml);
        $orig = iconv('UTF-8', $encoding, $xml);
        $xml  = $bom . $orig;
        $this->expectException('Zend_Xml_Exception');
        $this->expectExceptionMessage('ENTITY');
        $this->invokeHeuristicScan($xml);
    }

    public function getXmlWithoutXXE()
    {
        return <<<XML
<?xml version="1.0" encoding="{ENCODING}"?>
<methodCall>
    <methodName>retrieved: &pocdata;</methodName>
</methodCall>
XML;
    }

    #[DataProvider('multibyteEncodings')]
    public function testDoesNotFlagValidMultibyteXmlAsInvalidUnderFPM($encoding, $bom, $bomLength)
    {
        $xml = $this->getXmlWithoutXXE();
        $xml = str_replace('{ENCODING}', $encoding, $xml);
        $xml = iconv('UTF-8', $encoding, $xml);
        try {
            $this->invokeHeuristicScan($xml);
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('Security scan raised exception when it should not have');
        }
    }

    #[DataProvider('multibyteEncodings')]
    #[Group('mixedEncoding')]
    public function testDetectsXXEWhenXMLDocumentEncodingDiffersFromFileEncoding($encoding, $bom, $bomLength)
    {
        $xml = $this->getXmlWithXXE();
        $xml = str_replace('{ENCODING}', 'UTF-8', $xml);
        $xml = iconv('UTF-8', $encoding, $xml);
        $xml = $bom . $xml;
        $this->expectException('Zend_Xml_Exception');
        $this->expectExceptionMessage('ENTITY');
        $this->invokeHeuristicScan($xml);
    }
}
