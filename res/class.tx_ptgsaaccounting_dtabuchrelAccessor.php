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
 * Database accessor class for tx_ptgsaaccounting_dtabuchrel of the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-08
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
 * @since       2007-08-08
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_dtabuchrelAccessor  implements tx_pttools_iSingleton {
    /*
     * Contansts
     */
    const DB_TABLE = 'tx_ptgsaaccounting_dtabuchrel';
    
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
     * @since   2007-08-08
     */

    private function __construct () {
    }

    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsaaccounting_dtabuchrelAccessor      unique instance of the object (Singleton) 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-25
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
     * @since   2007-09-27
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
     * Returns an array with Id of all dtabuchrel records with given filename
     *
     * @param   string      type 'book' or 'reprocess'
     * @param   string      DTA-filename only needed for type 'book'
     * @param   boolean     use notBooked for where clause only needed for type 'book'
     * @return  array       array with all dtabuchrel record IDs 
     * @throws  tx_pttools_exception if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-11
     */
    public function getDtabuchrelIdArr($type='', $filename = '', $useBookDate = true )
    {
        trace('[METHOD] '.__METHOD__);
        trace($filename,0,'$filename');    
        $dtabuchIdArr = array();

        // get all DTABUCH ID  
        $select = 'uid as dtabuchrelUid';
        $from = self::DB_TABLE;
        $where = '1';
        if ($type == 'book' && $useBookDate == true) {
            $where .= " AND  gsa_book_date = ''".
                      " AND transfer_date != '' ".
                      " AND bank_rejected != '1' ".
                      " AND transfer_amount > 0";
        }
        if ($type == 'book' && $filename) {
            $where .= " AND  filename = ".$GLOBALS['TYPO3_DB']->fullQuoteStr($filename, $from);
        }
        if ($type == 'reprocess') {
            $where .= " AND  reprocess = '1'";
        }
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        
        if ($res === FALSE) {
            throw new tx_pttools_exception('Query failed', 2, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $dtabuchrelIdArr = array();
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $dtabuchrelIdArr[] = $row['dtabuchrelUid'];
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($dtabuchrelIdArr);
        return $dtabuchrelIdArr;
    }


    /**
     * Method to store Record in Database
     * @param   array   contains data to be stored in Database
     * @return  integer id of stored database Record
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-14
     */
    
    public function storeDtabuchrelData($dataArr) {
        trace('[METHOD] '.__METHOD__);
        $row = array (
               'uid' => $dataArr['uid'],
               'gsa_dtabuch_uid' => $dataArr['gsaDtabuchUid'],
               'bank_code' => (string)$dataArr['bankCode'],
               'account_no' => (string)$dataArr['accountNo'],
               'account_holder' => (string)$dataArr['accountHolder'],
               'related_doc_no' => (string)$dataArr['relatedDocNo'],
               'purpose' => (string)$dataArr['purpose'],
               'transfer_date' => (string)$dataArr['transferDate'],
               'invoice_date' => (string)$dataArr['invoiceDate'],
               'due_date' => (string)$dataArr['dueDate'],
               'book_date' => (string)$dataArr['bookDate'],
               'gsa_book_date' => (string)$dataArr['gsaBookDate'],
               'bank_rejected' => (boolean)$dataArr['bankRejected'],
               'reprocess' => (boolean)$dataArr['reprocess'],
               'bankaccount_check' => (boolean)$dataArr['bankaccountCheck'],
               'drop_user' => (boolean)$dataArr['dropUser'],
               'booking_amount' => (double)$dataArr['bookingAmount'],
               'transfer_amount' => (double)$dataArr['transferAmount'],
               'difference_amount' => (double)$dataArr['differenceAmount'],
               'invoice_ok_gsa' => (boolean)$dataArr['invoiceOkGsa'],
               'filename' => (string)$dataArr['filename'],
               'type' => (string)$dataArr['type'],
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
     * @since   2007-08-08
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
     * @since   2007-08-08
     */
    
    public function updateRecord($fieldsArr) {
        trace('[CMD] '.__METHOD__);
        $where = 'uid ='.intval($fieldsArr['uid']);
		$fieldsArr = tx_pttools_div::expandFieldValuesForQuery($fieldsArr);
        $result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(self::DB_TABLE, $where, $fieldsArr);
        if ($result == false) {
            throw new tx_pttools_exception('Error in Update pt_gsaacounting_dtabuchrel', 1);
        }
    }

    /**
    /**
     * Updates all dtabuchrel Record with filename with new filename
     *
     * @param   string filename which is already stored in dtabuchrel Record and will beupdate     
     * @param   string new filename for dtabuchrel Record         
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-14
     */
    
    public function updateFilename($filename, $newFilename) {
        trace('[METHOD] '.__METHOD__);
        if (!$filename) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' does not exist');
        }
        
        if (!$newFilename) {
            throw new tx_pttools_exception('Parameter error', 3, 'Second parameter for '.__CLASS__.' does not exist');
        }

        $row['filename'] = $newFilename;
        $where = 'filename ='.$GLOBALS['TYPO3_DB']->fullQuoteStr($filename, self::DB_TABLE);
        trace($row,0,'$row');
        trace($where,0,'$where');
        $result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(self::DB_TABLE, $where, $row);
        if ($result == false) {
            throw new tx_pttools_exception('Error in Update pt_gsaacounting_dtabuchrel ', 1);
        }
    }

    /**
     * Look if Record exist in database
     * @param   integer uid of database Record
     * @return  booelan record esist or not
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-08
     */
    
    public function exist($gsa_dtaBich_Uid) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $where   = 'uid = '.intval($gsa_dtaBich_Uid);
        $select  = 
            'uid AS uid'.
        '';
        $from    = self::DB_TABLE;
        $groupBy = '';
        $orderBy = '';
        $limit = '';

        trace($select,0,'$select');
        trace($where,0,'$where');
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);        trace(tx_pttools_div::returnLastBuiltSelectQuery($this->gsaDbObj, $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_row,0,'DtabuchrelArray'); 
        if ($a_row['uid']) {
            $found = true;
        } else {
            $found = false;
        }
        return $found;
        
        
    }

    /**
     * Select greatest uid from GSA DTABUCH 
     * @param   string  type of DTA Record could be 'Einzug' or 'Abbuchung'
     * @return  integer Last Id from GSA with this type
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-14
     */
    
    public function selectLastGsaUid($type) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $select  = 
            'max(gsa_dtabuch_uid)'.
        '';
        $from    = self::DB_TABLE;
        $where   = 'type ='.$GLOBALS['TYPO3_DB']->fullQuoteStr($type, self::DB_TABLE);
        $orderBy = ''; 
        $groupBy = ''; 
        $limit   = '';
        trace('exec_SELECTquery');
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);        
        trace('vor returnLastBuiltSelectQuery');
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        trace('nach returnLastBuiltSelectQuery');
        
        
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        return $a_row['max(gsa_dtabuch_uid)'];
        
    }

    /**
     * Select last Tranfer Date from DtabuchRel
     * @param   void
     * @return  string Last transferDate from DTABUCHREL in MySqlFormat
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-27
     */
    
    public function selectLastTransferDate() {
        trace('METHOD] '.__METHOD__);
        $select  = 
            'max(transfer_date)'.
        '';
        $from    = self::DB_TABLE;
        $where   = '1';
        $orderBy = ''; 
        $groupBy = ''; 
        $limit   = '';
        trace('exec_SELECTquery');
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);        
        trace('vor returnLastBuiltSelectQuery');
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        trace('nach returnLastBuiltSelectQuery');
        
        
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        return $a_row['max(transfer_date)'];
    }

    /**
     * Select Dtabuchrel Data from Database Record
     * @param   integer uid of database Record
     * @return  array   array of database record fields
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-08
     */
    
    public function selectDtabuchrelByUid($uid) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $where   = 'uid = '.intval($uid);
        
        return $this->selectRecord($where);
        
    }

    /**
     * Select Dtabuchrel Data from Database Record by given realted Document Number
     * @param   string Related Document Number 
     * @return  array  array of database record fields 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-10-19
     */
    
    public function selectDtabuchrelByRelatedDocNo($relatedDocNo) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $from = self::DB_TABLE;
        $where   = 'related_doc_no like '.$GLOBALS['TYPO3_DB']->fullQuoteStr($relatedDocNo.'%', $from);
        
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
     * @since   2007-08-08
     */
    public function selectRecord($where, $groupBy='', $orderBy='', $limit='' ) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $select  = 
            'uid AS uid'.
            ', gsa_dtabuch_uid AS gsaDtabuchUid'.
            ', bank_code AS bankCode'.
            ', account_no AS accountNo'.
            ', account_holder AS accountHolder'.
            ', related_doc_no AS relatedDocNo'.
            ', purpose AS purpose'.
            ', filename AS filename'.
            ', invoice_date AS invoiceDate'.
            ', due_date AS dueDate'.
            ', transfer_date AS transferDate'.
            ', book_date AS bookDate'.
            ', gsa_book_date AS gsaBookDate'.
            ', bank_rejected AS bankRejected'.
            ', reprocess AS reprocess'.
            ', bankaccount_check AS bankaccountCheck'.
            ', drop_user AS dropUser'.
            ', booking_amount AS bookingAmount'.
            ', transfer_amount AS transferAmount'.
            ', difference_amount AS differenceAmount'. 
            ', invoice_ok_gsa AS invoiceOkGsa'. 
            ', type AS type'. 
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
        
        trace($a_row,0,'DtabuchrelArray'); 
        return $a_row;
    }



}    

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchrelAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchrelAccessor.php']);
}
?>
