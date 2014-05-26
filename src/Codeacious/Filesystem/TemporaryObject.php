<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: TemporaryObject.php 861 2014-05-09 11:10:38Z glenn $
 */

namespace Codeacious\Filesystem;

/**
 * Interface for temporary files and directories.
 */
interface TemporaryObject
{
    /**
     * Check whether the item will be deleted at the end of the current PHP invocation.
     * 
     * @return boolean 
     */
    public function getDeleteOnExit();
    
    /**
     * Set whether the item should be deleted at the end of the current PHP invocation.
     * 
     * Note that setting this to false does not guarantee the item will be kept permanently. If it
     * resides in a system temporary directory, it may be cleaned up by the OS eventually.
     * 
     * @param boolean $bool
     * 
     * @return object This 
     */
    public function setDeleteOnExit($bool);
}
