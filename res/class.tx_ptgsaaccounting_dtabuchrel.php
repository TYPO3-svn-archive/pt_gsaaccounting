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
 * Class for dtabuchrel objects in the pt_hosting framework
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-08
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchrelAccessor.php';  // extension specific database accessor class for dtabuchrel data
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class

/**
 *  Class for dtabuchrel objects
 *
 * @access      public
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2007-08-08
 * @package     TYPO3
 * @subpackage  tx_ptgsa
 */
class tx_ptgsaaccounting_dtabuchrel  {
    
	/**
	 * Constants
	 */

	const EXTKEY = 'tx_ptgsaaccounting';
										// well known payment methods from GSA
    
    /**
     * Properties
     */

    protected $uid;             // integer unique Id of dtabuchrelrecord
    protected $gsaDtabuchUid;   // integer unique Id of dtabuch Record in GSA
    protected $bankCode;        // string Code of Bank
    protected $accountHolder;   // string Account Holder
    protected $accountNo;       // string Account Number
    protected $relatedDocNo;    // string Invoice Number from GSA comes from purpose
    protected $purpose;         // string Purpose additional to Invoice Number
    protected $filename;        // string Name of DTA-file contains Date time and sum 
    protected $bookingAmount;    // double Amount original amount from Invoice  
    protected $transferAmount;  // double Amount corrected for Credits an cancellation  
    protected $differenceAmount; // double Difference between outstanding Payment in GSA and transferAmount in dtabuchrel  
    protected $invoiceDate;     // date Date of invoice  
    protected $dueDate;         // date Date when payment is due  
    protected $transferDate;    // date Date when transferred to Bank  
    protected $bookDate;        // date for booking
    protected $gsaBookDate;      // date Book Date for Gsa
    protected $bankRejected;    // boolean Rejected from Bank
    protected $bankaccountCheck; // boolean Result of check Bank Account
    protected $dropUser;         // boolean User is dropUser
    protected $invoiceOkGsa;     // boolean Invoice already marked as ok in GSA when booking
    protected $reprocess;       // boolean reprocess while trandering once more to banke  
    protected $type;            // string type if debit or credit   
     
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) ID of the GSA-DB DTABUCH Record (px_DTABUCH.NUMMER). Set to 0 if you want to use the 2nd param.
     * @param   array       Array containing dtabuchrel data to set as dtabuchrel object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class (see tx_ptgsauseracc_address::setAddressFromGivenArray() for used properties). This param has no effect if the 1st param is set to something other than 0.
     * @param   string      InvoiceNumber
     * @return	void   
     * @throws  tx_pttools_exception   if the first param is not numeric  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-08
     */
	public function __construct($dtabuchrelId=0, $relatedDocNo='',$dtabuchrelDataArr=array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($dtabuchrelId)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if a customer record ID is given, retrieve customer array from database accessor (and overwrite 2nd param)
        trace($dtabuchrelId,0,'$dtabuchrelId');
        if ($dtabuchrelId  > 0) {
            $dtabuchrelDataArr = tx_ptgsaaccounting_dtabuchrelAccessor::getInstance()->selectDtabuchrelByUid($dtabuchrelId);
        } else if ($relatedDocNo != '') {
            $dtabuchrelDataArr = tx_ptgsaaccounting_dtabuchrelAccessor::getInstance()->selectDtabuchrelByRelatedDocNo((string) $relatedDocNo);
        }
        
        $this->setDtabuchrelFromGivenArray($dtabuchrelDataArr);
           
        trace($this);
        
    }
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the customer properties using data given by param array
     *
     * @param   array     Array containing dtabuchrel data to set as dtabuchrel object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class.
     * @return  void        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-08
     */
    protected function setDtabuchrelFromGivenArray($dtabuchrelDataArr) {
        
		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($dtabuchrelDataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($dtabuchrelDataArr[$propertyname]);
			}
		}
    }
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-08
     */
    protected function getDataArray() {
        trace('[METHOD] '.__METHOD__);

		$dataArray = array();

		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			$getter = 'get_'.$propertyname;
			$dataArray[$propertyname] = $this->$getter();
		}

		return $dataArray;
	}
        
    /**
     * checks if already transfered and not rejected  
     * @param   void   
     * @return  boolean  true if not transfered or rejected  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-29
     */
    public function isOutstanding() {
        $outstanding = true;
        if ($this->get_uid()) {
            if ($this->get_bankRejected()!= true) {
                $outstanding = false;
            }
        }
        return $outstanding;
    }
    
    /**
     * Stores current dtabuchrel data in TYPO3 DB
	 * if uid is non-zero, these records are updated;
	 * otherwise new records are created
	 *
     * @param   void        
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-08
     */
    public function storeSelf() {
        trace('[METHOD] '.__METHOD__);
		$dataArray = $this->getDataArray();
        $this->set_uid(tx_ptgsaaccounting_dtabuchrelAccessor::getInstance()->storeDtabuchrelData($dataArray));
        

	}
        

    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2007-08-09
    */
    public function get_uid() {
        return $this->uid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2007-08-09
    */
    public function set_uid($uid) {
        $this->uid = intval($uid);
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2007-08-09
    */
    public function get_gsaDtabuchUid() {
        return $this->gsaDtabuchUid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2007-08-09
    */
    public function set_gsaDtabuchUid($gsaDtabuchUid) {
        $this->gsaDtabuchUid = intval($gsaDtabuchUid);
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_bankCode() {
        return $this->bankCode;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_bankCode($bankCode) {
        $this->bankCode = (string) $bankCode;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_accountHolder() {
        return $this->accountHolder;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_accountHolder($accountHolder) {
        $this->accountHolder = (string) $accountHolder;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_accountNo() {
        return $this->accountNo;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_accountNo($accountNo) {
        $this->accountNo = (string) $accountNo;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_relatedDocNo() {
        return $this->relatedDocNo;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_relatedDocNo($relatedDocNo) {
        $this->relatedDocNo = (string) $relatedDocNo;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_purpose() {
        return $this->purpose;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_purpose($purpose) {
        $this->purpose = (string) $purpose;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-09-14
    */
    public function get_filename() {
        return $this->filename;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-09-14
    */
    public function set_filename($filename) {
        $this->filename = (string) $filename;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-08-15
    */
    public function get_bookingAmount() {
        return $this->bookingAmount;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-08-15
    */
    public function set_bookingAmount($bookingAmount) {
        $this->bookingAmount = (double)$bookingAmount;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-08-09
    */
    public function get_transferAmount() {
        return $this->transferAmount;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-08-09
    */
    public function set_transferAmount($transferAmount) {
        $this->transferAmount = (double)$transferAmount;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-09-11
    */
    public function get_differenceAmount() {
        return $this->differenceAmount;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-09-11
    */
    public function set_differenceAmount($differenceAmount) {
        $this->differenceAmount = (double)$differenceAmount;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_invoiceDate() {
        return $this->invoiceDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_invoiceDate($invoiceDate) {
        $this->invoiceDate = (string) $invoiceDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_dueDate() {
        return $this->dueDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_dueDate($dueDate) {
        $this->dueDate = (string) $dueDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-09
    */
    public function get_transferDate() {
        return $this->transferDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-09
    */
    public function set_transferDate($transferDate) {
        $this->transferDate = (string) $transferDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-09-14
    */
    public function get_bookDate() {
        return $this->bookDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-09-14
    */
    public function set_bookDate($bookDate) {
        $this->bookDate = (string) $bookDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-14
    */
    public function get_gsaBookDate() {
        return $this->gsaBookDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-14
    */
    public function set_gsaBookDate($gsaBookDate) {
        $this->gsaBookDate = (string) $gsaBookDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-08-09
    */
    public function get_bankRejected() {
        return $this->bankRejected;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-08-09
    */
    public function set_bankRejected($bankRejected) {
        $this->bankRejected = $bankRejected ? true : false;
    }

    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-08-14
    */
    public function get_bankaccountCheck() {
        return $this->bankaccountCheck;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-08-14
    */
    public function set_bankaccountCheck($bankaccount_check) {
        $this->bankaccountCheck = $bankaccount_check ? true : false;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-08-15
    */
    public function get_dropUser() {
        return $this->dropUser;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-08-15
    */
    public function set_dropUser($dropUser) {
        $this->dropUser = $dropUser ? true : false;
    }

     
    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-09-12
    */
    public function get_invoiceOkGsa() {
        return $this->invoiceOkGsa;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-09-12
    */
    public function set_invoiceOkGsa($invoiceOkGsa) {
        $this->invoiceOkGsa = $invoiceOkGsa ? true : false;
    }

    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-09-28
    */
    public function get_reprocess() {
        return $this->reprocess;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-09-28
    */
    public function set_reprocess($reprocess) {
        $this->reprocess = $reprocess ? true : false;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-11-23
    */
    public function get_type() {
        return $this->type;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-11-23
    */
    public function set_type($type) {
        $this->type = (string) $type;
    }


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchrel.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchrel.php']);
}

?>
