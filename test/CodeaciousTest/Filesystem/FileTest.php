<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: FileTest.php 890 2014-05-24 05:02:14Z glenn $
 */

namespace CodeaciousTest\Filesystem;

use Codeacious\Filesystem\Directory;
use Codeacious\Filesystem\File;
use PHPUnit_Framework_TestCase as TestCase;

class FileTest extends TestCase
{
    /**
     * @test
     */
    public function queryExisting()
    {
        $file = new File('/etc/passwd');
        $this->assertTrue($file->exists());
        $this->assertTrue($file->isReadable());
        $this->assertFalse($file->isWritable());
    }
    
    /**
     * @test
     */
    public function getNameAndExtension()
    {
        $file = new File('/tmp/file.jpg');
        $this->assertEquals('file.jpg', $file->getName());
        $this->assertEquals('jpg', $file->getExtension());
        $this->assertEquals('file', $file->getNameWithoutExtension());
        
        $file = new File('/tmp/file');
        $this->assertEquals('file', $file->getName());
        $this->assertNull($file->getExtension());
        $this->assertEquals('file', $file->getNameWithoutExtension());
        
        $file = new File('/tmp/.dotfile');
        $this->assertEquals('.dotfile', $file->getName());
        $this->assertNull($file->getExtension());
        $this->assertEquals('.dotfile', $file->getNameWithoutExtension());
    }
    
    /**
     * @test
     */
    public function getParent()
    {
        $testFile = new File('/tmp/unittest_file_'.time());
        $this->assertEquals('/tmp', $testFile->getParentPath());
        $parentDir = $testFile->getParentDirectory();
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $parentDir);
        $this->assertEquals(realpath('/tmp'), $parentDir->getPath());
    }
    
    /**
     * @test
     * @return \Codeacious\Filesystem\File
     */
    public function create()
    {
        $testFile = new File('/tmp/unittest_file_'.time());
        
        $this->assertFalse($testFile->exists());
        $this->assertFalse($testFile->isReadable());
        $this->assertFalse($testFile->isWritable());
        $this->assertNull($testFile->getSize());
        
        $this->assertSame($testFile, $testFile->setContents('test'));
        
        $this->assertTrue($testFile->exists());
        $this->assertTrue($testFile->isReadable());
        $this->assertTrue($testFile->isWritable());
        $this->assertEquals(4, $testFile->getSize());
        
        return $testFile;
    }
    
    /**
     * @test
     * @depends create
     */
    public function getAndSetContents(File $testFile)
    {
        $testFile->setContents('crumpets');
        $this->assertEquals('crumpets', $testFile->getContents());
        $testFile->setContents('test');
        $this->assertEquals('test', $testFile->getContents());
    }
    
    /**
     * @test
     * @depends create
     */
    public function getAndSetMediaType(File $testFile)
    {
        $this->assertNull($testFile->getMediaType());
        $testFile->setMediaType('text/plain');
        $this->assertEquals('text/plain', $testFile->getMediaType());
    }
    
    /**
     * @test
     * @depends create
     */
    public function getAndSetMetaFilename(File $testFile)
    {
        $this->assertNull($testFile->getMetaFilename());
        $testFile->setMetaFilename('myfile.txt');
        $this->assertEquals('myfile.txt', $testFile->getMetaFilename());
    }
    
    /**
     * @test
     * @depends create
     */
    public function getLastModifiedDate(File $testFile)
    {
        $date = $testFile->getLastModifiedDate();
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertEquals(date_default_timezone_get(), $date->getTimezone()->getName());
        
        $nonExisting = new File('/this_file_does_not_exist');
        $this->assertFalse($nonExisting->exists());
        $this->assertNull($nonExisting->getLastModifiedDate());
    }
    
    /**
     * @test
     * @depends create
     */
    public function copy(File $testFile)
    {
        $destPath = '/tmp/'.$testFile->getName().'_copy';
        
        $copy = $testFile->copyTo($destPath);
        $this->assertInstanceOf('\Codeacious\Filesystem\File', $copy);
        $this->assertNotSame($testFile, $copy);
        
        $this->assertEquals(basename($destPath), $copy->getName());
        $this->assertTrue($copy->exists());
        $this->assertEquals(4, $copy->getSize());
        
        $copy->delete(true);
    }
    
    /**
     * @test
     * @depends create
     */
    public function move(File $testFile)
    {
        $destPath = '/tmp/'.$testFile->getName().'_container';
        $destDir = new Directory($destPath);
        $destDir->create();
        
        $result = $testFile->moveTo($destDir);
        $this->assertSame($testFile, $result);
        
        $this->assertEquals(realpath($destPath), $testFile->getParentPath());
        $this->assertTrue($testFile->exists());
        
        $testFile->moveTo('/tmp');
        $destDir->delete(true);
    }
    
    /**
     * @test
     * @depends create
     */
    public function rename(File $testFile)
    {
        $originalName = $testFile->getName();
        $newName = $originalName.'_renamed';
        
        $result = $testFile->renameTo($newName);
        $this->assertSame($testFile, $result);
        
        $this->assertEquals($newName, $testFile->getName());
        $this->assertTrue($testFile->exists());
        
        $testFile->renameTo($originalName);
    }
    
    /**
     * @test
     * @depends create
     */
    public function delete(File $testFile)
    {
        $testFile->delete();
        $this->assertFalse($testFile->exists());
    }
}
