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
 * Class for orderCreditBalance objects in the pt_hosting framework
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2007-12-04
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_orderCreditBalanceAccessor.php';  // extension specific database accessor class for orderCreditBalance data
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class

/**
 *  Class for orderCreditBalance objects
 *
 * @access      public
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2007-12-04
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_orderCreditBalance  {
    
	/**
	 * Constants
	 */

	const EXTKEY = 'tx_ptgsaaccounting';
										// well known payment methods from GSA
    
    /**
     * Properties
     */

    protected $uid;             // integer unique Id of orderCreditBalancerecord
    protected $deleted;         // boolean if object is maeked as deleted  
    protected $gsaUid;          // integer unique Id of customer Record in GSA
    protected $orderWrapperUid; // integer Uid of the orderWrapper to use the creditBalance
    protected $relatedDocNo;    // string Invoice Number from GSA comes from purpose
    protected $amountInvoice;   // double amount of credit Balnaces accounted to the order  
    protected $amountCustomer;  // double amount of credit Balnaces for the customer  
    protected $bookDate;        // date for booking
    protected $reserved;        // boolean amount of creditBalance is reserved for this order  
    protected $booked;          // boolean amount of creditBalance is booked for this  
     
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) uid of the orderCreditBalance Record. Set to 0 if you want to use the 2nd or 3rd param.
     * @param   integer     (optional) Id of the related Order Wrapper Record. Set to 0 if you want to use the 3rd param.
     * @param   string      (optional) InvoiceNumber
     * @param   array       Array containing orderCreditBalance data to set as orderCreditBalance object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class (see tx_ptgsauseracc_address::setAddressFromGivenArray() for used properties). This param has no effect if the 1st param is set to something other than 0.
     * @return	void   
     * @throws  tx_pttools_exception   if the first param is not numeric  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
	public function __construct($uid=0, $orderWrapperUid=0,$relatedDocNo= '',$orderCreditBalanceDataArr=array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($uid)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        if (!is_numeric($orderWrapperUid)) {
            throw new tx_pttools_exception('Parameter error', 3, 'Second parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if a customer record ID is given, retrieve customer array from database accessor (and overwrite 2nd param)
        trace($uid,0,'uid');
        if ($uid  > 0) {
            $orderCreditBalanceDataArr = tx_ptgsaaccounting_orderCreditBalanceAccessor::getInstance()->selectOrderCreditbalanceByUid($uid);
        } else if ($orderWrapperUid > 0) {
            $orderCreditBalanceDataArr = tx_ptgsaaccounting_orderCreditBalanceAccessor::getInstance()->selectOrderCreditbalanceByOrderWrapperUid($orderWrapperUid);
        } else if ($relatedDocNo != '') {
            $orderCreditBalanceDataArr = tx_ptgsaaccounting_orderCreditBalanceAccessor::getInstance()->selectOrderCreditbalanceByRelatedDocNo((string) $relatedDocNo);
        }
        
        $this->setOrderCreditbalanceFromGivenArray($orderCreditBalanceDataArr);
           
        trace($this);
        
    }
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the customer properties using data given by param array
     *
     * @param   array     Array containing orderCreditBalance data to set as orderCreditBalance object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class.
     * @return  void        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    protected function setOrderCreditBalanceFromGivenArray($orderCreditBalanceDataArr) {
        
		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($orderCreditBalanceDataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($orderCreditBalanceDataArr[$propertyname]);
			}
		}
    }
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
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
     * Stores current orderCreditBalance data in TYPO3 DB
	 * if uid is non-zero, these records are updated;
	 * otherwise new records are created
	 *
     * @param   void        
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    public function storeSelf() {
        trace('[METHOD] '.__METHOD__);
		$dataArray = $this->getDataArray();
        $this->set_uid(tx_ptgsaaccounting_orderCreditBalanceAccessor::getInstance()->storeOrderCreditBalanceData($dataArray));
        

	}
        

    /**
     * Deletes current orderCreditBalance data in TYPO3 DB while set deleted flag to true
     * if uid is zero nothing has be done
     * otherwise new records are created
     *
     * @param   void        
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-01-09
     */
    public function deleteSelf() {
        trace('[METHOD] '.__METHOD__);
        if ($this->get_uid()) {
            $this->set_deleted(true);
            $dataArray = $this->getDataArray();
            $this->set_uid(tx_ptgsaaccounting_orderCreditBalanceAccessor::getInstance()->storeOrderCreditBalanceData($dataArray));
        }
        

    }
        

    /**
     * sets current creditBlance object as booked and strores it in Database    
     * @param   string Related Document Number from GSA
     * @return  void  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    public function bookCreditBalance($relatedDocNo) {
        trace('[METHOD] '.__METHOD__);
        $this->set_booked(true);
        $this->set_reserved(false);
        $this->set_relatedDocNo($relatedDocNo);
        $this->set_bookDate(date('Y-m-d'));
        $this->storeSelf();
    }
    

    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2007-12-04
    */
    public function get_uid() {
        return $this->uid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2007-12-04
    */
    public function set_uid($uid) {
        $this->uid = intval($uid);
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2007-12-04
    */
    public function get_gsaUid() {
        return $this->gsaUid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2007-12-04
    */
    public function set_gsaUid($gsaUid) {
        $this->gsaUid = intval($gsaUid);
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2007-12-04
    */
    public function get_orderWrapperUid() {
        return $this->orderWrapperUid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2007-12-04
    */
    public function set_orderWrapperUid($orderWrapperUid) {
        $this->orderWrapperUid = intval($orderWrapperUid);
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-12-04
    */
    public function get_relatedDocNo() {
        return $this->relatedDocNo;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-12-04
    */
    public function set_relatedDocNo($relatedDocNo) {
        $this->relatedDocNo = (string) $relatedDocNo;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-12-04
    */
    public function get_amountInvoice() {
        return $this->amountInvoice;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-12-04
    */
    public function set_amountInvoice($amountInvoice) {
        $this->amountInvoice = (double)$amountInvoice;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-12-04
    */
    public function get_amountCustomer() {
        return $this->amountCustomer;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-12-04
    */
    public function set_amountCustomer($amountCustomer) {
        $this->amountCustomer = (double)$amountCustomer;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-12-04
    */
    public function get_bookDate() {
        return $this->bookDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-12-04
    */
    public function set_bookDate($bookDate) {
        $this->bookDate = (string) $bookDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-12-04
    */
    public function get_reserved() {
        return $this->reserved;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-12-04
    */
    public function set_reserved($reserved) {
        $this->reserved = $reserved ? true : false;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-12-04
    */
    public function get_booked() {
        return $this->booked;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-12-04
    */
    public function set_booked($booked) {
        $this->booked = $booked ? true : false;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2008-01-09
    */
    public function get_deleted() {
        return $this->deleted;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2008-01-09
    */
    public function set_deleted($deleted) {
        $this->deleted = $deleted ? true : false;
    }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_orderCreditBalance.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_orderCreditBalance.php']);
}

?>
