<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: Directory.php 868 2014-05-20 10:10:19Z glenn $
 */

namespace Codeacious\Filesystem;

use Codeacious\ExternalProcess\Shell;

/**
 * Represents a directory on the filesystem.
 * 
 * The directory does not necessarily exist.
 */
class Directory extends FilesystemObject implements \Iterator
{
    /**
     * @var resource Directory resource used for iteration
     */
    private $resource;
    
    /**
     * @var string Name of the current child during iteration
     */
    private $currentChild;
    
    
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        if (is_dir($path))
            $path = realpath($path);
        parent::__construct($path);
    }
    
    /**
     * @return boolean 
     */
    public function exists()
    {
        return is_dir($this->path);
    }
    
    /**
     * @return boolean 
     */
    public function isReadable()
    {
        return ($this->exists() && is_readable($this->path));
    }
    
    /**
     * @return boolean 
     */
    public function isWritable()
    {
        return ($this->exists() && is_writable($this->path));
    }
    
    /**
     * @param integer $mode The permissions to set on the new directory
     * @param boolean $makeParents Whether to recursively create parent directories as necessary
     * 
     * @return \Codeacious\Filesystem\Directory This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function create($mode=0777, $makeParents=false)
    {
        if (!@mkdir($this->path, $mode, $makeParents))
            throw new Exception('Unable to create directory '.$this->path.$this->_lastWarning());
        
        return $this;
    }
    
    /**
     * Perform a recursive copy of this directory and its contents to a new location.
     * 
     * @param string|\Codeacious\Filesystem\Directory $newPath The full path that the new directory
     *    will have, or the path to the directory that will contain it.
     * 
     * @return \Codeacious\Filesystem\Directory The new copy
     * @throws \Codeacious\Filesystem\Exception
     */
    public function copyTo($newPath)
    {
        if (is_object($newPath))
        {
            if (! $newPath instanceof Directory)
                throw new Exception('Invalid argument type');
            $newPath = $newPath->getPath();
        }
        if (is_dir($newPath))
            $newPath .= DIRECTORY_SEPARATOR.$this->getName();
        
        if (!$this->exists())
        {
            throw new Exception('The item '.$this->path.' cannot be copied because it does not '
                .'exist');
        }
        
        $args = array('-R', $this->path, $newPath);
        $output = null;
        $error = null;
        if (! Shell::exec('cp', $args, $output, $error))
            throw new Exception('Unable to copy '.$this->path.' to '.$newPath.': '.$error);
        
        return new static($newPath);
    }
    
    /**
     * Move the directory to a new location, optionally changing its name.
     * 
     * @param string|\Codeacious\Filesystem\Directory $newPath The full path that the directory will
     *    now have, or the path to the directory that will contain it.
     * 
     * @return \Codeacious\Filesystem\Directory This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function moveTo($newPath)
    {
        $this->_moveTo($newPath);
        return $this;
    }
    
    /**
     * Change the name of the directory, leaving it in the same location.
     * 
     * @param string $newName
     * 
     * @return \Codeacious\Filesystem\Directory This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function renameTo($newName)
    {
        if (basename($newName) != $newName)
            throw new Exception('This method accepts a name only, not a path');
        
        return $this->moveTo($this->getParentPath().DIRECTORY_SEPARATOR.$newName);
    }
    
    /**
     * Remove this directory from the filesystem.
     * 
     * @param boolean $recursive If true, delete all items in the directory too. If false, the
     *    directory must be empty.
     * 
     * @return \Codeacious\Filesystem\Directory This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function delete($recursive=false)
    {
        if ($recursive)
        {
            foreach ($this as $item) /* @var $item FilesystemObject */
            {
                if ($item instanceof File)
                    $item->delete();
                elseif ($item instanceof Directory)
                    $item->delete(true);
            }
        }
        
        if (!@rmdir($this->path))
            throw new Exception('Unable to remove directory '.$this->path.$this->_lastWarning());
        
        return $this;
    }
    
    /**
     * Determine if this directory has a child item with the given name.
     * 
     * @param string $name
     * 
     * @return boolean
     */
    public function hasChild($name)
    {
        $itemPath = $this->path.DIRECTORY_SEPARATOR.$name;
        return file_exists($itemPath);
    }
    
    /**
     * Get a child item inside this directory, if it exists.
     * 
     * @param string $name
     * 
     * @return \Codeacious\Filesystem\FilesystemObject|null
     */
    public function getChild($name)
    {
        $itemPath = $this->path.DIRECTORY_SEPARATOR.$name;
        if (!file_exists($itemPath))
            return null;
        
        if (is_dir($itemPath))
            return new Directory($itemPath);
        
        return new File($itemPath);
    }
    
    /**
     * Get a file within this directory.
     * 
     * @param string $name
     * @param boolean $allowNonExisting Pass true to get a result even if the file does not exist
     * 
     * @return \Codeacious\Filesystem\File|null
     */
    public function getFile($name, $allowNonExisting=false)
    {
        if ($allowNonExisting)
            return new File($this->path.DIRECTORY_SEPARATOR.$name);
        
        $child = $this->getChild($name);
        if (! $child instanceof File)
            return null;
        
        return $child;
    }
    
    /**
     * Get a directory within this directory.
     * 
     * @param string $name
     * @param boolean $allowNonExisting Pass true to get a result even if the directory does not
     *    exist
     * 
     * @return \Codeacious\Filesystem\Directory|null
     */
    public function getSubdirectory($name, $allowNonExisting=false)
    {
        if ($allowNonExisting)
            return new Directory($this->path.DIRECTORY_SEPARATOR.$name);
        
        $child = $this->getChild($name);
        if (! $child instanceof Directory)
            return null;
        
        return $child;
    }
    
    /**
     * @see \Iterator
     * 
     * @return mixed
     */
    public function current()
    {
        if (! ($r = $this->_getResource()))
            return null;
        
        return $this->getChild($this->currentChild);
    }

    /**
     * @see \Iterator
     * 
     * @return mixed
     */
    public function key()
    {
        if (! ($r = $this->_getResource()))
            return null;
        
        if ($this->currentChild === false)
            return null;
        
        return $this->currentChild;
    }

    /**
     * @see \Iterator
     * 
     * @return void
     */
    public function next()
    {
        if (! ($r = $this->_getResource()))
            return;
        
        do
        {
            $this->currentChild = readdir($r);
        }
        while ($this->currentChild == '.' || $this->currentChild == '..');
    }

    /**
     * @see \Iterator
     * 
     * @return void
     */
    public function rewind()
    {
        if (! ($r = $this->_getResource()))
            return;
        
        rewinddir($r);
        $this->next();
    }

    /**
     * @see \Iterator
     * 
     * @return boolean
     */
    public function valid()
    {
        if (! ($r = $this->_getResource()))
            return false;
        
        if ($this->currentChild === false)
            return false;
        
        return true;
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
    
    /**
     * @return resource|null
     * @throws \Codeacious\Filesystem\Exception 
     */
    private function _getResource()
    {
        if (!$this->exists())
            return null;
        
        if (!$this->resource)
        {
            $resource = opendir($this->path);
            if ($resource === false)
                throw new Exception('Unable to open directory '.$this->path.$this->_lastWarning());
            
            $this->resource = $resource;
            $this->next();
        }
        
        return $this->resource;
    }
}
