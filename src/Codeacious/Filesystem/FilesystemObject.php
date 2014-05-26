<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: FilesystemObject.php 890 2014-05-24 05:02:14Z glenn $
 */

namespace Codeacious\Filesystem;

/**
 * An object on the filesystem.
 */
abstract class FilesystemObject
{
    /**
     * @var string
     */
    protected $path;
    
    
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }
    
    /**
     * @return boolean 
     */
    public abstract function exists();
    
    /**
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Return the last component of the path.
     * 
     * @return string 
     */
    public function getName()
    {
        return basename($this->path);
    }
    
    /**
     * Return the portion of the last path component which follows the final period, if there is
     * one.
     * 
     * @return string|null
     */
    public function getExtension()
    {
        $name = $this->getName();
        if (($pos = strrpos($name, '.')) == 0)
            return null;
        
        return substr($name, $pos+1);
    }
    
    /**
     * Return the last component of the path, after removing the final period and any characters
     * following it.
     * 
     * @return string
     */
    public function getNameWithoutExtension()
    {
        $name = $this->getName();
        if (($pos = strrpos($name, '.')) == 0)
            return $name;
        
        return substr($name, 0, 0-$pos);
    }
    
    /**
     * Return the path to the directory that this item is in.
     * 
     * @return string|null Null if this item is the root directory
     */
    public function getParentPath()
    {
        $path = dirname($this->path);
        if ($path == $this->path)
            return null;
        
        return $path;
    }
    
    /**
     * Return the directory that this item is in.
     * 
     * @return \Codeacious\Filesystem\Directory|null Null if this item is the root directory
     */
    public function getParentDirectory()
    {
        $path = $this->getParentPath();
        if (!$path)
            return null;
        
        return new Directory($path);
    }
    
    /**
     * Get the modification date of the item.
     * 
     * @return \DateTime|null A date (in the current timezone) or null if the item doesn't exist
     * @throws \Codeacious\Filesystem\Exception 
     */
    public function getLastModifiedDate()
    {
        if (!$this->exists())
            return null;
        
        $timestamp = @filemtime($this->path);
        if ($timestamp === false)
        {
            throw new Exception('Unable to determine modification date of '.$this->path
                .$this->_lastWarning());
        }
        
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        return $date;
    }
    
    /**
     * Move the item to a new location, optionally changing its name.
     * 
     * @param string|\Codeacious\Filesystem\Directory $newPath The full path that the item will now
     *    have, or the path to the directory that will contain the item.
     * 
     * @return void
     * @throws \Codeacious\Filesystem\Exception
     */
    protected function _moveTo($newPath)
    {
        if (is_object($newPath))
        {
            if (! $newPath instanceof Directory)
                throw new Exception('Invalid argument type');
            $newPath = $newPath->getPath();
        }
        
        if (!$this->exists())
        {
            throw new Exception('The item '.$this->path.' cannot be moved because it does not '
                .'exist');
        }
        
        if (is_dir($newPath))
            $newPath .= DIRECTORY_SEPARATOR.$this->getName();
        
        if (!@rename($this->path, $newPath))
        {
            throw new Exception('Unable to move '.$this->path.' to '.$newPath.
                $this->_lastWarning());
        }
        
        $this->path = realpath($newPath);
    }
    
    /**
     * Copy the item to a new location, optionally changing its name.
     * 
     * @param string|\Codeacious\Filesystem\Directory $newPath The full path that the new item will
     *    have, or the path to the directory that will contain the item.
     * 
     * @return string The path to the new items
     * @throws \Codeacious\Filesystem\Exception
     */
    protected function _copyTo($newPath)
    {
        if (is_object($newPath))
        {
            if (! $newPath instanceof Directory)
                throw new Exception('Invalid argument type');
            $newPath = $newPath->getPath();
        }
        
        if (!$this->exists())
        {
            throw new Exception('The item '.$this->path.' cannot be copied because it does not '
                .'exist');
        }
        
        if (is_dir($newPath))
            $newPath .= DIRECTORY_SEPARATOR.$this->getName();
        
        if (!@copy($this->path, $newPath))
        {
            throw new Exception('Unable to copy '.$this->path.' to '.$newPath.
                $this->_lastWarning());
        }
        
        return $newPath;
    }
    
    /**
     * Delete this item, it is exists.
     * 
     * @return void
     * @throws \Codeacious\Filesystem\Exception
     */
    protected function _delete()
    {
        if (!$this->exists())
            return;
        
        if (!@unlink($this->path))
        {
            throw new Exception('Unable to delete '.$this->path.$this->_lastWarning());
        }
    }
    
    /**
     * Converts the last PHP warning to be raised from this file into an error string that can be
     * included in an exception message.
     * 
     * PHP filesystem function typically generate a warning with details when they fail.
     * 
     * @return string 
     */
    private function _lastWarning()
    {
        if (($e = error_get_last()))
        {
            if ($e['type'] == E_WARNING && $e['file'] == __FILE__)
                return ' ('.strip_tags($e['message']).')';
        }
        return '';
    }
}
