<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: DirectoryTest.php 861 2014-05-09 11:10:38Z glenn $
 */

namespace CodeaciousTest\Filesystem;

use Codeacious\Filesystem\Directory;
use Codeacious\Filesystem\File;
use PHPUnit_Framework_TestCase as TestCase;

class DirectoryTest extends TestCase
{
    /**
     * @test
     */
    public function queryExisting()
    {
        $directory = new Directory('/');
        $this->assertTrue($directory->exists());
        $this->assertTrue($directory->isReadable());
        $this->assertFalse($directory->isWritable());
    }
    
    /**
     * @test
     */
    public function getParent()
    {
        $testDirectory = new Directory('/tmp/unittest_directory_'.time());
        $this->assertEquals('/tmp', $testDirectory->getParentPath());
        $parentDir = $testDirectory->getParentDirectory();
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $parentDir);
        $this->assertEquals(realpath('/tmp'), $parentDir->getPath());
    }
    
    /**
     * @test
     * @return \Codeacious\Filesystem\Directory
     */
    public function create()
    {
        $testDirectory = new Directory('/tmp/unittest_directory_'.time());
        
        $this->assertFalse($testDirectory->exists());
        $this->assertFalse($testDirectory->isReadable());
        $this->assertFalse($testDirectory->isWritable());
        
        $this->assertSame($testDirectory, $testDirectory->create());
        
        $this->assertTrue($testDirectory->exists());
        $this->assertTrue($testDirectory->isReadable());
        $this->assertTrue($testDirectory->isWritable());
        
        return $testDirectory;
    }
    
    /**
     * @test
     * @depends create
     */
    public function getLastModifiedDate(Directory $testDirectory)
    {
        $date = $testDirectory->getLastModifiedDate();
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertEquals(date_default_timezone_get(), $date->getTimezone()->getName());
        
        $nonExisting = new Directory('/this_file_does_not_exist');
        $this->assertFalse($nonExisting->exists());
        $this->assertNull($nonExisting->getLastModifiedDate());
    }
    
    /**
     * @test
     * @depends create
     */
    public function getFile(Directory $testDirectory)
    {
        $this->assertFalse($testDirectory->hasChild('testfile'));
        $this->assertNull($testDirectory->getChild('testfile'));
        $this->assertNull($testDirectory->getFile('testfile'));
        
        $testfile = $testDirectory->getFile('testfile', true);
        $this->assertInstanceOf('\Codeacious\Filesystem\File', $testfile);
        if (!touch($testfile->getPath()))
        {
            throw new \Exception('Unable to create a file for test purposes at '
                .$testfile->getPath());
        }
        
        $this->assertTrue($testDirectory->hasChild('testfile'));
        $testfile = $testDirectory->getChild('testfile');
        $this->assertInstanceOf('\Codeacious\Filesystem\File', $testfile);
        $testfile = $testDirectory->getFile('testfile');
        $this->assertInstanceOf('\Codeacious\Filesystem\File', $testfile);
        $this->assertNull($testDirectory->getSubdirectory('testfile'));
    }
    
    /**
     * @test
     * @depends create
     */
    public function getSubdirectory(Directory $testDirectory)
    {
        $this->assertFalse($testDirectory->hasChild('subdir'));
        $this->assertNull($testDirectory->getChild('subdir'));
        $this->assertNull($testDirectory->getSubdirectory('subdir'));
        
        $subdir = $testDirectory->getSubdirectory('subdir', true);
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $subdir);
        $this->assertFalse($subdir->exists());
        $subdir->create();
        
        $this->assertTrue($testDirectory->hasChild('subdir'));
        $subdir = $testDirectory->getChild('subdir');
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $subdir);
        $subdir = $testDirectory->getSubdirectory('subdir');
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $subdir);
        $this->assertNull($testDirectory->getFile('subdir'));
    }
    
    /**
     * @test
     * @depends create
     * @depends getFile
     * @depends getSubdirectory
     */
    public function iterate(Directory $testDirectory)
    {
        $childCount = 0;
        $foundFile = false;
        $foundSubdir = false;
        foreach ($testDirectory as $child)
        {
            $childCount++;
            $this->assertInstanceOf('\Codeacious\Filesystem\FilesystemObject', $child);
            if ($child instanceof File)
            {
                $this->assertEquals('testfile', $child->getName());
                $foundFile = true;
            }
            if ($child instanceof Directory)
            {
                $this->assertEquals('subdir', $child->getName());
                $foundSubdir = true;
            }
        }
        
        $this->assertEquals(2, $childCount);
        $this->assertTrue($foundFile);
        $this->assertTrue($foundSubdir);
    }
    
    /**
     * @test
     * @depends create
     * @depends getFile
     */
    public function copy(Directory $testDirectory)
    {
        $destPath = '/tmp/'.$testDirectory->getName().'_copy';
        
        //Copy by specifying the full destination path, as a string
        $copy = $testDirectory->copyTo($destPath);
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $copy);
        $this->assertNotSame($testDirectory, $copy);
        
        $this->assertEquals(basename($destPath), $copy->getName());
        $this->assertTrue($copy->exists());
        $this->assertTrue($copy->hasChild('testfile'));
        
        //Copy by specifying the destination parent directory, as an object
        $copy2 = $testDirectory->copyTo($copy);
        $this->assertInstanceOf('\Codeacious\Filesystem\Directory', $copy2);
        $this->assertNotSame($testDirectory, $copy2);
        
        $this->assertEquals($testDirectory->getName(), $copy2->getName());
        $this->assertEquals($copy->getPath(), $copy2->getParentPath());
        $this->assertTrue($copy->hasChild($copy2->getName()));
        
        $copy->delete(true);
    }
    
    /**
     * @test
     * @depends create
     */
    public function move(Directory $testDirectory)
    {
        $destPath = '/tmp/'.$testDirectory->getName().'_container';
        $destDir = new Directory($destPath);
        $destDir->create();
        
        $result = $testDirectory->moveTo($destDir);
        $this->assertSame($testDirectory, $result);
        
        $this->assertEquals(realpath($destPath), $testDirectory->getParentPath());
        $this->assertTrue($testDirectory->exists());
        
        $testDirectory->moveTo('/tmp');
        $destDir->delete(true);
    }
    
    /**
     * @test
     * @depends create
     */
    public function rename(Directory $testDirectory)
    {
        $originalName = $testDirectory->getName();
        $newName = $originalName.'_renamed';
        
        $result = $testDirectory->renameTo($newName);
        $this->assertSame($testDirectory, $result);
        
        $this->assertEquals($newName, $testDirectory->getName());
        $this->assertTrue($testDirectory->exists());
        
        $testDirectory->renameTo($originalName);
    }
    
    /**
     * @test
     * @depends create
     * @depends getFile
     * @expectedException \Codeacious\Filesystem\Exception
     */
    public function deleteNotEmpty(Directory $testDirectory)
    {
        $testDirectory->delete();
    }
    
    /**
     * @test
     * @depends create
     */
    public function delete(Directory $testDirectory)
    {
        $testDirectory->delete(true);
        $this->assertFalse($testDirectory->exists());
    }
}
