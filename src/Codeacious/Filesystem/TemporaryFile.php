<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: TemporaryFile.php 861 2014-05-09 11:10:38Z glenn $
 */

namespace Codeacious\Filesystem;

/**
 * A temporary file.
 */
class TemporaryFile extends File implements TemporaryObject
{
    /**
     * @var boolean 
     */
    private $deleteOnExit = true;
    
    
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct($path);
    }
    
    /**
     * @return void 
     */
    public function __destruct()
    {
        if ($this->deleteOnExit)
        {
            try
            {
                if ($this->exists())
                    $this->delete();
            }
            catch (Exception $e)
            {}
        }
    }
    
    /**
     * Check whether the item will be deleted at the end of the current PHP invocation.
     * 
     * @return boolean 
     */
    public function getDeleteOnExit()
    {
        return $this->deleteOnExit;
    }
    
    /**
     * Set whether the item should be deleted at the end of the current PHP invocation.
     * 
     * Note that setting this to false does not guarantee the item will be kept permanently. If it
     * resides in a system temporary directory, it may be cleaned up by the OS eventually.
     * 
     * @param boolean $bool
     * 
     * @return \Codeacious\Filesystem\TemporaryFile This 
     */
    public function setDeleteOnExit($bool)
    {
        $this->deleteOnExit = ($bool == true);
        
        return $this;
    }
    
    /**
     * Create a temporary file.
     * 
     * @param string $tempDir The path to the directory where the file should be created. If not
     *    supplied, the system temp directory is used.
     * 
     * @return \Codeacious\Filesystem\TemporaryFile
     * @throws \Codeacious\Filesystem\Exception 
     */
    public static function createNew($tempDir=null)
    {
        if (!$tempDir)
            $tempDir = sys_get_temp_dir();
        $filePath = tempnam($tempDir, 'TemporaryFile_');
        
        if (!$filePath)
            throw new Exception('Unable to create a temporary file in '.$tempDir);
        
        return new static($filePath);
    }
}
