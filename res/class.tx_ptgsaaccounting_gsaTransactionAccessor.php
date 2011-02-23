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
 * Database accessor class for GSA transactions (ERP: "Vorgang") of the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-15 
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Inclusion of extension specific resources
 */

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_gsaDbAccessor.php'; // parent class for all GSA database accessor classes
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_gsaTransactionAccessor.php';  // GSA Shop database accessor class for GSA transactions



/**
 *  Database accessor class for GSA transactions (ERP: "Vorgang"), based on GSA database structure
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-07
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsaaccounting_gsaTransactionAccessor extends tx_ptgsasocket_gsaDbAccessor implements tx_pttools_iSingleton {
    
    
    /**
     * Constants
     */
    const PM_CCARD = 'Kreditkarte';
    const PM_INVOICE = 'Rechnung';
    const PM_DEBIT = 'DTA-Buchung';
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsaaccounting_gsaTransactionAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsaaccounting_gsaTransactionAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-15
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        return self::$uniqueInstance;
        
    }
    
    
    
    /***************************************************************************
     *   GSA DB RELATED METHODS
     **************************************************************************/
    
    /**
     * Returns date and paymentMethod as array if Invoice is payed. Otherwise NULL is given back
     *
     * @param   string      the predecessor transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgaengervorgangsnummer") to check for its Cancellations
     * @return  mixed       array with date as timestamp and type ('cm', 'bt', 'cc', 'dd') of payment NULL if not payed or credited
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-04
     */
    public function isPayed($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'F.LETZTERUSERDATE as date, F.NUMMER as docId , F.AUFNR as relatedDocNo, '.
                   'F.ZAHLART as paymentMethod, F.ENDPRN as amountNet, '.
                   'F.ENDPRB as amountGross, F.ENDPRN as amountNet, '.
                   'F.GUTSUMME as credit , F.BEZSUMME as payment, F.SKONTOBETRAG as discount';
        $from    = $this->getTableName('FSCHRIFT').' AS F';
        $where   = 'F.AUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo,$from).
                   # So steht es in GSA Auftrag wenn verbucht
                   ' AND AUFTRAGOK=1 ';
        $groupBy = '';
        $orderBy = '';
        $limit = '';
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // get more Data if AuftragOk
        $a_result = array();
        $a_rowInvoice = $this->gsaDbObj->sql_fetch_assoc($res);
        trace($a_rowInvoice,0,'$a_rowInvoice');

        if ($a_rowInvoice['docId']) {
            if ($a_rowInvoice['payment'] >0) {
                // Suche Zahlungen
                // query preparation
                $select  = 'DATUM as DATE, ENDPRB as amount';
                $from    = $this->getTableName('FSCHRIFT');
                $where   = 'ERFART = "05GU" '.
                           # So steht es in GSA Auftrag wenn verbucht
                           'AND ALTERFART = "04RE" AND AUFTRAGOK=1 AND GEBUCHT=1 '.
                           'AND ALTAUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo, $from);
        
            } else if ($a_rowInvoice['credit'] >0) {
                // Suche Gutschriften
                $select  = 'DATUM as date, BETRAG as amount';
                $from    = $this->getTableName('ZAHLUNG');
                $where   = 'AUFINR = '.$this->gsaDbObj->fullQuoteStr($a_rowInvoice['docId'],$from);
                $groupBy = '';
                $orderBy = ' DATUM';
                $limit = '';
                $a_result['paymentMethod'] = 'cm';
            }
            // exec query using TYPO3 DB API
            $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
            if ($res == false) {
                throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
            } 

            if ($this->gsaDbObj->sql_num_rows($res) > 0) {
                while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
                    // if enabled, do charset conversion of all non-binary string data 
                    $payDate = $a_row['date'];
                }
            }
            $this->gsaDbObj->sql_free_result($res);
            
            // date as unix timestamp
            $a_result['date'] = strtotime($payDate != '' ? $payDate : $a_rowInvoice['date']);  
            
            trace(date('Y-m-d',$a_result['date']),0,'date formated');
            trace($a_rowInvoice['paymentMethod'],0,'a_rowInvoice[paymentMethod]');
            if (!$a_result['paymentMethod']) { 
                switch ($a_rowInvoice['paymentMethod']) {
                    case self::PM_INVOICE:                
                        $a_result['paymentMethod'] = 'bt';
                        break;
                    case self::PM_DEBIT:                
                        $a_result['paymentMethod'] = 'dd';
                        break;
                    case self::PM_CCARD:                
                        $a_result['paymentMethod'] = 'cc';
                        break;
                }
            }
            trace($a_result,0,'$a_result');
        } else {
            unset($a_result);
        }        
        return $a_result;
        
    }
    
    /**
     * Inserts a transaction document record into the GSA DB-table 'FSCHRIFT' and returns the inserted record's UID - this requires all data to be inserted passed in an array with array keys exactly like the GSA FSCHRIFT database table field names (except 'NUMMER', 'OPNUMMER', 'AUFNR')
     * 
     * @param   array       all data to be inserted, prepared in an array with array keys exactly like the GSA FSCHRIFT database table field names (except 'NUMMER', 'OPNUMMER' which will be created within this method)
     * @return  integer     UID of the inserted record ('FSCHRIFT.NUMMER') 
     * @throws  tx_pttools_exception   if the first param containing the data to insert is not a valid array
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-30
     */
    public function insertPayment($paymentArr) {
        
        $insertFieldsArr = array();
        $extConfigArr = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ptgsashop.'];
        
        #trace($fschriftFieldsArr, 0, '$fschriftFieldsArr'); 
        if (!is_array($paymentArr) || empty($paymentArr)) {
            throw new tx_pttools_exception('Wrong payment data format', 3, 'Data to insert in ZAHLUNG is not an array or is an empty array');
        }
        
        // query preparation
        $table = $this->getTableName('ZAHLUNG');
        foreach ($paymentArr as $key=>$value) {
            if (!is_null($value)) {
                $insertFieldsArr[$table.'.'.$key] = $value; // prefix insert field names with table name to prevent SQL errors with GSA DB fields name like SQL reserved words (e.g. 'MATCH')
            } else {
                unset($insertFieldsArr[$table.'.'.$key]); // this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
            }
        }
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // get unique identifiers (overwrite possibly existing array keys)
        $insertFieldsArr[$table.'.NUMMER']     = $this->getNextId($table); // database ID of the record
        if($paymentArr[OPNUMMER] != '-1') {
            $insertFieldsArr[$table.'.OPNUMMER']   = $this->getNextId($extConfigArr['gsaVirtualTableOpNr'], $extConfigArr['gsaVirtualOpNrMin']); // outstanding items numbers of invoices (ERP: "Offene Posten")
        } else {
            $insertFieldsArr[$table.'.OPNUMMER']   = 0; // No OPNUMMER if payment from Cancellation
        } 
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($insertFieldsArr,0,'$insertFieldsArr'); 
        return $insertFieldsArr[$table.'.NUMMER'];
        
    }
     
    /**
     * Returns a set of transaction documents (ERP: "Vorgaenge") from the GSA database.
     *
     * @param   string      CSL (comma seperated list) of GSA specific abbreviations of the requested transaction document types (ERP: "Erfassungsart"): e.g. '04RE' for invoices, '05GU' for credit notes or '06ST' for cancellations
     * @param   string      (optional) start date to restrict the list results in format YYYY-MM-DD (PHP: date('Y-m-d')), given date is included in results
     * @param   string      (optional) end date to restrict the list results in format YYYY-MM-DD (PHP: date('Y-m-d')), given date is included in results
     * @return  array       twodimensional array with selected data of all specified transaction documents
     * @throws  tx_pttools_exception   if first param is invalid
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-20
     */
    public function selectTransactionList($docType, $dateStart='', $dateEnd='') {
        
        // explode doctype CSL into an array
        $docTypeArr = tx_pttools_div::returnArrayFromCsl($docType);
        if (count($docTypeArr) < 1) {
            throw new tx_pttools_exception('Wrong param', 3, 'Wrong param $docType');
        }
        
        // query preparation
        $select  = 'ERFART, DATUM, AUFNR, ENDPRB, ENDPRN, OPNUMMER, SKONTOBETRAG';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = '(';
        for ($i=0; $i<count($docTypeArr); $i++) {
            $where .= ($i == 0 ? '' : ' OR ').'ERFART = '.$this->gsaDbObj->fullQuoteStr($docTypeArr[$i], $from);
        }
        $where  .= ') '.
                   ($dateStart != '' ? 'AND DATUM >= '.$this->gsaDbObj->fullQuoteStr($dateStart, $from).' ' : '').
                   ($dateEnd != '' ? 'AND DATUM <= '.$this->gsaDbObj->fullQuoteStr($dateEnd, $from).' ' : '').
                   'AND GEBUCHT = 1';
        $groupBy = '';
        $orderBy = 'DATUM, AUFNR';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        if ($this->gsaDbObj->sql_num_rows($res) > 0) {
            while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
                // if enabled, do charset conversion of all non-binary string data 
                if ($this->charsetConvEnabled == 1) {
                    $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
                }
                $a_result[] = $a_row;
            }
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }  
     
    /**
     * Returns the Cancellation from a given predecessor transaction document number (ERP: "Vorgangsnummer")
     *
     * @param   string      the predecessor transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgaengervorgangsnummer") to check for its Cancellations
     * @return  double      The amount for Cancellation from the given predecessor document 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-16
     */
    public function selectPaymentAmount($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'BETRAG as amount';
        $from    = $this->getTableName('ZAHLUNG');
        $where   = 'BEMERKUNG = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo, $from);
        
        $amount = $this->selectAmount($select, $from, $where);
        return $amount;
        
    }
    
    /**
     * Returns all open positions for an given invoiceDocNo  
     *
     * @param   string  document Number of Document Record in GSAUFTRAG  
     * @return  array   Outstanding positions  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-15     
     */
    public function selectPositions($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        trace($relatedDocNo,0,'$relatedDocNo');
        // query preparation
        $select  = 'POS.AUFINR as invoiceUid, POS.NUMMER as posUid , POS.POSINR as posNo, '.
                   'POS.ARTINR as articleId, '.
                   'POS.EP as unitPrice, POS.GP as totalPrice, '.
                   'POS.GUTSCHRIFT as noCredit, POS.STORNO as noCancellation, POS.MENGE as quantity, '. 
                   'POS.RABATT as discount ';
        $from    = 'FPOS AS POS'. 
                   ' INNER JOIN '.$this->getTableName('FSCHRIFT').' AS INV ON INV.NUMMER = POS.AUFINR INNER JOIN '.$this->getTableName('ARTIKEL').' as ART ON POS.ARTINR = ART.NUMMER';
        $where   = 'INV.AUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo,$from);
        $groupBy = '';
        $orderBy = 'POS.POSINR';
        $limit = '';
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        if ($this->gsaDbObj->sql_num_rows($res) > 0) {
            while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
                // if enabled, do charset conversion of all non-binary string data 
                if ($this->charsetConvEnabled == 1) {
                    $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
                }
                $a_result[] = $a_row;
            }
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
    }
    
    /**
     * TODO should be obsolete
     * Returns all open positions for an given invoiceDocNo  
     *
     * @param   string  invoice Number of Document Record in GSAUFTRAG  
     * @return  array   Outstanding positions  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-19
     */
    public function selectOutstandingPositions($invoiceDocNo) {
        trace('METHOD] '.__METHOD__);
        trace($invoiceDocNo,0,'$invoiceDocNo');
        // query preparation
        $select  = 'POS.AUFINR as invoiceUid, POS.NUMMER as posUid , POS.POSINR as posNo, '.
                   'POS.EP as unitPrice, POS.GP as totalPrice, '.
                   'POS.GUTSCHRIFT as noCredit, POS.STORNO as noCancellation, POS.MENGE as quantity, '. 
                   'ART.NUMMER as articleUid, '.
                   'ART.MATCH as articleName, '.
                   'INV.DATUM as date, '.
                   'INV.AUFNR as invoiceDocNo, INV.PRBRUTTO as isGross, '.
                   'INV.FLDN01 as amountShipping, INV.ENDPRB as amountGross, INV.ENDPRN as amountNet, '.
                   'INV.GUTSUMME as amountCredit , INV.BEZSUMME as amountPayment, INV.SKONTOBETRAG as amountDiscount';
        $from    = $this->getTableName('FPOS').' AS POS'. 
                   ' INNER JOIN '.$this->getTableName('FSCHRIFT').' AS INV ON INV.NUMMER = POS.AUFINR INNER JOIN '.$this->getTableName('ARTIKEL').' as ART ON POS.ARTINR = ART.NUMMER';
        $where   = 'INV.AUFNR = '.$this->gsaDbObj->fullQuoteStr($invoiceDocNo,$from);
        $groupBy = '';
        $orderBy = 'POS.POSINR';
        $limit = '';
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        if ($this->gsaDbObj->sql_num_rows($res) > 0) {
            while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
                // if enabled, do charset conversion of all non-binary string data 
                if ($this->charsetConvEnabled == 1) {
                    $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
                }
                $a_result[] = $a_row;
            }
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
                   

    }

    
    /**
     * select ERP Document by Id or by related Document Number   
     *
     * @param   integer (optional) Document Id 
     * @param   string  (optional) related Document Number   
     * @param   string  (optional) Sorting for query.  If nothing is specified, sorting will be done for 'F.AUFTRAGOK, F.DATUM, F.AUFNR'  
     * @throws  Exeption if none of the Parameters is specified  
     * @return  array   ERP Docuemnt   
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-10-23
     */
    public function selectDocument($docId=0,$relatedDocNo='') {
        $select = $this->getSelectClauseErpDocument();
        $from = $this->getTableName('FSCHRIFT').' AS F';
        $where = '1 ';
        if ($docId > 0) {
            $where .= 'AND F.NUMMER = '.$docId;
        } else if ($relatedDocNo != '') {
            $where .= 'AND F.AUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo,$from);
        } else {
        	throw new tx_pttools_exception('Error in selectDocument either Document Id or ERP related ERP Document Number has to be specified',1);
        }
        $groupBy  = '';
        $orderBy  = '';
        $limit    = '';
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res, $this->gsaDbObj, array('message'=>'Query failed selectDocument'));
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res); 
        return $a_row;
        
    }
    
    /**
     * Returns all open invoices from given Search Array  
     *
     * @param   array   (optional) search Array  
     * @param   string  (optional) document Type, if not specified the document type for invoice is used   
     * @param   string  (optional) Sorting for query.  If nothing is specified, sorting will be done for 'F.AUFTRAGOK, F.DATUM, F.AUFNR'  
     * @return  array   Outstanding items  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-30
     */
    public function selectOutstandingItems($searchArr=array(),$documentType='',$orderBy='', $customerId=0) {
        #$GLOBALS['trace'] = 1;
    	trace('METHOD] '.__METHOD__);
        
        trace($searchArr,0,'$searchArr');
        // query preparation
        
        $select  = $this->getSelectClauseErpDocument();
        $from    = $this->getTableName('FSCHRIFT').' AS F'. 
                   ' INNER JOIN '.$this->getTableName('ADRESSE').' AS ADR ON ADR.NUMMER = F.ADRINR ';
        if ($documentType != '') {
            $where   = 'F.ERFART = '.$this->gsaDbObj->fullQuoteStr($this->documentTypeToErp($documentType),$from);
        } else {
            $where   = 'F.ERFART = '.$this->gsaDbObj->fullQuoteStr('04RE',$from);
        }
        $where  .= ' AND F.GEBUCHT=1 ';
        
        if ($searchArr['searchAll']== false) {
            $where .= ' AND F.AUFTRAGOK != 1 AND F.ENDPRB != 0'; // AUFTRAGOK if all payed or nothing has to be payed
        }
        
        if ($customerId != 0) {
            $where .= ' AND ADR.NUMMER = '.intval($customerId); // CustomerId set
        }
        
        
        if ($this->charsetConvEnabled == 1) {
            $searchArr = tx_pttools_div::iconvArray($searchArr ,$this->siteCharset, $this->gsaCharset );
            trace($searchArr,0,'$searchArr');
        }

        $sFieldsArr= array();
        if ($searchArr['searchText']) {
            if ($searchArr['searchExact'] == false) {
                $searchText = $this->gsaDbObj->fullQuoteStr('%'.strtoupper($searchArr['searchText'].'%'),$from);
                $searchOp = ' like '; 
                $searchFunction = 'upper('; 
                $searchFunctionClose = ')'; 
            } else {
                $searchText = $this->gsaDbObj->fullQuoteStr($searchArr['searchText'],$from);
                $searchOp = ' = ';
                $searchFunction = ''; 
                $searchFunctionClose = ''; 
            }
            if ($searchArr['searchFields'] == 'sf_all') {
                $sFieldsArr[] = 'ADR.NAME';
                $sFieldsArr[] = 'ADR.VORNAME';
                $sFieldsArr[] = 'ADR.KONTAKT';
                $sFieldsArr[] = 'ADR.KUNDNR';
                $sFieldsArr[] = 'F.DATUM';
                $sFieldsArr[] = 'F.ENDPRB';
                $sFieldsArr[] = 'F.AUFNR';
                $sFieldsArr[] = 'F.ZAHLART';
            } else {
                $fields = array();
                $fields = explode('#',$searchArr['searchFields']);
                trace($searchArr['searchFields'],0,'searchFields');
                foreach ($fields as $fieldname) {
                    switch ($fieldname) {
                        case 'sf_firstname':
                            $sFieldsArr[] = 'ADR.VORNAME';
                            $sFieldsArr[] = 'ADR.KONTAKT';
                            break;
                        case 'sf_lastname':
                            $sFieldsArr[] = 'ADR.NAME';
                            $sFieldsArr[] = 'ADR.KONTAKT';
                            break;
                        case 'sf_gsacustomerId':
                            $sFieldsArr[] = 'ADR.KUNDNR';
                            break;
                        case 'sf_date':
                            $sFieldsArr[] = 'F.DATUM';
                            break;
                        case 'sf_amount':
                            $sFieldsArr[] = 'F.ENDPRB';
                            break;
                        case 'sf_relatedDocNo':
                            $sFieldsArr[] = 'F.AUFNR';
                            break;
                        case 'sf_paymentMethod':
                            $sFieldsArr[] = 'F.ZAHLART';
                            break;
                    } 
                }
            }
            $wherePart = '';
            if ($searchArr['sf_paymentMethod']) {
            	$paymentMethod = $this->paymentMethodToErp($searchArr['sf_paymentMethod']);
            } else {
                $paymentMethod = $this->paymentMethodToErp($searchText);
            	
            }
             
            trace($sFieldsArr,0,'$sFieldArr');
            foreach ($sFieldsArr as $fieldname) {
                trace($fieldname,0,'$fieldname');
                trace($wherePart,0,'$wherePart');
                trace($searchText,0,'$searchText');
                if ($fieldname == 'F.ENDPRB') {
                    if (is_numeric($searchArr['searchText'])) {
                        $wherePart .= $wherePart != '' ? ' OR ' : '';
                        $wherePart .= $fieldname . '=' .  str_replace('%','',str_replace(',','.',$searchText));
                    } 
                } else if ($fieldname == 'F.DATUM') {
                    $wherePart .= $wherePart != '' ? ' OR ' : '';
                    $wherePart .= $fieldname . '=' .   $this->gsaDbObj->fullQuoteStr($searchArr['searchText'],$from); 
                } else if ($fieldname == 'F.ZAHLART' && $this->paymentMethodToErp($searchText)!='') {
                    $wherePart .= $wherePart != '' ? ' OR ' : '';
                    $wherePart .= $fieldname .'='.  $this->gsaDbObj->fullQuoteStr($this->paymentMethodToErp($searchText),$from); 
                                    	
                } else {
                    $wherePart .= $wherePart != '' ? ' OR ' : '';
                    $wherePart .= $searchFunction.$fieldname.$searchFunctionClose. $searchOp .  $searchText; 
                }
            }
            $where .= $wherePart != '' ?' AND ('.$wherePart.')' : ''; 
        }
        if ($fieldname == 'F.ZAHLART' && $paymentMethod!='') {
            $where .= ' AND '.$fieldname .'='.  $this->gsaDbObj->fullQuoteStr($paymentMethod,$from); 
        }
        if ($documentType == '') {
            $orderBy = 'F.AUFTRAGOK, F.DATUM, F.AUFNR';
        }
        
        return $this->selectArray($select, $from, $where, '', $orderBy, '');
        
    }


    /**
     * Returns Payments for Invoice 
     *
     * @param   integer doc id of invoice in GSA  
     * @return  array   Invoices  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-06-25
     */
    public function selectPaymentByInvoice($docId) {
        trace('METHOD] '.__METHOD__);
        $select  = 'Z.DATUM as date, Z.NUMMER as paymentId , Z.AUFINR as docId, '.
                   'Z.MAHNGEB as dunningAmount, Z.BETRAG as amount, Z.AUSGUTSCHRIFTEN as creditAmount ';
        $from    = $this->getTableName('ZAHLUNG').' AS Z'; 
        $where   = 'Z.AUFINR = '.intval($docId); 
        $orderBy = 'DATUM DESC, AUFINR DESC';
        return $this->selectArray($select, $from, $where, '', $orderBy, '');
    }


    /**
     * Returns Ids of Customer which have due Invoices
     *
     * @param   string  (optional) paymentMethod   
     * @param   boolean (optional) useDunning   consider dunning data (dunningLevel, dunningDueDate and waiting period of customer) for decision if item is due
     * @return  array   Ids of Customers
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-23
     */
    public function selectDueCustomers($paymentMethod='', $useDunning = false) {
        trace('METHOD] '.__METHOD__);
        $select  = 'f.ADRINR AS id';
        $from    = $this->getTableName('FSCHRIFT').' f INNER JOIN '.$this->getTableName('ADRESSE').' a ON f.ADRINR = a.NUMMER INNER JOIN '.$this->getTableName('DEBITOR').' d ON a.NUMMER = d.ADRINR'; 
        
        $where   = 'f.ERFART = "04RE" ';
        if ($paymentMethod != '') {
            $where .= 'AND f.ZAHLART='.$this->gsaDbObj->fullQuoteStr($this->paymentMethodToErp($paymentMethod),'FSCHRIFT').' ';
        }
        $where  .= 'AND f.GEBUCHT = 1 '.
                   'AND f.AUFTRAGOK != 1 AND f.ENDPRB != 0 ' .                   
                   'AND TO_DAYS(NOW()) > (TO_DAYS(f.DATUM) + f.TAGNETTO'.($useDunning == true ? ' + d.MAHNTAGE) ' : ') '); 
        if ($useDunning == true) {
            $where .= 'AND ((f.MKZ > 0 AND f.MKZ < 3 AND NOW() > f.MAHNFRIST) OR f.MKZ = 0 OR ISNULL(f.MKZ)) ';
        }
        $orderBy = '';
        $groupBy = 'a.NUMMER';
        $limit = '';
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === FALSE) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        while ($row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            $idArr[] = $row['id'];
        }
        $this->gsaDbObj->sql_free_result($res);
        return $idArr;
    }


    /**
     * Returns Transactions of Customer 
     *
     * @param   integer address id of customer in GSA  
     * @param   string  Type of transaction (valid values ('04RE' Invoices, '05GU' crredits, '06ST' cancellation)   
     * @param   boolean Flag if only oustandingItems have selected  
     * @param   string  alternative order by clause   
     * @return  array   Payments for Customer  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-28
     */
    public function selectInvoicesByCustomer($gsaUid, $transactionType='', $outstandingItems=false, $orderBy = '') {
        trace('METHOD] '.__METHOD__);
        $select  = 'F.DATUM as date, F.NUMMER as docId , F.AUFNR as relatedDocNo, '.
                   'F.ZAHLART as paymentMethod,F.ENDPRB as amountGross, F.ENDPRN as amountNet, '.
                   'F.GUTSUMME as credit , F.BEZSUMME as payment, F.SKONTOBETRAG as discount,'.
                   'F.AUFTRAGOK as ok';
                   'A.NUMMER as customerId';
        $from    = $this->getTableName('FSCHRIFT').' AS F'; 
        $where   = 'F.ERFART = '.$this->gsaDbObj->fullQuoteStr($transactionType,'FSCHRIFT'). 
                   ' AND F.ADRINR = '.intval($gsaUid) ;
        if($outstandingItems == true ) {
            $where .= ' AND AUFTRAGOK != 1';
        }                           
        if ($orderBy == '') {
        	$orderBy = 'AUFTRAGOK ASC, DATUM DESC, AUFNR DESC';
        }
        return $this->selectArray($select, $from, $where, '', $orderBy, '');
    }

    
    /**
     * Returns Overpayment from Customer 
     *
     * @param   integer address id of customer in GSA  
     * @param   string  (optional) Document number for given Invoice  
     * @param   boolean (optional) Flag if only outstanding Credits  
     * @return  array   Invoices with overpayment  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-01-11
     */
    public function selectOverpaymentByCustomer($gsaUid,$relatedDocNo = '',$outstanding = true) {
        trace('METHOD] '.__METHOD__);
        $select  = 'F.DATUM as date, F.NUMMER as docId , F.AUFNR as relatedDocNo, '.
                   'F.ZAHLART as paymentMethod, F.ENDPRB as overpayment, F.ENDPRN as amountNet, '.
                   'F.GUTSCHRIFTZAHLUNG as creditPayment,'.
                   'F.FLDC02 as invoiceDocNo,'.
                   'F.AUFTRAGOK as ok';
        $from    = $this->getTableName('FSCHRIFT').' AS F'; 
        $where   = 'F.ERFART = '.$this->gsaDbObj->fullQuoteStr('05GU',$from). 
                   ' AND ALTNUMMER IS NULL '.
                   ' AND F.ADRINR = '.intval($gsaUid) ;
		if ($outstanding == true) {
        	$where .= ' AND (ENDPRB - GUTSCHRIFTZAHLUNG >0  OR GUTSCHRIFTZAHLUNG is NULL)';
		} else {
        	$where .= ' AND (ENDPRB  > 0)';
		}
		if ($relatedDocNo != '') {
        	$where .= ' AND FLDC02 LIKE '.$this->gsaDbObj->fullQuoteStr($relatedDocNo.'%',$from);
			
		} else {
        	$where .= ' AND FLDC02 LIKE '.$this->gsaDbObj->fullQuoteStr('RE-%',$from);
		}
			
        $orderBy = 'AUFTRAGOK, ERFART, DATUM, AUFNR';
        $overPaymentArray = $this->selectArray($select, $from, $where, '', $orderBy, ''); 
        $i = 0;
        while ($overPaymentArray[$i]['docId']) {
            $select  = 'F.DATUM as date, F.ENDPRB as amountGross';
            $from    = $this->getTableName('FSCHRIFT').' AS F'; 
            $where   = 'F.AUFNR = '.$this->gsaDbObj->fullQuoteStr($overPaymentArray[$i]['invoiceDocNo'],$from); 
            $invoiceArray = $this->selectArray($select, $from, $where, '', $orderBy, '');
            trace($invoiceArray,0,'$invoiceArray');
            $overPaymentArray[$i]['paymentAmount'] = $invoiceArray[0]['amountGross']; 
            $overPaymentArray[$i]['invoiceDate'] = $invoiceArray[0]['date']; 
            $i++;
        }
        trace($overPaymentArray,0,'$overPaymentArray');
        return $overPaymentArray;
    }


    /**
     * Returns invoices of given amount or possible searchText 
     *
     * @param   double  amount  
     * @param   string  possible SerachText  
     * @param   boolean Flag if only oustandingItems  
     * @return  array   Invoices  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-10-04
     */
    public function selectInvoices($amount, $searchText, $outstandingItems) {
        trace('METHOD] '.__METHOD__);
        
        trace($searchText,0,'$searchText');
        // query preparation
        $select  = 'ADR.NAME as lastname, ADR.VORNAME as firstname, '.
                   'F.DATUM as date, F.NUMMER as docId , F.AUFNR as relatedDocNo, '.
                   'F.ZAHLART as paymentMethod,F.ENDPRB as amountGross, F.ENDPRN as amountNet, '.
                   'F.GUTSUMME as credit , F.BEZSUMME as payment, F.SKONTOBETRAG as discount,'.
                   'F.AUFTRAGOK as ok';
        $from    = $this->getTableName('FSCHRIFT').' AS F'.
                   ' INNER JOIN '.$this->getTableName('ADRESSE').' AS ADR ON ADR.NUMMER = F.ADRINR ';

        $where   = 'F.ERFART = '.$this->gsaDbObj->fullQuoteStr('04RE',$from);
        if($outstandingItems == true ) {
            $where .= ' AND AUFTRAGOK != 1';
        }                           

        $sFieldsArr= array();
        if ($searchText) {
            $searchText = $this->gsaDbObj->fullQuoteStr('%'.strtoupper($searchText).'%',$from);
            $searchOp = ' like '; 
            $searchFunction = 'upper('; 
            $searchFunctionClose = ')'; 
            $sFieldsArr[] = 'ADR.NAME';
            $sFieldsArr[] = 'ADR.VORNAME';
            $sFieldsArr[] = 'F.DATUM';
            $sFieldsArr[] = 'F.AUFNR';
            $sFieldsArr[] = 'F.ENDPRB';
            $sFieldsArr[] = 'F.ZAHLART';
            $wherePart = '';
            trace($sFieldsArr,0,'$sFieldArr');
            foreach ($sFieldsArr as $fieldname) {
                trace($fieldname,0,'$fieldname');
                trace($wherePart,0,'$wherePart');
                trace($searchText,0,'$searchText');
                if ($fieldname == 'F.ENDPRB') {
                    if (is_numeric($searchText)) {
                        $wherePart .= $wherePart != '' ? ' OR ' : '';
                        $wherePart .= $fieldname . '=' .  str_replace('%','',str_replace(',','.',$searchText));
                    } 
                } else if ($fieldname == 'F.DATUM') {
                    $wherePart .= $wherePart != '' ? ' OR ' : '';
                    $wherePart .= $fieldname . '=' .   $searchText; 
                } else {
                    $wherePart .= $wherePart != '' ? ' OR ' : '';
                    $wherePart .= $searchFunction.$fieldname .$searchFunctionClose. $searchOp .  $searchText; 
                }
            }
            $where .= $wherePart != '' ?' AND ('.$wherePart.')' : ''; 
        } else if ($amount){
            // search only for outstanding amount
            $where .= ' AND F.ENDPRB - F.GUTSUMME - F.BEZSUMME = '.floatval($amount);
        }

        $orderBy = 'AUFTRAGOK, DATUM, AUFNR';
        return $this->selectArray($select, $from, $where, '', $orderBy, '');
        
    }

    /**
     * Returns all invoices 
     *
     * @param   void  
     * @return  array   Invoices  
     * @author  Dorit Rottne$outstandingItemsr <rottner@punkt.de>
     * @since   2007-10-17
     */
    public function selectAllInvoices() {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'ADR.NAME as lastname, ADR.VORNAME as firstname, '.
                   'F.DATUM as date, F.NUMMER as docId , F.AUFNR as relatedDocNo, '.
                   'F.ZAHLART as paymentMethod,F.ENDPRB as amountGross, F.ENDPRN as amountNet, '.
                   'F.GUTSUMME as credit , F.BEZSUMME as payment, F.SKONTOBETRAG as discount,'.
                   'F.AUFTRAGOK as ok';
        $from    = $this->getTableName('FSCHRIFT').' AS F'. 
                   ' INNER JOIN '.$this->getTableName('ADRESSE').' AS ADR ON ADR.NUMMER = F.ADRINR ';
        $where   = 'F.ERFART = '.$this->gsaDbObj->fullQuoteStr('04RE',$from);
        $groupBy = '';
        $orderBy = 'AUFTRAGOK, DATUM, AUFNR';
        $limit = '';
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        return $this->selectArray($select, $from, $where, '', $orderBy, '');
        
    }


    /**
     * Returns one FPOS (=position) records of a specified NUMMER (=uid) record from the GSA database.
     *
     * @param   integer     NUMMER (=uid) of the related FPOS record (FPOS.NUMMER)
     * @return  array       array of FPOS position records
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-07
     */
    public function selectTransactionDocPos($fposId) {
        
        // query preparation
        $select  = '*';
        $from    = $this->getTableName('FPOS');
        $where   = 'NUMMER = '.intval($fposId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false || $this->gsaDbObj->sql_num_rows($res) == 0) {
            throw new tx_pttools_exception('Query failed or returned empty result', 1, $this->gsaDbObj->sql_error());
        } 
            
        return $this->selectArray($select, $from, $where, '', $orderBy, '');
    }  
     

    /**
     * Returns the Cancellation from a given predecessor transaction document number (ERP: "Vorgangsnummer")
     *
     * @param   string      the predecessor transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgaengervorgangsnummer") to check for its Cancellations
     * @return  double      The amount for Cancellation from the given predecessor document 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-15
     */
    public function selectCancellationAmount($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'ENDPRB as amount';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'ERFART = '.$this->gsaDbObj->fullQuoteStr('06ST',$from).
                   # So steht es in GSA Auftrag wenn verbucht
                   'AND ALTERFART = "04RE" AND AUFTRAGOK=1 AND GEBUCHT=1 '.
                   'AND ALTAUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo, $from);
        
        $amount = $this->selectAmount($select, $from, $where);
        return $amount;
        
    }

    
    /**
     * Returns the amount of CreditMeno from a given predecessor transaction document number (ERP: "Vorgangsnummer")
     *
     * @param   string      the predecessor transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgaengervorgangsnummer") to check for its Cancellations
     * @return  double      The amount for CreditMemo from the given predecessor document 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-15
     */
    public function selectCreditMemoAmount($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'ENDPRB as amount';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'ERFART = '.$this->gsaDbObj->fullQuoteStr('05GU',$from).
                   # So steht es in GSA Auftrag wenn verbucht
                   'AND ALTERFART = '.$this->gsaDbObj->fullQuoteStr('04RE',$from). ' AND AUFTRAGOK=1 AND GEBUCHT=1 '.
                   'AND ALTAUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo, $from);
        
        $amount = $this->selectAmount($select, $from, $where);
        return $amount;
        
    }
    

    /**
     * Returns the amount of CreditMeno from a given customer
     *
     * @param   integer     uid of customer in gsa
     * @param   boolean     flag if only free creditMemos are handled
     * @return  double      The amount for CreditMemo from the given predecessor document 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-22
     */
    public function selectCreditMemoCustomerAmount($gsaUid,$free) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'SUM(ENDPRB)-SUM(GUTSCHRIFTZAHLUNG) as amount';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'ERFART = '.$this->gsaDbObj->fullQuoteStr('05GU',$from).
                   ' AND AUFTRAGOK=1 AND GEBUCHT=1 '.
                   ' AND ADRINR = '.intval($gsaUid);
        if ($free == true) {        
            $where.=  ' AND ALTNUMMER is NULL';
        }
        $amount = $this->selectAmount($select, $from, $where);
        return $amount;
    }


    /**
     * Returns an array of free credits for this customer
     *
     * @param   integer     uid of customer in gsa
     * @return  array       of transactionDocumentd for free Credits 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-28
     */
    public function selectFreeCredits($gsaUid) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'AUFNR';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = '1'.
                    ' AND ERFART = '.$this->gsaDbObj->fullQuoteStr('05GU',$from).
                    ' AND (ENDPRB - GUTSCHRIFTZAHLUNG > 0 OR GUTSCHRIFTZAHLUNG is NULL)'. 
                    ' AND AUFTRAGOK=1 AND GEBUCHT=1 '.
                    ' AND ALTNUMMER IS NULL '.
                    ' AND ADRINR = '.intval($gsaUid);
        $groupBy  = '';
        $orderBy  = '';
        $limit    = '';

        $idArr = array();
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            $relatedDocArr[] = $a_row['AUFNR'];
        }
        $this->gsaDbObj->sql_free_result($res);
        
        
        trace($relatedDocArr,0,'$relatedDocArr'); 
        return $relatedDocArr;
    }

    /**
     * Returns all continued Documents for an given doocument Number  
     *
     * @param   string  related Number of Document Record in GSA  
     * @return  array   Continued Documents  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-05-05
     */
    public function selectContinuedDocuments($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        trace($relatedDocNo,0,'$relatedDocNo');
        // query preparation
        $select  = 'NUMMER as number, ERFART as documentType, ENDPRB as amountGross, DATUM as date';
        $from    = $this->getTableName('FSCHRIFT'); 
        $where   = 'ALTAUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo,$from);
        $groupBy = '';
        $orderBy = 'NUMMER';
        $limit = '';
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        if ($this->gsaDbObj->sql_num_rows($res) > 0) {
            while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
                // if enabled, do charset conversion of all non-binary string data 
                $a_result[] = $a_row;
            }
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
                   

    }

    
    /**
     * Returns date from last payment
     *
     * @param   string      Payment Method 
     * @param   integer     uid of customer in gsa
     * @return  string      Date of Last Payment in tbale ZAHLUNG for this costomer or for the shop if no uid given
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-28
     */
    public function selectLastPaymentDate($paymentMethod, $gsaUid=0) {
        trace('METHOD] '.__METHOD__);
        

        // query preparation
        $select  = 'MAX(Z.DATUM) as date ';
        $from    = $this->getTableName('ZAHLUNG').' as Z, '.$this->getTableName('FSCHRIFT').' as F,'.$this->getTableName('ADRESSE').' as A';
        $where   = ' Z.AUFINR = F.NUMMER '.
                    ' AND AUSGUTSCHRIFTEN = 0'.
                    ' AND Z.BETRAG > 0';
        if (intval($gsaUid)>0) {
            $where .= 
                    ' AND A.NUMMER = F.ADRINR '.
                    ' AND F.ADRINR = '.intval($gsaUid);
        }
        if ($paymentMethod) {
            $where .= ' AND F.ZAHLART = '.$this->gsaDbObj->fullQuoteStr($this->paymentMethodToErp($paymentMethod),$from);
        }
        $groupBy  = '';
        $orderBy  = '';
        $limit    = '';
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res); 
        $date = $a_row['date'];
        $this->gsaDbObj->sql_free_result($res);
        return $date;
    }
    
    /**
     * Returns the amount of Shipping from a given predecessor transaction document number (ERP: "Vorgangsnummer")
     *
     * @param   string      the predecessor transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgaengervorgangsnummer") to check for its Cancellations
     * @return  double      The amount for CreditMemo from the given predecessor document 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-25
     */
    public function selectShippingAmount($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        
        // query preparation
        // FSCHRIFT.FLDN01 --> Versandkosten–Komp. 1: (default: „Porto“) -> Dieses Feld wird vom Shop vorerst für die gesamte Versandkostenpauschale verwendet!.6.0, Stand 11.08.2006)!
        $select  = 'FLDN01  as amount';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'ERFART = '.$this->gsaDbObj->fullQuoteStr('05GU',$from).
                   # So steht es in GSA Auftrag wenn verbucht
                   ' AND ALTERFART = '.$this->gsaDbObj->fullQuoteStr('04RE',$from).
                   ' AND ALTAUFNR = '.$this->gsaDbObj->fullQuoteStr($relatedDocNo, $from);
        
        $amount = $this->selectAmount($select, $from, $where);
        return $amount;
        
    }
    
    /**
     * Returns the amount of a given select, where clause
     *
     * @param   string      select clause
     * @param   string      name of table handled by Query
     * @param   string      where clause
     * @return  double      The amount specified in  select clause
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-02
     */
    public function selectAmount($select, $from, $where, $groupBy = '', $orderBy= '', $limit = '') {
        trace('METHOD] '.__METHOD__);
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace($this->gsaDbObj->sql_error(),0,'$this->gsaDbObj->sql_error()');
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            $amount += floatval($a_row['amount']);
        }
        $this->gsaDbObj->sql_free_result($res);
        if (!$amount) {
            $amount = 0;
        }
        
        
        trace($amount,0,'$amount'); 
        return $amount;
        
    }
    
    
    /**
     * Returns array of database Records 
     *
     * @param   string  select part of query  
     * @param   string  Table name to select  
     * @param   string  where part of query  
     * @param   string  group by part of query  
     * @param   string  limit part of query  
     * @param   string  order by part of query  
     * @return  array   array of Dtabase Records  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-28
     */
    public function selectArray ($select, $from, $where,  $groupBy='', $orderBy, $limit='')
    {
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        if ($this->gsaDbObj->sql_num_rows($res) > 0) {
            while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
                // if enabled, do charset conversion of all non-binary string data 
                if ($this->charsetConvEnabled == 1) {
                    $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
                }
                if ( $a_row['paymentMethod']) {
                    $a_row['paymentMethod'] = $this->paymentMethodFromErp($a_row['paymentMethod']);
                }
                if ( $a_row['documentType']) {
                    $a_row['documentType'] = $this->documentTypeFromErp($a_row['documentType']);
                }
                $a_result[] = $a_row;
            }
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
    }


    /**
     * Stores the data of the erpDocument Object via gsaShopTransactionAccessorObj
     *
     * @param   array   data to store        
     * @return  void  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-23
     */
    public function storeErpDocument($dataArr) {
        trace('METHOD] '.__METHOD__);
        tx_pttools_assert::isNotEmpty($dataArr['relatedDocNo'], array('message' => __METHOD__.': No valid erpDocument documentNumber: '.$dataArr['relatedDocNo'].'!'));
        
        $erpDocUpdateFieldsArr = array(); 
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $erpDocUpdateFieldsArr['ENDPRB']        = $dataArr['amountGross'];
        $erpDocUpdateFieldsArr['ENDPRN']        = $dataArr['amountNet'];
        $erpDocUpdateFieldsArr['GUTSUMME']      = $dataArr['credit'];
        $erpDocUpdateFieldsArr['BEZSUMME']      = $dataArr['payment'];
        $erpDocUpdateFieldsArr['GESRAB']        = $dataArr['totalDiscountPercent'];
        $erpDocUpdateFieldsArr['GESRABTYPE']    = $dataArr['totalDiscountType'];
        $erpDocUpdateFieldsArr['SKONTOBETRAG']  = $dataArr['discount'];
        $erpDocUpdateFieldsArr['AUFTRAGOK']     = $dataArr['ok'];
        $erpDocUpdateFieldsArr['MKZ']           = $dataArr['dunningLevel'];
        $erpDocUpdateFieldsArr['LMAHNGEB']      = $dataArr['dunningCharge'];
        $erpDocUpdateFieldsArr['MAHNDATUM']     = $dataArr['dunningDate'];
        $erpDocUpdateFieldsArr['MAHNFRIST']     = $dataArr['dunningDueDate'];
        
        $gsaShopTransactionAccessorObj->updateTransactionDocument($dataArr['relatedDocNo'], $erpDocUpdateFieldsArr);
        
    }
    
    
    /**
     * Returns the next Number for SchriftNr
     *
     * @param   void        
     * @return  integer     next Number for field SCHRIFTNR from table  FSCHRIFT
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-07
     */
    public function getNextSchriftnr() {
        trace('METHOD] '.__METHOD__);
        
        return $this->getNextNumber($this->getTableName('VORGANG'));
        
    }
    
    /**
     * Returns the Payment Method for acounting 
     *
     * @param   string  paymentMethod for ERP       
     * @return  string  paymentMethod for accounting
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-22
     */
    public function paymentMethodFromErp($erpPaymentMethod) {
        trace('METHOD] '.__METHOD__);
    	switch ($erpPaymentMethod) {
            case self::PM_INVOICE:
            	$paymentMethod = 'bt';
            	break; 
            case self::PM_DEBIT:
                $paymentMethod = 'dd'; 
                break; 
            case self::PM_CCARD:
                $paymentMethod = 'cc'; 
                break; 
    	}
        trace($paymentMethod.' '.$erpPaymentMethod,0,'return payment Method');
    	return $paymentMethod;
        
    }
    
/**
     * Returns the Payment Method for acounting 
     *
     * @param   string  paymentMethod for accounting        
     * @return  string  paymentMethod for ERP
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-22
     */
    public function paymentMethodToErp($paymentMethod) {
        trace('METHOD] '.__METHOD__);
        switch ($paymentMethod) {
            case 'bt':
                $erpPaymentMethod = self::PM_INVOICE;
                break; 
            case 'dd':
                $erpPaymentMethod = self::PM_DEBIT; 
                break; 
            case 'cc':
                $erpPaymentMethod = self::PM_CCARD; 
                break; 
        }
        return $erpPaymentMethod;
        
    }

    /**
     * Returns the Document Type for acounting 
     *
     * @param   string  Document Type for ERP       
     * @return  string  Document Type for accounting
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-23
     */
    public function documentTypeFromErp($erpDocumentType) {
        trace('METHOD] '.__METHOD__);
        switch ($erpDocumentType) {
            case '04RE':
                $documentType = 'invoice';
                break; 
            case '05GU':
                $documentType = 'credit'; 
                break; 
            case '06ST':
                $documentType = 'cancellation'; 
                break; 
        }
        trace($documentType.' '.$erpDocumentType,0,'return documentType');
        return $documentType;
    }
    
    
    /**
     * Returns the Document Type for acounting 
     *
     * @param   string  Document Type for ERP       
     * @return  string  Document Type for accounting
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-23
     */
    public function documentTypeToErp($documentType) {
        trace('METHOD] '.__METHOD__);
        switch ($documentType) {
            case 'invoice':
                $erpDocumentType = '04RE';
                break; 
            case 'credit':
                $erpDocumentType = '05GU'; 
                break; 
            case 'cancellation':
                $erpDocumentType = '06ST'; 
                break; 
        }
        trace($documentType.' '.$erpDocumentType,0,'return documentType');
        return $erpDocumentType;
    }
    
    /**
     * Returns the Select part for erpDocument 
     *
     * @param   void  Document Type for ERP       
     * @return  string  Select Clause for ERP Document 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2008-09-23
     */
    protected function getSelectClauseErpDocument() {
        trace('METHOD] '.__METHOD__);
        $select  = 'F.ADRINR as customerId, '.
                   'F.AUFTRAGOK as ok, F.DATUM as date, F.NUMMER as docId , F.AUFNR as relatedDocNo, '.
                   'F.ZAHLART as paymentMethod, F.ERFART as documentType, '.
                   'F.ENDPRB as amountGross, F.ENDPRN as amountNet,'. 
                   'F.GUTSUMME as credit , F.BEZSUMME as payment, F.SKONTOBETRAG as discount, '. 
                   'F.TAGNETTO as duePeriod, '.
                   'F.GESRAB as totalDicountPercent, '.
                   'F.GESRABTYPE as totalDicountType, '.
                   'F.MKZ as dunningLevel, F.LMAHNGEB AS dunningCharge, '.'
                    F.MAHNDATUM as dunningDate, F.MAHNFRIST as dunningDueDate';
        return $select;
    }
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php']);
}

?>
