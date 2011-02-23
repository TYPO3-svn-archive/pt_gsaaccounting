<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dorit Rottner (rottner@punkt.de)
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
 * @since   2008-10-21
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_paymentModifierAccessor.php';  // Accessor class for PaymentModifier 


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
 * @since       2008-10-21
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_paymentModifier  {
    
	/**
	 * Constants
	 */

	const EXTKEY = 'tx_ptgsaaccounting';
    
    /**
     * Properties
     */

    protected $uid;            // integer unique Id of paymentModifier
    protected $orderUid;        // integer Uid of the order archive
    protected $relatedDocNo;    // document number of the related ERP Document  
    protected $value;           // double amount of payment Modifier  
    protected $addDataType;     // string type of additional data    
    protected $addData;         // string additional data    
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) possible uid
     * @param   array       (optional) possible data Array
     * @return	void   
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
	public function __construct($uid=0,$dataArr=array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        tx_pttools_assert::isValidUid($uid,true,array('message'=>'Invalid uid:'.$uid));
        if ($uid) {
            $dataArr = tx_ptgsaaccounting_paymentModifierAccessor::getInstance()->selectPaymentModifierByUid($uid);
        }
        
        $this->setFromGivenArray($dataArr);
           
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
     * @since   2008-10-21
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
     * @since   2008-10-21
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
     * @since   2008-11-21
     */
    public function storeSelf() {
        trace('[METHOD] '.__METHOD__);
        $dataArray = $this->getDataArray();
        $this->set_uid(tx_ptgsaaccounting_paymentModifierAccessor::getInstance()->storePaymentModifierData($dataArray));
        

    }
	
    

    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/

    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2008-11-21
    */
    public function get_uid() {
        return $this->uid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2008-11-21
    */
    public function set_uid($uid) {
        $this->uid = intval($uid);
    }

    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2008-10-21
    */
    public function get_orderUid() {
        return $this->orderUid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2008-10-21
    */
    public function set_orderUid($orderUid) {
        $this->orderUid = intval($orderUid);
    }

    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2008-10-21
    */
    public function get_relatedDocNo() {
        return $this->relatedDocNo;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2008-10-21
    */
    public function set_relatedDocNo($relatedDocNo) {
        $this->relatedDocNo = (string) $relatedDocNo;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2008-11-21
    */
    public function get_addData() {
        return $this->addData;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2008-11-21
    */
    public function set_addData($addData) {
        $this->addData = (string) $addData;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2008-11-21
    */
    public function get_addDataType() {
        return $this->addDataType;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2008-11-21
    */
    public function set_addDataType($addDataType) {
        $this->addDataType = (string) $addDataType;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2008-10-21
    */
    public function get_value() {
        return $this->value;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2008-10-21
    */
    public function set_value($value) {
        $this->value = (double)$value;
    }


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_paymentModifier.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_paymentModifier.php']);
}

?>
