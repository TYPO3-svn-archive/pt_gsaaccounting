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
 * Command Line Interface Caller Script for tx_ptgsaaccounting_listgenerator
 * !!! This CLI script must always be executed by its absolute path !!!
 *
 * $Id$
 *
 * @author	Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-08-20
 */ 


// Dev debug - uncomment to use
//echo('$_ENV[_]='.$_ENV['_']."\n\n");
//echo('$_SERVER[_]='.$_SERVER['_']."\n\n");
//echo('$_SERVER[argv][0]='.$_SERVER['argv'][0]."\n\n");
//die();


/**
 * Standard initialization of a TYPO3 CLI module
 */
// Defining circumstances for CLI mode
define('TYPO3_cliMode', TRUE);
// Defining PATH_thisScript: must be the ABSOLUTE path of this script in the right context - this will work as long as the script is called by it's absolute path!
define('PATH_thisScript', (isset($_ENV['_']) ? $_ENV['_'] : (isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : $_SERVER['_']))); // double fallback - $_SERVER['argv'][0] works for FreeBSD
// Include configuration file
require(dirname(PATH_thisScript).'/conf.php');
// Include init file
require(dirname(PATH_thisScript).'/'.$BACK_PATH.'init.php');


/**
 * Individual class initialization   
 */  
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'cronmod/class.tx_ptgsaaccounting_listgenerator.php';
$cli_listgenerator = t3lib_div::makeInstance('tx_ptgsaaccounting_listgenerator');
$cli_listgenerator->run();


?>