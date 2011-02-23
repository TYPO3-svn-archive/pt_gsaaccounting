<?php

//    error_reporting (E_ALL ^ E_NOTICE);
//    define('TYPO3_OS', (stristr(PHP_OS,'win') && !stristr(PHP_OS,'darwin')) ? 'WIN' : '');
//    if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='xcgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&((!empty($_SERVER['ORIG_PATH_TRANSLATED'])&&isset($_SERVER['ORIG_PATH_TRANSLATED']))?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ((!empty($_SERVER['ORIG_PATH_TRANSLATED'])&&isset($_SERVER['ORIG_PATH_TRANSLATED']))?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):((!empty($_SERVER['ORIG_SCRIPT_FILENAME'])&&isset($_SERVER['ORIG_SCRIPT_FILENAME']))?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));
//    if (!defined('PATH_site')) define('PATH_site', dirname(dirname(dirname(dirname(dirname(dirname(dirname(PATH_thisScript))))))).'/');
//    if (!defined('PATH_t3lib')) define('PATH_t3lib', PATH_site.'t3lib/');
//    define('PATH_typo3conf', PATH_site.'typo3conf/');
//    define('TYPO3_mainDir', 'typo3/');
//    if (!defined('PATH_typo3')) define('PATH_typo3', PATH_site.TYPO3_mainDir);
//    if (!defined('PATH_tslib')) {
//        if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
//            define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
//        } elseif (@is_dir(PATH_site.'tslib/')) {
//            define('PATH_tslib', PATH_site.'tslib/');
//        }
//    }
    define('TYPO3_MODE','FE'); //useless if TYPO3_MODE defined
    
    
    require_once(PATH_t3lib.'class.t3lib_div.php');
    require_once(PATH_t3lib.'class.t3lib_extmgm.php');
    require_once(PATH_t3lib.'config_default.php');
    require_once(PATH_typo3conf.'localconf.php');
    require_once(PATH_tslib.'class.tslib_fe.php');
    require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
    require_once(PATH_t3lib.'class.t3lib_page.php');
    require_once(PATH_tslib.'class.tslib_content.php');
    
    require_once(PATH_t3lib.'class.t3lib_userauth.php');
    require_once(PATH_tslib.'class.tslib_feuserauth.php');
    
    $typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
    require_once(PATH_t3lib.'class.t3lib_cs.php');
    
    if (!defined ('TYPO3_db'))  die ('The configuration file was not included.');
    if (isset($HTTP_POST_VARS['GLOBALS']) || isset($HTTP_GET_VARS['GLOBALS']))      die('You cannot set the GLOBALS-array from outside this script.');
    
    require_once(PATH_t3lib.'class.t3lib_db.php');
    $TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
    
    require_once(PATH_t3lib.'class.t3lib_timetrack.php');
    $GLOBALS['TT'] = new t3lib_timeTrack;
    
    // ***********************************
    // Creating a fake $TSFE object
    // ***********************************
    $TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
    $id = isset($HTTP_GET_VARS['id'])?$HTTP_GET_VARS['id']:0;
    $GLOBALS['TSFE'] = new $TSFEclassName($TYPO3_CONF_VARS, $id, '0', 1, '', '','','');
    $GLOBALS['TSFE']->connectToMySQL();
    $GLOBALS['TSFE']->initFEuser();
    $GLOBALS['TSFE']->fetch_the_id();
    $GLOBALS['TSFE']->getPageAndRootline();
    $GLOBALS['TSFE']->initTemplate();
    $GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
    $GLOBALS['TSFE']->forceTemplateParsing = 1;
    $GLOBALS['TSFE']->getConfigArray();



?>
