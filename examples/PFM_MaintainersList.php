<?php
/**
 * An example script that retrieves maintainers list
 * from PEAR::PEAR_PackageFileManager package
 * using PEAR_PackageFileManager_Frontend with no front end.
 *
 * @author    Laurent Laville
 * @package   PEAR_PackageFileManager_Frontend
 * @version   $Id$
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @copyright 2006 Laurent Laville
 * @ignore
 */

require_once 'PEAR/PackageFileManager/Frontend.php';

/**
 * This class allow to use PEAR_PackageFileManager_Frontend as backend without any frontend.
 * No end-user action needed.
 * @ignore
 */
class PEAR_PackageFileManager_Frontend_Null extends PEAR_PackageFileManager_Frontend
{
    function PEAR_PackageFileManager_Frontend_Null($driver, $packagedirectory, $pathtopackagefile)
    {
        parent::PEAR_PackageFileManager_Frontend($driver, $packagedirectory, $pathtopackagefile);

        // load all default preferences
        $config = false;
        $this->loadPreferences($config);
    }
}

/**
 * A wrapper for PEAR::Var_Dump package or default php var_dump() function.
 * @ignore
 */
function varDump($var)
{
    $available = PEAR_PackageFileManager2::isIncludeable('Var_Dump.php');
    if ($available) {
        include_once 'Var_Dump.php';
        Var_Dump::display($var, false, array('display_mode' => 'HTML4_Table'));
    } else {
        echo '<pre style="background-color:#eee; color:#000; padding:1em;">';
        var_dump($var);
        echo '</pre>';
    }
}

/**
 * Callback error handler for PEAR_PackageFileManager_Frontend and
 * its error stack.
 * @ignore
 */
function haltOnError($err)
{
    if ($err['level'] == 'error') {
        echo __FUNCTION__;
        varDump($err);
        return PEAR_ERRORSTACK_DIE;
    }
}

session_start();

// where to find package sources
$pkgDir = 'd:/php/pear/PEAR_PackageFileManager';

PEAR_ErrorStack::staticPushCallback('haltOnError');

$pfmfe =& PEAR_PackageFileManager_Frontend::singleton('Null', $pkgDir);
$pfmfe->setOption('baseinstalldir', 'PEAR');

echo '<h1>All maintainers</h1>';
$maint = $pfmfe->getMaintList();
varDump($maint);

echo '<h1>Leader only </h1>';
$maint = $pfmfe->getMaintList('lead');
varDump($maint);

echo '<h1>Developper only </h1>';
$maint = $pfmfe->getMaintList('developer');
varDump($maint);

echo '<h1>unknown users </h1>';
$maint = $pfmfe->getMaintList('users');
varDump($maint);

// cleansweep session data
$pfmfe->container(true);
session_destroy();
?>