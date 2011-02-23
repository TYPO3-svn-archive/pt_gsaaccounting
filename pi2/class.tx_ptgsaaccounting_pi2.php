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

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionHandler.php';
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_erpDocumentCollection.php';  // GSA accounting Document Collection Class


/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsapdfdocs').'res/class.tx_ptgsapdfdocs_documentAccessor.php';  // PDF Document Aceesor for GSA Documents
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // GSA specific FE customer class
require_once t3lib_extMgm::extPath("jquery").'class.tx_jquery.php'; //jquery Libraray

/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]


/**
 * Plugin 'GSA Accounting: Handle credits' for the 'pt_gsaaccounting' extension.
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @package	TYPO3
 * @subpackage	tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_pi2 extends tslib_pibase {

	/**
	 * Constants
	 */
	const PM_CCARD = 'cc';
	const PM_INVOICE = 'bt';
	const PM_DEBIT = 'dd';
	const TT_INVOICE = '04RE';         // Transaction Type  for invoice from GSA
	const TT_CREDIT = '05GU';        // Transaction Type  for credit Note from GSA
	const TT_CANCELLATION = '06ST';   // Transaction Type Cancellation from GSA

	var $prefixId      = 'tx_ptgsaaccounting_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_ptgsaaccounting_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'pt_gsaaccounting';	// The extension key.
	protected $precision = 0;    // integer Precision for gsaacounting set in main method; depends on pgsashop config 'usePricesWithMoreThanTwoDecimals'
	protected $outstandingItemArr = array(); // array Contains the invoices from GSA Auftrag which are not payed

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
		$this->pi_USER_INT_obj=1;   // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		trace($this->piVars,0,'$this->piVars');
		trace($GLOBALS['TSFE']->fe_user->user,0,'fe_user->user');

		if($this->shopConfig['usePricesWithMoreThanTwoDecimals'] == 1) {
			$this->precision = 4;
		} else {
			$this->precision = 2;
		}

		try {
			if ($GLOBALS['TSFE']->loginUser != 1) {
				throw new tx_pttools_exception('User Login expired.', 4);
			}

			if ($this->piVars) {
				//$this->getEnvironment();
				// delete any stored info from session if called from outside
				if ($this->piVars['process_creditBalance']) {
					$content = $this->exec_processCreditBalance();
				} else if ($this->piVars['show_allAccountData']) {
					$content = $this->exec_showMyAccount(false);
				} else if ($this->piVars['show_outstandingItems']) {
					$content = $this->exec_showMyAccount(true);
				}  else if( $this->piVars['pay'] ) {
					$content = $this->payByCreditCard();
				}
			} else {
				$content = $this->exec_showMyAccount(true);
			}
		} catch (tx_pttools_exception $excObj) {
			// if an exception has been catched, handle it and overwrite plugin content with error message
			$excObj->handleException();
			$content = '<i>'.$excObj->__toString().'</i>';
		}
		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Redirect to a payment process.
	 *
	 * @return void
	 * @author Christoph Ehscheidt <ehscheidt@punkt.de>
	 * @since 2010-09-13
	 */
	public function payByCreditCard() {
		 
		$reference = $this->piVars['pay'];
		 
		tx_pttools_assert::isNotEmptyString($reference, array('message'=>'No reference no for the payment retry given.'));
		 
		$orderWrapper = tx_ptgsashop_orderWrapperAccessor::getInstance();

		$data = $orderWrapper->selectOrderWrapperByRelatedDocNo($reference);
		 
		// Secure that only the creator can pay the invoice
		if(empty($data['creatorId']) || $data['creatorId'] != $GLOBALS['TSFE']->fe_user->user['uid']) {
			tx_pttools_assert::isTrue(false, array('message'=>'You are not allowed to pay this invoice.'));
		}
		 
		tx_pttools_assert::isNotEmptyString($data['sumGross'], array('message'=>'No payment found.'));
		 
		$epaymentRequestDataArray = array(
            'merchantReference' => $reference, 
            'amount' => ( $data['sumGross'] > 0 ? $data['sumGross'] : 0), 
            'currencyCode' => (!empty($this->gsaShopConfig['currencyCode']) ? $this->gsaShopConfig['currencyCode'] : 'EUR'), 
            'articleQuantity' => 1, 
            'infotext' => $reference 
		);
		 
		$epaymentRequestDataObj = new tx_pttools_paymentRequestInformation($epaymentRequestDataArray);
		$epaymentRequestDataObj->storeToSession();
		 
		 
		$paymentPid = $this->conf['paymentPid'];
		 
		tx_pttools_assert::isNotEmpty($paymentPid, array('message'=>'No payment pid given. Check constants.'));
		 
		$url = $this->pi_getPageLink(390);
		 
		header('Location: '.t3lib_div::locationHeaderUrl($url));
		exit();
		 
	}

	/**
	 * show myAccount
	 * @param   boolean flag if outstanding Items Only
	 * @param   boolean flag showing Buttos for Outstanding Items resp. all Invoices
	 * @return  string formated content for myAccount for this customer
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2007-11-28
	 */
	public function exec_showMyAccount($outstandingItemsOnly=true, $showButtons=true) {
		trace('[CMD] '.__METHOD__);

		#$GLOBALS['trace']=1;
		$this->gsaTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
		$this->gsaTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
		$this->feCustomer = new tx_ptgsauserreg_feCustomer($GLOBALS['TSFE']->fe_user->user['uid']);
		$this->invoiceDocuments = array();

		if(intval($this->feCustomer->get_gsaMasterAddressId()) > 0) {
			 
			$this->invoiceDocuments = new tx_ptgsaaccounting_erpDocumentCollection($outstandingItemsOnly,$this->feCustomer->get_gsaMasterAddressId());
			#$this->invoiceArr = $this->gsaTransactionAccessorObj->selectInvoicesByCustomer($this->feCustomer->get_gsaMasterAddressId(), '04RE', $outstandingItemsOnly);
			if ($outstandingItemsOnly == true && count($this->invoiceDocuments) == 0) {
				//show all invoices instead of outstanding Items
				return $this->exec_showMyAccount(false, false);
					
			}
				
			$this->overpaymentItemArr = $this->gsaTransactionAccessorObj->selectOverpaymentByCustomer($this->feCustomer->get_gsaMasterAddressId());
				
			trace($this->invoiceArr,0,'$invoiceArr');

			$this->creditBalance = $this->gsaTransactionHandlerObj->getCreditBalance($this->feCustomer->get_gsaMasterAddressId(),0);
			#$this->creditBalance = $this->gsaTransactionAccessorObj->selectCreditMemoCustomerAmount($this->feCustomer->get_gsaMasterAddressId(),true);
			$this->lastPaymentDateInvoice = $this->gsaTransactionAccessorObj->selectLastPaymentDate(self::PM_INVOICE);
			$this->lastPaymentDateDebit = tx_ptgsaaccounting_dtabuchRelAccessor::getInstance()->selectLastTransferDate();
			$content = $this->display_myAccount($outstandingItemsOnly, $showButtons);
		} else {
			$content = $this->pi_getLL('err_novalid_customer_no','[err_novalid_customer_no]');
		}

		return $content;
	}

	/**
	 * exec_processCreditBalance
	 * @param   void
	 * @return  string formated content for myAccount for this customer
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2007-11-29
	 */
	public function exec_processCreditBalance() {
		trace('[CMD] '.__METHOD__);

		$this->gsaTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
		$this->gsaTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
		$this->feCustomer = new tx_ptgsauserreg_feCustomer($GLOBALS['TSFE']->fe_user->user['uid']);

		$this->creditBalance = $this->gsaTransactionAccessorObj->selectCreditMemoCustomerAmount($this->feCustomer->get_gsaMasterAddressId(),true);

		if ($this->creditBalance > 0) {
			// TODO user can specify processing of outstanding items, may be small amounts first or big amounts first, default latest first
			#$this->outstandingItemArr = $this->gsaTransactionAccessorObj->selectInvoicesByCustomer($this->feCustomer->get_gsaMasterAddressId(), '04RE', true, 'AUFNR');
			$this->invoiceDocuments = new tx_ptgsaaccounting_erpDocumentCollection(true,$this->feCustomer->get_gsaMasterAddressId());
				
			trace($this->erpDocuments,0,'erpDocuments');

			// get the outstanding items
			foreach ($this->invoiceDocuments as $invoice) {
				if ($invoice->get_relatedDocNo() && $this->gsaTransactionHandlerObj->isOutstanding($invoice->get_paymentMethod(), $invoice->get_relatedDocNo())) {
					$outstandingAmount = $this->gsaTransactionHandlerObj->getOutstandingAmountInvoice($invoice);
					trace($outstandingAmount,0,'$outstandingAmount');
					trace($this->creditBalance,0,'$this->creditBalance');
					if (bcsub($this->creditBalance, $invoice->get_outstandingAmount(),4) > 0) {
						$amount = $outstandingAmount;
					} else {
						$amount = $this->creditBalance;
					}
					// do processing with amount
					trace($invoice->get_relatedDocNo().':'.$amount,0,'process_creditBalance');
					$this->gsaTransactionHandlerObj->bookCreditBalance($invoice->get_relatedDocNo(), $this->feCustomer->get_gsaMasterAddressId(), $amount);
					$this->creditBalance = bcsub($this->creditBalance, $amount,4);
					// Stop if there is no Credit Balance
					if ($this->creditBalance <= 0) {
						break;
					}
				}
			}
		}

		$this->invoiceDocuments = new tx_ptgsaaccounting_erpDocumentCollection(true,$this->feCustomer->get_gsaMasterAddressId());
		$content = $this->exec_showMyAccount(true);

		return $content;
	}




	/**
	 * Display myAccount
	 * @param   boolean flag if outstanding Items Only
	 * @param   boolean flag showing Buttos for Outstanding Items resp. all Invoices
	 * @return  string  Formated content of myAccount
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2007-09-18
	 */
	private function display_myAccount($outstandingItemsOnly=true, $showButtons=true) {
		trace('[METHOD] '.__METHOD__);
		tx_jquery::includeLib();
		$cssFile = $this->conf['cssFile'];
		if ($cssFile) {
			$cssPath = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->tmpl->getFileName($cssFile);
			$linkCss =
            	'<link rel="stylesheet" type="text/css" href="'.$cssPath.'" />'."\n".
            	'<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/jquery.tablesorter.pack.js"></script>'."\n".
            	'<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/myAccount.js"></script>'."\n";
		}
		trace ($linkCss.':'.$cssPath,0,'$linkCss');
		$GLOBALS['TSFE']->additionalHeaderData['pt_gsaaccounting_myAccount'] = $linkCss;
		#echo 'conf:'. var_dump($this->conf); echo '<br>';

		// create Smarty object and assign prefix
		$smarty = new tx_pttools_smartyAdapter($this->extKey);
		$smarty->assign('fv_action', $this->pi_getPageLink($GLOBALS['TSFE']->id));
		$smarty->assign('tx_prefix',$this->prefixId); // prefix for name of the input fields
		$smarty->assign('fv_jspath','EXT:pt_gsaaccounting/res/js');

		// Buttons
		$smarty->assign('cond_showButtons',$showButtons);
		$smarty->assign('bl_back',$this->pi_getLL('bl_back','[bl_back]'));
		$smarty->assign('bl_book',$this->pi_getLL('bl_book','[bl_book]'));
		$smarty->assign('bl_outstandingItems',$this->pi_getLL('bl_outstandingItems','[bl_outstandingItems]'));
		$smarty->assign('bl_allAccountData',$this->pi_getLL('bl_allAccountData','[bl_allAccountData]'));

		// Labels
		$smarty->assign('fl_name',$this->pi_getLL('fl_name','[fl_name]'));
		$smarty->assign('fl_invoiceDate',$this->pi_getLL('fl_invoiceDate','[fl_invoiceDate]'));
		$smarty->assign('fl_overpaymentDate',$this->pi_getLL('fl_overpaymentDate','[fl_overpaymentDate]'));
		$smarty->assign('fl_overpayment',$this->pi_getLL('fl_overpayment','[fl_overpayment]'));
		$smarty->assign('fl_creditBalanceOverpayment',$this->pi_getLL('fl_creditBalanceOverpayment','[fl_creditBalanceOverpayment]'));
		$smarty->assign('fl_relatedDocNo',$this->pi_getLL('fl_relatedDocNo','[fl_relatedDocNo]'));
		$smarty->assign('fl_amountGross',$this->pi_getLL('fl_amountGross','[fl_amountGross]'));
		$smarty->assign('fl_credit',$this->pi_getLL('fl_credit','[fl_credit]'));
		$smarty->assign('fl_discount',$this->pi_getLL('fl_discount','[fl_discount]'));
		$smarty->assign('fl_paymentRest',$this->pi_getLL('fl_paymentRest','[fl_paymentRest]'));
		$smarty->assign('fl_paymentAmount',$this->pi_getLL('fl_paymentAmount','[fl_paymentAmount]'));
		$smarty->assign('fl_paymentMethod',$this->pi_getLL('fl_paymentMethod','[fl_paymentMethod]'));
		$smarty->assign('fl_note1',$this->pi_getLL('fl_note1','[fl_note1]'));
		$smarty->assign('fl_bookDate',$this->pi_getLL('fl_bookDate','[fl_bookDate]'));
		$smarty->assign('fl_fromCredit',$this->pi_getLL('fl_fromCredit','[fl_fromCredit]'));
		$smarty->assign('fl_bookPrompt',$this->pi_getLL('fl_bookPrompt','[fl_bookPrompt]'));
		$smarty->assign('fl_creditBalance',$this->pi_getLL('fl_creditBalance','[fl_creditBalance]'));
		$smarty->assign('fl_accountBalance',$this->pi_getLL('fl_accountBalance','[fl_accountBalance]'));
		$smarty->assign('fl_note_invoice1',$this->pi_getLL('fl_note_invoice1','[fl_note_invoice1]'));
		$smarty->assign('fl_note_invoice2',$this->pi_getLL('fl_note_invoice2','[fl_note_invoice2]'));
		$smarty->assign('fl_note_debit1',$this->pi_getLL('fl_note_debit1','[fl_note_debit1]'));
		$smarty->assign('fl_note_debit2',$this->pi_getLL('fl_note_debit2','[fl_note_debit2]'));

		// Values
		$smarty->assign('fv_currencyCode',$this->shopConfig['currencyCode']);
		$smarty->assign('fv_bookDate',date('d.m.Y'));
		$smarty->assign('fv_creditBalance',tx_pttools_finance::getFormattedPriceString($this->creditBalance,$this->shopConfig['currencyCode']));
		$this->accountBalance = 0;
		if ($outstandingItemsOnly == true) {
			$smarty->assign('cond_outstandingItems',true);
		}

		// Outstanding overpayment from Customer
		$i=0;
		foreach ($this->overpaymentItemArr as $itemArr) {
			$docId = tx_pttools_div::htmlOutput($itemArr['docId']);
			list($invoiceDocNo,$paymentAmount) = explode('#',$itemArr['invoiceDocNo']);
			$overpaymentDisplayArr[$docId]['invoiceDocNo']  = tx_pttools_div::htmlOutput($invoiceDocNo);
			$overpaymentDisplayArr[$docId]['overpaymentDate'] =
			$itemArr['date'] != '' ? tx_pttools_div::htmlOutput($itemArr['date']) : '&nbsp;';
			$overpaymentDisplayArr[$docId]['invoiceDate'] =
			$itemArr['invoiceDate'] != '' ? $itemArr['invoiceDate'] : '&nbsp;';
			$overpaymentDisplayArr[$docId]['paymentAmount']  = tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($paymentAmount,$this->shopConfig['currencyCode']));
			$overpaymentDisplayArr[$docId]['overpayment']  = tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($itemArr['overpayment'],$this->shopConfig['currencyCode']));
			$overpaymentDisplayArr[$docId]['creditBalance']  = tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString(bcsub($itemArr['overpayment'],$itemArr['creditPayment'],2),$this->shopConfig['currencyCode']));
			$i++;
		}

		// assign it to Smarty
		if ($i>0) {
			trace ('OVERPAYMENT');
			$smarty->assign('overpaymentDisplayArr',$overpaymentDisplayArr); // assign it to smarty Template
			$smarty->assign('cond_overpayment',true);
		}

		// Array of Invoices for Customer
		$i=0;
		foreach ($this->invoiceDocuments as $invoice) {
			if ($invoice->get_relatedDocNo()) {
				$continuedDocumentList = array();
				$paymentList = array();
				$docId = tx_pttools_div::htmlOutput($invoice->get_docId());
				$firstname =  $invoice->get_customerObj()->get_firstname();
				$lastname =  $invoice->get_customerObj()->get_lastname();
				$itemDisplayArr[$docId]['name'] =
				$firstname != '' ? tx_pttools_div::htmlOutput($firstname) . ' '. tx_pttools_div::htmlOutput($lastname) : tx_pttools_div::htmlOutput($lastname);
				$itemDisplayArr[$docId]['name'] =
				$itemDisplayArr[$docId]['name'] != '' ? $itemDisplayArr[$docId]['name'] : '&nbsp;';

				$itemDisplayArr[$docId]['date'] =
				$invoice->get_date() != '' ? tx_pttools_div::htmlOutput($invoice->get_date()) : '&nbsp;';

				$itemDisplayArr[$docId]['relatedDocNo'] = tx_pttools_div::htmlOutput($invoice->get_relatedDocNo());
				//TODO write constructor for pdfdoc with relatedDocNo
				if (!$invoice->get_hasCancellation()) {
					$pdf = $this->getRelatedDocNoLink($invoice->get_relatedDocNo());
				} else {
					$pdf = array();
				}

				$itemDisplayArr[$docId]['relatedDocNoLink'] = $pdf['url'];
				if ($pdf['signed'] == true) {
					$itemDisplayArr[$docId]['signedImg'] = $GLOBALS['TSFE']->tmpl->setup['config.']['pt_gsapdfdocs.']['signedImg'];
				} else {
					if ($pdf['url']) {
						$itemDisplayArr[$docId]['signedImg'] = $GLOBALS['TSFE']->tmpl->setup['config.']['pt_gsapdfdocs.']['notSignedImg'];
					}
				}
				$itemDisplayArr[$docId]['idRelatedDocNo'] = tx_pttools_div::htmlOutput($invoice->get_relatedDocNo());

				// set payment Method
				switch ($invoice->get_paymentMethod()) {
					case self::PM_INVOICE:
						$ll_pmname = 'pm_invoice';
						$cond_pmInvoice = true;
						break;
					case self::PM_DEBIT:
						$cond_pmDebit = true;
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
				 
				$itemDisplayArr[$docId]['paymentStatus'] = '';
				if($invoice->get_paymentMethod() != self::PM_CCARD) {
					 
					if($invoice->get_ok()) {
						$itemDisplayArr[$docId]['paymentStatus'] = $this->pi_getLL('transaction_ok','OK');
					} else {
						$itemDisplayArr[$docId]['paymentStatus'] = $this->pi_getLL('not_payed','NOT PAYED');
					}
					 
				} else {
					 
					if($invoice->get_ok() || $invoice->checkEPaymentSuccess()) {
						$itemDisplayArr[$docId]['paymentStatus'] = $this->pi_getLL('transaction_ok','OK');
					} else {
						$params[$this->prefixId]['pay'] = $invoice->get_relatedDocNo();
						$itemDisplayArr[$docId]['paymentStatus'] = $this->pi_linkToPage($this->pi_getLL('pay_now','Pay Now'), $GLOBALS['TSFE']->id, '_new', $params);
					}
				}
				 
				 
				if ($invoice->get_ok() == true) {
					$itemDisplayArr[$docId]['classTr'] = 'class="payed"';
				} else {
					$itemDisplayArr[$docId]['classTr'] = '';
				}
				$itemDisplayArr[$docId]['amountGross'] =
				tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($invoice->get_amountGross(),$this->shopConfig['currencyCode']));
				$itemDisplayArr[$docId]['credit'] =
				tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($invoice->get_credit() != NULL ? $invoice->get_credit() :0.00, $this->shopConfig['currencyCode'])) ;
				$itemDisplayArr[$docId]['payment'] =
				tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($invoice->get_payment()  != NULL ? $invoice->get_payment() : 0.00,$this->shopConfig['currencyCode']));
				$itemDisplayArr[$docId]['restPaymentAmount'] =
				bcsub($invoice->get_amountGross(),bcadd($invoice->get_payment(),bcadd($invoice->get_credit(), $invoice->get_discount(),$this->precision),$this->precision),$this->precision);
				$itemDisplayArr[$docId]['restPayment'] =
				tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($itemDisplayArr[$docId]['restPaymentAmount'],$this->shopConfig['currencyCode']));

				if ($itemDisplayArr[$docId]['restPaymentAmount'] > 0 && $itemDisplayArr[$docId]['restPaymentAmount'] != $invoice->get_amountGross() ) {
					$itemDisplayArr[$docId]['cond_restPayment'] = true;
					 
				}
				$itemDisplayArr[$docId]['notpayed'] = $itemDisplayArr[$docId]['restPaymentAmount'] > 0 ? true: false;
				//Look fot discount
				if ($invoice->get_discount() > 0 ) {
					$itemDisplayArr[$docId]['discount'] =
					tx_pttools_div::htmlOutput('- ' .tx_pttools_finance::getFormattedPriceString($invoice->get_discount(),$this->shopConfig['currencyCode']));
					$itemDisplayArr[$docId]['cond_discount'] = true;
				}
				if ($invoice->get_ok() == false) {
					$this->accountBalance = bcadd($this->accountBalance,$this->gsaTransactionHandlerObj->getOutstandingAmountInvoice($invoice), $this->precision);
				}

				// Get continued Documents For this Invoice
				#$GLOBALS['trace'] = 1;
				$continuedDocumentList = $this->gsaTransactionAccessorObj->selectContinuedDocuments($invoice->get_relatedDocNo());
				trace($continuedDocumentList,0,'$continuedDocumentList');
				foreach ($continuedDocumentList as $continuedArr) {
					$itemDisplayArr[$docId]['continuedCond'] = true;
					$continuedId = $continuedArr['number'];
						
					if ($continuedArr['documentType'] == self::TT_CREDIT) {
						$itemDisplayArr[$docId]['continuedArr'][$continuedId]['text'] = tx_pttools_div::htmlOutput($this->pi_getLL('fl_transCredit','[fl_transCredit]'));
					} else if ($continuedArr['documentType'] == self::TT_CANCELLATION) {
						$itemDisplayArr[$docId]['continuedArr'][$continuedId]['text'] = tx_pttools_div::htmlOutput($this->pi_getLL('fl_transCancellation','[fl_transCancellation]'));
					} else if ($continuedArr['documentType'] == self::TT_INVOICE) {
						$itemDisplayArr[$docId]['continuedArr'][$continuedId]['text'] = tx_pttools_div::htmlOutput($this->pi_getLL('fl_transInvoice','[fl_transInvoice]'));
					} else {
						trace($continuedArr['documentType'],0,'$continuedArr[documentType]');
						$itemDisplayArr[$docId]['continuedArr'][$continuedId]['text'] = 'Do not know';
					}
						
					$itemDisplayArr[$docId]['continuedArr'][$continuedId]['continuedId'] = tx_pttools_div::htmlOutput($continuedId);
					$itemDisplayArr[$docId]['continuedArr'][$continuedId]['amount'] = tx_pttools_div::htmlOutput('- '.tx_pttools_finance::getFormattedPriceString($continuedArr['amountGross'],$this->shopConfig['currencyCode']));
					$itemDisplayArr[$docId]['continuedArr'][$continuedId]['date'] =
					$continuedArr['date'] != '' ? tx_pttools_div::htmlOutput($continuedArr['date']) : '&nbsp;';
				}
				 
				// Get Payments for this Invoice
				$paymentList = $this->gsaTransactionAccessorObj->selectPaymentByInvoice($docId);
				trace($paymentList,0,'paymentList');
				$paymentArr = array();
				#$GLOBALS['trace'] = 0;
				foreach ($paymentList as $paymentArr) {
					$itemDisplayArr[$docId]['paymentCond'] = true;
					$itemDisplayArr[$docId]['paymentArr'][$paymentArr['paymentId']]['paymentText'] = tx_pttools_div::htmlOutput($this->pi_getLL('fl_paymentText','[fl_paymentText]'));
					if ($paymentArr['creditAmount'] >0) {
						$itemDisplayArr[$docId]['paymentArr'][$paymentArr['paymentId']]['paymentText'] .= tx_pttools_div::htmlOutput(' '.$this->pi_getLL('fl_fromCredit','[fl_fromCredit]'));

					}
					$itemDisplayArr[$docId]['paymentArr'][$paymentArr['paymentId']]['paymentId'] = tx_pttools_div::htmlOutput($paymentArr['paymentId']);
					$itemDisplayArr[$docId]['paymentArr'][$paymentArr['paymentId']]['amount'] = tx_pttools_div::htmlOutput('- '.tx_pttools_finance::getFormattedPriceString($paymentArr['amount'],$this->shopConfig['currencyCode']));
					$itemDisplayArr[$docId]['paymentArr'][$paymentArr['paymentId']]['date'] =
					$paymentArr['date'] != '' ? tx_pttools_div::htmlOutput($paymentArr['date']) : '&nbsp;';
					$itemDisplayArr[$docId]['paymentArr'][$paymentArr['paymentId']]['creditAmount'] = tx_pttools_div::htmlOutput('- '.tx_pttools_finance::getFormattedPriceString($paymentArr['creditAmount'],$this->shopConfig['currencyCode']));
				}
				#echo '<br>naechstes itemDisplayArr<br> ';
				#var_dump($itemDisplayArr[$docId]);

				// get overpayment for this invoice
				$overpaymentList = $this->gsaTransactionAccessorObj->selectOverpaymentByCustomer($this->feCustomer->get_gsaMasterAddressId(),$invoice->get_relatedDocNo(),false);
				foreach ($overpaymentList as $overpaymentArr) {
					$itemDisplayArr[$docId]['overpaymentCond'] = true;
					$itemDisplayArr[$docId]['overpaymentArr'][$paymentArr['overpaymentId']]['text'] = tx_pttools_div::htmlOutput($this->pi_getLL('fl_overpayment','[fl_overpayment]'));
					$itemDisplayArr[$docId]['overpaymentArr'][$paymentArr['overpaymentId']]['docId'] = tx_pttools_div::htmlOutput($overpaymentArr['docId']);
					$itemDisplayArr[$docId]['overpaymentArr'][$paymentArr['overpaymentId']]['amount'] = tx_pttools_div::htmlOutput('- '.tx_pttools_finance::getFormattedPriceString($overpaymentArr['overpayment'],$this->shopConfig['currencyCode']));
					$itemDisplayArr[$docId]['overpaymentArr'][$paymentArr['overpaymentId']]['date'] =
					$overpaymentArr['date'] != '' ? tx_pttools_div::htmlOutput($overpaymentArr['date']) : '&nbsp;';
					$itemDisplayArr[$docId]['overpaymentArr'][$paymentArr['overpaymentId']]['creditPayment'] = tx_pttools_div::htmlOutput('- '.tx_pttools_finance::getFormattedPriceString($overpaymentArr['creditPayment'],$this->shopConfig['currencyCode']));
				}

				$i++;
				 
			}
		}

		if ($i>0) {
			$smarty->assign('cond_invoices',true);
			$smarty->assign('fl_invoices',$this->pi_getLL('fl_invoices','[fl_invoices]'));
			$smarty->assign('fl_outstandingItems',$this->pi_getLL('fl_outstandingItems','[fl_outstandingItems]'));
			$smarty->assign('fv_outstandingAmount',tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($this->accountBalance,$this->shopConfig['currencyCode'])));
		}

		$smarty->assign('cond_pmInvoice',$cond_pmInvoice);
		$smarty->assign('cond_pmDebit',$cond_pmDebit);
		if ($this->lastPaymentDateInvoice) {
			$smarty->assign('fv_lastPaymentDateInvoice',tx_pttools_div::convertDate($this->lastPaymentDateInvoice,1));
		} else {
			$smarty->assign('cond_pmInvoice',false);
		}
		if ($this->lastPaymentDateDebit) {
			$smarty->assign('fv_lastPaymentDateDebit',tx_pttools_div::convertDate($this->lastPaymentDateDebit,1));
		} else {
			$smarty->assign('cond_pmDebit',false);
		}
		 
		$this->accountBalance = bcsub($this->creditBalance,$this->accountBalance,2);
		$smarty->assign('fv_accountBalance',tx_pttools_div::htmlOutput(tx_pttools_finance::getFormattedPriceString($this->accountBalance,$this->shopConfig['currencyCode'])));

		trace($itemDisplayArr,0,'$itemDisplayArr');

		$smarty->assign('itemDisplayArr',$itemDisplayArr);

		$smartyFile=$this->conf['templateFileMyAccount'];
		#trace($smartyFile,'0','smartyFile');
		$filePath = $smarty->getTplResFromTsRes($smartyFile);

		trace($filePath, 0, 'Smarty template resource filePath');
		return $smarty->fetch($filePath);
	}

	/**
	 * get Link to Related Documen of Invoice
	 * @param   string 	Document Number to get Link
	 * @return  string  url of Document
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-07-10
	 */
	private function getRelatedDocNoLink($relatedDocNo) {
		$pdfDocument = tx_ptgsapdfdocs_documentAccessor::getInstance()->selectDocumentsByRelatedErpDocNo($relatedDocNo);
		trace($pdfDocument[0],0,'pdfDocument');
		if ($pdfDocument[0]['file']) {
			$pdf['url'] = tx_ptgsapdfdocs_div::urlToInvoice($relatedDocNo);
		} else {
			$pdf['url'] = '';
		}
		if ($pdfDocument[0]['signed']) {
			$pdf['signed'] = true;
		}
		#echo 'pdf'; var_dump($pdf); echo '<br>';
		return  $pdf;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/pi2/class.tx_ptgsaaccounting_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/pi2/class.tx_ptgsaaccounting_pi2.php']);
}

?>