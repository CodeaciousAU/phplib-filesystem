<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: TemporaryDirectory.php 861 2014-05-09 11:10:38Z glenn $
 */

namespace Codeacious\Filesystem;

/**
 * A temporary directory.
 */
class TemporaryDirectory extends Directory implements TemporaryObject
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
                    $this->delete(true);
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
     * @return \Codeacious\Filesystem\TemporaryDirectory This 
     */
    public function setDeleteOnExit($bool)
    {
        $this->deleteOnExit = ($bool == true);
        
        return $this;
    }
    
    /**
     * Create a temporary directory.
     * 
     * @param string $parent The path under which the directory should be created. If not supplied,
     *    the system temp directory is assumed.
     * 
     * @return \Codeacious\Filesystem\TemporaryDirectory
     * @throws \Codeacious\Filesystem\Exception 
     */
    public static function createNew($parent=null)
    {
        if (!$parent)
            $parent = sys_get_temp_dir();
        
        //Create a temp file, to generate a unique name
        $filePath = tempnam($parent, 'TemporaryDir_');
        if (!$filePath)
            throw new Exception('Unable to create a temporary file in '.$parent);
        
        //Replace the file with a directory
        unlink($filePath);
        $dir = new static($filePath);
        $dir->create();
        
        return $dir;
    }
}
