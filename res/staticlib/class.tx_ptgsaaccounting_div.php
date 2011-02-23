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
 * Gsaaccounting helper methods library 
 *
 * $Id$
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-10-15
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Gsaaccounting library class with static helper methods
 *
 * @author      Dorit Rottner <rottner@punkt.de>
 * @since       2007-10-15
 * @package     TYPO3
 * @subpackage  tx_gsaaccounting
 */
class tx_ptgsaaccounting_div  {
    

    /**
     * This function converts a possible German format date input ('d.m.y' or 'd.m.Y') into a MySQL format date('Y-m-d')
     * If the given format is not 'Y-m-d', 'd.m.y' or 'd.m.Y' an empty string is returned
     * 
     * @param   string      date to be converted
     * @return  string      converted date in MySQL Format ('Y-m-d') or empty string  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-14
     */ 
    public static function convertDateToMySqlFormat($date) {
        
        if (!ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $date)){  
            if (ereg ("([0-9]{1,2}).([0-9]{1,2}).([0-9]{4})", $date)) {
                $dateConvertArr = strptime($date, '%d.%m.%Y');
            } else if (ereg ("([0-9]{1,2}).([0-9]{1,2}).([0-9]{2})", $date)) {
                $dateConvertArr = strptime($date, '%d.%m.%y');
            }
            if ($dateConvertArr) {
                trace($dateConvertArr,0,'$dateConvertArr');
                $convertedDate = strftime('%Y-%m-%d', mktime(0, 0, 0, $dateConvertArr['tm_mon']+1, $dateConvertArr['tm_mday'], $dateConvertArr['tm_year']+1900));
            } else {
                $convertedDate = '';
            }
        } else {
          $convertedDate = $date;
        }
        
        trace($convertedDate, 0, '$convertedDate');
        return $convertedDate;
        
    } 
    
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/staticlib/class.tx_ptgsaaccounting_div.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/staticlib/class.tx_ptgsaaccounting_div.php']);
}

?>
