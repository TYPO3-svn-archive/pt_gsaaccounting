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
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2008-09-22
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_erpDocument.php';// extension specific erpDocument class 
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // GSA accounting Transaction Accesor class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php';



/**
 * GSA erpDocument collection class
 *
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2008-09-22
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_erpDocumentCollection extends tx_pttools_objectCollection {
    
    /**
     * Properties
     */
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates a collection of erpDocument objects. If no parameter is specified all erpDocument records are given back
     *
     * @param   boolean     (optional) only outstanding Items 
     * @param   integer     (optional) Id for this customer
     * @param   string      (optional) paymentMethod 'bt','cc','dd' ('bt' = bank transfer/on account, 'cc' = credit card, 'dd' = direct debit)
     * @param   array       (optional) searchArray for different searchfields 
     * @param   string      (optional) documentType 'invoice', 'creditnote', 'cancellation'. Default type is invoice
     * @param   string      (optional) order by own sorting therefore the dataspecific Fields have to be used 
     * @return  void
 	 * @author	Dorit Rottner <rottner@punkt.de>
 	 * @since   2008-09-22
     */
    public function __construct($outstanding = false, $customerId = 0, $paymentMethod='', $searchArr=array(), $documentType = '', $orderBy = '') { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        tx_pttools_assert::isValidUid($customerId, true, array('message' => __CLASS__.'No valid customer Id: '.$customerId.'!'));

		// load collection from database
        if ($outstanding == false) { 
            $searchArr['searchAll'] = true;
        }
        if ($paymentMethod!='') {
            $searchArr['sf_paymentMethod'] = $paymentMethod;
        }
		$dataListArr = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->selectOutstandingItems($searchArr, $documentType, $orderBy, $customerId);

		foreach ($dataListArr as $dataArr) {
			$this->addItem(new tx_ptgsaaccounting_erpDocument(0,'',$dataArr), $dataArr['docId']);
		}
    }   
    
    /***************************************************************************
     *   extended collection methods
     **************************************************************************/
    
 
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
        
    /** 
     * Returns the maximal dunnuing level of the ERP documents within the collection
     *
     * @param   void
     * @return  integer     maximal dunnuing level of the ERP documents within the collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-02
     */
    public function getMaxDunningLevel() { 
        
        $maxDunningLevel = (int) 0;
        
        foreach ($this as $erpDocument) {
            if ($erpDocument->get_dunningLevel() > $maxDunningLevel) {
                $maxDunningLevel = (int) $erpDocument->get_dunningLevel();
            }
        }
        
        return $maxDunningLevel;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     

} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_erpDocumentCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_erpDocumentCollection.php']);
}

?>
