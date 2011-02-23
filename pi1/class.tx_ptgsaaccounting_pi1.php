<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Dorit Rottner <rottner@punkt.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'GSA Accounting: Outstanding Items' for the 'pt_gsaaccounting' extension.
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-30
 * @package TYPO3
 * @subpackage  tx_ptgsaaccounting
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionHandler.php';  // GSA accounting Transaction Handler class
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // GSA accounting Transaction Accesor class
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_erpDocumentCollection.php';  // GSA accounting Document Collection Class
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dueCustomerCollection.php';  // GSA accounting Customer CollectionClass
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/staticlib/class.tx_ptgsaaccounting_div.php';  // static Methods of extension pt_gsaaccounting

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_gsaTransactionAccessor.php';  // GSA accounting Transaction Accesor class

require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formReloadHandler.php'; // web form reload handler class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // message box class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods

require_once t3lib_extMgm::extPath("jquery").'class.tx_jquery.php';

/**
 * Debugging config for development RE-200807/00869
 */

//$trace     = 1; // (int) trace options: 0=disable, 1=screen output, 2= write to log file (if configured in Constant Editor of pt_tools)
//$errStrict = 0; // (bool) set strict error reporting level for development (requires $trace to be set to 1)
$GLOBALS['TYPO3_DB']->debugOutput = true; 
$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
#trace($GLOBALS['TYPO3_DB']);


class tx_ptgsaaccounting_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_ptgsaaccounting_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ptgsaaccounting_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'pt_gsaaccounting';	// The extension key.
	
    /**
     * Constants
     */
    const PM_CCARD = 'cc';
    const PM_INVOICE = 'bt';
    const PM_DEBIT = 'dd';

    const PRECISSION = 4;
    const SESSION_KEY_SEARCH = 'pt_gsaaccounting_search';   // (string) session key name to store search Values
    const SESSION_KEY_BOOKDATE = 'pt_gsaaccounting_bookdate';   // (string) session key name to store book Date 
    const SESSION_KEY_RELATEDDOCNO = 'pt_gsaaccounting_relateddocno';   // (string) session key name relateDocNo in GSA 

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
        $this->conf = $conf;
        trace($this->conf,0,'$this->conf');
        $this->shopConfig = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ptgsashop.'];        
        trace($this->shopConfig,0,'shopConfig');

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
        trace($this->piVars,0,'$this->piVars');
        trace($GLOBALS['TSFE']->fe_user->user,0,'fe_user->user');
	
        $this->sysadmin = in_array($this->conf['shopOperatorGroupUid'],$GLOBALS['TSFE']->fe_user->groupData['uid']) ? true : false;
        $this->allowBookPayment = in_array($this->conf['bookPaymentGroupUid'],$GLOBALS['TSFE']->fe_user->groupData['uid']) ? true : false;
        trace($this->sysadmin,0,'$this->sysadmin');

        try {
            if ($GLOBALS['TSFE']->loginUser != 1) {
                    throw new tx_pttools_exception('User Login expired.', 4);
            }
            if ($this->sysadmin != true ) {
                throw new tx_pttools_exception('You are not authorized.', 4);
            }
            //$this->formHandler = new tx_pttools_formTemplateHandler($this, $this->formdesc);

            if ($this->piVars) {
                $this->getEnvironment();
                // delete any stored info from session if called from outside
                if ($this->command == 'back_outstandingItemList'){
                    $content = $this->exec_showOutstandingItemList();
                } elseif ($this->command == 'back_search'){
                    $content = $this->exec_showSearchForm();
                } elseif ($this->command == 'search'){
                    $content = $this->exec_searchOutstandingItems();
                } elseif ($this->command == 'select_allItems'){
                    $content = $this->exec_showOutstandingItemList();
                } elseif ($this->command == 'showPosition'){
                    $content = $this->exec_showOutstandingPositions();
                } elseif ($this->command == 'book'){
                    $content = $this->exec_bookOutstandingItems();
                } elseif ($this->command == 'credit'){
                    $content = $this->exec_creditOutstandingPositions();
                } elseif ($this->command == 'showSearch'){
                    $content = $this->exec_showSearchForm();
                }
            } else {
                trace ($GLOBALS['TSFE']->fe_user->groupData['uid'] ,0,'groupUid');
                $content = $this->exec_showSearchForm();
            }
        } catch (tx_pttools_exception $excObj) {
                // if an exception has been catched, handle it and overwrite plugin content with error message
                $excObj->handleException();
                $content = '<i>'.$excObj->__toString().'</i>';
        }
		return $this->pi_wrapInBaseClass($content);
	}

    /**
     * book Payment or Credits for an whole Invoice
     * @param   void
     * @return  string formated content for outstandingItemList Form
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-31
     */
    public function exec_bookOutstandingItems() {
        trace('[CMD] '.__METHOD__);

        $session = tx_pttools_sessionStorageAdapter::getInstance();

        $doPaymentArr = array();
        $doCreditArr = array();
        $doOkArr = array();
        $restokArr = array();
        $paymentArr = array();
        $doCancellationArr = array();
        
        // get Payment Values from piVars
        foreach ($this->piVars as $key => $val ) {
            #trace($key.':'.$val);
            $fields = explode('_', $key); 
            if ($fields[0] == 'bookit' && $val == 'payment') {
                $doPaymentArr[(string)$fields[1]] = true;
            }
            if ($fields[0] == 'bookit' && $val == 'credit') {
                $doCreditArr[(string)$fields[1]] = true;
            }
            if ($fields[0] == 'bookit' && $val == 'cancellation') {
                $doCancellationArr[(string)$fields[1]] = true;
            }
            if ($fields[0] == 'doOk') {
                $doOkArr[(string)$fields[1]] = true;
            }
            if ($fields[0] == 'payment') {
                $paymentArr[(string)$fields[1]] = floatval(str_replace(',','.',$val));
            }
            if ($fields[0] == 'restok') {
                $restokArr[(string)$fields[1]] = true;
            } 
        }
        trace($doPaymentArr,0,'$doPaymentArr');        
        trace($doCreditArr,0,'$doCreditArr');        
        trace($restokArr,0,'$restokArr');        
        trace($paymentArr,0,'$paymentArr');        
        
        $gsaAccountingTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $errorMessage = '';
        foreach ($doPaymentArr as $relatedDocNo => $val ) {
            $date = tx_ptgsaaccounting_div::convertDateToMySqlFormat($this->piVars['bookDate']);
            if ($date!= '') {
                // get Document from GSA-DB
                $testArr = $gsaAccountingTransactionAccessorObj->isPayed($relatedDocNo);
                trace($testArr,0,'vor Bezahlung $testArr');
                $documentArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
                trace($documentArr,0,'$documentArr');
                $outstandingAmount = $documentArr['ENDPRB'] - $documentArr['GUTSUMME'] - $documentArr['BEZSUMME']-$documentArr['SKONTOBETRAG'];
                if (bcsub($outstandingAmount, $paymentArr[$relatedDocNo],self::PRECISSION) >=0) {
                    $restok = $restokArr[$relatedDocNo] == true ? true : false; 
                    if ($restok == true) {
                        // Handle difference as discount like ERP
                        $discount = $documentArr['ENDPRB'] - $documentArr['GUTSUMME'] - $documentArr['BEZSUMME']-$documentArr['SKONTOBETRAG'] - $paymentArr[$relatedDocNo];
                    } else {
                        $discount = 0;
                    }                     
                    trace('book payment');    
                    $gsaAccountingTransactionHandlerObj->bookPayment($relatedDocNo,$paymentArr[$relatedDocNo],$discount, $restok, $date,$GLOBALS['TSFE']->fe_user->user['username']);            
                } else {
                    $errorMessage .= $this->pi_getLL('msg_errorr_paymentToMuch', '[msg_errorr_paymentToMuch]').' '.$relatedDocNo.'<br>';
                    trace($errorMessage,0,'$errorMessage in else');
                }
                $testArr = $gsaAccountingTransactionAccessorObj->isPayed($relatedDocNo);
                trace($testArr,0,'nach Bezahlung $testArr');
            } else {
                $errorMessage .= $this->pi_getLL('msg_errorr_wrongDate', '[msg_errorr_wrongDate]').'<br>';
            }    
        }        
        trace($errorMessage,0,'$errorMessage');
        foreach ($doCreditArr as $relatedDocNo => $val ) {
            // get Document from GSA-DB
            $documentArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
            trace($documentArr,0,'$documentArr');
            $outstandingAmount = $documentArr['ENDPRB'] - $documentArr['GUTSUMME'] - $documentArr['BEZSUMME']-$documentArr['SKONTOBETRAG'];
            $amountShipping = $documentArr['FLDN01'];
            if (bcsub($outstandingAmount, $paymentArr[$relatedDocNo],self::PRECISSION) >=0) {
                $restok = $restokArr[$relatedDocNo] == true ? true : false; 
                if ($restok == true) {
                    $infoMessage = $this->pi_getLL('msg_info_restOkCredit', '[msg_errorr_restOkCredit]').'<br>';
                }                     
                trace('credit');    
                $gsaAccountingTransactionHandlerObj->bookCreditCancellation($relatedDocNo,'GU',$paymentArr[$relatedDocNo],$amountShipping);            
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingitems_hooks']['fixCredit'])) {   
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingitems_hooks']['fixCredit'] as $funcName) {
                        $erpDocument = new tx_ptgsaaccounting_erpDocument(0,$relatedDocNo);
                        $params = array(
                            '$erpDocument' => $erpDocument,
                            '$amount' => $paymentArr[$relatedDocNo],
                        );
                        t3lib_div::callUserFunction($funcName, $params, $this, '');
                        if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "fixCancellation"', $funcName), $this->extKey, 1, array('params' => $params));
                    }
                }   
            }
            $testArr = $gsaAccountingTransactionAccessorObj->isPayed($relatedDocNo);
            trace($testArr,0,'nach Gutschrift $testArr');
        }       
        
        foreach ($doCancellationArr as $relatedDocNo => $val ) {
            // get Document from GSA-DB
            $documentArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
            trace($documentArr,0,'$documentArr');
            $outstandingAmount = $documentArr['ENDPRB'] - $documentArr['GUTSUMME'] - $documentArr['BEZSUMME']-$documentArr['SKONTOBETRAG'];
            $amountShipping = $documentArr['FLDN01'];
            if (bcsub($outstandingAmount, $paymentArr[$relatedDocNo],self::PRECISSION) >=0) {
                $restok = $restokArr[$relatedDocNo] == true ? true : false; 
                if ($restok == true) {
                    $infoMessage = $this->pi_getLL('msg_info_restOkCredit', '[msg_errorr_restOkCredit]').'<br>';
                }                     
                trace('cancellation');    
                $gsaAccountingTransactionHandlerObj->bookCreditCancellation($relatedDocNo,'ST',$paymentArr[$relatedDocNo],$amountShipping);            
		        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingitems_hooks']['fixCancellation'])) {   
		            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingitems_hooks']['fixCancellation'] as $funcName) {
		                $erpDocument = new tx_ptgsaaccounting_erpDocument(0,$relatedDocNo);
		            	$params = array(
		                    '$erpDocument' => $erpDocument,
                            '$amount' => $paymentArr[$relatedDocNo],
		            	);
		                t3lib_div::callUserFunction($funcName, $params, $this, '');
		                if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "fixCancellation"', $funcName), $this->extKey, 1, array('params' => $params));
		            }
		        }   
            }
            $testArr = $gsaAccountingTransactionAccessorObj->isPayed($relatedDocNo);
            trace($testArr,0,'nach Storno $testArr');
        }       
        
        foreach ($restokArr as $relatedDocNo => $val ) {
            trace ($relatedDocNo,0,'$relatedDocNo from restok');
            if (!$doCreditArr[$relatedDocNo] && !$doPaymentArr[$relatedDocNo] &&!$paymentArr[$relatedDocNo]) {
                $gsaAccountingTransactionHandlerObj->setInvoiceOk($relatedDocNo);
            }
        }
        
        

        $searchArr = $session->read(self::SESSION_KEY_SEARCH);
        
        $erpDocuments = new tx_ptgsaaccounting_erpDocumentCollection(true,0,'',$searchArr);
        trace($erpDocuments,0,'$erpDocuments');
                
        $content = '';
        $msg = '';
        if($errorMessage) {
            $msgBoxObj = new tx_pttools_msgBox('error', $errorMessage);
            $msg .= $msgBoxObj->__toString();
        }  
        if($errorMessage) {
            $msgBoxObj = new tx_pttools_msgBox('info', $infoMessage);
            $msg .= $msgBoxObj->__toString();
        }  
        $content = $msg.$this->display_outstandingItemList($erpDocuments);

        return $content;
    }

    /**
     * credit single positions or shipping
     * @param   void
     * @return  string formated content for outstandingItemList Form
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-20
     */
    public function exec_creditOutstandingPositions() {
        trace('[CMD] '.__METHOD__);

        $session = tx_pttools_sessionStorageAdapter::getInstance();
        $relatedDocNo = $session->read(self::SESSION_KEY_RELATEDDOCNO);

        $quantityArr= array();
        $priceArr = array();
        $articleUidArr = array();
        
        // get Payment Values from piVars
        #$GLOBALS['trace'] = 1;
        trace ($this->piVars);
        foreach ($this->piVars as $key => $val ) {
            trace($key.':'.$val);
            $fields = explode('_', $key); 
            if ($fields[0] == 'articleUid') {
                $articleUidArr[(string)$fields[1].'_'.$fields[2]] = $val;
            }
            if ($fields[0] == 'unitPrice') {
                $priceArr[(string)$fields[1].'_'.$fields[2]] = floatval(str_replace(',','.',$val));
            }
            if ($fields[0] == 'bookit' && $val) {
                $quantityArr[(string)$fields[1].'_'.$fields[2]] = intval($val);
            }
            if ($fields[0] == 'shipping') {
                $shipping = true;
                $amountShipping = floatval(str_replace(',','.',$val));
            } 
            if ($fields[0] == 'booktype' && $val == 'credit') {
                $booktype  = 'GU';
            } else if ($fields[0] == 'booktype' && $val == 'cancellation') {
                $booktype  = 'ST';
            }  
            
        }
        foreach ($articleUidArr as $key => $val) {
        	if ($quantityArr[$key]) {
        		$articleArr['article_uid'] = intval($val);
        		$articleArr['quantity'] = $quantityArr[$key];
        	}
        }
        trace($quantityArr,0,'$quantityArr');        
        trace($shipping,0,'$shipping');        
        trace($priceArr,0,'$priceArr');        
        trace($booktype,0,'$booktype');        
        trace($articleArr,0,'$articleArr');        
        
        

        if ($booktype && !empty($quantityArr)) {
	        $gsaAccountingTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
	        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
	        $errorMessage = '';
	        $infoMessage = '';
        	$i=0;
	        $posArr = array();
	        foreach ($quantityArr as $key => $val ) {
	            $amountGross = bcadd($amountGross, bcmul($priceArr[$key],$val,self::PRECISSION),self::PRECISSION);
	            trace($key.':'.$val);
	            $fields = explode('_',$key);
	            $posArr[$i]['invoiceId'] = $fields[0]; 
	            $posArr[$i]['posNo'] = $fields[1]; 
	            $posArr[$i]['priceUnit'] = $priceArr[$key]; 
	            $posArr[$i]['noToCredit'] = $val; 
	            $i++;
	        }       
            $documentArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
            trace($documentArr,0,'$documentArr');
	        
            // Add shiiping if exist
            if ($amountShipping) {
	            $amountGross = bcadd($amountGross,$amountShipping,self::PRECISSION);
	            if ($documentArr['PRBRUTTO'] == 1) {
	                $taxrate  = tx_ptgsashop_lib::getTaxRate($this->shopConfig['dispatchTaxCode'], $documentArr['DATUM']);
	                $amountShipping = tx_pttools_finance::getNetPriceFromGross($amountShipping, $taxrate);
	            } 
	            $amountShipping = tx_pttools_finance::getFormattedPriceString($amountShipping);
	        }
	        trace($posArr,0,'$posArr');
	        // get Document from GSA-DB
	        $outstandingAmount = $documentArr['ENDPRB'] - $documentArr['GUTSUMME'] - $documentArr['BEZSUMME']-$documentArr['SKONTOBETRAG'];
            $amountGross = round($amountGross,2);
	        trace($amountGross,0,'$amountGross');
            trace($outstandingAmount,0,'$outstandingAmount');
            if (bcsub($outstandingAmount, $amountGross,self::PRECISSION) >=0) {
	            trace('book-it '.$booktype);    
	            $gsaAccountingTransactionHandlerObj->bookCreditCancellation($relatedDocNo,$booktype,$amountGross, $amountShipping, $posArr );            
                
	            // Hooks after GSA Accountinghandling  
	            if ($booktype == 'GU') {
                    $erpDocument = new tx_ptgsaaccounting_erpDocument(0,$relatedDocNo);
	            	if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingpositions_hooks']['fixCredit'])) {   
	                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingpositions_hooks']['fixCredit'] as $funcName) {
	                        $params = array(
	                            'erpDocument' => $erpDocument,
	                            'articleUidArr' => $articleArr,
	                        );
	                        t3lib_div::callUserFunction($funcName, $params, $this, '');
	                        if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "fixCancellation"', $funcName), $this->extKey, 1, array('params' => $params));
	                    }
	                }   
                	
                } else if ($booktype == 'ST'){
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingpositions_hooks']['fixCancellation'])) {   
                        foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['outstandingpositions_hooks']['fixCancellation'] as $funcName) {
                            $params = array(
                                'erpDocument' => $erpDocument,
                                'articleUidArr' => $articleArr,
                            );
                            t3lib_div::callUserFunction($funcName, $params, $this, '');
                            if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "fixCancellation"', $funcName), $this->extKey, 1, array('params' => $params));
                        }
                    }   
                	
                }
            }
        }
        $searchArr = $session->read(self::SESSION_KEY_SEARCH);
        $erpDocuments = new tx_ptgsaaccounting_erpDocumentCollection(true,0,'',$searchArr);
        trace($erpDocuments,0,'$erpDocuments');
        
        $content = '';
        $msg = '';
        if($errorMessage) {
            $msgBoxObj = new tx_pttools_msgBox('error', $errorMessage);
            $msg .= $msgBoxObj->__toString();
        }  
        if($errorMessage) {
            $msgBoxObj = new tx_pttools_msgBox('info', $infoMessage);
            $msg .= $msgBoxObj->__toString();
        }  
        $content = $msg.$this->display_outstandingItemList($erpDocuments);

        return $content;
    }

    /**
     * search Outstanding Items and store search array in SESSION_KEY
     * @param   void
     * @return  string formated content for outstandingItemList Form
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-05
     */
    public function exec_searchOutstandingItems() {
        trace('[CMD] '.__METHOD__);

        $session = tx_pttools_sessionStorageAdapter::getInstance();
        if (is_array($this->piVars['searchField'])) {
            trace($this->piVars['searchField']);
            $searchArr['searchFields'] = '';
            foreach($this->piVars['searchField'] as $key => $value) {
                trace($key.':'.$value,0,'key => value');
                if ($value == 'sf_all') {
                    $searchArr['searchFields'] = (string) $value;
                    break;
                } else {
                    $searchArr['searchFields'] .= $searchArr['searchFields'] != '' ? '#' : '';
                    $searchArr['searchFields'] .= (string) $value;
                }
            }
        }
        $searchArr['searchOperator']  = $this->piVars['searchOperator'] != '' ? $this->piVars['searchOperator'] : '';
        $searchArr['searchExact']     = $this->piVars['searchExact'] != '' ? true : false;
        $searchArr['searchAll']       = $this->piVars['searchAll'] != '' ? true : false;
        $searchArr['searchText']      = $this->piVars['searchText']  != '' ?  (string) $this->piVars['searchText'] : '';
        $session->store(self::SESSION_KEY_SEARCH,$searchArr);
        
        trace($searchArr,0,'$searchArr');


        $content = '';
        $content = $this->exec_showOutstandingItemList();

        return $content;
    }

    /**
     * show Outstanding Item List
     * @param   void
     * @return  string formated content for outstandingItemList Form
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-30
     */
    public function exec_showOutstandingItemList() {
        trace('[CMD] '.__METHOD__);

        $session = tx_pttools_sessionStorageAdapter::getInstance();
        $searchArr = $session->read(self::SESSION_KEY_SEARCH);
        $session->store(self::SESSION_KEY_RELATEDDOCNO,NULL);

        trace($searchArr,0,'$searchArr');

        #$GLOBALS['trace'] = 1; 
        #$erpCustomers = new tx_ptgsaaccounting_dueCustomerCollection('bt');
        #trace($erpCustomers,0,'$erpCustomer');
        $erpDocuments = new tx_ptgsaaccounting_erpDocumentCollection(true,0,'',$searchArr);
        trace($erpDocuments,0,'$erpDocuments');
        #$GLOBALS['trace'] = 0; 
        
        $content = $this->display_outstandingItemList($erpDocuments);
        
        return $content;
    }

    /**
     * show Item Positions
     * @param   void
     * @return  string formated content for itemPosisions Form
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-18
     */
    public function exec_showOutstandingPositions() {
        trace('[CMD] '.__METHOD__);

        $session = tx_pttools_sessionStorageAdapter::getInstance();
        $relatedDocNo = $session->read(self::SESSION_KEY_RELATEDDOCNO);

        $gsaTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();

        $positionArr = $gsaTransactionAccessorObj->selectOutstandingPositions($relatedDocNo);
        $shippingCreditedAmount = $gsaTransactionAccessorObj->selectShippingAmount($relatedDocNo);
                
        trace($positionArr,0,'$positionArr');
        trace($shippingCreditedAmount,0,'$shippingCreditedAmount');

        $content = '';
        $content = $this->display_outstandingPositions($positionArr, $shippingCreditedAmount);

        return $content;
    }
    /**
     * show Search Form
     * @param   void
     * @return  string formated content for Search Form
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-04
     */
    public function exec_showSearchForm() {
        trace('[CMD] '.__METHOD__);

        $session = tx_pttools_sessionStorageAdapter::getInstance();
        $searchArr = array();
        $session->store(self::SESSION_KEY_SEARCH,$searchArr);

        $content = '';
        $content = $this->display_searchForm();

        return $content;
    }



    /**
     * Display OutstandingItemList
     * @param   collection   tx_ptgsaaccounting_erpDocumentCollection of outstanding items
     * @return  string  Formated List of outstanding Items
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-18
     */
    private function display_outstandingItemList(tx_ptgsaaccounting_erpDocumentCollection $erpDocuments) {
        trace('[METHOD] '.__METHOD__);
        // fetch css
        tx_jquery::includeLib();
        $cssFile = $this->conf['cssFile'];
        if ($cssFile) {
            $cssPath = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->tmpl->getFileName($cssFile);
            $linkCss = '<link rel="stylesheet" type="text/css" href="'.$cssPath.'" />'."\n";
        }
        trace ($linkCss.':'.$cssPath,0,'$linkCss');
        $GLOBALS['TSFE']->additionalHeaderData['pt_gsaaccounting_outstandingItems'] = 
            $linkCss.
            #'<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/jquery-1.1.3.js"></script>'."\n".
            '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/jquery.tablesorter.pack.js"></script>'."\n".
            '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/outstandingItemsSort.js"></script>'."\n";

         // create Smarty object and assign prefix
        $smarty = new tx_pttools_smartyAdapter($this);
        $smarty->assign('fv_action', $this->pi_getPageLink($GLOBALS['TSFE']->id));
        $smarty->assign('tx_prefix',$this->prefixId); // prefix for name of the input fields
        $smarty->assign('fv_jspath','EXT:pt_gsaaccounting/res/js');        

        // Buttons
        $smarty->assign('bl_back',$this->pi_getLL('bl_back','[bl_back]'));
        $smarty->assign('bl_backSearch',$this->pi_getLL('bl_backSearch','[bl_backSearch]'));
        $smarty->assign('bl_book',$this->pi_getLL('bl_book','[bl_book]'));
        $smarty->assign('bl_restok',$this->pi_getLL('bl_restok','[bl_restok]'));
        $smarty->assign('bl_selectAllPayment',$this->pi_getLL('bl_selectAllPayment','[bl_selectAllPayment]'));
        $smarty->assign('bl_selectAllCredit',$this->pi_getLL('bl_selectAllCredit','[bl_selectAllCredit]'));
        $smarty->assign('bl_selectAllCancel',$this->pi_getLL('bl_selectAllCancel','[bl_selectAllCancel]'));
        $smarty->assign('bl_deselectAll',$this->pi_getLL('bl_deselectAll','[bl_deselectAll]'));
        $smarty->assign('bl_position',$this->pi_getLL('bl_position','[bl_position]'));
        
        // Labels
        $smarty->assign('fl_name',$this->pi_getLL('fl_name','[fl_name]'));
        $smarty->assign('fl_gsacustomerNo',$this->pi_getLL('fl_gsacustomerNo','[fl_gsacustomerNo]'));
        $smarty->assign('fl_date',$this->pi_getLL('fl_date','[fl_date]'));
        $smarty->assign('fl_relatedDocNo',$this->pi_getLL('fl_relatedDocNo','[fl_relatedDocNo]'));
        $smarty->assign('fl_amountGross',$this->pi_getLL('fl_amountGross','[fl_amountGross]'));
        $smarty->assign('fl_payed',$this->pi_getLL('fl_payed','[fl_payed]'));
        $smarty->assign('fl_credit',$this->pi_getLL('fl_credit','[fl_credit]'));
        $smarty->assign('fl_discount',$this->pi_getLL('fl_discount','[fl_discount]'));
        $smarty->assign('fl_payment',$this->pi_getLL('fl_payment','[fl_payment]'));
        $smarty->assign('fl_doPayment',$this->pi_getLL('fl_doPayment','[fl_doPayment]'));
        $smarty->assign('fl_doCredit',$this->pi_getLL('fl_doCredit','[fl_doCredit]'));
        $smarty->assign('fl_doCancellation',$this->pi_getLL('fl_doCancellation','[fl_doCancellation]'));
        $smarty->assign('fl_doCancel',$this->pi_getLL('fl_doCancel','[fl_doCancel]'));
        $smarty->assign('fl_restok',$this->pi_getLL('fl_restok','[fl_restok]'));
        $smarty->assign('fl_paymentMethod',$this->pi_getLL('fl_paymentMethod','[fl_paymentMethod]'));
        $smarty->assign('fl_note1',$this->pi_getLL('fl_note1','[fl_note1]'));
        $smarty->assign('fl_bookDate',$this->pi_getLL('fl_bookDate','[fl_bookDate]'));
        $smarty->assign('fl_bookPrompt',$this->pi_getLL('fl_bookPrompt','[fl_bookPrompt]'));
        $smarty->assign('currencyCode',$this->shopConfig['currencyCode']);
        $smarty->assign('cond_payment_allowed',$this->allowBookPayment == true ? true : false);
        
        
        // Values
        $smarty->assign('fv_bookDate',date('d.m.Y'));
        foreach ($erpDocuments as $erpDocument) {
        	trace($erpDocument->isDue(),0,'$erpDocument->isDue()');
        	$docId = $erpDocument->get_docId(); 
        	$firstname = $erpDocument->get_customerObj()->get_firstname();
            trace ('after firstname');
        	$lastname = $erpDocument->get_customerObj()->get_lastname();
            $itemDisplayArr[$docId]['name'] =  
                $firstname != '' ? $firstname . ' '. $lastname : $lastname;
            $itemDisplayArr[$docId]['name'] =  
                $itemDisplayArr[$docId]['name'] != '' ? tx_pttools_div::htmlOutput($itemDisplayArr[$docId]['name']) : '&nbsp;';  
            $itemDisplayArr[$docId]['gsacustomerNo'] =  tx_pttools_div::htmlOutput($erpDocument->get_customerObj()->get_gsa_kundnr());  
            $itemDisplayArr[$docId]['date'] = 
                $erpDocument->get_date() != '' ? $erpDocument->get_date() : '&nbsp;';

            if ($this->conf['adminInvoicePage']) {
                $paramArr = array($this->conf['adminInvoiceNumberParamname'] => tx_pttools_div::htmlOutput($erpDocument->get_relatedDocNo()),
                                  $this->conf['adminInvoiceSendButton'] => "yes");
                trace($paramArr,0,'$paramArr');
                $itemDisplayArr[$docId]['relatedDocNo'] = 
                    $this->pi_linkToPage($erpDocument->get_relatedDocNo(), $this->conf['adminInvoicePage'],'', $paramArr);
            } else { 
                $itemDisplayArr[$docId]['relatedDocNo'] = tx_pttools_div::htmlOutput($erpDocument->get_relatedDocNo());
            }
            $itemDisplayArr[$docId]['idRelatedDocNo'] = tx_pttools_div::htmlOutput($erpDocument->get_relatedDocNo());
        	 
            // set payment Method 
            switch ($erpDocument->get_paymentMethod()) {
                case self::PM_INVOICE:                
                    $ll_pmname = 'pm_invoice';
                    break;
                case self::PM_DEBIT:                
                    $ll_pmname = 'pm_debit';
                	break;
                case self::PM_CCARD:                
                    $ll_pmname = 'pm_creditcard';
                    break;
                // should not come here
                default:                
                    $ll_pmname = 'pm_unknown';
                    break;
            }
            $itemDisplayArr[$docId]['paymentMethod'] = tx_pttools_div::htmlOutput($this->pi_getLL($ll_pmname,'['.$ll_pmname.']'));
            
            // get class for row
            if ($erpDocument->get_ok() == true) {
                $itemDisplayArr[$docId]['classTr'] = 'class="payed"';
            } else { 
                $itemDisplayArr[$docId]['classTr'] = '';
            } 
                
            $itemDisplayArr[$docId]['amountGross'] = 
                tx_pttools_finance::getFormattedPriceString($erpDocument->get_amountGross());
            $itemDisplayArr[$docId]['credit'] = 
                tx_pttools_finance::getFormattedPriceString($erpDocument->get_credit() != NULL ? $erpDocument->get_credit() :0.00) ;
            $itemDisplayArr[$docId]['discount'] = 
                tx_pttools_finance::getFormattedPriceString($erpDocument->get_discount() != NULL ? $erpDocument->get_discount() : 0.00);
            $itemDisplayArr[$docId]['payment'] = 
                tx_pttools_finance::getFormattedPriceString($erpDocument->get_payment()  != NULL ? $erpDocument->get_payment() : 0.00);
            $itemDisplayArr[$docId]['restPaymentVal'] = 
                $erpDocument->get_amountGross()-$erpDocument->get_payment() - $erpDocument->get_credit()- $erpDocument->get_discount();
            $itemDisplayArr[$docId]['restPayment'] = 
                tx_pttools_finance::getFormattedPriceString($itemDisplayArr[$docId]['restPaymentVal']);
            $itemDisplayArr[$docId]['payment_allowed'] = $this->allowBookPayment == true ? true : false;
            $itemDisplayArr[$docId]['notpayed'] = $itemDisplayArr[$docId]['restPaymentVal'] > 0 ? true: false;

        }
            
        trace($itemListArr,0,'$itemListArr');
        trace($itemDisplayArr,0,'$itemDisplayArr');
        $smarty->assign('itemDisplayArr',$itemDisplayArr); // prefix for name of the input fields
        
        $smartyFile=$this->conf['templateFileOutstandingItemList'];
        #trace($smartyFile,'0','smartyFile');        
        $filePath = $smarty->getTplResFromTsRes($smartyFile);

        trace($filePath, 0, 'Smarty template resource filePath');
        return $smarty->fetch($filePath);
    }


    /**
     * Display outstanding Positions for one item
     * @param   array   Array of  item positions
     * @param   double  Amount of alreday credited shippingCost 
     * @return  string  Formated List of item positions
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-18
     */
    private function display_outstandingPositions($posArr, $amountShippingCredited) {
        trace('[METHOD] '.__METHOD__);
        // fetch css
        $cssFile = $this->conf['cssFile'];
        if ($cssFile) {
            $cssPath = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->tmpl->getFileName($cssFile);
            $linkCss = '<link rel="stylesheet" type="text/css" href="'.$cssPath.'" />'."\n";
        }
        trace ($linkCss.':'.$cssPath,0,'$linkCss');
        $GLOBALS['TSFE']->additionalHeaderData['pt_gsaaccounting_outstandingPositions'] = 
            $linkCss.
            '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/jquery-1.1.3.js"></script>'."\n".
            '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/jquery.tablesorter.pack.js"></script>'."\n".
            '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/outstandingItemsSort.js"></script>'."\n";

         // create Smarty object and assign prefix
        $smarty = new tx_pttools_smartyAdapter($this);
        $smarty->assign('fv_action', $this->pi_getPageLink($GLOBALS['TSFE']->id));
        $smarty->assign('tx_prefix',$this->prefixId); // prefix for name of the input fields
        $smarty->assign('fv_jspath','EXT:pt_gsaaccounting/res/js');        

        // Buttons
        $smarty->assign('bl_back',$this->pi_getLL('bl_back','[bl_back]'));
        $smarty->assign('bl_creditPositions',$this->pi_getLL('bl_creditPositions','[bl_creditPositions]'));
        
        // Labels
        $smarty->assign('fl_articleName',$this->pi_getLL('fl_articleName','[fl_articleName]'));
        $smarty->assign('fl_date',$this->pi_getLL('fl_date','[fl_date]'));
        $smarty->assign('fl_relatedDocNo',$this->pi_getLL('fl_relatedDocNo','[fl_relatedDocNo]'));
        $smarty->assign('fl_unitPrice',$this->pi_getLL('fl_unitPrice','[fl_unitPrice]'));
        $smarty->assign('fl_totalPrice',$this->pi_getLL('fl_totalPrice','[fl_totalPrice]'));
        $smarty->assign('fl_quantity',$this->pi_getLL('fl_quantity','[fl_quantity]'));
        $smarty->assign('fl_noOutstanding',$this->pi_getLL('fl_noOutstanding','[fl_noOutstanding]'));
        $smarty->assign('fl_noToCredit',$this->pi_getLL('fl_noToCredit','[fl_noToCredit]'));
        $smarty->assign('fl_noCancellation',$this->pi_getLL('fl_noCancellation','[fl_noCancellation]'));
        $smarty->assign('fl_shipping',$this->pi_getLL('fl_shipping','[fl_shipping]'));
        $smarty->assign('fl_bookPrompt',$this->pi_getLL('fl_bookPrompt','[fl_bookPrompt]'));
        $smarty->assign('msg_errorQuantity',$this->pi_getLL('msg_errorQuantity','[msg_errorQuantity]'));
        $smarty->assign('currencyCode',$this->shopConfig['currencyCode']);
        $smarty->assign('fl_doCredit',$this->pi_getLL('fl_doCredit','[fl_doCredit]'));
        $smarty->assign('fl_doCancellation',$this->pi_getLL('fl_doCancellation','[fl_doCancellation]'));
        $smarty->assign('fl_doCancel',$this->pi_getLL('fl_doCancel','[fl_doCancel]'));
        
        
        $i=0;
        foreach ($posArr as $pos) {
            if ($pos['amountCredit'] > 0 || $pos['noCancellation']>0) {
                $quantityOutstanding = intval($pos['quantity'] - $pos['noCredit'] - $pos['noCancellation']);
            } else {
                $quantityOutstanding = intval($pos['quantity']);
            }               
            // Only if there are positions to credit
            if ($quantityOutstanding > 0) {
                $posId = $pos['posNo']; 
                $posDisplayArr[$posId]['articleName'] = $pos['articleName'];  
                $posDisplayArr[$posId]['articleUid'] = $pos['articleUid'];  
                $posDisplayArr[$posId]['posNo'] = $pos['posNo'];  
                $posDisplayArr[$posId]['invoiceUid'] = $pos['invoiceUid'];  
                $posDisplayArr[$posId]['invoiceDocNo'] = $pos['invoiceDocNo'];  
                $posDisplayArr[$posId]['quantity'] = intval($pos['quantity']);
                $posDisplayArr[$posId]['noCredit'] = intval($pos['noCredit']);
                $posDisplayArr[$posId]['noOutstanding'] = intval($quantityOutstanding);
                $posDisplayArr[$posId]['amountOutstanding'] = 
                    bcmul(intval($quantityOutstanding),$pos['unitPrice'],2);
                $posDisplayArr[$posId]['unitPrice'] = $pos['unitPrice'];
                $posDisplayArr[$posId]['totalPrice'] = tx_pttools_finance::getFormattedPriceString($pos['totalPrice']);
            }
        }
        trace($posArr,0,'$posArr');
        trace($posDisplayArr,0,'$posDisplayArr');
        $smarty->assign('posDisplayArr',$posDisplayArr);
        
        if ($pos) {
            $amountShipping = bcsub($pos['amountShipping'], $amountShippingCredited,self::PRECISSION);
            trace($amountShippingCredited,0,'$amountShippingCredited');
            trace($amountShipping,0,'$amountShipping in pos');
            $smarty->assign('fv_amountShipping',$amountShipping);
            if ($pos['isGross'] == true) {
                $taxrate  = tx_ptgsashop_lib::getTaxRate($this->shopConfig['dispatchTaxCode'], $pos['date']);
                $amountShipping = tx_pttools_finance::getGrossPriceFromNet($amountShipping, $taxrate);
                
            } else {
                $amountShipping = $pos['amountShipping'];
            } 
        }
        
        $amountShipping = tx_pttools_finance::getFormattedPriceString($amountShipping);
        trace($amountShipping,0,'$amountShipping');
        
        // Values
        if($amountShipping > 0) {
            $smarty->assign('fv_amountShipping',$amountShipping);
            $smarty->assign('cond_shipping',true);
        } else {
            $smarty->assign('cond_shipping',false);
        }        
        
        $smartyFile=$this->conf['templateFileOutstandingPositions'];
        $filePath = $smarty->getTplResFromTsRes($smartyFile);

        trace($filePath, 0, 'Smarty template resource filePath');
        return $smarty->fetch($filePath);
    }


    /**
     * Display SearchForm
     * @param   void   
     * @return  string  Formated List of SearchForm
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-04
     */
    private function display_searchForm() {
        trace('[METHOD] '.__METHOD__);

        // fetch css
        $cssFile = $this->conf['cssFile'];
        trace ($cssFile,0,'$cssFile');
        if ($cssFile) {
            $cssPath = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->tmpl->getFileName($cssFile);
            $GLOBALS['TSFE']->additionalHeaderData['tx_ptgsaaccounting_smarty_css'] = '<link rel="stylesheet" type="text/css" href="'.$cssPath.'" />';
        }

         // create Smarty object and assign prefix
        $smarty = new tx_pttools_smartyAdapter($this);
        $smarty->assign('fv_action', $this->pi_getPageLink($GLOBALS['TSFE']->id));
        $smarty->assign('tx_prefix',$this->prefixId); // prefix for name of the input fields
        
        // Buttons
        $smarty->assign('bl_back',$this->pi_getLL('bl_back','[bl_back]'));
        $smarty->assign('bl_search',$this->pi_getLL('bl_search','[bl_search]'));
        $smarty->assign('bl_selectAll',$this->pi_getLL('bl_selectAll','[bl_selectAll]'));
        
        // Labels
        $smarty->assign('fl_searchText',$this->pi_getLL('fl_searchText','[fl_searchText]'));
        $smarty->assign('fl_searchFields',$this->pi_getLL('fl_searchFields','[fl_searchFields]'));
        $smarty->assign('fl_searchOperator',$this->pi_getLL('fl_searchOperator','[fl_searchOperator]'));
        $smarty->assign('fl_searchExact',$this->pi_getLL('fl_searchExact','[fl_searchExact]'));
        $smarty->assign('fl_searchAll',$this->pi_getLL('fl_searchAll','[fl_searchAll]'));
        
        $smarty->assign('searchFieldOptions', $this->getSearchFields());
        $smarty->assign('searchFieldSelected', $this->getSearchFields(2));
        $smarty->assign('searchOperatorOptions', $this->getSearchOperators());

        $smartyFile=$this->conf['templateFileSearchForm'];
        #trace($smartyFile,'0','smartyFile');        
        $filePath = $smarty->getTplResFromTsRes($smartyFile);

        trace($filePath, 0, 'Smarty template resource filePath');
        return $smarty->fetch($filePath);
    }


    /**
     * get Environment for Payment
     * @param   void
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-31
     */
    private function getEnvironment() {
        trace('[METHOD] '.__METHOD__);
        $session = tx_pttools_sessionStorageAdapter::getInstance();
        trace ($session,0,'session');
        foreach ($this->piVars as $key => $val ) {
            $fields = explode('_', $key); 
            #trace ($fields);
            if ($fields[0] == 'book') {
                $this->command = $fields[0];
            } else if ($fields[0] == 'credit') {
                $this->command = $fields[0];
            } else if ($fields[0] == 'showPosition') {
                $this->command = $fields[0];
                $session->store(self::SESSION_KEY_RELATEDDOCNO,$fields[1]);
            } else if ($fields[0] == 'back' ) {
                $this->command = $fields[0] . '_' . $fields[1];
            } else if ($fields[0] == 'select' ) {
                $this->command = $fields[0] . '_' . $fields[1];
            } else if ($fields[0] == 'search' ) {
                $this->command = $fields[0];
            }
        }
        trace ($this->command, 0, '$this->command');

    }

    /**
     * get SearchFields
     * @param   integer type 1 = options 2 = selected 
     * @return  array  possible Searchfields as array 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-04
     */
    private function getSearchFields($type=1) {
        $fieldsArr = array();
        if ($type == 1) {
            $fieldsArr['sf_all'] = $this->pi_getLL('fl_all','[fl_all]');        
            $fieldsArr['sf_firstname'] = $this->pi_getLL('fl_firstname','[fl_firstname]');        
            $fieldsArr['sf_lastname'] = $this->pi_getLL('fl_lastname','[fl_lastname]');        
            $fieldsArr['sf_gsacustomerId'] = $this->pi_getLL('fl_gsacustomerId','[fl_gsacustomerId]');        
            $fieldsArr['sf_date'] = $this->pi_getLL('fl_date','[fl_date]');        
            $fieldsArr['sf_relatedDocNo'] = $this->pi_getLL('fl_relatedDocNo','[fl_relatedDocNo]');        
            $fieldsArr['sf_amountGross'] = $this->pi_getLL('fl_amountGross','[fl_amountGross]');        
            $fieldsArr['sf_paymentMethod'] = $this->pi_getLL('fl_paymentMethod','[fl_paymentMethod]');        
        } else {
            $fieldsArr[] = 'sf_all';
        }
        trace($fieldsArr,0,'$fieldsArr');
        return $fieldsArr;
    }

    /**
     * get Search Operators
     * @param   integer type 1 = options 2 = selected 
     * @return  array  possible SearchOperarors as array 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-04
     */
    private function getSearchOperators($type=1) {
        $fieldsArr = array();
        $fieldsArr['so_and'] = $this->pi_getLL('so_and','[so_and]');        
        $fieldsArr['so_or'] = $this->pi_getLL('so_or','[so_or]');        
        #$fieldsArr['so_not'] = $this->pi_getLL('so_not','[so_not]');        
        trace($fieldsArr,0,'$fieldsArr');
        return $fieldsArr;
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/pi1/class.tx_ptgsaaccounting_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/pi1/class.tx_ptgsaaccounting_pi1.php']);
}

?>
