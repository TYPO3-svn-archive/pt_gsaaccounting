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
 * Database accessor class for GSA px_DTABUCH of the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author  Dorit Rottner <rottner@punkt.de>
 * @since   2007-07-31
 */ 

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_gsaDbAccessor.php'; // parent class for all GSA database accessor classes
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern

 
/**
 * dtabuchrel Database Accessor class 
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-07-31
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_dtabuchAccessor  extends tx_ptgsasocket_gsaDbAccessor implements tx_pttools_iSingleton {
    /*
     * Contansts
     */
    const DB_TABLE = 'px_DTABUCH';
    
    /**
     * Properties
     */

    private static $uniqueInstance = NULL; // (tx_ptgsaaccounting_dtabuchAccessor object) Singleton unique instance
    

    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    

    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsaaccounting_dtabuchAccessor      unique instance of the object (Singleton) 
     * @access  public     
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-07-31
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        return self::$uniqueInstance;
        
    }


    /***************************************************************************
     *   GETTER & SETTER METHODS
     **************************************************************************/

    /***************************************************************************
     *   Business METHODS
     **************************************************************************/
    /**
     * Select Complete Dtabuch Data from Databse Record
     * @param   integer uid of database Record
     * @param   boolean sendnewsletter Flag for feuser
     * @param   string  username store in relics
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-07-31
     */
    
    public function selectDtabuchByUid($uid) {
        trace('METHOD] '.__METHOD__);
        // query preparation
        $table = $this->getTableName(self::DB_TABLE);
        $select  = 
            $table.'.NUMMER AS dtauid'.
            ','.$table.'.NAME AS name'.
            ','.$table. '.NAME2 AS name2'.
            ','.$table. '.BLZ AS bankCode'.
            ','.$table. '.KONTO AS bankAccount'.
            ','.$table. '.TYP AS type'.
            ','.$table. '.BETRAG AS bookingSum'.
            ','.$table. '.ZWECK AS purpose'.
            ','.$table. '.ZWECK2 AS purpose2'.
            ','.$table. '.PROGRAMM AS program'.
            ','.$table. '.DATUM AS bookingDate'.
            ','.$table. '.FAELLIG AS dueDate'.
            ','.$table. '.EURO AS isEuro'.
        '';
        $from    = $table;
        $where = 'NUMMER ='.intval($uid);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        trace($select,0,'$select');
        trace($where,0,'$where');
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row,0,'DtabuchArray'); 
        return $a_row;
        
    }


    /**
     * Returns an array with Id of all DTABUCH records 
     *
     * @param   integer     Uid of last transfered DTABUCH entry
     * @param   string      type of DTA Record could be 'Einzug' or 'Abbuchung'
     * @param   boolean     use dueDate for where clause
     * @return  array       array with all DTABUCH record IDs 
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-03
     */
    public function getDtabuchIdArr($dtaUid = 0, $type='', $useDueDate = true)
    {
        $dtabuchIdArr = array();

        // get all DTABUCH ID  
        $select = 'NUMMER';
        $from = $this->getTableName(self::DB_TABLE);
        $where = ' NUMMER > '.intval($dtaUid);
        if ($type == true) {
            $where .= ' AND TYP = '.$this->gsaDbObj->fullQuoteStr($type, $from);
        }
        if ($useDueDate == true) {
            $where .= ' AND FAELLIG <= '.$this->gsaDbObj->fullQuoteStr(date('Y-m-d'),$from);
        }
        $groupBy = '';
        $orderBy = 'NUMMER';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === FALSE) {
            throw new tx_pttools_exception('Query failed', 2, $this->gsaDbObj->sql_error());
        }
        while ($row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            $dtabuchIdArr[] = $row['NUMMER'];
        }
        $this->gsaDbObj->sql_free_result($res);

        trace($dtabuchIdArr);
        return $dtabuchIdArr;
    }

    /**
     * Inserts a data carrier exchange (ERP: "DTA/Datenträgeraustausch") record into the GSA DB table 'px_DTABUCH' and returns the inserted record's UID.
     * 
     * This method requires the database structure of paradox file 'DTABUCH.DB' to be imported into GSA MySQL-DB as 'px_DTABUCH'. This may be done e.g. by importing the MySQL dump pt_gsashop/doc/px_DTABUCH.sql.
     * 
     * @param   array    contains data to be stored in Database
     * @return  integer     UID of the inserted record ('px_DTABUCH.NUMMER')
     * @throws  tx_pttools_exception   if the target table does not exist
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-23
     */
    public function storeDtabuchData($dataArr) {
        $table = $this->getTableName(self::DB_TABLE);
        // check existance of target table
        if (tx_pttools_div::dbTableExists($table, $this->gsaDbObj) == false) {
            throw new tx_pttools_exception('Required database table does not exist.', 1, 
                                           'Database table '.$table.' does not exist in the GSA database - it has to be inserted manually by using pt_gsasocket/doc/'.$table.'.sql');
        }

        $row = array (
               'NAME' => $dataArr['name'],      
               'NAME2' => $dataArr['name2'],      
               'BLZ' => $dataArr['bankCode'],      
               'KONTO' => $dataArr['bankAccount'],      
               'ZWECK' => $dataArr['purpose'],      
               'ZWECK2' => $dataArr['purpose2'],      
               'BETRAG' => $dataArr['amount'],      
               'TYP' => $dataArr['type'],      
               'PROGRAMM' => $dataArr['program'], 
               'DATUM' => $dataArr['bookingDate'],      
               'FAELLIG' => $dataArr['dueDate'],      
                #$row['BUCHDAT'] = NULL. // date ### TODO: ??? do not uncomment until used with a value since NULL will result in an '0000-00-00' entry for the date field type
                #$row['DISKID']  = NULL, // date ### TODO: ???
                #$row['MEHRZWECK'] = NULL, // blob  ###TODO: ???
               'EURO'  => 1,
        );

        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $row = tx_pttools_div::iconvArray($row, $this->siteCharset, $this->gsaCharset);
        }
        
        if (intval($dataArr['uid']) == 0) {
            $dataArr['uid'] = $this->insertRecord($row);
        } else {
            $row['NUMMER'] = $dataArr['uid'];
            $this->updateRecord($row);
        }
        return $dataArr['uid'];

        
    }

    /**
     * Method to insert Record in Database
     * @param  array    contains data to insert
     * @return integer  uid after Insert statement 
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-23
     */
    
    public function insertRecord($row) {
        trace('[METHOD] '.__METHOD__);

        trace($row,0,'$row');
        $table = $this->getTableName(self::DB_TABLE);
        $result = $this->gsaDbObj->exec_INSERTquery($table, $row);
        if ($result == false) {
            throw new tx_pttools_exception('Error in Insert '.$table, 1);
        }
        $lastInsertedId = $this->gsaDbObj->sql_insert_id();
        
        trace($lastInsertedId); 
        return $lastInsertedId;
    }


    /**
     * Method to update Record in Database
     * @param   array   which contains the Data to be updated
     * @return  integer uid of updated Record 
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-11-23
     */
    
    public function updateRecord($row) {
        trace('[METHOD] '.__METHOD__);

        $table = $this->getTableName(self::DB_TABLE);
        $where = 'NUMMER ='.intval($row['NUMMER']);

        trace($row,0,'$row');
        $result = $this->gsaDbObj->exec_UPDATEquery($table, $where, $row);
        if ($result == false) {
            throw new tx_pttools_exception('Error in Update '.$table, 1);
        }
        
        return $row['NUMMER'];
    }



}    

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchAccessor.php']);
}
?>
