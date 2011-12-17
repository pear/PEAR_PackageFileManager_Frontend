<?php
/**
 * Decorator for PEAR_PackageFileManager_Frontend
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   PEAR
 * @package    PEAR_PackageFileManager_Frontend
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @copyright  2005-2006 Laurent Laville
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    CVS: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager/Frontend.php';

/**
 * Decorates any PEAR_PackageFileManager_Frontend class
 *
 * @category   PEAR
 * @package    PEAR_PackageFileManager_Frontend
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @copyright  2005-2006 Laurent Laville
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 * @abstract
 */

class PEAR_PackageFileManager_Frontend_Decorator extends PEAR_PackageFileManager_Frontend
{
    /**
     * An instance PEAR_PackageFileManager_Frontend
     *
     * @var    object
     * @since  0.1.0
     */
    var $fe;

    /**
     * Decorator constructor
     *
     * @param  object   $frontend  instance of PEAR_PackageFileManager_Frontend
     * @since  0.1.0
     * @access public
     */
    function __construct($frontend)
    {
        $this->fe = $frontend;
    }

    /**
     * Decorator::getMaintList()
     *
     * @return array
     * @since  0.1.0
     * @access public
     * @see    PEAR_PackageFileManager_Frontend::getMaintList()
     */
    function getMaintList($users)
    {
        return $this->fe->getMaintList($users);
    }

    /**
     * Decorator::getFileList()
     *
     * @param  boolean  $default  if we get initial data set at first run
     * @param  boolean  $ignore   Either if you want all files or just ignored
     * @param  string   $plugin   PEAR_PackageFileManager filelist generator
     * @return array
     * @since  0.1.0
     * @access public
     * @see    PEAR_PackageFileManager_Frontend::getFileList()
     */
    function getFileList($default, $ignore, $plugin)
    {
        return $this->fe->getFileList($default, $ignore, $plugin);
    }

    /**
     * Decorator::setFileList()
     *
     * @param  string   $plugin   PEAR_PackageFileManager filelist generator
     * @return void
     * @since  0.1.0
     * @access public
     * @see    PEAR_PackageFileManager_Frontend::setFileList()
     */
    function setFileList($plugin)
    {
        $this->fe->setFileList($plugin);
    }

    /**
     * Decorator::getDepList()
     *
     * @return array
     * @since  0.1.0
     * @access public
     * @see    PEAR_PackageFileManager_Frontend::getDepList()
     */
    function getDepList()
    {
        return $this->fe->getDepList();
    }

    /**
     * Decorator::getExceptionList()
     *
     * @return array
     * @since  0.1.0
     * @access public
     * @see    PEAR_PackageFileManager_Frontend::getExceptionList()
     */
    function getExceptionList()
    {
        return $this->fe->getExceptionList();
    }
}
?>
