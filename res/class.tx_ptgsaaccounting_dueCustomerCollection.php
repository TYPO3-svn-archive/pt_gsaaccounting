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
 * dueCustomer collection class for the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2008-09-23
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // GSA accounting Transaction Accesor class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php';// Customer from pt_gsauserreg 
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customerCollection.php';// Customer Collection from pt_gsauserreg 
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class



/**
 * GSA dueCustomer collection class
 *
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2008-09-23
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_dueCustomerCollection extends tx_ptgsauserreg_customerCollection {
    
    /**
     * Properties
     */
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates a collection of dueCustomer objects. If no parameter is specified all dueCustomer records are given back
     *
     * @param   string   (optional) paymentMethod 'bt','cc','db' ('bt' = bank transfer/on account, 'cc' = credit card, 'dd' = direct debit)    
     * @param   boolean (optional) useDunning   consider dunning data (dunningLevel, dunningDueDate and waiting period of customer) for decision if items are due
     * @return  void
 	 * @author	Dorit Rottner <rottner@punkt.de>
 	 * @since   2008-09-23
     */
    public function __construct($paymentMethod='', $useDunning = false) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
		$idArr = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->selectDueCustomers($paymentMethod, $useDunning);
        
		if (is_array($idArr)) {
    		foreach ($idArr as $id) {
    			$this->addItem(new tx_ptgsauserreg_customer($id), $id);
    		}
		}
		
    }   
    
    /***************************************************************************
     *   extended collection methods
     **************************************************************************/
    
 
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     

} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dueCustomerCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dueCustomerCollection.php']);
}

?>
