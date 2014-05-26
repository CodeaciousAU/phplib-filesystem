<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: File.php 861 2014-05-09 11:10:38Z glenn $
 */

namespace Codeacious\Filesystem;

/**
 * Represents a file on the filesystem.
 * 
 * <ul>
 *    <li>The file does not necessarily exist.</li>
 *    <li>This object can be assigned additional metadata, such as a media type. This metadata
 *          exists only within the object and is not stored on the filesystem.</li>
 *    <li>In this context, a directory is not considered to be a file.</li>
 * </ul>
 */
class File extends FilesystemObject
{
    /**
     * @var string 
     */
    protected $mediaType;
    
    /**
     * @var string 
     */
    protected $metaFilename;
    
    
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        if (is_file($path))
            $path = realpath($path);
        parent::__construct($path);
    }
    
    /**
     * @return boolean 
     */
    public function exists()
    {
        return is_file($this->path);
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
     * Get the size of the file.
     * 
     * @return integer|null File size in bytes or null if the file doesn't exist
     * @throws \Codeacious\Filesystem\Exception 
     */
    public function getSize()
    {
        if (!$this->exists())
            return null;
        
        $size = @filesize($this->path);
        if ($size === false)
        {
            throw new Exception('Unable to determine size of file '.$this->path.
                $this->_lastWarning());
        }
        
        return $size;
    }
    
    /**
     * Move the file to a new location, optionally changing its name.
     * 
     * @param string|\Codeacious\Filesystem\Directory $newPath The full path that the file will now
     *    have, or the path to the directory that will contain the file.
     * 
     * @return \Codeacious\Filesystem\File This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function moveTo($newPath)
    {
        $this->_moveTo($newPath);
        return $this;
    }
    
    /**
     * Copy the file to a new location, optionally changing its name.
     * 
     * @param string|\Codeacious\Filesystem\Directory $newPath The full path that the new file will
     *    have, or the path to the directory that will contain the file.
     * 
     * @return \Codeacious\Filesystem\File The new file
     * @throws \Codeacious\Filesystem\Exception
     */
    public function copyTo($newPath)
    {
        $newItem = $this->_copyTo($newPath);
        return new File($newItem);
    }
    
    /**
     * Change the name of the file, leaving it in the same directory.
     * 
     * @param string $newName
     * 
     * @return \Codeacious\Filesystem\File This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function renameTo($newName)
    {
        if (basename($newName) != $newName)
            throw new Exception('This method accepts a file name only, not a path');
        
        return $this->moveTo($this->getParentPath().DIRECTORY_SEPARATOR.$newName);
    }
    
    /**
     * Remove this file from the filesystem.
     * 
     * @return \Codeacious\Filesystem\File This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function delete()
    {
        $this->_delete();
        return $this;
    }
    
    /**
     * @return string 
     * @throws \Codeacious\Filesystem\Exception
     */
    public function getContents()
    {
        if (!$this->exists())
            return null;
        
        if (($s = @file_get_contents($this->path)) === false)
            throw new Exception('Unable to read file '.$this->path.$this->_lastWarning());
        
        return $s;
    }
    
    /**
     * @param string $contents
     * 
     * @return \Codeacious\Filesystem\File This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function setContents($contents)
    {
        if (@file_put_contents($this->path, $contents) === false)
            throw new Exception('Unable to write to file '.$this->path.$this->_lastWarning());
        
        return $this;
    }
    
    /**
     * Output the contents of this file.
     * 
     * @return \Codeacious\Filesystem\File This
     * @throws \Codeacious\Filesystem\Exception
     */
    public function output()
    {
        if (!$this->exists())
            return $this;
        
        if (@readfile($this->path) === false)
            throw new Exception('Unable to output file '.$this->path.$this->_lastWarning());
        
        return $this;
    }
    
    /**
     * Get the Internet media type (MIME type) associated with this object (if one has been set).
     * 
     * @return string
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * Associate an Internet media type (MIME type) with this object.
     * 
     * This does not affect the file on filesystem.
     * 
     * @param string $value
     * @return \Codeacious\Filesystem\File This
     */
    public function setMediaType($value)
    {
        $this->mediaType = $value;
        return $this;
    }
    
    /**
     * Get the 'virtual' filename associated with this object (if one has been set).
     * 
     * @return string
     */
    public function getMetaFilename()
    {
        return $this->metaFilename;
    }

    /**
     * Associate a 'virtual' filename with this object.
     * 
     * This does not affect the file on filesystem.
     * 
     * @param string $value
     * @return \Codeacious\Filesystem\File This
     */
    public function setMetaFilename($value)
    {
        $this->metaFilename = $value;
        return $this;
    }
    
    
    /**
     * @return resource
     * @throws \Codeacious\Filesystem\Exception 
     */
    protected function _open()
    {
        if (! ($f = @fopen($this->path)))
            throw new Exception('Unable to open file '.$this->path.$this->_lastWarning());
        
        return $f;
    }
    
    /**
     * @param resource $f
     * @return void
     */
    protected function _close($f)
    {
        fclose($f);
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
