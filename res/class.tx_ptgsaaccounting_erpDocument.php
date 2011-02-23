<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Dorit Rottner (rottner@punkt.de)
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
 * Class for erpDocument objects in the pt_accounting framework
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2008-09-22
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Inclusion of extension specific libraries
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // extension specific database accessor class for GSA transactions
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionHandler.php';

/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; // assertion class

/**
 *  Class for erpDocument objects
 *
 * @access      public
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2008-09-22
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_erpDocument  {

	/**
	 * Constants
	 */

	const EXTKEY = 'tx_ptgsaaccounting';

	/**
	 * Properties
	 */

	protected $docId = 0;           // integer of document Record
	protected $customerId = 0;      // integer Id of the Customer Record
	protected $paymentMethod = '';  // string payment Method
	protected $documentType = '';   // string document Type 'invoice', 'credit' or 'cancellation'
	protected $relatedDocNo = '';   // string Document Number of the ERP System
	protected $amountGross = 0;     // double amount Gross
	protected $amountNet = 0;       // double amount Net
	protected $payment = 0;         // double amount Payment
	protected $credit = 0;          // double amount of Credit
	protected $totalDiscountPercent = 0; // double discount of Document in Percent
	protected $totalDiscountType = 0; // type of total discount set to 0 because ERP Document writes 0 if there is no special dicount type
	protected $discount = 0;        // double amount of discount
	protected $date = '';           // string date of document
	protected $ok = false;          // boolean flag if handling of ERP Record is finished
	protected $duePeriod = 0;       // integer Number of days when document is due
	protected $dunningLevel = 0;    // integer Level for Dunning from 0 to 3
	protected $dunningDate = '';    // string  date of Dunning in MySQl Format
	protected $dunningDueDate = ''; // string  date of Dunning is due in in MySQl Format
	protected $dunningCharge = 0;   // double charge for Dunning
	protected $customerObj = null;  // customer Object
	protected $hasCancellation = false;   // boolean flag if handling if ERP Record has Cancellation as continued Document
	#protected $positionCollection = null;  // Collection of related Positions

	/**
	 * Class constructor - fills object's properties with param array data
	 *
	 * @param   integer  (optional) ID of the erpDocument. Set to 0 if you want to use the 2nd or 3rd param.
	 * @param   String   (optional) number of the erpDocument. Set to 0 if you want to use the 3rd param.
	 * @param   array    (optional) Array containing erpDocument data to set as erpDocument object's properties; array keys have to be named exactly like the proprerties of this class. This param has no effect if the 1st param is set to something other than 0. or the second is set to something other than ''.
	 * @param   integer  (optional) number of days when Document isDue. Default is false
	 * @return	void
	 * @throws  tx_pttools_exception   if the first param is not numeric
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-22
	 */
	public function __construct($docId=0, $relatedDocNo='',$dataArr=array()) {

		trace('***** Creating new '.__CLASS__.' object. *****');


		tx_pttools_assert::isValidUid($docId, true, array('message' => __CLASS__.'No valid erpDocument docId: '.$docId.'!'));


		// if a customer record ID is given, retrieve customer array from database accessor (and overwrite 2nd param)
		if ($docId  > 0 || $relatedDocNo != '') {
			$dataArr = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->selectDocument($docId, $relatedDocNo);
		}

		if (!empty($dataArr)) {
			$this->hasCancellation = tx_ptgsaaccounting_gsaTransactionHandler::hasCancellation($dataArr['relatedDocNo']);
			#$GLOBALS['trace'] =1;
			trace($this->hasCancellation,0,'$this->hasCancellation');
			#$GLOBALS['trace'] =0;
			$this->setFromGivenArray($dataArr);
		}
		 
		trace($this);

	}


	/***************************************************************************
	 *   GENERAL METHODS
	 **************************************************************************/

	/**
	 * Sets the properties using data given by param array
	 *
	 * @param   array     Array containing erpDocument data to set as erpDocument object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class.
	 * @return  void
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-22
	 */
	protected function setFromGivenArray($dataArr) {

		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($dataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($dataArr[$propertyname]);
			}
		}
	}

	/**
	 * returns array with data from all properties
	 *
	 * @param   void
	 * @return  array	array with data from all properties
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-22
	 */
	protected function getDataArray() {

		$dataArray = array();

		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			$getter = 'get_'.$propertyname;
			$dataArray[$propertyname] = $this->$getter();
		}

		return $dataArray;
	}


	/**
	 * Stores erpDocument in the ERP Database update is done no new Record is written
	 *
	 * @param   void
	 * @return  void
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-23
	 */
	public function storeSelf() {
		trace('[METHOD] '.__METHOD__);
		$dataArr = $this->getDataArray();
		tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->storeErpDocument($dataArr);
	}

	/**
	 * Check if document is due
	 *
	 * @param   boolean flag dunning has be considered
	 * @return  boolean document is due
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-23
	 */
	public function isDue($useDunning = false) {
		trace('[METHOD] '.__METHOD__);
		$isDue = false;

		$dueDate = $this->getDueDate($useDunning);
		if (date('Y-m-d',time()) > $dueDate) {
			$isDue = true;
		}
		return $isDue;
	}

	/**
	 * Check if document is due
	 *
	 * @param   boolean flag dunning has be considered
	 * @return  string  dueDate in MySQL Format
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-29
	 */
	public function getDueDate($useDunning = false) {
		trace('[METHOD] '.__METHOD__);

		if ($useDunning == true) {
			$dueDate = $this->get_dunningDate();
		} else {
			if ($this->get_duePeriod()) {
				$dueDate = date('Y-m-d',strtotime($this->get_date().'+'.intval($this->get_duePeriod()).' day'));
			} else {
				$dueDate = $this->get_date();
			}
		}
		return $dueDate;
	}


	/**
	 * getOutsandingAmount
	 *
	 * @param   void
	 * @return  double  outstanding amount for this document
	 * @author  Dorit Rottner <rottner@punkt.de>
	 * @since   2008-09-29
	 */
	public function getOutstandingAmount() {
		trace('[METHOD] '.__METHOD__);

		// only consider payment and credit because discount is only set if there is no outstanding amount
		$outstandingAmount = bcsub($this->get_amountGross(),bcadd($this->get_credit(), $this->get_payment(),2),2);
		return $outstandingAmount;
	}

	/***************************************************************************
	 *   PROPERTY GETTER/SETTER METHODS
	 **************************************************************************/
	 
	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  integer property value
	 * @since   2008-09-22
	 */
	public function get_docId() {
		return $this->docId;
	}

	/**
	 * Set the property value
	 *
	 * @param   integer
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_docId($docId) {
		$this->docId = intval($docId);
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  integer property value
	 * @since   2008-09-22
	 */
	public function get_customerId() {
		return $this->customerId;
	}

	/**
	 * Set the property value
	 *
	 * @param   integer
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_customerId($customerId) {
		$this->customerId = intval($customerId);
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  string  property value
	 * @since   2008-09-22
	 */
	public function get_paymentMethod() {
		return $this->paymentMethod;
	}

	/**
	 * Set the property value
	 *
	 * @param   string
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_paymentMethod($paymentMethod) {
		$this->paymentMethod = (string) $paymentMethod;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  string  property value
	 * @since   2008-09-22
	 */
	public function get_documentType() {
		return $this->documentType;
	}

	/**
	 * Set the property value
	 *
	 * @param   string
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_documentType($documentType) {
		$this->documentType = (string) $documentType;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  string  property value
	 * @since   2008-09-22
	 */
	public function get_relatedDocNo() {
		return $this->relatedDocNo;
	}

	/**
	 * Set the property value
	 *
	 * @param   string
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_relatedDocNo($relatedDocNo) {
		$this->relatedDocNo = (string) $relatedDocNo;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-09-22
	 */
	public function get_amountGross() {
		return $this->amountGross;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_amountGross($amountGross) {
		$this->amountGross = (double)$amountGross;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-09-22
	 */
	public function get_amountNet() {
		return $this->amountNet;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_amountNet($amountNet) {
		$this->amountNet = (double)$amountNet;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-09-22
	 */
	public function get_payment() {
		return $this->payment;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_payment($payment) {
		$this->payment = (double)$payment;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-09-22
	 */
	public function get_credit() {
		return $this->credit;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_credit($credit) {
		$this->credit = (double)$credit;
	}

	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-10-23
	 */
	public function get_totalDiscountPercent() {
		return $this->totalDiscountPercent;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-10-23
	 */
	public function set_totalDiscountPercent($totalDiscountPercent) {
		$this->totalDiscountPercent = (double)$totalDiscountPercent;
	}

	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  integer  property value
	 * @since   2008-10-23
	 */
	public function get_totalDiscountType() {
		return $this->totalDiscountType;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-10-23
	 */
	public function set_totalDiscountType($totalDiscountType) {
		$this->discount = (double)$totalDiscountType;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-09-22
	 */
	public function get_discount() {
		return $this->discount;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_discount($discount) {
		$this->discount = (double)$discount;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  string  property value
	 * @since   2008-09-22
	 */
	public function get_date() {
		return $this->date;
	}

	/**
	 * Set the property value
	 *
	 * @param   string
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_date($date) {
		$this->date = (string) $date;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  boolean property value
	 * @since   2008-09-22
	 */
	public function get_ok() {
		return $this->ok;
	}

	/**
	 * Set the property value
	 *
	 * @param   boolean
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_ok($ok) {
		$this->ok = $ok ? true : false;
	}

	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  integer property value
	 * @since   2008-09-23
	 */
	public function get_duePeriod() {
		return $this->duePeriod;
	}

	/**
	 * Set the property value
	 *
	 * @param   integer
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_duePeriod($duePeriod) {
		$this->duePeriod = intval($duePeriod);
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  integer property value
	 * @since   2008-09-22
	 */
	public function get_dunningLevel() {
		return $this->dunningLevel;
	}

	/**
	 * Set the property value
	 *
	 * @param   integer
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_dunningLevel($dunningLevel) {
		$this->dunningLevel = intval($dunningLevel);
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  string  property value
	 * @since   2008-09-22
	 */
	public function get_dunningDate() {
		return $this->dunningDate;
	}

	/**
	 * Set the property value
	 *
	 * @param   string
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_dunningDate($dunningDate) {
		$this->dunningDate = (string) $dunningDate;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  string  property value
	 * @since   2008-09-22
	 */
	public function get_dunningDueDate() {
		return $this->dunningDueDate;
	}

	/**
	 * Set the property value
	 *
	 * @param   string
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_dunningDueDate($dunningDueDate) {
		$this->dunningDueDate = (string) $dunningDueDate;
	}


	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  double  property value
	 * @since   2008-09-22
	 */
	public function get_dunningCharge() {
		return $this->dunningCharge;
	}

	/**
	 * Set the property value
	 *
	 * @param   double
	 * @return  void
	 * @since   2008-09-22
	 */
	public function set_dunningCharge($dunningCharge) {
		$this->dunningCharge = (double)$dunningCharge;
	}

	/**
	 * Returns the property value
	 *
	 * @param   void
	 * @return  boolean property value
	 * @since   2009-08-05
	 */
	public function get_hasCancellation() {
		return $this->hasCancellation;
	}

	/**
	 * Set the property value
	 *
	 * @param   boolean
	 * @return  void
	 * @since   2009-08-05
	 */
	public function set_hasCancellation($hasCancellation) {
		$this->hasCancellation = $hasCancellation ? true : false;
	}

	/**
	 * Set the property value
	 *
	 * @param   tx_ptgsauserreg_customer customer
	 * @return  void
	 * @since   2008-09-23
	 */
	public function set_customerObj(tx_ptgsauserreg_customer $customerObj) {
		$this->customerObj = $customerObj;
	}

	/**
	 * Get the property value
	 *
	 * @param   void
	 * @return  tx_ptgsauserreg_customer customer
	 * @since   2008-09-23
	 */
	public function get_customerObj() {
		trace('[METHOD] '.__METHOD__);
		 
		if (is_null($this->customerObj) && $this->customerId) {
			$this->customerObj = new tx_ptgsauserreg_customer($this->customerId);
		}
		return $this->customerObj;
	}

	/**
	 * Checks if the ePayment for a invoice was successful.
	 *
	 * @return bool True if the payment was successful
	 *
	 * @author Christoph Ehscheidt <ehscheidt@punkt.de>
	 * @since 2010-09-07
	 */
	public function checkEPaymentSuccess() {
		$select = 'w.related_doc_no, p.orders_id, p.epayment_success';
		 
		$from = 'tx_ptgsashop_order_wrappers w
                        JOIN tx_ptgsashop_orders_paymentmethods p
                               ON p.orders_id = w.orders_id AND p.method = \'cc\'
                       ';
		$where = 'w.related_doc_no = \''.$this->relatedDocNo.'\'';
		 
		 
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where,'','',1);
		 
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		 
		if(!empty($row['epayment_success']) &&  $row['epayment_success'] == 1) {
			return true;
		}
		 
		return false;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_erpDocument.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_erpDocument.php']);
}

?>
