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
 * Hooking class of the 'pt_gsaaccounting' extension for hooks in tx_ptgsashop_pi3
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-11-29
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
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_orderCreditBalanceAccessor.php';
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_orderCreditBalance.php';

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'pi3/class.tx_ptgsashop_pi3.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class


/**
 * Class being included by pt_gsashop using hooks in tx_ptgsashop_pi3
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-11-29
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_hooks_ptgsashop_pi3 extends tx_ptgsashop_pi3 {
    
    /**
     * Constants
     */
    const EXT_KEY       = 'pt_gsaaccounting';               // (string) the extension key
    const LL_FILEPATH   = 'res/hooks/locallang.xml';           // (string) path to the locallang file to use within this class
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
        trace('[METHOD] '.__METHOD__);
    	//$GLOBALS['trace'] = 1;
        if($this->shopConfigArr['usePricesWithMoreThanTwoDecimals'] == 1) {
            $this->precision = 4;
        } else {
            $this->precision = 2;
        }    
        trace($this->shopConfigArr,0,'shopConfigArr');
    }


    /**
     * This method is called by a hook in tx_ptgsashop_pi3::processOrderSubmission(): initiate pt_accounting specific stuff
     *
     * @param   object      current instance of parent object calling this hook
     * @return  void        GSA ERP doc number (dt: "Vorgangsnumer)
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-01-09
     */
	public function processOrderSubmission_fixFinalOrderHook ($pObj, $relatedErpDocNo) {
        trace('[METHOD] '.__METHOD__);
        
        $orderWrapperObj = new tx_ptgsashop_orderWrapper(0, $pObj->orderObj->get_orderArchiveId());
        $orderCreditBalanceObj = new tx_ptgsaaccounting_orderCreditBalance(0, $orderWrapperObj->get_uid());
        $gsaAccountingTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();

        // evtl. Guthaben mit Rechnung verbuchen
        if ($this->orderCreditBalanceObj->get_amountInvoice() > 0 ) {
            $this->orderCreditBalanceObj->bookCreditBalance($orderWrapperObj->get_relatedDocNo());
            //$gsaAccountingTransactionHandlerObj->bookkkiii();
            $gsaAccountingTransactionHandlerObj->bookCreditBalance($orderWrapperObj->get_relatedDocNo(), $this->orderCreditBalanceObj->get_gsaUid(), $this->orderCreditBalanceObj->get_amountInvoice());
        }
        unset($gsaAccountingTransactionHandlerObj);

	}

    
    /**
     * This method is called by a hook in tx_ptgsashop_pi3::displayOrderOverview(): initiate pt_accounting specific stuff
     *
     * @param   object      current instance of parent object calling this hook
     * @param   array       markerArray
     * @return  array       changed markerArray 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-29
     */
    public function displayOrderOverview_MarkerArrayHook($pObj, $markerArray) {
        trace('[METHOD] '.__METHOD__);

        $llArray = tx_pttools_div::readLLfile(t3lib_extMgm::extPath(self::EXT_KEY).self::LL_FILEPATH); // get locallang data
        trace($llArray,0,'$llArray');
        
        // Instance gsaacounting Transaction Object   
        $gsaTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
        trace($markerArray,0,'$markerArray');
        trace($pObj->conf,0,'$pObj->conf');
        trace($pObj->piVars,0,'piVars');
        $orderWrapperObj = new tx_ptgsashop_orderWrapper(0, $pObj->orderObj->get_orderArchiveId());
        $creditBalance = $gsaTransactionHandlerObj->getCreditBalance($pObj->customerObj->get_gsaMasterAddressId(),$orderWrapperObj->get_uid());        
        if ($creditBalance > 0) {
            // See if there are outstandingItems
            
            $outstandingAmount = $gsaTransactionHandlerObj->getOutstandingAmountCustomer($pObj->customerObj->get_gsaMasterAddressId());
            $creditBalance = bcsub($creditBalance,$outstandingAmount,$this->precision);
            if ($creditBalance > 0) {
                if ($pObj->piVars['notAccountCreditBalance']) {
                    trace('hier');
                    $markerArray['ll_overview_doAccountCreditBalance'] = tx_pttools_div::getLLL('bl_overview_doAccountCreditBalance', $llArray);
                    $markerArray['cond_doAccountCreditBalance'] = false;
                } else {
                    $markerArray['cond_doAccountCreditBalance'] = true;
                    $markerArray['ll_overview_notAccountCreditBalance'] = tx_pttools_div::getLLL('bl_overview_notAccountCreditBalance', $llArray);
                }
                    
                trace($creditBalance. 'Guthaben vohanden');
                trace($markerArray['ll_overview_notAccountCreditBalance'],0,'ll_overview_notAccountCreditBalance');
                $markerArray['cond_creditBalance'] = true;
                $markerArray['creditBalance']  = tx_pttools_finance::getFormattedPriceString($creditBalance);
                $markerArray['cond_accountCreditBalance'] = true;

                $markerArray['ll_overview_creditBalance'] = tx_pttools_div::getLLL('fl_overview_creditBalance', $llArray);
                $markerArray['ll_overview_creditBalanceInfo'] = tx_pttools_div::getLLL('fl_overview_creditBalanceInfo', $llArray);
                $markerArray['ll_overview_accountCreditBalance'] = tx_pttools_div::getLLL('fl_overview_accountCreditBalance', $llArray);
                $markerArray['ll_overview_payment'] = tx_pttools_div::getLLL('fl_overview_payment', $llArray);
                $markerArray['ll_overview_creditBalanceRest'] = tx_pttools_div::getLLL('fl_overview_creditBalanceRest', $llArray);
                if ($pObj->piVars['notAccountCreditBalance']) {
                    $creditBalanceInvoice = 0;
                    $markerArray['paymentAmount'] = $markerArray['orderSumTotal_gross'];
                    $markerArray['creditBalanceInvoice'] = tx_pttools_finance::getFormattedPriceString(0);
                } else {
                    if (bcsub($orderWrapperObj->get_sumGross() , $creditBalance,$this->precision)> 0) {
                        $creditBalanceInvoice = $creditBalance;
                        $markerArray['paymentAmount'] = tx_pttools_finance::getFormattedPriceString(bcsub($orderWrapperObj->get_sumGross() , $creditBalance,$this->precision));
                    } else {
                        $creditBalanceInvoice = $markerArray['orderSumTotal_gross'];
                        $markerArray['paymentAmount'] = tx_pttools_finance::getFormattedPriceString(0);
                    }
                    $markerArray['creditBalanceInvoice'] = tx_pttools_finance::getFormattedPriceString(-$creditBalanceInvoice);
                }
                
                $markerArray['creditBalanceRest'] = tx_pttools_finance::getFormattedPriceString(bcsub($creditBalance,$creditBalanceInvoice,2));
                // store it in orderCreditBalance Record
                $orderCreditBalanceObj= new tx_ptgsaaccounting_orderCreditBalance(0,$orderWrapperObj->get_uid());
                $orderCreditBalanceObj->set_gsaUid($orderWrapperObj->get_customerId());
                $orderCreditBalanceObj->set_orderWrapperUid($orderWrapperObj->get_uid());
                $orderCreditBalanceObj->set_amountInvoice($creditBalanceInvoice);
                $orderCreditBalanceObj->set_amountCustomer($creditBalance);
                $orderCreditBalanceObj->set_reserved(true);
                $orderCreditBalanceObj->set_booked(false);
                $orderCreditBalanceObj->storeSelf();
                
            }
            
        }

        return $markerArray;
        
    }
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/hooks/class.tx_ptgsaaccounting_hooks_ptgsashop_pi3.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/hooks/class.tx_ptgsaaccounting_hooks_ptgsashop_pi3.php']);
}

?>
