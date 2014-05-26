<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: TemporaryFileTest.php 861 2014-05-09 11:10:38Z glenn $
 */

namespace CodeaciousTest\Filesystem;

use Codeacious\Filesystem\TemporaryFile;
use PHPUnit_Framework_TestCase as TestCase;

class TemporaryFileTest extends TestCase
{
    /**
     * @test
     */
    public function create()
    {
        $file = TemporaryFile::createNew();
        $this->assertInstanceOf('\Codeacious\Filesystem\TemporaryFile', $file);
        $this->assertTrue($file->exists());
        $this->assertTrue($file->getDeleteOnExit());
    }
}
