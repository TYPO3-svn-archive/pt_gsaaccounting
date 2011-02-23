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
 * Hooking class of the 'pt_gsaaccounting' extension for hooks in tx_ptgsashop_orderPresentator
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-11-30
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionHandler.php';

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderPresentator.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class


/**
 * Class being included by pt_gsashop using hooks in tx_ptgsashop_orderPresentator
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-11-29
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_hooks_ptgsashop_orderPresentator extends tx_ptgsashop_orderPresentator {
    
    /**
     * Constants
     */
    const PM_CCARD      = 'Kreditkarte';
    const PM_INVOICE    = 'Rechnung';
    const PM_DEBIT      = 'DTA-Buchung';
    
    protected $precision = 0;    // integer Precision for gsaacounting set in main method; depends on pgsashop config 'usePricesWithMoreThanTwoDecimals'

    /**
     * Class constructor: sets the object's properties
     *
     * @param   void
     * @return  void     
     * @global  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-29
     */
    public function __construct() {
        $this->shopConfigArr = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ptgsashop.'];        
        
        if($this->shopConfigArr['usePricesWithMoreThanTwoDecimals'] == 1) {
            $this->precision = 4;
        } else {
            $this->precision = 2;
        }    
        trace($this->shopConfigArr,0,'shopConfigArr');
    }


    
    /**
     * This method is called by a hook in tx_ptgsashop_orderPresentator::getPlaintextPresentation(): initiate pt_accounting specific stuff
     *
     * @param   object      current instance of parent object calling this hook
     * @param   array       markerArray
     * @return  array       changed markerArray 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    public function getPlaintextPresentation_MarkerArrayHook($pObj, $markerArray) {
        //$GLOBALS['trace'] = 2;
        trace('[METHOD] '.__METHOD__);
        $gsaTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
        trace($markerArray,0,'$markerArray');
        trace($pObj->conf,0,'$pObj->conf');
        trace($pObj->piVars,0,'piVars');
        $orderWrapperObj = new tx_ptgsashop_orderWrapper(0, $pObj->orderObj->get_orderArchiveId());
        trace($orderWrapperObj,0,'$orderWrapperObj');
        $orderCreditBalanceObj= new tx_ptgsaaccounting_orderCreditBalance(0,$orderWrapperObj->get_uid());
        $creditBalanceInvoice = $orderCreditBalanceObj->get_amountInvoice();
        $creditBalance = $orderCreditBalanceObj->get_amountCustomer();
        trace($creditBalance,0,'$creditBalance');        
        trace($creditBalanceInvoice,0,'$creditBalanceInvoice');        
        // Look if there is a Credit Balance
        if ($creditBalance > 0) {
            trace($orderCreditBalanceObj->get_amountInvoice(). 'Guthaben reserviert');
            $markerArray['cond_creditBalance'] = true;
            $markerArray['creditBalance']  = tx_pttools_finance::getFormattedPriceString($creditBalance);
            $markerArray['creditBalanceRest'] = tx_pttools_finance::getFormattedPriceString(bcsub($creditBalance,$creditBalanceInvoice,2));
            if ($creditBalanceInvoice <= 0 ) {
                $markerArray['paymentAmount'] = $orderWrapperObj->get_sumGross();
                $markerArray['creditBalanceInvoice'] = sprintf("%9.2f", 0);
            } else {
                $markerArray['cond_accountCreditBalance'] = true;
                trace(floatval(bcsub($orderWrapperObj->get_sumGross() , $creditBalanceInvoice,$this->precision)),0,'difference');
                if (floatval(bcsub($orderWrapperObj->get_sumGross() , $creditBalanceInvoice,$this->precision))> 0) {
                    $markerArray['creditBalanceInvoice'] = sprintf("%9.2f", -$creditBalanceInvoice);
                    $markerArray['paymentAmount'] = sprintf("%9.2f", bcsub($orderWrapperObj->get_sumGross() , $creditBalanceInvoice,$this->precision));
                } else {
                    $markerArray['creditBalanceInvoice'] = sprintf("%9.2f",floatval(-$orderWrapperObj->get_sumGross()));
                    $markerArray['paymentAmount'] = sprintf("%9.2f", 0);
                }
            }
        }
        return $markerArray;
        
    }
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/hooks/class.tx_ptgsaaccounting_hooks_ptgsashop_orderPresentator.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/hooks/class.tx_ptgsaaccounting_hooks_ptgsashop_orderPresentator.php']);
}

?>
