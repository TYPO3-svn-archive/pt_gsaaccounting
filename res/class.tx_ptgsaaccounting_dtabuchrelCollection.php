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
 * dtabuch collection class for the 'pt_gsaaccounting' extension
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2007-08-03
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuch.php';// extension specific address class (dtabuch)

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class



/**
 * GSA dtabuch collection class
 *
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2007-08-03
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_dtabuchrelCollection extends tx_pttools_objectCollection {
    
    /**
     * Properties
     */
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates a collection of dtabuchrel objects. If no parameter is specified all dtabuchrel records are given back
     *
     * @param   string      type 'book' or 'reprocess'
     * @param   string      DTA-filename only needed for type 'book'
     * @param   boolean     use notBooked for where clause only needed for type 'book'
     * @return  void
 	 * @author	Dorit Rottner <rottner@punkt.de>
 	 * @since   2007-09-11
     */
    public function __construct($type ='book', $filename='',$notBooked = true) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        trace($filename,0,'$filename');
		// load collection from database
		$idArr = tx_ptgsaaccounting_dtabuchrelAccessor::getInstance()->getdtabuchrelIdArr($type, $filename,$notBooked = true);
		foreach ($idArr as $dtabuchrelId) {
			$this->addItem(new tx_ptgsaaccounting_dtabuchrel($dtabuchrelId));
		}
    }   
    
    /***************************************************************************
     *   extended collection methods
     **************************************************************************/
    
 
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     

} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchrelCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/res/class.tx_ptgsaaccounting_dtabuchrelCollection.php']);
}

?>
