<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007 Rainer Kuhn (kuhn@punkt.de)
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
 * Command Line Interface List Generator for GSA transactions
 *
 * $Id$
 *
 * @author	Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-08-28
 */ 



/**
 * Inclusion of external resources
 */
require_once 'Console/Getopt.php';  // PEAR Console_Getopt: parsing params as options (see http://pear.php.net/manual/en/package.console.console-getopt.php). This requires the PEAR module to be installed on your server and the path to PEAR being part of your include path.
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_cliHandler.php'; // CLI handler class with general CLI methods
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class

/**
 * Inclusion of extension resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';



/**
 * Command Line Interface Class for generating transaction volume lists from the GSA database
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-08-20
 * @package     TYPO3
 * @subpackage  tx_ptgsaaccounting
 */
class tx_ptgsaaccounting_listgenerator {

    /**
     * Class properties
     */
    protected $extKey = 'pt_gsaaccounting'; // (string) the extension key.
    protected $scriptName = 'tx_ptgsaaccounting_listgenerator'; // (string) this script's name
    protected $extConfArr = array(); // (array) basic extension configuration data from localconf.php (configurable in Extension Manager) 
    
    protected $cliHandler; // (object of type tx_pttools_cliHandler) CLI handler class with general CLI methods
    
    protected $shortOptions = ''; // (string) short options for Console_Getopt  (see http://pear.php.net/manual/en/package.console.console-getopt.intro-options.php)
    protected $longOptionsArr = array(); // (array) long options for Console_Getopt (see http://pear.php.net/manual/en/package.console.console-getopt.intro-options.php)
    protected $helpString = ''; // (string) help string
    
    protected $docTypesList = ''; // (string) Comma seperated list of GSA doctype abbreviations: '04RE', '05GU' and/or '06ST'
    protected $startDate = ''; // (string) start date for transaction list in format YYYY-MM-DD
    protected $endDate = ''; // (string) end date  for transaction list in format YYYY-MM-DD
    protected $ignoreDiscounts = false; // (boolean) flag whether discounts (ERP: "Skonto") should be ignored
    
    
    
    
    /***************************************************************************
    *   CONSTRUCTOR & RUN METHOD
    ***************************************************************************/
    
    /**
     * Class constructor: define CLI options, set class properties 
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-08-23
     */
    public function __construct() {
            
            // for TYPO3 3.8.0+: enable storage of last built SQL query in $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery for all query building functions of class t3lib_DB
            $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
            
            // define command line options
            $this->shortOptions = 'hd:f:t:i'; // (string) short options for Console_Getopt  (see http://pear.php.net/manual/en/package.console.console-getopt.intro-options.php)
            $this->longOptionsArr = array('help', 'doctypes=', 'from=', 'to=', 'ignore'); // (array) long options for Console_Getopt (see http://pear.php.net/manual/en/package.console.console-getopt.intro-options.php)
            $this->helpString = "Availabe options:\n".
                                "-h/--help      Help: this list of available options\n".
                                "-d/--doctypes  Comma seperated list of GSA doctype abbreviations (required):\n".
                                "               e.g. '04RE', '05GU', '06ST', '04RE,05GU,06ST', ...\n".
                                "-f/--from      start date for transaction list in format 'YYYY-MM-DD' (optional)\n".
                                "-t/--to        end date  for transaction list in format 'YYYY-MM-DD' (optional)\n".
                                "-i/--ignore    ignore discounts [ERP: 'Skonto'] (optional)\n".
                                "\n";
            
            // start script output
            echo "\n".
                 "---------------------------------------------------------------------\n".
                 "CLI List Generator for GSA Transactions initialized...\n".
                 "---------------------------------------------------------------------\n";
                
            // get extension configuration configured in Extension Manager (from localconf.php) - NOTICE: this has to be placed *before* the first call of $this->cliHandler->cliMessage()!!
            $this->extConfArr = tx_pttools_div::returnExtConfArray($this->extKey);
            if (!is_array($this->extConfArr)) {
                fwrite(STDERR, "[ERROR] No extension configuration found!\nScript terminated.\n\n");
                die();
            }
            
            // invoke CLI handler with extension configuration
            $this->cliHandler = new tx_pttools_cliHandler($this->scriptName, 
                                                          $this->extConfArr['cliAdminEmailRecipient'],
                                                          $this->extConfArr['cliHostName'],
                                                          $this->extConfArr['cliQuietMode'],
                                                          $this->extConfArr['cliEnableLogging'],
                                                          $this->extConfArr['cliLogDir']
                                                         );
            $this->cliHandler->cliMessage('Script initialized', false, 2, true); // start new audit log entry
            $this->cliHandler->cliMessage('$this->extConfArr = '.print_r($this->extConfArr, 1));
            
            // dev only
            #fwrite(STDERR, "[TRACE] died: STOP \n\n"); die();
            
    }
    
    /**
     * Run method of the CLI class: executes the business logic
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-08-20
     */
    public function run() {
        
        try {
            
            $this->processOptions();
            $this->cliHandler->cliMessage($this->getTransactionList(), false, 1);
            
            
        } catch (tx_pttools_exception $excObj) {
            
            // if an exception has been catched, handle it and display error message
            $this->cliHandler->cliMessage($excObj->__toString()."\n".$excObj->returnTraceString(), true, 1);
            
        }
        
    }
    
    
    
    /***************************************************************************
    *   BUSINESS LOGIC METHODS
    ***************************************************************************/
    
    /** 
     * Processes the command line arguments as options and sets the resulting class properties
     *
     * @param   void
     * @return  void       
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-08-22
     */
    protected function processOptions() {
        
        $console = new Console_Getopt;  // PEAR module (see http://pear.php.net/manual/en/package.console.console-getopt.php)
        $parsedOptionsArr = $this->cliHandler->getOptions($console, $this->shortOptions, $this->helpString, $this->longOptionsArr, true);
        
        // evaluate options, set properties
        for ($i=0; $i<sizeOf($parsedOptionsArr); $i++) {
            if ($parsedOptionsArr[$i][0] == 'h' || $parsedOptionsArr[$i][0] == '--help') {
                die($this->helpString);
            }
            if ($parsedOptionsArr[$i][0] == 'd' || $parsedOptionsArr[$i][0] == '--doctypes') {
                $this->docTypesList = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 'f' || $parsedOptionsArr[$i][0] == '--from') {
                $this->startDate = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 't' || $parsedOptionsArr[$i][0] == '--to') {
                $this->endDate = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 'i' || $parsedOptionsArr[$i][0] == '--ignore') {
                $this->ignoreDiscounts = true;
            }
        }
        
        // check for option errors
        if (strlen($this->docTypesList) < 2) {
            $this->cliHandler->cliMessage("Invalid doc type '".$this->docTypesList."'!\nSee help (option -h) for available options.", true, 1);
        }
            
    }
    
    /** 
     * Returns a list of requested transactions depending on given command line arguments
     *
     * @param   void
     * @return  string      list of requested transactions depending on given command line arguments
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-08-20
     */
    protected function getTransactionList() {
        
        $totalNet = 0.0;
        $totalGross = 0.0;
        $totalTax = 0.0;
        $precision = 2;
        $substractTransactionArr = array('05GU', '06ST'); // array of GSA transaction abbreviations to substract from total sums
        $transactionList = 
            "Search for: '".$this->docTypesList."' from: '".$this->startDate."' to: '".$this->endDate."'\n".
            "---------------------------------------------------------------------\n";
        $transactionsArr = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance()->selectTransactionList($this->docTypesList, $this->startDate, $this->endDate);
                
        if (is_array($transactionsArr) && count($transactionsArr) > 0) {
            
            $transactionList .= 
                "Date        ERP Doc No.            Sum net          Tax     Sum gross\n".
                "---------------------------------------------------------------------\n";
                
            foreach ($transactionsArr as $transactionDataArr) {
                
                $tax = bcsub($transactionDataArr['ENDPRB'], $transactionDataArr['ENDPRN'], $precision);
                $sumTax   = (in_array($transactionDataArr['ERFART'], $substractTransactionArr) ? bcmul(-1, $tax, $precision) : $tax); // -1 fur subtraction from total sum
                $sumNet   = (in_array($transactionDataArr['ERFART'], $substractTransactionArr) ? bcmul(-1, $transactionDataArr['ENDPRN'], $precision) : $transactionDataArr['ENDPRN']); // -1 fur subtraction from total sum
                $sumGross = (in_array($transactionDataArr['ERFART'], $substractTransactionArr) ? bcmul(-1, $transactionDataArr['ENDPRB'], $precision) : $transactionDataArr['ENDPRB']); // -1 fur subtraction from total sum
                
                $transactionList .= $transactionDataArr['DATUM']."  ".
                                    $transactionDataArr['AUFNR']." ".
                                    sprintf("%".(8+$precision).".".$precision."f", $sumNet)." ".$this->extConfArr['currencyCode'].
                                    sprintf("%".(7+$precision).".".$precision."f", $sumTax)." ".$this->extConfArr['currencyCode'].
                                    sprintf("%".(8+$precision).".".$precision."f", $sumGross)." ".$this->extConfArr['currencyCode'].
                                    "\n";
                                    
                $totalNet   = bcadd($totalNet, $sumNet, $precision);
                $totalTax   = bcadd($totalTax, $sumTax, $precision);
                $totalGross = bcadd($totalGross, $sumGross, $precision);
                
                // check for given discounts (ERP: "Skonto")
                if ($this->ignoreDiscounts == false && ($transactionDataArr['ERFART'] == '04RE' && $transactionDataArr['SKONTOBETRAG'] > 0)) {
                    
                    $discountGross = bcmul(-1, $transactionDataArr['SKONTOBETRAG'], $precision); // -1 fur subtraction from total sum
                    $discountNet   = bcmul(bcdiv($transactionDataArr['ENDPRN'], $transactionDataArr['ENDPRB'], $precision), $discountGross, $precision); // (totalNet / totalGross) * discountGross
                    $discountTax   = bcsub($discountGross, $discountNet, $precision);
                    
                    $transactionList .= "    incl. Discount (Skonto) ".
                                        sprintf("%".(8+$precision).".".$precision."f", $discountNet)." ".$this->extConfArr['currencyCode'].
                                        sprintf("%".(7+$precision).".".$precision."f", $discountTax)." ".$this->extConfArr['currencyCode'].
                                        sprintf("%".(8+$precision).".".$precision."f", $discountGross)." ".$this->extConfArr['currencyCode'].
                                        "\n";
                                        
                    $totalNet   = bcadd($totalNet, $discountNet, $precision);
                    $totalTax   = bcadd($totalTax, $discountTax, $precision);
                    $totalGross = bcadd($totalGross, $discountGross, $precision);
                }
                
            }
            
            $transactionList .= 
                "=====================================================================\n".
                "Totals sums:                ".
                sprintf("%".(8+$precision).".".$precision."f", $totalNet)." ".$this->extConfArr['currencyCode'].
                sprintf("%".(7+$precision).".".$precision."f", $totalTax)." ".$this->extConfArr['currencyCode'].
                sprintf("%".(8+$precision).".".$precision."f", $totalGross)." ".$this->extConfArr['currencyCode']."\n";
            
        } else {
            $transactionList .= 
                "No matching records found.\n";
        }
        
        $transactionList .= 
                "=====================================================================\n";
        
        return $transactionList;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/cronmod/class.tx_ptgsaaccounting_listgenerator.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/cronmod/class.tx_ptgsaaccounting_listgenerator.php']);
}

?>