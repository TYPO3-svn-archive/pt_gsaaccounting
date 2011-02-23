#! /usr/local/bin/php
<?php
##########################################################################
#####  This CLI script must always be executed by its absolute path! #####
##########################################################################
##### First line of this file is OS specific: use the following for: #####
##### FreeBSD : #! /usr/local/bin/php                                #####
##### Linux(?): #! /usr/bin/php                                      #####
##########################################################################
/** 
 * Command Line Interface Caller Script for tx_ptgsaaccounting_dta
 * !!! This CLI script must always be executed by its absolute path !!!
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-06-30
 */ 
 
define('TYPO3_cliMode', TRUE);
// Defining PATH_thisScript: must be the ABSOLUTE path of this script in the right context - this will work as long as the script is called by it's absolute path!

define('PATH_thisScript', (isset($_ENV['_']) ? $_ENV['_'] : (isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : $_SERVER['_']))); // double fallback - $_SERVER['argv'][0] works for FreeBSD

// set up TYPO3-environment
require_once(dirname(PATH_thisScript).'/conf.php');
define('PATH_tslib',dirname(PATH_thisScript).'/'.$BACK_PATH.'sysext/cms/tslib/');
require_once(dirname(PATH_thisScript).'/'.$BACK_PATH.'init.php');


//Creating a fake TSFE object
require_once(dirname(PATH_thisScript).'/faketsfe.inc.php');
//require_once t3lib_extMgm::extPath('pt_gsaaccounting').'cronmod/faketsfe.inc.php'; // fake TSFE
require_once(t3lib_extMgm::extPath('pt_gsaaccounting').'cronmod/class.tx_ptgsaaccounting_dta.php');


//$trace = 2;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsasocket']['logEnabled'] = 0;
$statuscode = false;
$processor = new tx_ptgsaaccounting_dta();
$statuscode = $processor->run($_SERVER['argv']);
// setpgid (Eigene ProzessgruppenID für die Kindprozesse => bekommen nicht die Signale des Vaters ab (CTRL+C))

exit ($statuscode);


?>