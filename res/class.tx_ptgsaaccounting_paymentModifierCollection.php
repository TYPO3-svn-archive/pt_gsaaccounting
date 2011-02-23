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
 * Payment modifier collection
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2008-10-21
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_paymentModifierAccessor.php';  // Accessor class for PaymentModifier 

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_iPaymentModifierCollection.php'; // interface for payment Modifier

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; // assertion class 
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class


/**
 * Payment modifier collection: to be implemented for voucher etc. to be processed by the shop core
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2008-10-21
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_paymentModifierCollection extends tx_pttools_objectCollection implements tx_ptgsashop_iPaymentModifierCollection {
     
    /**
     * Class constructor: creates a collection of paymentModifier objects. if order Uid is set the Collection is loaded from Archive
     *
     * @param   integer (possible) orderUid if (orderUid > 0 load it from Archive)   
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
    public function __construct($orderUid=0) { 
        tx_pttools_assert::isValidUid($orderUid,true,array('message'=>'Invalid OrrderUid:'.$orderUid));
    	if ($orderUid > 0) {
        	$this->loadFromOrderArchive($orderUid);
        }
    }
    
    
    /**
     * Returns the total value of all payment modifiers of the collection to be processed by the shop core
     *
     * @param   void
     * @return  double      total value of all payment modifiers
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function getValue() {
        
        $value = 0;
        foreach($this as $paymentModifier) {
        	$value = bcadd($value, $paymentModifier->get_value(),2);
        }
     	// HOOK: allow multiple hooks to change Value of all payment Modifiers
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['getValueHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['getValueHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $value = $hookObj->getValueHook($this);
            }
        }
        return $value;
     }
     
    /**
     * Handles a possible value excess of the payment modifiers collection (if value of collection is bigger than order total)
     *
     * @param   double      amount of the value excess of the payment modifiers collection (excess sum if value of collection is bigger than order total, 0 otherwise)
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function handleValueExcess($valueExcessSum) {
        // HOOK: allow multiple hooks to handle possible value access of all payment Modifiers
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection']['handleValueExcess'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection']['handleValueExcess'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->getValue($this, $valueExcessSum);
            }
        }
        
     	
     }
     
    /**
     * Returns a HTML representation of all payment modifiers of the collection
     *
     * @param   void
     * @return  string      HTML representation of all payment modifiers of the collection
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function getViewHtml() {
     	$viewHtml = '';
        // HOOK: allow multiple hooks to change the HTML presentation of all payment Modifiers 
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['getViewHtmlHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['getViewHtmlHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $viewHtml = $hookObj->getViewHtmlHook($this, $viewHtml);
            }
        }
        
     	return $viewHtml;
     }
     
    /**
     * Returns a plain text representation of all payment modifiers of the collection
     *
     * @param   void
     * @return  string      plain text representation of all payment modifiers of the collection
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function getViewPlainText() {
        $viewPlainText = '';
        // HOOK: allow multiple hooks to change the plaintext presentation of all payment Modifiers 
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection']['getViewPlainTextHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['getViewPlainTextHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $viewPlainText = $hookObj->getViewPlainTextHook($this, $viewPlainText);
            }
        }
        
        return $viewPlainText;
     	
     }
     
    /**
     * Handles the storage of all payment modifiers of the collection into the ERP data basis
     *
     * @param  string      document number ("Vorgangsnummer") of the already saved related order document in the ERP system
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function storeToErp($erpDocNo) {
     	
     }
     
    /**
     * Handles the storage of all payment modifiers of the collection into GSA shop's order archive
     *
     * @param   integer  ID of the related order record in the TYPO3 order archive database
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function storeToOrderArchive($orderArchiveId) {
        foreach($this as $paymentModifier) {
            $paymentModifier->set_orderUid($orderArchiveId);
        	$paymentModifier->storeSelf();
        }
     	
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection']['storeToOrderArchive'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['storeToOrderArchive'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->storeToOrderArchive($this);
            }
        }
     	
     }
     
    /**
     * Handles the storage of all payment modifiers of the collection into GSA shop's order archive
     *
     * @param   integer  ID of the related order record in the TYPO3 order archive database
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-21
     */
     public function loadFromOrderArchive($orderUid) {
        $idArr = tx_ptgsaaccounting_paymentModifierAccessor::getInstance()->getIdArr($orderUid); 
     	foreach($idArr as $id) {
            $this->addItem(new tx_ptgsaaccounting_paymentModifier($id),$id);
        }
        
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection']['loadFronOrderArchive'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['paymentModifierCollection_hooks']['loadFronOrderArchive'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->loadFromOrderArchive($orderUid);
            }
        }
        
     }
    
} // end class

/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_paymentModifierCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_paymentModifierCollection.php']);
}


?>