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
 * Database accessor class for tx_ptgsaaccounting_orderCreditBalance of the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-12-04
 */ 

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern

 
/**
 * contract Database Accessor class 
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-12-04
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_orderCreditBalanceAccessor  implements tx_pttools_iSingleton {
    /*
     * Contansts
     */
    const DB_TABLE = 'tx_ptgsaaccounting_orderCreditBalance';
    
    /**
     * Properties
     */

    private static $uniqueInstance = NULL; // (tx_kbbimport_gsaaccountingAccessor object) Singleton unique instance
    

    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    

    /**
     * Class constructor: prefills the object's properies depending on given params
     *
     * @param   void 
     * @return  void   
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */

    private function __construct () {
    }

    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsaaccounting_orderCreditBalanceAccessor      unique instance of the object (Singleton) 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        return self::$uniqueInstance;
        
    }
    
    /**
     * Final method to prevent object cloning (using 'clone'), in order to use only the unique instance of the Singleton object.
     * @param   void
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    public final function __clone() {
        
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }
    


    /***************************************************************************
     *   GETTER & SETTER METHODS
     **************************************************************************/

    /***************************************************************************
     *   Business METHODS
     **************************************************************************/

    /**
     * Method to store Record in Database
     * @param   array    containd data to be stored in Database
     * @return  void 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function storeOrderCreditBalanceData($dataArr) {
        trace('[METHOD] '.__METHOD__);
        $row = array (
               'uid' => $dataArr['uid'],
               'gsa_uid' => intval($dataArr['gsaUid']),
               'deleted' => (boolean)$dataArr['deleted'],
               'order_wrapper_uid' => intval($dataArr['orderWrapperUid']),
               'related_doc_no' => (string)$dataArr['relatedDocNo'],
               'book_date' => (string)$dataArr['bookDate'],
               'amount_invoice' => (double)$dataArr['amountInvoice'],
               'amount_customer' => (double)$dataArr['amountCustomer'],
               'reserved' => (boolean)$dataArr['reserved'],
               'booked' => (boolean)$dataArr['booked'],
        );

        if (intval($dataArr['uid']) == 0) {
            $row = tx_pttools_div::expandFieldValuesForQuery( $row, true, 1);
            $dataArr['uid'] = $this->insertRecord($row);
        } else {
            $row = tx_pttools_div::expandFieldValuesForQuery($row);
            $this->updateRecord($row);
        }
        return $dataArr['uid'];
    }

 
    /**
     * Method to insert Record in Database
     * @param  array    contains data to insert
     * @return integer  uid after Insert statement 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function insertRecord($fieldsArr) {
        trace('[METHOD] '.__METHOD__);

        trace($fieldsArr,0,'$fieldsArr');
		$fieldsArr = tx_pttools_div::expandFieldValuesForQuery($fieldsArr, true, intval($GLOBALS['TSFE']->tmpl->setup['config.']['pt_gsaaccounting.']['accountingDataSysfolderPid']));
        $result = $GLOBALS['TYPO3_DB']->exec_INSERTquery(self::DB_TABLE, $fieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], self::DB_TABLE, $fieldsArr));
        if ($result == false) {
            throw new tx_pttools_exception('Error in Insert pt_gsaaccounting', 1);
        }
        trace ('result nach insert');
        trace ($result);
        
        return $GLOBALS['TYPO3_DB']->sql_insert_id();
    }


    /**
     * Method to update Record in Database
     * @param   array  which contains the Data to be updated
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function updateRecord($fieldsArr) {
        trace('[CMD] '.__METHOD__);
        $where = 'uid ='.intval($fieldsArr['uid']);
		$fieldsArr = tx_pttools_div::expandFieldValuesForQuery($fieldsArr);
        $result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(self::DB_TABLE, $where, $fieldsArr);
        trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], self::DB_TABLE, $where, $fieldsArr));
        if ($result == false) {
            throw new tx_pttools_exception('Error in Update pt_gsaacounting_orderCreditBalance', 1);
        }
    }


    /**
     * Select orderCreditBalance Data from Database Record
     * @param   integer uid of database Record
     * @return  array   array of database record fields
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function selectOrderCreditbalanceByUid($uid) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $where   = 'uid = '.intval($uid);
        
        return $this->selectRecord($where);
        
    }

    /**
     * Select credit Balance reserved by Customer not including the current order
     * @param   integer uid of database Record
     * @return  array   array of database record fields
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function selectReservedCreditBalanceCustomer($gsaUid,$orderWrapperUid) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $select = 'sum(amount_invoice)';
        $from = self::DB_TABLE;
        $where   = 'gsa_uid = '.intval($gsaUid). ' AND reserved = true AND booked = false'. tx_pttools_div::enableFields($from) ;
        if ($orderWrapperUid) {
            $where .= ' AND order_wrapper_uid != '.intval($orderWrapperUid);
        }
        $groupBy = '';
        $orderBy = ''; 
        $limit = '';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);        
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        trace($a_row,0,'$a_row');
        return $a_row['sum(amount_invoice)'];
        
    }

    /**
     * Select orderCreditBalance Data from Database Record
     * @param   integer uid of database Record
     * @return  array   array of database record fields
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function selectOrderCreditbalanceByOrderWrapperUid($orderWrapperUid) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $where   = 'order_wrapper_uid = '.intval($orderWrapperUid);
        
        return $this->selectRecord($where);
        
    }

    /**
     * Select orderCreditBalance Data from Database Record by given realted Document Number
     * @param   string Related Document Number from GSA
     * @return  array  array of database record fields 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    
    public function selectOrderCreditbalanceByRelatedDocNo($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $from = self::DB_TABLE;
        $where   = 'related_doc_no = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($relatedDocNo, $from);
        
        return $this->selectRecord($where);
        
    }

    /**
     * Method to select record by given Parameters
     * @param   string  where part of query  
     * @param   string  group by part of query  
     * @param   string  order by part of query  
     * @param   string  limit part of query  
     * @return array    record as array
     * @global TYPO3_DB     
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-12-04
     */
    public function selectRecord($where, $groupBy='', $orderBy='', $limit='' ) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $select  = 
            'uid AS uid'.
            ', gsa_uid AS gsaUid'.
            ', order_wrapper_uid AS orderWrapperUid'.
            ', related_doc_no AS relatedDocNo'.
            ', book_date AS bookDate'.
            ', amount_invoice AS amountInvoice'.
            ', amount_customer AS amountCustomer'.
            ', reserved AS reserved'. 
            ', booked AS booked'. 
        '';
        $from    = self::DB_TABLE;

        trace($select,0,'$select');
        trace($where,0,'$where');
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);        
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $a_row = array();
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 1) {
            $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_row,0,'orderCreditBalanceArray'); 
        return $a_row;
    }



}    

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_orderCreditBalanceAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_orderCreditBalanceAccessor.php']);
}
?>
