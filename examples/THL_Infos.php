<?php
/**
 * An example script that retrieves informations
 * from PEAR::Text_Highlighter package
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

session_start();

// where to find package sources
$pkgDir = 'E:/PEAR/Text/Text_Highlighter-0.6.9';

$pfmfe =& PEAR_PackageFileManager_Frontend::singleton('Null', $pkgDir);
$pfmfe->setOption('baseinstalldir', 'Text');
if ($pfmfe->hasErrors()) {
    $errors = $pfmfe->getErrors();
    varDump($errors);
    die('exit on Error');
}

echo '<h1>Package Summary </h1>';
$def = $pfmfe->getDefaults('package');
varDump($def);

echo '<h1>Latest Release </h1>';
$def = $pfmfe->getDefaults('release');
varDump($def);

echo '<h1>Maintainers </h1>';
$def = $pfmfe->getDefaults('maintainers');
varDump($def);

echo '<h1>Dependencies </h1>';
$def = $pfmfe->getDefaults('dependencies');
varDump($def);

// cleansweep session data
$pfmfe->container(true);
session_destroy();
?>