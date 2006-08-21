<?php
/**
 * PEAR_PackageFileManager_Frontend, the singleton-based frontend for user input/output.
 *
 * PHP versions 4 and 5
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

require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR/Config.php';
require_once 'Config.php';

/**#@+
 * Error Codes
 *
 * @since  0.1.0
 * @access public
 */
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_NODRIVER',        -1);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_LOGGER',    -2);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTOPEN_CONFIG', -3);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTCOPY_CONFIG', -4);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_EMPTY_CONFIG',    -5);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_CONFIG',    -6);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_NOOPTION',        -7);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USERS',     -8);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_NOUSER',          -9);
define('PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USEROLE',  -10);
/**#@-*/
/**
 * Error messages
 *
 * @global array $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS']
 * @access private
 * @since  0.1.0
 */
$GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'] =
    array(
        PEAR_PACKAGEFILEMANAGER_FRONTEND_NODRIVER =>
            'No such driver "%driver%"',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_LOGGER =>
            'Logger must be compatible with PEAR::Log',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTOPEN_CONFIG =>
            'Loading preferences error: %error%',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTCOPY_CONFIG =>
            'Saving preferences error: %error%',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_EMPTY_CONFIG =>
            'Your preference data source is empty. Uses all default values instead',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_CONFIG =>
            'Your preference data source is invalid. Uses all default values instead',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_NOOPTION =>
            'Unknown option "%option%"',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USERS =>
            'No such list of users "%users%"',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_NOUSER =>
            'User "%handle%" does not exists',
        PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USEROLE =>
            'Invalid user role "%role%"; must be one of "%valid%"',
    );

/**
 * Singleton-based frontend for user input/output.
 *
 * This package is designed to be a backend to different front ends
 * written for the PEAR_PackageFileManager2 class.
 * For example, this package can be used to drive a PHP-GTK2 or web front end.
 *
 * @category   PEAR
 * @package    PEAR_PackageFileManager_Frontend
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @copyright  2005-2006 Laurent Laville
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */

class PEAR_PackageFileManager_Frontend
{
   /**
    * Name of the frontend (driver), used to store the values in session
    *
    * @var     string
    * @since   0.1.0
    * @access public
    */
    var $driver;

    /**
     * @var    string
     * @since  0.1.0
     * @access public
     */
    var $packagedirectory;

    /**
     * @var    string
     * @since  0.1.0
     * @access public
     */
    var $pathtopackagefile;

    /**
     * Frontend options set in the configuration file.
     *
     * @var    array
     * @since  0.3.0
     * @access private
     */
    var $_options = array(
        'outputdirectory'    => false,
        'roles'              => array(),
        'dir_roles'          => array(),
        'changelogoldtonew'  => false,
        'simpleoutput'       => false,
        'exportcompatiblev1' => false,
        'baseinstalldir'     => '',
        'package_type'       => array(),
        'stability'          => array(),
        'maintainer_roles'   => array(),
        'plugingenerator'    => array(),
        'filelistgenerator'  => ''
        );

    /**
     * Instance of a log handler (class) that support a log() method
     *
     * @var    object
     * @since  0.1.0
     * @access private
     */
    var $_logger;


    /**
     * ZE1 class constructor
     *
     * @param  string  $driver              Name of the frontend (Web, Gtk2)
     * @param  mixed   $packagedirectory    Path to the base directory of the package
     * @param  mixed   $pathtopackagefile   Path to an existing package file to read in
     * @access public
     * @since  0.1.0
     */
    function PEAR_PackageFileManager_Frontend($driver, $packagedirectory, $pathtopackagefile)
    {
        $this->packagedirectory  = $packagedirectory;
        $this->pathtopackagefile = $pathtopackagefile;
        $this->driver  = $driver;
        $this->_logger = false;
    }

    /**
     * Creates a unique instance of the given front end class.
     *
     * @param  string  $driver              Name of the frontend (Web, Gtk2)
     * @param  mixed   $packagedirectory    Path to the base directory of the package
     * @param  mixed   $pathtopackagefile   Path to an existing package file to read in
     * @param  mixed   $logger              PEAR::Log or compatible instance
     * @return PEAR_PackageFileManager_Frontend_{$driver}
     * @access public
     * @since  0.1.0
     * @static
     */
    function &singleton($driver = '', $packagedirectory = false, $pathtopackagefile = false,
                        $logger = false)
    {
        static $instance;

        if (!isset($instance)) {
            if ($logger) {
                $s =& PEAR_ErrorStack::singleton('@package_name@');
                $s->setLogger($logger);
            }

            $uiclass = "PEAR_PackageFileManager_Frontend_$driver";

            if (!class_exists($uiclass)) {
                $file = str_replace('_', '/', $uiclass) . '.php';
                if (PEAR_PackageFileManager2::isIncludeable($file)) {
                    include_once $file;
                }
            }
            if (!class_exists($uiclass)) {
                $err =& PEAR_ErrorStack::staticPush('@package_name@',
                    PEAR_PACKAGEFILEMANAGER_FRONTEND_NODRIVER, 'error',
                    array('driver' => $driver),
                    $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_NODRIVER]
                    );
                return $err;
            }
            $fe =& new $uiclass($driver, $packagedirectory, $pathtopackagefile);
            if ($logger) {
                $fe->setLogger($logger);
            }
            $instance = $fe;
        }
        return $instance;
    }

    /**
     * Set up a PEAR::Log object or compatible for this frontend
     *
     * @param  mixed   $logger              PEAR::Log or compatible instance
     * @return TRUE on success, FALSE on error
     * @access public
     * @since  0.1.0
     */
    function setLogger(&$logger)
    {
        if (isset($logger) && (!is_object($logger) || !method_exists($logger, 'log'))) {
            PEAR_ErrorStack::staticPush('@package_name@',
                PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_LOGGER, 'warning',
                array(),
                $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_LOGGER]
                );
            return false;
        }
        $s =& PEAR_ErrorStack::singleton('@package_name@');
        $s->setLogger($logger);
        $this->_logger = &$logger;
        return true;
    }

    /**
     * Load the user preferences for this frontend
     *
     * @param  mixed    $config  a PEAR::Config container or an identification array
     * @return TRUE on success, FALSE on error
     * @access public
     * @since  0.1.0
     */
    function loadPreferences(&$config)
    {
        // user options configuration (temporary)
        $conf = new Config();

        // source is Array
        if (is_array($config)) {
            list($source, $type, $options) = $config;
            if (is_string($source)) {
                $this->log('debug', "Load preferences from file \"$source\" ($type)");
            } else {
                $this->log('debug', "Load preferences from data stream ($type)");
            }
            if (!isset($options)) {
                // in case no parser options are given
                $options = array();
            }
            $res =& $conf->parseConfig($source, $type, $options);
            $fail = PEAR::isError($res);

        } elseif (is_bool($config) && $config === false) {
            $fail = false;
            $custom = array();

        } else {
            $fail = true;
        }

        if ($fail) {
            PEAR_ErrorStack::staticPush('@package_name@',
                PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTOPEN_CONFIG, 'error',
                array('error' => $res->getMessage() ),
                $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTOPEN_CONFIG]
                );
            return false;
        }

        if  (!isset($custom)) {
            $root =& $conf->getRoot();
            $options = $root->toArray(false);
            $custom = $options['root']['settings'];
        }

        // default options
        $pfm = new PEAR_PackageFileManager2();
        $pfmOptions = $pfm->getOptions();
        $default = array(
            'outputdirectory'    => $pfmOptions['outputdirectory'],
            'roles'              => $pfmOptions['roles'],
            'dir_roles'          => $pfmOptions['dir_roles'],
            'changelogoldtonew'  => $pfmOptions['changelogoldtonew'],
            'simpleoutput'       => $pfmOptions['simpleoutput'],
            'exportcompatiblev1' => false,
            'baseinstalldir'     => '/',
            'package_type'       => array('php','extsrc','extbin'),
            'stability'          => array('snapshot','devel','alpha','beta','stable'),
            'maintainer_roles'   => array('lead','developer','contributor','helper'),
            'plugingenerator'    => array('File', 'Cvs', 'Svn', 'Perforce'),
            'filelistgenerator'  => $pfmOptions['filelistgenerator']
        );

        if (count($custom)) {
            // hack for PEAR::Config that cannot parse correctly boolean for XML container
            foreach ($this->_options as $kdef => $vdef) {
                if (is_bool($this->_options[$kdef])) {
                    if (empty($custom[$kdef])) {
                        $custom[$kdef] = false;
                    } else {
                        $a = $custom[$kdef];
                        $a = eregi_replace('^false$', '0', trim($a));
                        $a = eregi_replace('^true$' , '1', trim($a));
                        $custom[$kdef] = (bool) $a;
                    }
                }
            } //
            $this->_options = $this->array_merge_recursive($default, $custom);
        } else {
            $this->_options = $default;
        }
        return true;
    }

    /**
     * Save the user preferences for this frontend
     *
     * @param  mixed   $target     Datasource to write to
     * @param  string  $type       Type of configuration
     * @param  array   $options    Options for config container
     * @return TRUE on success, FALSE on error
     * @access public
     * @since  0.1.0
     */
    function savePreferences($target, $type, $options = null)
    {
        if (strcasecmp($type, 'xml') == 0) {
            if (!is_array($options)) {
                settype($options, 'array');
            }
            $name = array('name' => '');
            $options = array_merge($options, $name);
        }
        $conf = new Config();
        $conf->parseConfig($this->_options, 'phpArray');
        $out = $conf->writeConfig($target, $type, $options);
        if (PEAR::isError($out)) {
            PEAR_ErrorStack::staticPush('@package_name@',
                PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTCOPY_CONFIG, 'error',
                array('error' => $out->getMessage() ),
                $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_CANTCOPY_CONFIG]
                );
            return false;
        }
        return true;
    }

    /**
     * Get all user preferences for this frontend
     *
     * @param  array   $options    Options for config container
     * @return TRUE on success, FALSE on error
     * @access public
     * @since  0.3.0
     */
    function getPreferences()
    {
        return $this->_options;
    }

    /**
     * Returns the value of an option from the configuration array.
     *
     * @param  string  option name
     * @return mixed   the option value or false on failure
     * @since  0.1.0
     * @access public
     */
    function getOption($option)
    {
        if (array_key_exists($option, $this->_options)) {
            return $this->_options[$option];
        }

        PEAR_ErrorStack::staticPush('@package_name@',
            PEAR_PACKAGEFILEMANAGER_FRONTEND_NOOPTION, 'exception',
            array('option' => $option),
            $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_NOOPTION]
            );
        return false;
    }

    /**
     * Sets an option after the configuration array has been loaded.
     *
     * @param  string   option name
     * @param  mixed    value for the option
     * @return bool  true on success or false on failure
     * @since  0.1.0
     * @access public
     */
    function setOption($option, $value)
    {
        if (array_key_exists($option, $this->_options)) {
            $this->_options[$option] = $value;
            return true;
        }

        PEAR_ErrorStack::staticPush('@package_name@',
            PEAR_PACKAGEFILEMANAGER_FRONTEND_NOOPTION, 'exception',
            array('option' => $option),
            $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_NOOPTION]
            );
        return false;
    }

    /**
     * Returns whether or not errors have occurred (and been captured).
     *
     * @param  string   $level    Level name to check for a particular severity
     * @return boolean
     * @access public
     * @since  0.1.0
     */
    function hasErrors($level = false)
    {
        return PEAR_ErrorStack::staticHasErrors('@package_name@', $level);
    }

    /**
     * Get a list of all errors since last purge
     *
     * @param  bool     $purge   set in order to empty the error stack
     * @return array
     * @access public
     * @since  0.1.0
     */
    function getErrors($purge = false)
    {
        return PEAR_ErrorStack::staticGetErrors($purge);
    }

    /**
     * Repackage PEAR error
     *
     * @param  object  $error  PEAR_Error object to repackage
     * @return void
     * @access public
     * @since  0.1.0
     * @author Ian Eure <ieure@php.net>  from StackThunk 0.9.0
     */
    function repackagePEAR_Error(&$error)
    {
        static $map;
        if (!isset($map)) {
            $map = array(
                E_ERROR            => 'error',
                E_WARNING          => 'warning',
                E_PARSE            => 'exception',
                E_NOTICE           => 'notice',
                E_CORE_ERROR       => 'error',
                E_CORE_WARNING     => 'warning',
                E_COMPILE_ERROR    => 'exception',
                E_COMPILE_WARNING  => 'warning',
                E_USER_ERROR       => 'error',
                E_USER_WARNING     => 'warning',
                E_USER_NOTICE      => 'notice'
            );
        }

        // Strip this function from the trace
        if (is_array($error->backtrace)) {
            array_shift($error->backtrace);
            $error->userinfo['backtrace'] =& $error->backtrace;
        }
        PEAR_ErrorStack::staticPush('@package_name@',
            $error->code, $map[$error->level],
            $error->userinfo, $error->message, false, $error->backtrace
            );
    }

    /**
     * Log an error using PEAR::Log or compatible backend
     *
     * @param  string   $level    Error level
     * @param  string   $message  Error message
     * @return void
     * @since  0.1.0
     * @access public
     */
    function log($level, $message)
    {
        if ($this->_logger) {
            if (method_exists($this->_logger, 'stringToPriority')) {
                $priority = $this->_logger->stringToPriority($level);
            } else {
                $priority = null;
            }
            $this->_logger->log($message, $priority);
        }
    }

    /**
     * Returns a reference to a session variable containing the form-page
     * values and pages' validation status.
     *
     * @param  bool      If true, then reset the container: clear all default, constant and submitted values
     * @return array
     * @access public
     * @since  0.1.0
     */
    function &container($reset = false)
    {
        $name = '_' . $this->driver . '_container';
        if (!isset($_SESSION[$name]) || $reset) {
            $_SESSION[$name] = array(
                'defaults'  => array(),
                'constants' => array(),
                'values'    => array(),
                'valid'     => array()
            );
        }
        return $_SESSION[$name];
    }

    /**
     * Returns list of package maintainers
     *
     * @param  string  $users  maintainer category
     * @return mixed   false on error or if maintainers does not exists, array otherwise
     * @since  0.1.0
     * @access public
     */
    function getMaintList($users = null)
    {
        $maint = array('lead','developer','contributor','helper');
        if (isset($users) && !in_array($users, $maint)) {
            PEAR_ErrorStack::staticPush('@package_name@',
                PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USERS, 'error',
                array('users' => $users),
                $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USERS]
                );
            return false;
        }

        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }

        if ($users == 'lead') {
            $maintainers = $sess['pfm']->getLeads();
        } elseif ($users == 'developer') {
            $maintainers = $sess['pfm']->getDevelopers();
        } elseif ($users == 'contributor') {
            $maintainers = $sess['pfm']->getContributors();
        } elseif ($users == 'helper') {
            $maintainers = $sess['pfm']->getHelpers();
        } else {
            $maintainers = $sess['pfm']->getMaintainers();
        }
        return $maintainers;
    }

    /**
     * Wrapper for method PEAR_PackageFile_v2_rw::deleteMaintainer()
     *
     * @param  string  $handle  handle of user to remove from package
     * @return TRUE on success, FALSE on error
     * @since  0.2.0
     * @access public
     * @see    PEAR_PackageFile_v2_rw::deleteMaintainer()
     */
    function addMaintainer($role, $handle, $name, $email, $active = 'yes')
    {
        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }
        $ok = $sess['pfm']->addMaintainer($role, $handle, $name, $email, $active);
        if (!$ok) {
            PEAR_ErrorStack::staticPush('@package_name@',
                PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USEROLE, 'error',
                array('role' => $role, 'valid' => 'lead, developer, contributor, helper'),
                $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_WRONG_USEROLE]
                );
            return false;
        }
        return true;
    }

    /**
     * Wrapper for method PEAR_PackageFile_v2_rw::deleteMaintainer()
     *
     * @param  string  $handle  handle of user to remove from package
     * @return TRUE on success, FALSE on error
     * @since  0.2.0
     * @access public
     * @see    PEAR_PackageFile_v2_rw::deleteMaintainer()
     */
    function deleteMaintainer($handle)
    {
        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }
        $found = $sess['pfm']->deleteMaintainer($handle);
        if (!$found) {
            PEAR_ErrorStack::staticPush('@package_name@',
                PEAR_PACKAGEFILEMANAGER_FRONTEND_NOUSER, 'error',
                array('handle' => $handle),
                $GLOBALS['_PEAR_PACKAGEFILEMANAGER_FRONTEND_ERRORS'][PEAR_PACKAGEFILEMANAGER_FRONTEND_NOUSER]
                );
        }
        return $found;
    }

    /**
     * Prepare file list, with role, replacements and platform exception info.
     *
     * @param  boolean  $default  if we get initial data set at first run
     * @param  boolean  $ignore   Either if you want all files or just ignored
     * @param  string   $plugin   PEAR_PackageFileManager filelist generator
     * @return array
     * @since  0.1.0
     * @access public
     */
    function getFileList($default = false, $ignore = false, $plugin = 'file')
    {
        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }
        if (!isset($sess['files'])) {
            $this->setFileList($plugin);
        }
        if ($default && isset($sess['defaults']['_files'])) {
            $sess['files'] = $sess['defaults']['_files'];
        }

        $list = array();

        foreach ($sess['files'] as $k => $file) {
            if (!is_int($k)) {
                // global file mapping is not scanned
                continue;
            }
            if ($ignore === true && $file['ignore'] !== true) {
                continue;
            } elseif ($ignore === false && $file['ignore'] === true) {
                continue;
            }
            // preserve original key ($k) to allow edit file
            $list[$k] = $file;
        }

        // returns files mapping with relative path to package directory
        $pkg = $sess['pfm'];
        $options = $pkg->getOptions();
        $filelist = array();
        foreach($list as $k => $file) {
            $path = str_replace($options['packagedirectory'], '', $sess['files']['mapping'][$k]);
            if (substr($path, 0, 1) == '/') {
                $path = substr($path, 1);
            }
            $filelist[$k] = $path;
        }
        $list['mapping'] = $filelist;

        $msg  = (count($list) - 1) .'/'. count($sess['files']) . ' ';
        $msg .= ($ignore === true) ? 'ignored' : 'selected';
        $msg .= ' file(s)';
        $this->log('debug', str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') . $msg);

        return $list;
    }

    /**
     * Defines a listing of every file in package directory and
     * all subdirectories.
     *
     * @param  string  $plugin   PEAR_PackageFileManager filelist generator
     * @return void
     * @since  0.1.0
     * @access public
     */
    function setFileList($plugin)
    {
        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }
        $sess['files'] = array();
        if (!isset($plugin)) {
            $plugin = 'file';
        }

        $plugin = ucfirst(strtolower($plugin));
        $pkg = $sess['pfm'];
        $options = array_merge($pkg->getOptions(), array('filelistgenerator' => $plugin));
        $pkg->setOptions($options, true);

        $generatorclass = 'PEAR_PackageFileManager_'. $plugin;
        $this->log('debug', __FUNCTION__ .'('. __LINE__ .') generator plugin='.$generatorclass);

        if (!class_exists($generatorclass)) {
            $classSource = str_replace('_', '/', $generatorclass) . '.php';
            if (PEAR_PackageFileManager2::isIncludeable($classSource)) {
                include_once $classSource;
            }
        }

        // get dir list corresponding to generator plugin
        $gen = new $generatorclass($pkg, $options);
        $fsMap = $gen->dirList(substr($options['packagedirectory'], 0, strlen($options['packagedirectory']) - 1));
        $limit = count($fsMap);
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            ' ' . $limit . ' file(s) into tree "'. $options['packagedirectory']. '"'
        );

        for ($i = 0; $i < $limit; $i++) {
            $sess['files'][] = array('ignore' => false,
                'role' => '', 'platform' => false, 'eol' => false, 'replacements' => array(),
                'installas' => ''
            );
        }
        $sess['files']['mapping'] = $fsMap;
    }

    /**
     * Returns list of package dependencies (other packages and/or extensions)
     *
     * @param  boolean $default  if we get initial data set at first run
     * @return array
     * @since  0.1.0
     * @access public
     */
    function getDepList($default = false)
    {
        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }
        if (!isset($sess['dependencies'])) {
            $this->_getDependencies();
        }
        if ($default && isset($sess['defaults']['_dependencies'])) {
            $sess['dependencies'] = $sess['defaults']['_dependencies'];
        }
        return $sess['dependencies'];
    }

    /**
     * Returns list of directories and file extensions roles
     *
     * @param  boolean $default  if we get initial data set at first run
     * @return array
     * @since  0.1.0
     * @access public
     */
    function getRoleList($default = false)
    {
        $sess =& $this->container();

        if (!isset($sess['roles'])) {
            $filelist = $this->getFileList();
            $sess['roles'] = array();
            $settings_dir_roles = $this->getOption('dir_roles');
            $settings_roles = $this->getOption('roles');
            $ext_roles = array('*' => $settings_roles['*']);
            $dir_roles = array();
            $exts = array();
            $dirs = array('.');

            foreach ($filelist['mapping'] as $k => $file) {
                $parts = pathinfo($file);

                if (!in_array($parts['dirname'], $dirs)) {
                    if (array_key_exists($parts['dirname'], $settings_dir_roles)) {
                        $dir_roles[$parts['dirname']] = $settings_dir_roles[$parts['dirname']];
                    } else {
                        $dir_roles[$parts['dirname']] = 'php';
                    }
                    $dirs[] = $parts['dirname'];
                }
                if (isset($parts['extension']) && !in_array($parts['extension'], $exts)) {
                    if (array_key_exists($parts['extension'], $settings_roles)) {
                        $ext_roles[$parts['extension']] = $settings_roles[$parts['extension']];
                    } else {
                        $ext_roles[$parts['extension']] = $ext_roles['*'];
                    }
                    $exts[] = $parts['extension'];
                }
            }

            foreach ($ext_roles as $ext => $role) {
                if ($ext !== '*') {
                    $sess['roles'][] = array('directory' => '', 'extension' => $ext, 'role' => $role);
                }
            }
            foreach ($dir_roles as $dir => $role) {
                $sess['roles'][] = array('directory' => $dir, 'extension' => '', 'role' => $role);
            }
        }
        // sort role list by extension (in ascending order), directories are always first
        $roles = array();
        foreach ($sess['roles'] as $key => $row) {
            $roles[$key] = $row['extension'];
        }
        array_multisort($roles, SORT_ASC, $sess['roles']);

        if ($default && isset($sess['defaults']['_roles'])) {
            $sess['roles'] = $sess['defaults']['_roles'];
        }

        return $sess['roles'];
    }

    /**
     * Returns list of file role for specific files
     *
     * @return array
     * @since  0.1.0
     * @access public
     */
    function getExceptionList()
    {
        $sess =& $this->container();
        $filelist = $this->getFileList();
        return $filelist;
    }

    /**
     * Returns default values of a package (imported or pre-set)
     *
     * @param  string  $type   category of data
     * @return mixed
     * @since  0.1.0
     * @access public
     */
    function getDefaults($type)
    {
        switch ($type) {
            case 'package':
            case 'page1':
                $def = $this->_getPackage();
                break;
            case 'release':
            case 'page2':
                $def = $this->_getRelease();
                break;
            case 'maintainers':
            case 'page3':
                $def = $this->_getMaintainers();
                break;
            case 'dependencies':
            case 'page4':
                $def = $this->_getDependencies();
                break;
            case 'replacements':
            case 'page5':
                $def = $this->_getReplacements();
                break;
            default:
                $def = null;
        }
        return $def;
    }

    /**
     * Sets default values used when we click on 'Reset' button of the frontend
     *
     * @param  string   $type          category of data
     * @param  boolean  $overwrite     allow to define a new copy of default values
     * @return void
     * @since  0.2.0
     * @access public
     */
    function setDefaults($type, $values = null, $overwrite = false)
    {
        $sess =& $this->container();

        if (isset($sess['defaults']["_$type"]) && !$overwrite) {
            return;
        }
        switch ($type) {
            case 'maintainers':
            case 'dependencies':
            case 'files':
                if (!isset($values)) {
                    $values = $sess[$type];
                }
                $sess['defaults']["_$type"] = $values;
                break;
            default:
                break;
        }
    }

    /**
     * Returns package summary informations:
     * <pre>
     *  - pearInstaller      : 'min', 'max', 'recommanded' and 'exclude' versions of PEAR installer
     *  - phpVersion         : 'min', 'max' and 'exclude' versions of PHP dependency
     *  - packageFileName    : the name of the packagefile, defaults to package.xml
     *  - baseInstallDir     : the base directory to install this package in
     *  - channel            :
     *  - packageDir         : the path to the base directory of the package
     *  - packageType        :
     *  - packageName        :
     *  - packageSummary     :
     *  - packageDescription :
     *  - packageOutputDir   : the path in which to place the generated package.xml
     * </pre>
     *
     * @return array
     * @since  0.1.0
     * @access private
     */
    function _getPackage()
    {
        $packagedirectory  = $this->realpathnix($this->packagedirectory);
        $pathtopackagefile = $this->realpathnix($this->pathtopackagefile);
        $baseinstalldir = $this->getOption('baseinstalldir');

        if ($packagedirectory === false) {
            $optionsUpdate = array(
                'pathtopackagefile' => $pathtopackagefile ? dirname($pathtopackagefile) : false,
                'packagedirectory'  => $this->realpathnix('.'),
                'baseinstalldir'    => $baseinstalldir
            );
            if (is_file($pathtopackagefile)) {
                $optionsUpdate['packagefile'] = basename($pathtopackagefile);
            }
            $newPackage = true;
        } else {
            $optionsUpdate = array(
                'pathtopackagefile' => $pathtopackagefile ? dirname($pathtopackagefile) : false,
                'packagedirectory'  => $packagedirectory,
                'baseinstalldir'    => $baseinstalldir,
                'clearcontents'     => false
            );
            if (is_file($packagedirectory)) {
                $optionsUpdate['packagefile'] = basename($packagedirectory);
                $optionsUpdate['packagedirectory'] = dirname($packagedirectory);
            }
            if (is_file($pathtopackagefile)) {
                $packagefile = $pathtopackagefile;
            } else {
                $packagefile  = ($pathtopackagefile) ? $pathtopackagefile : $packagedirectory;
                if (substr($packagefile, -1, 1) == '/') {
                    $packagefile .= 'package.xml';
                }
            }
            $pkg = &PEAR_PackageFileManager2::importFromPackageFile1($packagefile, $optionsUpdate);
            if (PEAR::isError($pkg)) {
                $this->repackagePEAR_Error($pkg);
                $newPackage = true;
            } else {
                $newPackage = false;
            }
        }

        if ($newPackage) {
            $pkg = new PEAR_PackageFileManager2();
            $pkg->setOptions($optionsUpdate);
            $pkg->setPackageType('php');
            $pkg->setChannel('pear.php.net');
            $pkg->setPearinstallerDep('1.4.3');
            $source = ' create';
            $packagefile = $optionsUpdate['packagedirectory'] . 'package.xml';
        } else {
            $source = ' import';
        }
        $pkg->setPackageFile($packagefile);
        $source .= " package file '$packagefile'";
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            $source);

        $sess =& $this->container();
        $sess['pfm'] =& $pkg;
        $sess['_newpackage'] = $newPackage;
        $options = $pkg->getOptions();
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            ' PFM options='. serialize($options)
        );

        if ($sess['_newpackage']) {
            $def = array(
                'pearInstaller'   => array('min' => '1.4.3', 'max' => false, 'recommanded' => false, 'exclude' => false),
                'phpVersion'      => array('min' => '4.2.0', 'max' => false, 'exclude' => false),
                'packageFileName' => $options['packagefile'],
                'baseInstallDir'  => $options['baseinstalldir']
            );
        } else {
            $deps = $pkg->getDeps(true);
            if (is_array($deps['required']['php']['exclude'])) {
                $deps['required']['php']['exclude'] = $deps['required']['php']['exclude'][0];
            }
            if (is_array($deps['required']['pearinstaller']['exclude'])) {
                $deps['required']['pearinstaller']['exclude'] = $deps['required']['pearinstaller']['exclude'][0];
            }
            $def = array(
                'phpVersion'      => $deps['required']['php'],
                'pearInstaller'   => $deps['required']['pearinstaller'],
                'packageFileName' => $options['packagefile'],
            );

        }
        $def = array_merge($def, array(
            'channel'            => $pkg->getChannel(),
            'packageDir'         => $options['packagedirectory'],
            'packageType'        => $pkg->getPackageType(),
            'packageName'        => $pkg->getPackage(),
            'packageSummary'     => $pkg->getSummary(),
            'packageDescription' => $pkg->getDescription(),
            'packageOutputDir'   => $this->getOption('outputdirectory'),
            'baseInstallDir'     => $baseinstalldir
            ));
        return $def;
    }

    /**
     * Returns release notes informations:
     * <pre>
     *  - releaseState    :
     *  - releaseVersion  :
     *  - APIState        :
     *  - APIVersion      :
     *  - releaseDate     :
     *  - releaseLicense  :
     *  - releaseNotes    :
     * </pre>
     *
     * @return array
     * @since  0.1.0
     * @access private
     */
    function _getRelease()
    {
        $sess =& $this->container();
        $options = $sess['pfm']->getOptions();

        if ($sess['_newpackage']) {
            if (is_array($options['license'])) {
                $license = $options['license'];
            } else {
                $license = array('content' => $options['license']);
            }
            $def = array(
                'releaseDate'    => time(),
                'releaseLicense' => $license
            );
        } else {
            $uri = $sess['pfm']->getLicenseLocation();
            if ($uri) {
                $uri = array_shift($uri);
            }
            $def = array(
                'releaseState'   => $sess['pfm']->getState(),
                'releaseVersion' => $sess['pfm']->getVersion(),
                'APIState'       => $sess['pfm']->getState('api'),
                'APIVersion'     => $sess['pfm']->getVersion('api'),
                'releaseDate'    => $sess['pfm']->getDate(),
                'releaseLicense' => array('content' => $sess['pfm']->getLicense(),
                                          'uri'     => $uri),
                'releaseNotes'   => $sess['pfm']->getNotes()
            );
        }
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            serialize($def)
        );
        return $def;
    }

    /**
     * Returns release informations:
     * <pre>
     *  - :
     * </pre>
     *
     * @return bool  TRUE if array of maintainer, FALSE otherwise
     * @since  0.1.0
     * @access private
     */
    function _getMaintainers()
    {
        $sess =& $this->container();
        $def = $sess['pfm']->getMaintainers();
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            serialize($def)
        );
        return (is_array($def));
    }

    /**
     * Returns dependencies list, packages installed and php extensions available:
     * <pre>
     *  - :
     * </pre>
     *
     * @return bool
     * @since  0.1.0
     * @access private
     */
    function _getDependencies()
    {
        $sess =& $this->container();
        if (!isset($sess['pfm'])) {
            $this->_getPackage();
        }

        $pearConfig = new PEAR_Config();
        $pear_install_dir = $pearConfig->get('php_dir');
        $pearRegistry = new PEAR_Registry($pear_install_dir);

        // PEAR packages installed from each channel
        $allpackages = $pearRegistry->_listAllPackages();
        foreach($allpackages as $channel => $packages) {
            if ($packages) {
                sort($packages, SORT_ASC);
                foreach ($packages as $package) {
                    $info = &$pearRegistry->getPackage($package, $channel);
                    if (is_object($info)) {
                        $name = $info->getPackage();
                    } else {
                        $name = $info['package'];
                    }
                    $sess['packages'][$channel][] = $name;
                }
            } else {
                $sess['packages'][$channel] = array();
            }
        }

        // PHP extensions available
        $sess['extensions'] = get_loaded_extensions();

        // Packages and Extensions dependencies
        $sess['dependencies'] = array();
        if ($sess['_newpackage'] === false) {
            $dependencies = $sess['pfm']->getDeps(true);

            foreach (array('required', 'optional') as $simpledep) {
                foreach (array('package', 'subpackage', 'extension') as $type) {

                    if (isset($dependencies[$simpledep][$type])) {
                        $iter = $dependencies[$simpledep][$type];
                        if (!isset($iter[0])) {
                            $iter = array($iter);
                        }
                        foreach ($iter as $package) {
                            $dep = $package;
                            if ($type == 'extension') {
                                $dep['extension'] = $package['name'];
                                unset($dep['name']);
                            } else {
                                if (is_array($package['exclude'])) {
                                    $dep['exclude'] = $package['exclude'][0];
                                }
                            }
                            $dep['type'] = $simpledep;
                            $dep['group'] = false;
                            $sess['dependencies'][] = $dep;
                        }
                    }
                }
            }
            if (isset($dependencies['group'])) {
                $groups = $dependencies['group'];
                if (!isset($groups[0])) {
                    $groups = array($groups);
                }
                foreach ($groups as $group) {
                    foreach (array('package', 'subpackage', 'extension') as $type) {

                        if (isset($group[$type])) {
                            $iter = $group[$type];
                            if (!isset($iter[0])) {
                                $iter = array($iter);
                            }
                            foreach ($iter as $package) {
                                $dep = $package;
                                if ($type == 'extension') {
                                    $dep['extension'] = $package['name'];
                                    unset($dep['name']);
                                } else {
                                    if (is_array($package['exclude'])) {
                                        $dep['exclude'] = $package['exclude'][0];
                                    }
                                }
                                $dep['type'] = 'group-'. $type;
                                $dep['group'] = $group['attribs'];
                                $sess['dependencies'][] = $dep;
                            }
                        }
                    }
                }
            }
        }

        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            ' dependencies=' . serialize($sess['dependencies'])
        );
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            ' packages=' . serialize($sess['packages'])
        );
        $this->log('debug',
            str_pad(__FUNCTION__ .'('. __LINE__ .')', 20, '.') .
            ' extensions=' . serialize($sess['extensions'])
        );
        return true;
    }

    /**
     * Returns replaces tasks applied on each file of the distribution:
     * <pre>
     *  - :
     * </pre>
     *
     * @return array
     * @since  0.1.0
     * @access private
     */
    function _getReplacements()
    {
        $sess =& $this->container();
        $option = $sess['pfm']->getOptions();

        $filelist = $sess['pfm']->getFilelist(true);
        if (is_array($filelist)) {
            $dirs = array('.');
            $exts = array();
            $k = 0;
            $dir_roles = $this->getOption('dir_roles');
            $ext_roles = $this->getOption('roles');

            foreach($filelist as $fname => $contents) {
                $sess['files']['mapping'][$k] = $option['packagedirectory'] . $fname;
                $parts = pathinfo($fname);

                if (isset($contents['attribs']['role'])) {
                    $role = $contents['attribs']['role'];
                } else {
                    if (!in_array($parts['dirname'], $dirs)) {
                        $dirs[] = $parts['dirname'];
                        if (array_key_exists($parts['dirname'], $dir_roles)) {
                            $role = $dir_roles[$parts['dirname']];
                        } else {
                            $role = 'php';
                        }
                    }
                    if ($parts['dirname'] === '.') {
                        if (isset($parts['extension']) && !in_array($parts['extension'], $exts)) {
                            $exts[] = $parts['extension'];
                            if (array_key_exists($parts['extension'], $ext_roles)) {
                                $role = $ext_roles[$parts['extension']];
                            } else {
                                $role = $ext_roles['*'];
                            }
                        }
                    }
                }
                $sess['files'][$k] = array('ignore' => false,
                    'role' => $role, 'platform' => false, 'replacements' => array()
                );
                if (isset($contents['tasks:replace'])) {
                    if (count($contents['tasks:replace']) == 1) {
                        $contents['tasks:replace'] = array($contents['tasks:replace']);
                    }
                    foreach($contents['tasks:replace'] as $r => $replace) {
                        $sess['files'][$k]['replacements'][$r] = array(
                            'from' => $replace['attribs']['from'],
                            'type' => $replace['attribs']['type'],
                            'to'   => $replace['attribs']['to']
                        );
                    }
                }
                $k++;
            }
        }

        $def = array('_files' => $sess['files']);
        return $def;
    }

    /**
     * Returns the element's value
     *
     * @param  string   $pageName      name of the page
     * @param  string   $elementName   name of the element in the page
     * @return mixed    value for the element
     * @since  0.2.0
     * @access public
     */
    function exportValue($pageName, $elementName)
    {
        $sess =& $this->container();
        return isset($sess['values'][$pageName][$elementName])? $sess['values'][$pageName][$elementName]: null;
    }

    /**
     *
     * @param  string  $pageId  page identifier
     * @return string
     * @since  0.2.0
     * @access public
     */
    function getPageName($pageId)
    {
        $keys = $this->multi_array_search($pageId, $this->_pages);
        return $this->_pages[$keys[0]]['@']['name'];
    }

    /**
     *
     * @since  0.1.0
     * @access public
     * @return mixed
     */
    function preparePackageFile()
    {
        $sess =& $this->container();
        $pkg = $sess['pfm'];

        // get valid options from page 1
        $pageName = $this->getPageName('page1');
        $options = array_merge($pkg->getOptions(), array(
            'outputdirectory'  => $this->exportValue($pageName,'packageOutputDir'),
            'packagefile'      => $this->exportValue($pageName,'packageFileName'),
            'packagedirectory' => $this->exportValue($pageName,'packageDir'),
            'baseinstalldir'   => $this->exportValue($pageName,'baseInstallDir')
        ));
        $warn = $pkg->setOptions($options, true);
        $this->pushWarning($warn);

        $pearInstaller = $this->exportValue($pageName,'pearInstaller');
        if (empty($pearInstaller['min'])) {
            $installer['min'] = false;
        } else {
            $installer['min'] = $pearInstaller['min'];
        }
        if (empty($pearInstaller['max'])) {
            $installer['max'] = false;
        } else {
            $installer['max'] = $pearInstaller['max'];
        }
        if (empty($pearInstaller['recommended'])) {
            $installer['recommended'] = false;
        } else {
            $installer['recommended'] = $pearInstaller['recommended'];
        }
        if (empty($pearInstaller['exclude'])) {
            $installer['exclude'] = false;
        } else {
            $installer['exclude'] = $pearInstaller['exclude'];
        }

        $phpVersion = $this->exportValue($pageName,'phpVersion');
        if (empty($phpVersion['min'])) {
            $php['min'] = false;
        } else {
            $php['min'] = $phpVersion['min'];
        }
        if (empty($phpVersion['max'])) {
            $php['max'] = false;
        } else {
            $php['max'] = $phpVersion['max'];
        }
        if (empty($phpVersion['exclude'])) {
            $php['exclude'] = false;
        } else {
            $php['exclude'] = $phpVersion['exclude'];
        }

        $packagetype = $this->exportValue($pageName,'packageType');
        $channel     = $this->exportValue($pageName,'channel');
        $package     = $this->exportValue($pageName,'packageName');
        $summary     = $this->exportValue($pageName,'packageSummary');
        $descr       = $this->exportValue($pageName,'packageDescription');

        $pkg->clearDeps();
        $pkg->clearContents();
        $pkg->setPearinstallerDep($installer['min'], $installer['max'], $installer['recommended'], $installer['exclude']);
        $pkg->setPhpDep($php['min'], $php['max'], $php['exclude']);
        $pkg->setChannel($channel);
        $pkg->setPackage($package);
        $pkg->setPackageType($packagetype);
        $pkg->setSummary($summary);
        $pkg->setDescription($descr);

        // get valid options from page 2
        $pageName = $this->getPageName('page2');
        $license  = $this->exportValue($pageName,'releaseLicense');
        if (empty($license['uri'])) {
            $uri = false;
        } else {
            $uri = $license['uri'];
        }
        $releaseState   = $this->exportValue($pageName,'releaseState');
        $releaseVersion = $this->exportValue($pageName,'releaseVersion');
        $apiState       = $this->exportValue($pageName,'APIState');
        $apiVersion     = $this->exportValue($pageName,'APIVersion');
        $releaseNotes   = $this->exportValue($pageName,'releaseNotes');

        $pkg->setLicense($license['content'], $uri);
        $pkg->addRelease();
        $pkg->setAPIVersion($apiVersion);
        $pkg->setAPIStability($apiState);
        $pkg->setReleaseVersion($releaseVersion);
        $pkg->setReleaseStability($releaseState);
        $pkg->setNotes($releaseNotes);

        // get valid options from page 4
        if (isset($sess['dependencies'])) {
            $groupname = '';
            foreach ($sess['dependencies'] as $dependency) {
                unset($min, $max, $recommended, $exclude, $extension);
                extract($dependency);
                if (!isset($extension)) {
                    if (substr($type, 0, 5) == 'group') {
                        $type = substr($type, 6);
                        if ($groupname != $group['name']) {
                            $pkg->addDependencyGroup($group['name'], $group['hint']);
                            $groupname = $group['name'];
                        }
                        $pkg->addGroupPackageDepWithChannel($type, $group['name'],
                            $name, $channel, $min, $max, $recommended, $exclude);
                    } else {
                        $pkg->addPackageDepWithChannel($type, $name,
                            $channel, $min, $max, $recommended, $exclude);
                    }
                } else {
                    if (substr($type, 0, 5) == 'group') {
                        $type = substr($type, 6);
                        if ($groupname != $group['name']) {
                            $pkg->addDependencyGroup($group['name'], $group['hint']);
                            $groupname = $group['name'];
                        }
                        $pkg->addGroupExtensionDep($group['name'], $extension,
                            $min, $max, $recommended, $exclude);
                    } else {
                        $pkg->addExtensionDep($type, $extension,
                            $min, $max, $recommended, $exclude);
                    }
                }
            }
        }

        // get valid options from page 5
        $pageName = $this->getPageName('page5');
        $plugin = $this->exportValue($pageName,'filelistgenerator');
        if (isset($plugin)) {
            $options = array_merge($pkg->getOptions(), array(
                'filelistgenerator' => $plugin
            ));
            $warn = $pkg->setOptions($options, true);
            $this->pushWarning($warn);
        }

        if (isset($sess['files'])) {
            $nix = $win = array();
            foreach ($sess['files'] as $k => $file) {
                if (!is_int($k)) {
                    // global file mapping is not scanned
                    continue;
                }
                if ($file['ignore'] === true) {
                    $pkg->addIgnore($sess['files']['mapping'][$k]);
                }
                $f = str_replace($options['packagedirectory'], '', $sess['files']['mapping'][$k]);
                if ($file['platform'] == 'windows') {
                    $win[] = $f;
                } elseif ($file['platform'] == '(*ix|*ux)') {
                    $nix[] = $f;
                }
                if ($file['eol']) {
                    if ($file['eol'] == 'windows') {
                        $pkg->addWindowsEol($f);
                    } else {
                        $pkg->addUnixEol($f);
                    }
                }
                if (!empty($file['installas'])) {
                    $pkg->addInstallAs($f, $file['installas']);
                }
                foreach($file['replacements'] as $r => $replace) {
                    $f = str_replace($options['packagedirectory'], '', $sess['files']['mapping'][$k]);
                    $warn = $pkg->addReplacement($f,
                        $replace['type'], $replace['from'], $replace['to']);
                    $this->pushWarning($warn);
                }
            }
            if (count($win)) {
                $pkg->setOSInstallCondition('windows');
                foreach($win as $file) {
                    $pkg->addIgnoreToRelease($file);
                }
            }
            if (count($nix)) {
                if (count($win)) {
                    $pkg->addRelease();
                }
                foreach($nix as $file) {
                    $pkg->addIgnoreToRelease($file);
                }
            }
        }

        // get valid options from page 7
        $pageName = $this->getPageName('page7');
        if (isset($sess['files'])) {
            $exceptions = array();
            foreach ($sess['files'] as $k => $file) {
                if (!is_int($k)) {
                    // global file mapping is not scanned
                    continue;
                }
                if (empty($file['role'])) {
                    continue;
                }
                $f = str_replace($options['packagedirectory'], '', $sess['files']['mapping'][$k]);
                $exceptions[$f] = $file['role'];
            }
            $options = array_merge($pkg->getOptions(), array(
                'exceptions' => $exceptions
            ));
            $warn = $pkg->setOptions($options, true);
            $this->pushWarning($warn);
        }

        // get valid options from page 8
        $pageName = $this->getPageName('page8');
        $options = array_merge($pkg->getOptions(), array(
            'changelogoldtonew' => $this->exportValue($pageName,'changelogOldToNew'),
            'simpleoutput'      => $this->exportValue($pageName,'simpleOutput')
        ));
        $warn = $pkg->setOptions($options, true);
        $this->pushWarning($warn);

        return $pkg;
    }

    /**
     *
     * @since  0.1.0
     * @access public
     * @return mixed
     */
    function buildPackageFile($debug, $exportV1, $changelogOldToNew, $simpleOutput)
    {
        $pkg = $this->preparePackageFile();

        $options = array_merge($pkg->getOptions(), array(
            'changelogoldtonew' => $changelogOldToNew,
            'simpleoutput'      => $simpleOutput,
        ));
        $pkg->setOptions($options, true);

        $pkg->generateContents();
        // purge warnings
        $warn = $pkg->getValidationWarnings();

        if (isset($debug) && $debug) {
            ob_start();
            $warn = $pkg->writePackageFile($debug);
            $preview = ob_get_contents();
            ob_end_clean();
        } else {
            if ($exportV1) {
                $pkgV1 = &$pkg->exportCompatiblePackageFile1();
                $this->pushWarning($pkgV1);
                $warn = $pkgV1->writePackageFile($debug);
                $this->pushWarning($warn);
            }
            $warn = $pkg->writePackageFile($debug);
        }
        $this->pushWarning($warn);

        if (PEAR_PackageFileManager_Frontend::hasErrors()) {
            return false;
        }

        if (isset($debug) && $debug) {
            return $preview;
        }
        $outputdir = !empty($options['outputdirectory']) ? $options['outputdirectory'] : $options['packagedirectory'];
        $packagexml = $outputdir . $options['packagefile'];
        return $packagexml;
    }

    /**
     * Push a warning or error to the warning stack.
     *
     * @param  object  $err  PEAR_Error instance
     * @return void
     * @since  0.1.0
     * @access public
     */
    function pushWarning($err)
    {
        if (PEAR::isError($err)) {
            $this->repackagePEAR_Error($err);
        }
    }

   /**
    * Processes the request.
    *
    * @access  public
    * @since   0.1.0
    * @abstract
    */
    function run()
    {
        trigger_error('run is an abstract method that must be ' .
                      'overridden in the child class', E_USER_ERROR);
    }

    /**
     * @since  0.1.0
     * @access public
     */
    function realpathnix($path)
    {
        if (!is_string($path)) {
            $path = false;
        } else {
            $path = trim($path);
            if (file_exists($path)) {
                $path = str_replace(DIRECTORY_SEPARATOR, '/', realpath($path));
                if (is_dir($path) && substr($path, -1, 1) !== '/') {
                    $path .= '/';
                }
            } else {
                $path = false;
            }
        }
        return $path;
    }

    /**
     * array_merge_recursive()
     *
     * Similar to array_merge_recursive but keyed-valued are always overwritten.
     * Priority goes to the 2nd array.
     *
     * @param  array  $paArray1
     * @param  array  $paArray2
     * @return array
     * @access public
     * @since  0.1.0
     * @author brian at vermonster dot com
     * @link   http://www.php.net/manual/en/function.array-merge-recursive.php#42663
     */
    function array_merge_recursive($paArray1, $paArray2)
    {
       if (!is_array($paArray1) or !is_array($paArray2)) {
           return $paArray2;
       }
       foreach ($paArray2 as $sKey2 => $sValue2) {
           $paArray1[$sKey2] = $this->array_merge_recursive(@$paArray1[$sKey2], $sValue2);
       }
       return $paArray1;
    }

    /**
     * Multi-Dimensional Array Search
     *
     * <code>
     *   <?php
     *   $foo[1]['a']['xx'] = 'bar 1';
     *   $foo[1]['b']['xx'] = 'bar 2';
     *   $foo[2]['a']['bb'] = 'bar 3';
     *   $foo[2]['a']['yy'] = 'bar 4';
     *   $foo['info'][1] = 'bar 5';
     *
     *   $result = multi_array_search('bar 3', $foo);
     *   print_r($result);
     *   ?>
     * </code>
     * <pre>
     *   Output:
     *   Array
     *   (
     *        [0] => 2
     *        [1] => a
     *        [2] => bb
     *   )
     * </pre>
     *
     * @author scripts at webfire dot org
     * @since  0.3.0
     * @link   http://www.php.net/manual/en/function.array-search.php#47116
     */
    function multi_array_search($search_value, $the_array)
    {
       if (is_array($the_array)) {

           foreach ($the_array as $key => $value) {
               $result = $this->multi_array_search($search_value, $value);
               if (is_array($result)) {
                   $return = $result;
                   array_unshift($return, $key);
                   return $return;

               } elseif ($result == true) {
                   $return[] = $key;
                   return $return;
               }
           }
           return false;
       } else {
           return ($search_value == $the_array);
       }
    }
}
?>