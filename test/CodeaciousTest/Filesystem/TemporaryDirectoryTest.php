<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: TemporaryDirectoryTest.php 861 2014-05-09 11:10:38Z glenn $
 */

use Codeacious\Filesystem\TemporaryDirectory;
use PHPUnit_Framework_TestCase as TestCase;

class TemporaryDirectoryTest extends TestCase
{
    /**
     * @test
     */
    public function create()
    {
        $dir = TemporaryDirectory::createNew();
        $this->assertInstanceOf('\Codeacious\Filesystem\TemporaryDirectory', $dir);
        $this->assertTrue($dir->exists());
        $this->assertTrue($dir->getDeleteOnExit());
    }
}
