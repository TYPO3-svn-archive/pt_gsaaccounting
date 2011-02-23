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
 * GSA transaction (ERP: "Vorgang") handler class for the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-28
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of TYPO3 libraries
 *
 * @see t3lib_div
 */
require_once(PATH_t3lib.'class.t3lib_div.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // database accessor class for GSA transactions
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/staticlib/class.tx_ptgsaaccounting_div.php';  // static Methods of extension pt_gsaaccounting
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchrel.php';           // class for dtabuchrel
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_orderCreditBalanceAccessor.php';   // class for orderCreditBalanceAceesor

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_textfileDataAccessor.php';  // GSA Shop library with static methods

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // combined GSA/TYPO3 FE customer class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php';  // GSA specific customer class



/**
 * GSA transaction (ERP: "Vorgang") handler class. NOTICE: This class contains temporary solutions since structure and meaning of the GSA database tables is not completely investigated!!
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-08-28 
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting_
 */
class tx_ptgsaaccounting_gsaTransactionHandler {
    
    /**
     * Constants
     */
    const TEST_MODE = false;
    const PM_CCARD      = 'Kreditkarte';
    const PM_INVOICE    = 'Rechnung';
    const PM_DEBIT      = 'DTA-Buchung';
    /**
     * Properties
     */
    protected $classConfigArr = array(); // (array) array with configuration values used by this class (this is set once in the class constructor)
    protected $shopConfigArr = array(); // (array) array with configuration values used by the pt_gsashop extension
    protected $precision = 0;    // integer Precision for gsaacounting set in constructor depends on pgsashop config 'usePricesWithMoreThanTwoDecimals'

    

        
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the object's properties
     *
     * @param   void
     * @return  void     
     * @global  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-29
     */
    public function __construct() {
        trace('[METHOD] '.__METHOD__);
        $this->classConfigArr = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ptgsaaccounting.'];
        $this->shopConfigArr = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ptgsashop.'];        
        
        if($this->shopConfigArr['usePricesWithMoreThanTwoDecimals'] == 1) {
            $this->precision = 4;
        } else {
            $this->precision = 2;
        }    
        trace($this->classConfigArr,0,'classConfigArr');
        trace($this->shopConfigArr,0,'shopConfigArr');
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /** 
     * Continues an invoice document Number to an continued document in the GSA database (ERP: "Auftrag zur Gutschrift fortfuehren") and processes all appropriate GSA DB updates
     * 
     *
     * @param   string      related Document Number 
     * @param   string      type of continued Document (at time credit or cancellation) 
     * @param   double      Amount of Cancellation or Credit
     * @param   array       (optional) Array of positions to credit if not whole invoice is credited 
     * @param   boolean     (optional) flag if shipping is included 
     * @param   array       (optional) Array of possible Positions // TODO  Positions as objects
     * @return  string      the document number (ERP: "Vorgangsnummer") of the inserted continued document record (or of an already existing inserted continued document record)
     * @see     tx_ptgsashop_orderWrapperAccessor::updateOrderWrapperDocNoByReplacement()
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-15
     */ 
    public function continueInvoiceDocument( $relatedDocNo, $type, $amountGross,  $amountShipping, $posArr = NULL) {
        tx_pttools_assert::isInList($type,array('credit','cancellation'),array('message'=>'invalid type'. $type));
    	$shopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
    	$accountingTransactionAccessorObj = tx_accounting_gsaTransactionAccessor::getInstance();
        $erpType = $accountingTransactionAccessorObj->documentTypeToErp($type);
    	$invoiceDocFieldsArr = $shopTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
    	$this->continueInvoice($invoiceDocFieldsArr, $erpType, $amountGross, $amountShipping,$posArr);
    }
    
    /** 
     * Continues an invoice to an continued document in the GSA database (ERP: "Auftrag zur Gutschrift fortfuehren") and processes all appropriate GSA DB updates
     * 
     *
     * @param   array       Invoice Document from GSAUFTRAG 
     * @param   string      type of continued Document (at time GU for credit or ST for cancellation) 
     * @param   double      Amount of Cancellation or Credit
     * @param   array       (optional) Array of positions to credit if not whole invoice is credited 
     * @param   boolean     (optional) flag if shipping is included 
     * @return  string      the document number (ERP: "Vorgangsnummer") of the inserted continued document record (or of an already existing inserted continued document record)
     * @see     tx_ptgsashop_orderWrapperAccessor::updateOrderWrapperDocNoByReplacement()
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-28
     */ 
    public function continueInvoice($invoiceDocFieldsArr, $type, $amountGross,  $amountShipping, $posArr = NULL) {
        trace('[METHOD] '.__METHOD__);
        
        $continuedPosFieldsArr = array();
        $invoiceDocUpdateFieldsArr = array(); 
        $continuedPosUpdateFieldsArr = array(); 
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $gsaArticleAccessorObj = tx_ptgsashop_articleAccessor::getInstance();
        
        // if Tax not from Article get it per percentage
        $amountOutstanding = $invoiceDocFieldsArr['ENDPRB'] - $invoiceDocFieldsArr['BEZSUMME'] - $invoiceDocFieldsArr['GUTSUMME'] - $invoiceDocFieldsArr['GUTSCHRIFTZAHLUNG'] - $invoiceDocFieldsArr['SKONTOBETRAG'];
        $amountToPay = $amountOutstanding > $amountGross ? $amountGross : $amountOutstanding;
        // Ammount not equal Amount of Invoice
        if ($amountToPay != $invoiceDocFieldsArr['ENDPRB']) {
            // get Taxrate
            $taxRate = tx_pttools_finance::getTaxRate($invoiceDocFieldsArr['ENDPRB'], $invoiceDocFieldsArr['ENDPRN']);
            $amountNet = round(tx_pttools_finance::getNetPriceFromGross($amountToPay, $taxRate),2);
        } else {
            $amountNet = $invoiceDocFieldsArr['ENDPRN'];
        }
        trace($posArr,0,'$posArr');
        trace ($amountToPay,0,'$amountToPay');
        trace ($taxRate,0,'$taxRate');
        trace ($amountGross,0,'$amountGross');
        trace ($amountNet,0,'$amountNet');
        // prepare  data: copy continued record data to continued record data and change/overwrite relevant values
        $continuedDocFieldsArr = $invoiceDocFieldsArr;
        
        $continuedDocFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocument(): database ID of the record
        $continuedDocFieldsArr['ALTNUMMER']  = $invoiceDocFieldsArr['NUMMER'];
        # $continuedDocFieldsArr['SCHRIFTNR']  = 0; ### TODO: do not uncomment - ERP seems _not_ to increase but to copy when using the GUI (see comment in tx_ptgsaaccounting__gsaTransactionAccessor::insertTransactionDocument())!  // will be overwritten/set by insertTransactionDocument(): continued transaction document number ("fortlaufende Vorgangsnummer")
        if ($type == 'GU') {
            $continuedDocFieldsArr['ERFART']     = '05GU';
            $continuedDocFieldsArr['NEUGU']      = 1;
            $gsaDocTypeAbbr      = 'GU';
        } else if ($type == 'ST') {
            $continuedDocFieldsArr['ERFART']     = '06ST';
            $gsaDocTypeAbbr      = 'ST';
        } else {
            throw new tx_pttools_exception('Wrong type', 3, 'Type :'.$type);
        }
        
        if ($amountGross > $amountToPay) {
            // Sollte nicht vorkommen
        }
        
        // Done for both creditBalance and Cancellation 
        $invoiceDocUpdateFieldsArr['GUTSUMME'] = $invoiceDocFieldsArr['GUTSUMME'];
        $invoiceDocUpdateFieldsArr['GUTSUMME']   += $amountToPay;

        $continuedDocFieldsArr['ALTERFART']  = $invoiceDocFieldsArr['ERFART'];
        $continuedDocFieldsArr['MARKIERT']   = NULL;
        $continuedDocFieldsArr['OPNUMMER']   = 0; // ### TODO: ERP GUI writes a different number based on file OPNR.INI (not multi-user safe!) // will be overwritten/set by insertTransactionDocument(): outstanding items numbers of invoices (ERP: "Offene Posten")
        $continuedDocFieldsArr['GEBUCHT']    = 1;
        $continuedDocFieldsArr['GEDRUCKT']   = 1;
        $continuedDocFieldsArr['AUFTRAGOK']  = 1;
        $continuedDocFieldsArr['FORTGEFUEHRT'] = 0;
        $continuedDocFieldsArr['RESTOK']     = 1;
        $continuedDocFieldsArr['GEMAILT']    = 0;
        $continuedDocFieldsArr['DATUM']      = date('Y-m-d');
        $continuedDocFieldsArr['AUFNR']      = ''; // will be overwritten/set by insertTransactionDocument(): transaction document number (ERP: "Vorgangsnummer")
        $continuedDocFieldsArr['ALTAUFNR']   = $invoiceDocFieldsArr['AUFNR'];
        $continuedDocFieldsArr['GUTSUMME']   = 0.0000;
        $continuedDocFieldsArr['BEZSUMME']   = 0.0000;
        $continuedDocFieldsArr['ENDPRB']     = $amountToPay; 
        $continuedDocFieldsArr['ENDPRN']     = $amountNet;
        $continuedDocFieldsArr['FLDN01']     = $amountShipping;
        $continuedDocFieldsArr['MKZ']        = 0;      # TODO: find out where this comes from and what it is needed for
        $continuedDocFieldsArr['IVERTNR']    = 0;      # TODO: find out where this comes from and what it is needed for 
        $continuedDocFieldsArr['VORLAGEERLEDIGT'] = 0; # TODO: find out where this comes from and what it is needed for 
        $continuedDocFieldsArr['GESGEWICHT']      = 0.0000; # TODO: find out where this comes from and what it is needed for 
        #$continuedDocFieldsArr['UHRZEIT']         = ''; # TODO: ERP GUI writes nonsense here (e.g. '1899-12-30 13:10:27')
        $continuedDocFieldsArr['LTERMIN']         = date('d.m.Y');
        $continuedDocFieldsArr['BEMERKUNG']       = 'Automatisch generierte GU/ST fuer Rechnung aus Online-Bestellung'; // data type: longblob
        $continuedDocFieldsArr['LETZTERUSER']     = 'Online-Shop GU/ST Generator';  // data type: varchar(30)
        $continuedDocFieldsArr['LETZTERUSERDATE'] = date('Y-m-d H:i:s');
        trace($continuedDocFieldsArr,0,'$continuedDocFieldsArr');
        # $invoiceDocFieldsArr['SCHRIFTNR']  = 0; ### TODO: do not uncomment - ERP seems _not_ to increase but to copy when using the GUI (see comment in tx_ptgsashop_gsaTransactionAccessor::insertTransactionDocument())!  // will be overwritten/set by insertTransactionDocument(): continued transaction document number ("fortlaufende Vorgangsnummer")
                                            
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            // insert new continued document record
        	$continuedDocRecordId = $gsaShopTransactionAccessorObj->insertTransactionDocument($continuedDocFieldsArr, $gsaDocTypeAbbr);
            $continuedErpDocNo = $gsaShopTransactionAccessorObj->selectTransactionDocumentNumber($continuedDocRecordId);
        }        
        // update existing order invoice document record
        $invoiceDocUpdateFieldsArr['FORTGEFUEHRT']   = 1;
        $invoiceDocUpdateFieldsArr['LETZTERUSER']     = 'Online-Shop Gutschrgenerator';  // data type: varchar(30)
        $invoiceDocUpdateFieldsArr['LETZTERUSERDATE'] = date('Y-m-d H:i:s');
        trace($amountToPay >= $amountOutstanding,0,'$amountToPay >= $amountOutstanding');
        trace($amountToPay,0,'$amountToPay');
        trace($amountOutstanding,0,'$amountOutstanding');
        //restOk works not with Creditmemo TODO look what happens in GSAUFTRAG
        if ((bcsub($amountToPay ,$amountOutstanding,$this->precision)>= 0))  { 
        #if ($restOk == true ||   (bcsub($amountToPay ,$amountOutstanding,$this->precision)>= 0))  { 
            // update existing order confirmation document record
            $invoiceDocUpdateFieldsArr['GEBUCHT']        = 1;
            $invoiceDocUpdateFieldsArr['AUFTRAGOK']      = 1;
            $invoiceDocUpdateFieldsArr['FORTGEFUEHRT']   = 1;
            $invoiceDocUpdateFieldsArr['RESTOK']         = 1;
        }
        trace($invoiceDocUpdateFieldsArr,0,'$$invoiceDocUpdateFieldsArr');
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            $gsaShopTransactionAccessorObj->updateTransactionDocument($invoiceDocFieldsArr['AUFNR'], $invoiceDocUpdateFieldsArr);
        }        
        
    // ---------- PROCESS TRANSACTION POSITION RECORDS (GSA DB: FPOS and others) ---------- 
        
        // get order confirmation positions data
        $invoicePositionsArr = $gsaShopTransactionAccessorObj->selectTransactionDocPositions($invoiceDocFieldsArr['NUMMER']);
        
        // for all positions of the order confirmation document...
        
        if (is_array($invoicePositionsArr)) {
            if ($amountGross ==  $invoiceDocFieldsArr['ENDPRB'] || isset($posArr)) {
                $i = 0;
                foreach ($invoicePositionsArr as $key=>$invoicePosFieldsArr) {
                    $noToCredit = 0;
                    $posGP = $invoicePosFieldsArr['GP'];
                    if (isset($posArr)) {
                        foreach ($posArr as $pos) {
                            if($pos['posNo'] ==  $invoicePosFieldsArr['POSINR']) {
                                $noToCredit = $pos['noToCredit'];
                                $posGP = bcmul($pos['priceUnit'],$noToCredit,$this->precision);
                                trace($posGP, 0, '$posGP');
                                break;
                            }
                        }
                    }                    
                    trace($noToCredit,0,'$noToCredit');
                    trace($posArr,0,'$posArr');
                    
                    if ((isset($posArr) && $noToCredit > 0) || (!isset($posArr)))  {
                      // ...update the related article's transaction volume ("Umsatz") data: ARTIKEL.UMSATZ (net prices!) and ARTIKEL.LETZTERUMSATZ (Format: '2007-06-19 12:00:25')
                        // überprüfen
                        $i++;
                        
                        if ($invoiceDocFieldsArr['PRBRUTTO'] == 1) {
                            // retrieve net price from gross price if the continued document has the gross price flag set to 1!
                            $positionTaxRate  = tx_ptgsashop_lib::getTaxRate($invoicePosFieldsArr['USTSATZ'], $invoiceDocFieldsArr['DATUM']);
                            $posTotalNetPrice = tx_pttools_finance::getNetPriceFromGross($posGP, $positionTaxRate);
                        } else {
                            $posTotalNetPrice = $posGP; 
                        }
                        
                        trace($posGP, 0, '$posGP');
                        trace($posTotalNetPrice, 0, '$posTotalNetPrice');

                        if ($noToCredit > 0) {  
                            $quantity =  $noToCredit;
                        } else {                       
                            $quantity =  $invoicePosFieldsArr['MENGE'];
                        }

                      // ...insert new continued document position records 
                        
                        $continuedPosFieldsArr = $invoicePosFieldsArr; // prepare continued position data: copy order confirmation position data to continued position data and change/overwrite relevant values
                        $articleArray = $gsaArticleAccessorObj->selectCompleteArticleData($continuedPosFieldsArr['ARTINR']);
                        $continuedPosFieldsArr['AUFINR']     = $continuedDocRecordId;
                        $continuedPosFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocPosition()
                        $continuedPosFieldsArr['ALTAUFINR']  = $invoiceDocFieldsArr['NUMMER'];  // uid of predeceeding document record
                        $continuedPosFieldsArr['ALTNUMMER']  = $invoicePosFieldsArr['NUMMER'];  // uid of predeceeding position record
                        $continuedPosFieldsArr['FORTNUMMER'] = $invoicePosFieldsArr['NUMMER'];  # TODO: find out where this comes from and what it is needed for
                        $continuedPosFieldsArr['MENGE']  = $quantity;   // quantity to credit
                        $continuedPosFieldsArr['GUTSCHRIFT']  = 0.000;  // GUTSCHRIFT = 0 for thhis position
                        $continuedPosFieldsArr['RECHNUNG']  = 0.000;    // RECHNUNG = 0 for thhis position
                        $continuedPosFieldsArr['LIEFERUNG']  = 0.000;   // LIEFERUNG = 0 for thhis position
                        
                        $continuedPosFieldsArr['GESPEICHERT']  = 0;  // produced from GSAUUFTRAG even 'speichern' explititely done (for Invoices set to 1)
                        
                        $continuedPosFieldsArr['ALTERFART']  = $invoiceDocFieldsArr['ERFART'];
                        $counter = bcmul($i,10000.0000,$this->precision);
                        $continuedPosFieldsArr['INSZAEHLER'] = $counter; ### ??? TODO: Gutschriften werden erhöht 
                        
                        // For old invoices
                        $continuedPosFieldsArr['VEINHEIT'] = $continuedPosFieldsArr['VEINHEIT'] != '0.000' ? $continuedPosFieldsArr['VEINHEIT'] : $articleArray['VEINHEIT'];
                        $continuedPosFieldsArr['PEINHEIT'] = $continuedPosFieldsArr['PEINHEIT'] != '0.000' ? $continuedPosFieldsArr['PEINHEIT'] : $articleArray['PEINHEIT'];
                        $continuedPosFieldsArr['LEINHEIT'] = $continuedPosFieldsArr['PEINHEIT'] != '0.000' ? $continuedPosFieldsArr['LEINHEIT'] : $articleArray['LEINHEIT'];
                        $continuedPosFieldsArr['EINHEIT']  = $continuedPosFieldsArr['EINHEIT'] != '' ? $continuedPosFieldsArr['EINHEIT'] : $articleArray['EINHEIT'];
                        
                        trace($continuedPosFieldsArr,0,'$continuedPosFieldsArr');
                        if (self::TEST_MODE == false) {
                            // write record if not TEST_MODE
                            $continuedPosRecordId = $gsaShopTransactionAccessorObj->insertTransactionDocPosition($continuedPosFieldsArr);
                        }
                        
                      // ...update existing position position records quantity of Invoices  
                        trace($quantity, 0, '$quantity');
                        $invoicePosUpdateFieldsArr['GUTSCHRIFT'] = bcadd($invoicePosFieldsArr['GUTSCHRIFT'],$quantity,$this->precision);
                        $invoicePosUpdateFieldsArr['RECHNUNG'] = $invoicePosFieldsArr['MENGE'];
                        $invoicePosUpdateFieldsArr['LIEFERUNG'] = $invoicePosFieldsArr['MENGE'];
                        trace($invoicePosFieldsArr,0,'$invoicePosFieldsArr');
                        if (self::TEST_MODE == false) {
                            // write record if not TEST_MODE
                            $gsaShopTransactionAccessorObj->updateTransactionDocPosition($invoicePosFieldsArr['NUMMER'], $invoicePosUpdateFieldsArr);
                        }

                        $prePosArr = $gsaAccountingTransactionAccessorObj->selectTransactionDocPos($invoicePosFieldsArr['ALTNUMMER']);
                        // Write it in Starting Document
                        $prePosUpdateArr['GUTSCHRIFT'] = bcadd($prePosArr['GUTSCHRIFT'],$quantity,$this->precision);
                        trace($prePosUpdateArr,0,'$prePosUpdateArr');
                        if (self::TEST_MODE == false) {
                            // write record if not TEST_MODE
                            $gsaShopTransactionAccessorObj->updateTransactionDocPosition($invoicePosFieldsArr['ALTNUMMER'], $prePosUpdateArr);
                            $gsaArticleAccessorObj->updateTransactionVolume($continuedPosFieldsArr['ARTINR'], (double)-$posTotalNetPrice);
                        }
                    }
                }
            } else {
                
                // FPOS for free credit memo for invoice
                $i = 0;
                foreach ($invoicePositionsArr as $key=>$invoicePosFieldsArr) {
                  // ...update the related article's transaction volume ("Umsatz") data: ARTIKEL.UMSATZ (net prices!) and ARTIKEL.LETZTERUMSATZ (Format: '2007-06-19 12:00:25')
                    // ünerprüfen
                    $i++;
                    break;
                }
                $this->freeCreditMemoPos($amountNet,$continuedDocRecordId, $invoicePosFieldsArr);
            }
        }
        
        // ---------- return transaction document number of inserted (or already existing) continued ---------- 
        
        return $continuedErpDocNo;
        
    }
    
    /** 
     * Processes all appropriate GSA DB updates for the "Print Document" action of the ERP GUI
     * 
     * @param   string      the GSA ERP document number 
     * @return  void        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-28
     */ 
    public function setPrintedStatus($relatedDocNo) {
        
        $gsaTransactionAccessorObj = tx_ptgsaaccounting__gsaTransactionAccessor::getInstance();
        
        // get complete transaction document data and related customer object
        $relatedDocFieldsArr = $gsaTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
        $customerObj = new tx_ptgsauserreg_customer($relatedDocFieldsArr['ADRINR']);
        
        // update existing continued document record: FSCHRIFT.GEDRUCKT
        $relatedDocUpdateFieldsArr['GEDRUCKT'] = 1;
        $gsaTransactionAccessorObj->updateTransactionDocument($relatedDocNo, $relatedDocUpdateFieldsArr);
        
        // update the related customer's contact data (ADRESSE.LKONTAKT)
        $customerObj->registerLastContact();
        
    }
    
    /** 
     * Processes all appropriate GSA DB updates for the "Continue Invoice" action of the ERP GUI
     * 
     * @param   string      the GSA ERP document number of the invoice (ERP: "Rechnung/RE")
     * @param   string      type of continued Document (at time GU for credit or ST for cancellation) 
     * @param   double      Amount of cancellation or credit memo including Shipping 
     * @param   double      amount of shipping   
     * @param   array       (optional) Array of positions to credit if not whole invoice is credited 
     * @return  void     
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-29
     */ 
    public function bookCreditCancellation($invoiceDocNo,$type,$amountGross, $amountShipping, $posArr=NULL) {
        trace('[METHOD] '.__METHOD__);
        
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $invoiceDocFieldsArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($invoiceDocNo); // get complete transaction document data
        
        $this->continueInvoice($invoiceDocFieldsArr, $type, $amountGross,  $amountShipping, $posArr);
        // get related customer object, set booking data
        $customerObj = new tx_ptgsauserreg_customer($invoiceDocFieldsArr['ADRINR']);
        $bookingDate = date('Y-m-d');
        
        // update the related customer's transaction volume ("Umsatz"): ADRESSE.KUMSATZ, KUNDE.UMSATZ, KUNDE.SALDO, KUNDE.LETZTERUMSATZ
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            $customerObj->registerTransactionVolume(-$amountGross);  // TODO: netto oder brutto übergeben?? -> Analyse von wz abwarten!
        }
    }

    

    /** 
     * Bookes amount in free Credit Records for the Customer
     * 
     * @param   string      the GSA ERP document number of the invoice (GSA: "Rechnung/RE")
     * @param   integer     the GSA adressId of the Customer
     * @param   double      creditAmount 
     * @return  void     
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-27
     */ 
    public function bookCreditBalance($relatedDocNo, $gsaUid, $amount) {
        trace('[METHOD] '.__METHOD__);
        
        $gsaAccTransAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $gsaShopTransAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $freeCreditArr = $gsaAccTransAccessorObj->selectFreeCredits($gsaUid);
        $amountResidual = $amount;
        foreach ($freeCreditArr as $key=>$creditDocNo) {
            
            
            $freeCredit = $gsaShopTransAccessorObj->selectTransactionDocumentData($creditDocNo);
            $freeCreditUpdate = array();
            $credit = bcsub($freeCredit['ENDPRB'],$freeCredit['GUTSCHRIFTZAHLUNG'],$this->precision);
            if (bcsub($credit,$amountResidual,$this->precision) > 0) {
                $freeCreditUpdate['GUTSCHRIFTZAHLUNG'] = bcadd($amountResidual,$freeCredit['GUTSCHRIFTZAHLUNG'] ,$this->precision);
                $amountResidual = 0; 
            } else {
                $amountResidual = bcsub($amountResidual,$credit,$this->precision); 
                $freeCreditUpdate['GUTSCHRIFTZAHLUNG'] = bcadd($credit,$freeCredit['GUTSCHRIFTZAHLUNG'] ,$this->precision);
            }
            $freeCreditUpdate['LETZTERUSER']     = 'Online-Shop selected by Customer';  // data type: varchar(30)
            $freeCreditUpdate['LETZTERUSERDATE'] = date('Y-m-d H:i:s');
            
            if (self::TEST_MODE == false) {
                // write record if not TEST_MODE
                $gsaShopTransAccessorObj->updateTransactionDocument($freeCredit['AUFNR'], $freeCreditUpdate);
            }        
            if ($amountResidual <= 0) {
                break;
            }
                
        }        

        if ($amountResidual > 0) {
            $this->bookPayment($relatedDocNo, bcsub($amount, $amountResidual,$this->precision), 0, false, date('Y-m-d'), $additionalNote='Online-Shop '.$gsaUid, $amount);
            //throw new tx_pttools_exception('There is not enough free credit for this customer', 3);
        } else {
            $this->bookPayment($relatedDocNo, $amount, 0, false, date('Y-m-d'), $additionalNote='Online-Shop Customer:'.$gsaUid, $amount);
        }
        // get related customer object, set booking data
        $customerObj = new tx_ptgsauserreg_customer($gsaUid);
        $bookingDate = date('Y-m-d');
        
        // update the related customer's transaction volume ("Umsatz"): ADRESSE.KUMSATZ, KUNDE.UMSATZ, KUNDE.SALDO, KUNDE.LETZTERUMSATZ
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            $customerObj->registerTransactionVolume(0);  // saldo and umsatz doesn't change
        }
    }

    /** 
     * Processes all appropriate GSA DB updates for the "Book Payment" action of the ERP GUI
     * 
     * @param   string      the GSA ERP document number of the continued (ERP: "Rechnung/RE")
     * @param   double      amount for Payment
     * @param   double      discount for Payment used for difference from GSA if payment is not compltete but Invoive marked as payed
     * @param   boolean     set RestOK Flag in Invoice Document even if payment is not complete 
     * @param   string      Date for payment in Format "YYYY-mm-dd" or "dd.mm.YYYY", if not specified the date from now is used  
     * @param   string      additional Note for Field 'BEMERKUNG' in GSA if specified it will be added to the Invoice Number    
     * @param   double      amount fromCredit
     * @return  void        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-29
     */ 
    public function bookPayment($invoiceDocNo, $amount, $discount=0, $restOk = false, $date='', $additionalNote='', $fromCreditAmount=0) {
        trace('[METHOD] '.__METHOD__);
        
        $date = tx_ptgsaaccounting_div::convertDateToMySqlFormat($date);
        trace($additionalNote,0,'$additionalNote'); 
        $note = $additionalNote != '' ? $invoiceDocNo . ' ' . $additionalNote : $invoiceDocNo;
        if ($date == '') {
             throw new tx_pttools_exception('Wrong DateFormat', 3, 'Date '.$date.' has no valid Format. Allowed Formats are \'Y-m-d\' \'d.m.Y\' \'d.m.y\'');
        }
        $invoiceDocUpdateFieldsArr = array(); 
        $paymentUpdateFieldsArr = array(); 
        
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $invoiceDocFieldsArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($invoiceDocNo); // get complete transaction document data
        
        trace($amount,0,'$amount');
        trace($discount,0,'$discount');

        // get related customer object, set booking data
        $customerObj = new tx_ptgsauserreg_customer($invoiceDocFieldsArr['ADRINR']);
        
        // update the related customer's transaction volume ("Umsatz"): ADRESSE.KUMSATZ, KUNDE.UMSATZ, KUNDE.SALDO, KUNDE.LETZTERUMSATZ
        $customerObj->registerTransactionPayment($amount, $discount);  

        $invoiceDocFieldsArr['BEZSUMME']        += (double)$amount;
        $invoiceDocFieldsArr['SKONTOBETRAG']    += (double)$discount;

        $invoiceDocUpdateFieldsArr['BEZSUMME']         = $invoiceDocFieldsArr['BEZSUMME'];
        $invoiceDocUpdateFieldsArr['SKONTOBETRAG']     = $invoiceDocFieldsArr['SKONTOBETRAG'];
        $invoiceDocUpdateFieldsArr['LETZTERUSER']      = 'Online-Shop Bezahlgenerator';  // data type: varchar(30)
        $invoiceDocUpdateFieldsArr['LETZTERUSERDATE']  = date('Y-m-d H:i:s');
        
        $payed = $invoiceDocFieldsArr['BEZSUMME'] + $invoiceDocFieldsArr['GUTSUMME'] + $invoiceDocFieldsArr['SKONTOBETRAG'];
        trace($payed,0,'payed');
        if ($restOk || (bcsub($payed,$invoiceDocFieldsArr['ENDPRB'],$this->precision) )>=0) {
            trace('Auftrag Ok');
            $invoiceDocUpdateFieldsArr['RESTOK']    = 1;    
            $invoiceDocUpdateFieldsArr['AUFTRAGOK'] = 1;    
        }
        $gsaShopTransactionAccessorObj->updateTransactionDocument($invoiceDocNo, $invoiceDocUpdateFieldsArr);
        
        /*
         *  Example  XML Part from GSAUFTRAG Tabelle ZAHLUNG TODO: Delete if it works 
    <ZAHLUNG>
        <NUMMER>11</NUMMER>
        <AUFINR>2049</AUFINR>
        <OPNUMMER>50000061</OPNUMMER>
        <BETRAG>40.2100</BETRAG>
        <DATUM>2007-08-28</DATUM>
        <BEMERKUNG>RE-200708/00090</BEMERKUNG>
        <BANK>Volksbank</BANK> aus configArray
        <FIBUKTO>1800</FIBUKTO> aus configArray 
        <EURO>1</EURO>
        <AUSGUTSCHRIFTEN>0.0000</AUSGUTSCHRIFTEN> TODO: whats that
    </ZAHLUNG>
       */
        $gsaTextfileDataAccessor = tx_ptgsasocket_textfileDataAccessor::getInstance();
        $paymentUpdateFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertPayment()
        $paymentUpdateFieldsArr['OPNUMMER']   = 0; // will be overwritten/set by insertPayment()
        $paymentUpdateFieldsArr['BETRAG']     = (double)$amount;
        $paymentUpdateFieldsArr['DATUM']      = $date;
        $paymentUpdateFieldsArr['AUFINR']     = $invoiceDocFieldsArr['NUMMER'];
        $paymentUpdateFieldsArr['BEMERKUNG']  = $note;
        $paymentUpdateFieldsArr['BANK']       = $this->classConfigArr['shopOperatorBankName'];
        $paymentUpdateFieldsArr['EURO']       = '1';
        $paymentUpdateFieldsArr['AUSGUTSCHRIFTEN'] = $fromCreditAmount;
        if ($this->classConfigArr['shopOperatorFinanceAccount']) {
            $paymentUpdateFieldsArr['FIBUKTO']    = $this->classConfigArr['shopOperatorFinanceAccount'];
        }
        trace($paymentUpdateFieldsArr,0,'$paymentUpdateFieldsArr'); //TODO: whats that
        $gsaAccountingTransactionAccessorObj->insertPayment($paymentUpdateFieldsArr);
    }
    

    /** 
     * Set the Ok Flags in invoice Document. If there is allready an outstanding amount it is accounted as discount 
     * 
     * @param   string      the GSA ERP document number of the continued (ERP: "Rechnung/RE")
     * @return  void        
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-11
     */ 
    public function setInvoiceOk($invoiceDocNo) {
        trace('[METHOD] '.__METHOD__);
        
        $invoiceDocUpdateFieldsArr = array(); 
        $paymentUpdateFieldsArr = array(); 
        
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $invoiceDocFieldsArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($invoiceDocNo); // get complete transaction document data
        

        // get related customer object, set booking data
        $customerObj = new tx_ptgsauserreg_customer($invoiceDocFieldsArr['ADRINR']);
        
        // update the related customer's transaction volume ("Umsatz"): ADRESSE.KUMSATZ, KUNDE.UMSATZ, KUNDE.SALDO, KUNDE.LETZTERUMSATZ
        $discount = $invoiceDocFieldsArr['ENDPRB'] - $invoiceDocFieldsArr['BEZSUMME'] - $invoiceDocFieldsArr['GUTSUMME'] - $invoiceDocFieldsArr['SKONTOBETRAG'];
        $amount   = 0;
        $customerObj->registerTransactionPayment($amount, $discount);  

        $invoiceDocFieldsArr['BEZSUMME']        += (double)$amount;
        $invoiceDocFieldsArr['SKONTOBETRAG']    += (double)$discount;

        $invoiceDocUpdateFieldsArr['BEZSUMME']         = $invoiceDocFieldsArr['BEZSUMME'];
        $invoiceDocUpdateFieldsArr['SKONTOBETRAG']     = $invoiceDocFieldsArr['SKONTOBETRAG'];
        $invoiceDocUpdateFieldsArr['LETZTERUSER']      = 'Online-Shop Bezahlgenerator';  // data type: varchar(30)
        $invoiceDocUpdateFieldsArr['LETZTERUSERDATE']  = date('Y-m-d H:i:s');
        
        if (($invoiceDocFieldsArr['BEZSUMME'] + $invoiceDocFieldsArr['GUTSUMME'] + $invoiceDocFieldsArr['SKONTOBETRAG']) >= $invoiceDocFieldsArr['ENDPRB']) {
            trace('Auftrag Ok');
            $invoiceDocUpdateFieldsArr['RESTOK']    = 1;    
            $invoiceDocUpdateFieldsArr['AUFTRAGOK'] = 1;    
        }
        $gsaShopTransactionAccessorObj->updateTransactionDocument($invoiceDocNo, $invoiceDocUpdateFieldsArr);
    }
    
    /** 
     * Processes an free credit memo document record in GSA FSCHRIFT 
     * 
     * @param   string  type of Document 'creditMemo'  or 'cancellation'  
     * @param   double  gross amount for credit memo
     * @param   string  Taxcode for predecessor Invoice   
     * @param   array   Document Array possible predecessor Invoice  (GSA-DB: "FSCHRIFT")
     * @param   tx_ptgsauserreg_customer   Customer Object
     * @param   string  Note stored in docFieldsArr['LETZTERUSER']  
     * @param   string  date booking date for credit Memo or Cancellation   
     * @return  integer UID of the inserted record (GSA-DB: 'FSCHRIFT.NUMMER')  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-06
     */ 
    public function freeDocCreditMemoOrCancellation($type, $amountGross, $taxCode, $invoiceFieldsArr, tx_ptgsauserreg_customer $customerObj, $note='',  $date= '') {
        trace('[METHOD] '.__METHOD__);
        
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        // Freie Gutschrift
        $docFieldsArr = array();    // prepare documentFieldsArray
        $docFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocument(): database ID of the record

        trace($taxCode,0,'$taxCode');
        $schriftNr = $gsaAccountingTransactionAccessorObj->getNextSchriftNr();
        trace($schriftNr,0,'$shriftnr');
        if ($date == '') {
        	$date = date('Y-m-d');
        }
        if($amountGross != $invoiceFieldsArr['ENDPRB']) {
            $taxRate = tx_pttools_finance::getTaxRate($invoiceFieldsArr['ENDPRB'], $invoiceFieldsArr['ENDPRN']);
            trace($taxRate,0,'$taxRate');
            trace($amountGross,0,'$amountGross');
            $amountNet = round(tx_pttools_finance::getNetPriceFromGross($amountGross, $taxRate),2);
        } else {
            $amountNet = $invoiceFieldsArr['ENDPRN'];
        }
        if ($type == 'creditMemo') {
            $erfart = 'GU';
            $docFieldsArr['ERFART']     = '05GU';
            $docFieldsArr['NEUGU']      = 1;
            $docFieldsArr['BEZSUMME']            =  0.000; 
            $tmpDataArr['comment']               = 'Automatisch generierte freie Gutschrift ';
        } else if ($type == 'cancellation') {
        	$erfart = 'ST';
        	$docFieldsArr['ERFART']     = '06ST';
            $docFieldsArr['BEZSUMME']   = - $amountGross; 
            $docFieldsArr['SKONTOBETRAG']   = 0.000; 
            $tmpDataArr['comment']              = 'Automatisch generiertes freies Storno ';
        } else {
            throw new tx_pttools_exception('Wrong type', 3, 'Type :'.$type);
        }
        
        $docFieldsArr['NUMMER']     = 0; // ### TODO: ERP GUI writes a different number based on file OPNR.INI (not multi-user safe!) // will be overwritten/set by insertTransactionDocument(): outstanding items numbers of invoices (ERP: "Offene Posten")
        $docFieldsArr['SCHRIFTNR']  = $schriftNr; 
        # $insertFieldsArr['FSCHRIFT.SCHRIFTNR']  = $this->getNextNumber('VORGANG'); // DO NOT UNCOMMENT (see todo note below)! // continued transaction document number ("fortlaufende Vorgangsnummer")
        $docFieldsArr['GEBUCHT']    = 1; 
        $docFieldsArr['GEDRUCKT']   = 1; 
        $docFieldsArr['AUFTRAGOK']  = 1; 
        $docFieldsArr['RESTOK']     = 1; 
        $docFieldsArr['GEMAILT']    = 0; 
        $docFieldsArr['DATUM']      = $date;
        $docFieldsArr['AUFNR']      = ''; // will be overwritten/set by insertTransactionDocument(): transaction document number (ERP: "Vorgangsnummer")
        $docFieldsArr['ADRINR']     = $customerObj->get_gsauid(); // Adress number from Customer
        $docFieldsArr['LIEFERINR']  = 0; // Nothing to deliver
        $docFieldsArr['ENDPRB']     = $amountGross;
        $docFieldsArr['ENDPRN']     = $amountNet;
        $docFieldsArr['PRBRUTTO']   = $customerObj->get_gsa_prbrutto(); //Gross Flag od customer 

        //$docFieldsArr['BEZSUMME']   = 0.0000; see above
        $docFieldsArr['GUTSUMME']   = 0.0000;
        $docFieldsArr['LTERMIN']    = date('d.m.Y');
        $docFieldsArr['SKONTO1']    = 0.0000;
        $docFieldsArr['SKTAGE1']    = 0.0000;
        $docFieldsArr['SKONTO2']    = 0.0000;
        $docFieldsArr['SKTAGE2']    = 0.0000;
        $docFieldsArr['TAGNETTO']   = $customerObj->get_gsa_tagnetto();
        $docFieldsArr['VERSART']    = $invoiceFieldsArr['VERSART']; //  Type of dispatch
        $docFieldsArr['ZAHLART']    = $customerObj->get_paymentMethod();
        $docFieldsArr['GESRAB']     = 0.0000;;
        $docFieldsArr['GESRABTYPE'] = 0;
        $docFieldsArr['RABATTGR']   = 0.0000;
        $docFieldsArr['PREISGR']    = $customerObj->get_priceGroup();
        $docFieldsArr['AUSLAND']    = $customerObj->get_isForeigner();
        $docFieldsArr['EGAUSLAND']  = $customerObj->get_isEUForeigner();
        $docFieldsArr['FLDC02']     = $invoiceFieldsArr['FLDC02'];  // Invoice Number when overpament (starts with RE-)or coupon code (CO-) if coupon or partnerlink code (PL-)  
        $docFieldsArr['FLDN01']     = $invoiceFieldsArr['FLDN01'];  // dispatchcost 
        $docFieldsArr['FLDN02']     = $invoiceFieldsArr['FLDN02'];  // 
        $docFieldsArr['FLDN03']     = $invoiceFieldsArr['FLDN03'];  // 
        $docFieldsArr['FLDN04']     = $invoiceFieldsArr['FLDN04'];  // 
        $docFieldsArr['INKOPF']     = '';
        $docFieldsArr['INFUSS']     = '';
        $docFieldsArr['BEMERKUNG']  = $tmpDataArr['comment'];
        //$docFieldsArr['FIBUBELEG']  = ''; //TODO don't know where ist is set in GSAUFTRAG 
        $docFieldsArr['EURO']       = 1;
        $docFieldsArr['IVERTNR']    = 0;
        $docFieldsArr['KUNDGR']     = $customerObj->get_gsa_kundgr();
        $docFieldsArr['AUSKASSE']   = 0;
        $docFieldsArr['NAME']       = ($customerObj->get_company() == '' ? $customerObj->get_lastname() : 
                                                                           $customerObj->get_company()); 
        $docFieldsArr['GESGEWICHT'] = 0.0000; # TODO: find out where this comes from and what it is needed for 
        $docFieldsArr['P13B']       = 0;
        $docFieldsArr['RMNEU']      = 1;
        $docFieldsArr['UHRZEIT']    = date('Y-m-d H:i:s'); # TODO: ERP GUI writes nonsense here (e.g. '1899-12-30 13:10:27')
        $docFieldsArr['EGIDENTNR']  = $customerObj->get_euVatId(); 


        // not set in GSAUFTRAG would be nice to set it 
        $docFieldsArr['LETZTERUSER']     = $note;  // data type: varchar(30)
        $docFieldsArr['LETZTERUSERDATE'] = date('Y-m-d H:i:s');
        trace($docFieldsArr,0,'$docFieldsArr');
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            $docRecordId = $gsaShopTransactionAccessorObj->insertTransactionDocument($docFieldsArr, $erfart);
        }        
        
        $this->freePosCreditMemoOrCancellation($type,$amountNet, $taxCode, $docRecordId, $customerObj);

        if ($type == 'cancellation') {
            //decrease accounting balance  
            $customerObj->registerTransactionPayment(-$amountGross, 0);  
            // insert in table ZAHLUNg with negative amount
            $paymentUpdateFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertPayment()
            $paymentUpdateFieldsArr['OPNUMMER']   = -1; // will be overwritten/set by insertPayment()
            $paymentUpdateFieldsArr['BETRAG']     = (double)-$amountGross;
            $paymentUpdateFieldsArr['DATUM']      = $date;
            $paymentUpdateFieldsArr['AUFINR']     = $docRecordId;
            $paymentUpdateFieldsArr['BEMERKUNG']  = $note;
            $paymentUpdateFieldsArr['BANK']       = $this->classConfigArr['shopOperatorBankName'];
            $paymentUpdateFieldsArr['EURO']       = '1';
            $paymentUpdateFieldsArr['AUSGUTSCHRIFTEN'] = '0.000';
            if ($this->classConfigArr['shopOperatorFinanceAccount']) {
                $paymentUpdateFieldsArr['FIBUKTO']    = $this->classConfigArr['shopOperatorFinanceAccount'];
            }
            trace($paymentUpdateFieldsArr,0,'$paymentUpdateFieldsArr'); //TODO: whats that
            $gsaAccountingTransactionAccessorObj->insertPayment($paymentUpdateFieldsArr);
        }
        return $docRecordId;
    }
    
    
    /** 
     * Processes an free credit memo position record in GSA FPOS 
     * 
     * @param   double  gross amount for credit memo
     * @param   string  the GSA ERP document number of the continued (ERP: "Gutschrift/GU")
     * @param   array   posfieldArray of predecessor invoice position  (GSA-DB: "FPOS")
     * @return  string  the position number (GSA-DB: "FPOS.NUMMER") of the inserted position record
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-11
     */ 
    public function freeCreditMemoPos($amountNet, $continuedDocRecordId, $posFieldsArr) {
        trace('[METHOD] '.__METHOD__);
        
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();

        // Freie Gutschrift
        $continuedPosFieldsArr = array(); // prepare continued position data: copy order confirmation position data to continued position data and change/overwrite relevant values
        $continuedPosFieldsArr['AUFINR']     = $continuedDocRecordId;
        $continuedPosFieldsArr['POSINR']     = $posFieldsArr['POSINR'];
        $continuedPosFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocPosition()
        $continuedPosFieldsArr['ALTAUFINR']  = 0; // not set
        $continuedPosFieldsArr['NRESERVIERT']= $posFieldsArr['MENGE'];
        $continuedPosFieldsArr['GESPEICHERT']= 0;
        $continuedPosFieldsArr['POSNR']      = $posFieldsArr['POSNR'];
        $continuedPosFieldsArr['POSART']     = 'DF';
        $continuedPosFieldsArr['ADRINR']     = $posFieldsArr['ADRINR'];
        $continuedPosFieldsArr['ARTNR']       = 'Gutschrift';
        $continuedPosFieldsArr['MENGE']      = 1.0000;
        $continuedPosFieldsArr['MENGE2']     = 1.0000;
        $continuedPosFieldsArr['MENGE3']     = 1.0000;
        $continuedPosFieldsArr['MENGE4']     = 1.0000;
        $continuedPosFieldsArr['RABATT']     = 0.0000;
        $continuedPosFieldsArr['ZU1']        = 0.0000;
        $continuedPosFieldsArr['ZU2']        = 0.0000;
        $continuedPosFieldsArr['EKPREIS']    = 0.0000;
        $continuedPosFieldsArr['EP']         = $amountNet;
        $continuedPosFieldsArr['GP']         = $amountNet;
        $continuedPosFieldsArr['USTSATZ']     = $posFieldsArr['USTSATZ'];
            
        $continuedPosFieldsArr['VEINHEIT'] = $posFieldsArr['VEINHEIT'];
        $continuedPosFieldsArr['PEINHEIT'] = $posFieldsArr['PEINHEIT'];
        $continuedPosFieldsArr['LAGART']   = 0;
        $continuedPosFieldsArr['LEINHEIT'] = $posFieldsArr['LEINHEIT'];
            
        $continuedPosFieldsArr['ZWSUMME']   = 0.0000;
        $continuedPosFieldsArr['MITGESRAB'] = 0;
        $continuedPosFieldsArr['MITSKONTO'] = 0;
        $continuedPosFieldsArr['FIXKOST1']  = 0.0000;
        $continuedPosFieldsArr['FIXKOST2']  = 0.0000;
        $continuedPosFieldsArr['FIXPROZ1']  = 0.0000;
        $continuedPosFieldsArr['FIXPROZ2']  = 0.0000;
        $continuedPosFieldsArr['USTEG']     = $posFieldsArr['USTEG'];
        $continuedPosFieldsArr['USTAUSLAND']= $posFieldsArr['USTAUSLAND'];
        $continuedPosFieldsArr['ZUSTEXT1']  = '';
        $continuedPosFieldsArr['ZUSTEXT2'] = '';
        $continuedPosFieldsArr['SONPREIS']  = 0;
        $continuedPosFieldsArr['ALTTEIL']   = 0;
        $continuedPosFieldsArr['PROVMANU']  = 0;
        $continuedPosFieldsArr['RGARANTIE'] = 0;
        $continuedPosFieldsArr['RREKLAMATION'] = 0;
        $continuedPosFieldsArr['RBEMERKUNG'] = '';
        $continuedPosFieldsArr['RKOSTENPFLICHT'] = 0;
        $continuedPosFieldsArr['RTEXTKUNDE'] = '';
        $continuedPosFieldsArr['RTEXTBERICHT'] = '';
        
        $continuedPosFieldsArr['NRESERVIERT']  = 1.0000;
        $continuedPosFieldsArr['NGEBUCHT']     = 0.0000;
        $continuedPosFieldsArr['GESPEICHERT']  = 1;
        $continuedPosFieldsArr['INSZAEHLER'] = bcmul($posFieldsArr['POSINR'],10000.0000,$this->precision); ### ??? TODO: Gutschriften werden erhöht 

        trace($continuedPosFieldsArr,0,'$continuedPosFieldsArr');
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            $continuedPosRecordId = $gsaShopTransactionAccessorObj->insertTransactionDocPosition($continuedPosFieldsArr);
        }
        return $continuedPosRecordId;
    }


    /** 
     * Processes an free credit memo position record in GSA FPOS 
     * 
     * @param   string  type of FPOS Record
     * @param   double  gross amount for credit memo or Cancellation
     * @param   double  taxRate for amount 
     * @param   string  the GSA ERP document number of the continued (GSA-DB: "Gutschrift/GU")
     * @param   object  Customer Object
     * @return  string  the position number (GSA-DB: "FPOS.NUMMER") of the inserted position record
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-08
     */ 
    public function freePosCreditMemoOrCancellation($type,$amountNet, $taxCode, $docRecordId, $customerObj) {
        trace('[METHOD] '.__METHOD__);
        
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        if ($type == 'GU') {
        } else if ($type == 'ST'){ 
            $posFieldsArr['GEWICHT']      = 0.0000;
        } 


        // Freie Gutschrift
        $posFieldsArr = array(); // prepare continued position data: copy order confirmation position data to continued position data and change/overwrite relevant values
        $posFieldsArr['AUFINR']     = $docRecordId;
        $posFieldsArr['POSINR']     = 1;
        $posFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocPosition()
        $posFieldsArr['ALTAUFINR']  = 0; // not set
        $posFieldsArr['POSNR']      = '#001';
        $posFieldsArr['POSART']     = 'DF';
        $posFieldsArr['ADRINR']     = $customerObj->get_gsauid(); // Adress number from Customer
        $posFieldsArr['MENGE']      = 1.0000;
        $posFieldsArr['MENGE2']     = 1.0000;
        $posFieldsArr['MENGE3']     = 1.0000;
        $posFieldsArr['MENGE4']     = 1.0000;

        $posFieldsArr['RABATT']     = 0.0000;
        $posFieldsArr['ZU1']        = 0.0000;
        $posFieldsArr['ZU2']        = 0.0000;
        $posFieldsArr['EKPREIS']    = 0.0000;
        $posFieldsArr['EP']         = $amountNet;
        $posFieldsArr['GP']         = $amountNet;
        $posFieldsArr['USTSATZ']     = $taxCode; 
            
        $posFieldsArr['VEINHEIT'] = 1.000;
        $posFieldsArr['PEINHEIT'] = 1.000;
        $posFieldsArr['LAGART']   = 0;
        $posFieldsArr['LEINHEIT'] = 1.000;
            
        $posFieldsArr['ZWSUMME']   = 0.0000;
        $posFieldsArr['MITGESRAB'] = 0;
        $posFieldsArr['MITSKONTO'] = 0;
        $posFieldsArr['FIXKOST1']  = 0.0000;
        $posFieldsArr['FIXKOST2']  = 0.0000;
        $posFieldsArr['FIXPROZ1']  = 0.0000;
        $posFieldsArr['FIXPROZ2']  = 0.0000;
        $posFieldsArr['USTEG']     = 0.0000;
        $posFieldsArr['USTAUSLAND'] = '00'; //TODO überprüfen
        $posFieldsArr['ZUSTEXT1']  = '';
        $posFieldsArr['ZUSTEXT2'] = '';
        $posFieldsArr['SONPREIS']  = 0;
        $posFieldsArr['ALTTEIL']   = 0;
        $posFieldsArr['PROVMANU']  = 0;
        $posFieldsArr['RGARANTIE'] = 0;
        $posFieldsArr['RREKLAMATION'] = 0;
        $posFieldsArr['RBEMERKUNG'] = '';
        $posFieldsArr['RKOSTENPFLICHT'] = 0;
        $posFieldsArr['RTEXTKUNDE'] = '';
        $posFieldsArr['RTEXTBERICHT'] = '';
        
        $posFieldsArr['NRESERVIERT']  = 1.0000;
        $posFieldsArr['NGEBUCHT']     = 0.0000;
        $posFieldsArr['GESPEICHERT']  = 1;
        $posFieldsArr['INSZAEHLER'] = bcmul($posFieldsArr['POSINR'],10000.0000,$this->precision);  

        trace($posFieldsArr,0,'$posFieldsArr');
        if (self::TEST_MODE == false) {
            // write record if not TEST_MODE
            $posRecordId = $gsaShopTransactionAccessorObj->insertTransactionDocPosition($posFieldsArr);
        }
        return $posRecordId;
    }


    /**
     * This method retrieves customerInfomation about payment resp. creditBalance
     * 
     * @param   object      Object of the customer
     * @return  double      OustandingAmount or credit for this Customer  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-27
     */ 
    public function getCustomerPaymentAmount($customerObj) {
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $outstandingAmount = $customerObj->getOutandingAmount();
        $creditBalance = $gsaAccountingTransactionAccessorObj->selectCreditMemoCustomerAmount($customerObj->get_gsauid(),true); 
        return bcsub($outstandingAmount ,$creditBalance ,$this->precision);
    }
    

    /**
     * This method retrieves last Payment Date of the customer
     * 
     * @param   string      Payment Method 
     * @param   integer     uid of customer in gsa
     * @return  string      Date of Last Payment for this costomer or for the shop if no uid given
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-27
     */ 
    public function getLastPaymentDate($paymentMethod='', $gsaUid = 0) {
        return $gsaAccountingTransactionAccessorObj->selectLastPaymentDate($paymentMethod='', intval($gsaUid));
    }
    

    /**
     * This method gets the outstanding Amount for one Customer    
     * 
     * @param   integer    adress uid of customer in gsa
     * @return  double     outstanding Amount for the customer 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-30
     */ 
    public function getOutstandingAmountCustomer($gsaUid) {
        trace('[METHOD] '.__METHOD__);
        $gsaAccountingTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $outstandingAmount=0;
        $outstandingItemArr = $gsaAccountingTransactionAccessorObj->selectInvoicesByCustomer($gsaUid, '04RE', true);
        foreach ($outstandingItemArr as $itemArr) {
            if (($itemArr['relatedDocNo'] && $this->isOutstanding($itemArr['paymentMethod'], $itemArr['relatedDocNo']) )||
                !$itemArr['relatedDocNo']) {
                $outstandingAmount = bcadd($outstandingAmount,$this->getOutstandingAmountInvoice($itemArr),$this->precision);
                trace($outstandingAmount,0,'$outstandingAmount');
            }
        }            
        return $outstandingAmount;
    }


    /**
     * This method gets the credit Balance for one Customer    
     * 
     * @param   integer    adress uid of customer in gsa
     * @param   integer    (optional) orderWrapper uid of possible current order 
     * @return  double     outstanding Amount for the customer 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-05
     */ 
    public function getCreditBalance($gsaUid, $orderWrapperUid=0) {
        trace('[METHOD] '.__METHOD__);
        $creditBalance = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->selectCreditMemoCustomerAmount($gsaUid,true);
        $reservedCredit = tx_ptgsaaccounting_orderCreditBalanceAccessor::getInstance()->selectReservedCreditBalanceCustomer($gsaUid,$orderWrapperUid);
        trace( $reservedCredit,0,' $reservedCredit');
        $creditBalance = bcsub($creditBalance,$reservedCredit,$this->precision);
        return $creditBalance;
    }


    /**
     * This method calculates outstandinAmount for one invoice   
     * 
     * @param   object     tx_ptgsaaccounting_erpDocument invoice 
     * @return  double     outstandingAmount
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-27
     */ 
    public function getOutstandingAmountInvoice(tx_ptgsaaccounting_erpDocument $invoice) {
        trace($this->precision,0,'this->precision');
        trace($invoice,0,'$invoice');
        $outstandingAmount = bcsub($invoice->get_amountGross(),
                                bcadd($invoice->get_credit(),
                                    bcadd($invoice->get_payment(),$invoice->get_discount(),$this->precision),
                                $this->precision),
                             $this->precision);
        trace($outstandingAmount,0,'$outstandingAmount');
        return $outstandingAmount;
    }

        
    /**
     * checks for paymentMethod DEBIT in DTABUCHREL if already transfered and not rejected  
     * @param   string  paymentMethod from GSA   
     * @param   string  Document Number from GSA   
     * @return  boolean true if Invoice is outstanding  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-30
     */
   public function isOutstanding($paymentMethod, $relatedDocNo) {
        trace('[METHOD] '.__METHOD__);
        $outstanding = true;
        if ($paymentMethod == self::PM_DEBIT) {
            $dtabuchrel = new tx_ptgsaaccounting_dtabuchrel(0,$relatedDocNo);
            $outstanding = $dtabuchrel->isOutstanding();
        }
        return $outstanding;
    }
    
    /**
     * checks if relatedDocNo has an cancellation Document  
     * @param   string  Document Number from GSA   
     * @return  boolean true if Invoice is outstanding  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2009-08-05
     */
   public function hasCancellation($relatedDocNo) {
        trace('[METHOD] '.__METHOD__);
        $hasCancellation = false;
        $continuedDocumentArray = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->selectContinuedDocuments($relatedDocNo);
        foreach ($continuedDocumentArray as $continuedDocument) {
            if ($continuedDocument['documentType'] == '06ST') {
                $hasCancellation = true;
                trace($relatedDocNo,0,'Cancellation');
                break;
            }
        }
        return $hasCancellation;
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_gsaTransactionHandler.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_gsaTransactionHandler.php']);
}

?>