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
 * Class for dtabuch objects in the pt_gsahbci framework
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-03
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of extension specific libraries
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // extension specific database accessor class for GSA transactions
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchAccessor.php';  // extension specific database accessor class for dtabuch data from GSA

/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class

/**
 *  Class for dtabuch objects
 *
 * @access      public
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2007-08-03
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_dtabuch  {
    
	/**
	 * Constants
	 */

	const EXTKEY = 'tx_ptgsaaccounting';
										// well known payment methods from GSA
    
    /**
     * Properties
     */

    protected $dtauid;      // integer unique Id of dtabuchrecord
    protected $name;        // string first part of 27 charcters of accountHolder
    protected $name2;       // string second part of 27 charcters of accountHolder
    protected $bankCode;    // string Code of customers Bank
    protected $bankAccount; // string bank account of customers 
    protected $type;        // string is actually 'Einzug' 
    protected $amount;      // double amount from GSA DTABUCH
    protected $bookingSum;  // double bookingSum Sum without credits and cancellation 
    protected $purpose;     // string contains relatedDocNo from ERP
    protected $purpose2;    // string Addidtional purpose, if 27 charecters not enough
    protected $program;     // string is actually 'AUFW' 
    protected $bookingDate; // date Date when entry is generated 
    protected $dueDate;     // date Date due from this date  
    protected $transferDate;  // date Date when transferred to Bank  
    protected $isEuro;      // boolean hardcoded to 1  
     
    
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) ID of the GSA-DB DTABUCH Record (px_DTABUCH.NUMMER). Set to 0 if you want to use the 2nd param.
     * @param   array       Array containing dtabuch data to set as dtabuch object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class (see tx_ptgsauseracc_address::setAddressFromGivenArray() for used properties). This param has no effect if the 1st param is set to something other than 0.
     * @return	void   
     * @throws  tx_pttools_exception   if the first param is not numeric  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-03
     */
	public function __construct($dtabuchId=0, $dtabuchDataArr=array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($dtabuchId)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if a customer record ID is given, retrieve customer array from database accessor (and overwrite 2nd param)
        if ($dtabuchId  > 0) {
            $dtabuchDataArr = tx_ptgsaaccounting_dtabuchAccessor::getInstance()->selectDtabuchByUid($dtabuchId);
        }
        
        $this->setDtabuchFromGivenArray($dtabuchDataArr);
           
        trace($this);
        
    }
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the properties using data given by param array
     *
     * @param   array     Array containing dtabuch data to set as dtabuch object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class.
     * @return  void        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-03
     */
    protected function setDtabuchFromGivenArray($dtabuchDataArr) {
        
		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($dtabuchDataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($dtabuchDataArr[$propertyname]);
			}
		}
    }
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-03
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
     * Stores current dtabuch data in GSA DB
	 * if gsauid is non-zero, these records are updated;
	 * otherwise new records are created
	 *
     * @param   void        
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-03
     */
    public function storeSelf() {

		$dataArray = $this->getDataArray();
        $this->set_dtauid(tx_ptgsaaccounting_dtabuchAccessor::getInstance()->storeDtabuchData($dataArray));

	}
        

    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer property value
     * @since   2007-08-03
    */
    public function get_dtauid() {
        return $this->dtauid;
    }

    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2007-08-03
    */
    public function set_dtauid($dtauid) {
        $this->dtauid = intval($dtauid);
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_name() {
        return $this->name;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_name($name) {
        $this->name = (string) $name;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_name2() {
        return $this->name2;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_name2($name2) {
        $this->name2 = (string) $name2;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_bankCode() {
        return $this->bankCode;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_bankCode($bankCode) {
        $this->bankCode = (string) $bankCode;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_bankAccount() {
        return $this->bankAccount;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_bankAccount($bankAccount) {
        $this->bankAccount = (string) $bankAccount;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_type() {
        return $this->type;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_type($type) {
        $this->type = (string) $type;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-08-07
    */
    public function get_amount() {
        return $this->amount;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-08-07
    */
    public function set_amount($amount) {
        $this->amount = (double)$amount;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-08-03
    */
    public function get_bookingSum() {
        return $this->bookingSum;
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-08-03
    */
    public function set_bookingSum($bookingSum) {
        $this->bookingSum = (double)$bookingSum;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_purpose() {
        return $this->purpose;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_purpose($purpose) {
        $this->purpose = (string) $purpose;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_purpose2() {
        return $this->purpose2;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_purpose2($purpose2) {
        $this->purpose2 = (string) $purpose2;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_program() {
        return $this->program;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_program($program) {
        $this->program = (string) $program;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_bookingDate() {
        return $this->bookingDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_bookingDate($bookingDate) {
        $this->bookingDate = (string) $bookingDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_dueDate() {
        return $this->dueDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_dueDate($dueDate) {
        $this->dueDate = (string) $dueDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  string  property value
     * @since   2007-08-03
    */
    public function get_transferDate() {
        return $this->transferDate;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2007-08-03
    */
    public function set_transferDate($transferDate) {
        $this->transferDate = (string) $transferDate;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean property value
     * @since   2007-08-03
    */
    public function get_isEuro() {
        return $this->isEuro;
    }

    /**
     * Set the property value
     *
     * @param   boolean
     * @return  void
     * @since   2007-08-03
    */
    public function set_isEuro($isEuro) {
        $this->isEuro = $isEuro ? true : false;
    }


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuch.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuch.php']);
}

?>
